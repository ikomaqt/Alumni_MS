<?php
include 'sqlconnection.php';
include 'user_navbar.php';

// Handle count-only requests with SSE support
if (isset($_GET['count_only'])) {
    $user_id = $_SESSION['user_id'];
    
    if (isset($_GET['sse'])) {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        
        while (true) {
            // Update the countQuery to include job applications
            $countQuery = "SELECT 
                SUM(CASE WHEN nr.notification_id IS NULL THEN 1 ELSE 0 END) as total_unread,
                SUM(CASE WHEN nr.notification_id IS NULL AND n.type = 'event' THEN 1 ELSE 0 END) as event_unread,
                SUM(CASE WHEN nr.notification_id IS NULL AND n.type = 'job' THEN 1 ELSE 0 END) as job_unread,
                SUM(CASE WHEN nr.notification_id IS NULL AND n.type = 'application' THEN 1 ELSE 0 END) as application_unread
            FROM (
                SELECT CONCAT('event-', id) as id, 'event' as type FROM events
                UNION ALL
                SELECT CONCAT('job-', job_id), 'job' FROM job_listings WHERE (posted_by_id IS NULL OR posted_by_id != '$user_id')
                UNION ALL
                SELECT CONCAT('application-', ja.application_id), 'application' 
                FROM job_applications ja 
                INNER JOIN job_listings jl ON ja.job_id = jl.job_id 
                WHERE jl.posted_by_id = '$user_id'
            ) n
            LEFT JOIN notifications_read nr ON nr.notification_id = n.id AND nr.user_id = '$user_id'";
            
            $result = mysqli_query($conn, $countQuery);
            $counts = mysqli_fetch_assoc($result);
            
            echo "data: " . json_encode([
                'unreadCount' => (int)$counts['total_unread'],
                'unreadEventCount' => (int)$counts['event_unread'],
                'unreadJobCount' => (int)$counts['job_unread'],
                'unreadApplicationCount' => (int)$counts['application_unread']
            ]) . "\n\n";
            
            ob_flush();
            flush();
            sleep(3); // Check every 3 seconds
        }
    } else {
        // Query to get unread notifications counts
        $countQuery = "SELECT 
            SUM(CASE WHEN nr.notification_id IS NULL THEN 1 ELSE 0 END) as total_unread,
            SUM(CASE WHEN nr.notification_id IS NULL AND n.type = 'event' THEN 1 ELSE 0 END) as event_unread,
            SUM(CASE WHEN nr.notification_id IS NULL AND n.type = 'job' THEN 1 ELSE 0 END) as job_unread,
            SUM(CASE WHEN nr.notification_id IS NULL AND n.type = 'application' THEN 1 ELSE 0 END) as application_unread
        FROM (
            SELECT CONCAT('event-', id) as id, 'event' as type FROM events WHERE created_at >= (SELECT created_at FROM users WHERE user_id = '$user_id')
            UNION ALL
            SELECT CONCAT('job-', job_id), 'job' FROM job_listings WHERE created_at >= (SELECT created_at FROM users WHERE user_id = '$user_id') AND (posted_by_id IS NULL OR posted_by_id != '$user_id')
            UNION ALL
            SELECT CONCAT('application-', ja.application_id), 'application' 
            FROM job_applications ja 
            INNER JOIN job_listings jl ON ja.job_id = jl.job_id 
            WHERE jl.posted_by_id = '$user_id' AND ja.applied_at >= (SELECT created_at FROM users WHERE user_id = '$user_id')
        ) n
        LEFT JOIN notifications_read nr ON nr.notification_id = n.id AND nr.user_id = '$user_id'";
        
        $result = mysqli_query($conn, $countQuery);
        $counts = mysqli_fetch_assoc($result);
        
        exit(json_encode([
            'unreadCount' => (int)$counts['total_unread'],
            'unreadEventCount' => (int)$counts['event_unread'],
            'unreadJobCount' => (int)$counts['job_unread'],
            'unreadApplicationCount' => (int)$counts['application_unread']
        ]));
    }
}

