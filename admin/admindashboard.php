<?php

session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
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

    // Query for total revenue (sum of approved bookings' total amounts).
    // Using COALESCE returns 0 if no approved bookings
    $revenueQuery = "SELECT COALESCE(SUM(total_amount), 0) AS total_revenue FROM bookings WHERE booking_status = 'approved'";
    $revenueResult = $conn->query($revenueQuery);
    if ($revenueResult && $revenueRow = $revenueResult->fetch_assoc()) {
        $totalRevenue = $revenueRow['total_revenue'];
    }
} catch (Exception $e) {
    error_log("Error fetching statistics: " . $e->getMessage());
}

// Redundant check: Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../public/login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$userData = getUserById($userId);

if (!$userData) {
    header("Location: ../public/login.php");
    exit();
}

// Fetch recent bookings from database
$recentBookings = [];
try {
    $bookingsQuery = "
        SELECT b.*, 
               c.car_name, c.year, c.transmission, c.fuel_type, c.mileage, c.people_capacity, c.main_image as car_image, c.price_per_day,
               u.first_name, u.last_name, u.email, u.phone, u.profile_image,
               b.pickup_location, b.pickup_date, b.pickup_time, b.dropoff_date, b.dropoff_time, b.customer_note
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
            <a href="admindashboard.php" class="navbar-brand mx-4 mb-3">
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
                <a href="admindashboard.php" class="nav-item nav-link active">
                    <i class="fa fa-tachometer-alt me-2"></i>Dashboard
                </a>
                <a href="adminbookings.php" class="nav-item nav-link">
                    <i class="fa fa-calendar-check me-2"></i>Bookings
                </a>
                <a href="adminmybookings.php" class="nav-item nav-link">
                    <i class="fa fa-calendar-check me-2"></i>My Bookings
                </a>
                <a href="adminmessage.php" class="nav-item nav-link" id="navMessageLink">
                    <i class="fa fa-envelope me-2"></i>Messages
                    <span id="navUnreadBadge" class="badge bg-danger ms-2" style="display:none;"></span>
                </a>
                <a href="adminform.php" class="nav-item nav-link">
                    <i class="fa fa-paper-plane me-2"></i>Forms
                </a>
                <a href="adminsubmitform.php" class="nav-item nav-link">
                    <i class="fa fa-paper-plane me-2"></i>Submit Form
                </a>
                <a href="managecars.php" class="nav-item nav-link">
                    <i class="fa fa-car-side me-2"></i>Manage Cars
                </a>
                <a href="manageusers.php" class="nav-item nav-link">
                    <i class="fa fa-users-cog me-2"></i>Manage Users
                </a>
                <a href="adminprofile.php" class="nav-item nav-link">
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
            <a href="admindashboard.php" class="navbar-brand d-flex d-lg-none me-4">
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
                            <th scope="col">Pickup Location</th>
                            <th scope="col">Pickup Time</th>
                            <th scope="col">Dropoff Time</th>
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

        <!-- Booking Card Modal (Popup, Small) -->
        <div class="modal fade" id="bookingCardModal" tabindex="-1" aria-labelledby="bookingCardModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 410px;">
                <div class="modal-content p-0">
                    <div class="modal-body p-0">
                        <div class="h-100 bg-light rounded p-3" style="font-size: 0.97rem;">
                            <div class="mb-2 text-end">
                                <span class="btn btn-outline-info btn-sm"><i class="fa fa-calendar-alt me-1"></i>Booking Date: <strong id="bookingDateAdmin"></strong></span>
                            </div>
                            <div class="text-center mb-2">
                                <img id="carImg" src="" alt="" class="rounded mb-2" style="width:100%; height:180px; object-fit:cover; max-width:350px;">
                                <div>
                                    <span id="carNameBtn" class="btn btn-outline-primary btn-sm me-1 mb-1 mt-1"><i class="fa fa-car me-1"></i></span>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap justify-content-center gap-1 mb-2">
                                <span id="carTransmission" class="btn btn-outline-secondary btn-sm px-2 py-0"></span>
                                <span id="carFuel" class="btn btn-outline-secondary btn-sm px-2 py-0"></span>
                                <span id="carYear" class="btn btn-outline-secondary btn-sm px-2 py-0"></span>
                            </div>
                            <div class="d-flex flex-wrap justify-content-center gap-1 mb-2">
                                <span id="carMileage" class="btn btn-outline-secondary btn-sm px-2 py-0"></span>
                                <span id="carCapacity" class="btn btn-outline-secondary btn-sm px-2 py-0"></span>
                                <span id="carPrice" class="btn btn-outline-success btn-sm px-2 py-0"></span>
                            </div>
                            <div class="mb-2 text-center">
                                <button id="statusButton" class="btn btn-sm px-3" style="min-width: 90px;" disabled></button>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <img id="customerImg" class="rounded-circle me-2" src="" alt="" style="width: 32px; height: 32px;">
                                <div class="text-start">
                                    <div class="fw-semibold" id="customerName" style="font-size:1rem;"></div>
                                    <div class="small text-muted"><i class="fa fa-phone me-1"></i><span id="customerPhone"></span></div>
                                </div>
                            </div>
                            <div class="mb-1">
                                <span class="btn btn-outline-dark btn-sm mb-1 px-2 py-0"><i class="fa fa-map-marker-alt me-1"></i>Pickup: <strong id="pickupLocation"></strong></span>
                            </div>
                            <div class="mb-1">
                                <span class="btn btn-outline-dark btn-sm mb-1 px-2 py-0"><i class="fa fa-clock me-1"></i>Pick Up: <strong id="pickupTime"></strong></span>
                            </div>
                            <div class="mb-1">
                                <span class="btn btn-outline-dark btn-sm mb-1 px-2 py-0"><i class="fa fa-clock me-1"></i>Drop Off: <strong id="dropoffTime"></strong></span>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold mb-1" style="font-size:0.96em"><i class="fa fa-sticky-note me-1"></i>Note</label>
                                <textarea id="customerNote" class="form-control bg-light" style="font-size:0.94em" rows="1" readonly></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold mb-1" style="font-size:0.96em"><i class="fa fa-comments me-1"></i>Admin Message</label>
                                <textarea id="adminMessageModal" class="form-control" style="font-size:0.94em" rows="1" placeholder="Type a message..."></textarea>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <button id="approveBtnModal" class="btn btn-success btn-sm me-1 px-3" onclick="handleBookingActionModal('approve')">Approve</button>
                                <button id="cancelBtnModal" class="btn btn-danger btn-sm me-1 px-3" onclick="handleBookingActionModal('cancel')">Cancel</button>
                                <button class="btn btn-outline-secondary btn-sm px-3" onclick="handleBookingActionModal('delete')">Delete</button>
                            </div>
                            <div id="successMsgModal" class="alert alert-success mt-2 py-2 px-3 d-none" style="font-size:0.98em;" role="alert">
                                Action completed successfully!
                            </div>
                            <div class="text-end mt-2">
                                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal for Recent Bookings -->
        <div class="modal fade" id="deleteConfirmModalBookings" tabindex="-1" aria-labelledby="deleteConfirmModalBookingsLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title" id="deleteConfirmModalBookingsLabel">
                            <i class="fa fa-exclamation-triangle text-warning me-2"></i>Confirm Delete
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <p class="mb-0">Are you sure you want to delete this booking? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtnBookings">Yes, Delete</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Pass PHP recent bookings data to JavaScript variable
            const bookings = <?php echo json_encode($recentBookings); ?>;

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

                filteredBookings.forEach(b => {
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
            <td>${b.pickupLocation}</td>
            <td>${b.pickupTime}</td>
            <td>${b.dropoffTime}</td>
        </tr>
        `;
                });
            }

            function renderBookingsTable() {
                const tbody = document.getElementById('bookingTableBody');
                tbody.innerHTML = '';
                bookings.forEach(b => {
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
            <td>${b.pickupLocation}</td>
            <td>${b.pickupTime}</td>
            <td>${b.dropoffTime}</td>
        </tr>
        `;
                });
            }

            window.addEventListener('DOMContentLoaded', renderBookingsTable);
        </script>
        <!-- Recent Bookings End -->

        <!-- Widgets Start -->
        <div class="container-fluid pt-4 px-4">
            <div class="row g-4">
                <!-- Notifications Widget Start -->
                <div class="col-sm-12 col-md-6 col-xl-4">
                    <div class="h-100 bg-light rounded p-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0">Notifications</h6>
                        </div>
                        <!-- Notification Item -->
                        <div class="d-flex align-items-center border-bottom py-3">
                            <div class="btn btn-outline-primary btn-square me-3" style="pointer-events: none;">
                                <i class="fa fa-bell"></i>
                            </div>
                            <div class="w-100">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <span class="fw-semibold text-dark">Booking Approved</span>
                                    <small class="badge bg-primary fw-normal">2 min ago</small>
                                </div>
                                <div class="text-muted small mt-1">Your booking for <strong>Toyota Camry</strong> has been approved.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center border-bottom py-3">
                            <div class="btn btn-outline-danger btn-square me-3" style="pointer-events: none;">
                                <i class="fa fa-times-circle"></i>
                            </div>
                            <div class="w-100">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <span class="fw-semibold text-dark">Booking Canceled</span>
                                    <small class="badge bg-danger fw-normal">1 hour ago</small>
                                </div>
                                <div class="text-muted small mt-1">Your booking for <strong>BMW 3 Series</strong> was canceled.</div>
                            </div>
                        </div>
                        <!-- Notification Item -->
                        <div class="d-flex align-items-center pt-3">
                            <div class="btn btn-outline-success btn-square me-3" style="pointer-events: none;">
                                <i class="fa fa-user-plus"></i>
                            </div>
                            <div class="w-100">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <span class="fw-semibold text-dark">Welcome!</span>
                                    <small class="badge bg-success fw-normal">Today</small>
                                </div>
                                <div class="text-muted small mt-1">Thanks for joining our platform. Get started now!</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Notifications Widget End -->

                <!-- To Do List Widget Start -->
                <div class="col-sm-12 col-md-6 col-xl-4">
                    <div class="h-100 bg-light rounded p-4">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h6 class="mb-0">To Do List</h6>
                        </div>
                        <div class="d-flex mb-2">
                            <input class="form-control bg-transparent" type="text" placeholder="Enter task">
                            <button type="button" class="btn btn-primary ms-2">Add</button>
                        </div>
                        <div class="d-flex align-items-center border-bottom py-2">
                            <input class="form-check-input m-0" type="checkbox">
                            <div class="w-100 ms-3">
                                <div class="d-flex w-100 align-items-center justify-content-between">
                                    <span>Short task goes here...</span>
                                    <button class="btn btn-sm"><i class="fa fa-times"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center border-bottom py-2">
                            <input class="form-check-input m-0" type="checkbox">
                            <div class="w-100 ms-3">
                                <div class="d-flex w-100 align-items-center justify-content-between">
                                    <span>Short task goes here...</span>
                                    <button class="btn btn-sm"><i class="fa fa-times"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center border-bottom py-2">
                            <input class="form-check-input m-0" type="checkbox" checked>
                            <div class="w-100 ms-3">
                                <div class="d-flex w-100 align-items-center justify-content-between">
                                    <span><del>Short task goes here...</del></span>
                                    <button class="btn btn-sm text-primary"><i class="fa fa-times"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center border-bottom py-2">
                            <input class="form-check-input m-0" type="checkbox">
                            <div class="w-100 ms-3">
                                <div class="d-flex w-100 align-items-center justify-content-between">
                                    <span>Short task goes here...</span>
                                    <button class="btn btn-sm"><i class="fa fa-times"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center pt-2">
                            <input class="form-check-input m-0" type="checkbox">
                            <div class="w-100 ms-3">
                                <div class="d-flex w-100 align-items-center justify-content-between">
                                    <span>Short task goes here...</span>
                                    <button class="btn btn-sm"><i class="fa fa-times"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- To Do List Widget End -->

                <!-- Booked Cars Widget Start -->
                <?php
                // Fetch approved bookings with car and user info for the Booked Cars widget
                $bookedCars = [];
                try {
                    $bookedCarsQuery = "
        SELECT b.id, c.car_name, c.main_image as car_image, u.first_name, u.last_name, u.profile_image
        FROM bookings b
        LEFT JOIN cars c ON b.car_id = c.car_id
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.booking_status = 'approved'
        ORDER BY b.booking_date DESC
        LIMIT 10
    ";
                    $bookedCarsResult = $conn->query($bookedCarsQuery);
                    if ($bookedCarsResult && $bookedCarsResult->num_rows > 0) {
                        while ($row = $bookedCarsResult->fetch_assoc()) {
                            // Handle car image
                            $carImg = !empty($row['car_image']) ? '../' . $row['car_image'] : '../assets/images/car-1.jpg';
                            // Handle user profile image
                            $profileImg = '../assets/images/blog-5.jpg';
                            if (!empty($row['profile_image'])) {
                                $possiblePaths = [
                                    '../assets/uploads/profiles/' . basename($row['profile_image']),
                                    $row['profile_image'],
                                    '../assets/uploads/profiles/' . $row['profile_image']
                                ];
                                foreach ($possiblePaths as $imgPath) {
                                    if (file_exists($imgPath)) {
                                        $profileImg = $imgPath;
                                        break;
                                    }
                                }
                            }
                            $bookedCars[] = [
                                'car_name' => $row['car_name'],
                                'car_img' => $carImg,
                                'user_name' => $row['first_name'] . ' ' . $row['last_name'],
                                'profile_img' => $profileImg
                            ];
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error fetching booked cars: " . $e->getMessage());
                }
                ?>
                <div class="col-sm-12 col-md-6 col-xl-4">
                    <div class="h-100 bg-light rounded p-4">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h6 class="mb-0">Booked Cars</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle table-bordered table-sm mb-0">
                                <thead>
                                <tr class="text-dark">
                                    <th scope="col">Car Name</th>
                                    <th scope="col">Booked By</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (!empty($bookedCars)): ?>
                                    <?php foreach ($bookedCars as $booked): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo htmlspecialchars($booked['car_img']); ?>" alt="<?php echo htmlspecialchars($booked['car_name']); ?>" class="rounded-circle me-2" style="width:32px;height:32px;">
                                                    <span><?php echo htmlspecialchars($booked['car_name']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo htmlspecialchars($booked['profile_img']); ?>" alt="<?php echo htmlspecialchars($booked['user_name']); ?>" class="rounded-circle me-2" style="width:32px;height:32px;">
                                                    <span><?php echo htmlspecialchars($booked['user_name']); ?></span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2">No booked cars found.</td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Booked Cars Widget End -->

            </div>
        </div>
        <!-- Widgets End -->

        <script>
            // Users data for search functionality
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
                    const statusClass = user.status.toLowerCase() === 'admin'
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
                                    if (strtolower($user['status']) === 'admin') $statusClass = 'btn-outline-success';
                                    else if (strtolower($user['status']) === 'manager') $statusClass = 'btn-outline-warning';
                                    ?>
                                    <button class="btn <?php echo $statusClass; ?> btn-sm" disabled><?php echo htmlspecialchars($user['status']); ?></button>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="showUserModal('<?php echo htmlspecialchars(addslashes($user['name'])); ?>', '<?php echo htmlspecialchars(addslashes($user['email'])); ?>', '<?php echo htmlspecialchars(addslashes($user['phone'])); ?>', '<?php echo htmlspecialchars(addslashes($user['status'])); ?>', '<?php echo htmlspecialchars(addslashes($user['dob'])); ?>', '<?php echo htmlspecialchars(addslashes($user['img'])); ?>')">Detail</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Recent Customers End -->

        <!-- Footer Start -->
        <div class="container-fluid pt-4 px-4">
            <div class="bg-light rounded-top p-4">
                <div class="row">

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

<!-- Template Javascript -->
<script>
    // Update unread badges and make the user's name bold if there are unread messages.
    function updateUnreadBadges() {
        fetch("get_unread_counts.php")
            .then(response => response.json())
            .then(data => {
                // data is an object mapping sender_id to unread count.
                document.querySelectorAll("#userListMsg .user-list-item").forEach(item => {
                    const senderId = item.getAttribute("data-id");
                    const badge = item.querySelector(".unread-badge");
                    const userNameSpan = item.querySelector(".user-name");
                    if (data[senderId] && data[senderId] > 0) {
                        badge.textContent = data[senderId];
                        badge.style.display = "inline-block";
                        if (userNameSpan) {
                            userNameSpan.style.fontWeight = "bold";
                        }
                    } else {
                        badge.textContent = "";
                        badge.style.display = "none";
                        if (userNameSpan) {
                            userNameSpan.style.fontWeight = "normal";
                        }
                    }
                });

                // Also update the global nav unread badge.
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
            .catch(error => {
                console.error("Error fetching unread counts:", error);
            });
    }

    // Call updateUnreadBadges on page load and every 15 seconds.
    setInterval(updateUnreadBadges, 15000);
    document.addEventListener("DOMContentLoaded", () => {
        updateUnreadBadges();
    });
</script>
<script src="../assets/js/dashboard.js"></script>
</body>

</html>