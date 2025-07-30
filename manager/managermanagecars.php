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

// Handle AJAX requests for CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'add') {
        // Add car
        $car_name = $_POST['car_name'];
        $transmission = $_POST['transmission'];
        $fuel_type = $_POST['fuel_type'];
        $year = $_POST['year'];
        $mileage = $_POST['mileage'];
        $people_capacity = $_POST['people_capacity'];
        $price_per_day = $_POST['price_per_day'];
        $description = $_POST['description'];
        $added_by = $userId;

        // Handle main image upload
        $main_image = null;
        if (!empty($_FILES['main_image']['name'])) {
            $main_image = 'assets/uploads/cars/' . time() . '_main_' . uniqid() . '.' . pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION);
            move_uploaded_file($_FILES['main_image']['tmp_name'], '../' . $main_image);
        }

        // Handle extra images
        $extra_images = [];
        if (!empty($_FILES['extra_images']['name'][0])) {
            foreach ($_FILES['extra_images']['tmp_name'] as $k => $tmp_name) {
                $extension = pathinfo($_FILES['extra_images']['name'][$k], PATHINFO_EXTENSION);
                $extra_img = 'assets/uploads/cars/' . time() . '_extra_' . uniqid() . '.' . $extension;
                move_uploaded_file($tmp_name, '../' . $extra_img);
                $extra_images[] = $extra_img;
            }
        }

        $stmt = $conn->prepare("INSERT INTO cars (car_name, transmission, fuel_type, year, mileage, people_capacity, price_per_day, description, main_image, extra_images, added_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiiidsssi",
            $car_name,
            $transmission,
            $fuel_type,
            $year,
            $mileage,
            $people_capacity,
            $price_per_day,
            $description,
            $main_image,
            json_encode($extra_images),
            $added_by
        );
        if ($stmt->execute()) {
            $car_id = $conn->insert_id;
            $result = $conn->query("SELECT c.*, u.first_name, u.last_name, u.profile_image FROM cars c LEFT JOIN users u ON c.added_by = u.id WHERE c.car_id = $car_id");
            $car = $result->fetch_assoc();
            echo json_encode(['success' => true, 'car' => $car]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'Failed to add car.']);
        }
        exit;
    }

    if ($_POST['action'] === 'edit') {
        // Edit car
        $car_id = $_POST['car_id'];
        $car_name = $_POST['car_name'];
        $transmission = $_POST['transmission'];
        $fuel_type = $_POST['fuel_type'];
        $year = $_POST['year'];
        $mileage = $_POST['mileage'];
        $people_capacity = $_POST['people_capacity'];
        $price_per_day = $_POST['price_per_day'];
        $description = $_POST['description'];

        // Handle main image upload (optional)
        $main_image = $_POST['current_main_image'];
        if (!empty($_FILES['main_image']['name'])) {
            $main_image = 'assets/uploads/cars/' . time() . '_main_' . uniqid() . '.' . pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION);
            move_uploaded_file($_FILES['main_image']['tmp_name'], '../' . $main_image);
        }

        // Handle extra images (optional)
        $extra_images = [];
        if (!empty($_POST['current_extra_images'])) {
            $extra_images = json_decode($_POST['current_extra_images'], true);
        }
        if (!empty($_FILES['extra_images']['name'][0])) {
            $extra_images = [];
            foreach ($_FILES['extra_images']['tmp_name'] as $k => $tmp_name) {
                $extension = pathinfo($_FILES['extra_images']['name'][$k], PATHINFO_EXTENSION);
                $extra_img = 'assets/uploads/cars/' . time() . '_extra_' . uniqid() . '.' . $extension;
                move_uploaded_file($tmp_name, '../' . $extra_img);
                $extra_images[] = $extra_img;
            }
        }

        $stmt = $conn->prepare("UPDATE cars SET car_name=?, transmission=?, fuel_type=?, year=?, mileage=?, people_capacity=?, price_per_day=?, description=?, main_image=?, extra_images=? WHERE car_id=?");
        $stmt->bind_param("sssiiidsssi",
            $car_name,
            $transmission,
            $fuel_type,
            $year,
            $mileage,
            $people_capacity,
            $price_per_day,
            $description,
            $main_image,
            json_encode($extra_images),
            $car_id
        );
        if ($stmt->execute()) {
            $result = $conn->query("SELECT c.*, u.first_name, u.last_name, u.profile_image FROM cars c LEFT JOIN users u ON c.added_by = u.id WHERE c.car_id = $car_id");
            $car = $result->fetch_assoc();
            echo json_encode(['success' => true, 'car' => $car]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'Failed to update car.']);
        }
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $car_id = $_POST['car_id'];
        $stmt = $conn->prepare("DELETE FROM cars WHERE car_id=?");
        $stmt->bind_param("i", $car_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'Failed to delete car.']);
        }
        exit;
    }

    if ($_POST['action'] === 'get') {
        $car_id = $_POST['car_id'];
        $result = $conn->query("SELECT c.*, u.first_name, u.last_name, u.profile_image FROM cars c LEFT JOIN users u ON c.added_by = u.id WHERE c.car_id = $car_id");
        $car = $result->fetch_assoc();
        echo json_encode(['success' => true, 'car' => $car]);
        exit;
    }
}

