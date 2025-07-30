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

// ===== DATABASE INTEGRATION FOR CAR BOOKING =====

/**
 * Get car details by ID from database
 */
function getCarById($carId) {
    global $conn;

    $query = "SELECT * FROM cars WHERE car_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $carId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

/**
 * Create new booking in database
 */
function createBooking($userId, $carId, $pickupLocation, $pickupDate, $dropoffDate, $totalDays, $pricePerDay, $totalAmount, $customerNote) {
    global $conn;

    // Set default times
    $pickupTime = '09:00:00';
    $dropoffTime = '18:00:00';

    // Enable error logging
    error_log("Creating booking: User=$userId, Car=$carId, Days=$totalDays, Amount=$totalAmount");

    $query = "INSERT INTO bookings (
        user_id, car_id, pickup_location, pickup_date, pickup_time, 
        dropoff_date, dropoff_time, total_days, price_per_day, total_amount, 
        customer_note, booking_status, booking_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("iisssssidds",
        $userId, $carId, $pickupLocation, $pickupDate, $pickupTime,
        $dropoffDate, $dropoffTime, $totalDays, $pricePerDay, $totalAmount, $customerNote
    );

    $result = $stmt->execute();
    if (!$result) {
        error_log("Execute failed: " . $stmt->error);
    }
    return $result;
}

/**
 * Check if car is available for booking dates
 */
function isCarAvailable($carId, $pickupDate, $dropoffDate) {
    global $conn;

    $query = "SELECT COUNT(*) as booking_count FROM bookings 
              WHERE car_id = ? 
              AND booking_status IN ('pending', 'approved') 
              AND ((pickup_date <= ? AND dropoff_date >= ?) OR 
                   (pickup_date <= ? AND dropoff_date >= ?) OR 
                   (pickup_date >= ? AND dropoff_date <= ?))";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("issssss", $carId, $pickupDate, $pickupDate, $dropoffDate, $dropoffDate, $pickupDate, $dropoffDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row['booking_count'] == 0;
}

// Handle AJAX request specifically for checking car availability
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_availability') {
    header('Content-Type: application/json');
    $carIdToCheck = (int)($_POST['car_id'] ?? 0);
    $pickupDateToCheck = $_POST['pickup_date'] ?? '';
    $dropoffDateToCheck = $_POST['dropoff_date'] ?? '';

    if (empty($carIdToCheck) || empty($pickupDateToCheck) || empty($dropoffDateToCheck)) {
        echo json_encode(['available' => false, 'message' => 'Incomplete data for availability check.']);
        exit();
    }

    $isAvailable = isCarAvailable($carIdToCheck, $pickupDateToCheck, $dropoffDateToCheck);
    echo json_encode(['available' => $isAvailable]);
    exit();
}


// Get car ID from URL parameter (default to Cadillac Escalade)
$carId = isset($_GET['car_id']) ? (int)$_GET['car_id'] : 6; // Assuming Cadillac Escalade has ID 6
$carData = getCarById($carId);

// If car not found, redirect to explore cars page
if (!$carData) {
    header("Location: explorecars.php");
    exit();
}

