<?php
include 'sqlconnection.php';
include 'user_navbar.php';

// Fetch all events from database
$upcomingEvents = [];
$pastEvents = [];

$currentDate = date('Y-m-d');
$eventsSql = "SELECT * FROM events ORDER BY event_date DESC";
$result = $conn->query($eventsSql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['event_date'] >= $currentDate) {
            $upcomingEvents[] = $row;
        } else {
            $pastEvents[] = $row;
        }
    }
}

// Check which events are saved by the user
$savedEvents = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $savedEventsSql = "SELECT event_id FROM saved_events WHERE user_id = ?";
    $stmt = $conn->prepare($savedEventsSql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $savedEvents[] = $row['event_id'];
    }
}

// Handle event saving/unsaving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['event_id'])) {
    $event_id = intval($_POST['event_id']);
    $user_id = $_SESSION['user_id'] ?? 0;
    
    if ($user_id) {
        if ($_POST['action'] === 'save') {
            $sql = "INSERT INTO saved_events (user_id, event_id, saved_at) VALUES (?, ?, NOW())";
        } else {
            $sql = "DELETE FROM saved_events WHERE user_id = ? AND event_id = ?";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $event_id);
        $stmt->execute();
        
        // Return success response
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Calendar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        h1 {
            margin-bottom: 2rem;
            color: #2d3748;
            font-size: 1.75rem;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
          
      display: flex;
      flex-direction: column;
      gap: 1rem;
      margin-bottom: 0.5rem;
    
        }

        /* Tabs */
        .tabs {
            margin-bottom: 2rem;
        }

        .tabs-list {
            display: flex;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 2rem;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .tab-trigger {
            padding: 0.75rem 1.5rem;
            background: none;
            border: none;
            font-size: 0.9rem;
            font-weight: 600;
            color: #718096;
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
            text-align: center;
            font-family: 'Montserrat', sans-serif;
        }

        .tab-trigger:hover {
            color: #4a5568;
            background-color: #f7fafc;
        }

        .tab-trigger.active {
            color: #3182ce;
            background-color: #ebf8ff;
            border-bottom: 2px solid #3182ce;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Event grid */
        .event-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 1.25rem;
        }

        @media (min-width: 640px) {
            .event-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .event-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 1280px) {
            .event-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Event card */
        .event-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            height: 420px; /* Set a fixed height for card */
            max-width: 320px;
            margin: 0 auto;
            border: 1px solid #edf2f7;
        }

        .event-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
        }

        .event-image-wrapper {
            width: 100%;
            aspect-ratio: 16/10; /* Maintain a consistent aspect ratio */
            background: #f3f3f3;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .event-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: transparent;
            display: block;
        }

        .event-header {
            padding: 1rem 1rem 0.5rem;
            position: relative;
        }

        .event-title {
            font-size: 1rem;
            font-weight: 600;
            margin-right: 2rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            color: #2d3748;
            font-family: 'Montserrat', sans-serif;
        }

        .heart-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 32px;
            height: 32px;
            background-color: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .heart-btn:hover {
            background-color: #f7fafc;
        }

        /* Heart icon styles - update this CSS block */
        .heart-btn i {
            font-size: 0.9rem;
            color: #9ca3af;
            transition: color 0.2s;
        }

        .heart-btn i.active {
            color: #ef4444;
        }

        .event-content {
            padding: 0 1rem 1rem;
            flex-grow: 1;
        }

        .event-info {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            font-size: 0.85rem;
        }

        .event-info i {
            color: #718096;
            width: 14px;
            margin-top: 3px;
            font-size: 0.8rem;
        }

        .event-description {
            color: #718096;
            margin-top: 0.75rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-size: 0.85rem;
        }

        .event-footer {
            padding: 0.75rem 1rem;
            border-top: 1px solid #edf2f7;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8fafc;
        }

        .created-date {
            font-size: 0.7rem;
            color: #a0aec0;
        }

        .view-details-btn {
            background-color: #3182ce;
            color: white;
            border: none;
            padding: 0.4rem 0.75rem;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.75rem;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
        }

        .view-details-btn:hover {
            background-color: #2c5282;
        }

        .empty-message {
            text-align: center;
            padding: 3rem 0;
            color: #718096;
            font-size: 1rem;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            background-color: white;
            margin: 2rem auto;
            padding: 1.5rem;
            border-radius: 8px;
            max-width: 550px;
            position: relative;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.25rem;
            background: none;
            border: none;
            cursor: pointer;
            color: #718096;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .close-modal:hover {
            background-color: #f7fafc;
        }

        .modal-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #2d3748;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
        }

        .modal-info {
            margin-bottom: 0.75rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .modal-info i {
            color: #718096;
            width: 16px;
            margin-top: 4px;
        }

        .modal-description {
            margin: 1.5rem 0;
            line-height: 1.7;
            color: #4a5568;
            font-size: 0.95rem;
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 12px 24px;
            border-radius: 4px;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }

        .toast.show {
            opacity: 1;
            visibility: visible;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Events Calendar</h1>
        
        <div class="tabs">
            <div class="tabs-list">
                <button class="tab-trigger active" data-tab="upcoming">Upcoming Events</button>
                <button class="tab-trigger" data-tab="past">Past Events</button>
            </div>
            
            <div id="upcoming" class="tab-content active">
                <div class="event-grid">
                    <?php if (empty($upcomingEvents)): ?>
                        <p class="empty-message">No upcoming events available.</p>
                    <?php else: ?>
                        <?php foreach ($upcomingEvents as $index => $event): ?>
                            <div class="event-card">
                                <div class="event-image-wrapper">
                                    <img src="<?php echo htmlspecialchars($event['event_image'] ?: 'https://placehold.co/500x300'); ?>" 
                                         alt="<?php echo htmlspecialchars($event['event_title']); ?>" 
                                         class="event-image">
                                </div>
                                <div class="event-header">
                                    <h3 class="event-title"><?php echo htmlspecialchars($event['event_title']); ?></h3>
                                    <button class="heart-btn" data-id="<?php echo $event['id']; ?>" onclick="toggleEvent(this)">
                                        <i class="<?php echo in_array($event['id'], $savedEvents) ? 'fas' : 'far'; ?> fa-heart <?php echo in_array($event['id'], $savedEvents) ? 'active' : ''; ?>"></i>
                                    </button>
                                </div>
                                <div class="event-content">
                                    <div class="event-info">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span><?php echo date('l, F j, Y', strtotime($event['event_date'])); ?></span>
                                    </div>
                                    <div class="event-info">
                                        <i class="fas fa-clock"></i>
                                        <span><?php 
                                            $start = date('g:i A', strtotime($event['event_start_time']));
                                            $end = date('g:i A', strtotime($event['event_end_time']));
                                            echo "$start - $end";
                                        ?></span>
                                    </div>
                                    <div class="event-info">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($event['event_place']); ?></span>
                                    </div>
                                    <p class="event-description"><?php echo htmlspecialchars($event['event_description']); ?></p>
                                </div>
                                <div class="event-footer">
                                    <span class="created-date">Added on <?php echo date('M j, Y', strtotime($event['created_at'])); ?></span>
                                    <a href="view_event.php?event_id=<?php echo $event['id']; ?>" class="view-details-btn">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div id="past" class="tab-content">
                <div class="event-grid">
                    <?php if (empty($pastEvents)): ?>
                        <p class="empty-message">No past events available.</p>
                    <?php else: ?>
                        <?php foreach ($pastEvents as $index => $event): ?>
                            <div class="event-card">
                                <div class="event-image-wrapper">
                                    <img src="<?php echo htmlspecialchars($event['event_image'] ?: 'https://placehold.co/500x300'); ?>" 
                                         alt="<?php echo htmlspecialchars($event['event_title']); ?>" 
                                         class="event-image">
                                </div>
                                <div class="event-header">
                                    <h3 class="event-title"><?php echo htmlspecialchars($event['event_title']); ?></h3>
                                    <button class="heart-btn" data-id="<?php echo $event['id']; ?>" onclick="toggleEvent(this)">
                                        <i class="<?php echo in_array($event['id'], $savedEvents) ? 'fas' : 'far'; ?> fa-heart <?php echo in_array($event['id'], $savedEvents) ? 'active' : ''; ?>"></i>
                                    </button>
                                </div>
                                <div class="event-content">
                                    <div class="event-info">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span><?php echo date('l, F j, Y', strtotime($event['event_date'])); ?></span>
                                    </div>
                                    <div class="event-info">
                                        <i class="fas fa-clock"></i>
                                        <span><?php 
                                            $start = date('g:i A', strtotime($event['event_start_time']));
                                            $end = date('g:i A', strtotime($event['event_end_time']));
                                            echo "$start - $end";
                                        ?></span>
                                    </div>
                                    <div class="event-info">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($event['event_place']); ?></span>
                                    </div>
                                    <p class="event-description"><?php echo htmlspecialchars($event['event_description']); ?></p>
                                </div>
                                <div class="event-footer">
                                    <span class="created-date">Added on <?php echo date('M j, Y', strtotime($event['created_at'])); ?></span>
                                    <a href="view_event.php?event_id=<?php echo $event['id']; ?>" class="view-details-btn">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Remove the old showEventDetails function and modal code since we're using direct links now
        // Keep the rest of the JavaScript for tabs and heart button functionality

        // Remove the static events array and replace with PHP data
        const events = <?php echo json_encode(array_merge($upcomingEvents, $pastEvents)); ?>;

        // Tab switching functionality
        const tabTriggers = document.querySelectorAll('.tab-trigger');
        const tabContents = document.querySelectorAll('.tab-content');

        tabTriggers.forEach(trigger => {
            trigger.addEventListener('click', () => {
                // Remove active class from all triggers and contents
                tabTriggers.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked trigger and corresponding content
                trigger.classList.add('active');
                const tabId = trigger.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });

        // Heart button functionality
        function toggleHeart(button) {
            const icon = button.querySelector('i');
            if (icon.classList.contains('far')) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                button.classList.add('active');
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                button.classList.remove('active');
            }
        }

        // Toggle saved event
        async function toggleEvent(button) {
            const eventId = button.dataset.id;
            const icon = button.querySelector('i');
            const isSaved = icon.classList.contains('active');
            const action = isSaved ? 'unsave' : 'save';

            try {
                const response = await fetch('user_view_event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: action,
                        event_id: eventId,
                    }),
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                // Toggle heart icon classes
                if (isSaved) {
                    icon.classList.remove('fas', 'active');
                    icon.classList.add('far');
                    showToast('Event removed from saved events');
                } else {
                    icon.classList.remove('far');
                    icon.classList.add('fas', 'active');
                    showToast('Event saved successfully');
                }
                
            } catch (error) {
                console.error('Error saving event:', error);
                showToast('Error saving event. Please try again.');
            }
        }

        // Show toast message
        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.textContent = message;
            document.body.appendChild(toast);

            // Show the toast
            setTimeout(() => toast.classList.add('show'), 100);

            // Remove the toast after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
