<?php
include 'sqlconnection.php';
include 'user_navbar.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch bookmarked jobs with job details
$sql = "SELECT b.id as bookmark_id, b.created_at as saved_at, j.*, 
        CASE 
            WHEN j.posted_by_type = 'admin' THEN a.name
            WHEN j.posted_by_type = 'user' THEN CONCAT(u.first_name, ' ', u.last_name)
        END AS posted_by_name,
        CASE 
            WHEN j.status = 'open' THEN 'visible'
            WHEN j.status = 'closed' THEN 'hidden'
        END AS visibility_status
        FROM bookmarks b
        INNER JOIN job_listings j ON b.job_id = j.job_id
        LEFT JOIN admin_users a ON j.posted_by_id = a.admin_id AND j.posted_by_type = 'admin'
        LEFT JOIN users u ON j.posted_by_id = u.user_id AND j.posted_by_type = 'user'
        WHERE b.user_id = ?
        ORDER BY j.status = 'open' DESC, b.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookmarkedJobs = $result->fetch_all(MYSQLI_ASSOC);
$savedCount = count($bookmarkedJobs);

// Fetch user data
$user_sql = "SELECT * FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$userData = $user_stmt->get_result()->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Saved Jobs</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/profile_sidebar.css">
  <!-- Add SweetAlert2 CDN -->
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
      top: 88px; /* Adjusted for navbar height (72px + 16px spacing) */
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
    .profile-header {
      position: relative;
      height: 8rem;
      background: linear-gradient(to right, #4f46e5, #6366f1);
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

    @media (max-width: 767px) {
      .sidebar {
        display: none;
      }
    }

    /* Main Content Styles */
    .card {
      background-color: white;
      border-radius: 0.75rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }

    .btn-primary {
      background-color: #3b5bdb;
      color: white;
    }

    .btn-primary:hover {
      background-color: #364fc7;
    }

    .btn-outline:hover {
      background-color: #f3f4f6;
    }

    /* Page header */
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }

    .page-title {
      font-size: 1.75rem;
      font-weight: 700;
      color: #111827;
    }

    .saved-count {
      background-color: #3b5bdb;
      color: white;
      font-size: 0.875rem;
      font-weight: 500;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
    }

    /* Saved Jobs Grid Layout */
    .saved-jobs-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 1.5rem;
    }

    .saved-job-card {
      position: relative;
      background-color: white;
      border-radius: 0.75rem;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
      overflow: hidden;
      transition: transform 0.2s, box-shadow 0.2s;
      border: 1px solid #e5e7eb;
    }

    .saved-job-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
    }

    /* Heart button */
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

    .saved-job-header {
      padding: 1.5rem 1.5rem 0.75rem;
      border-bottom: 1px solid #f3f4f6;
    }

    .saved-job-company {
      display: flex;
      align-items: center;
      margin-bottom: 0.75rem;
    }

    .company-logo {
      width: 2.5rem;
      height: 2.5rem;
      border-radius: 0.375rem;
      background-color: #f3f4f6;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 0.75rem;
      color: #6b7280;
      font-weight: 600;
      font-size: 1rem;
    }

    .company-name {
      font-size: 0.875rem;
      color: #6b7280;
    }

    .saved-job-title {
      font-size: 1.125rem;
      font-weight: 600;
      color: #111827;
      margin-bottom: 0.75rem;
      line-height: 1.3;
    }

    .saved-job-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      margin-bottom: 1rem;
    }

    .saved-job-meta-item {
      display: flex;
      align-items: center;
      font-size: 0.875rem;
      color: #6b7280;
      line-height: 1.4;
    }

    .saved-job-meta-item i {
      margin-right: 0.5rem;
      font-size: 1rem;
      color: #8b5cf6;
      width: 16px;
      text-align: center;
    }

    .badge {
      display: inline-block;
      padding: 0.25rem 0.5rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 500;
      text-transform: uppercase;
    }

    .badge-primary {
      background-color: #3b5bdb;
      color: white;
    }

    .badge-secondary {
      background-color: #e5e7eb;
      color: #4b5563;
    }

    .badge-success {
      background-color: #10b981;
      color: white;
    }

    .badge-warning {
      background-color: #f59e0b;
      color: white;
    }

    .saved-job-description {
      padding: 0.75rem 1.5rem;
      margin-bottom: 1rem;
    }

    .saved-job-description p {
      font-size: 0.875rem;
      color: #4b5563;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
      line-height: 1.5;
    }

    .saved-job-footer {
      padding: 1rem 1.5rem;
      background-color: #f9fafb;
      border-top: 1px solid #e5e7eb;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .saved-date {
      font-size: 0.75rem;
      color: #6b7280;
    }

    .no-events-message {
      text-align: center;
      padding: 2rem;
    }

    .no-events-icon {
      font-size: 3rem;
      color: #9ca3af;
      margin-bottom: 1rem;
    }

    .no-events-text {
      font-size: 1rem;
      color: #6b7280;
      margin-bottom: 1rem;
    }

    .browse-events-btn {
      display: inline-block;
      padding: 0.5rem 1rem;
      border-radius: 0.375rem;
      font-size: 0.875rem;
      font-weight: 500;
      text-align: center;
      text-decoration: none;
      background-color: #3b5bdb;
      color: white;
      transition: background-color 0.2s;
    }

    .browse-events-btn:hover {
      background-color: #364fc7;
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

    /* Responsive adjustments */
    @media (max-width: 640px) {
      .saved-jobs-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="grid">
      <?php include 'includes/sidebar_profile.php'; ?>

      <!-- Main Content -->
      <div class="content-area">
        <div class="page-header">
          <h1 class="page-title">Saved Jobs</h1>
          <span class="saved-count"><?php echo $savedCount; ?></span>
        </div>

        <!-- Saved Jobs Grid -->
        <div class="saved-jobs-grid" id="savedJobsGrid">
          <?php if ($savedCount > 0): ?>
            <?php foreach ($bookmarkedJobs as $job): ?>
              <?php if ($job['status'] === 'open'): ?>
                <div class="saved-job-card" data-bookmark-id="<?php echo $job['bookmark_id']; ?>">
                  <button class="btn btn-icon btn-outline save-job-btn" data-id="<?php echo $job['bookmark_id']; ?>" title="Remove from saved">
                    <i class="fas fa-heart icon active heart-icon"></i>
                  </button>
                  <div class="saved-job-header">
                    <div class="saved-job-company">
                      <div class="company-logo"><?php echo strtoupper(substr($job['company'], 0, 2)); ?></div>
                      <span class="company-name"><?php echo htmlspecialchars($job['company']); ?></span>
                    </div>
                    <h3 class="saved-job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                    <div class="saved-job-meta">
                      <span class="saved-job-meta-item">
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?>
                      </span>
                      <span class="saved-job-meta-item">
                        <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($job['employment_type']); ?>
                      </span>
                      <span class="saved-job-meta-item">
                        <i class="fas fa-dollar-sign"></i> <?php echo htmlspecialchars($job['salary_range']); ?>
                      </span>
                    </div>
                    <span class="badge badge-<?php echo getStatusBadgeClass($job['status']); ?>">
                      <?php echo strtoupper($job['status']); ?>
                    </span>
                  </div>
                  <div class="saved-job-description">
                    <p><?php echo htmlspecialchars($job['description']); ?></p>
                  </div>
                  <div class="saved-job-footer">
                    <span class="saved-date">Saved <?php echo getTimeAgo($job['saved_at']); ?></span>
                    <a href="job_info.php?id=<?php echo $job['job_id']; ?>&selected=true" class="btn btn-primary view-job-btn">View</a>
                  </div>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        
        <!-- Empty State Message -->
        <div id="emptyState" class="card" style="display: <?php echo $savedCount > 0 ? 'none' : 'block'; ?>">
          <div class="card-content empty-state">
            <i class="fas fa-bookmark empty-state-icon"></i>
            <p class="empty-state-text">You haven't saved any jobs yet.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Function to check and update empty state
      function updateEmptyState() {
        const savedJobsGrid = document.getElementById('savedJobsGrid');
        const emptyState = document.getElementById('emptyState');
        const jobCards = savedJobsGrid.querySelectorAll('.saved-job-card');
        const savedCount = document.querySelector('.saved-count');
        
        if (jobCards.length === 0) {
          savedJobsGrid.style.display = 'none';
          emptyState.style.display = 'block';
          savedCount.textContent = '0';
        } else {
          savedJobsGrid.style.display = 'grid';
          emptyState.style.display = 'none';
          savedCount.textContent = jobCards.length;
        }
      }

      // Handle view job button clicks
      document.querySelectorAll('.view-job-btn').forEach(button => {
        button.addEventListener('click', async function(e) {
          e.preventDefault();
          const jobId = new URLSearchParams(this.href.split('?')[1]).get('id');
          
          // Show loading state
          const originalText = this.textContent;
          this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
          this.disabled = true;
          
          try {
            // First try to get the page number
            const response = await fetch(`job_info.php?ajax=1&check_page=1&id=${jobId}`);
            if (response.ok) {
              const data = await response.json();
              // Redirect with the correct page number
              window.location.href = `job_info.php?id=${jobId}&selected=true&page=${data.page}`;
            } else {
              // If page check fails, redirect with just the ID
              window.location.href = `job_info.php?id=${jobId}&selected=true&page=${data.page}`;
            }
          } catch (error) {
            console.error('Error:', error);
            // Fallback to basic redirect
            window.location.href = this.href;
          }
        });
      });

      const saveButtons = document.querySelectorAll('.save-job-btn');
      
      saveButtons.forEach(button => {
        button.addEventListener('click', async function() {
          const jobCard = this.closest('.saved-job-card');
          const bookmarkId = jobCard.dataset.bookmarkId;
          
          // Show SweetAlert2 confirmation
          const result = await Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to remove this job from your saved list?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, remove it!',
            cancelButtonText: 'Cancel'
          });

          // If user confirms, proceed with deletion
          if (result.isConfirmed) {
            try {
              const response = await fetch('delete_bookmark.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                },
                body: JSON.stringify({ bookmark_id: bookmarkId })
              });

              if (response.ok) {
                // Show success message
                Swal.fire(
                  'Removed!',
                  'The job has been removed from your saved list.',
                  'success'
                );

                jobCard.style.opacity = '0';
                jobCard.style.transform = 'scale(0.95)';
                jobCard.style.transition = 'opacity 0.3s, transform 0.3s';
                
                setTimeout(() => {
                  jobCard.remove();
                  updateEmptyState();
                }, 300);
              }
            } catch (error) {
              console.error('Error:', error);
              Swal.fire(
                'Error!',
                'Something went wrong while removing the job.',
                'error'
              );
            }
          }
        });
      });

      // Initial check for empty state
      updateEmptyState();
    });
  </script>

  <?php
  function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
      case 'open':
        return 'primary';
      case 'closed':
        return 'secondary';
      case 'applied':
        return 'success';
      case 'closing soon':
        return 'warning';
      default:
        return 'secondary';
    }
  }

  function getTimeAgo($timestamp) {
    $datetime = new DateTime($timestamp);
    $now = new DateTime();
    $interval = $now->diff($datetime);
    
    if ($interval->y >= 1) return $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
    if ($interval->m >= 1) return $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
    if ($interval->d >= 1) return $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
    if ($interval->h >= 1) return $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
    if ($interval->i >= 1) return $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
    return 'just now';
  }
  ?>
</body>
</html>