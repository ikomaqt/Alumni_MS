<?php
session_start(); // Start the session to access user data
include 'sqlconnection.php'; // Include your database connection file

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode the JSON input
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate input data
    if (!isset($data['event_id']) || !isset($data['action'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);
        exit();
    }

    $event_id = intval($data['event_id']); // Sanitize event ID
    $action = $data['action']; // Action: 'check', 'save', or 'unsave'
    $user_id = $_SESSION['user_id'] ?? null; // Get user ID from session

    // Validate user ID
    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
        exit();
    }

    // Handle the action
    if ($action === 'check') {
        // Check if the event is bookmarked by the user
        $sql = "SELECT * FROM saved_events WHERE user_id = ? AND event_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $event_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo json_encode(['status' => 'success', 'isBookmarked' => true]);
        } else {
            echo json_encode(['status' => 'success', 'isBookmarked' => false]);
        }

        $stmt->close();
    } elseif ($action === 'save') {
        // Save the event for the user
        $sql = "INSERT INTO saved_events (user_id, event_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $event_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save event']);
        }

        $stmt->close();
    } elseif ($action === 'unsave') {
        // Unsave the event for the user
        $sql = "DELETE FROM saved_events WHERE user_id = ? AND event_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $event_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to unsave event']);
        }

        $stmt->close();
    } else {
        // Invalid action
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }

    $conn->close(); // Close the database connection
} else {
    // Invalid request method
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>