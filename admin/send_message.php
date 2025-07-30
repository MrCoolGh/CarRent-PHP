<?php
session_start();
require_once '../config/db.php';

// Ensure the user is logged in.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}

$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'] ?? '';
$message_text = trim($_POST['message_text'] ?? '');

if (empty($receiver_id) || empty($message_text)) {
    http_response_code(400);
    exit("Missing parameters.");
}

$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $sender_id, $receiver_id, $message_text);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Message sent."]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to send message."]);
}

$stmt->close();
$conn->close();
?>