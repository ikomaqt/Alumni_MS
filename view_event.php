<?php

include 'sqlconnection.php';
include 'user_navbar.php';

// Fetch event details
$event_id = $_GET['event_id'] ?? 0;
$event = null;

if ($event_id) {
    $sql = "SELECT * FROM events WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $event = $result->fetch_assoc();
    }
}

// Check if event is saved by current user
$isSaved = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $savedEventsSql = "SELECT event_id FROM saved_events WHERE user_id = ? AND event_id = ?";
    $stmt = $conn->prepare($savedEventsSql);
    $stmt->bind_param("ii", $user_id, $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $isSaved = $result->num_rows > 0;
}

// Handle heart button actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['event_id'])) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please login first']);
        exit;
    }
    
    $event_id = intval($_POST['event_id']);
    $user_id = $_SESSION['user_id'];
    
    if ($_POST['action'] === 'save') {
        $sql = "INSERT INTO saved_events (user_id, event_id, saved_at) VALUES (?, ?, NOW())";
    } else {
        $sql = "DELETE FROM saved_events WHERE user_id = ? AND event_id = ?";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $event_id);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    exit;
}

if (!$event) {
    header('Location: user_view_event.php');
    exit;
}

// Check if event has ended
$currentDateTime = new DateTime();
$eventDateTime = new DateTime($event['event_date'] . ' ' . $event['event_end_time']);
$isEventEnded = $currentDateTime > $eventDateTime;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --text-color: #2c3e50;
            --text-light: #7f8c8d;
            --bg-color: #f5f7fa;
            --card-bg: #ffffff;
            --border-radius: 12px;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Poppins', sans-serif;
            padding: 100px 15px 30px; /* Increased top padding to account for navbar */
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }
        
        .container {
            display: flex;
            flex-wrap: wrap;
            max-width: 1000px;
            gap: 20px;
            margin-bottom: 20px;
            justify-content: center;
        }
        
        .card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .square-card {
            width: 450px;
            height: 450px;
        }
        
        .image-card {
            position: relative;
            padding: 0;
        }
        
        .image-overlay {
            display: flex;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .image-container {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }
        
        .image-card:hover .image-container img {
            transform: scale(1.05);
        }
        
        .details-card {
            padding: 0;
            display: flex;
            flex-direction: column;
        }
        
        .details-header {
            padding: 25px 30px;
            border-bottom: 1px solid #eee;
        }
        
        .details-header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .event-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            line-height: 1.2;
            color: var(--primary-color);
        }
        
        .heart-icon {
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

        .heart-icon:hover {
            background-color: #f7fafc;
        }

        .heart-icon i {
            font-size: 0.9rem;
            color: #9ca3af;
            transition: color 0.2s;
        }

        .heart-icon i.active {
            color: #ef4444;
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
        
        .date-badge {
            display: inline-flex;
            align-items: center;
            background-color: rgba(52, 152, 219, 0.1);
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 14px;
            color: var(--secondary-color);
        }
        
        .date-badge i {
            margin-right: 8px;
        }
        
        .details-body {
            padding: 30px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .details-section {
            margin-bottom: 25px;
        }
        
        .details-section:last-child {
            margin-bottom: 0;
        }
        
        .section-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-light);
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .time-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .time-block {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .time-icon {
            width: 40px;
            height: 40px;
            background-color: rgba(52, 152, 219, 0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary-color);
        }
        
        .time-details {
            flex-grow: 1;
        }
        
        .time-label {
            font-family: 'Montserrat', sans-serif;
            font-size: 12px;
            color: var(--text-light);
            margin-bottom: 2px;
        }
        
        .time-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .location-block {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background-color: #fff8ee;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #f39c12;
        }
        
        .location-icon {
            width: 40px;
            height: 40px;
            background-color: rgba(243, 156, 18, 0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f39c12;
        }
        
        .location-details {
            flex-grow: 1;
        }
        
        .location-label {
            font-family: 'Montserrat', sans-serif;
            font-size: 12px;
            color: var(--text-light);
            margin-bottom: 2px;
        }
        
        .location-value {
            font-size: 16px;
            font-weight: 600;
            color: var (--text-color);
        }
        
        .created-date {
            margin-top: auto;
            font-size: 13px;
            color: var(--text-light);
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .about-card {
            width: 920px; /* Width of two square cards plus gap */
            padding: 30px;
        }
        
        .about-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
            position: relative;
            padding-bottom: 15px;
        }
        
        .about-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background-color: var(--secondary-color);
        }
        
        .about-description {
            color: var(--text-color);
            line-height: 1.7;
            font-size: 16px;
        }
        
        @media (max-width: 950px) {
            .container {
                flex-direction: column;
                align-items: center;
            }
            
            .about-card {
                width: 450px;
            }
        }
        
        @media (max-width: 500px) {
            .square-card, .about-card {
                width: 100%;
                height: auto;
            }
            
            .image-container {
                height: 300px;
            }
            
            .time-grid {
                grid-template-columns: 1fr;
            }
            
            .details-header, .details-body {
                padding: 20px;
            }
        }

        /* Back button styles */
        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            margin-bottom: 0;
            width: auto;
            height: 36px;
            border-radius: 18px;
            background-color: var(--card-bg);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            padding: 0 18px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            color: var(--primary-color);
            font-weight: 500;
            z-index: 2;
        }

        .back-button:hover {
            background-color: #f7fafc;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .back-button i {
            color: var(--primary-color);
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <!-- Place back button absolutely inside the first card -->
    <div class="container">
        <div class="card square-card image-card" style="position:relative;">
            <button class="back-button" onclick="goBack()">Back</button>
            <div class="image-container">
                <img src="<?php echo htmlspecialchars($event['event_image'] ?: 'uploads/default-event.jpg'); ?>" 
                     alt="<?php echo htmlspecialchars($event['event_title']); ?>">
                <?php if ($isEventEnded): ?>
                <div class="image-overlay">
                    Event Ended
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Event Details Card -->
        <div class="card square-card details-card">
            <div class="details-header">
                <div class="details-header-content">
                    <h1 class="event-title"><?php echo htmlspecialchars($event['event_title']); ?></h1>
                    <button class="heart-icon" data-id="<?php echo $event['id']; ?>" onclick="toggleEvent(this)">
                        <i class="<?php echo $isSaved ? 'fas' : 'far'; ?> fa-heart <?php echo $isSaved ? 'active' : ''; ?>"></i>
                    </button>
                </div>
                <div class="date-badge">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo date('F j, Y', strtotime($event['event_date'])); ?></span>
                </div>
            </div>
            
            <div class="details-body">
                <div class="details-section">
                    <h3 class="section-title">Event Schedule</h3>
                    <div class="time-grid">
                        <div class="time-block">
                            <div class="time-icon">
                                <i class="fas fa-hourglass-start"></i>
                            </div>
                            <div class="time-details">
                                <div class="time-label">STARTS</div>
                                <div class="time-value"><?php echo date('g:i A', strtotime($event['event_start_time'])); ?></div>
                            </div>
                        </div>
                        
                        <div class="time-block">
                            <div class="time-icon">
                                <i class="fas fa-hourglass-end"></i>
                            </div>
                            <div class="time-details">
                                <div class="time-label">ENDS</div>
                                <div class="time-value"><?php echo date('g:i A', strtotime($event['event_end_time'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="details-section">
                    <h3 class="section-title">Venue Information</h3>
                    <div class="location-block">
                        <div class="location-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="location-details">
                            <div class="location-label">LOCATION</div>
                            <div class="location-value"><?php echo htmlspecialchars($event['event_place']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="created-date">
                    Created: <?php echo date('F j, Y', strtotime($event['created_at'])); ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- About This Event Card -->
    <div class="container">
        <div class="card about-card">
            <h2 class="about-title">About This Event</h2>
            <p class="about-description"><?php echo nl2br(htmlspecialchars($event['event_description'])); ?></p>
        </div>
    </div>
</body>
<script>
    // Add this before the existing scripts
    function goBack() {
        window.history.back();
    }

    // Toggle saved event
    async function toggleEvent(button) {
        const eventId = button.dataset.id;
        const icon = button.querySelector('i');
        const isSaved = icon.classList.contains('active');
        const action = isSaved ? 'unsave' : 'save';

        try {
            const response = await fetch('view_event.php', {
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
</html>