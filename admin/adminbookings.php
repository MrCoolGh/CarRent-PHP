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

// ===== DATABASE INTEGRATION FOR BOOKINGS =====

/**
 * Enhanced helper function to get a valid profile image path
 */
function get_profile_image_path($imagePath, $defaultImage) {
    if (!empty($imagePath)) {
        $possiblePaths = [
            '../assets/uploads/profiles/' . basename($imagePath),
            $imagePath,
            '../assets/uploads/profiles/' . str_replace('../assets/uploads/profiles/', '', $imagePath)
        ];

        foreach ($possiblePaths as $testPath) {
            if (file_exists($testPath)) {
                return htmlspecialchars($testPath);
            }
        }
    }
    return $defaultImage;
}

/**
 * Get all bookings with user and car details from database, with filtering
 */
function getAllBookingsWithDetails($filter_status, $search_term) {
    global $conn;

    $params = [];
    $types = '';

    $query = "
        SELECT 
            b.*,
            u.first_name, u.last_name, u.email, u.phone as user_phone, u.profile_image as user_profile,
            c.car_name, c.year, c.transmission, c.fuel_type, 
            c.mileage, c.people_capacity as capacity, c.main_image as car_image, c.price_per_day
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN cars c ON b.car_id = c.car_id
        WHERE 1=1
    ";

    // Add status filter if provided
    if (!empty($filter_status)) {
        $query .= " AND b.booking_status = ?";
        $params[] = $filter_status;
        $types .= 's';
    }

    // Add search term filter if provided
    if (!empty($search_term)) {
        $query .= " AND (CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR c.car_name LIKE ?)";
        $like_term = "%" . $search_term . "%";
        $params[] = $like_term;
        $params[] = $like_term;
        $types .= 'ss';
    }

    $query .= " ORDER BY b.booking_date DESC";

    $stmt = $conn->prepare($query);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    return [];
}

/**
 * Update booking status in database with detailed error reporting
 */
function updateBookingStatus($bookingId, $status, $adminMessage, $changedBy) {
    global $conn;

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update booking status and admin message in the bookings table
        $updateQuery = "UPDATE bookings SET booking_status = ?, admin_message = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        if (!$stmt) {
            throw new Exception("Database prepare error (bookings): " . $conn->error);
        }
        $stmt->bind_param("ssi", $status, $adminMessage, $bookingId);
        if (!$stmt->execute()) {
            throw new Exception("Database execute error (bookings): " . $stmt->error);
        }
        $stmt->close();

        // Insert a record into the booking status history table
        $historyQuery = "INSERT INTO booking_status_history (booking_id, new_status, changed_by, admin_comment, created_at) VALUES (?, ?, ?, ?, NOW())";
        $historyStmt = $conn->prepare($historyQuery);
        if (!$historyStmt) {
            throw new Exception("Database prepare error (history): " . $conn->error);
        }
        $historyStmt->bind_param("isis", $bookingId, $status, $changedBy, $adminMessage);
        if (!$historyStmt->execute()) {
            throw new Exception("Database execute error (history): " . $historyStmt->error);
        }
        $historyStmt->close();

        // If both queries succeed, commit the transaction
        $conn->commit();
        return ['success' => true];

    } catch (Exception $e) {
        // If any error occurs, roll back the transaction
        $conn->rollback();
        // Log the detailed error for server-side debugging
        error_log("Booking update failed for booking ID $bookingId: " . $e->getMessage());
        // Return a detailed error message to the front-end
        return ['success' => false, 'message' => $e->getMessage()];
    }
}


/**
 * Delete booking from database
 */
function deleteBooking($bookingId) {
    global $conn;

    $query = "DELETE FROM bookings WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $bookingId);

    return $stmt->execute();
}

