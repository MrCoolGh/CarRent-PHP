<?php
// fetch_bookings.php
header('Content-Type: application/json');

// Include your database connection
require_once '../config/db.php';

// Example query - adjust table/column names to your schema
// Assumes you have tables: bookings, cars, users
$sql = "
    SELECT 
        b.id,
        b.pickup_time,
        b.dropoff_time,
        b.status,
        c.name AS car_name,
        u.first_name,
        u.last_name
    FROM bookings b
    JOIN cars c ON b.car_id = c.id
    JOIN users u ON b.user_id = u.id
    ORDER BY b.pickup_time ASC
";

$result = $conn->query($sql);

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = [
        "car" => ["name" => $row['car_name']],
        "customer" => ["name" => $row['first_name'] . " " . $row['last_name']],
        "pickupTime" => $row['pickup_time'],
        "dropoffTime" => $row['dropoff_time'],
        "status" => $row['status']
    ];
}

echo json_encode($bookings);
?>