<?php
session_start(); // Start the session to access user data
include 'sqlconnection.php'; // Include your database connection file
include 'user_navbar.php'; // Include your navbar

// Get the current date and time
$currentDateTime = date('Y-m-d H:i:s');

// Fetch upcoming events (events that have not ended yet)
$upcomingEventsSql = "
    SELECT *
    FROM events
    WHERE CONCAT(event_date, ' ', event_end_time) > '$currentDateTime'
    ORDER BY event_date ASC
";
$upcomingEventsResult = $conn->query($upcomingEventsSql);

$upcomingEvents = [];
if ($upcomingEventsResult->num_rows > 0) {
    while ($row = $upcomingEventsResult->fetch_assoc()) {
        $upcomingEvents[] = $row; // Store each upcoming event in the $upcomingEvents array
    }
}

// Fetch past events (events that have already ended)
$pastEventsSql = "
    SELECT *
    FROM events
    WHERE CONCAT(event_date, ' ', event_end_time) <= '$currentDateTime'
    ORDER BY event_date DESC
";
$pastEventsResult = $conn->query($pastEventsSql);

$pastEvents = [];
if ($pastEventsResult->num_rows > 0) {
    while ($row = $pastEventsResult->fetch_assoc()) {
        $pastEvents[] = $row; // Store each past event in the $pastEvents array
    }
}

$conn->close(); // Close the database connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Events</title>
  <style>
    /* Reset and base styles */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      line-height: 1.5;
      color: #333;
      background-color: #fff;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem 1rem;
    }

    h1 {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
      color: #1e3a8a; /* Updated to base color */
    }

    h2 {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
      color: #1e3a8a; /* Updated to base color */
    }

    .subtitle {
      color: #666;
      margin-bottom: 2rem;
    }

    /* Event grid */
    .event-grid {
      display: grid;
      grid-template-columns: repeat(1, 1fr);
      gap: 1.5rem;
      margin-bottom: 3rem;
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

    /* Event card */
    .event-card {
      border-radius: 0.5rem;
      overflow: hidden;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      background-color: #fff;
      transition: transform 0.2s ease-in-out;
    }

    .event-card:hover {
      transform: translateY(-5px);
    }

    .event-image-container {
      position: relative;
      width: 100%;
      padding-top: 56.25%; /* 16:9 aspect ratio */
      overflow: hidden;
    }

    .event-image {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s ease;
    }

    .event-card:hover .event-image {
      transform: scale(1.05);
    }

    .event-content {
      padding: 1rem;
    }

    .event-title {
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: #1e3a8a; /* Updated to base color */
    }

    .event-description {
      color: #666;
      margin-bottom: 1rem;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .event-meta {
      margin-top: 1rem;
    }

    .event-meta-item {
      display: flex;
      align-items: center;
      color: #666;
      font-size: 0.875rem;
      margin-bottom: 0.5rem;
    }

    .event-meta-icon {
      margin-right: 0.5rem;
      width: 16px;
      height: 16px;
      color: #1e3a8a; /* Updated to base color */
    }

    .event-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem;
      border-top: 1px solid #eee;
    }

    .bookmark-icon {
      cursor: pointer;
      color: #666;
      transition: color 0.2s ease;
    }

    .bookmark-icon:hover {
      color: #1e3a8a; /* Updated to base color */
    }

    .bookmark-icon.active {
      color: #1e3a8a; /* Updated to base color */
      fill: #1e3a8a; /* Updated to base color */
    }

    .btn {
      display: inline-block;
      padding: 0.5rem 1rem;
      font-size: 0.875rem;
      font-weight: 500;
      text-align: center;
      text-decoration: none;
      border-radius: 0.25rem;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }

    .btn-outline {
      color: #1e3a8a; /* Updated to base color */
      background-color: transparent;
      border: 1px solid #1e3a8a; /* Updated to base color */
    }

    .btn-outline:hover {
      background-color: #1e3a8a; /* Updated to base color */
      color: #fff; /* White text on hover */
    }

    /* Separator */
    .separator {
      height: 1px;
      background-color: #eee;
      margin: 3rem 0;
    }

    /* Empty state */
    .empty-state {
      text-align: center;
      padding: 3rem;
      background-color: #f5f5f5;
      border-radius: 0.5rem;
      width: 1170px;
      height: 250px;
    }

    .empty-state-title {
      font-size: 1.125rem;
      font-weight: 500;
      margin-bottom: 0.5rem;
      color: #1e3a8a; /* Updated to base color */
    }

    .empty-state-message {
      color: #666;
    }
    .toast {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%) translateY(100px);
    background-color: #1e3a8a;
    color: #fff;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 1000;
}

.toast.show {
    transform: translateX(-50%) translateY(0);
    opacity: 1;
}
  </style>
  
