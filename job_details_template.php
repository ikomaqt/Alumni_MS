<?php
// Add this at the very top
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Add right after session_start
if(!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

include 'user_navbar.php';
// If $job is not set, try to fetch it using the id from GET (for direct access)
if (!isset($job) && isset($_GET['id'])) {
    include 'sqlconnection.php';
    $job_id = intval($_GET['id']);
    $sql_job = "SELECT j.*, c.category_name
                FROM job_listings j
                LEFT JOIN job_categories c ON j.category = c.category_id
                WHERE j.job_id = ?";
    $stmt = $conn->prepare($sql_job);
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result_job = $stmt->get_result();
    if ($result_job->num_rows > 0) {
        $job = $result_job->fetch_assoc();
        // Check if the job is bookmarked by the user
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $user_id = $_SESSION['user_id'] ?? 0;
        $is_bookmarked = false;
        if ($user_id) {
            $sql_check_bookmark = "SELECT * FROM bookmarks WHERE user_id = ? AND job_id = ?";
            $stmt_check = $conn->prepare($sql_check_bookmark);
            $stmt_check->bind_param("ii", $user_id, $job_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            if ($result_check->num_rows > 0) {
                $is_bookmarked = true;
            }
        }
        $job['is_bookmarked'] = $is_bookmarked;
    }
    $conn->close();
}

// $job must be set before including this file
if (!isset($job)) {
    echo '<div class="empty-state"><p>Job not found.</p></div>';
    return;
}

// Detect AJAX request or adaptive mode
$is_ajax = (
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_GET['ajax']) && $_GET['ajax'])
);
$adaptive = $is_ajax || (isset($_GET['adaptive']) && $_GET['adaptive'] == '1');

// Output only the job details HTML for AJAX/adaptive
if ($is_ajax || $adaptive) {
    ?>
    <div class="card-header">
      <div class="flex justify-between items-center">
        <div>
          <h2><?= htmlspecialchars($job['title']) ?></h2>
          <p class="text-muted"><?= htmlspecialchars($job['company']) ?></p>
        </div>
        <button class="btn btn-icon btn-outline save-job-detail-btn" data-id="<?= $job['job_id'] ?>">
          <i class="fas fa-heart icon <?= $job['is_bookmarked'] ? 'active' : '' ?> heart-icon"></i>
        </button>
      </div>
    </div>
    <div class="card-content">
      <div style="padding: 1rem;">
        <div class="job-badges mb-4">
          <span class="badge badge-secondary">
            <i class="fas fa-map-marker-alt icon-sm"></i>
            <?= htmlspecialchars($job['location']) ?>
          </span>
          <span class="badge badge-secondary">
            <i class="fas fa-briefcase icon-sm"></i>
            <?= htmlspecialchars($job['employment_type']) ?>
          </span>
        </div>
        <div class="categories-container">
          <?php foreach (explode(',', $job['category_name']) as $category): ?>
            <span class="badge badge-outline"><?= htmlspecialchars(trim($category)) ?></span>
          <?php endforeach; ?>
        </div>
        <div class="job-detail-section">
          <h3 class="job-detail-title">Salary</h3>
          <p><?= htmlspecialchars($job['salary_range']) ?></p>
        </div>
        <div class="job-detail-section">
          <h3 class="job-detail-title">Description</h3>
          <p class="text-muted"><?= nl2br(htmlspecialchars($job['description'])) ?></p>
        </div>
      </div>
    </div>
    <div class="card-footer">
      <a href="apply.php?id=<?= $job['job_id'] ?>" class="btn btn-primary btn-block">Apply Now</a>
    </div>
    <?php
    return;
}

