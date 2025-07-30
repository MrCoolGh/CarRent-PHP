<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

// Ensure the user is authenticated.
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

// Fetch all active users from the database.
$query = "SELECT id, first_name, last_name, user_type, profile_image FROM users WHERE status = 'active'";
$result = $conn->query($query);

$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Return the list of users as JSON.
echo json_encode($users);
?>