</head>
<body>
  <div class="container">
    <header>
      <h1>Events</h1>
      <p class="subtitle">Discover and register for exciting events in your area.</p>
    </header>

    <!-- Upcoming Events Section -->
    <section>
      <h2>Upcoming Events</h2>
      <div class="event-grid" id="upcoming-events">
        <?php if (empty($upcomingEvents)): ?>
          <div class="empty-state">
            <h3 class="empty-state-title">No upcoming events</h3>
            <p class="empty-state-message">Check back later for new events.</p>
          </div>
        <?php else: ?>
          <?php foreach ($upcomingEvents as $event): ?>
            <div class="event-card">
              <div class="event-image-container">
                <img src="<?php echo $event['event_image']; ?>" alt="<?php echo $event['event_title']; ?>" class="event-image">
              </div>
              <div class="event-content">
                <h3 class="event-title"><?php echo $event['event_title']; ?></h3>
                <p class="event-description"><?php echo $event['event_description']; ?></p>
                <div class="event-meta">
                  <div class="event-meta-item">
                    <svg class="event-meta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                      <line x1="16" y1="2" x2="16" y2="6"></line>
                      <line x1="8" y1="2" x2="8" y2="6"></line>
                      <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <?php echo date('F j, Y', strtotime($event['event_date'])); ?>
                  </div>
                  <div class="event-meta-item">
                    <svg class="event-meta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"></circle>
                      <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <?php echo date('h:i A', strtotime($event['event_start_time'])); ?> - <?php echo date('h:i A', strtotime($event['event_end_time'])); ?>
                  </div>
                  <div class="event-meta-item">
                    <svg class="event-meta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                      <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <?php echo $event['event_place']; ?>
                  </div>
                </div>
              </div>
              <div class="event-footer">
                <svg class="bookmark-icon" data-event-id="<?php echo $event['id']; ?>" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                </svg>
                <a href="user_view_event.php?event_id=<?php echo $event['id']; ?>" class="btn btn-outline">View Details</a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <div class="separator"></div>

    <!-- Past Events Section -->
    <section>
      <h2>Past Events</h2>
      <div class="event-grid" id="past-events">
        <?php if (empty($pastEvents)): ?>
          <div class="empty-state">
            <h3 class="empty-state-title">No past events</h3>
            <p class="empty-state-message">There are no past events to display.</p>
          </div>
        <?php else: ?>
          <?php foreach ($pastEvents as $event): ?>
            <div class="event-card">
              <div class="event-image-container">
                <img src="<?php echo $event['event_image']; ?>" alt="<?php echo $event['event_title']; ?>" class="event-image">
              </div>
              <div class="event-content">
                <h3 class="event-title"><?php echo $event['event_title']; ?></h3>
                <p class="event-description"><?php echo $event['event_description']; ?></p>
                <div class="event-meta">
                  <div class="event-meta-item">
                    <svg class="event-meta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                      <line x1="16" y1="2" x2="16" y2="6"></line>
                      <line x1="8" y1="2" x2="8" y2="6"></line>
                      <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <?php echo date('F j, Y', strtotime($event['event_date'])); ?>
                  </div>
                  <div class="event-meta-item">
                    <svg class="event-meta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"></circle>
                      <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <?php echo date('h:i A', strtotime($event['event_start_time'])); ?> - <?php echo date('h:i A', strtotime($event['event_end_time'])); ?>
                  </div>
                  <div class="event-meta-item">
                    <svg class="event-meta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                      <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <?php echo $event['event_place']; ?>
                  </div>
                </div>
              </div>
              <div class="event-footer">
                <svg class="bookmark-icon" data-event-id="<?php echo $event['id']; ?>" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                </svg>
                <a href="user_view_event.php?event_id=<?php echo $event['id']; ?>" class="btn btn-outline">View Details</a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <!-- Toast Notification -->
  <div id="toast" class="toast">
    <p id="toast-message"></p>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const toast = document.getElementById('toast');
      const toastMessage = document.getElementById('toast-message');

      function showToast(message) {
        toastMessage.textContent = message;
        toast.classList.add('show');
        setTimeout(() => {
          toast.classList.remove('show');
        }, 3000); // Hide after 3 seconds
      }

      // Bookmark icon click handler
      document.querySelectorAll('.bookmark-icon').forEach(icon => {
        icon.addEventListener('click', function() {
          const eventId = this.getAttribute('data-event-id');
          const isBookmarked = this.classList.contains('active');
          const action = isBookmarked ? 'unsave' : 'save';

          fetch('bookmark_event.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({ event_id: eventId, action: action }),
          })
          .then(response => response.json())
          .then(data => {
            if (data.status === 'success') {
              this.classList.toggle('active');
              if (this.classList.contains('active')) {
                this.setAttribute('fill', 'currentColor');
                showToast('Event saved!');
              } else {
                this.setAttribute('fill', 'none');
                showToast('Event unsaved!');
              }
            } else {
              alert(data.message);
            }
          });
        });
      });

      // Check bookmarked events on page load
      document.querySelectorAll('.bookmark-icon').forEach(icon => {
        const eventId = icon.getAttribute('data-event-id');

        fetch('bookmark_event.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ event_id: eventId, action: 'check' }),
        })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success' && data.isBookmarked) {
            icon.classList.add('active');
            icon.setAttribute('fill', 'currentColor');
          }
        });
      });
    });
  </script>
</body>
</html>