// Handle mark as read POST request
if (isset($_POST['notification_id']) || isset($_POST['mark_all'])) {
    $user_id = $_SESSION['user_id'];

    if (isset($_POST['mark_all'])) {
        // Mark all as read
        // Get all notification IDs for this user (events + jobs + applications, using same filters)
        $notificationIds = [];
        // Events
        $eventQuery = "SELECT CONCAT('event-', id) as nid FROM events WHERE created_at >= (SELECT created_at FROM users WHERE user_id = '$user_id')";
        $eventResult = mysqli_query($conn, $eventQuery);
        while ($row = mysqli_fetch_assoc($eventResult)) {
            $notificationIds[] = $row['nid'];
        }
        // Jobs
        $jobQuery = "SELECT CONCAT('job-', job_id) as nid FROM job_listings WHERE created_at >= (SELECT created_at FROM users WHERE user_id = '$user_id') AND (posted_by_id IS NULL OR posted_by_id != '$user_id')";
        $jobResult = mysqli_query($conn, $jobQuery);
        while ($row = mysqli_fetch_assoc($jobResult)) {
            $notificationIds[] = $row['nid'];
        }
        // Applications
        $applicationQuery = "SELECT CONCAT('application-', ja.application_id) as nid 
                             FROM job_applications ja 
                             INNER JOIN job_listings jl ON ja.job_id = jl.job_id 
                             WHERE jl.posted_by_id = '$user_id' AND ja.applied_at >= (SELECT created_at FROM users WHERE user_id = '$user_id')";
        $applicationResult = mysqli_query($conn, $applicationQuery);
        while ($row = mysqli_fetch_assoc($applicationResult)) {
            $notificationIds[] = $row['nid'];
        }
        // Insert all as read
        foreach ($notificationIds as $nid) {
            $nidEscaped = mysqli_real_escape_string($conn, $nid);
            mysqli_query($conn, "INSERT IGNORE INTO notifications_read (user_id, notification_id) VALUES ('$user_id', '$nidEscaped')");
        }
    } elseif (isset($_POST['notification_id'])) {
        $notification_id = $_POST['notification_id'];
        $nidEscaped = mysqli_real_escape_string($conn, $notification_id);
        mysqli_query($conn, "INSERT IGNORE INTO notifications_read (user_id, notification_id) VALUES ('$user_id', '$nidEscaped')");
    }

    // Get updated counts (same as everywhere else)
    $countQuery = "SELECT 
        SUM(CASE WHEN nr.notification_id IS NULL THEN 1 ELSE 0 END) as total_unread,
        SUM(CASE WHEN nr.notification_id IS NULL AND n.type = 'event' THEN 1 ELSE 0 END) as event_unread,
        SUM(CASE WHEN nr.notification_id IS NULL AND n.type = 'job' THEN 1 ELSE 0 END) as job_unread,
        SUM(CASE WHEN nr.notification_id IS NULL AND n.type = 'application' THEN 1 ELSE 0 END) as application_unread
    FROM (
        SELECT CONCAT('event-', id) as id, 'event' as type FROM events WHERE created_at >= (SELECT created_at FROM users WHERE user_id = '$user_id')
        UNION ALL
        SELECT CONCAT('job-', job_id), 'job' FROM job_listings WHERE created_at >= (SELECT created_at FROM users WHERE user_id = '$user_id') AND (posted_by_id IS NULL OR posted_by_id != '$user_id')
        UNION ALL
        SELECT CONCAT('application-', ja.application_id), 'application' 
        FROM job_applications ja 
        INNER JOIN job_listings jl ON ja.job_id = jl.job_id 
        WHERE jl.posted_by_id = '$user_id' AND ja.applied_at >= (SELECT created_at FROM users WHERE user_id = '$user_id')
    ) n
    LEFT JOIN notifications_read nr ON nr.notification_id = n.id AND nr.user_id = '$user_id'";
    $countResult = mysqli_query($conn, $countQuery);
    $counts = mysqli_fetch_assoc($countResult);

    exit(json_encode([
        'success' => true,
        'unreadCount' => (int)$counts['total_unread'],
        'unreadEventCount' => (int)$counts['event_unread'],
        'unreadJobCount' => (int)$counts['job_unread'],
        'unreadApplicationCount' => (int)$counts['application_unread']
    ]));
}

