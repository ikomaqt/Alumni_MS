<?php
// submit_event.php

include 'admin.php'; // Include necessary files

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $eventTitle = $_POST['eventtitle'];
    $eventDate = $_POST['eventdate'];
    $eventStartTime = $_POST['eventstarttime'];
    $eventEndTime = $_POST['eventendtime'];
    $eventDescription = $_POST['eventdescription'];
    $eventPlace = $_POST['eventplace'];

    // Validate and sanitize input (you should add proper validation and sanitization)
    // ...

    // Insert into database (assuming you have a function to do this)
    // Example: $success = insertEvent($eventTitle, $eventDate, $eventStartTime, $eventEndTime, $eventDescription, $eventPlace);

    // For demonstration purposes, let's assume the insertion was successful
    $success = true;

    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Event posted successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to post event.']);
    }
    exit;
}
?>