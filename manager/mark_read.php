<?php
session_start();
require_once '../config/db.php';

// Ensure the user is logged in.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}

$current_user = $_SESSION['user_id'];
$sender_id = $_POST['sender_id'] ?? null;

if (!$sender_id) {
    http_response_code(400);
    exit("Missing sender_id parameter.");
}

$stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?");
$stmt->bind_param("ii", $current_user, $sender_id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to mark messages as read."]);
}

$stmt->close();
$conn->close();
?>