// If not AJAX/adaptive, render a full HTML page (desktop mode)
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($job['title']) ?> - Job Details</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Reset and base styles */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    }
    
    body { 
      background: #f9fafb; 
      font-family: 'Inter', sans-serif; 
      margin: 0;
      line-height: 1.6;
      color: #111827;
    }
    
    .container { 
      max-width: 800px; 
      margin: 0 auto; 
      padding: 1.5rem;
    }
    
    .job-container {
      background: #fff; 
      border-radius: 0.75rem; 
      box-shadow: 0 1px 3px rgba(0,0,0,0.08);
      margin: 1.5rem auto;
      overflow: hidden;
      transition: box-shadow 0.3s ease;
    }
    
    .job-container:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .card-header, .card-footer { 
      padding: 1.75rem 2rem; 
      border-bottom: 1px solid #f3f4f6; 
    }
    
    .card-footer { 
      border-top: 1px solid #f3f4f6; 
      border-bottom: none; 
    }
    
    .card-content { 
      padding: 1.75rem 2rem; 
    }
    
    /* Typography */
    h2 {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: #111827;
    }
    
    h3 {
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: #111827;
    }
    
    .text-muted { 
      color: #6b7280; 
      font-size: 0.95rem; 
    }
    
    /* Badges */
    .badge { 
      display: inline-flex; 
      align-items: center; 
      gap: 0.375rem; 
      padding: 0.375rem 0.75rem; 
      border-radius: 9999px; 
      font-size: 0.8125rem; 
      font-weight: 500; 
      margin-right: 0.5rem;
      margin-bottom: 0.5rem;
    }
    
    .badge-outline { 
      border: 1px solid #e5e7eb; 
      background: transparent; 
      color: #4b5563; 
    }
    
    .badge-secondary { 
      background: #f3f4f6; 
      color: #4b5563; 
    }
    
    /* Buttons */
    .btn { 
      display: inline-flex; 
      align-items: center; 
      justify-content: center; 
      padding: 0.75rem 1.25rem; 
      border-radius: 0.625rem; 
      font-weight: 500; 
      cursor: pointer; 
      border: none; 
      font-size: 0.95rem;
      transition: all 0.3s ease;
    }
    
    .btn-primary { 
      background: #3b82f6; 
      color: #fff; 
    }
    
    .btn-primary:hover { 
      background: #2563eb; 
    }
    
    .btn-outline { 
      background: #fff; 
      border: 1px solid #e5e7eb; 
      color: #4b5563; 
    }
    
    .btn-outline:hover {
      background: #f9fafb;
    }
    
    .btn-icon { 
      padding: 0.625rem; 
      border-radius: 0.625rem; 
    }
    
    .btn-block { 
      display: block; 
      width: 100%; 
    }
    
    /* Heart icon */
    .heart-icon { 
      color: #9ca3af; 
      cursor: pointer; 
      transition: color 0.3s;
      font-size: 1.125rem;
    }
    
    .heart-icon.active { 
      color: #ef4444; 
    }
    
    /* Job details */
    .categories-container { 
      margin: 1.25rem 0; 
      display: flex; 
      flex-wrap: wrap; 
      gap: 0.625rem; 
    }
    
    .job-detail-section { 
      margin-bottom: 2rem; 
    }
    
    .job-detail-title { 
      font-size: 1.25rem; 
      font-weight: 600; 
      margin-bottom: 0.75rem; 
      color: #111827; 
    }
    
    .job-detail-list { 
      list-style-position: outside; 
      padding-left: 1.5rem; 
      color: #4b5563;
      line-height: 1.7;
    }
    
    .job-detail-list li { 
      margin-bottom: 0.75rem; 
    }
    
    /* Back button */
    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.625rem;
      margin: 1.5rem 0 0.5rem;
      background: #fff;
      border: 1px solid #e5e7eb;
      color: #3b82f6;
      padding: 0.625rem 1.25rem;
      border-radius: 0.625rem;
      font-weight: 500;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    
    .back-btn:hover {
      background: #f3f4f6;
      color: #2563eb;
      transform: translateY(-1px);
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    
    /* Utilities */
    .flex {
      display: flex;
    }
    
    .justify-between {
      justify-content: space-between;
    }
    
    .items-center {
      align-items: center;
    }
    
    .mb-4 {
      margin-bottom: 1rem;
    }
    
    /* Responsive adjustments */
    @media (max-width: 640px) {
      .container { 
        padding: 1rem; 
      }
      
      .job-container {
        margin: 1rem auto;
        border-radius: 0.5rem;
      }
      
      .card-header, .card-footer, .card-content { 
        padding: 1.25rem 1.5rem; 
      }
      
      .back-btn {
        margin: 1rem 0 0.5rem;
      }
    }
    
    @media (max-width: 480px) {
      h2 {
        font-size: 1.25rem;
      }
      
      h3 {
        font-size: 1.125rem;
      }
      
      .btn {
        padding: 0.625rem 1rem;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <a href="javascript:history.back()" class="back-btn">
      <i class="fas fa-arrow-left"></i> Back to Jobs
    </a>
    
    <div class="job-container">
      <div class="card-header">
        <div class="flex justify-between items-center">
          <div>
            <h2><?= htmlspecialchars($job['title']) ?></h2>
            <p class="text-muted"><?= htmlspecialchars($job['company']) ?></p>
          </div>
          <button class="btn btn-icon btn-outline save-job-detail-btn" data-id="<?= $job['job_id'] ?>">
            <i class="fas fa-heart icon <?= $job['is_bookmarked'] ? 'active' : '' ?> heart-icon"></i>
          </button>
        </div>
      </div>
      
      <div class="card-content">
        <div class="job-badges mb-4">
          <span class="badge badge-secondary">
            <i class="fas fa-map-marker-alt icon-sm"></i>
            <?= htmlspecialchars($job['location']) ?>
          </span>
          <span class="badge badge-secondary">
            <i class="fas fa-briefcase icon-sm"></i>
            <?= htmlspecialchars($job['employment_type']) ?>
          </span>
          <span class="badge badge-secondary">
            <i class="fas fa-calendar-alt icon-sm"></i>
            Posted <?= htmlspecialchars($job['created_at']) ?>
          </span>
        </div>
        
        <div class="categories-container">
          <?php foreach (explode(',', $job['category_name']) as $category): ?>
            <span class="badge badge-outline"><?= htmlspecialchars(trim($category)) ?></span>
          <?php endforeach; ?>
        </div>
        
        <div class="job-detail-section">
          <h3 class="job-detail-title">Salary</h3>
          <p><?= htmlspecialchars($job['salary_range']) ?></p>
        </div>
        
        <div class="job-detail-section">
          <h3 class="job-detail-title">Description</h3>
          <p class="text-muted"><?= nl2br(htmlspecialchars($job['description'])) ?></p>
        </div>
        
        <div class="job-detail-section">
          <h3 class="job-detail-title">Requirements</h3>
          <ul class="job-detail-list">
            <?php foreach (explode(',', $job['requirements']) as $requirement): ?>
              <li><?= htmlspecialchars(trim($requirement)) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
      
      <div class="card-footer">
        <a href="apply.php?id=<?= $job['job_id'] ?>" class="btn btn-primary btn-block">Apply Now</a>
      </div>
    </div>
  </div>
  
  <script>
    // AJAX save/unsave logic for heart icon on standalone page
    document.querySelectorAll('.save-job-detail-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const jobId = this.dataset.id;
        const icon = this.querySelector('.heart-icon');
        const action = icon.classList.contains('active') ? 'unsave' : 'save';
        
        fetch('job_info.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `action=${action}&job_id=${jobId}`
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            if (action === 'save') {
              icon.classList.add('active');
              showToast('Job saved successfully');
            } else {
              icon.classList.remove('active');
              showToast('Job removed from saved');
            }
          } else {
            showToast(data.message || 'Action failed');
          }
        })
        .catch(() => {
          showToast('Network error. Please try again.');
        });
      });
    });
    
    // Toast utility
    function showToast(message) {
      // Create toast if it doesn't exist
      let toast = document.getElementById('toast');
      if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        toast.style.position = 'fixed';
        toast.style.bottom = '1.5rem';
        toast.style.left = '50%';
        toast.style.transform = 'translateX(-50%)';
        toast.style.backgroundColor = '#1f2937';
        toast.style.color = 'white';
        toast.style.padding = '0.875rem 1.75rem';
        toast.style.borderRadius = '0.625rem';
        toast.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
        toast.style.zIndex = '1000';
        toast.style.fontSize = '0.95rem';
        toast.style.transition = 'opacity 0.3s, visibility 0.3s';
        document.body.appendChild(toast);
      }
      
      // Set message and show toast
      toast.textContent = message;
      toast.style.opacity = '1';
      toast.style.visibility = 'visible';
      
      // Hide toast after 3 seconds
      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.visibility = 'hidden';
      }, 3000);
    }
  </script>
</body>
</html>