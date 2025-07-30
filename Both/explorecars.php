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
    // If user data not found, redirect to login
    header("Location: ../public/login.php");
    exit();
}

// Fetch all cars from the database
$cars = [];
$carQuery = "SELECT c.*, u.first_name, u.last_name FROM cars c 
             LEFT JOIN users u ON c.added_by = u.id 
             ORDER BY c.created_at DESC";

try {
    $result = $conn->query($carQuery);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cars[] = $row;
        }
    }
} catch (Exception $e) {
    // Log the error but continue with empty cars array
    error_log("Error fetching cars: " . $e->getMessage());
}

// Handle filter queries
$filteredCars = $cars;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['filter'])) {
    $fuelType = isset($_GET['fuel_type']) ? $_GET['fuel_type'] : '';
    $transmission = isset($_GET['transmission']) ? $_GET['transmission'] : '';
    $priceMin = isset($_GET['price_min']) ? (int)$_GET['price_min'] : 0;
    $priceMax = isset($_GET['price_max']) ? (int)$_GET['price_max'] : PHP_INT_MAX;
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    $filteredCars = array_filter($cars, function($car) use ($fuelType, $transmission, $priceMin, $priceMax, $search) {
        $matchesSearch = empty($search) ||
            stripos($car['car_name'], $search) !== false ||
            stripos($car['description'], $search) !== false;

        $matchesFuel = empty($fuelType) || strtolower($car['fuel_type']) === strtolower($fuelType);
        $matchesTransmission = empty($transmission) || strtolower($car['transmission']) === strtolower($transmission);
        $matchesPrice = $car['price_per_day'] >= $priceMin && $car['price_per_day'] <= $priceMax;

        return $matchesSearch && $matchesFuel && $matchesTransmission && $matchesPrice;
    });
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="shortcut icon" href="./assets/logo.svg" type="image/svg+xml">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600&family=Open+Sans&display=swap"
          rel="stylesheet">
</head>
<style>
    .car-search-section {
        background: var(--gradient, #f7f9fa);
        padding: 48px 0 16px 0;
        text-align: center;
    }
    .car-search-title {
        font-size: 2.8rem;
        font-family: var(--ff-nunito, 'Nunito', Arial, sans-serif);
        color: var(--space-cadet, #232946);
        margin-bottom: 24px;
        font-weight: 700;
        letter-spacing: -1px;
    }
    .car-search-title{
        padding-top: 2rem;
    }
    .car-search-form {
        display: flex;
        justify-content: center;
        align-items: center;
        max-width: 700px;
        margin: 0 auto 40px auto;
        background: #ebeff5;
        border: 1px solid var(--white, #fff);
        box-shadow: var(--shadow-1, 0 8px 24px rgba(60,72,94,0.08));
        border-radius: 18px;
        padding: 0;
    }
    .car-search-input {
        flex: 1 1 0%;
        padding: 18px 20px;
        border: none;
        border-radius: 18px 0 0 18px;
        font-size: 1.1rem;
        font-family: inherit;
        outline: none;
        color: var(--space-cadet, #232946);
        background: transparent;
    }
    .car-search-input::placeholder {
        color: var(--independence, #676c7e);
    }
    .car-search-btn {
        width: 170px;
        height: 56px;
        border: none;
        border-radius: 0 18px 18px 0;
        background: var(--background, var(--carolina-blue));
        color: var(--color, var(--white));
        min-width: var(--width, 40px);
        min-height: var(--height, 40px);
        font-size: 1.1rem;
        font-family: inherit;
        font-weight: 700;
        cursor: pointer;
        text-transform: uppercase;
        transition: background 0.2s;
    }
    .car-search-btn:is(:hover, :focus) { box-shadow: var(--shadow-2);
        transition: var(--transition);
    }

    .car-search-btn:is(:hover, :focus)::before { opacity: 1;
        transition: var(--transition);}

    /* Search filter section */
    .search-filters {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
        max-width: 800px;
        margin: 0 auto 30px auto;
        padding: 20px;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .filter-group {
        display: flex;
        flex-direction: column;
        min-width: 150px;
    }
    .filter-label {
        font-weight: 600;
        margin-bottom: 5px;
        color: #333;
        font-size: 0.9rem;
    }
    .filter-select {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 0.95rem;
        background: #f8f9fa;
        color: #333;
        outline: none;
        transition: border-color 0.2s;
    }
    .filter-select:focus {
        border-color: var(--carolina-blue);
        background: #fff;
    }
    .filter-apply-btn {
        padding: 10px 20px;
        background: var(--carolina-blue);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        align-self: flex-end;
        transition: background 0.2s;
    }
    .filter-apply-btn:hover {
        background: #0056b3;
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

    /* No Cars Found Modal Styles */
    .no-cars-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    .no-cars-modal.show {
        opacity: 1;
        visibility: visible;
    }

    .no-cars-content {
        background-color: white;
        border-radius: 12px;
        padding: 30px;
        max-width: 500px;
        width: 90%;
        text-align: center;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        transform: translateY(20px);
        transition: transform 0.3s ease;
        position: relative;
    }

    .no-cars-modal.show .no-cars-content {
        transform: translateY(0);
    }

    .no-cars-icon {
        font-size: 3.5rem;
        color: #f39c12;
        margin-bottom: 15px;
        display: block;
    }

    .no-cars-title {
        font-size: 1.8rem;
        color: #333;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .no-cars-text {
        font-size: 1.1rem;
        color: #666;
        margin-bottom: 25px;
        line-height: 1.5;
    }

    .no-cars-btn {
        background-color: var(--carolina-blue);
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 50px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }

    .no-cars-btn:hover {
        background-color: #0056b3;
    }

    .no-cars-close {
        position: absolute;
        top: 15px;
        right: 15px;
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #999;
        cursor: pointer;
        transition: color 0.2s;
    }

    .no-cars-close:hover {
        color: #333;
    }

    /* Responsive */
    @media (max-width: 600px) {
        .car-search-title { font-size: 2rem; }
        .car-search-form { flex-direction: column; border-radius: 18px; }
        .car-search-input { border-radius: 18px 18px 0 0; width: 100%; }
        .car-search-btn { border-radius: 0 0 18px 18px; width: 100%; }
        .search-filters { flex-direction: column; align-items: stretch; }
        .filter-group { min-width: 100%; }
        .no-cars-content { padding: 20px; }
        .no-cars-icon { font-size: 2.5rem; }
        .no-cars-title { font-size: 1.5rem; }
        .no-cars-text { font-size: 1rem; }
    }
</style>

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
                    <a href="../Both/homepage.php" class="navbar-link" data-nav-link>Home</a>
                </li>

                <li>
                    <a href="../Both/homepage.php"  class="navbar-link" data-nav-link>About Us</a>
                </li>


                <li>
                    <a href="../Both/homepage.php"  class="navbar-link" data-nav-link>Contact Us</a>
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
<!-- Car Search Bar Section -->
<section class="car-search-section">
    <div class="container">
        <h1 class="car-search-title">Available Cars</h1>

        <!-- Add search form -->
        <form action="explorecars.php" method="GET" class="car-search-form">
            <input type="text" name="search" id="searchInput" class="car-search-input" placeholder="Search for cars...">
            <input type="hidden" name="filter" value="true">
            <button type="submit" class="car-search-btn">Search</button>
        </form>

        <!-- Advanced Search Filters -->
        <form action="explorecars.php" method="GET" class="search-filters">
            <input type="hidden" name="filter" value="true">

            <div class="filter-group">
                <label class="filter-label">Fuel Type</label>
                <select class="filter-select" id="fuelTypeFilter" name="fuel_type">
                    <option value="">All Fuels</option>
                    <option value="Petrol">Petrol</option>
                    <option value="Diesel">Diesel</option>
                    <option value="Electric">Electric</option>
                    <option value="Hybrid">Hybrid</option>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Price Range</label>
                <select class="filter-select" id="priceRangeFilter" name="price_range">
                    <option value="">All Prices</option>
                    <option value="0-200">Under GH₵ 200</option>
                    <option value="200-400">GH₵ 200 - 400</option>
                    <option value="400-600">GH₵ 400 - 600</option>
                    <option value="600+">Above GH₵ 600</option>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Transmission</label>
                <select class="filter-select" id="transmissionFilter" name="transmission">
                    <option value="">All Types</option>
                    <option value="Automatic">Automatic</option>
                    <option value="Manual">Manual</option>
                </select>
            </div>
            <button type="submit" class="filter-apply-btn">
                <i class="fas fa-filter"></i> Apply Filters
            </button>
        </form>
    </div>
</section>

<!-- Car Cards Section -->
<section class="section featured-car" id="featured-car">
    <div class="container">

        <ul class="featured-car-list">
            <?php if (empty($filteredCars)): ?>
                <!-- We'll keep the original message div but hide it -->
                <div style="text-align: center; width: 100%; padding: 50px 20px; display: none;">
                    <h3>No cars found matching your criteria</h3>
                    <p>Try adjusting your search filters or check back later.</p>
                </div>
                <!-- The popup will be triggered by JavaScript -->
            <?php else: ?>
                <?php foreach ($filteredCars as $index => $car):
                    // Handle image paths
                    $mainImage = !empty($car['main_image']) ? '../' . ltrim($car['main_image'], '/') : '../assets/images/car-1.jpg';
                    $extraImages = [];
                    if (!empty($car['extra_images'])) {
                        try {
                            $extraImagesArray = json_decode($car['extra_images'], true);
                            if (is_array($extraImagesArray)) {
                                foreach ($extraImagesArray as $img) {
                                    $extraImages[] = '../' . ltrim($img, '/');
                                }
                            }
                        } catch (Exception $e) {
                            // Invalid JSON, continue with empty extra images
                        }
                    }
                    ?>
                    <li>
                        <div class="featured-car-card">
                            <figure class="card-banner">
                                <img src="<?= htmlspecialchars($mainImage) ?>" alt="<?= htmlspecialchars($car['car_name']) ?>" loading="lazy" width="440" height="300" class="w-100">
                            </figure>
                            <div class="card-content">
                                <div class="card-title-wrapper">
                                    <h3 class="h3 card-title">
                                        <a href="../Both/bookingpage.php?car_id=<?= $car['car_id'] ?>"><?= htmlspecialchars($car['car_name']) ?></a>
                                    </h3>
                                    <data class="year" value="<?= htmlspecialchars($car['year']) ?>"><?= htmlspecialchars($car['year']) ?></data>
                                </div>
                                <ul class="card-list">
                                    <li class="card-list-item">
                                        <ion-icon name="people-outline"></ion-icon>
                                        <span class="card-item-text"><?= htmlspecialchars($car['people_capacity']) ?> People</span>
                                    </li>
                                    <li class="card-list-item">
                                        <ion-icon name="flash-outline"></ion-icon>
                                        <span class="card-item-text"><?= htmlspecialchars($car['fuel_type']) ?></span>
                                    </li>
                                    <li class="card-list-item">
                                        <ion-icon name="speedometer-outline"></ion-icon>
                                        <span class="card-item-text"><?= htmlspecialchars($car['mileage']) ?> km</span>
                                    </li>
                                    <li class="card-list-item">
                                        <ion-icon name="hardware-chip-outline"></ion-icon>
                                        <span class="card-item-text"><?= htmlspecialchars($car['transmission']) ?></span>
                                    </li>
                                </ul>
                                <div class="card-price-wrapper">
                                    <p class="card-price">
                                        <strong>GH₵ <?= htmlspecialchars($car['price_per_day']) ?></strong> / day
                                    </p>
                                    <button class="btn fav-btn" aria-label="Add to favourite list">
                                        <ion-icon name="heart-outline"></ion-icon>
                                    </button>
                                    <button class="btn" onclick="window.location.href='../Both/bookingpage.php?car_id=<?= $car['car_id'] ?>'">Rent now</button>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (empty($filteredCars) || count($filteredCars) < 2): ?>
                <!-- Keep some of the static cars to fill the page if not enough from database -->

            <?php endif; ?>
        </ul>

    </div>
</section>

<!-- No Cars Found Modal -->
<div class="no-cars-modal" id="noCarsModal">
    <div class="no-cars-content">
        <button class="no-cars-close" id="closeNoCarsModal">&times;</button>
        <ion-icon name="car-sport-outline" class="no-cars-icon"></ion-icon>
        <h3 class="no-cars-title">No cars found matching your criteria</h3>
        <p class="no-cars-text">Try adjusting your search filters or check back later for more vehicle options.</p>
        <button class="no-cars-btn" id="resetFiltersBtn">Reset Filters</button>
    </div>
</div>

<!--
  - #FOOTER
-->
<footer class="site-footer-section">
    <div class="site-footer-container">
        <div class="site-footer-row">
            <!-- About & Subscribe -->
            <div class="site-footer-col">
                <h4 class="site-footer-title">About Us</h4>
                <p class="site-footer-text">
                    Dolor amet sit justo amet elitr clita ipsum elitr est. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                </p>
                <form class="site-footer-subscribe">
                    <input type="email" class="site-footer-input" placeholder="Enter your email" required>
                    <button type="submit" class="site-footer-btn">Subscribe</button>
                </form>
            </div>
            <!-- Quick Links -->
            <div class="site-footer-col">
                <h4 class="site-footer-title">Quick Links</h4>
                <ul class="site-footer-links">
                    <li><a href="#"><i class="fas fa-angle-right"></i> About</a></li>
                    <li><a href="#"><i class="fas fa-angle-right"></i> Cars</a></li>
                    <li><a href="#"><i class="fas fa-angle-right"></i> Car Types</a></li>
                    <li><a href="#"><i class="fas fa-angle-right"></i> Team</a></li>
                    <li><a href="#"><i class="fas fa-angle-right"></i> Contact us</a></li>
                    <li><a href="#"><i class="fas fa-angle-right"></i> Terms & Conditions</a></li>
                </ul>
            </div>
            <!-- Business Hours -->
            <div class="site-footer-col">
                <h4 class="site-footer-title">Business Hours</h4>
                <div class="site-footer-hours">
                    <div>
                        <span class="site-footer-hours-label">Mon - Friday:</span>
                        <span class="site-footer-hours-value">09.00 am to 07.00 pm</span>
                    </div>
                    <div>
                        <span class="site-footer-hours-label">Saturday:</span>
                        <span class="site-footer-hours-value">10.00 am to 05.00 pm</span>
                    </div>
                    <div>
                        <span class="site-footer-hours-label">Vacation:</span>
                        <span class="site-footer-hours-value">All Sunday is our vacation</span>
                    </div>
                </div>
            </div>
            <!-- Contact Info -->
            <div class="site-footer-col">
                <h4 class="site-footer-title">Contact Info</h4>
                <ul class="site-footer-contact">
                    <li><a href="#"><i class="fa fa-map-marker-alt"></i> 123 Street, New York, USA</a></li>
                    <li><a href="mailto:info@example.com"><i class="fas fa-envelope"></i> info@example.com</a></li>
                    <li><a href="tel:+01234567890"><i class="fas fa-phone"></i> +012 345 67890</a></li>
                    <li><a href="tel:+01234567890"><i class="fas fa-print"></i> +012 345 67890</a></li>
                </ul>
                <div class="site-footer-socials">
                    <a href="#" class="site-footer-social"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="site-footer-social"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="site-footer-social"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="site-footer-social"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
        <div class="site-footer-bottom">
            <p>&copy; 2025 Your Company Name. All rights reserved.</p>
        </div>
    </div>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const profileBtn = document.getElementById('userProfileBtn');
        const dropdownMenu = document.getElementById('userDropdownMenu');
        const dashboardLink = document.getElementById('dashboardLink');
        const noCarsModal = document.getElementById('noCarsModal');
        const closeNoCarsModal = document.getElementById('closeNoCarsModal');
        const resetFiltersBtn = document.getElementById('resetFiltersBtn');

        // Show the no cars modal if no cars were found
        <?php if (empty($filteredCars) && isset($_GET['filter'])): ?>
        // Delay showing the modal slightly for better UX
        setTimeout(() => {
            noCarsModal.classList.add('show');
        }, 500);
        <?php endif; ?>

        // Close modal when clicking the close button
        closeNoCarsModal.addEventListener('click', function() {
            noCarsModal.classList.remove('show');
        });

        // Reset filters when clicking the reset button
        resetFiltersBtn.addEventListener('click', function() {
            window.location.href = 'explorecars.php';
        });

        // Also close modal when clicking outside of it
        noCarsModal.addEventListener('click', function(e) {
            if (e.target === noCarsModal) {
                noCarsModal.classList.remove('show');
            }
        });

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

    // Pre-select filter values based on URL parameters
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);

        // Set the search input value
        if (urlParams.has('search')) {
            document.getElementById('searchInput').value = urlParams.get('search');
        }

        // Set the fuel type dropdown
        if (urlParams.has('fuel_type')) {
            document.getElementById('fuelTypeFilter').value = urlParams.get('fuel_type');
        }

        // Set the transmission dropdown
        if (urlParams.has('transmission')) {
            document.getElementById('transmissionFilter').value = urlParams.get('transmission');
        }

        // Set the price range dropdown
        if (urlParams.has('price_range')) {
            document.getElementById('priceRangeFilter').value = urlParams.get('price_range');
        }
    });

    // Process price range selection to set hidden min/max fields
    document.getElementById('priceRangeFilter').addEventListener('change', function() {
        const priceRange = this.value;
        let minPrice = document.querySelector('input[name="price_min"]');
        let maxPrice = document.querySelector('input[name="price_max"]');

        // Create hidden fields if they don't exist
        if (!minPrice) {
            minPrice = document.createElement('input');
            minPrice.type = 'hidden';
            minPrice.name = 'price_min';
            this.form.appendChild(minPrice);
        }

        if (!maxPrice) {
            maxPrice = document.createElement('input');
            maxPrice.type = 'hidden';
            maxPrice.name = 'price_max';
            this.form.appendChild(maxPrice);
        }

        // Set values based on selection
        switch(priceRange) {
            case '0-200':
                minPrice.value = '0';
                maxPrice.value = '200';
                break;
            case '200-400':
                minPrice.value = '200';
                maxPrice.value = '400';
                break;
            case '400-600':
                minPrice.value = '400';
                maxPrice.value = '600';
                break;
            case '600+':
                minPrice.value = '600';
                maxPrice.value = '100000'; // High number to represent no upper limit
                break;
            default:
                minPrice.value = '';
                maxPrice.value = '';
        }
    });
</script>
<!-- Ionicons CDN -->
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
<script src="../assets/js/main.js"></script>

<!-- Hidden input for user type - set this value from your PHP backend -->
<input type="hidden" id="userType" value="<?php echo htmlspecialchars($userData['user_type']); ?>">

</body>
</html>