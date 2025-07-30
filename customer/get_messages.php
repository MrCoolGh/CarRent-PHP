<?php
session_start();
require_once '../config/db.php';

// Ensure the user is logged in.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}

$current_user = $_SESSION['user_id'];
$other_user = $_GET['receiver_id'] ?? '';

if (empty($other_user)) {
    http_response_code(400);
    exit("Receiver not specified.");
}

$stmt = $conn->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY sent_at ASC");
$stmt->bind_param("iiii", $current_user, $other_user, $other_user, $current_user);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

header('Content-Type: application/json');
echo json_encode($messages);

$stmt->close();
$conn->close();
?>