// The AJAX handler now provides specific error messages from the database.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $adminMessage = $_POST['admin_message'] ?? '';

    switch ($action) {
        case 'approve':
            $result = updateBookingStatus($bookingId, 'approved', $adminMessage, $userId);
            if ($result['success']) {
                echo json_encode(['success' => true, 'message' => 'Booking approved successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to approve booking: ' . $result['message']]);
            }
            break;

        case 'cancel':
            $result = updateBookingStatus($bookingId, 'canceled', $adminMessage, $userId);
            if ($result['success']) {
                echo json_encode(['success' => true, 'message' => 'Booking canceled successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to cancel booking: ' . $result['message']]);
            }
            break;

        case 'delete':
            if (deleteBooking($bookingId)) {
                echo json_encode(['success' => true, 'message' => 'Booking deleted successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete booking.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            break;
    }

    exit();
}

// Get filter parameters from URL
$filter_status = $_GET['status'] ?? '';
$search_term = $_GET['search'] ?? '';

// Get all bookings from database with filters
$allBookings = getAllBookingsWithDetails($filter_status, $search_term);
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
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->
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
</script>

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
                        <img class="rounded-circle" src="../assets/images/blog-1.jpg" alt="" style="width: 40px; height: 40px;">
                    <?php endif; ?>
                    <div class="bg-success rounded-circle border border-2 border-white position-absolute end-0 bottom-0 p-1"></div>
                </div>
                <div class="ms-3">
                    <h6 class="mb-0"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h6>
                    <span><?php echo ucfirst(htmlspecialchars($userData['user_type'])); ?></span>
                </div>
            </div>
            <div class="navbar-nav w-100">
                <a href="admindashboard.php" class="nav-item nav-link">
                    <a href="admindashboard.php" class="nav-item nav-link ">
                        <i class="fa fa-tachometer-alt me-2"></i>Dashboard
                    </a>

                    <a href="adminbookings.php" class="nav-item nav-link active ">
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
                            <img class="rounded-circle me-lg-2" src="../assets/images/blog-5.jpg" alt="" style="width: 40px; height: 40px;">
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


        <!-- Bookings Widgets Start -->
        <div class="container-fluid pt-4 px-4">

            <!-- Search and Filter Bar -->
            <div class="bg-light rounded p-4 mb-4">
                <form action="adminbookings.php" method="GET" class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <label for="search" class="visually-hidden">Search</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Search by customer or car name..." value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="visually-hidden">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="" <?php if (empty($filter_status)) echo 'selected'; ?>>All Statuses</option>
                            <option value="pending" <?php if ($filter_status === 'pending') echo 'selected'; ?>>Pending</option>
                            <option value="approved" <?php if ($filter_status === 'approved') echo 'selected'; ?>>Approved</option>
                            <option value="canceled" <?php if ($filter_status === 'canceled') echo 'selected'; ?>>Canceled</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex">
                        <button type="submit" class="btn btn-primary flex-grow-1 me-2">Filter</button>
                        <a href="adminbookings.php" class="btn btn-secondary flex-grow-1">Clear</a>
                    </div>
                </form>
            </div>

            <div class="row g-4" id="bookingsRow">
                <?php if (empty($allBookings)): ?>
                    <div class="col-12">
                        <div class="bg-light rounded p-4 text-center">
                            <h5 class="text-muted">No bookings found</h5>
                            <p class="text-muted mb-0">No bookings match your current filter criteria.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($allBookings as $index => $booking):
                        $cardId = $booking['id'];
                        $statusClass = $booking['booking_status'] === 'approved' ? 'btn-outline-success' :
                            ($booking['booking_status'] === 'canceled' ? 'btn-outline-danger' : 'btn-outline-warning');
                        $statusIcon = $booking['booking_status'] === 'approved' ? 'fa-check-circle' :
                            ($booking['booking_status'] === 'canceled' ? 'fa-times-circle' : 'fa-hourglass-half');
                        $userProfileImage = get_profile_image_path($booking['user_profile'], '../assets/images/blog-2.jpg');
                        $carImage = !empty($booking['car_image']) ? '../' . $booking['car_image'] : '../assets/images/car-1.jpg';
                        ?>
                        <!-- Booking Card <?php echo $cardId; ?> -->
                        <div class="col-sm-12 col-md-6 col-xl-4 booking-card" id="bookingCard<?php echo $cardId; ?>">
                            <div class="h-100 bg-light rounded p-4">
                                <!-- Booking Date -->
                                <div class="mb-2 text-end">
                                    <span class="btn btn-outline-info btn-sm"><i class="fa fa-calendar-alt me-1"></i>Booking Date: <strong id="bookingDateAdmin<?php echo $cardId; ?>"><?php echo date('Y-m-d', strtotime($booking['booking_date'])); ?></strong></span>
                                </div>
                                <!-- Car Image & Name -->
                                <div class="text-center mb-3">
                                    <img src="<?php echo htmlspecialchars($carImage); ?>" alt="<?php echo htmlspecialchars($booking['car_name']); ?>" class="rounded mb-2" style="width:100%; height:250px; object-fit:cover; max-width:360px;">
                                    <div>
                                        <span class="btn btn-outline-primary btn-sm me-1 mb-1"><i class="fa fa-car me-1"></i><?php echo htmlspecialchars($booking['car_name']); ?></span>
                                    </div>
                                </div>
                                <!-- Car Details -->
                                <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                                    <span class="btn btn-outline-secondary btn-sm"><i class="fa fa-cogs me-1"></i><?php echo ucfirst($booking['transmission']); ?></span>
                                    <span class="btn btn-outline-secondary btn-sm"><i class="fa fa-gas-pump me-1"></i><?php echo ucfirst($booking['fuel_type']); ?></span>
                                    <span class="btn btn-outline-secondary btn-sm"><i class="fa fa-calendar me-1"></i><?php echo $booking['year']; ?></span>
                                    <span class="btn btn-outline-secondary btn-sm"><i class="fa fa-road me-1"></i><?php echo htmlspecialchars($booking['mileage']); ?></span>
                                    <span class="btn btn-outline-secondary btn-sm"><i class="fa fa-user-friends me-1"></i><?php echo $booking['capacity']; ?> People</span>
                                    <span class="btn btn-outline-success btn-sm"><i class="fa fa-money-bill-alt me-1"></i>GH₵ <?php echo number_format($booking['price_per_day'], 0); ?> / day</span>
                                </div>
                                <!-- Total Days & Amount Section -->
                                <div class="d-flex justify-content-center gap-2 mb-3">
                                    <span class="btn btn-outline-info btn-sm"><i class="fa fa-calendar-day me-1"></i>Days: <strong id="totalDaysAdmin<?php echo $cardId; ?>"><?php echo $booking['total_days']; ?></strong></span>
                                    <span class="btn btn-outline-success btn-sm"><i class="fa fa-money-bill-alt me-1"></i>Total: <strong id="totalAmountAdmin<?php echo $cardId; ?>">GH₵ <?php echo number_format($booking['total_amount'], 0); ?></strong></span>
                                </div>
                                <!-- Booking Status -->
                                <div class="mb-3 text-center">
                                    <button id="statusButton<?php echo $cardId; ?>" class="btn <?php echo $statusClass; ?> btn-sm" disabled>
                                        <i class="fa <?php echo $statusIcon; ?> me-1"></i><?php echo ucfirst($booking['booking_status']); ?>
                                    </button>
                                </div>
                                <!-- Customer Info -->
                                <div class="d-flex align-items-center mb-3">
                                    <img class="rounded-circle me-2" src="<?php echo $userProfileImage; ?>" alt="" style="width: 40px; height: 40px;">
                                    <div class="text-start">
                                        <div class="fw-semibold"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></div>
                                        <div class="small text-muted"><i class="fa fa-phone me-1"></i><?php echo htmlspecialchars($booking['user_phone']); ?></div>
                                    </div>
                                </div>
                                <!-- Pick Up & Drop Off -->
                                <div class="mb-2">
                                    <span class="btn btn-outline-dark btn-sm mb-1"><i class="fa fa-map-marker-alt me-1"></i>Pick Up: <strong><?php echo htmlspecialchars($booking['pickup_location']); ?></strong></span>
                                </div>
                                <div class="mb-2">
                                    <span class="btn btn-outline-dark btn-sm mb-1"><i class="fa fa-clock me-1"></i>Pick Up Time: <strong><?php echo date('Y-m-d', strtotime($booking['pickup_date'])) . ' ' . date('h:i A', strtotime($booking['pickup_time'])); ?></strong></span>
                                </div>
                                <div class="mb-2">
                                    <span class="btn btn-outline-dark btn-sm mb-1"><i class="fa fa-clock me-1"></i>Drop Off Time: <strong><?php echo date('Y-m-d', strtotime($booking['dropoff_date'])) . ' ' . date('h:i A', strtotime($booking['dropoff_time'])); ?></strong></span>
                                </div>
                                <!-- Customer Note -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold"><i class="fa fa-sticky-note me-1"></i>Customer Note</label>
                                    <textarea class="form-control bg-light" rows="2" readonly><?php echo htmlspecialchars($booking['customer_note'] ?: 'No note provided.'); ?></textarea>
                                </div>
                                <!-- Admin Chatbox -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold"><i class="fa fa-comments me-1"></i>Admin Message</label>
                                    <textarea id="adminMessage<?php echo $cardId; ?>" class="form-control" rows="2" placeholder="Type a message before approving or canceling..."><?php echo htmlspecialchars($booking['admin_message'] ?: ''); ?></textarea>
                                </div>
                                <!-- Action Buttons -->
                                <div class="d-flex justify-content-between">
                                    <!-- --- FIX APPLIED HERE (Initial Button State) --- -->
                                    <button id="approveBtn<?php echo $cardId; ?>" class="btn btn-success me-1" onclick="handleBookingAction('approve', <?php echo $cardId; ?>)" <?php echo ($booking['booking_status'] === 'approved') ? 'disabled' : ''; ?>>Approve</button>
                                    <button id="cancelBtn<?php echo $cardId; ?>" class="btn btn-warning me-1" onclick="handleBookingAction('cancel', <?php echo $cardId; ?>)" <?php echo ($booking['booking_status'] === 'canceled') ? 'disabled' : ''; ?>>Cancel</button>
                                    <button class="btn btn-danger" onclick="handleBookingAction('delete', <?php echo $cardId; ?>)">Delete</button>
                                </div>
                                <!-- Success Message -->
                                <div id="successMsg<?php echo $cardId; ?>" class="alert alert-success mt-3 py-2 px-3 d-none" role="alert">
                                    Action completed successfully!
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <!-- Booking Card End -->
            </div>
            <!-- Delete Success Message (Global) -->
            <div id="deleteSuccessMsg" class="alert alert-success mt-4 d-none" role="alert" style="max-width:400px;margin:auto;">
                Booking deleted successfully!
            </div>
        </div>
        <!-- Bookings Widgets End -->

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title" id="deleteConfirmModalLabel">
                            <i class="fa fa-exclamation-triangle text-warning me-2"></i>Confirm Delete
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <p class="mb-0">Are you sure you want to delete this booking? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Yes, Delete</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            let deleteBookingId = null;

            // Enhanced booking action handler with database integration
            async function handleBookingAction(action, id) {
                const adminMsg = document.getElementById(`adminMessage${id}`);
                const approveBtn = document.getElementById(`approveBtn${id}`);
                const cancelBtn = document.getElementById(`cancelBtn${id}`);
                const statusBtn = document.getElementById(`statusButton${id}`);
                const successMsg = document.getElementById(`successMsg${id}`);
                const bookingCard = document.getElementById(`bookingCard${id}`);

                if (action === 'approve' || action === 'cancel') {
                    if (!adminMsg.value.trim()) {
                        adminMsg.classList.add('is-invalid');
                        adminMsg.focus();
                        return;
                    }
                    adminMsg.classList.remove('is-invalid');

                    // Send AJAX request to update database
                    try {
                        const formData = new FormData();
                        formData.append('action', action);
                        formData.append('booking_id', id);
                        formData.append('admin_message', adminMsg.value.trim());

                        const response = await fetch('adminbookings.php', {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        if (result.success) {
                            // --- FIX APPLIED HERE (JavaScript Button State) ---
                            if (action === 'approve') {
                                approveBtn.disabled = true;
                                cancelBtn.disabled = false; // Allow canceling after approval
                                statusBtn.className = "btn btn-outline-success btn-sm";
                                statusBtn.innerHTML = '<i class="fa fa-check-circle me-1"></i>Approved';
                                successMsg.innerHTML = "Booking approved successfully!";
                            } else if (action === 'cancel') {
                                cancelBtn.disabled = true;
                                approveBtn.disabled = false; // Allow re-approving after cancellation
                                statusBtn.className = "btn btn-outline-danger btn-sm";
                                statusBtn.innerHTML = '<i class="fa fa-times-circle me-1"></i>Canceled';
                                successMsg.innerHTML = "Booking canceled successfully!";
                            }

                            successMsg.classList.remove('d-none');
                            setTimeout(() => {
                                successMsg.classList.add('d-none');
                            }, 1500);
                        } else {
                            alert('Error: ' + result.message);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('An error occurred while processing the request.');
                    }

                } else if (action === 'delete') {
                    // Store the booking ID and show confirmation modal
                    deleteBookingId = id;
                    var deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
                    deleteModal.show();
                }
            }

            // Enhanced delete handler with database integration
            document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
                if (deleteBookingId !== null) {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('booking_id', deleteBookingId);

                        const response = await fetch('adminbookings.php', {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        if (result.success) {
                            const bookingCard = document.getElementById(`bookingCard${deleteBookingId}`);

                            // Remove the booking card from the DOM
                            if (bookingCard) {
                                bookingCard.remove();
                                // Show global delete message
                                const deleteSuccessMsg = document.getElementById('deleteSuccessMsg');
                                if (deleteSuccessMsg) {
                                    deleteSuccessMsg.classList.remove('d-none');
                                    setTimeout(() => {
                                        deleteSuccessMsg.classList.add('d-none');
                                    }, 1500);
                                }
                            }
                        } else {
                            alert('Error: ' + result.message);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the booking.');
                    }

                    // Reset the delete booking ID
                    deleteBookingId = null;

                    // Hide the modal
                    bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal')).hide();
                }
            });
        </script>



        <!-- Footer Start -->
        <div class="container-fluid pt-4 px-4">
            <div class="bg-light rounded-top p-4">
                <div class="row">
                    <div class="col-12 col-sm-6 text-center text-sm-start">
                    </div>
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
<script>// Update unread badges and make the user's name bold if there are unread messages.
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
    });</script>
<script src="../assets/js/dashboard.js"></script>
</body>

</html>