// FETCH CARS FROM DATABASE (default page load)
$cars = [];
try {
    $stmt = $conn->prepare("SELECT c.*, u.first_name, u.last_name, u.profile_image FROM cars c LEFT JOIN users u ON c.added_by = u.id ORDER BY c.created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $cars[] = $row;
        }
    }
} catch (Exception $e) {
    $cars = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>DASHBOARD</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="assets/img/favicon.ico" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="../assets/lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .img-preview {
            margin: 2px;
            border-radius: 6px;
            object-fit: cover;
            width: 65px;
            height: 45px;
            cursor: pointer;
        }
        /* Increased size for main car image */
        .main-car-img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            max-width: 400px;
        }
        .car-title {
            font-weight: bold;
            font-size: 1.25rem;
        }
        .car-price {
            background: green;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
    </style>
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
                        <img class="rounded-circle" src="../assets/images/car-1.jpg" alt="" style="width: 40px; height: 40px;">
                    <?php endif; ?>
                    <div class="bg-success rounded-circle border border-2 border-white position-absolute end-0 bottom-0 p-1"></div>
                </div>
                <div class="ms-3">
                    <h6 class="mb-0"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h6>
                    <span><?php echo ucfirst(htmlspecialchars($userData['user_type'])); ?></span>
                </div>
            </div>
            <div class="navbar-nav w-100">
                <a href="managerdashboard.php" class="nav-item nav-link">
                    <a href="managerdashboard.php" class="nav-item nav-link ">
                        <i class="fa fa-tachometer-alt me-2"></i>Dashboard
                    </a>

                    <a href="managerbookings.php" class="nav-item nav-link">
                        <i class="fa fa-calendar-check me-2"></i>Bookings
                    </a>

                    <a href="managermessage.php" class="nav-item nav-link" id="navMessageLink">
                        <i class="fa fa-envelope me-2"></i>Messages
                        <span id="navUnreadBadge" class="badge bg-danger ms-2" style="display:none;"></span>
                    </a>

                    <a href="managerform.php" class="nav-item nav-link">
                        <i class="fa fa-paper-plane me-2"></i>Forms
                    </a>

                    <a href="managermanagecars.php" class="nav-item nav-link active">
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
                            <img class="rounded-circle me-lg-2" src="../assets/images/car-6.jpg" alt="" style="width: 40px; height: 40px;">
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

        <!-- Manage Cars Start -->
        <div class="container-fluid pt-4 px-4">
            <div class="bg-light text-center rounded p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h6 class="mb-0">Manage Cars</h6>
                    <button class="btn btn-success btn-sm" onclick="showAddCarModal()">+ Add Car</button>
                </div>
                <div id="addCarSuccessMsg" class="alert alert-success py-2 px-3 d-none" role="alert">
                    Car added successfully!
                </div>
                <div id="editCarSuccessMsg" class="alert alert-success py-2 px-3 d-none" role="alert">
                    Car updated successfully!
                </div>
                <div id="deleteSuccessMsgCar" class="alert alert-success py-2 px-3 d-none" role="alert">
                    Car deleted successfully!
                </div>
            </div>
            <!-- Cars List -->
            <div class="row g-4 pt-4" id="carListRow">
                <?php foreach ($cars as $car):
                    $carUserName = htmlspecialchars(trim($car['first_name'] . ' ' . $car['last_name']));
                    $carUserImg = '../assets/images/blog-4.jpg';
                    if (!empty($car['profile_image'])) {
                        $possiblePaths = [
                            '../assets/uploads/profiles/' . basename($car['profile_image']),
                            $car['profile_image'],
                            '../assets/uploads/profiles/' . $car['profile_image']
                        ];
                        foreach ($possiblePaths as $imagePath) {
                            if (file_exists($imagePath)) {
                                $carUserImg = htmlspecialchars($imagePath);
                                break;
                            }
                        }
                    }
                    $mainImg = !empty($car['main_image']) ? '../' . ltrim($car['main_image'], '/') : '../assets/images/car-1.jpg';
                    $extrasArr = [];
                    if (!empty($car['extra_images']) && $car['extra_images'] !== '[]') {
                        $decoded = json_decode($car['extra_images'], true);
                        if (is_array($decoded)) {
                            foreach ($decoded as $imgPath) {
                                $extrasArr[] = '../' . ltrim($imgPath, '/');
                            }
                        }
                    }
                    if (!in_array($mainImg, $extrasArr)) {
                        array_unshift($extrasArr, $mainImg);
                    }
                    $extrasArr = array_slice($extrasArr, 0, 5);
                    $carDate = date("Y-m-d", strtotime($car['created_at']));
                    ?>
                    <div class="col-sm-12 col-md-6 col-xl-4 mb-4" id="carCard<?= $car['car_id'] ?>">
                        <div class="h-100 bg-light rounded p-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="btn btn-outline-info btn-sm">
                                    <i class="fa fa-calendar-alt me-1"></i>
                                    Added: <strong><?= $carDate ?></strong>
                                </span>
                                <span class="d-flex align-items-center">
                                    <img src="<?= $carUserImg ?>" alt="User" class="rounded-circle me-2" style="width: 28px; height: 28px;">
                                    <span class="fw-semibold"><?= $carUserName ?></span>
                                </span>
                            </div>
                            <div class="text-center mb-2">
                                <img id="mainCarImg<?= $car['car_id'] ?>" src="<?= $mainImg ?>" alt="<?= htmlspecialchars($car['car_name']) ?>" class="rounded mb-2 main-car-img">
                            </div>
                            <!-- Extra images thumbnails with hover effect to show main image -->
                            <div class="d-flex justify-content-center gap-2 mb-3">
                                <?php foreach ($extrasArr as $idx => $img): ?>
                                    <img src="<?= $img ?>" alt="Extra<?= $idx ?>" class="rounded car-thumb-img <?= $idx === 0 ? 'border-primary border' : '' ?>"
                                         style="width:65px; height:45px; object-fit:cover; cursor:pointer;"
                                         onmouseover="changeMainCarImg(<?= $car['car_id'] ?>, '<?= $img ?>')"
                                    />
                                <?php endforeach; ?>
                            </div>
                            <!-- Car title comes first -->
                            <h5 class="car-title text-center mb-3"><i class="fa fa-car me-1"></i><?= htmlspecialchars($car['car_name']) ?></h5>
                            <!-- Car details after the title -->
                            <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                                <span class="btn btn-outline-secondary btn-sm"><i class="fa fa-cogs me-1"></i><?= htmlspecialchars($car['transmission']) ?></span>
                                <span class="btn btn-outline-secondary btn-sm"><i class="fa fa-gas-pump me-1"></i><?= htmlspecialchars($car['fuel_type']) ?></span>
                                <span class="btn btn-outline-secondary btn-sm"><i class="fa fa-calendar me-1"></i><?= htmlspecialchars($car['year']) ?></span>
                                <span class="btn btn-outline-secondary btn-sm"><i class="fa fa-road me-1"></i><?= htmlspecialchars($car['mileage']) ?> km</span>
                                <span class="btn btn-outline-secondary btn-sm"><i class="fa fa-user-friends me-1"></i><?= htmlspecialchars($car['people_capacity']) ?> People</span>
                            </div>
                            <!-- Car description box -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold"><i class="fa fa-align-left me-1"></i>Description</label>
                                <textarea class="form-control bg-light" id="carDescription<?= $car['car_id'] ?>" rows="2" readonly><?= htmlspecialchars($car['description']) ?></textarea>
                            </div>
                            <!-- Car price with green background -->
                            <div class="text-center mb-3">
                                <span class="car-price"><i class="fa fa-money-bill-alt me-1"></i>GH₵ <?= htmlspecialchars($car['price_per_day']) ?> / day</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-primary me-1" onclick="showEditCarModal(<?= $car['car_id'] ?>)">Edit</button>
                                <button class="btn btn-danger me-1" onclick="deleteCar(<?= $car['car_id'] ?>)">Delete</button>
                                <button class="btn btn-info" onclick="showViewCarModal(<?= $car['car_id'] ?>)">View</button>
                            </div>
                            <div id="carSuccessMsg<?= $car['car_id'] ?>" class="alert alert-success mt-3 py-2 px-3 d-none" role="alert">
                                Success!
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- Manage Cars End -->

        <!-- Add Car Modal -->
        <div class="modal fade" id="addCarModal" tabindex="-1" aria-labelledby="addCarModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" id="addCarForm" onsubmit="saveNewCar();return false;">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCarModalLabel">Add New Car</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Car Name -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fa fa-car me-1"></i>Car Name</label>
                            <input type="text" class="form-control" id="addCarName" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <!-- Transmission -->
                                <label class="form-label"><i class="fa fa-cogs me-1"></i>Transmission</label>
                                <select class="form-select" id="addCarTransmission" required>
                                    <option value="">Select transmission</option>
                                    <option value="Automatic">Automatic</option>
                                    <option value="Manual">Manual</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <!-- Fuel Type -->
                                <label class="form-label"><i class="fa fa-gas-pump me-1"></i>Fuel Type</label>
                                <select class="form-select" id="addCarFuelType" required>
                                    <option value="">Select fuel type</option>
                                    <option value="Petrol">Petrol</option>
                                    <option value="Diesel">Diesel</option>
                                    <option value="Electric">Electric</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <!-- Year -->
                                <label class="form-label"><i class="fa fa-calendar me-1"></i>Year</label>
                                <input type="number" class="form-control" id="addCarYear" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <!-- Mileage -->
                                <label class="form-label"><i class="fa fa-road me-1"></i>Mileage</label>
                                <input type="text" class="form-control" id="addCarMileage" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <!-- People -->
                                <label class="form-label"><i class="fa fa-user-friends me-1"></i>People</label>
                                <input type="number" class="form-control" id="addCarPeople" min="1" required>
                            </div>
                        </div>
                        <!-- Price per Day -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fa fa-money-bill-alt me-1"></i>Amount / Day (GH₵)</label>
                            <input type="number" class="form-control" id="addCarPrice" required>
                        </div>
                        <!-- Description -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fa fa-align-left me-1"></i>Description</label>
                            <textarea class="form-control" id="addCarDescription" rows="2" required></textarea>
                        </div>
                        <!-- Main Image -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fa fa-image me-1"></i>Main Picture</label>
                            <input type="file" class="form-control" id="addCarMainImg" accept="image/*" required>
                        </div>
                        <!-- Extra Images -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fa fa-images me-1"></i>Extra Pictures (4)</label>
                            <input type="file" class="form-control" id="addCarExtraImgs" accept="image/*" multiple>
                            <small class="form-text text-muted">Add up to 4 extra pictures.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Add Car</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                    <div id="addCarModalSuccessMsg" class="alert alert-success mt-2 py-2 px-3 d-none w-100" role="alert">
                        Car added successfully!
                    </div>
                </form>
            </div>
        </div>
        <!-- End Add Car Modal -->

        <!-- Edit Car Modal -->
        <div class="modal fade" id="editCarModal" tabindex="-1" aria-labelledby="editCarModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" id="editCarForm" onsubmit="saveEditCar();return false;">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCarModalLabel">Edit Car Information</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Car Name -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fa fa-car me-1"></i>Car Name</label>
                            <input type="text" class="form-control" id="editCarName" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <!-- Transmission -->
                                <label class="form-label"><i class="fa fa-cogs me-1"></i>Transmission</label>
                                <select class="form-select" id="editCarTransmission" required>
                                    <option value="Automatic">Automatic</option>
                                    <option value="Manual">Manual</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <!-- Fuel Type -->
                                <label class="form-label"><i class="fa fa-gas-pump me-1"></i>Fuel Type</label>
                                <select class="form-select" id="editCarFuelType" required>
                                    <option value="Petrol">Petrol</option>
                                    <option value="Diesel">Diesel</option>
                                    <option value="Electric">Electric</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <!-- Year -->
                                <label class="form-label"><i class="fa fa-calendar me-1"></i>Year</label>
                                <input type="number" class="form-control" id="editCarYear" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <!-- Mileage -->
                                <label class="form-label"><i class="fa fa-road me-1"></i>Mileage</label>
                                <input type="text" class="form-control" id="editCarMileage" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <!-- People -->
                                <label class="form-label"><i class="fa fa-user-friends me-1"></i>People</label>
                                <input type="number" class="form-control" id="editCarPeople" min="1" required>
                            </div>
                        </div>
                        <!-- Price per Day -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fa fa-money-bill-alt me-1"></i>Amount / Day (GH₵)</label>
                            <input type="number" class="form-control" id="editCarPrice" required>
                        </div>
                        <!-- Description -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fa fa-align-left me-1"></i>Description</label>
                            <textarea class="form-control" id="editCarDescription" rows="2" required></textarea>
                        </div>
                        <!-- Main Image -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fa fa-image me-1"></i>Main Picture</label>
                            <input type="file" class="form-control" id="editCarMainImg" accept="image/*">
                            <img id="editCarMainImgPreview" src="" alt="" class="mt-2 rounded" style="width:120px; height:60px; object-fit:cover; display:none;">
                        </div>
                        <!-- Extra Images -->
                        <div class="mb-3">
                            <label class="form-label"><i class="fa fa-images me-1"></i>Extra Pictures (4)</label>
                            <input type="file" class="form-control" id="editCarExtraImgs" accept="image/*" multiple>
                            <div id="editCarExtrasPreview" class="d-flex gap-2 mt-2"></div>
                            <small class="form-text text-muted">Add up to 4 extra pictures.</small>
                        </div>
                        <input type="hidden" id="editCarId">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save Changes</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                    <div id="editCarSuccessMsg" class="alert alert-success mt-2 py-2 px-3 d-none w-100" role="alert">
                        Car updated successfully!
                    </div>
                </form>
            </div>
        </div>
        <!-- End Edit Car Modal -->

        <!-- View Car Modal without user info -->
        <div class="modal fade" id="viewCarModal" tabindex="-1" aria-labelledby="viewCarModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewCarModalLabel">Car Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Display Added Date -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="btn btn-outline-info btn-sm">
                                <i class="fa fa-calendar-alt me-1"></i>
                                Added: <strong id="viewCarDate"></strong>
                            </span>
                        </div>
                        <!-- Main Car Image -->
                        <div class="text-center mb-2">
                            <img id="viewMainCarImg" src="" alt="Main Car" class="rounded mb-2" style="width:100%; height:300px; object-fit:cover; max-width:400px;">
                        </div>
                        <!-- Extra Images -->
                        <div class="d-flex justify-content-center gap-2 mb-3" id="viewCarExtras"></div>
                        <!-- Car Details -->
                        <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                            <span class="btn btn-outline-primary btn-sm" id="viewCarName"></span>
                            <span class="btn btn-outline-secondary btn-sm" id="viewCarTrans"></span>
                            <span class="btn btn-outline-secondary btn-sm" id="viewCarFuel"></span>
                            <span class="btn btn-outline-secondary btn-sm" id="viewCarYear"></span>
                            <span class="btn btn-outline-secondary btn-sm" id="viewCarMileage"></span>
                            <span class="btn btn-outline-secondary btn-sm" id="viewCarPeople"></span>
                            <span class="btn btn-outline-success btn-sm" id="viewCarPrice"></span>
                        </div>
                        <!-- Car Description -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fa fa-align-left me-1"></i>Description
                            </label>
                            <textarea class="form-control bg-light" id="viewCarDescription" rows="3" readonly style="resize:none"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End View Car Modal -->

        <!-- Delete Confirmation Modal for Cars -->
        <div class="modal fade" id="deleteConfirmModalCars" tabindex="-1" aria-labelledby="deleteConfirmModalCarsLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title" id="deleteConfirmModalCarsLabel">
                            <i class="fa fa-exclamation-triangle text-warning me-2"></i>Confirm Delete
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <p class="mb-0">Are you sure you want to delete this car? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtnCars">Yes, Delete</button>
                    </div>
                </div>
            </div>
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

            // Helper: show/hide alerts
            function showAlert(id) {
                document.querySelectorAll('.alert').forEach(el => el.classList.add('d-none'));
                document.getElementById(id).classList.remove('d-none');
                setTimeout(() => document.getElementById(id).classList.add('d-none'), 2000);
            }

            // Add Car
            function showAddCarModal() {
                document.getElementById("addCarForm").reset();
                var modal = new bootstrap.Modal(document.getElementById('addCarModal'));
                modal.show();
            }

            function saveNewCar() {
                let formData = new FormData();
                formData.append('action', 'add');
                formData.append('car_name', document.getElementById('addCarName').value);
                formData.append('transmission', document.getElementById('addCarTransmission').value);
                formData.append('fuel_type', document.getElementById('addCarFuelType').value);
                formData.append('year', document.getElementById('addCarYear').value);
                formData.append('mileage', document.getElementById('addCarMileage').value);
                formData.append('people_capacity', document.getElementById('addCarPeople').value);
                formData.append('price_per_day', document.getElementById('addCarPrice').value);
                formData.append('description', document.getElementById('addCarDescription').value);
                if(document.getElementById('addCarMainImg').files.length > 0) {
                    formData.append('main_image', document.getElementById('addCarMainImg').files[0]);
                }
                if(document.getElementById('addCarExtraImgs').files.length > 0) {
                    for(let i = 0; i < document.getElementById('addCarExtraImgs').files.length; i++) {
                        formData.append('extra_images[]', document.getElementById('addCarExtraImgs').files[i]);
                    }
                }
                fetch(location.href, {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.text())
                    .then(responseText => {
                        const jsonStart = responseText.indexOf('{');
                        if (jsonStart >= 0) {
                            const jsonStr = responseText.substring(jsonStart);
                            try {
                                const data = JSON.parse(jsonStr);
                                if(data.success && data.car) {
                                    location.reload();
                                    showAlert('addCarSuccessMsg');
                                } else {
                                    alert('Failed to add car: ' + (data.msg || 'Unknown error'));
                                }
                            } catch (e) {
                                console.error("JSON parse error:", e);
                                alert('Error parsing JSON response. Check console for details.');
                            }
                        } else {
                            console.error("No JSON found in response:", responseText);
                            alert('Server returned an invalid response. Check console for details.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while adding the car: ' + error.message);
                    });
                return false;
            }

            // Edit Car
            function showEditCarModal(carId) {
                fetch(location.href, {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: 'action=get&car_id='+encodeURIComponent(carId)
                })
                    .then(response => response.text())
                    .then(responseText => {
                        const jsonStart = responseText.indexOf('{');
                        if (jsonStart >= 0) {
                            const jsonStr = responseText.substring(jsonStart);
                            try {
                                const data = JSON.parse(jsonStr);
                                if(data.success) {
                                    let car = data.car;
                                    document.getElementById('editCarId').value = car.car_id;
                                    document.getElementById('editCarName').value = car.car_name;
                                    document.getElementById('editCarTransmission').value = car.transmission;
                                    document.getElementById('editCarFuelType').value = car.fuel_type;
                                    document.getElementById('editCarYear').value = car.year;
                                    document.getElementById('editCarMileage').value = car.mileage;
                                    document.getElementById('editCarPeople').value = car.people_capacity;
                                    document.getElementById('editCarPrice').value = car.price_per_day;
                                    document.getElementById('editCarDescription').value = car.description;
                                    document.getElementById('editCarMainImgPreview').src = '../' + car.main_image;
                                    document.getElementById('editCarMainImgPreview').style.display = car.main_image ? 'block' : 'none';
                                    document.getElementById('editCarMainImg').value = "";
                                    document.getElementById('editCarExtrasPreview').innerHTML = "";
                                    let extras = [];
                                    try { extras = JSON.parse(car.extra_images); } catch(e){}
                                    extras.forEach(function(img) {
                                        document.getElementById('editCarExtrasPreview').innerHTML += `<img src="../${img}" class="img-preview">`;
                                    });
                                    if(!document.getElementById('editCarCurrentMainImg')) {
                                        let m = document.createElement('input');
                                        m.type = "hidden"; m.id = "editCarCurrentMainImg"; m.name="current_main_image";
                                        document.getElementById("editCarForm").appendChild(m);
                                    }
                                    document.getElementById('editCarCurrentMainImg').value = car.main_image;
                                    if(!document.getElementById('editCarCurrentExtras')) {
                                        let t = document.createElement('input');
                                        t.type = "hidden"; t.id = "editCarCurrentExtras"; t.name="current_extra_images";
                                        document.getElementById("editCarForm").appendChild(t);
                                    }
                                    document.getElementById('editCarCurrentExtras').value = car.extra_images;
                                    var modal = new bootstrap.Modal(document.getElementById('editCarModal'));
                                    modal.show();
                                }
                            } catch (e) {
                                console.error("JSON parse error:", e);
                                alert('Error parsing JSON response. Check console for details.');
                            }
                        } else {
                            console.error("No JSON found in response:", responseText);
                            alert('Server returned an invalid response. Check console for details.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while fetching car data: ' + error.message);
                    });
            }

            function saveEditCar() {
                let formData = new FormData();
                formData.append('action', 'edit');
                formData.append('car_id', document.getElementById('editCarId').value);
                formData.append('car_name', document.getElementById('editCarName').value);
                formData.append('transmission', document.getElementById('editCarTransmission').value);
                formData.append('fuel_type', document.getElementById('editCarFuelType').value);
                formData.append('year', document.getElementById('editCarYear').value);
                formData.append('mileage', document.getElementById('editCarMileage').value);
                formData.append('people_capacity', document.getElementById('editCarPeople').value);
                formData.append('price_per_day', document.getElementById('editCarPrice').value);
                formData.append('description', document.getElementById('editCarDescription').value);
                formData.append('current_main_image', document.getElementById('editCarCurrentMainImg').value);
                formData.append('current_extra_images', document.getElementById('editCarCurrentExtras').value);
                if(document.getElementById('editCarMainImg').files.length > 0) {
                    formData.append('main_image', document.getElementById('editCarMainImg').files[0]);
                }
                if(document.getElementById('editCarExtraImgs').files.length > 0) {
                    for(let i = 0; i < document.getElementById('editCarExtraImgs').files.length; i++) {
                        formData.append('extra_images[]', document.getElementById('editCarExtraImgs').files[i]);
                    }
                }
                fetch(location.href, {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.text())
                    .then(responseText => {
                        const jsonStart = responseText.indexOf('{');
                        if (jsonStart >= 0) {
                            const jsonStr = responseText.substring(jsonStart);
                            try {
                                const data = JSON.parse(jsonStr);
                                if(data.success && data.car) {
                                    location.reload();
                                    showAlert('editCarSuccessMsg');
                                } else {
                                    alert('Failed to update car: ' + (data.msg || 'Unknown error'));
                                }
                            } catch (e) {
                                console.error("JSON parse error:", e);
                                alert('Error parsing JSON response. Check console for details.');
                            }
                        } else {
                            console.error("No JSON found in response:", responseText);
                            alert('Server returned an invalid response. Check console for details.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the car: ' + error.message);
                    });
                return false;
            }

            function showViewCarModal(carId) {
                fetch(location.href, {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: 'action=get&car_id='+encodeURIComponent(carId)
                })
                    .then(response => response.text())
                    .then(responseText => {
                        const jsonStart = responseText.indexOf('{');
                        if (jsonStart >= 0) {
                            const jsonStr = responseText.substring(jsonStart);
                            try {
                                const data = JSON.parse(jsonStr);
                                if(data.success) {
                                    let car = data.car;
                                    document.getElementById('viewCarDate').textContent = car.created_at.split(' ')[0];
                                    document.getElementById('viewMainCarImg').src = car.main_image ? '../'+car.main_image : '../assets/images/car-1.jpg';
                                    let extras = [];
                                    try { extras = JSON.parse(car.extra_images); } catch(e){}
                                    document.getElementById('viewCarExtras').innerHTML = '';
                                    extras.forEach(function(img){
                                        document.getElementById('viewCarExtras').innerHTML += `<img src="../${img}" class="img-preview">`;
                                    });
                                    document.getElementById('viewCarName').textContent = car.car_name;
                                    document.getElementById('viewCarTrans').textContent = car.transmission;
                                    document.getElementById('viewCarFuel').textContent = car.fuel_type;
                                    document.getElementById('viewCarYear').textContent = car.year;
                                    document.getElementById('viewCarMileage').textContent = car.mileage;
                                    document.getElementById('viewCarPeople').textContent = car.people_capacity + ' People';
                                    document.getElementById('viewCarPrice').textContent = 'GH₵ ' + car.price_per_day + ' / day';
                                    document.getElementById('viewCarDescription').value = car.description;
                                    var modal = new bootstrap.Modal(document.getElementById('viewCarModal'));
                                    modal.show();
                                }
                            } catch (e) {
                                console.error("JSON parse error:", e);
                                alert('Error parsing JSON response. Check console for details.');
                            }
                        } else {
                            console.error("No JSON found in response:", responseText);
                            alert('Server returned an invalid response. Check console for details.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while loading car details: ' + error.message);
                    });
            }

            let deleteCarId = null;
            function deleteCar(carId) {
                deleteCarId = carId;
                var modal = new bootstrap.Modal(document.getElementById('deleteConfirmModalCars'));
                modal.show();
            }
            document.getElementById('confirmDeleteBtnCars').onclick = function() {
                fetch(location.href, {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: 'action=delete&car_id='+encodeURIComponent(deleteCarId)
                })
                    .then(response => response.text())
                    .then(responseText => {
                        const jsonStart = responseText.indexOf('{');
                        if (jsonStart >= 0) {
                            const jsonStr = responseText.substring(jsonStart);
                            try {
                                const data = JSON.parse(jsonStr);
                                if(data.success) {
                                    document.getElementById('carCard'+deleteCarId).remove();
                                    showAlert('deleteSuccessMsgCar');
                                } else {
                                    alert('Failed to delete car: ' + (data.msg || 'Unknown error'));
                                }
                            } catch (e) {
                                console.error("JSON parse error:", e);
                                alert('Error parsing JSON response. Check console for details.');
                            }
                        } else {
                            console.error("No JSON found in response:", responseText);
                            alert('Server returned an invalid response. Check console for details.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the car: ' + error.message);
                    });
                bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModalCars')).hide();
            }

            // Change main car image on hover over small images
            function changeMainCarImg(carId, imgSrc) {
                document.getElementById('mainCarImg' + carId).src = imgSrc;
                document.querySelectorAll('#carCard' + carId + ' .car-thumb-img').forEach(img => {
                    img.classList.remove('border-primary', 'border');
                    if (img.src === imgSrc) {
                        img.classList.add('border-primary', 'border');
                    }
                });
            }
        </script>

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

<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/lib/chart/chart.min.js"></script>
<script src="../assets/lib/easing/easing.min.js"></script>
<script src="../assets/lib/waypoints/waypoints.min.js"></script>
<script src="../assets/lib/owlcarousel/owl.carousel.min.js"></script>
<script src="../assets/lib/tempusdominus/js/moment.min.js"></script>
<script src="../assets/lib/tempusdominus/js/moment-timezone.min.js"></script>
<script src="../assets/lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>
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