// Get active tab from query parameter or default to 'all'
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'all';

// Determine if we need to show the "View All" button
$viewingAll = isset($_GET['view']) && $_GET['view'] == 'all';

// Get user's account creation date
$user_id = $_SESSION['user_id'];
$userQuery = "SELECT created_at FROM users WHERE user_id = '$user_id'";
$userResult = mysqli_query($conn, $userQuery);
$userData = mysqli_fetch_assoc($userResult);
$userCreatedAt = $userData['created_at'];

// Fetch jobs based on user's account creation date, EXCLUDE jobs posted by user
$jobs = [];
$jobQuery = "SELECT job_id, title, company, location, employment_type, job_type, 
             salary_range, description, requirements, contact_email, created_at, status 
             FROM job_listings " .
             (!$viewingAll ? "WHERE created_at >= '$userCreatedAt' AND (posted_by_id IS NULL OR posted_by_id != '$user_id')" : "WHERE (posted_by_id IS NULL OR posted_by_id != '$user_id')") .
             " ORDER BY created_at DESC";
if ($result = mysqli_query($conn, $jobQuery)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $notification_id = "job-" . $row['job_id'];
        // Check if notification is read from database
        $readQuery = "SELECT 1 FROM notifications_read 
                     WHERE user_id = '$user_id' 
                     AND notification_id = '$notification_id' 
                     LIMIT 1";
        $readResult = mysqli_query($conn, $readQuery);
        $isRead = mysqli_num_rows($readResult) > 0;

        $jobs[] = [
            "id" => $notification_id,
            "type" => "job",
            "title" => $row['title'],
            "description" => $row['description'],
            "company" => $row['company'],
            "location" => $row['location'],
            "employmentType" => $row['employment_type'],
            "jobType" => $row['job_type'],
            "salaryRange" => $row['salary_range'],
            "requirements" => $row['requirements'],
            "contactEmail" => $row['contact_email'],
            "createdAt" => $row['created_at'],
            "status" => $row['status'],
            "read" => $isRead
        ];
    }
    mysqli_free_result($result);
}

// Fetch job applications for jobs posted by the user
$applicationQuery = "SELECT 
    ja.application_id,
    ja.job_id,
    ja.name,
    ja.applied_at,
    ja.status,
    jl.title as job_title
    FROM job_applications ja
    INNER JOIN job_listings jl ON ja.job_id = jl.job_id
    WHERE jl.posted_by_id = '$user_id'" .
    (!$viewingAll ? " AND ja.applied_at >= '$userCreatedAt'" : "") .
    " ORDER BY ja.applied_at DESC";

if ($result = mysqli_query($conn, $applicationQuery)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $notification_id = "job-" . $row['application_id']; // Use job- prefix for applications
        // Check if notification is read
        $readQuery = "SELECT 1 FROM notifications_read 
                     WHERE user_id = '$user_id' 
                     AND notification_id = '$notification_id' 
                     LIMIT 1";
        $readResult = mysqli_query($conn, $readQuery);
        $isRead = mysqli_num_rows($readResult) > 0;

        $jobs[] = [
            "id" => $notification_id,
            "type" => "job", // Mark as job
            "title" => "New application for " . $row['job_title'],
            "description" => $row['name'] . " has applied for the position",
            "company" => "", // Not relevant for application
            "location" => "",
            "employmentType" => "",
            "jobType" => "",
            "salaryRange" => "",
            "requirements" => "",
            "contactEmail" => "",
            "createdAt" => $row['applied_at'],
            "status" => $row['status'],
            "read" => $isRead,
            "applicantName" => $row['name'],
            "jobId" => $row['job_id'],
            "jobTitle" => $row['job_title'],
            "isApplication" => true // Custom flag for rendering
        ];
    }
    mysqli_free_result($result);
}

