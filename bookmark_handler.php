<?php
session_start(); // Start session to manage user login state
include 'sqlconnection.php'; // Include your database connection file

// Default response
$response = ['status' => 'error', 'message' => 'Invalid request'];

// Check if required data is provided
if (isset($_POST['job_id']) && isset($_POST['action'])) {
    $job_id = intval($_POST['job_id']); // Sanitize job ID
    $action = $_POST['action']; // Get action (save, remove, or check)
    $user_id = $_SESSION['user_id'] ?? 0; // Get user ID from session

    // Validate user ID and job ID
    if ($user_id && $job_id > 0) {
        switch ($action) {
            case 'save':
                // Check if the job is already bookmarked
                $sql_check = "SELECT * FROM bookmarks WHERE user_id = ? AND job_id = ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("ii", $user_id, $job_id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();

                if ($result_check->num_rows > 0) {
                    $response = ['status' => 'error', 'message' => 'Job already bookmarked'];
                } else {
                    // Save the job to bookmarks
                    $sql_save = "INSERT INTO bookmarks (user_id, job_id) VALUES (?, ?)";
                    $stmt_save = $conn->prepare($sql_save);
                    $stmt_save->bind_param("ii", $user_id, $job_id);

                    if ($stmt_save->execute()) {
                        $response = ['status' => 'success', 'message' => 'Job saved to bookmarks'];
                    } else {
                        $response = ['status' => 'error', 'message' => 'Failed to save job'];
                    }
                }
                break;

            case 'remove':
                // Remove the job from bookmarks
                $sql_remove = "DELETE FROM bookmarks WHERE user_id = ? AND job_id = ?";
                $stmt_remove = $conn->prepare($sql_remove);
                $stmt_remove->bind_param("ii", $user_id, $job_id);

                if ($stmt_remove->execute()) {
                    $response = ['status' => 'success', 'message' => 'Job removed from bookmarks'];
                } else {
                    $response = ['status' => 'error', 'message' => 'Failed to remove job'];
                }
                break;

            case 'check':
                // Check if the job is bookmarked
                $sql_check = "SELECT * FROM bookmarks WHERE user_id = ? AND job_id = ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("ii", $user_id, $job_id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();

                if ($result_check->num_rows > 0) {
                    $response = ['status' => 'success', 'is_bookmarked' => true];
                } else {
                    $response = ['status' => 'success', 'is_bookmarked' => false];
                }
                break;

            default:
                $response = ['status' => 'error', 'message' => 'Invalid action'];
                break;
        }
    } else {
        $response = ['status' => 'error', 'message' => 'User not logged in or invalid job ID'];
    }
}

// Check if required data is provided for events
if (isset($_POST['event_id']) && isset($_POST['action'])) {
    $event_id = intval($_POST['event_id']); // Sanitize event ID
    $action = $_POST['action']; // Get action (save or remove)
    $user_id = $_SESSION['user_id'] ?? 0; // Get user ID from session

    // Validate user ID and event ID
    if ($user_id && $event_id > 0) {
        switch ($action) {
            case 'save':
                // Check if the event is already bookmarked
                $sql_check = "SELECT * FROM saved_events WHERE user_id = ? AND event_id = ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("ii", $user_id, $event_id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();

                if ($result_check->num_rows > 0) {
                    $response = ['status' => 'error', 'message' => 'Event already bookmarked'];
                } else {
                    // Save the event to bookmarks
                    $sql_save = "INSERT INTO saved_events (user_id, event_id) VALUES (?, ?)";
                    $stmt_save = $conn->prepare($sql_save);
                    $stmt_save->bind_param("ii", $user_id, $event_id);

                    if ($stmt_save->execute()) {
                        $response = ['status' => 'success', 'message' => 'Event saved to bookmarks'];
                    } else {
                        $response = ['status' => 'error', 'message' => 'Failed to save event'];
                    }
                }
                break;

            case 'remove':
                // Remove the event from bookmarks
                $sql_remove = "DELETE FROM saved_events WHERE user_id = ? AND event_id = ?";
                $stmt_remove = $conn->prepare($sql_remove);
                $stmt_remove->bind_param("ii", $user_id, $event_id);

                if ($stmt_remove->execute()) {
                    $response = ['status' => 'success', 'message' => 'Event removed from bookmarks'];
                } else {
                    $response = ['status' => 'error', 'message' => 'Failed to remove event'];
                }
                break;

            default:
                $response = ['status' => 'error', 'message' => 'Invalid action'];
                break;
        }
    } else {
        $response = ['status' => 'error', 'message' => 'User not logged in or invalid event ID'];
    }
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);

// Close the database connection
$conn->close();
?>