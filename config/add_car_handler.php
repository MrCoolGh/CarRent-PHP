<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$response = ['success' => false, 'message' => 'An error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- FILE UPLOAD HANDLING ---
    $uploadDir = '../assets/uploads/cars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Handle Main Image
    $mainImageName = '';
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $mainImageTmp = $_FILES['main_image']['tmp_name'];
        $mainImageName = time() . '_' . basename($_FILES['main_image']['name']);
        if (!move_uploaded_file($mainImageTmp, $uploadDir . $mainImageName)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload main image.']);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Main image is required. Error code: '.$_FILES['main_image']['error']]);
        exit();
    }

    // Handle Extra Images
    $extraImageNames = [];
    if (isset($_FILES['extra_images'])) {
        $extraImages = $_FILES['extra_images'];
        for ($i = 0; $i < count($extraImages['name']); $i++) {
            if ($extraImages['error'][$i] === UPLOAD_ERR_OK) {
                $extraImageTmp = $extraImages['tmp_name'][$i];
                $extraImageName = time() . '_extra_' . basename($extraImages['name'][$i]);
                if (move_uploaded_file($extraImageTmp, $uploadDir . $extraImageName)) {
                    $extraImageNames[] = $extraImageName;
                }
            }
        }
    }

    // --- DATABASE INSERTION ---
    $name = $_POST['name'] ?? '';
    $brand = $_POST['brand'] ?? '';
    $model = $_POST['model'] ?? '';
    $year = (int)($_POST['year'] ?? 0);
    $transmission = $_POST['transmission'] ?? 'automatic';
    $fuel_type = $_POST['fuel_type'] ?? 'petrol';
    $mileage = $_POST['mileage'] ?? '';
    $capacity = (int)($_POST['capacity'] ?? 0);
    $price_per_day = (float)($_POST['price_per_day'] ?? 0.0);
    $description = $_POST['description'] ?? '';
    $added_by = $_SESSION['user_id'];
    $extra_images_json = json_encode($extraImageNames);

    $sql = "INSERT INTO cars (name, brand, model, year, transmission, fuel_type, mileage, capacity, price_per_day, description, main_image, extra_images, status, added_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available', ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // FIX: The data type string now perfectly matches your database schema.
        // s = string, i = integer, d = double (for decimal)
        $stmt->bind_param(
            "sssisssidisssi",
            $name, $brand, $model, $year, $transmission, $fuel_type, $mileage, $capacity, $price_per_day, $description, $mainImageName, $extra_images_json, $added_by
        );

        if ($stmt->execute()) {
            $newCarId = $stmt->insert_id;

            // Fetch current user details for the response
            $userData = getUserById($added_by);
            $user_img_path = !empty($userData['profile_image']) ? '../assets/uploads/profiles/' . $userData['profile_image'] : '../assets/images/blog-4.jpg';

            $response['success'] = true;
            $response['message'] = 'Car added successfully!';
            $response['car'] = [
                'id' => $newCarId,
                'name' => $name,
                'brand' => $brand,
                'model' => $model,
                'transmission' => ucfirst($transmission),
                'fuel' => ucfirst($fuel_type),
                'year' => $year,
                'mileage' => $mileage,
                'people' => $capacity,
                'price' => $price_per_day,
                'description' => $description,
                'mainImg' => '../assets/uploads/cars/' . $mainImageName,
                'extras' => array_map(function($img) { return '../assets/uploads/cars/' . $img; }, $extraImageNames),
                'date' => date('Y-m-d'),
                'user' => [
                    'name' => htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']),
                    'img' => htmlspecialchars($user_img_path)
                ]
            ];
        } else {
            $response['message'] = 'Database error: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = 'Database prepare error: ' . $conn->error;
    }
}

$conn->close();
echo json_encode($response);
?>