// Fetch events based on user's account creation date
$events = [];
$eventQuery = "SELECT id, event_title, event_date, event_start_time, event_end_time,
               event_description, event_place, created_at, event_image 
               FROM events " .
               (!$viewingAll ? "WHERE created_at >= '$userCreatedAt'" : "") .
               " ORDER BY created_at DESC";
if ($result = mysqli_query($conn, $eventQuery)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $notification_id = "event-" . $row['id'];
        // Check if notification is read from database
        $readQuery = "SELECT 1 FROM notifications_read 
                     WHERE user_id = '$user_id' 
                     AND notification_id = '$notification_id' 
                     LIMIT 1";
        $readResult = mysqli_query($conn, $readQuery);
        $isRead = mysqli_num_rows($readResult) > 0;

        $events[] = [
            "id" => $notification_id,
            "type" => "event",
            "title" => $row['event_title'],
            "description" => $row['event_description'],
            "eventDate" => $row['event_date'],
            "eventStartTime" => $row['event_start_time'],
            "eventEndTime" => $row['event_end_time'],
            "eventPlace" => $row['event_place'],
            "eventImage" => $row['event_image'],
            "createdAt" => $row['created_at'],
            "read" => $isRead
        ];
    }
    mysqli_free_result($result);
}

// Merge and sort notifications
$notifications = array_merge($jobs, $events);
usort($notifications, function($a, $b) {
    return strtotime($b['createdAt']) - strtotime($a['createdAt']);
});

// Filter notifications based on active tab
$filteredNotifications = $notifications;
if ($activeTab == 'events') {
    $filteredNotifications = array_filter($notifications, function($notification) {
        return $notification['type'] == 'event';
    });
} elseif ($activeTab == 'jobs') {
    $filteredNotifications = array_filter($notifications, function($notification) {
        return $notification['type'] == 'job';
    });
}

// Count total notifications
$totalNotifications = count($filteredNotifications);

// Determine if we need to show the "View All" button
$showViewAll = $totalNotifications > 10;

// Limit to 10 notifications if not viewing all
// By default, only 10 notifications are shown. If "View All" is clicked, show all.
if (!$viewingAll && $showViewAll) {
    $filteredNotifications = array_slice($filteredNotifications, 0, 10);
}

// Count notifications by type
$unreadCount = count(array_filter($notifications, function($notification) {
    return !$notification['read'];
}));
$unreadEventCount = count(array_filter($notifications, function($notification) {
    return !$notification['read'] && $notification['type'] == 'event';
}));
$unreadJobCount = count(array_filter($notifications, function($notification) {
    return !$notification['read'] && $notification['type'] == 'job';
}));

