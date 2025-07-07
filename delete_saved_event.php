<?php
session_start();
include 'sqlconnection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die("Unauthorized");
}

// Get POST data
$saved_id = isset($_POST['saved_id']) ? $_POST['saved_id'] : null;

if (!$saved_id) {
    http_response_code(400);
    die("Missing saved_id parameter");
}

// Prepare and execute the delete query
$stmt = $conn->prepare("DELETE FROM saved_events WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $saved_id, $_SESSION['user_id']);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        http_response_code(200);
        echo "Event removed successfully";
    } else {
        http_response_code(404);
        echo "Event not found or already deleted";
    }
} else {
    http_response_code(500);
    echo "Error deleting event: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
