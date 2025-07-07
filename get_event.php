<?php
header('Content-Type: application/json');
require_once 'sqlconnection.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('No ID provided');
    }

    $id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT 
        id,
        event_title,
        DATE_FORMAT(event_date, '%Y-%m-%d') as event_date,
        DATE_FORMAT(event_start_time, '%H:%i') as event_start_time,
        DATE_FORMAT(event_end_time, '%H:%i') as event_end_time,
        event_description,
        event_place,
        event_image,
        status
    FROM events WHERE id = ?");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement');
    }

    $stmt->bind_param("i", $id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query');
    }

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        throw new Exception('Event not found');
    }

    // Add additional formatted dates for display
    $row['formatted_date'] = date('F d, Y', strtotime($row['event_date']));
    $row['formatted_start_time'] = date('h:i A', strtotime($row['event_start_time']));
    $row['formatted_end_time'] = date('h:i A', strtotime($row['event_end_time']));

    echo json_encode([
        'success' => true,
        'data' => $row
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