// Helper function to format time ago
function formatTimeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return "Just now";
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ($mins == 1 ? " minute ago" : " minutes ago");
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ($hours == 1 ? " hour ago" : " hours ago");
    } elseif ($diff < 604800) { // 7 days
        $days = floor($diff / 86400);
        return $days . ($days == 1 ? " day ago" : " days ago");
    } else {
        return date("M j, Y", $time);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | JobFinder</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-light: #dbeafe;
            --primary-dark: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --unread-indicator: #3b82f6;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: var(--gray-800);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        .app-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .header-actions {
            display: flex;
            gap: 0.75rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .btn-outline {
            background-color: white;
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }
        .btn-outline:hover {
            background-color: var(--gray-50);
            border-color: var(--gray-400);
        }
        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: 1px solid transparent;
        }
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        .btn-icon {
            margin-right: 0.5rem;
            width: 1rem;
            height: 1rem;
        }
        .tab-navigation {
            display: flex;
            background-color: white;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        .tab {
            flex: 1;
            padding: 0.75rem 1rem;
            text-align: center;
            font-weight: 500;
            color: var(--gray-600);
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            border-bottom: 2px solid transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background-color: var(--primary-light);
            background-opacity: 0.3;
        }
        .tab:hover:not(.active) {
            background-color: var(--gray-50);
            color: var(--gray-800);
        }
        .notification-counter {
            background: var(--primary);
            color: white;
            padding: 0.125rem 0.375rem;
            border-radius: 999px;
            font-size: 0.75rem;
            min-width: 1.25rem;
            height: 1.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.15s ease, opacity 0.15s ease;
        }
        .tab:hover .notification-counter {
            background: var(--primary-dark);
        }
        .notifications-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .notification-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            position: relative;
        }
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        /* Unread notification styling */
        .notification-card.unread {
            border-left: 4px solid var(--unread-indicator);
            background-color: #f0f7ff;
        }
        .notification-card.unread .notification-title {
            font-weight: 600;
        }
        .notification-content {
            padding: 1.25rem;
            position: relative;
        }
        .notification-type-badge {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-event {
            background-color: var(--primary-light);
            color: var(--primary-dark);
        }
        .badge-job {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        .badge-application {
            background-color: rgba(139, 92, 246, 0.1);
            color: #7c3aed;
        }
        .notification-header {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .notification-icon {
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            flex-shrink: 0;
        }
        .icon-event {
            background-color: var(--primary-light);
            color: var(--primary);
        }
        .icon-job {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        .icon-application {
            background-color: rgba(139, 92, 246, 0.1);
            color: #7c3aed;
        }
        .notification-title-area {
            flex: 1;
        }
        .notification-title {
            font-size: 1rem;
            font-weight: 500;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }
        .notification-time {
            font-size: 0.75rem;
            color: var(--gray-500);
        }
        .notification-body {
            margin-left: 3.25rem;
        }
        .notification-description {
            font-size: 0.875rem;
            color: var(--gray-700);
            margin-bottom: 0.75rem;
        }
        .notification-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: var(--gray-600);
            margin-bottom: 1rem;
        }
        .meta-icon {
            width: 0.875rem;
            height: 0.875rem;
        }
        .notification-actions {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid var(--gray-200);
            margin-top: 0.5rem;
            padding-top: 0.75rem;
        }
        .action-button {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--primary);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.25rem 0;
            transition: color 0.15s ease;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .action-button:hover {
            color: var(--primary-dark);
        }
        .view-all-container {
            display: flex;
            justify-content: center;
            margin-top: 1rem;
            width: 100%;
        }
        .view-all-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 1rem;
            background-color: white;
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            color: var(--gray-700);
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.15s ease;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .view-all-btn:hover {
            background-color: var(--gray-50);
            border-color: var(--gray-300);
        }
        .view-all-btn.btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .empty-icon {
            width: 3rem;
            height: 3rem;
            margin: 0 auto 1rem;
            color: var(--gray-400);
        }
        .empty-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }
        .empty-description {
            font-size: 0.875rem;
            color: var(--gray-500);
            max-width: 24rem;
            margin: 0 auto;
        }
        @media (max-width: 640px) {
            .app-container {
                padding: 1rem;
            }
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            .header-actions {
                width: 100%;
            }
            
            .btn {
                flex: 1;
            }
            .notification-type-badge {
                position: static;
                margin-bottom: 0.5rem;
                display: inline-block;
            }
            .notification-header {
                flex-direction: column;
            }
            .notification-body {
                margin-left: 0;
            }
        }
        /* Add these new styles */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }
        .btn-loading .spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        /* Update loading indicator styles */
        .loading-indicator {
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            margin-top: 0; /* Remove margin since it's in the same container as button */
            width: 100%;
            background-color: white;
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .loading-spinner {
            width: 1.5rem;
            height: 1.5rem;
            border: 2px solid var(--gray-200);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s linear infinite;
            margin-right: 0.75rem;
        }
        /* Modal styles */
        .modal-dialog {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-dialog.show {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateY(-20px);
            opacity: 0;
            transition: all 0.3s;
        }

        .modal-dialog.show .modal-content {
            transform: translateY(0);
            opacity: 1;
        }

        .modal-header {
            margin-bottom: 1rem;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
        }

        .modal-body {
            color: var(--gray-700);
            margin-bottom: 1.5rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
        }
    </style>
</head>
<body>
    <main class="app-container">
        <header class="page-header">
            <h1 class="page-title">
                Notifications
            </h1>
            <div class="header-actions">
                <button class="btn btn-outline" id="markAllRead">
                    <svg class="btn-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    Mark All Read
                </button>
            </div>
        </header>
        <nav class="tab-navigation">
            <a href="?tab=all<?php echo $viewingAll ? '&view=all' : ''; ?>" 
               class="tab <?php echo $activeTab == 'all' ? 'active' : ''; ?>">
                All
                <?php if ($unreadCount > 0): ?>
                    <span class="notification-counter"><?php echo $unreadCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="?tab=events<?php echo $viewingAll ? '&view=all' : ''; ?>" 
               class="tab <?php echo $activeTab == 'events' ? 'active' : ''; ?>">
                Events
                <?php if ($unreadEventCount > 0): ?>
                    <span class="notification-counter"><?php echo $unreadEventCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="?tab=jobs<?php echo $viewingAll ? '&view=all' : ''; ?>" 
               class="tab <?php echo $activeTab == 'jobs' ? 'active' : ''; ?>">
                Jobs
                <?php if ($unreadJobCount > 0): ?>
                    <span class="notification-counter"><?php echo $unreadJobCount; ?></span>
                <?php endif; ?>
            </a>
        </nav>
        <section class="notifications-container">
            <?php if (empty($filteredNotifications)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                    </div>
                    <h2 class="empty-title">No notifications yet</h2>
                    <p class="empty-description">When you receive notifications about new events or job opportunities, they will appear here.</p>
                </div>
            <?php else: ?>
                <?php foreach ($filteredNotifications as $notification): ?>
                    <article class="notification-card <?php echo $notification['read'] ? 'read' : 'unread'; ?>" 
                             data-id="<?php echo htmlspecialchars($notification['id']); ?>"
                             <?php if ($notification['type'] === 'job'): ?>
                             data-status="<?php echo htmlspecialchars($notification['status']); ?>"
                             <?php endif; ?>>
                        <div class="notification-content">
                            <span class="notification-type-badge badge-<?php echo $notification['type']; ?>">
                                <?php echo $notification['type'] === 'event' ? 'Event' : 'Job'; ?>
                            </span>
                            <div class="notification-header">
                                <div class="notification-icon icon-<?php echo $notification['type']; ?>">
                                    <?php if ($notification['type'] === 'event'): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                    <?php else: ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="notification-title-area">
                                    <h2 class="notification-title">
                                        <?php if ($notification['type'] === 'event'): ?>
                                            New Event: <?php echo htmlspecialchars($notification['title']); ?>
                                        <?php else: ?>
                                            <?php if (!empty($notification['isApplication'])): ?>
                                                New application for <?php echo htmlspecialchars($notification['jobTitle']); ?>
                                            <?php else: ?>
                                                New Job: <?php echo htmlspecialchars($notification['title']); ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </h2>
                                    <div class="notification-time">
                                        <?php echo formatTimeAgo($notification['createdAt']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="notification-body">
                                <p class="notification-description">
                                    <?php if ($notification['type'] === 'event'): ?>
                                        A new event has been scheduled for <?php echo date('M j, Y', strtotime($notification['eventDate'])); ?>
                                    <?php elseif (!empty($notification['isApplication'])): ?>
                                        <?php echo htmlspecialchars($notification['applicantName']); ?> has applied for the position
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($notification['company']); ?> • <?php echo htmlspecialchars($notification['location']); ?>
                                    <?php endif; ?>
                                </p>
                                <?php if ($notification['type'] === 'event'): ?>
                                    <div class="notification-meta">
                                        <svg class="meta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                        <span><?php echo date('M j, Y', strtotime($notification['eventDate'])); ?> at <?php echo date('g:i A', strtotime($notification['eventStartTime'])); ?></span>
                                    </div>
                                <?php elseif (!empty($notification['isApplication'])): ?>
                                    <div class="notification-meta">
                                        <span>Status: <?php echo ucfirst(htmlspecialchars($notification['status'])); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="notification-actions">
                                    <button class="action-button view-details">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        View Details
                                    </button>
                                    <?php if (!$notification['read']): ?>
                                    <button class="action-button mark-read">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        Mark as read
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if ($showViewAll && !$viewingAll): ?>
                <div class="view-all-container">
                    <button class="view-all-btn" id="viewAllBtn" data-tab="<?php echo $activeTab; ?>">
                        <span class="btn-content">View All Notifications</span>
                    </button>
                    <div class="loading-indicator" id="loadingIndicator">
                        <div class="loading-spinner"></div>
                        <span class="loading-text">Loading more notifications...</span>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
    <div class="modal-dialog" id="jobClosedModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Job No Longer Available</h3>
            </div>
            <div class="modal-body" id="jobClosedMessage"></div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="closeModal()">Okay, I understand</button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add event delegation to the notifications container
            const notificationsContainer = document.querySelector('.notifications-container');
            
            notificationsContainer.addEventListener('click', async function(e) {
                // Handle mark as read button clicks
                if (e.target.closest('.mark-read')) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const button = e.target.closest('.mark-read');
                    const notificationCard = button.closest('.notification-card');
                    const notificationId = notificationCard.dataset.id;
                    
                    await markAsRead(notificationCard, notificationId);
                    return;
                }
                
                // Handle view details button clicks
                if (e.target.closest('.view-details')) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const notificationCard = e.target.closest('.notification-card');
                    await handleViewDetails(notificationCard);
                    return;
                }
                
                // Handle card clicks (if not clicking a button)
                const notificationCard = e.target.closest('.notification-card');
                if (notificationCard && !e.target.closest('.action-button')) {
                    await handleViewDetails(notificationCard);
                }
            });

            // Update markAsRead function
            async function markAsRead(notificationCard, notificationId) {
                try {
                    const formData = new FormData();
                    formData.append('notification_id', notificationId);
                    
                    const response = await fetch('set_notification_read.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        notificationCard.classList.remove('unread');
                        notificationCard.classList.add('read');
                        const markReadBtn = notificationCard.querySelector('.mark-read');
                        if (markReadBtn) markReadBtn.remove();
                        
                        // Update counters and badges
                        updateTabCounter('all', data.unreadCount);
                        updateTabCounter('events', data.unreadEventCount);
                        updateTabCounter('jobs', data.unreadJobCount);
                        
                        if (window.updateNavbarBadges) {
                            window.updateNavbarBadges({
                                count: data.unreadCount,
                                eventCount: data.unreadEventCount,
                                jobCount: data.unreadJobCount
                            });
                        }
                    }
                } catch (error) {
                    console.error('Error marking as read:', error);
                }
            }

            // Separate view details functionality
            async function handleViewDetails(notificationCard) {
                const notificationId = notificationCard.dataset.id;
                const [type, id] = notificationId.split('-');
                
                // Mark as read before redirecting if unread
                if (notificationCard.classList.contains('unread')) {
                    await markAsRead(notificationCard, notificationId);
                }

                // Check if this is an application notification (job type, isApplication flag)
                if (type === 'job' && notificationCard && notificationCard.hasAttribute('data-status')) {
                    // Check for isApplication flag in dataset (set by PHP)
                    if (notificationCard.innerHTML.includes('New application for')) {
                        // Redirect to user_posted_jobs.php and open applicant popup
                        window.location.href = 'user_posted_jobs.php?view_applicant=' + id;
                        return;
                    }
                }
                
                // Redirect based on type with selected parameter
                if (type === 'event') {
                    window.location.href = 'view_event.php?event_id=' + id;
                } else if (type === 'job') {
                    // Check if the job is closed
                    const jobStatus = notificationCard.getAttribute('data-status');
                    if (jobStatus === 'closed') {
                        // Show closed job modal
                        const messageElement = document.getElementById('jobClosedMessage');
                        messageElement.textContent = `The position at this job is now closed.`;
                        document.getElementById('jobClosedModal').classList.add('show');
                        return;
                    }
                    
                    // Redirect to job_info with selected=true parameter
                    window.location.href = `job_info.php?id=${id}&selected=true`;
                }
            }

            // Update markAllAsRead function to use the new endpoint
            async function markAllAsRead() {
                try {
                    const response = await fetch('set_notification_read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'mark_all=1'
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Update all unread cards to read
                        document.querySelectorAll('.notification-card.unread').forEach(card => {
                            card.classList.remove('unread');
                            card.classList.add('read');
                            const markReadBtn = card.querySelector('.mark-read');
                            if (markReadBtn) markReadBtn.remove();
                        });
                        
                        // Update counters and badges
                        updateTabCounter('all', data.unreadCount);
                        updateTabCounter('events', data.unreadEventCount);
                        updateTabCounter('jobs', data.unreadJobCount);
                        
                        if (window.updateNavbarBadges) {
                            window.updateNavbarBadges({
                                count: data.unreadCount,
                                eventCount: data.unreadEventCount,
                                jobCount: data.unreadJobCount
                            });
                        }
                        
                        // Trigger navbar update
                        if (window.updateNavbarNotificationCount) {
                            window.updateNavbarNotificationCount();
                        }
                    }
                } catch (error) {
                    console.error('Error marking all as read:', error);
                }
            }

            // Add click handler for mark all read button
            document.getElementById('markAllRead').addEventListener('click', markAllAsRead);

            // Update tab counter function
            function updateTabCounter(tab, count) {
                const tabElement = document.querySelector(`.tab[href*="tab=${tab}"]`);
                if (!tabElement) return;

                let counter = tabElement.querySelector('.notification-counter');

                if (count > 0) {
                    if (!counter) {
                        counter = document.createElement('span');
                        counter.className = 'notification-counter';
                        tabElement.appendChild(counter);
                    }
                    
                    counter.style.transform = 'scale(0.8)';
                    counter.textContent = count;
                    requestAnimationFrame(() => {
                        counter.style.transform = 'scale(1)';
                    });
                } else if (counter) {
                    counter.style.transform = 'scale(0.8)';
                    counter.style.opacity = '0';
                    setTimeout(() => counter.remove(), 150);
                }
            }

            // View all functionality
            const viewAllBtn = document.getElementById('viewAllBtn');
            const loadingIndicator = document.getElementById('loadingIndicator');
            
            if (viewAllBtn) {
                viewAllBtn.addEventListener('click', async function(e) {
                    e.preventDefault();
                                       
                    // Hide button and show loading indicator
                    viewAllBtn.style.display = 'none';
                    loadingIndicator.style.display = 'flex';
                    
                    try {
                        const tab = this.dataset.tab;
                        const url = `notifications.php?tab=${tab}&view=all`;
                        const response = await fetch(url);
                         
                        if (!response.ok) throw new Error('Network response was not ok');
                         
                        const html = await response.text();
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newNotifications = doc.querySelector('.notifications-container').innerHTML;
                        document.querySelector('.notifications-container').innerHTML = newNotifications;

                        history.pushState({}, '', url);

                    } catch (error) {
                        console.error('Error loading notifications:', error);
                        loadingIndicator.innerHTML = `
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <span style="margin-left: 0.5rem; color: var(--danger)">Failed to load notifications</span>
                        `;
                    }
                });
            }
        });

        // Add these functions outside DOMContentLoaded
        function closeModal() {
            document.getElementById('jobClosedModal').classList.remove('show');
        }

        // Global click handler for modal
        document.getElementById('jobClosedModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>