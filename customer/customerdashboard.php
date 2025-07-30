<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: ../public/login.php");
    exit();
}

// Get user information
$userId = $_SESSION['user_id'];
$userData = getUserById($userId);
if (!$userData) {
    header("Location: ../public/login.php");
    exit();
}

/**
 * Enhanced helper function to get a valid profile image path.
 * Ensures it always fetches from the registration photo directory.
 *
 * @param array $userData The user's data array.
 * @param string $defaultImage The path to the default image.
 * @return string The valid image path.
 */
function get_profile_image_path($userData, $defaultImage) {
    if (!empty($userData['profile_image'])) {
        $possiblePaths = [
            '../assets/uploads/profiles/' . basename($userData['profile_image']),
            $userData['profile_image'],
            '../assets/uploads/profiles/' . str_replace('../assets/uploads/profiles/', '', $userData['profile_image'])
        ];
        foreach ($possiblePaths as $imagePath) {
            if (file_exists($imagePath)) {
                return htmlspecialchars($imagePath);
            }
        }
    }
    return $defaultImage;
}

// Get the total number of posted cars from the "cars" table
$queryCars = "SELECT COUNT(*) AS total FROM cars";
$resultCars = $conn->query($queryCars);
$availableCars = 0;
if ($resultCars && $row = $resultCars->fetch_assoc()) {
    $availableCars = $row['total'];
}

// Handle AJAX requests for profile update, password change, and account deletion...
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $dob = $_POST['date_of_birth'];
        $profileImagePath = $userData['profile_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileExtension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($fileExtension, $allowedExtensions)) {
                $fileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                    if ($userData['profile_image'] && file_exists($userData['profile_image'])) {
                        unlink($userData['profile_image']);
                    }
                    $profileImagePath = $uploadPath;
                }
            }
        }
        if (updateUserProfile($userId, $firstName, $lastName, $email, $phone, $dob, $profileImagePath)) {
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating profile. Please try again.']);
        }
        exit();
    }
    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        if (strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            exit();
        }
        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
            exit();
        }
        if (!password_verify($currentPassword, $userData['password'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
            exit();
        }
        if (updateUserPassword($userId, $newPassword)) {
            echo json_encode(['success' => true, 'message' => 'Password changed successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error changing password. Please try again.']);
        }
        exit();
    }
    if ($action === 'delete_account') {
        if (deleteUser($userId)) {
            session_destroy();
            echo json_encode(['success' => true, 'message' => 'Account deleted successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting account. Please try again.']);
        }
        exit();
    }
}

