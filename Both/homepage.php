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

// Fetch featured cars from the database - ADDED THIS BLOCK
$featuredCars = [];
try {
    // Get up to 6 recent cars to display on homepage
    $carQuery = "SELECT c.*, u.first_name, u.last_name FROM cars c 
                LEFT JOIN users u ON c.added_by = u.id 
                ORDER BY c.created_at DESC LIMIT 6";
    $result = $conn->query($carQuery);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $featuredCars[] = $row;
        }
    }
} catch (Exception $e) {
    // Log error but continue with empty array
    error_log("Error fetching featured cars: " . $e->getMessage());
}
// END OF ADDED BLOCK
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
    <style>
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
    </style>
</head>

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
                    <a href="#" class="navbar-link active_link" data-nav-link>Home</a>
                </li>

                <li>
                    <a href="#about" class="navbar-link" data-nav-link>About us</a>
                </li>

                <li>
                    <a href="#contact" class="navbar-link" data-nav-link>Contact Us</a>
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

<main>
    <article>

        <!--
          - #HERO
        -->

        <section class="section hero" id="home">
            <div class="container">

                <div class="hero-content">
                    <h2 class="h1 hero-title">The easy way to takeover a lease</h2>

                    <p class="hero-text">
                        Live in Kasoa , Accra and Pokuase
                    </p>
                </div>

                <div class="hero-banner"></div>

                <form action="" class="hero-form">

                    <div class="input-wrapper">
                        <label for="input-1" class="input-label">Car, model, or brand</label>

                        <input type="text" name="car-model" id="input-1" class="input-field"
                               placeholder="What car are you looking?">
                    </div>

                    <div class="input-wrapper">
                        <label for="input-2" class="input-label">Amount</label>

                        <input type="text" name="monthly-pay" id="input-2" class="input-field" placeholder="Add an amount in GH₵">
                    </div>

                    <div class="input-wrapper">
                        <label for="input-3" class="input-label">Make Year</label>

                        <input type="text" name="year" id="input-3" class="input-field" placeholder="Add a minimal make year">
                    </div>

                    <button type="submit" class="btn">Search</button>

                </form>

            </div>
        </section>





        <!--
          - #FEATURED CAR
        -->

        <section class="section featured-car" id="featured-car">
            <div class="container">

                <div class="title-wrapper">
                    <h2 class="h2 section-title">Available Cars</h2>

                    <a href="explorecars.php" class="featured-car-link">
                        <span>View more</span>

                        <ion-icon name="arrow-forward-outline"></ion-icon>
                    </a>
                </div>

                <ul class="featured-car-list">
                    <?php if (!empty($featuredCars)): ?>
                        <?php foreach ($featuredCars as $car):
                            // Handle image paths
                            $mainImage = !empty($car['main_image']) ? '../' . ltrim($car['main_image'], '/') : '../assets/images/car-1.jpg';
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
                                            <a href="../Both/bookingpage.php?car_id=<?= $car['car_id'] ?>" class="btn">Rent now</a>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>

                    <?php else: ?>
                        <!-- Show static cars if no database cars available -->
                        <li>
                            <div class="featured-car-card">

                                <figure class="card-banner">
                                    <img src="../assets/images/car-1.jpg" alt="Toyota RAV4 2021" loading="lazy" width="440" height="300"
                                         class="w-100">
                                </figure>

                                <div class="card-content">

                                    <div class="card-title-wrapper">
                                        <h3 class="h3 card-title">
                                            <a href="../Both/bookingpage.php">Toyota RAV4</a>
                                        </h3>

                                        <data class="year" value="2021">2021</data>
                                    </div>

                                    <ul class="card-list">

                                        <li class="card-list-item">
                                            <ion-icon name="people-outline"></ion-icon>

                                            <span class="card-item-text">4 People</span>
                                        </li>

                                        <li class="card-list-item">
                                            <ion-icon name="flash-outline"></ion-icon>

                                            <span class="card-item-text">Hybrid</span>
                                        </li>

                                        <li class="card-list-item">
                                            <ion-icon name="speedometer-outline"></ion-icon>

                                            <span class="card-item-text">6.1km / 1-litre</span>
                                        </li>

                                        <li class="card-list-item">
                                            <ion-icon name="hardware-chip-outline"></ion-icon>

                                            <span class="card-item-text">Automatic</span>
                                        </li>

                                    </ul>

                                    <div class="card-price-wrapper">

                                        <p class="card-price">
                                            <strong>GH₵ 440</strong> / day
                                        </p>

                                        <button class="btn fav-btn" aria-label="Add to favourite list">
                                            <ion-icon name="heart-outline"></ion-icon>
                                        </button>

                                        <a href="../Both/bookingpage.php" class="btn">Rent now</a>

                                    </div>

                                </div>

                            </div>
                        </li>

                        <li>
                            <div class="featured-car-card">

                                <figure class="card-banner">
                                    <img src="../assets/images/car-2.jpg" alt="BMW 3 Series 2019" loading="lazy" width="440" height="300"
                                         class="w-100">
                                </figure>

                                <div class="card-content">

                                    <div class="card-title-wrapper">
                                        <h3 class="h3 card-title">
                                            <a href="../Both/bookingpage.php">BMW 3 Series</a>
                                        </h3>

                                        <data class="year" value="2019">2019</data>
                                    </div>

                                    <ul class="card-list">

                                        <li class="card-list-item">
                                            <ion-icon name="people-outline"></ion-icon>

                                            <span class="card-item-text">4 People</span>
                                        </li>

                                        <li class="card-list-item">
                                            <ion-icon name="flash-outline"></ion-icon>

                                            <span class="card-item-text">Gasoline</span>
                                        </li>

                                        <li class="card-list-item">
                                            <ion-icon name="speedometer-outline"></ion-icon>

                                            <span class="card-item-text">8.2km / 1-litre</span>
                                        </li>

                                        <li class="card-list-item">
                                            <ion-icon name="hardware-chip-outline"></ion-icon>

                                            <span class="card-item-text">Automatic</span>
                                        </li>

                                    </ul>

                                    <div class="card-price-wrapper">

                                        <p class="card-price">
                                            <strong>GH₵ 350</strong> / day
                                        </p>

                                        <button class="btn fav-btn" aria-label="Add to favourite list">
                                            <ion-icon name="heart-outline"></ion-icon>
                                        </button>

                                        <a href="../Both/bookingpage.php" class="btn">Rent now</a>

                                    </div>

                                </div>

                            </div>
                        </li>

                        <li>
                            <div class="featured-car-card">

                                <figure class="card-banner">
                                    <img src="../assets/images/car-3.jpg" alt="Volkswagen T-Cross 2020" loading="lazy" width="440"
                                         height="300" class="w-100">
                                </figure>

                                <div class="card-content">

                                    <div class="card-title-wrapper">
                                        <h3 class="h3 card-title">
                                            <a href="../Both/bookingpage.php">Volkswagen T-Cross</a>
                                        </h3>

                                        <data class="year" value="2020">2020</data>
                                    </div>

                                    <ul class="card-list">

                                        <li class="card-list-item">
                                            <ion-icon name="people-outline"></ion-icon>

                                            <span class="card-item-text">4 People</span>
                                        </li>

                                        <li class="card-list-item">
                                            <ion-icon name="flash-outline"></ion-icon>

                                            <span class="card-item-text">Gasoline</span>
                                        </li>

                                        <li class="card-list-item">
                                            <ion-icon name="speedometer-outline"></ion-icon>

                                            <span class="card-item-text">5.3km / 1-litre</span>
                                        </li>

                                        <li class="card-list-item">
                                            <ion-icon name="hardware-chip-outline"></ion-icon>

                                            <span class="card-item-text">Automatic</span>
                                        </li>

                                    </ul>

                                    <div class="card-price-wrapper">

                                        <p class="card-price">
                                            <strong>GH₵ 350</strong> / day
                                        </p>

                                        <button class="btn fav-btn" aria-label="Add to favourite list">
                                            <ion-icon name="heart-outline"></ion-icon>
                                        </button>

                                        <a href="../Both/bookingpage.php" class="btn">Rent now</a>

                                    </div>

                                </div>

                            </div>
                        </li>

                        <li>
                            <div class="featured-car-card">

                                <figure class="card-banner">
                                    <img src="../assets/images/car-4.jpg" alt="Cadillac Escalade 2020" loading="lazy" width="440"
                                         height="300" class="w-100">
                                </figure>

                                <div class="card-content">

                                    <div class="card-title-wrapper">
                                        <h3 class="h3 card-title">
                                            <a href="../Both/bookingpage.php">Cadillac Escalade</a>
                                        </h3>

                                        <data class="year" value="2020">2020</data>
                                    </div>

                                    <ul class="card-list">

                                        <li class="card-list-item">
                                            <ion-icon name="people-outline"></ion-icon>

                                            <span class="card-item-text">4 People</span>
                                        </li>

                                        <li class="card-list-item">
                                            <ion-icon name="flash-outline"></ion-icon>

                                            <span class="card-item-text">Gasoline</span>
                                        </li>

                                        <li class="card-list-item">
                                            <ion-icon name="speedometer-outline"></ion-icon>

                                            <span class="card-item-text">7.7km / 1-litre</span>
                                        </li>

                                        <li class="card-list-item">
                                            <ion-icon name="hardware-chip-outline"></ion-icon>

                                            <span class="card-item-text">Automatic</span>
                                        </li>

                                    </ul>

                                    <div class="card-price-wrapper">

                                        <p class="card-price">
                                            <strong>GH₵ 350</strong> / day
                                        </p>

                                        <button class="btn fav-btn" aria-label="Add to favourite list">
                                            <ion-icon name="heart-outline"></ion-icon>
                                        </button>

                                        <a href="../Both/bookingpage.php" class="btn">Rent now</a>

                                    </div>

                                </div>

                            </div>
                        </li>

                        <li>
                            <div class="featured-car-card">

                                <figure class="card-banner">
                                    <img src="../assets/images/car-5.jpg" alt="BMW 4 Series GTI 2021" loading="lazy" width="440"
                                         height="300" class="w-100">
                                </figure>

                                <div class="card-content">

                                    <div class="card-title-wrapper">
                                        <h3 class="h3 card-title">
                                            <a href="../Both/bookingpage.php">BMW 4 Series GTI</a>
                                        </h3>

                                        <data class="year" value="2021">2021</data>
                                    </div>

                                    <ul class="card-list">

                                        <li class="card-list-item">
                                            <ion-icon name="people-outline"></ion-icon>

                                            <span class="card-item-text">4 People</span>
                                        </li>

                                        <li class="card-list-item">
                                            <ion-icon name="flash-outline"></ion-icon>

                                            <span class="card-item-text">Gasoline</span>
                                        </li>

                                        <li class="card-list-item">
                                            <ion-icon name="speedometer-outline"></ion-icon>

                                            <span class="card-item-text">7.6km / 1-litre</span>
                                        </li>

                                        <li class="card-list-item">
                                            <ion-icon name="hardware-chip-outline"></ion-icon>

                                            <span class="card-item-text">Automatic</span>
                                        </li>

                                    </ul>

                                    <div class="card-price-wrapper">

                                        <p class="card-price">
                                            <strong>GH₵ 350</strong> / day
                                        </p>

                                        <button class="btn fav-btn" aria-label="Add to favourite list">
                                            <ion-icon name="heart-outline"></ion-icon>
                                        </button>

                                        <a href="../Both/bookingpage.php" class="btn">Rent now</a>

                                    </div>

                                </div>

                            </div>
                        </li>

                        <li>
                            <div class="featured-car-card">

                                <figure class="card-banner">
                                    <img src="../assets/images/car-6.jpg" alt="BMW 4 Series 2019" loading="lazy" width="440" height="300"
                                         class="w-100">
                                </figure>

                                <div class="card-content">

                                    <div class="card-title-wrapper">
                                        <h3 class="h3 card-title">
                                            <a href="../Both/bookingpage.php">BMW 4 Series</a>
                                        </h3>

                                        <data class="year" value="2019">2019</data>
                                    </div>

                                    <ul class="card-list">

                                        <li class="card-list-item">
                                            <ion-icon name="people-outline"></ion-icon>

                                            <span class="card-item-text">4 People</span>
                                        </li>

                                        <li class="card-list-item">
                                            <ion-icon name="flash-outline"></ion-icon>

                                            <span class="card-item-text">Gasoline</span>
                                        </li>

                                        <li class="card-list-item">
                                            <ion-icon name="speedometer-outline"></ion-icon>

                                            <span class="card-item-text">7.2km / 1-litre</span>
                                        </li>

                                        <li class="card-list-item">
                                            <ion-icon name="hardware-chip-outline"></ion-icon>

                                            <span class="card-item-text">Automatic</span>
                                        </li>

                                    </ul>

                                    <div class="card-price-wrapper">

                                        <p class="card-price">
                                            <strong>GH₵ 350</strong> / day
                                        </p>

                                        <button class="btn fav-btn" aria-label="Add to favourite list">
                                            <ion-icon name="heart-outline"></ion-icon>
                                        </button>

                                        <a href="../Both/bookingpage.php" class="btn">Rent now</a>

                                    </div>

                                </div>

                            </div>
                        </li>
                    <?php endif; ?>
                </ul>

            </div>
        </section>





        <!--
          - #GET START
        -->

        <section class="section get-start">
            <div class="container">

                <h2 class="h2 section-title">Get started with 4 simple steps</h2>

                <ul class="get-start-list">

                    <li>
                        <div class="get-start-card">

                            <div class="card-icon icon-1">
                                <ion-icon name="person-add-outline"></ion-icon>
                            </div>

                            <h3 class="card-title">Create a profile</h3>

                            <p class="card-text">
                                If you are going to use a passage of Lorem Ipsum, you need to be sure.
                            </p>

                            <a href="explorecars.php" class="card-link">Get started</a>

                        </div>
                    </li>

                    <li>
                        <div class="get-start-card">

                            <div class="card-icon icon-2">
                                <ion-icon name="car-outline"></ion-icon>
                            </div>

                            <h3 class="card-title">Tell us what car you want</h3>

                            <p class="card-text">
                                Various versions have evolved over the years, sometimes by accident, sometimes on purpose
                            </p>

                        </div>
                    </li>

                    <li>
                        <div class="get-start-card">

                            <div class="card-icon icon-3">
                                <ion-icon name="person-outline"></ion-icon>
                            </div>

                            <h3 class="card-title">Match with seller</h3>

                            <p class="card-text">
                                It to make a type specimen book. It has survived not only five centuries, but also the leap into
                                electronic
                            </p>

                        </div>
                    </li>

                    <li>
                        <div class="get-start-card">

                            <div class="card-icon icon-4">
                                <ion-icon name="card-outline"></ion-icon>
                            </div>

                            <h3 class="card-title">Make a deal</h3>

                            <p class="card-text">
                                There are many variations of passages of Lorem available, but the majority have suffered alteration
                            </p>

                        </div>
                    </li>

                </ul>

            </div>
        </section>





        <!--
          - #BLOG
        -->

        <section class="section blog" id="blog">
            <div class="container">

                <h2 class="h2 section-title">Our Blog</h2>

                <ul class="blog-list has-scrollbar">

                    <li>
                        <div class="blog-card">

                            <figure class="card-banner">

                                <a href="#">
                                    <img src="../assets/images/blog-1.jpg" alt="Opening of new offices of the company" loading="lazy"
                                         class="w-100">
                                </a>

                                <a href="#" class="btn card-badge">Company</a>

                            </figure>

                            <div class="card-content">

                                <h3 class="h3 card-title">
                                    <a href="#">Opening of new offices of the company</a>
                                </h3>

                                <div class="card-meta">

                                    <div class="publish-date">
                                        <ion-icon name="time-outline"></ion-icon>

                                        <time datetime="2022-01-14">January 14, 2022</time>
                                    </div>

                                    <div class="comments">
                                        <ion-icon name="chatbubble-ellipses-outline"></ion-icon>

                                        <data value="114">114</data>
                                    </div>

                                </div>

                            </div>

                        </div>
                    </li>

                    <li>
                        <div class="blog-card">

                            <figure class="card-banner">

                                <a href="#">
                                    <img src="../assets/images/blog-2.jpg" alt="What cars are most vulnerable" loading="lazy"
                                         class="w-100">
                                </a>

                                <a href="#" class="btn card-badge">Repair</a>

                            </figure>

                            <div class="card-content">

                                <h3 class="h3 card-title">
                                    <a href="#">What cars are most vulnerable</a>
                                </h3>

                                <div class="card-meta">

                                    <div class="publish-date">
                                        <ion-icon name="time-outline"></ion-icon>

                                        <time datetime="2022-01-14">January 14, 2022</time>
                                    </div>

                                    <div class="comments">
                                        <ion-icon name="chatbubble-ellipses-outline"></ion-icon>

                                        <data value="114">114</data>
                                    </div>

                                </div>

                            </div>

                        </div>
                    </li>

                    <li>
                        <div class="blog-card">

                            <figure class="card-banner">

                                <a href="#">
                                    <img src="../assets/images/blog-3.jpg" alt="Statistics showed which average age" loading="lazy"
                                         class="w-100">
                                </a>

                                <a href="#" class="btn card-badge">Cars</a>

                            </figure>

                            <div class="card-content">

                                <h3 class="h3 card-title">
                                    <a href="#">Statistics showed which average age</a>
                                </h3>

                                <div class="card-meta">

                                    <div class="publish-date">
                                        <ion-icon name="time-outline"></ion-icon>

                                        <time datetime="2022-01-14">January 14, 2022</time>
                                    </div>

                                    <div class="comments">
                                        <ion-icon name="chatbubble-ellipses-outline"></ion-icon>

                                        <data value="114">114</data>
                                    </div>

                                </div>

                            </div>

                        </div>
                    </li>

                    <li>
                        <div class="blog-card">

                            <figure class="card-banner">

                                <a href="#">
                                    <img src="../assets/images/blog-4.jpg" alt="What´s required when renting a car?" loading="lazy"
                                         class="w-100">
                                </a>

                                <a href="#" class="btn card-badge">Cars</a>

                            </figure>

                            <div class="card-content">

                                <h3 class="h3 card-title">
                                    <a href="#">What´s required when renting a car?</a>
                                </h3>

                                <div class="card-meta">

                                    <div class="publish-date">
                                        <ion-icon name="time-outline"></ion-icon>

                                        <time datetime="2022-01-14">January 14, 2022</time>
                                    </div>

                                    <div class="comments">
                                        <ion-icon name="chatbubble-ellipses-outline"></ion-icon>

                                        <data value="114">114</data>
                                    </div>

                                </div>

                            </div>

                        </div>
                    </li>

                    <li>
                        <div class="blog-card">

                            <figure class="card-banner">

                                <a href="#">
                                    <img src="../assets/images/blog-5.jpg" alt="New rules for handling our cars" loading="lazy"
                                         class="w-100">
                                </a>

                                <a href="#" class="btn card-badge">Company</a>

                            </figure>

                            <div class="card-content">

                                <h3 class="h3 card-title">
                                    <a href="#">New rules for handling our cars</a>
                                </h3>

                                <div class="card-meta">

                                    <div class="publish-date">
                                        <ion-icon name="time-outline"></ion-icon>

                                        <time datetime="2022-01-14">January 14, 2022</time>
                                    </div>

                                    <div class="comments">
                                        <ion-icon name="chatbubble-ellipses-outline"></ion-icon>

                                        <data value="114">114</data>
                                    </div>

                                </div>

                            </div>

                        </div>
                    </li>

                </ul>

            </div>
        </section>


        <section class="modern-about-section" id="about">
            <h2 class="modern-about-title">About Us</h2>
            <p class="modern-about-description">
                Welcome to our company! We are dedicated to providing premium car rental services with a focus on quality, reliability, and customer satisfaction. Our experienced team is passionate about making your journey smooth, safe, and enjoyable. Discover the difference with us—where your comfort is our commitment.
            </p>
            <div class="modern-about-cards">
                <!-- Mission Card -->
                <div class="modern-about-card">
                    <div class="modern-about-card__icon mission">
                        <ion-icon name="rocket-outline"></ion-icon>
                    </div>
                    <h3 class="modern-about-card__title">Our Mission</h3>
                    <p class="modern-about-card__desc">
                        To deliver exceptional car rental experiences by exceeding customer expectations with integrity, innovation, and top-notch service.
                    </p>
                </div>
                <!-- Vision Card -->
                <div class="modern-about-card">
                    <div class="modern-about-card__icon vision">
                        <ion-icon name="eye-outline"></ion-icon>
                    </div>
                    <h3 class="modern-about-card__title">Our Vision</h3>
                    <p class="modern-about-card__desc">
                        To become the most trusted and preferred car rental brand, setting new standards in the industry for quality and customer care.
                    </p>
                </div>
            </div>
        </section>





        <section class="modern-contactus-section" id="contact">
            <div class="modern-contactus-container">
                <!-- Left contact info, socials, and hours -->
                <div class="modern-contactus-info">
                    <div class="modern-contactus-titlewrap">
                        <span class="modern-contactus-titleicon"><ion-icon name="chatbubbles-outline"></ion-icon></span>
                        <h2 class="modern-contactus-title">Contact Us</h2>
                    </div>
                    <div class="modern-contactus-details">
                        <div class="modern-contactus-detail">
                            <ion-icon name="call-outline"></ion-icon>
                            <a href="tel:+01234567890">+012 345 67890</a>
                        </div>
                        <div class="modern-contactus-detail">
                            <ion-icon name="mail-outline"></ion-icon>
                            <a href="mailto:info@example.com">info@example.com</a>
                        </div>
                        <div class="modern-contactus-detail">
                            <ion-icon name="location-outline"></ion-icon>
                            <span>123 Street, New York, USA</span>
                        </div>
                    </div>
                    <div class="modern-contactus-hours">
                        <div class="modern-contactus-hours-row">
                            <ion-icon name="time-outline"></ion-icon>
                            <div>
                                <div><span class="modern-contactus-hours-label">Mon - Friday:</span> <span class="modern-contactus-hours-value">09.00 am to 07.00 pm</span></div>
                                <div><span class="modern-contactus-hours-label">Saturday:</span> <span class="modern-contactus-hours-value">10.00 am to 05.00 pm</span></div>
                                <div><span class="modern-contactus-hours-label">Vacation:</span> <span class="modern-contactus-hours-value">All Sunday is our vacation</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="modern-contactus-socials">
                        <a href="#" class="modern-contactus-social modern-contactus-social--facebook" title="Facebook"><ion-icon name="logo-facebook"></ion-icon></a>
                        <a href="#" class="modern-contactus-social modern-contactus-social--twitter" title="Twitter"><ion-icon name="logo-twitter"></ion-icon></a>
                        <a href="#" class="modern-contactus-social modern-contactus-social--instagram" title="Instagram"><ion-icon name="logo-instagram"></ion-icon></a>
                        <a href="#" class="modern-contactus-social modern-contactus-social--linkedin" title="LinkedIn"><ion-icon name="logo-linkedin"></ion-icon></a>
                    </div>
                </div>
                <!-- Right contact form -->
                <form class="modern-contactus-form">
                    <div class="modern-contactus-form-field">
                        <label for="name"><ion-icon name="person-outline"></ion-icon></label>
                        <input type="text" id="name" name="name" placeholder="Your Name" required>
                    </div>
                    <div class="modern-contactus-form-field">
                        <label for="email"><ion-icon name="mail-outline"></ion-icon></label>
                        <input type="email" id="email" name="email" placeholder="Your Email" required>
                    </div>
                    <div class="modern-contactus-form-field">
                        <label for="subject"><ion-icon name="bookmark-outline"></ion-icon></label>
                        <input type="text" id="subject" name="subject" placeholder="Subject" required>
                    </div>
                    <div class="modern-contactus-form-field">
                        <label for="message"><ion-icon name="chatbubble-ellipses-outline"></ion-icon></label>
                        <textarea id="message" name="message" placeholder="Your Message" required></textarea>
                    </div>
                    <button type="submit" class="modern-contactus-btn">
                        <span>Send Message</span>
                        <ion-icon name="send-outline"></ion-icon>
                    </button>
                </form>
            </div>
        </section>

    </article>
