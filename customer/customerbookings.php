<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
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

// ===== DATABASE FUNCTIONS FOR CUSTOMER BOOKINGS =====

/**
 * Get a valid profile image path
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
                return $testPath;
            }
        }
    }
    return $defaultImage;
}

/**
 * Get customer bookings with car details from database
 */
function getCustomerBookingsWithDetails($userId) {
    global $conn;

    $query = "
        SELECT 
            b.*,
            c.car_name, c.year, c.transmission, c.fuel_type, 
            c.mileage, c.people_capacity as capacity, c.main_image as car_image, c.price_per_day
        FROM bookings b
        LEFT JOIN cars c ON b.car_id = c.car_id
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    return [];
}

/**
 * Update customer booking
 */
function updateCustomerBooking($bookingId, $userId, $pickupDate, $pickupTime, $dropoffDate, $dropoffTime, $pickupLocation, $customerNote, $totalDays, $totalAmount) {
    global $conn;

    $query = "UPDATE bookings SET 
                pickup_date = ?, pickup_time = ?, dropoff_date = ?, dropoff_time = ?, 
                pickup_location = ?, customer_note = ?, total_days = ?, total_amount = ?, 
                updated_at = NOW() 
              WHERE id = ? AND user_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssssiddi", $pickupDate, $pickupTime, $dropoffDate, $dropoffTime, $pickupLocation, $customerNote, $totalDays, $totalAmount, $bookingId, $userId);

    return $stmt->execute();
}

/**
 * Delete customer booking (completely remove from database)
 */
function deleteCustomerBooking($bookingId, $userId) {
    global $conn;

    $query = "DELETE FROM bookings WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $bookingId, $userId);

    return $stmt->execute();
}