// Fetch recent bookings for this customer
$customerBookings = [];
try {
    $queryBookings = "
        SELECT b.*, 
               c.car_name, c.year, c.transmission, c.fuel_type, c.mileage, c.people_capacity, 
               c.main_image as car_image, c.price_per_day, b.booking_date,
               b.total_amount, b.booking_status, b.pickup_location, b.pickup_date, b.pickup_time, 
               b.dropoff_date, b.dropoff_time, b.customer_note
        FROM bookings b
        LEFT JOIN cars c ON b.car_id = c.car_id
        WHERE b.user_id = $userId
        ORDER BY b.booking_date DESC
        LIMIT 10
    ";
    $resultBookings = $conn->query($queryBookings);
    if ($resultBookings && $resultBookings->num_rows > 0) {
        while ($booking = $resultBookings->fetch_assoc()) {
            $statusClass = '';
            switch (strtolower($booking['booking_status'])) {
                case 'approved':
                    $statusClass = 'btn-outline-success';
                    break;
                case 'canceled':
                    $statusClass = 'btn-outline-danger';
                    break;
                default:
                    $statusClass = 'btn-outline-warning';
                    break;
            }
            $carImage = !empty($booking['car_image']) ? '../' . $booking['car_image'] : '../assets/images/car-1.jpg';
            $customerBookings[] = [
                'id' => $booking['id'],
                'date' => date('d M Y', strtotime($booking['booking_date'])),
                'car' => [
                    'name' => $booking['car_name'],
                    'transmission' => $booking['transmission'],
                    'fuel' => $booking['fuel_type'],
                    'year' => $booking['year'],
                    'mileage' => $booking['mileage'] . ' km',
                    'capacity' => $booking['people_capacity'] . ' People',
                    'price' => 'GH₵ ' . $booking['price_per_day'] . ' / day',
                    'img' => $carImage
                ],
                'amount' => 'GH₵ ' . $booking['total_amount'],
                'status' => ucfirst($booking['booking_status']),
                'statusBtnClass' => $statusClass,
                'pickupLocation' => $booking['pickup_location'],
                'pickupTime' => $booking['pickup_date'] . ' ' . substr($booking['pickup_time'], 0, 5),
                'dropoffTime' => $booking['dropoff_date'] . ' ' . substr($booking['dropoff_time'], 0, 5),
                'note' => $booking['customer_note'] ?: 'No notes provided.'
            ];
        }
    }
} catch (Exception $e) {
    error_log("Error fetching customer bookings: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>DASHBOARD</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">
    <!-- Favicon -->
    <link href="assets/img/favicon.ico" rel="icon">
    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Libraries Stylesheet -->
    <link href="../assets/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="../assets/lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />
    <!-- Customized Bootstrap Stylesheet -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Template Stylesheet -->
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid position-relative bg-white d-flex p-0">
    <!-- Sidebar Start -->
    <div class="sidebar pe-4 pb-3">
        <nav class="navbar bg-light navbar-light">
            <a href="customerdashboard.php" class="navbar-brand mx-4 mb-3">
                <h3 class="text-primary"><i class="fa fa-hashtag me-2"></i>DASHBOARD</h3>
            </a>
            <div class="d-flex align-items-center ms-4 mb-4">
                <div class="position-relative">
                    <img class="rounded-circle" src="<?php echo get_profile_image_path($userData, '../assets/images/car-5.jpg'); ?>" alt="Profile" style="width: 40px; height: 40px; object-fit: cover;">
                    <div class="bg-success rounded-circle border border-2 border-white position-absolute end-0 bottom-0 p-1"></div>
                </div>
                <div class="ms-3">
                    <h6 class="mb-0"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h6>
                    <span><?php echo ucfirst(htmlspecialchars($userData['user_type'])); ?></span>
                </div>
            </div>
            <div class="navbar-nav w-100">
                <a href="customerdashboard.php" class="nav-item nav-link active">
                    <i class="fa fa-tachometer-alt me-2"></i>Dashboard
                </a>
                <a href="customerbookings.php" class="nav-item nav-link">
                    <i class="fa fa-calendar-check me-2"></i>My Bookings
                </a>
                <a href="customermessage.php" class="nav-item nav-link" id="navMessageLink">
                    <i class="fa fa-envelope me-2"></i>Messages
                    <span id="navUnreadBadge" class="badge bg-danger ms-2" style="display:none;"></span>
                </a>
                <a href="customerform.php" class="nav-item nav-link">
                    <i class="fa fa-paper-plane me-2"></i>Submit Form
                </a>
                <a href="customerprofile.php" class="nav-item nav-link">
                    <i class="fa fa-user-circle me-2"></i>Profile
                </a>
            </div>
        </nav>
    </div>
    <!-- Sidebar End -->

    <!-- Content Start -->
    <div class="content">
        <!-- Navbar Start -->
        <nav class="navbar navbar-expand bg-light navbar-light sticky-top px-4 py-0">
            <a href="customerdashboard.php" class="navbar-brand d-flex d-lg-none me-4">
                <h2 class="text-primary mb-0"><i class="fa fa-hashtag"></i></h2>
            </a>
            <a href="#" class="sidebar-toggler flex-shrink-0">
                <i class="fa fa-bars"></i>
            </a>
            <form class="d-none d-md-flex ms-4">
                <input class="form-control border-0" type="search" placeholder="Search">
            </form>
            <div class="navbar-nav align-items-center ms-auto">

                <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                        <img class="rounded-circle me-lg-2" src="<?php echo get_profile_image_path($userData, '../assets/images/car-1.jpg'); ?>" alt="Profile" style="width: 40px; height: 40px; object-fit: cover;">
                        <span class="d-none d-lg-inline-flex"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end bg-light border-0 rounded-0 rounded-bottom m-0">
                        <a href="../Both/homepage.php" class="dropdown-item">Home Page</a>
                        <a href="../public/logout.php" class="dropdown-item">Log Out</a>
                    </div>
                </div>
            </div>
        </nav>
        <!-- Navbar End -->

        <!-- Sale & Revenue Start -->
        <div class="container-fluid pt-4 px-4">
            <div class="row g-4">
                <!-- Available Cars Widget -->
                <div class="col-sm-6 col-xl-3">
                    <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
                        <i class="fa fa-car fa-3x text-primary"></i>
                        <div class="ms-3">
                            <p class="mb-2">Available Cars</p>
                            <h6 class="mb-0" id="availableCarsCount"><?php echo $availableCars; ?></h6>
                        </div>
                    </div>
                </div>
                <!-- Total Booked Cars By User -->
                <div class="col-sm-6 col-xl-3">
                    <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
                        <i class="fa fa-car-side fa-3x text-primary"></i>
                        <div class="ms-3">
                            <p class="mb-2">Booked Cars</p>
                            <h6 class="mb-0" id="myBookedCarsCount"></h6>
                        </div>
                    </div>
                </div>
                <!-- My Bookings Widget -->
                <div class="col-sm-6 col-xl-3">
                    <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
                        <i class="fa fa-calendar-check fa-3x text-primary"></i>
                        <div class="ms-3">
                            <p class="mb-2">My Bookings</p>
                            <h6 class="mb-0" id="myBookingsCount"></h6>
                        </div>
                    </div>
                </div>
                <!-- Total Spent Widget -->
                <div class="col-sm-6 col-xl-3">
                    <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
                        <i class="fa fa-money-bill-wave fa-3x text-primary"></i>
                        <div class="ms-3">
                            <p class="mb-2">Total Spent</p>
                            <h6 class="mb-0" id="totalSpent"></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Sale & Revenue End -->

        <!-- My Recent Bookings Start -->
        <div class="container-fluid pt-4 px-4">
            <div class="bg-light text-center rounded p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h6 class="mb-0">My Recent Bookings</h6>
                    <div class="input-group" style="max-width: 300px;">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input type="text" class="form-control" id="recentBookingsSearchInput" placeholder="Search bookings..." onkeyup="searchRecentBookings()">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table text-start align-middle table-bordered table-hover mb-0" id="recentBookingsTable">
                        <thead>
                        <tr class="text-dark">
                            <th scope="col"><input class="form-check-input" type="checkbox"></th>
                            <th scope="col">Date</th>
                            <th scope="col">Car Name</th>
                            <th scope="col">Amount</th>
                            <th scope="col">Status</th>
                        </tr>
                        </thead>
                        <tbody id="bookingTableBody">
                        <!-- Booking rows will be generated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- My Recent Bookings End -->

        <script>
            // Pass customer bookings data from PHP to JavaScript
            const myBookings = <?php echo json_encode($customerBookings); ?>;

            // Search functionality for recent bookings
            function searchRecentBookings() {
                const searchTerm = document.getElementById('recentBookingsSearchInput').value.toLowerCase();
                const filteredBookings = myBookings.filter(b => {
                    return b.car.name.toLowerCase().includes(searchTerm) ||
                        b.status.toLowerCase().includes(searchTerm) ||
                        b.date.toLowerCase().includes(searchTerm) ||
                        b.amount.toLowerCase().includes(searchTerm);
                });
                renderBookingsTable(filteredBookings);
            }

            // Update dashboard widgets with customer booking information
            function updateCustomerDashboardWidgets() {
                const bookedCarNames = [...new Set(myBookings.map(b => b.car.name))];
                document.getElementById('myBookedCarsCount').textContent = bookedCarNames.length;
                document.getElementById('myBookingsCount').textContent = myBookings.length;
                // Only include approved bookings in the total spent sum; if none, set to zero.
                const approvedBookings = myBookings.filter(b => b.status.toLowerCase() === 'approved');
                const spent = approvedBookings.length > 0
                    ? approvedBookings.map(b => parseFloat(b.amount.replace(/[^0-9.]/g, ''))).reduce((a, b) => a + b, 0)
                    : 0;
                document.getElementById('totalSpent').textContent = "GH₵" + spent.toFixed(2);
            }
            updateCustomerDashboardWidgets();

            // Render the bookings table without an "Action" column.
            function renderBookingsTable(bookingsToRender = null) {
                const bookingsData = bookingsToRender || myBookings;
                const tbody = document.getElementById('bookingTableBody');
                tbody.innerHTML = '';
                bookingsData.forEach((b, i) => {
                    tbody.innerHTML += `
                        <tr>
                            <td><input class="form-check-input" type="checkbox"></td>
                            <td>${b.date}</td>
                            <td>${b.car.name}</td>
                            <td>${b.amount}</td>
                            <td>
                                <button class="btn ${b.statusBtnClass} btn-sm" disabled>${b.status}</button>
                            </td>
                        </tr>
                    `;
                });
                if (bookingsData.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center">No bookings found matching your search.</td></tr>';
                }
            }
            renderBookingsTable();

            document.addEventListener('DOMContentLoaded', function() {
                var calendarEl = document.getElementById('calendar');
                if (calendarEl) {
                    var events = myBookings.map(b => ({
                        title: b.car.name,
                        start: b.pickupTime.split(' ')[0],
                        end: b.dropoffTime.split(' ')[0]
                    }));
                    var calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        height: 380,
                        events: events
                    });
                    calendar.render();
                }
            });
        </script>



        <!-- Footer Start -->
        <div class="container-fluid pt-4 px-4">
            <div class="bg-light rounded-top p-4">
                <div class="row">
                    <!-- Footer content can be added here -->
                </div>
            </div>
        </div>
        <!-- Footer End -->
    </div>
    <!-- Content End -->

    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