</main>


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
    });</script>

<script>const fadeCards = Array.from(document.querySelectorAll('.testimonial-fade-card'));
    const fadeDotsContainer = document.getElementById('testimonialFadeDots');
    const fadeArrowLeft = document.querySelector('.fade-arrow--left');
    const fadeArrowRight = document.querySelector('.fade-arrow--right');
    let fadeCurrent = 0;
    const fadeTotal = fadeCards.length;

    // Create navigation dots
    for (let i = 0; i < fadeTotal; i++) {
        const dot = document.createElement('div');
        dot.className = 'testimonials-dot' + (i === 0 ? ' active' : '');
        dot.dataset.idx = i;
        dot.addEventListener('click', () => fadeGoTo(i));
        fadeDotsContainer.appendChild(dot);
    }

    function fadeUpdate() {
        fadeCards.forEach((card, idx) => {
            card.classList.toggle('active', idx === fadeCurrent);
            fadeDotsContainer.children[idx].classList.toggle('active', idx === fadeCurrent);
        });
    }

    // Go to next/prev/target
    function fadeGoTo(idx) {
        fadeCurrent = (idx + fadeTotal) % fadeTotal;
        fadeUpdate();
        fadeResetAutoplay();
    }
    function fadeNext() { fadeGoTo(fadeCurrent + 1); }
    function fadePrev() { fadeGoTo(fadeCurrent - 1); }

    fadeArrowLeft.addEventListener('click', fadePrev);
    fadeArrowRight.addEventListener('click', fadeNext);

    // Touch swipe for mobile
    let fadeStartX = null;
    const fadeSlider = document.getElementById('testimonialFadeTrack');
    fadeSlider.addEventListener('touchstart', e => { fadeStartX = e.touches[0].clientX; });
    fadeSlider.addEventListener('touchmove', e => {
        if (fadeStartX === null) return;
        let diff = e.touches[0].clientX - fadeStartX;
        if (Math.abs(diff) > 45) {
            if (diff > 0) fadePrev(); else fadeNext();
            fadeStartX = null;
        }
    });
    fadeSlider.addEventListener('touchend', () => { fadeStartX = null; });

    // Mouse drag for desktop
    let fadeMouseStart = null;
    fadeSlider.addEventListener('mousedown', e => { fadeMouseStart = e.clientX; });
    fadeSlider.addEventListener('mousemove', e => {
        if (fadeMouseStart === null) return;
        let diff = e.clientX - fadeMouseStart;
        if (Math.abs(diff) > 60) {
            if (diff > 0) fadePrev(); else fadeNext();
            fadeMouseStart = null;
        }
    });
    fadeSlider.addEventListener('mouseup', () => { fadeMouseStart = null; });
    fadeSlider.addEventListener('mouseleave', () => { fadeMouseStart = null; });

    // Autoplay
    let fadeAutoplay = setInterval(fadeNext, 5000);
    function fadeResetAutoplay() {
        clearInterval(fadeAutoplay);
        fadeAutoplay = setInterval(fadeNext, 5000);
    }

    window.addEventListener('DOMContentLoaded', fadeUpdate);</script>

<script src="../assets/js/main.js"></script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

<!-- Hidden input for user type - set this value from your PHP backend -->
<input type="hidden" id="userType" value="<?php echo htmlspecialchars($userData['user_type']); ?>">

</body>
</html>