// Handle booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_booking') {
    header('Content-Type: application/json');

    // Debug: Log all POST variables
    error_log("Booking submission: " . json_encode($_POST));

    $pickupLocation = $_POST['pickup_location'] ?? '';
    $pickupDate = $_POST['pickup_date'] ?? '';
    $dropoffDate = $_POST['dropoff_date'] ?? '';
    $customerNote = $_POST['customer_note'] ?? '';
    $totalDays = (int)($_POST['total_days'] ?? 0);
    $totalAmount = (float)($_POST['total_amount'] ?? 0);

    // Validate dates
    if (empty($pickupDate) || empty($dropoffDate)) {
        echo json_encode(['success' => false, 'message' => 'Please select both pickup and dropoff dates.']);
        exit();
    }

    // Check if pickup date is not in the past
    if ($pickupDate < date('Y-m-d')) {
        echo json_encode(['success' => false, 'message' => 'Pickup date cannot be in the past.']);
        exit();
    }

    // Final server-side check to ensure car is available
    if (!isCarAvailable($carId, $pickupDate, $dropoffDate)) {
        echo json_encode(['success' => false, 'message' => 'Sorry, this car is not available for the selected dates. Please try different dates.']);
        exit();
    }

    try {
        // Create booking
        if (createBooking($userId, $carId, $pickupLocation, $pickupDate, $dropoffDate, $totalDays, $carData['price_per_day'], $totalAmount, $customerNote)) {
            echo json_encode(['success' => true, 'message' => 'Booking created successfully!']);
        } else {
            // Check for MySQL error
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
    } catch (Exception $e) {
        // Log and return any exceptions
        error_log("Booking exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
    }

    exit();
}

// Process extra images (JSON format)
$extraImages = [];
if (!empty($carData['extra_images'])) {
    $decodedImages = json_decode($carData['extra_images'], true) ?: [];
    foreach ($decodedImages as $img) {
        $extraImages[] = '../' . $img;
    }
}

// Set main image
$mainImage = !empty($carData['main_image']) ? '../' . $carData['main_image'] : null;

// Set default images if no extra images
if (empty($mainImage) && empty($extraImages)) {
    $extraImages = [
        "https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=600&q=80",
        "https://images.unsplash.com/photo-1511918984145-48de785d4c4e?auto=format&fit=crop&w=600&q=80",
        "https://images.unsplash.com/photo-1525609004556-c46c7d6cf023?auto=format&fit=crop&w=600&q=80",
        "https://images.unsplash.com/photo-1461632830798-3adb3034e4c8?auto=format&fit=crop&w=600&q=80"
    ];
    $mainImage = $extraImages[0];
} elseif (empty($mainImage) && !empty($extraImages)) {
    $mainImage = $extraImages[0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book <?php echo htmlspecialchars($carData['car_name']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Ionicons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800&family=Open+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</head>
<style>body {
        margin: 0;
        font-family: 'Nunito', 'Open Sans', Arial, sans-serif;
        background: #f2f5fa;
    }
    .booking-main-container {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        gap: 44px;
        max-width: 1200px;
        margin: 48px auto 0 auto;
        padding: 0 10px 48px 10px;
        margin-top: 7rem;
    }
    .booking-left, .booking-right {
        flex: 1 1 0;
    }
    .booking-car-container {
        background: #f0f3f8;
        border-radius: 22px;
        box-shadow: 0 8px 32px rgba(33,150,243,0.10), 0 1.5px 5px rgba(33,150,243,0.09);
        padding: 32px 30px 28px 30px;
        max-width: 440px;
        margin-bottom: 24px;
        border: 1px solid white;
        width: 100%;
    }
    .booking-main-img {
        width: 100%;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 8px #2196f325;
        margin-bottom: 18px;
    }
    .booking-main-img img {
        width: 100%;
        height: 280px;
        object-fit: cover;
        border-radius: 16px;
        display: block;
    }
    .booking-extra-images {
        display: flex;
        gap: 14px;
        margin-bottom: 18px;
        justify-content: center;
    }
    .booking-extra-img {
        width: 66px;
        height: 50px;
        border-radius: 11px;
        object-fit: cover;
        cursor: pointer;
        box-shadow: 0 1px 5px #2196f325;
        border: 2px solid #e3e8ef;
        background: #f6f8fc;
        transition:
                transform 0.23s cubic-bezier(.34,1.56,.64,1),
                box-shadow 0.22s,
                border 0.18s;
        position: relative;
        z-index: 1;
    }
    .booking-extra-img:hover, .booking-extra-img:focus {
        transform: scale(1.23) translateY(-8px);
        z-index: 2;
        box-shadow: 0 4px 18px #2196f345;
        border: 2px solid #2196f3;
    }
    .booking-car-info {
        margin-top: 10px;
    }
    .booking-car-title {
        font-size: 1.34rem;
        font-weight: 800;
        color: #2c2c3c;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 13px;
    }
    .booking-car-year {
        background: #f2f6fc;
        color: #2196f3;
        border: 1.5px dashed #90caf9;
        border-radius: 11px;
        font-size: 1rem;
        padding: 2px 13px;
    }
    .booking-details-row {
        display: flex;
        flex-wrap: wrap;
        gap: 20px 22px;
        margin-bottom: 13px;
        color: #2c2c3c;
        font-size: 1.01rem;
    }
    .booking-details-row ion-icon {
        color: #2196f3;
        font-size: 1.08em;
        margin-right: 5px;
        vertical-align: middle;
    }
    .booking-car-desc {
        font-size: 1.01rem;
        color: #7b83a1;
        line-height: 1.6;
        margin-bottom: 13px;
    }
    .booking-car-price span {
        font-size: 1.18rem;
        color: #2196f3;
        font-weight: 800;
    }
    .booking-car-price small {
        color: #7b83a1;
        font-size: 0.93rem;
        font-weight: 400;
    }

    /* Booking Form */
    .booking-right {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        min-width: 340px;
        max-width: 420px;
    }
    .booking-form {
        background: #f0f3f8;
        border-radius: 22px;
        box-shadow: 0 8px 32px rgba(33,150,243,0.10), 0 1.5px 5px rgba(33,150,243,0.09);
        padding: 32px 28px 28px 28px;
        display: flex;
        flex-direction: column;
        gap: 7px;
        position: relative;
        border: 1px solid white;
    }
    .booking-form-title {
        font-size: 1.27rem;
        color: #2c2c3c;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 16px;
    }
    .booking-form-group {
        display: flex;
        flex-direction: column;
        margin-bottom: 13px;
    }
    .booking-form-group label {
        font-size: 1.01rem;
        color: #2196f3;
        font-weight: 700;
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .booking-form-group input,
    .booking-form-group select,
    .booking-form-group textarea {
        border: 1.5px solid #e3e8ef;
        border-radius: 9px;
        padding: 10px 12px;
        font-size: 1rem;
        background: #f6f8fc;
        color: #2c2c3c;
        font-family: inherit;
        outline: none;
        transition: border .18s;
    }
    .booking-form-group input:focus,
    .booking-form-group select:focus,
    .booking-form-group textarea:focus {
        border-color: #2196f3;
    }
    .booking-form-group textarea {
        min-height: 62px;
        resize: vertical;
    }
    .booking-form-total {
        font-size: 1.05rem;
        color: #2196f3;
        font-weight: 600;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        background: #f2f6fc;
        padding: 8px 12px;
        border-radius: 9px;
        border: 1px solid #e3f2fd;
    }
    .booking-form-total ion-icon {
        color: #2196f3;
        font-size: 1.1em;
    }
    .booking-form-btn {
        width: 100%;
        background: linear-gradient(90deg, #2196f3 60%, #1565c0 100%);
        color: #fff;
        font-size: 1.13rem;
        font-weight: 700;
        padding: 13px 0;
        border: none;
        border-radius: 11px;
        box-shadow: 0 2px 12px #2196f320;
        cursor: pointer;
        margin-top: 4px;
        transition: background 0.22s, box-shadow 0.22s, transform 0.22s, opacity 0.22s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    .booking-form-btn:disabled {
        background: #bdc3c7;
        cursor: not-allowed;
        opacity: 0.7;
        transform: none;
        box-shadow: none;
    }
    .booking-form-btn:hover:not(:disabled),
    .booking-form-btn:focus:not(:disabled) {
        background: linear-gradient(90deg, #1565c0 60%, #2196f3 100%);
        box-shadow: 0 4px 20px #2196f335;
        transform: translateY(-2px) scale(1.026);
    }
    .booking-form-group--hidden {
        display: none;
    }

    .booking-success-message {
        display: none;
        position: fixed;
        top: 32px;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(90deg,#d0f8ce 60%,#b2f7b9 100%);
        color: #176c0c;
        border: 1.5px solid #8ae68c;
        min-width: 240px;
        max-width: 90vw;
        padding: 15px 22px;
        border-radius: 14px;
        font-size: 1.08rem;
        text-align: center;
        z-index: 99;
        font-weight: 600;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s, top 0.23s;
    }
    .booking-success-message.show {
        opacity: 1;
        pointer-events: auto;
        top: 58px;
    }

    /* Total Days Display */
    .booking-form-days {
        font-size: 1.05rem;
        color: #2196f3;
        font-weight: 600;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
        background: #f2f6fc;
        padding: 8px 12px;
        border-radius: 9px;
        border: 1px solid #e3f2fd;
    }
    .booking-form-days ion-icon {
        color: #2196f3;
        font-size: 1.1em;
    }

    /* Booking Confirmation Modal */
    .booking-confirm-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s, visibility 0.3s;
    }
    .booking-confirm-modal.show {
        opacity: 1;
        visibility: visible;
    }
    /* --- IMPLEMENTATION --- */
    .booking-confirm-modal-content {
        background: #fff;
        border-radius: 14px;
        padding: 20px; /* Further reduced padding */
        max-width: 320px; /* Further reduced max-width */
        width: 85%; /* Reduced width */
        box-shadow: 0 6px 24px rgba(0, 0, 0, 0.12);
        position: relative;
        text-align: left;
    }
    .booking-confirm-modal-close {
        position: absolute;
        top: 8px;
        right: 12px;
        background: none;
        border: none;
        font-size: 1.2rem;
        color: #999;
        cursor: pointer;
        padding: 3px;
        line-height: 1;
    }
    .booking-confirm-modal-close:hover {
        color: #666;
    }
    .booking-confirm-modal h3 {
        color: #2c2c3c;
        font-size: 1.15rem; /* Further reduced font size */
        font-weight: 700;
        margin-bottom: 12px; /* Further reduced margin */
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    .booking-confirm-details {
        display: flex;
        flex-direction: column;
        gap: 8px; /* Further reduced gap */
        margin-bottom: 16px; /* Further reduced margin */
    }
    .booking-confirm-item {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        padding: 6px 8px; /* Further reduced padding */
        background: #f6f8fc;
        border-radius: 8px;
        border: 1px solid #e3e8ef;
    }
    .booking-confirm-item ion-icon {
        font-size: 0.95rem; /* Further reduced icon size */
        color: #2196f3;
        margin-top: 2px;
        flex-shrink: 0;
    }
    .booking-confirm-item-content {
        flex: 1;
    }
    .booking-confirm-item-label {
        font-weight: 600;
        color: #2196f3;
        font-size: 0.8rem; /* Further reduced font size */
        margin-bottom: 2px; /* Further reduced margin */
    }
    .booking-confirm-item-value {
        color: #2c2c3c;
        font-weight: 500;
        font-size: 0.85rem; /* Further reduced font size */
    }
    .booking-confirm-buttons {
        display: flex;
        gap: 8px;
        justify-content: center;
    }
    .booking-confirm-btn {
        padding: 8px 14px; /* Further reduced padding */
        border: none;
        border-radius: 8px;
        font-size: 0.85rem; /* Further reduced font size */
        font-weight: 600;
        cursor: pointer;
        transition: all 0.22s;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .booking-confirm-btn.confirm {
        background: linear-gradient(90deg, #2196f3 60%, #1565c0 100%);
        color: #fff;
        box-shadow: 0 2px 8px #2196f320;
    }
    .booking-confirm-btn.confirm:hover {
        background: linear-gradient(90deg, #1565c0 60%, #2196f3 100%);
        box-shadow: 0 3px 15px #2196f335;
        transform: translateY(-1px);
    }
    .booking-confirm-btn.cancel {
        background: #f5f5f5;
        color: #666;
        border: 1px solid #ddd;
    }
    .booking-confirm-btn.cancel:hover {
        background: #e0e0e0;
        color: #444;
    }

    /* Car Booked Message */
    .booking-booked-message {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 16px;
        padding: 24px;
        text-align: center;
        color: #856404;
        font-weight: 600;
        font-size: 1.1rem;
        display: none;
    }
    .booking-booked-message ion-icon {
        font-size: 2rem;
        color: #f39c12;
        margin-bottom: 12px;
        display: block;
    }
    .booking-booked-message h3 {
        color: #856404;
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .booking-booked-message p {
        margin: 0;
        line-height: 1.5;
    }

    /* Booked Success Message */
    .booked-success-message {
        background: linear-gradient(90deg, #d0f8ce 60%, #b2f7b9 100%);
        color: #176c0c;
        border: 1.5px solid #8ae68c;
        border-radius: 12px;
        padding: 16px 20px;
        text-align: center;
        font-weight: 600;
        font-size: 1.05rem;
        margin-top: 16px;
        display: none;
        opacity: 0;
        transition: opacity 0.3s;
    }
    .booked-success-message.show {
        display: block;
        opacity: 1;
    }
    .booked-success-message ion-icon {
        font-size: 1.3rem;
        margin-right: 8px;
        vertical-align: middle;
    }

    /* Profile picture styles */
    .user-btn {
        position: relative;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f0f0f0;
    }

    .user-btn img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }

    .user-btn ion-icon {
        font-size: 20px;
        color: #666;
    }

    .user-dropdown {
        position: relative;
    }

    .dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        min-width: 160px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        border-radius: 8px;
        z-index: 1000;
        display: none;
        margin-top: 5px;
    }

    .dropdown-item {
        display: block;
        padding: 12px 16px;
        text-decoration: none;
        color: #333;
        border-bottom: 1px solid #eee;
        transition: background-color 0.3s;
    }

    .dropdown-item:hover {
        background-color: #f8f9fa;
    }

    .dropdown-item:last-child {
        border-bottom: none;
    }

    .user-info {
        padding: 12px 16px;
        border-bottom: 1px solid #eee;
        background: #f8f9fa;
    }

    .user-name {
        font-weight: 600;
        color: #333;
        margin: 0;
        font-size: 14px;
    }

    .user-email {
        color: #666;
        margin: 2px 0 0 0;
        font-size: 12px;
    }

    #availability-message {
        display: none;
        color: #c0392b; /* Red text */
        background: #f9e7e6; /* Light red background */
        border: 1px solid #e74c3c;
        padding: 12px;
        border-radius: 9px;
        margin-bottom: 15px;
        text-align: center;
        font-weight: 600;
        font-size: 1.02rem;
    }

    /* Responsive */
    @media (max-width: 1100px) {
        .booking-main-container {
            flex-direction: column;
            align-items: center;
            gap: 30px;
        }
        .booking-left, .booking-right {
            max-width: 95vw;
            width: 100%;
        }
        .booking-car-container, .booking-form {
            max-width: 98vw;
        }
    }
    @media (max-width: 600px) {
        .booking-main-container {
            padding: 0 2vw 36px 2vw;
        }
        .booking-car-container,
        .booking-form {
            padding: 18px 5vw;
        }
        .booking-main-img img {
            height: 180px;
        }
        .booking-extra-img {
            width: 48px;
            height: 36px;
        }
        .booking-confirm-modal-content {
            padding: 16px;
            margin: 16px;
            max-width: 280px;
        }
        .booking-confirm-buttons {
            flex-direction: column;
        }
    }</style>
<body>

<header class="header" data-header>
    <div class="container">

        <div class="overlay" data-overlay></div>

        <a href="#" class="logo">
            <img src="../assets/images/logo.svg" alt="Ridex logo">
        </a>

        <nav class="navbar" data-navbar>
            <ul class="navbar-list">

                <li>
                    <a href="homepage.php" class="navbar-link" data-nav-link>Home</a>
                </li>

                <li>
                    <a href="homepage.php" class="navbar-link" data-nav-link>About us</a>
                </li>

                <li>
                    <a href="homepage.php" class="navbar-link" data-nav-link>Contact Us</a>
                </li>

            </ul>
        </nav>

        <div class="header-actions">

            <a href="explorecars.php" class="btn" aria-labelledby="aria-label-txt">
                <ion-icon name="car-outline"></ion-icon>

                <span id="aria-label-txt">Explore cars</span>
            </a>

            <!-- Profile Button with Dropdown -->
            <div class="user-dropdown">
                <a href="#" class="btn user-btn" aria-label="Profile" id="userProfileBtn">
                    <?php
                    // Enhanced profile image handling
                    $imageFound = false;
                    if (!empty($userData['profile_image'])) {
                        // Try multiple possible paths for the image
                        $possiblePaths = [
                            '../assets/uploads/profiles/' . basename($userData['profile_image']),
                            $userData['profile_image'],
                            '../assets/uploads/profiles/' . $userData['profile_image']
                        ];

                        foreach ($possiblePaths as $imagePath) {
                            if (file_exists($imagePath)) {
                                echo '<img src="' . htmlspecialchars($imagePath) . '" alt="Profile Picture">';
                                $imageFound = true;
                                break;
                            }
                        }
                    }

                    if (!$imageFound): ?>
                        <ion-icon name="person-outline"></ion-icon>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu" id="userDropdownMenu">
                    <div class="user-info">
                        <p class="user-name"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></p>
                        <p class="user-email"><?php echo htmlspecialchars($userData['email']); ?></p>
                    </div>
                    <a href="#" class="dropdown-item" id="dashboardLink">Dashboard</a>
                    <a href="../public/logout.php" class="dropdown-item">Log Out</a>
                </div>
            </div>

            <button class="nav-toggle-btn" data-nav-toggle-btn aria-label="Toggle Menu">
                <span class="one"></span>
                <span class="two"></span>
                <span class="three"></span>
            </button>

        </div>

    </div>
</header>
<div class="booking-main-container">
    <!-- LEFT: Car Details & Gallery -->
    <div class="booking-left">
        <div class="booking-car-container">
            <div class="booking-main-img">
                <img id="mainCarImg" src="<?php echo htmlspecialchars($mainImage); ?>" alt="<?php echo htmlspecialchars($carData['car_name']); ?>">
            </div>
            <div class="booking-extra-images">
                <?php foreach ($extraImages as $image): ?>
                    <img src="<?php echo htmlspecialchars($image); ?>" class="booking-extra-img" alt="<?php echo htmlspecialchars($carData['car_name']); ?>">
                <?php endforeach; ?>
            </div>

            <div class="booking-car-info">
                <h2 class="booking-car-title"><?php echo htmlspecialchars($carData['car_name']); ?> <span class="booking-car-year"><?php echo $carData['year']; ?></span></h2>
                <div class="booking-details-row">
                    <span><ion-icon name="people-outline"></ion-icon> <?php echo $carData['people_capacity']; ?> People</span>
                    <span><ion-icon name="flash-outline"></ion-icon> <?php echo ucfirst($carData['fuel_type']); ?></span>
                    <span><ion-icon name="speedometer-outline"></ion-icon> <?php echo htmlspecialchars($carData['mileage']); ?></span>
                    <span><ion-icon name="repeat-outline"></ion-icon> <?php echo ucfirst($carData['transmission']); ?></span>
                </div>
                <div class="booking-car-desc">
                    <p>
                        <?php echo htmlspecialchars($carData['description']); ?>
                    </p>
                </div>
                <div class="booking-car-price">
                    <span><b>GH₵ <?php echo number_format($carData['price_per_day'], 0); ?></b> <small>/day</small></span>
                </div>
            </div>
        </div>
    </div>
    <!-- RIGHT: Booking Form -->
    <div class="booking-right">
        <form class="booking-form" id="bookingForm" autocomplete="off">
            <h3 class="booking-form-title"><ion-icon name="calendar-outline"></ion-icon> Reserve this car now</h3>
            <div class="booking-form-group">
                <label for="pickup-location"><ion-icon name="location-outline"></ion-icon> Pick Up Location</label>
                <select id="pickup-location" name="pickupLocation" required>
                    <option value="Our Office" selected>Our Office</option>
                    <option value="other">Other Location</option>
                </select>
            </div>
            <div class="booking-form-group booking-form-group--hidden" id="custom-pickup-group">
                <label for="custom-pickup"><ion-icon name="pencil-outline"></ion-icon> Enter Pick Up Location</label>
                <input type="text" id="custom-pickup" name="customPickup" placeholder="Type pick up location...">
            </div>
            <div class="booking-form-group">
                <label for="pickup-date"><ion-icon name="time-outline"></ion-icon> Pick Up Date</label>
                <input type="date" id="pickup-date" name="pickupDate" min="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="booking-form-group">
                <label for="dropoff-date"><ion-icon name="time-outline"></ion-icon> Drop Off Date</label>
                <input type="date" id="dropoff-date" name="dropoffDate" min="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="booking-form-group">
                <label for="booking-message"><ion-icon name="chatbubble-ellipses-outline"></ion-icon> Message (Optional)</label>
                <textarea id="booking-message" name="bookingMessage" placeholder="Leave a note..."></textarea>
            </div>
            <div class="booking-form-days" id="bookingDays" style="display: none;">
                <ion-icon name="calendar-number-outline"></ion-icon>
                <span id="totalDaysText">Total Days: 0</span>
            </div>
            <div class="booking-form-total">
                <span><ion-icon name="cash-outline"></ion-icon> Total Amount:</span>
                <span id="bookingTotal">GH₵ 0</span>
            </div>

            <div id="availability-message"></div>

            <button type="submit" class="booking-form-btn">
                <span>Book Now</span>
                <ion-icon name="checkmark-circle-outline"></ion-icon>
            </button>
        </form>

        <!-- Car Booked Message -->
        <div class="booking-booked-message" id="carBookedMessage">
            <ion-icon name="car-outline"></ion-icon>
            <h3>Booking Confirmed!</h3>
            <p>Your booking has been successfully submitted and is now pending approval. We'll contact you soon with confirmation details.</p>

            <!-- Success Message Below Booked Message -->
            <div class="booked-success-message" id="bookedSuccessMessage">
                <ion-icon name="checkmark-circle-outline"></ion-icon>
                Your booking has been confirmed successfully! We'll contact you soon.
            </div>
        </div>

        <div id="booking-success" class="booking-success-message">Booking successful! We'll contact you soon.</div>
    </div>
</div>

<!-- Booking Confirmation Modal -->
<div id="bookingConfirmModal" class="booking-confirm-modal">
    <div class="booking-confirm-modal-content">
        <button class="booking-confirm-modal-close" id="closeConfirmModal">&times;</button>
        <h3>
            <ion-icon name="checkmark-circle-outline"></ion-icon>
            Confirm Your Booking
        </h3>
        <div class="booking-confirm-details">
            <div class="booking-confirm-item">
                <ion-icon name="car-outline"></ion-icon>
                <div class="booking-confirm-item-content">
                    <div class="booking-confirm-item-label">Car</div>
                    <div class="booking-confirm-item-value"><?php echo htmlspecialchars($carData['car_name']); ?></div>
                </div>
            </div>
            <div class="booking-confirm-item">
                <ion-icon name="location-outline"></ion-icon>
                <div class="booking-confirm-item-content">
                    <div class="booking-confirm-item-label">Pick Up Location</div>
                    <div class="booking-confirm-item-value" id="confirmPickupLocation">Our Office</div>
                </div>
            </div>
            <div class="booking-confirm-item">
                <ion-icon name="calendar-number-outline"></ion-icon>
                <div class="booking-confirm-item-content">
                    <div class="booking-confirm-item-label">Pickup Date</div>
                    <div class="booking-confirm-item-value" id="confirmPickupDate">-</div>
                </div>
            </div>
            <div class="booking-confirm-item">
                <ion-icon name="calendar-number-outline"></ion-icon>
                <div class="booking-confirm-item-content">
                    <div class="booking-confirm-item-label">Drop Off Date</div>
                    <div class="booking-confirm-item-value" id="confirmDropoffDate">-</div>
                </div>
            </div>
            <div class="booking-confirm-item">
                <ion-icon name="calendar-number-outline"></ion-icon>
                <div class="booking-confirm-item-content">
                    <div class="booking-confirm-item-label">Total Days</div>
                    <div class="booking-confirm-item-value" id="confirmTotalDays">0 days</div>
                </div>
            </div>
            <div class="booking-confirm-item">
                <ion-icon name="cash-outline"></ion-icon>
                <div class="booking-confirm-item-content">
                    <div class="booking-confirm-item-label">Total Amount</div>
                    <div class="booking-confirm-item-value" id="confirmTotalAmount">GH₵ 0</div>
                </div>
            </div>
            <div class="booking-confirm-item" id="confirmMessageItem" style="display: none;">
                <ion-icon name="chatbubble-ellipses-outline"></ion-icon>
                <div class="booking-confirm-item-content">
                    <div class="booking-confirm-item-label">Your Message</div>
                    <div class="booking-confirm-item-value" id="confirmMessage"></div>
                </div>
            </div>
        </div>
        <div class="booking-confirm-buttons">
            <button class="booking-confirm-btn cancel" id="cancelBooking">
                <ion-icon name="close-outline"></ion-icon>
                Cancel
            </button>
            <button class="booking-confirm-btn confirm" id="confirmBooking">
                <ion-icon name="checkmark-outline"></ion-icon>
                Confirm Booking
            </button>
        </div>
    </div>
</div>

<!-- Debug Information Panel - Hidden by default -->
<div id="debug-panel" style="display: none; position: fixed; bottom: 0; left: 0; right: 0; background: #f8f9fa; border-top: 3px solid #2196f3; padding: 15px; z-index: 9999; max-height: 300px; overflow: auto;">
    <h4>Debug Information</h4>
    <div id="debug-content" style="font-family: monospace; white-space: pre-wrap;"></div>
    <button onclick="document.getElementById('debug-panel').style.display='none';" style="position: absolute; top: 10px; right: 10px;">Close</button>
</div>

<script>
    // Database-driven JavaScript with PHP data
    const CAR_PRICE_PER_DAY = <?php echo $carData['price_per_day']; ?>;
    const CAR_ID = <?php echo $carId; ?>;

    // Debug function to show errors
    function showDebug(content) {
        document.getElementById('debug-content').textContent = content;
        document.getElementById('debug-panel').style.display = 'block';
    }

    // Swap main image on clicking extra images
    document.querySelectorAll('.booking-extra-img').forEach(img => {
        img.addEventListener('mouseenter', function () {
            document.getElementById('mainCarImg').src = this.src;
        });
        img.addEventListener('focus', function () {
            document.getElementById('mainCarImg').src = this.src;
        });
    });

    // Show/hide custom pickup location input
    const pickupLocation = document.getElementById('pickup-location');
    const customPickupGroup = document.getElementById('custom-pickup-group');
    pickupLocation.addEventListener('change', function () {
        if (this.value === 'other') {
            customPickupGroup.classList.remove('booking-form-group--hidden');
            document.getElementById('custom-pickup').required = true;
        } else {
            customPickupGroup.classList.add('booking-form-group--hidden');
            document.getElementById('custom-pickup').required = false;
        }
    });

    // Calculate total price based on date selection
    const pickupDate = document.getElementById('pickup-date');
    const dropoffDate = document.getElementById('dropoff-date');
    const bookingTotal = document.getElementById('bookingTotal');
    const bookingDays = document.getElementById('bookingDays');
    const totalDaysText = document.getElementById('totalDaysText');

    const availabilityMessage = document.getElementById('availability-message');
    const bookNowBtn = document.querySelector('.booking-form-btn');

    async function checkAvailability() {
        // Don't check if dates are incomplete or invalid
        if (!pickupDate.value || !dropoffDate.value || new Date(dropoffDate.value) < new Date(pickupDate.value)) {
            availabilityMessage.style.display = 'none';
            bookNowBtn.disabled = false;
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'check_availability');
            formData.append('car_id', CAR_ID);
            formData.append('pickup_date', pickupDate.value);
            formData.append('dropoff_date', dropoffDate.value);

            const response = await fetch('bookingpage.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (!result.available) {
                availabilityMessage.textContent = 'This car is not available for the selected dates. Please check again later.';
                availabilityMessage.style.display = 'block';
                bookNowBtn.disabled = true;
            } else {
                availabilityMessage.style.display = 'none';
                bookNowBtn.disabled = false;
            }

        } catch (error) {
            console.error('Availability check failed:', error);
            // In case of an error, we allow the booking to proceed and let the server do the final validation
            availabilityMessage.style.display = 'none';
            bookNowBtn.disabled = false;
        }
    }

    function calculateTotal() {
        let start = new Date(pickupDate.value);
        let end = new Date(dropoffDate.value);
        if (pickupDate.value && dropoffDate.value && end >= start) {
            let days = Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1;
            if (days > 0) {
                bookingTotal.textContent = `GH₵ ${CAR_PRICE_PER_DAY * days}`;
                totalDaysText.textContent = `Total Days: ${days}`;
                bookingDays.style.display = 'flex';
                return;
            }
        }
        bookingTotal.textContent = "GH₵ 0";
        bookingDays.style.display = 'none';
    }

    // Set minimum dates to today and add event listeners
    pickupDate.addEventListener('change', function() {
        calculateTotal();
        if (pickupDate.value) {
            dropoffDate.min = pickupDate.value;
        }
        checkAvailability(); // Check availability on change
    });
    dropoffDate.addEventListener('change', function() {
        calculateTotal();
        checkAvailability(); // Check availability on change
    });


    // Booking Confirmation Modal functionality
    const bookingForm = document.getElementById('bookingForm');
    const bookingSuccess = document.getElementById('booking-success');
    const bookingConfirmModal = document.getElementById('bookingConfirmModal');
    const closeConfirmModal = document.getElementById('closeConfirmModal');
    const cancelBooking = document.getElementById('cancelBooking');
    const confirmBooking = document.getElementById('confirmBooking');
    const carBookedMessage = document.getElementById('carBookedMessage');
    const bookedSuccessMessage = document.getElementById('bookedSuccessMessage');

    bookingForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Validate form
        if (!pickupDate.value || !dropoffDate.value) {
            alert('Please select both pickup and dropoff dates.');
            return;
        }

        // Get form values
        const pickupLocationValue = pickupLocation.value === 'other' ?
            document.getElementById('custom-pickup').value : 'Our Office';
        const totalDaysValue = totalDaysText.textContent.replace('Total Days: ', '');
        const totalAmountValue = bookingTotal.textContent;
        const messageValue = document.getElementById('booking-message').value.trim();

        // Populate confirmation modal
        document.getElementById('confirmPickupLocation').textContent = pickupLocationValue;
        document.getElementById('confirmPickupDate').textContent = pickupDate.value;
        document.getElementById('confirmDropoffDate').textContent = dropoffDate.value;
        document.getElementById('confirmTotalDays').textContent = totalDaysValue + ' days';
        document.getElementById('confirmTotalAmount').textContent = totalAmountValue;

        // Show/hide message section
        const messageItem = document.getElementById('confirmMessageItem');
        if (messageValue) {
            document.getElementById('confirmMessage').textContent = messageValue;
            messageItem.style.display = 'flex';
        } else {
            messageItem.style.display = 'none';
        }

        // Show confirmation modal
        bookingConfirmModal.classList.add('show');
    });

    // Close modal events
    closeConfirmModal.addEventListener('click', function() {
        bookingConfirmModal.classList.remove('show');
    });

    cancelBooking.addEventListener('click', function() {
        bookingConfirmModal.classList.remove('show');
    });

    // Close modal when clicking outside
    bookingConfirmModal.addEventListener('click', function(e) {
        if (e.target === bookingConfirmModal) {
            bookingConfirmModal.classList.remove('show');
        }
    });

    // Enhanced confirm booking with database integration
    confirmBooking.addEventListener('click', async function() {
        const pickupLocationValue = pickupLocation.value === 'other' ?
            document.getElementById('custom-pickup').value : 'Our Office';
        const messageValue = document.getElementById('booking-message').value.trim();
        const totalDaysValue = parseInt(totalDaysText.textContent.replace('Total Days: ', ''));
        const totalAmountValue = parseFloat(bookingTotal.textContent.replace('GH₵ ', ''));

        try {
            // Send booking data to server
            const formData = new FormData();
            formData.append('action', 'create_booking');
            formData.append('pickup_location', pickupLocationValue);
            formData.append('pickup_date', pickupDate.value);
            formData.append('dropoff_date', dropoffDate.value);
            formData.append('customer_note', messageValue);
            formData.append('total_days', totalDaysValue);
            formData.append('total_amount', totalAmountValue);

            // Show what's being sent for debugging
            console.log("Sending booking data:", {
                action: 'create_booking',
                car_id: CAR_ID,
                pickup_location: pickupLocationValue,
                pickup_date: pickupDate.value,
                dropoff_date: dropoffDate.value,
                customer_note: messageValue,
                total_days: totalDaysValue,
                total_amount: totalAmountValue
            });

            const response = await fetch('bookingpage.php?car_id=' + CAR_ID, {
                method: 'POST',
                body: formData
            });

            const responseText = await response.text();

            try {
                // Try to parse the response as JSON
                const result = JSON.parse(responseText);

                if (result.success) {
                    bookingConfirmModal.classList.remove('show');
                    bookingSuccess.textContent = "Booking confirmed successfully! We'll contact you soon.";
                    bookingSuccess.classList.add('show');

                    // Hide the booking form and show the booked message
                    bookingForm.style.display = 'none';
                    carBookedMessage.style.display = 'block';

                    // Show success message below the booked message
                    bookedSuccessMessage.classList.add('show');

                    // Hide the success message after 6 seconds
                    setTimeout(() => {
                        bookedSuccessMessage.classList.remove('show');
                    }, 6000);

                    setTimeout(() => {
                        bookingSuccess.classList.remove('show');
                    }, 6000);
                } else {
                    showDebug('Error response: ' + JSON.stringify(result, null, 2));
                    alert('Error: ' + result.message);
                }
            } catch (jsonError) {
                // If response is not valid JSON, show the raw response
                showDebug('Server response (not valid JSON):\n' + responseText);
                alert('The server returned an invalid response. See debug panel for details.');
            }
        } catch (error) {
            console.error('Error:', error);
            showDebug('Fetch error: ' + error.message);
            alert('An error occurred while creating the booking. Please try again.');
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const profileBtn = document.getElementById('userProfileBtn');
        const dropdownMenu = document.getElementById('userDropdownMenu');
        const dashboardLink = document.getElementById('dashboardLink');

        // Toggle dropdown on click
        profileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
        });

        // Hide dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!profileBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.style.display = 'none';
            }
        });

        // Dashboard link redirect based on user type - FIXED VERSION
        dashboardLink.addEventListener('click', function(e) {
            e.preventDefault();

            // Get user type from PHP session data
            const userType = '<?php echo htmlspecialchars($userData['user_type']); ?>';

            switch(userType) {
                case 'admin':
                    window.location.href = '../admin/admindashboard.php';
                    break;
                case 'manager':
                    window.location.href = '../manager/managerdashboard.php';
                    break;
                case 'customer':
                    window.location.href = '../customer/customerdashboard.php';
                    break;
                default:
                    // If user type is not determined, redirect to login
                    alert('User type not recognized. Please log in again.');
                    window.location.href = '../public/login.php';
                    break;
            }
        });
    });
</script>
<script src="../assets/js/main.js"></script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

<!-- Hidden input for user type - set this value from your PHP backend -->
<input type="hidden" id="userType" value="<?php echo htmlspecialchars($userData['user_type']); ?>">

</body>
</html>