<?php
session_start();
require_once '../config/db.php';

// Ensure the user is logged in.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}

$current_user = $_SESSION['user_id'];

// Get unread messages counts grouped by sender_id (for messages sent to the logged in user).
$stmt = $conn->prepare("SELECT sender_id, COUNT(*) AS count FROM messages WHERE receiver_id = ? AND is_read = 0 GROUP BY sender_id");
$stmt->bind_param("i", $current_user);
$stmt->execute();
$result = $stmt->get_result();

$counts = [];
while ($row = $result->fetch_assoc()) {
    $counts[$row['sender_id']] = intval($row['count']);
}

header('Content-Type: application/json');
echo json_encode($counts);

$stmt->close();
$conn->close();
?>