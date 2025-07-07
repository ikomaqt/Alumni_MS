<?php
session_start();
require_once 'sqlconnection.php';

if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];

// Handle mark as read
if (isset($_POST['notification_id']) || isset($_POST['mark_all'])) {
    if (isset($_POST['mark_all'])) {
        // Mark all notifications as read
        $stmt = $conn->prepare("
            INSERT IGNORE INTO notifications_read (user_id, notification_id)
            SELECT ?, CONCAT('event-', id)
            FROM events 
            WHERE created_at >= (SELECT created_at FROM users WHERE user_id = ?)
            UNION ALL
            SELECT ?, CONCAT('job-', job_id)
            FROM job_listings 
            WHERE created_at >= (SELECT created_at FROM users WHERE user_id = ?)
            AND (posted_by_id IS NULL OR posted_by_id != ?)
        ");
        $stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
        $success = $stmt->execute();
    } else {
        // Mark single notification as read
        $notification_id = $_POST['notification_id'];
        $stmt = $conn->prepare("INSERT IGNORE INTO notifications_read (user_id, notification_id) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $notification_id);
        $success = $stmt->execute();
    }

    if ($success) {
        // Get updated counts
        $stmt = $conn->prepare("
            SELECT 
                SUM(CASE WHEN nr.notification_id IS NULL THEN 1 ELSE 0 END) as total_unread,
                SUM(CASE WHEN nr.notification_id IS NULL AND n.type = 'event' THEN 1 ELSE 0 END) as event_unread,
                SUM(CASE WHEN nr.notification_id IS NULL AND n.type = 'job' THEN 1 ELSE 0 END) as job_unread
            FROM (
                SELECT CONCAT('event-', id) as id, 'event' as type FROM events WHERE created_at >= (SELECT created_at FROM users WHERE user_id = ?)
                UNION ALL
                SELECT CONCAT('job-', job_id), 'job' FROM job_listings WHERE created_at >= (SELECT created_at FROM users WHERE user_id = ?) AND (posted_by_id IS NULL OR posted_by_id != ?)
            ) n
            LEFT JOIN notifications_read nr ON nr.notification_id = n.id AND nr.user_id = ?
        ");
        $stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $counts = $result->fetch_assoc();

        exit(json_encode([
            'success' => true,
            'unreadCount' => (int)$counts['total_unread'],
            'unreadEventCount' => (int)$counts['event_unread'], 
            'unreadJobCount' => (int)$counts['job_unread']
        ]));
    }
}

exit(json_encode(['success' => false]));