// Handle AJAX requests for booking actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];
    $bookingId = (int)($_POST['booking_id'] ?? 0);

    switch ($action) {
        case 'update':
            $pickupDateTime = $_POST['pickup_datetime'] ?? '';
            $dropoffDateTime = $_POST['dropoff_datetime'] ?? '';
            $pickupLocation = $_POST['pickup_location'] ?? '';
            $customerNote = $_POST['customer_note'] ?? '';
            $totalDays = (int)($_POST['total_days'] ?? 0);
            $totalAmount = (float)($_POST['total_amount'] ?? 0);

            // Split datetime into date and time
            $pickupDate = date('Y-m-d', strtotime($pickupDateTime));
            $pickupTime = date('H:i:s', strtotime($pickupDateTime));
            $dropoffDate = date('Y-m-d', strtotime($dropoffDateTime));
            $dropoffTime = date('H:i:s', strtotime($dropoffDateTime));

            if (updateCustomerBooking($bookingId, $userId, $pickupDate, $pickupTime, $dropoffDate, $dropoffTime, $pickupLocation, $customerNote, $totalDays, $totalAmount)) {
                echo json_encode(['success' => true, 'message' => 'Booking updated successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update booking.']);
            }
            break;

        case 'delete':
            if (deleteCustomerBooking($bookingId, $userId)) {
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

// Get customer bookings from database
$customerBookings = getCustomerBookingsWithDetails($userId);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Rent Car Now</title>
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

    <!-- Sidebar Start -->
    <div class="sidebar pe-4 pb-3">
        <nav class="navbar bg-light navbar-light">
            <a href="../admin/admindashboard.php" class="navbar-brand mx-4 mb-3">
                <h3 class="text-primary"><i class="fa fa-hashtag me-2"></i>DASHBOARD</h3>
            </a>
            <div class="d-flex align-items-center ms-4 mb-4">
                <div class="position-relative">
                    <?php
                    $userProfileImage = get_profile_image_path($userData['profile_image'], '../assets/images/blog-1.jpg');
                    ?>
                    <img class="rounded-circle" src="<?php echo $userProfileImage; ?>" alt="" style="width: 40px; height: 40px;">
                    <div class="bg-success rounded-circle border border-2 border-white position-absolute end-0 bottom-0 p-1"></div>
                </div>
                <div class="ms-3">
                    <h6 class="mb-0"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h6>
                    <span><?php echo ucfirst(htmlspecialchars($userData['user_type'])); ?></span>
                </div>
            </div>
            <div class="navbar-nav w-100">
                <a href="customerdashboard.php" class="nav-item nav-link">
                    <i class="fa fa-tachometer-alt me-2"></i>Dashboard
                </a>
                <a href="customerbookings.php" class="nav-item nav-link active">
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
            <a href="../admin/admindashboard.php" class="navbar-brand d-flex d-lg-none me-4">
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
                        <img class="rounded-circle me-lg-2" src="<?php echo $userProfileImage; ?>" alt="" style="width: 40px; height: 40px; object-fit: cover;">
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

        <!-- Customer Bookings Widgets Start -->
        <div class="container-fluid pt-4 px-4">
            <div class="row g-4" id="customerBookingsRow">
                <?php if (empty($customerBookings)): ?>
                    <div class="col-12">
                        <div class="bg-light rounded p-4 text-center">
                            <h5 class="text-muted">No bookings found</h5>
                            <p class="text-muted mb-0">You haven't made any bookings yet. <a href="../Both/explorecars.php">Explore cars</a> to make your first booking!</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($customerBookings as $index => $booking):
                        $cardId = $booking['id'];
                        $statusClass = $booking['booking_status'] === 'approved' ? 'btn-outline-success' :
                            ($booking['booking_status'] === 'canceled' ? 'btn-outline-danger' : 'btn-outline-warning');
                        $statusIcon = $booking['booking_status'] === 'approved' ? 'fa-check-circle' :
                            ($booking['booking_status'] === 'canceled' ? 'fa-times-circle' : 'fa-hourglass-half');

                        // Prepare car image path
                        $carImage = !empty($booking['car_image']) ? '../' . $booking['car_image'] : '../assets/images/car-4.jpg';
                        ?>
                        <!-- Customer Booking Card <?php echo $cardId; ?> -->
                        <div class="col-sm-12 col-md-6 col-xl-4 booking-card" id="customerBookingCard<?php echo $cardId; ?>">
                            <div class="h-100 bg-light rounded p-4">
                                <!-- Booking Date -->
                                <div class="mb-2 text-end">
                                    <span class="btn btn-outline-info btn-sm"><i class="fa fa-calendar-alt me-1"></i>Booking Date: <strong id="bookingDateDisplay<?php echo $cardId; ?>"><?php echo date('Y-m-d', strtotime($booking['booking_date'])); ?></strong></span>
                                </div>
                                <!-- Car Image & Name -->
                                <div class="text-center mb-3">
                                    <img src="<?php echo htmlspecialchars($carImage); ?>" alt="<?php echo htmlspecialchars($booking['car_name']); ?>" class="rounded mb-2" style="width:100%; height:200px; object-fit:cover; max-width:320px;">
                                    <div>
                                        <h5 class="text-dark fw-bold"><i class="fa fa-car me-2"></i><?php echo htmlspecialchars($booking['car_name']); ?></h5>
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
                                    <span class="btn btn-outline-info btn-sm"><i class="fa fa-calendar-day me-1"></i>Days: <strong id="totalDaysCard<?php echo $cardId; ?>"><?php echo $booking['total_days']; ?></strong></span>
                                    <span class="btn btn-outline-success btn-sm"><i class="fa fa-money-bill-alt me-1"></i>Total: <strong id="totalAmountCard<?php echo $cardId; ?>">GH₵ <?php echo number_format($booking['total_amount'], 0); ?></strong></span>
                                </div>
                                <!-- Booking Status -->
                                <div class="mb-3 text-center">
                                    <button id="statusButtonC<?php echo $cardId; ?>" class="btn <?php echo $statusClass; ?> btn-sm" disabled>
                                        <i class="fa <?php echo $statusIcon; ?> me-1"></i><?php echo ucfirst($booking['booking_status']); ?>
                                    </button>
                                </div>
                                <!-- Pick Up & Drop Off -->
                                <div class="mb-2">
                                    <span class="btn btn-outline-dark btn-sm mb-1"><i class="fa fa-map-marker-alt me-1"></i>Pick Up: <strong id="pickupLocationDisplay<?php echo $cardId; ?>"><?php echo htmlspecialchars($booking['pickup_location']); ?></strong></span>
                                </div>
                                <div class="mb-2">
                                    <span class="btn btn-outline-dark btn-sm mb-1"><i class="fa fa-clock me-1"></i>Pick Up Time: <strong id="pickupTimeDisplay<?php echo $cardId; ?>"><?php echo $booking['pickup_date'] . ' ' . substr($booking['pickup_time'], 0, 5); ?></strong></span>
                                </div>
                                <div class="mb-2">
                                    <span class="btn btn-outline-dark btn-sm mb-1"><i class="fa fa-clock me-1"></i>Drop Off Time: <strong id="dropoffTimeDisplay<?php echo $cardId; ?>"><?php echo $booking['dropoff_date'] . ' ' . substr($booking['dropoff_time'], 0, 5); ?></strong></span>
                                </div>
                                <!-- Customer Note -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold"><i class="fa fa-sticky-note me-1"></i>Note</label>
                                    <textarea class="form-control bg-light" id="customerNoteDisplay<?php echo $cardId; ?>" rows="2" readonly><?php echo htmlspecialchars($booking['customer_note'] ?: 'No note provided.'); ?></textarea>
                                </div>
                                <!-- Admin Note Area -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold"><i class="fa fa-comments me-1"></i>Admin Message</label>
                                    <textarea class="form-control bg-light" id="adminNoteDisplay<?php echo $cardId; ?>" rows="2" readonly><?php echo htmlspecialchars($booking['admin_message'] ?: 'Your booking has been received. We will contact you soon.'); ?></textarea>
                                </div>
                                <!-- Action Buttons -->
                                <div class="d-flex justify-content-between">
                                    <?php if ($booking['booking_status'] !== 'approved'): ?>
                                        <button class="btn btn-primary me-1" onclick="showEditBookingModal(<?php echo $cardId; ?>)" data-price-per-day="<?php echo $booking['price_per_day']; ?>">Edit</button>
                                        <button class="btn btn-danger" onclick="confirmDeleteBooking(<?php echo $cardId; ?>)">Delete</button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary me-1" disabled>Edit</button>
                                        <button class="btn btn-secondary" disabled>Approved</button>
                                    <?php endif; ?>
                                </div>
                                <!-- Success Message -->
                                <div id="successMsgC<?php echo $cardId; ?>" class="alert alert-success mt-3 py-2 px-3 d-none" role="alert">
                                    Action completed successfully!
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Edit Booking Modal -->
        <div class="modal fade" id="editBookingModal" tabindex="-1" aria-labelledby="editBookingModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" onsubmit="saveEditBooking();return false;">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editBookingModalLabel">Edit Your Booking</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Pick Up Date/Time -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fa fa-clock me-1"></i>Pick Up Date/Time</label>
                            <input type="datetime-local" class="form-control" id="editPickupTime" required>
                        </div>
                        <!-- Drop Off Date/Time -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fa fa-clock me-1"></i>Drop Off Date/Time</label>
                            <input type="datetime-local" class="form-control" id="editDropoffTime" required>
                        </div>
                        <!-- Pick Up Location -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fa fa-map-marker-alt me-1"></i>Pick Up Location</label>
                            <select class="form-select" id="editPickupLocation" onchange="toggleOtherLocationInput()">
                                <option value="Our Office">Our Office</option>
                                <option value="Other">Other Location</option>
                            </select>
                        </div>
                        <!-- Other Location Input -->
                        <div class="mb-3 d-none" id="otherLocationDiv">
                            <label class="form-label"><i class="fa fa-map-pin me-1"></i>Specify Location</label>
                            <textarea class="form-control" id="editOtherLocation" rows="2" placeholder="Type your pick up location..."></textarea>
                        </div>
                        <!-- Note -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fa fa-sticky-note me-1"></i>Note</label>
                            <textarea class="form-control" id="editCustomerNote" rows="2"></textarea>
                        </div>
                        <!-- Days & Total Price -->
                        <div class="mb-3">
                            <span class="btn btn-outline-info btn-sm mb-1"><i class="fa fa-calendar-day me-1"></i>Days: <strong id="totalDays"></strong></span>
                            <span class="btn btn-outline-success btn-sm mb-1"><i class="fa fa-money-bill-alt me-1"></i>Total: <strong id="totalAmount"></strong></span>
                        </div>
                        <input type="hidden" id="editBookingCardId">
                        <input type="hidden" id="editBookingPricePerDay">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save Changes</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                    <div id="editSuccessMsg" class="alert alert-success mt-2 py-2 px-3 d-none w-100" role="alert">
                        Booking updated successfully!
                    </div>
                </form>
            </div>
        </div>
        <!-- End Edit Booking Modal -->

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title" id="deleteConfirmModalLabel">
                            <i class="fa fa-exclamation-triangle text-warning me-2"></i>Confirm Delete Booking
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <p class="mb-0">Are you sure you want to delete this booking? This action cannot be undone and will permanently remove the booking from the database.</p>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Booking</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Yes, Delete Booking</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Global Delete Success Message -->
        <div id="deleteSuccessMsgC" class="alert alert-success mt-4 d-none" role="alert" style="max-width:400px;margin:auto;">
            Booking deleted successfully!
        </div>

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

            let carPerDayPrice = {};
            let deleteBookingId = null;

            function calculateCardTotals(id) {
                const pickupTime = document.getElementById("pickupTimeDisplay" + id).textContent.trim();
                const dropoffTime = document.getElementById("dropoffTimeDisplay" + id).textContent.trim();

                if (pickupTime && dropoffTime) {
                    const pickupDate = new Date(pickupTime.replace(" ", "T"));
                    const dropoffDate = new Date(dropoffTime.replace(" ", "T"));

                    if (dropoffDate > pickupDate) {
                        const days = Math.ceil((dropoffDate - pickupDate) / (1000 * 60 * 60 * 24));
                        const total = days * (carPerDayPrice[id] || 0);

                        document.getElementById("totalDaysCard" + id).textContent = days;
                        document.getElementById("totalAmountCard" + id).textContent = `GH₵ ${total}`;
                    }
                }
            }

            function showEditBookingModal(id) {
                try {
                    const pickupTime = document.getElementById("pickupTimeDisplay" + id).textContent.trim();
                    const dropoffTime = document.getElementById("dropoffTimeDisplay" + id).textContent.trim();
                    const pickupLocation = document.getElementById("pickupLocationDisplay" + id).textContent.trim();
                    const note = document.getElementById("customerNoteDisplay" + id).value;
                    const editButton = document.querySelector(`[onclick="showEditBookingModal(${id})"]`);
                    if (!editButton) {
                        return;
                    }
                    const pricePerDay = editButton ? editButton.getAttribute('data-price-per-day') : 0;

                    document.getElementById("editBookingCardId").value = id;
                    document.getElementById("editBookingPricePerDay").value = pricePerDay;
                    document.getElementById("editPickupTime").value = pickupTime ? formatForInput(pickupTime) : "";
                    document.getElementById("editDropoffTime").value = dropoffTime ? formatForInput(dropoffTime) : "";
                    document.getElementById("editCustomerNote").value = note;
                    document.getElementById("editPickupLocation").value = (pickupLocation === "Our Office" ? "Our Office" : "Other");
                    document.getElementById("editOtherLocation").value = (pickupLocation !== "Our Office" ? pickupLocation : "");
                    toggleOtherLocationInput();

                    carPerDayPrice[id] = parseFloat(pricePerDay);
                    updateTotals();

                    const modalElement = document.getElementById('editBookingModal');
                    if (!modalElement) {
                        return;
                    }

                    try {
                        if (typeof bootstrap === 'undefined' || typeof bootstrap.Modal === 'undefined') {
                            if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
                                $(modalElement).modal('show');
                            } else {
                                alert('Error: Could not open modal. Please check your browser compatibility.');
                            }
                        } else {
                            var modal = new bootstrap.Modal(modalElement);
                            modal.show();
                        }
                    } catch (modalError) {
                        console.error('Error showing modal:', modalError);
                    }

                    document.getElementById("editPickupTime").onchange = updateTotals;
                    document.getElementById("editDropoffTime").onchange = updateTotals;
                } catch (error) {
                    console.error('Error in showEditBookingModal:', error);
                }
            }

            function formatForInput(datetime) {
                try {
                    return datetime.replace(" ", "T");
                } catch (error) {
                    console.error('Error formatting datetime:', error);
                    return datetime;
                }
            }

            function formatForDisplay(datetime) {
                try {
                    return datetime.replace("T", " ");
                } catch (error) {
                    console.error('Error formatting datetime for display:', error);
                    return datetime;
                }
            }

            function updateTotals() {
                try {
                    const pickup = document.getElementById("editPickupTime").value;
                    const dropoff = document.getElementById("editDropoffTime").value;
                    const id = document.getElementById("editBookingCardId").value;
                    const pricePerDay = parseFloat(document.getElementById("editBookingPricePerDay").value) || 0;

                    let days = 0;
                    let total = 0;
                    if (pickup && dropoff) {
                        const pickupDate = new Date(pickup);
                        const dropoffDate = new Date(dropoff);
                        if (dropoffDate > pickupDate) {
                            days = Math.ceil((dropoffDate - pickupDate) / (1000 * 60 * 60 * 24));
                            days = days === 0 ? 1 : days;
                            total = days * pricePerDay;
                        }
                    }

                    const totalDaysElement = document.getElementById("totalDays");
                    const totalAmountElement = document.getElementById("totalAmount");

                    if (totalDaysElement) totalDaysElement.innerText = days || "-";
                    if (totalAmountElement) totalAmountElement.innerText = total ? `GH₵ ${total}` : "-";
                } catch (error) {
                    console.error('Error updating totals:', error);
                }
            }

            function toggleOtherLocationInput() {
                try {
                    const sel = document.getElementById("editPickupLocation");
                    const otherDiv = document.getElementById("otherLocationDiv");
                    if (!sel || !otherDiv) {
                        return;
                    }

                    if (sel.value === "Other") {
                        otherDiv.classList.remove("d-none");
                    } else {
                        otherDiv.classList.add("d-none");
                    }
                } catch (error) {
                    console.error('Error toggling location input:', error);
                }
            }

            async function saveEditBooking() {
                try {
                    const id = document.getElementById("editBookingCardId").value;
                    const pickupTime = document.getElementById("editPickupTime").value;
                    const dropoffTime = document.getElementById("editDropoffTime").value;
                    const pickupLocationSel = document.getElementById("editPickupLocation").value;
                    const otherLocation = document.getElementById("editOtherLocation").value.trim();
                    const note = document.getElementById("editCustomerNote").value;
                    const pricePerDay = parseFloat(document.getElementById("editBookingPricePerDay").value) || 0;

                    if (!pickupTime || !dropoffTime) {
                        alert('Please select both pickup and dropoff dates.');
                        return;
                    }

                    const pickupDate = new Date(pickupTime);
                    const dropoffDate = new Date(dropoffTime);
                    const totalDays = Math.ceil((dropoffDate - pickupDate) / (1000 * 60 * 60 * 24));
                    const totalAmount = totalDays * pricePerDay;
                    const finalPickupLocation = pickupLocationSel === "Other" ?
                        (otherLocation || "Other Location") : "Our Office";

                    try {
                        const formData = new FormData();
                        formData.append('action', 'update');
                        formData.append('booking_id', id);
                        formData.append('pickup_datetime', pickupTime);
                        formData.append('dropoff_datetime', dropoffTime);
                        formData.append('pickup_location', finalPickupLocation);
                        formData.append('customer_note', note);
                        formData.append('total_days', totalDays);
                        formData.append('total_amount', totalAmount);

                        const currentScriptPath = window.location.pathname;

                        const response = await fetch(currentScriptPath, {
                            method: 'POST',
                            body: formData
                        });

                        if (!response.ok) {
                            throw new Error(`Server returned ${response.status}: ${response.statusText}`);
                        }

                        const responseText = await response.text();

                        let result;
                        try {
                            result = JSON.parse(responseText);
                        } catch (jsonError) {
                            throw new Error('Invalid server response format');
                        }

                        if (result.success) {
                            document.getElementById("pickupTimeDisplay" + id).textContent = formatForDisplay(pickupTime);
                            document.getElementById("dropoffTimeDisplay" + id).textContent = formatForDisplay(dropoffTime);
                            document.getElementById("pickupLocationDisplay" + id).textContent = finalPickupLocation;
                            document.getElementById("customerNoteDisplay" + id).value = note;
                            document.getElementById("totalDaysCard" + id).textContent = totalDays;
                            document.getElementById("totalAmountCard" + id).textContent = `GH₵ ${totalAmount}`;

                            const editSuccessMsg = document.getElementById("editSuccessMsg");
                            editSuccessMsg.classList.remove("d-none");
                            setTimeout(() => {
                                editSuccessMsg.classList.add("d-none");
                                try {
                                    const modalElement = document.getElementById('editBookingModal');
                                    if (!modalElement) {
                                        return;
                                    }

                                    const modalInstance = bootstrap.Modal.getInstance(modalElement);
                                    if (modalInstance) {
                                        modalInstance.hide();
                                    } else {
                                        if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
                                            $(modalElement).modal('hide');
                                        } else {
                                            modalElement.classList.remove('show');
                                            modalElement.style.display = 'none';
                                            document.body.classList.remove('modal-open');
                                            const backdrop = document.querySelector('.modal-backdrop');
                                            if (backdrop) backdrop.remove();
                                        }
                                    }
                                } catch (modalError) {
                                    console.error('Error hiding modal:', modalError);
                                }
                            }, 1200);
                        } else {
                            alert('Error: ' + result.message);
                        }
                    } catch (fetchError) {
                        console.error('Fetch error:', fetchError);
                        alert('An error occurred while contacting the server. Please try again.');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while updating the booking.');
                }
            }

            function confirmDeleteBooking(id) {
                deleteBookingId = id;
                try {
                    const modalElement = document.getElementById('deleteConfirmModal');
                    if (!modalElement) {
                        return;
                    }

                    try {
                        if (typeof bootstrap === 'undefined' || typeof bootstrap.Modal === 'undefined') {
                            if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
                                $(modalElement).modal('show');
                            } else {
                                alert('Error: Could not open delete confirmation. Please check your browser compatibility.');
                            }
                        } else {
                            var deleteModal = new bootstrap.Modal(modalElement);
                            deleteModal.show();
                        }
                    } catch (modalError) {
                        console.error('Error showing delete modal:', modalError);
                    }
                } catch (error) {
                    console.error('Error in confirmDeleteBooking:', error);
                }
            }

            document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
                try {
                    if (deleteBookingId === null) {
                        return;
                    }

                    try {
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('booking_id', deleteBookingId);

                        const currentScriptPath = window.location.pathname;

                        const response = await fetch(currentScriptPath, {
                            method: 'POST',
                            body: formData
                        });

                        if (!response.ok) {
                            throw new Error(`Server returned ${response.status}: ${response.statusText}`);
                        }

                        const responseText = await response.text();

                        let result;
                        try {
                            result = JSON.parse(responseText);
                        } catch (jsonError) {
                            throw new Error('Invalid server response format');
                        }

                        if (result.success) {
                            handleCustomerBookingAction('delete', deleteBookingId);
                        } else {
                            alert('Error: ' + result.message);
                        }
                    } catch (fetchError) {
                        console.error('Fetch error during delete:', fetchError);
                        alert('An error occurred while contacting the server. Please try again.');
                    }

                    deleteBookingId = null;

                    try {
                        const modalElement = document.getElementById('deleteConfirmModal');
                        if (!modalElement) {
                            return;
                        }

                        try {
                            const modalInstance = bootstrap.Modal.getInstance(modalElement);
                            if (modalInstance) {
                                modalInstance.hide();
                            } else {
                                if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
                                    $(modalElement).modal('hide');
                                } else {
                                    modalElement.classList.remove('show');
                                    modalElement.style.display = 'none';
                                    document.body.classList.remove('modal-open');
                                    const backdrop = document.querySelector('.modal-backdrop');
                                    if (backdrop) backdrop.remove();
                                }
                            }
                        } catch (modalError) {
                            console.error('Error hiding delete modal:', modalError);
                        }
                    } catch (error) {
                        console.error('Error handling modal close:', error);
                    }
                } catch (error) {
                    console.error('Error in delete confirmation handler:', error);
                }
            });

            function handleCustomerBookingAction(action, id) {
                try {
                    if (action === 'delete' || action === 'cancel') {
                        const card = document.getElementById(`customerBookingCard${id}`);
                        if (!card) {
                            return;
                        }

                        card.remove();

                        const deleteSuccessMsg = document.getElementById('deleteSuccessMsgC');
                        if (deleteSuccessMsg) {
                            deleteSuccessMsg.classList.remove('d-none');
                            setTimeout(() => {
                                deleteSuccessMsg.classList.add('d-none');
                            }, 2000);
                        }
                    }
                } catch (error) {
                    console.error('Error in handleCustomerBookingAction:', error);
                }
            }

            window.addEventListener('DOMContentLoaded', function() {
                try {
                    <?php foreach ($customerBookings as $booking): ?>
                    carPerDayPrice[<?php echo $booking['id']; ?>] = <?php echo $booking['price_per_day']; ?>;
                    calculateCardTotals(<?php echo $booking['id']; ?>);
                    <?php endforeach; ?>
                } catch (error) {
                    console.error('Initialization error:', error);
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