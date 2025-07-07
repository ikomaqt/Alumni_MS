<?php
include 'sqlconnection.php'; // Include your database connection file
include 'user_navbar.php'; // Include your navbar

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You are not logged in. Please <a href='login.php'>log in</a> to access this page.");
}

$user_id = $_SESSION['user_id'];

// Fetch user data for sidebar
$user_sql = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = $conn->query($user_sql);

if (!$user_result) {
    die("Error fetching user data: " . $conn->error);
}

$userData = [];
if ($user_result->num_rows > 0) {
    $userData = $user_result->fetch_assoc();
} else {
    die("User not found.");
}

// Get the current date and time
$currentDateTime = date('Y-m-d H:i:s');

// Fetch saved events with event details
$savedEventsSql = "
    SELECT se.id as saved_id, se.user_id, se.event_id, se.saved_at,
           e.event_title, e.event_date, e.event_start_time, e.event_end_time, 
           e.event_description, e.event_place, e.event_image
    FROM saved_events se
    JOIN events e ON se.event_id = e.id
    WHERE se.user_id = ?
    ORDER BY se.saved_at DESC";

$stmt = $conn->prepare($savedEventsSql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$savedEvents = $result->fetch_all(MYSQLI_ASSOC);

$conn->close(); // Close the database connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Saved Events</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    /* Reset and base styles */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    }

    body {
      background-color: #f5f5f5;
      color: #333;
      line-height: 1.6;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 1.5rem 1rem;
    }

    /* Grid layout */
    .grid {
      display: grid;
      gap: 2rem;
    }

    @media (min-width: 768px) {
      .grid {
        grid-template-columns: 1fr 3fr;
      }
    }

    /* Sticky sidebar */
    .sidebar {
      position: sticky;
      top: 88px;
      height: fit-content;
      max-height: calc(100vh - 88px);
      overflow-y: auto;
      align-self: start;
    }

    @media (max-width: 767px) {
      .sidebar {
        display: none;
      }
    }

    /* Profile sidebar */
    .profile-card {
      background-color: white;
      border-radius: 0.75rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }

    .profile-header {
      position: relative;
      height: 8rem;
      background-image: url('https://hebbkx1anhila5yf.public.blob.vercel-storage.com/image-sDkUDO5eiVVDPzdRQNGf9pY40T4ejj.png');
      background-size: cover;
      background-position: center;
    }

    .profile-avatar {
      position: absolute;
      bottom: -3rem;
      left: 50%;
      transform: translateX(-50%);
      width: 6rem;
      height: 6rem;
      border-radius: 50%;
      border: 4px solid white;
      background-color: white;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .profile-avatar svg {
      width: 3.5rem;
      height: 3.5rem;
      color: #9ca3af;
    }

    .profile-content {
      padding-top: 3.5rem;
      text-align: center;
    }

    .profile-name {
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 0.25rem;
    }

    .profile-title {
      font-size: 0.875rem;
      color: #6b7280;
    }

    .separator {
      height: 1px;
      background-color: #e5e7eb;
      margin: 1rem 0;
    }

    .nav-list {
      list-style: none;
    }

    .nav-item {
      margin-bottom: 0.25rem;
    }

    .nav-link {
      display: block;
      padding: 0.5rem 0.75rem;
      border-radius: 0.375rem;
      font-size: 0.875rem;
      font-weight: 500;
      color: #4b5563;
      text-decoration: none;
      transition: background-color 0.2s;
    }

    .nav-link:hover {
      background-color: #f3f4f6;
    }

    .nav-link.active {
      background-color: rgba(79, 70, 229, 0.1);
      color: #4f46e5;
      position: relative;
    }

    .nav-link.active::before {
      content: '';
      position: absolute;
      left: 0.5rem;
      top: 50%;
      transform: translateY(-50%);
      width: 0.25rem;
      height: 0.25rem;
      border-radius: 50%;
      background-color: #4f46e5;
      animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
      0% { transform: translateY(-50%) scale(1); opacity: 1; }
      50% { transform: translateY(-50%) scale(1.5); opacity: 0.7; }
      100% { transform: translateY(-50%) scale(1); opacity: 1; }
    }

    .btn {
      display: inline-block;
      padding: 0.5rem 1rem;
      border-radius: 0.375rem;
      font-size: 0.875rem;
      font-weight: 500;
      text-align: center;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-outline {
      background-color: transparent;
      color: #4b5563;
      border: 1px solid #d1d5db;
    }

    .btn-full {
      width: 100%;
    }

    .btn-outline:hover {
      background-color: #f3f4f6;
    }

    /* Main Content Styles */
    .card {
      background-color: white;
      border-radius: 0.75rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }

    .card-content {
      text-align: center;
      padding: 2rem;
      color: #6b7280;
      font-size: 1.1rem;
    }

    .btn-primary {
      background-color: #3b5bdb;
      color: white;
    }

    .btn-primary:hover {
      background-color: #364fc7;
    }

    /* Page header */
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      position: relative;
    }

    .page-title {
      font-size: 1.75rem;
      font-weight: 700;
      color: #111827;
      display: flex;
      align-items: center;
    }

    .page-title-icon {
      margin-right: 0.75rem;
      font-size: 1.5rem;
      color: #8b5cf6;
      animation: bounce 2s infinite;
    }

    @keyframes bounce {
      0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
      40% { transform: translateY(-10px); }
      60% { transform: translateY(-5px); }
    }

    .saved-count {
      background-color: #3b5bdb;
      color: white;
      font-size: 0.875rem;
      font-weight: 500;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      position: relative;
    }

    /* Remove the ping animation and after element for saved-count */

    /* Saved Events Grid Layout */
    .saved-events-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 1.5rem;
    }

    .saved-event-card {
      position: relative;
      background-color: white;
      border-radius: 0.75rem;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
      overflow: hidden;
      transition: transform 0.2s, box-shadow 0.2s;
      border: 1px solid #e5e7eb;
    }

    .saved-event-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
    }

    /* Save button */
    .save-job-btn {
      position: absolute;
      top: 1rem;
      right: 1rem;
      z-index: 10;
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: white;
      border: 1px solid #e5e7eb;
      border-radius: 0.375rem;
      cursor: pointer;
      transition: background-color 0.2s;
    }

    .save-job-btn:hover {
      background-color: #f3f4f6;
    }

    .heart-icon {
      color: #9ca3af;
      transition: color 0.2s;
    }

    .heart-icon.active {
      color: #ef4444;
    }

    /* Event image */
    .event-image {
      width: 100%;
      height: 160px;
      object-fit: cover;
      transition: transform 0.5s ease;
    }

    .saved-event-card:hover .event-image {
      transform: scale(1.05);
    }

    .event-image-container {
      position: relative;
      overflow: hidden;
    }

    .event-details {
      display: flex;
      flex-direction: column;
      margin-bottom: 1.25rem;
    }

    .event-info {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .event-info-item {
      display: flex;
      align-items: center;
      font-size: 0.875rem;
      color: #6b7280;
      line-height: 1.4;
    }

    .event-info-item i {
      margin-right: 0.5rem;
      color: #3b5bdb;
      width: 16px;
      text-align: center;
      font-size: 1rem;
    }

    .event-content {
      padding: 1.25rem;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .event-title {
      font-size: 1.125rem;
      font-weight: 600;
      color: #111827;
      margin-bottom: 0.75rem;
      line-height: 1.3;
      transition: color 0.3s ease;
    }

    .saved-event-card:hover .event-title {
      color: #8b5cf6;
    }

    .event-description {
      font-size: 0.875rem;
      color: #4b5563;
      margin-bottom: 1rem;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
      flex: 1;
    }

    .event-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-top: 0.75rem;
      border-top: 1px solid #e5e7eb;
      margin-top: auto;
    }

    .saved-date {
      font-size: 0.75rem;
      color: #6b7280;
      display: flex;
      align-items: center;
    }

    .saved-date i {
      margin-right: 0.25rem;
      color: #3b5bdb;
    }

    /* View button with special effects */
    .view-event-btn {
      position: relative;
      overflow: hidden;
      z-index: 1;
      transition: all 0.3s ease;
    }

    .view-event-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.1);
      transform: translateX(-100%);
      transition: transform 0.3s ease;
      z-index: -1;
    }

    .view-event-btn:hover::before {
      transform: translateX(0);
    }

    /* Responsive adjustments */
    @media (max-width: 640px) {
      .saved-events-grid {
        grid-template-columns: 1fr;
      }
    }

    /* Spinner animation */
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    .fa-spin {
      animation: spin 1s linear infinite;
    }

    /* Empty state styles */
    .empty-state {
      text-align: center;
      padding: 3rem 1rem;
    }

    .empty-state-icon {
      font-size: 3rem;
      color: #9ca3af;
      margin-bottom: 1rem;
    }

    .empty-state-text {
      color: #4b5563;
      font-size: 1.1rem;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="grid">
      <!-- Include the sidebar -->
      <?php include 'includes/sidebar_profile.php'; ?>

      <!-- Main Content -->
      <div class="content-area">
        <div class="page-header">
          <h1 class="page-title">
            Saved Events
          </h1>
          <span class="saved-count"><?php echo count($savedEvents); ?></span>
        </div>

        <!-- Saved Events Grid -->
        <div class="saved-events-grid">
          <?php if (!empty($savedEvents)): ?>
            <?php foreach ($savedEvents as $event): ?>
              <div class="saved-event-card">
                <button class="btn btn-icon btn-outline save-job-btn" data-saved-id="<?php echo $event['saved_id']; ?>" title="Remove from saved">
                  <i class="fas fa-heart icon active heart-icon"></i>
                </button>
                <div class="event-image-container">
                  <img src="<?php echo !empty($event['event_image']) ? htmlspecialchars($event['event_image']) : 'assets/images/default-event.jpg'; ?>" 
                       alt="<?php echo htmlspecialchars($event['event_title']); ?>" 
                       class="event-image">
                </div>
                <div class="event-content">
                  <h3 class="event-title"><?php echo htmlspecialchars($event['event_title']); ?></h3>
                  <div class="event-details">
                    <div class="event-info">
                      <span class="event-info-item">
                        <i class="fas fa-calendar-day"></i>
                        <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                      </span>
                      <span class="event-info-item">
                        <i class="fas fa-clock"></i>
                        <?php 
                        $start = date('g:i A', strtotime($event['event_start_time']));
                        $end = date('g:i A', strtotime($event['event_end_time']));
                        echo "$start - $end";
                        ?>
                      </span>
                      <span class="event-info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($event['event_place']); ?>
                      </span>
                    </div>
                  </div>
                  <p class="event-description">
                    <?php echo htmlspecialchars($event['event_description']); ?>
                  </p>
                  <div class="event-footer">
                    <span class="saved-date">
                      <i class="fas fa-bookmark"></i>
                      Saved <?php echo date('M d, Y', strtotime($event['saved_at'])); ?>
                    </span>
                    <a href="view_event.php?event_id=<?php echo $event['event_id']; ?>" 
                       class="btn btn-primary view-event-btn">View Details</a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        
        <!-- Empty State Message -->
        <div id="emptyState" class="card" style="display: <?php echo count($savedEvents) > 0 ? 'none' : 'block'; ?>">
          <div class="card-content empty-state">
            <i class="fas fa-calendar empty-state-icon"></i>
            <p class="empty-state-text">You haven't saved any events yet.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Remove the view event button click handler and let the anchor tag handle navigation naturally

      // Handle save buttons with animation
      const saveButtons = document.querySelectorAll('.save-job-btn');
      
      saveButtons.forEach(button => {
        button.addEventListener('click', async function() {
          const eventCard = this.closest('.saved-event-card');
          const savedId = this.dataset.savedId;
          
          // Show SweetAlert2 confirmation
          const result = await Swal.fire({
            title: 'Remove from saved?',
            text: "Are you sure you want to remove this event from your saved list?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3b5bdb',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, remove it',
            cancelButtonText: 'Cancel'
          });

          if (result.isConfirmed) {
            try {
              const formData = new FormData();
              formData.append('saved_id', savedId);
              
              const response = await fetch('delete_saved_event.php', {
                method: 'POST',
                body: formData
              });

              if (response.ok) {
                Swal.fire({
                  title: 'Removed!',
                  text: 'Event has been removed from your saved list',
                  icon: 'success',
                  confirmButtonColor: '#3b5bdb'
                });

                eventCard.style.opacity = '0';
                eventCard.style.transform = 'scale(0.95) translateY(10px)';
                eventCard.style.transition = 'opacity 0.3s, transform 0.3s';
                
                setTimeout(() => {
                  eventCard.remove();
                  const savedCount = document.querySelector('.saved-count');
                  const currentCount = parseInt(savedCount.textContent);
                  savedCount.textContent = currentCount - 1;

                  if (currentCount - 1 === 0) {
                    location.reload();
                  }
                }, 300);
              }
            } catch (error) {
              console.error('Error:', error);
              Swal.fire({
                title: 'Error!',
                text: 'Something went wrong while removing the event',
                icon: 'error',
                confirmButtonColor: '#3b5bdb'
              });
            }
          }
        });
      });
    });
  </script>
</body>
</html>