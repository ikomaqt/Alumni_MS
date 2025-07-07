<?php
session_start();
include 'sqlconnection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
$bookmark_id = $data['bookmark_id'] ?? null;

if (!$bookmark_id) {
    http_response_code(400);
    die('Invalid request');
}

// Delete the bookmark
$sql = "DELETE FROM bookmarks WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $bookmark_id, $_SESSION['user_id']);

if ($stmt->execute()) {
    http_response_code(200);
    echo 'Bookmark deleted successfully';
} else {
    http_response_code(500);
    echo 'Error deleting bookmark';
}

$conn->close();
?>