</div>

<!-- JavaScript Libraries -->
<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/lib/chart/chart.min.js"></script>
<script src="../assets/lib/easing/easing.min.js"></script>
<script src="../assets/lib/waypoints/waypoints.min.js"></script>
<script src="../assets/lib/owlcarousel/owl.carousel.min.js"></script>
<script src="../assets/lib/tempusdominus/js/moment.min.js"></script>
<script src="../assets/lib/tempusdominus/js/moment-timezone.min.js"></script>
<script src="../assets/lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>
<script>
    function updateUnreadBadges() {
        fetch("get_unread_counts.php")
            .then(response => response.json())
            .then(data => {
                document.querySelectorAll("#userListMsg .user-list-item").forEach(item => {
                    const senderId = item.getAttribute("data-id");
                    const badge = item.querySelector(".unread-badge");
                    const userNameSpan = item.querySelector(".user-name");
                    if (data[senderId] && data[senderId] > 0) {
                        badge.textContent = data[senderId];
                        badge.style.display = "inline-block";
                        if (userNameSpan) userNameSpan.style.fontWeight = "bold";
                    } else {
                        badge.textContent = "";
                        badge.style.display = "none";
                        if (userNameSpan) userNameSpan.style.fontWeight = "normal";
                    }
                });
                let totalUnread = Object.values(data).reduce((a, b) => a + b, 0);
                const navBadge = document.getElementById("navUnreadBadge");
                if (totalUnread > 0) {
                    navBadge.textContent = totalUnread;
                    navBadge.style.display = "inline-block";
                    navBadge.style.backgroundColor = "green";
                } else {
                    navBadge.textContent = "";
                    navBadge.style.display = "none";
                }
            })
            .catch(error => console.error("Error fetching unread counts:", error));
    }
    setInterval(updateUnreadBadges, 15000);
    document.addEventListener("DOMContentLoaded", () => {
        updateUnreadBadges();
    });
</script>

<!-- Template Javascript -->
<script src="../assets/js/dashboard.js"></script>
</body>
</html>