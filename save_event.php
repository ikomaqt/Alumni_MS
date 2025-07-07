<?php
session_start();
include 'sqlconnection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

// Handle event saving/unsaving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['event_id'])) {
    $event_id = intval($_POST['event_id']);
    $user_id = $_SESSION['user_id'];
    
    try {
        if ($_POST['action'] === 'save') {
            // Check if already saved
            $check_sql = "SELECT * FROM saved_events WHERE user_id = ? AND event_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ii", $user_id, $event_id);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows === 0) {
                $sql = "INSERT INTO saved_events (user_id, event_id, saved_at) VALUES (?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $user_id, $event_id);
                $stmt->execute();
            }
        } else {
            $sql = "DELETE FROM saved_events WHERE user_id = ? AND event_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $user_id, $event_id);
            $stmt->execute();
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    $conn->close();
    exit;
}

// Invalid request
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
