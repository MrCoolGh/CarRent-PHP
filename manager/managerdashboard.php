<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'manager') {
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

// Fetch total cars, customers, bookings, and revenue from the database
$totalCars = 0;
$totalCustomers = 0;
$totalBookings = 0;
$totalRevenue = 0;

try {
    // Query for total cars
    $carsQuery = "SELECT COUNT(*) AS total_cars FROM cars";
    $carsResult = $conn->query($carsQuery);
    if ($carsResult && $carsRow = $carsResult->fetch_assoc()) {
        $totalCars = $carsRow['total_cars'];
    }

    // Query for total customers
    $customersQuery = "SELECT COUNT(*) AS total_customers FROM users WHERE user_type = 'customer'";
    $customersResult = $conn->query($customersQuery);
    if ($customersResult && $customersRow = $customersResult->fetch_assoc()) {
        $totalCustomers = $customersRow['total_customers'];
    }

    // Query for total bookings
    $bookingsQuery = "SELECT COUNT(*) AS total_bookings FROM bookings";
    $bookingsResult = $conn->query($bookingsQuery);
    if ($bookingsResult && $bookingsRow = $bookingsResult->fetch_assoc()) {
        $totalBookings = $bookingsRow['total_bookings'];
    }

    // Query for total revenue (sum of approved bookings' total amounts)
    $revenueQuery = "SELECT SUM(total_amount) AS total_revenue FROM bookings WHERE booking_status = 'approved'";
    $revenueResult = $conn->query($revenueQuery);
    if ($revenueResult && $revenueRow = $revenueResult->fetch_assoc()) {
        $totalRevenue = $revenueRow['total_revenue'] ? $revenueRow['total_revenue'] : 0;
    }
} catch (Exception $e) {
    error_log("Error fetching statistics: " . $e->getMessage());
}

// Check if user is logged in and is admin (repeated for safety)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'manager') {
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

// Fetch recent bookings from database (your existing code)
$recentBookings = [];
try {
    $bookingsQuery = "
        SELECT b.*, 
               c.car_name, c.year, c.transmission, c.fuel_type, c.mileage, c.people_capacity, c.main_image as car_image, c.price_per_day,
               u.first_name, u.last_name, u.email, u.phone, u.profile_image
        FROM bookings b
        LEFT JOIN cars c ON b.car_id = c.car_id
        LEFT JOIN users u ON b.user_id = u.id
        ORDER BY b.booking_date DESC
        LIMIT 10
    ";

    $bookingResult = $conn->query($bookingsQuery);

    if ($bookingResult && $bookingResult->num_rows > 0) {
        while ($booking = $bookingResult->fetch_assoc()) {
            $statusClass = '';
            switch($booking['booking_status']) {
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

            $profileImage = '../assets/images/blog-1.jpg'; // Default image
            if (!empty($booking['profile_image'])) {
                $possiblePaths = [
                    '../assets/uploads/profiles/' . basename($booking['profile_image']),
                    $booking['profile_image'],
                    '../assets/uploads/profiles/' . $booking['profile_image']
                ];

                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $profileImage = $path;
                        break;
                    }
                }
            }

            $recentBookings[] = [
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
                'customer' => [
                    'name' => $booking['first_name'] . ' ' . $booking['last_name'],
                    'phone' => $booking['phone'],
                    'img' => $profileImage
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
    error_log("Error fetching bookings: " . $e->getMessage());
}

// Fetch all users from database for "Recent Users" table
$recentUsers = [];
try {
    $usersQuery = "SELECT * FROM users ORDER BY id DESC LIMIT 10";
    $userResult = $conn->query($usersQuery);
    if ($userResult && $userResult->num_rows > 0) {
        while ($user = $userResult->fetch_assoc()) {
            // Profile image handling
            $profileImg = '../assets/images/blog-5.jpg'; // Default
            if (!empty($user['profile_image'])) {
                $possiblePaths = [
                    '../assets/uploads/profiles/' . basename($user['profile_image']),
                    $user['profile_image'],
                    '../assets/uploads/profiles/' . $user['profile_image']
                ];
                foreach ($possiblePaths as $imgPath) {
                    if (file_exists($imgPath)) {
                        $profileImg = $imgPath;
                        break;
                    }
                }
            }
            $recentUsers[] = [
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'status' => ucfirst($user['user_type']),
                'img' => $profileImg,
                'dob' => isset($user['dob']) ? $user['dob'] : '',
            ];
        }
    }
} catch (Exception $e) {
    error_log("Error fetching users: " . $e->getMessage());
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
    <link href="assets/img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="../assets/lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>

<body>
<div class="container-fluid position-relative bg-white d-flex p-0">
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

    <!-- Sidebar Start -->
    <div class="sidebar pe-4 pb-3">
        <nav class="navbar bg-light navbar-light">
            <a href="managerdashboard.php" class="navbar-brand mx-4 mb-3">
                <h3 class="text-primary"><i class="fa fa-hashtag me-2"></i>DASHBOARD</h3>
            </a>
            <div class="d-flex align-items-center ms-4 mb-4">
                <div class="position-relative">
                    <?php
                    // Enhanced profile image handling for sidebar
                    $imageFound = false;
                    if (!empty($userData['profile_image'])) {
                        $possiblePaths = [
                            '../assets/uploads/profiles/' . basename($userData['profile_image']),
                            $userData['profile_image'],
                            '../assets/uploads/profiles/' . $userData['profile_image']
                        ];

                        foreach ($possiblePaths as $imagePath) {
                            if (file_exists($imagePath)) {
                                echo '<img class="rounded-circle" src="' . htmlspecialchars($imagePath) . '" alt="Profile" style="width: 40px; height: 40px;">';
                                $imageFound = true;
                                break;
                            }
                        }
                    }

                    if (!$imageFound): ?>
                        <img class="rounded-circle" src="../assets/images/blog-2.jpg" alt="" style="width: 40px; height: 40px;">
                    <?php endif; ?>
                    <div class="bg-success rounded-circle border border-2 border-white position-absolute end-0 bottom-0 p-1"></div>
                </div>
                <div class="ms-3">
                    <h6 class="mb-0"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h6>
                    <span><?php echo ucfirst(htmlspecialchars($userData['user_type'])); ?></span>
                </div>
            </div>
            <div class="navbar-nav w-100">
                <a href="managerdashboard.php" class="nav-item nav-link active">
                    <i class="fa fa-tachometer-alt me-2"></i>Dashboard
                </a>
                <a href="managerbookings.php" class="nav-item nav-link ">
                    <i class="fa fa-calendar-check me-2"></i>Bookings
                </a>
                <a href="managermessage.php" class="nav-item nav-link" id="navMessageLink">
                    <i class="fa fa-envelope me-2"></i>Messages
                    <span id="navUnreadBadge" class="badge bg-danger ms-2" style="display:none;"></span>
                </a>
                <a href="managerform.php" class="nav-item nav-link">
                    <i class="fa fa-paper-plane me-2"></i>Forms
                </a>
                <a href="managermanagecars.php" class="nav-item nav-link">
                    <i class="fa fa-car-side me-2"></i>Manage Cars
                </a>
                <a href="managermanageusers.php" class="nav-item nav-link">
                    <i class="fa fa-users-cog me-2"></i>Manage Users
                </a>
                <a href="managerprofile.php" class="nav-item nav-link">
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
            <a href="managerdashboard.php" class="navbar-brand d-flex d-lg-none me-4">
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
                        <?php
                        // Enhanced profile image handling for navbar
                        $navImageFound = false;
                        if (!empty($userData['profile_image'])) {
                            $possiblePaths = [
                                '../assets/uploads/profiles/' . basename($userData['profile_image']),
                                $userData['profile_image'],
                                '../assets/uploads/profiles/' . $userData['profile_image']
                            ];

                            foreach ($possiblePaths as $imagePath) {
                                if (file_exists($imagePath)) {
                                    echo '<img class="rounded-circle me-lg-2" src="' . htmlspecialchars($imagePath) . '" alt="Profile" style="width: 40px; height: 40px;">';
                                    $navImageFound = true;
                                    break;
                                }
                            }
                        }

                        if (!$navImageFound): ?>
                            <img class="rounded-circle me-lg-2" src="../assets/images/blog-4.jpg" alt="" style="width: 40px; height: 40px;">
                        <?php endif; ?>
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
                <div class="col-sm-6 col-xl-3">
                    <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
                        <i class="fa fa-car fa-3x text-primary"></i>
                        <div class="ms-3">
                            <p class="mb-2">Total Cars</p>
                            <h6 class="mb-0"><?php echo $totalCars; ?></h6>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
                        <i class="fa fa-users fa-3x text-primary"></i>
                        <div class="ms-3">
                            <p class="mb-2">Total Customers</p>
                            <h6 class="mb-0"><?php echo $totalCustomers; ?></h6>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
                        <i class="fa fa-book fa-3x text-primary"></i>
                        <div class="ms-3">
                            <p class="mb-2">Total Bookings</p>
                            <h6 class="mb-0"><?php echo $totalBookings; ?></h6>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
                        <i class="fa fa-chart-pie fa-3x text-primary"></i>
                        <div class="ms-3">
                            <p class="mb-2">Total Revenue</p>
                            <h6 class="mb-0">GH₵ <?php echo number_format($totalRevenue, 2); ?></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Sale & Revenue End -->

        <!-- Recent Bookings Start -->
        <div class="container-fluid pt-4 px-4">
            <div class="bg-light text-center rounded p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h6 class="mb-0">Recent Bookings</h6>
                    <div class="input-group" style="max-width: 300px;">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input type="text" class="form-control" id="bookingsSearchInput" placeholder="Search bookings..." onkeyup="searchBookings()">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table text-start align-middle table-bordered table-hover mb-0" id="recentBookingsTable">
                        <thead>
                        <tr class="text-dark">
                            <th scope="col"><input class="form-check-input" type="checkbox"></th>
                            <th scope="col">Date</th>
                            <th scope="col">Car Name</th>
                            <th scope="col">Customer</th>
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
        <!-- Recent Bookings End -->

        <script>
            function updateUnreadNavBadge() {
                fetch('fetch_unread_total.php')
                    .then(res => res.text())
                    .then(count => {
                        count = parseInt(count, 10);
                        let badge = document.getElementById('navUnreadBadge');
                        if (!badge) return;
                        badge.style.display = count > 0 ? 'inline-block' : 'none';
                        badge.textContent = count;
                    });
            }
            window.addEventListener('DOMContentLoaded', updateUnreadNavBadge);
            // Database bookings data passed from PHP to JavaScript
            const bookings = <?php echo json_encode($recentBookings); ?>;
            let modalBookingIdx = null;
            let deleteBookingId = null;

            // Search functionality for bookings
            function searchBookings() {
                const searchTerm = document.getElementById('bookingsSearchInput').value.toLowerCase();
                const tbody = document.getElementById('bookingTableBody');
                tbody.innerHTML = '';

                const filteredBookings = bookings.filter(b => {
                    return b.car.name.toLowerCase().includes(searchTerm) ||
                        b.customer.name.toLowerCase().includes(searchTerm) ||
                        b.status.toLowerCase().includes(searchTerm) ||
                        b.date.toLowerCase().includes(searchTerm);
                });

                filteredBookings.forEach((b, i) => {
                    const originalIndex = bookings.findIndex(booking => booking.id === b.id);
                    tbody.innerHTML += `
        <tr>
            <td><input class="form-check-input" type="checkbox"></td>
            <td>${b.date}</td>
            <td>${b.car.name}</td>
            <td>${b.customer.name}</td>
            <td>${b.amount}</td>
            <td>
                <button class="btn ${b.statusBtnClass} btn-sm" disabled>${b.status}</button>
            </td>
        </tr>
        `;
                });
            }
            function renderBookingsTable() {
                const tbody = document.getElementById('bookingTableBody');
                tbody.innerHTML = '';
                bookings.forEach((b, i) => {
                    tbody.innerHTML += `
        <tr>
            <td><input class="form-check-input" type="checkbox"></td>
            <td>${b.date}</td>
            <td>${b.car.name}</td>
            <td>${b.customer.name}</td>
            <td>${b.amount}</td>
            <td>
                <button class="btn ${b.statusBtnClass} btn-sm" disabled>${b.status}</button>
            </td>
        </tr>
        `;
                });
            }
            window.addEventListener('DOMContentLoaded', renderBookingsTable);
        </script>
        <!-- Recent Bookings End -->



        <!-- Recent Customers Start -->
        <div class="container-fluid pt-4 px-4">
            <div class="bg-light text-center rounded p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h6 class="mb-0">Recent Users</h6>
                    <div class="input-group" style="max-width: 300px;">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input type="text" class="form-control" id="usersSearchInput" placeholder="Search users..." onkeyup="searchUsers()">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table text-start align-middle table-bordered table-hover mb-0">
                        <thead>
                        <tr class="text-dark">
                            <th scope="col">Profile</th>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Phone</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                        </thead>
                        <tbody id="usersTableBody">
                        <?php foreach ($recentUsers as $user): ?>
                            <?php if (strtolower($user['status']) !== 'admin'): ?>
                                <tr>
                                    <td>
                                        <img class="rounded-circle" src="<?php echo htmlspecialchars($user['img']); ?>" alt="" style="width: 40px; height: 40px;">
                                    </td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = 'btn-outline-primary';
                                        if (strtolower($user['status']) === 'manager') $statusClass = 'btn-outline-warning';
                                        ?>
                                        <button class="btn <?php echo $statusClass; ?> btn-sm" disabled><?php echo htmlspecialchars($user['status']); ?></button>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="showUserModal('<?php echo htmlspecialchars(addslashes($user['name'])); ?>', '<?php echo htmlspecialchars(addslashes($user['email'])); ?>', '<?php echo htmlspecialchars(addslashes($user['phone'])); ?>', '<?php echo htmlspecialchars(addslashes($user['status'])); ?>', '<?php echo htmlspecialchars(addslashes($user['dob'])); ?>', '<?php echo htmlspecialchars(addslashes($user['img'])); ?>')">Detail</button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Recent Customers End -->

        <script>
            const usersData = <?php echo json_encode($recentUsers); ?>;
            function searchUsers() {
                const searchTerm = document.getElementById('usersSearchInput').value.toLowerCase();
                const tbody = document.getElementById('usersTableBody');
                tbody.innerHTML = '';
                const filteredUsers = usersData.filter(user => {
                    return user.name.toLowerCase().includes(searchTerm) ||
                        user.email.toLowerCase().includes(searchTerm) ||
                        user.phone.toLowerCase().includes(searchTerm) ||
                        user.status.toLowerCase().includes(searchTerm);
                });
                filteredUsers.forEach(user => {
                    const statusClass = user.status.toLowerCase() === 'customer'
                        ? 'btn-outline-success'
                        : user.status.toLowerCase() === 'manager'
                            ? 'btn-outline-warning'
                            : 'btn-outline-primary';
                    tbody.innerHTML += `
                        <tr>
                            <td>
                                <img class="rounded-circle" src="${user.img}" alt="" style="width: 40px; height: 40px;">
                            </td>
                            <td>${user.name}</td>
                            <td>${user.email}</td>
                            <td>${user.phone}</td>
                            <td>
                                <button class="btn ${statusClass} btn-sm" disabled>${user.status}</button>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="showUserModal('${user.name.replace(/'/g, "\\'")}', '${user.email.replace(/'/g, "\\'")}', '${user.phone.replace(/'/g, "\\'")}', '${user.status.replace(/'/g, "\\'")}', '${user.dob ? user.dob.replace(/'/g, "\\'") : ''}', '${user.img.replace(/'/g, "\\'")}')">Detail</button>
                            </td>
                        </tr>
                    `;
                });
            }
            function showUserModal(name, email, phone, status, dob, img) {
                document.getElementById('modalUserImg').src = img;
                document.getElementById('modalUserName').textContent = name;
                document.getElementById('modalUserEmail').textContent = email;
                document.getElementById('modalUserPhone').textContent = phone;
                document.getElementById('modalUserType').textContent = status;
                document.getElementById('modalUserDob').textContent = dob;
                var modal = new bootstrap.Modal(document.getElementById('userDetailModal'));
                modal.show();
            }
        </script>
        <!-- User Detail Modal -->
        <div class="modal fade" id="userDetailModal" tabindex="-1" aria-labelledby="userDetailModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="userDetailModalLabel">User Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img id="modalUserImg" class="rounded-circle mb-3" src="" style="width:80px;height:80px;" alt="User Picture">
                        <h6 id="modalUserName"></h6>
                        <p class="mb-1"><strong>Email:</strong> <span id="modalUserEmail"></span></p>
                        <p class="mb-1"><strong>Phone:</strong> <span id="modalUserPhone"></span></p>
                        <p class="mb-1"><strong>User Type:</strong> <span id="modalUserType"></span></p>
                        <p class="mb-1"><strong>Date of Birth:</strong> <span id="modalUserDob"></span></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Start -->
        <div class="container-fluid pt-4 px-4">
            <div class="bg-light rounded-top p-4">
                <div class="row">
                    <div class="col-12 col-sm-6 text-center text-sm-start"></div>
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
<!-- Template Javascript -->
<script src="../assets/js/dashboard.js"></script>
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
</body>
</html>