<?php
include 'sqlconnection.php'; // Include your database connection file
include 'user_navbar.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You are not logged in. Please <a href='login.php'>log in</a> to access this page.");
}

$user_id = $_SESSION['user_id']; // Get the logged-in user's ID from the session

// Fetch job listings with the name and profile image of the user or admin who posted the job
$sql = "
    SELECT j.*,
           CASE
               WHEN j.posted_by_type = 'admin' THEN a.name
               WHEN j.posted_by_type = 'user' THEN CONCAT(u.first_name, ' ', COALESCE(NULLIF(u.middle_name, ''), ''), ' ', u.last_name)
           END AS posted_by_name,
           CASE
               WHEN j.posted_by_type = 'admin' THEN a.profile_pic
               WHEN j.posted_by_type = 'user' THEN u.profile_img
           END AS posted_by_profile_img,
           CASE
               WHEN j.posted_by_type = 'admin' THEN a.role
               WHEN j.posted_by_type = 'user' THEN u.role
           END AS poster_role,
           CASE
               WHEN j.posted_by_type = 'user' THEN u.graduation_year
               ELSE NULL
           END AS graduation_year
    FROM job_listings j
    LEFT JOIN admin_users a ON j.posted_by_id = a.admin_id AND j.posted_by_type = 'admin'
    LEFT JOIN users u ON j.posted_by_id = u.user_id AND j.posted_by_type = 'user'
    ORDER BY j.posted_at DESC
";
$result = $conn->query($sql);

$jobListings = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $jobListings[] = $row;
    }
}

// Fetch user data
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

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Job Listings</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Reset and base styles */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
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
      top: 88px; /* Adjusted for new navbar height (72px + 16px spacing) */
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

    /* Card styles */
    .card {
      background-color: white;
      border-radius: 0.5rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }

    .card-header {
      padding: 1.5rem;
      /* border-bottom: 1px solid #e5e7eb; */ /* Removed line under job header */
    }

    .card-content {
      padding: 1.5rem;
    }

    .card-footer {
      padding: 1rem 1.5rem;
      background-color: #f9fafb;
      border-top: 1px solid #e5e7eb;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
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
      font-family: 'Montserrat', sans-serif;
    }

    .profile-title {
      font-size: 0.875rem;
      color: #6b7280;
      font-family: 'Poppins', sans-serif;
    }

    .separator {
      height: 1px;
      background-color: #e5e7eb;
      margin: 2rem -1.5rem 0 -1.5rem; /* 2rem above, 0 below, full width */
      border: none;
      width: auto;
    }

    /* Navigation */
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
      font-family: 'Poppins', sans-serif;
    }

    .nav-link:hover {
      background-color: #f3f4f6;
    }

    .nav-link.active {
      background-color: rgba(79, 70, 229, 0.1);
      color: #4f46e5;
    }

    /* Button styles */
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
      font-family: 'Poppins', sans-serif;
    }

    .btn-primary {
      background-color: #4f46e5;
      color: white;
      border: 1px solid #4f46e5;
    }

    .btn-primary:hover {
      background-color: #4338ca;
    }

    .btn-outline {
      background-color: transparent;
      color: #4b5563;
      border: 1px solid #d1d5db;
    }

    .btn-outline:hover {
      background-color: #f3f4f6;
    }

    .btn-full {
      width: 100%;
    }

    @media (max-width: 767px) {
      .btn.btn-primary {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        min-width: unset;
      }
    }

    /* Job card styles */
    .job-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .job-title {
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 0.25rem;
      font-family: 'Montserrat', sans-serif;
    }

    .job-company {
      font-size: 1rem;
      font-weight: 500;
      color: #4b5563;
      font-family: 'Poppins', sans-serif;
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
      background-color: #4f46e5;
      color: white;
    }

    .badge-secondary {
      background-color: #e5e7eb;
      color: #4b5563;
    }

    .job-details {
      display: grid;
      grid-template-columns: 1fr;
      gap: 1rem;
      margin: 1.5rem 0;
    }

    @media (min-width: 640px) {
      .job-details {
        grid-template-columns: 1fr 1fr;
      }
    }

    .job-detail {
      display: flex;
      align-items: center;
      font-size: 0.875rem;
      color: #6b7280;
      padding: 0.5rem;
      background-color: #f9fafb;
      border-radius: 0.375rem;
    }

    .job-detail svg {
      width: 1rem;
      height: 1rem;
      margin-right: 0.5rem;
      flex-shrink: 0;
    }

    .job-section {
      margin-bottom: 1.5rem;
    }

    .job-section-title {
      font-size: 1rem;
      font-weight: 600;
      margin-bottom: 0.75rem;
      color: #374151;
    }

    .job-section-content {
      font-size: 0.875rem;
      color: #4b5563;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
      line-height: 1.6;
    }

    /* Page header */
    .page-title {
      font-size: 1.75rem;
      font-weight: 700;
      color: #111827;
      margin-bottom: 0.5rem;
      font-family: 'Montserrat', sans-serif;
    }

    .page-subtitle {
      font-size: 1rem;
      color: #6b7280;
      margin-bottom: 2rem;
    }

    /* Spacing */
    .mb-4 {
      margin-bottom: 1rem;
    }

    .mb-6 {
      margin-bottom: 1.5rem;
    }

    .space-y-4 > * + * {
      margin-top: 1.5rem;
    }

    /* Content area */
    .content-area {
      width: 100%;
    }

    .post-input-card {
      margin-bottom: 1.5rem;
    }

    .post-input-wrapper {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem;
    }

    .profile-pic {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: #e5e7eb;
      flex-shrink: 0;
      overflow: hidden;
    }

    .profile-pic img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .post-input {
      flex-grow: 1;
      padding: 0.75rem;
      border: 1px solid #e5e7eb;
      border-radius: 999px;
      background-color: #f3f4f6;
      color: #6b7280;
      cursor: pointer;
      transition: background-color 0.2s;
      text-decoration: none;
    }

    .post-input:hover {
      background-color: #e5e7eb;
    }

    /* Posted by section */
    .posted-by {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid #e5e7eb;
    }

    .posted-by-pic {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      overflow: hidden;
      flex-shrink: 0;
    }

    .posted-by-pic img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .posted-by-info {
      display: flex;
      flex-direction: column;
    }

    .posted-by-name {
      font-size: 0.875rem;
      font-weight: 600;
      color: #374151;
    }

    .posted-by-type {
      font-size: 0.75rem;
      color: #6b7280;
      margin-bottom: 0.125rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    /* Modal styles (copied from user_posted_jobs.php) */
    .modal-overlay {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.6);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s;
    }
    .modal-overlay.active {
      opacity: 1;
      visibility: visible;
    }
    .modal {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      width: 95%;
      max-width: 700px;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
      transform: translateY(-20px);
      transition: transform 0.3s;
    }
    .modal-overlay.active .modal {
      transform: translateY(0);
    }
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 30px;
      border-bottom: 1px solid #e5e7eb;
      background: #fff;
      border-radius: 12px 12px 0 0;
    }
    .modal-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #1f2937;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .modal-title i {
      color: #4f46e5;
    }
    .modal-close {
      background: none;
      border: none;
      font-size: 26px;
      cursor: pointer;
      color: #6b7280;
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      padding: 0;
      margin: -8px;
      line-height: 0;
      text-align: center;
      font-family: Arial, sans-serif;
    }
    .modal-close:hover {
      color: #ef4444;
      background: rgba(239,68,68,0.1);
      transform: scale(1.1);
    }
    .modal-body {
      padding: 30px;
    }
    .form-group {
      margin-bottom: 1.5rem;
    }
    .form-grid {
      display: grid;
      gap: 1.5rem;
    }
    @media (min-width: 768px) {
      .form-grid {
        grid-template-columns: 1fr 1fr;
      }
    }
    label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
      color: #1f2937;
    }
    input, select, textarea {
      width: 100%;
      padding: 0.75rem 1rem;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      font-size: 0.95rem;
      background: #f8fafc;
      transition: all 0.2s;
    }
    input:focus, select:focus, textarea:focus {
      outline: none;
      border-color: #4f46e5;
      box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
    }
    textarea {
      min-height: 120px;
      resize: vertical;
      line-height: 1.6;
    }
    .form-footer {
      display: flex;
      justify-content: flex-end;
      gap: 0.75rem;
      margin-top: 1.5rem;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      padding: 0.6rem 1.2rem;
      background-color: #4f46e5;
      color: white;
      border: none;
      border-radius: 0.5rem;
      font-size: 0.95rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .btn:hover {
      background-color: #4338ca;
      transform: translateY(-1px);
    }
    .btn-secondary {
      background-color: #6b7280;
    }
    .btn-secondary:hover {
      background-color: #4b5563;
    }
    @media (max-width: 576px) {
      .modal { padding: 0.5rem; }
      .modal-header, .modal-body { padding: 1rem; }
      .form-footer { flex-direction: column; gap: 0.5rem; }
      .form-footer .btn { width: 100%; }
    }

    /* Responsive improvements */
    @media (max-width: 640px) {
      .card-header, .card-content, .card-footer {
        padding: 1.25rem;
      }
      
      .job-title {
        font-size: 1.125rem;
      }
      
      .job-company {
        font-size: 0.875rem;
      }
      
      .job-section-title {
        font-size: 0.875rem;
      }
      
      .space-y-4 > * + * {
        margin-top: 1.25rem;
      }
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
        <!-- New Post Input Field -->
        <div class="card post-input-card">
          <div class="post-input-wrapper">
            <div class="profile-pic">
              <?php if (!empty($userData['profile_img'])): ?>
                <img src="<?php echo htmlspecialchars($userData['profile_img']); ?>" alt="Profile">
              <?php else: ?>
                <img src="assets/default-profile.png" alt="Default Profile">
              <?php endif; ?>
            </div>
            <!-- Use input field instead of button, open modal on click -->
            <input
              type="text"
              class="post-input"
              id="openPostJobModalInput"
              value="What's on your mind?"
              readonly
              style="background-color:#f3f4f6; color:#6b7280; cursor:pointer;"
            />
          </div>
        </div>

        <!-- Post Job Modal (copied/adapted from user_posted_jobs.php) -->
        <div class="modal-overlay" id="postJobModal">
          <div class="modal">
            <div class="modal-header">
              <h2 class="modal-title"><i class="fas fa-plus"></i> Post New Job</h2>
              <button class="modal-close" onclick="closePostJobModal()">&times;</button>
            </div>
            <div class="modal-body">
              <form id="postJobForm" method="POST">
                <input type="hidden" name="action" value="add_job">
                <!-- Removed status field, status will be set to active automatically -->
                <div class="form-grid">
                  <div class="form-column">
                    <div class="form-group">
                      <label for="title">Job Title *</label>
                      <input type="text" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                      <label for="company">Company Name *</label>
                      <input type="text" id="company" name="company" required>
                    </div>
                    <div class="form-group">
                      <label for="location">Location</label>
                      <input type="text" id="location" name="location" placeholder="e.g. Manila, Philippines">
                    </div>
                    <div class="form-group">
                      <label for="salary_range">Salary Range</label>
                      <input type="text" id="salary_range" name="salary_range" placeholder="e.g. ₱25,000 - ₱35,000">
                    </div>
                  </div>
                  <div class="form-column">
                    <div class="form-group">
                      <label for="employment_type">Employment Type *</label>
                      <select id="employment_type" name="employment_type" required>
                        <option value="Full-time">Full-time</option>
                        <option value="Part-time">Part-time</option>
                        <option value="Contract">Contract</option>
                        <option value="Freelance">Freelance</option>
                        <option value="Internship">Internship</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="job_type">Job Type *</label>
                      <select id="job_type" name="job_type" required>
                        <option value="On-site">On-site</option>
                        <option value="Remote">Remote</option>
                        <option value="Hybrid">Hybrid</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="contact_email">Contact Email *</label>
                      <input type="email" id="contact_email" name="contact_email" required>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label for="description">Job Description *</label>
                  <textarea id="description" name="description" rows="5" required></textarea>
                </div>
                <div class="form-group">
                  <label for="requirements">Requirements *</label>
                  <textarea id="requirements" name="requirements" rows="5" required></textarea>
                </div>
                <!-- Removed status select -->
                <div class="form-footer">
                  <button type="button" class="btn btn-secondary" onclick="closePostJobModal()">Cancel</button>
                  <button type="submit" class="btn">Post Job</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <!-- End Post Job Modal -->

        <div class="space-y-4">
          <?php foreach ($jobListings as $job): ?>
            <div class="card">
              <div class="card-header">
                <!-- Posted by information (moved above job-header, lines fixed) -->
                <div class="posted-by" style="margin-top:0; padding-top:0; border-top:none; margin-bottom: 2rem;">
                  <div class="posted-by-pic">
                    <?php if (!empty($job['posted_by_profile_img'])): ?>
                      <img src="<?php echo htmlspecialchars($job['posted_by_profile_img']); ?>" alt="Posted by">
                    <?php else: ?>
                      <img src="assets/default-profile.png" alt="Default Profile">
                    <?php endif; ?>
                  </div>
                  <div class="posted-by-info">
                    <span class="posted-by-name"><?php echo htmlspecialchars($job['posted_by_name'] ?? 'Unknown User'); ?></span>
                    <span class="posted-by-type">
                        Posted by
                    </span>
                  </div>
                </div>
                <!-- Separator between posted by and job header (full width, normal color, more space) -->
                <div class="separator"></div>
                <div class="job-header" style="margin: 2rem 0;">
                  <div>
                    <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                    <p class="job-company"><?php echo htmlspecialchars($job['company']); ?></p>
                  </div>
                  <span class="badge badge-primary"><?php echo htmlspecialchars(ucfirst($job['status'])); ?></span>
                </div>
                <!-- Separator between job header and job details (full width, normal color, equal margin) -->
                <div class="separator"></div>
              </div>
              
              <div class="card-content">
                <div class="job-details">
                  <div class="job-detail">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                      <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <span><?php echo htmlspecialchars($job['location']); ?></span>
                  </div>
                  <div class="job-detail">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                      <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                    </svg>
                    <span><?php echo htmlspecialchars($job['employment_type']); ?></span>
                  </div>
                  <div class="job-detail">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="12" y1="1" x2="12" y2="23"></line>
                      <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    <span><?php echo htmlspecialchars($job['salary_range']); ?></span>
                  </div>
                  <div class="job-detail">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"></circle>
                      <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>Posted <?php echo getTimeAgo($job['posted_at']); ?></span>
                  </div>
                </div>
                
                <div class="job-section">
                  <h4 class="job-section-title">Job Description</h4>
                  <p class="job-section-content"><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                </div>
                
                <div class="job-section">
                  <h4 class="job-section-title">Requirements</h4>
                  <p class="job-section-content"><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></p>
                </div>
              </div>
              
              <div class="card-footer">
                <div style="flex: 1;"></div>
                <a href="job_info.php?id=<?php echo $job['job_id']; ?>&selected=true" class="btn btn-primary" style="margin-left:auto;">View Details</a>
              </div>
            </div>
          <?php endforeach; ?>

          <?php if (empty($jobListings)): ?>
            <div class="card">
              <div class="card-content">
                <p class="text-center">No job listings available at the moment.</p>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // Open modal on input click
    document.getElementById('openPostJobModalInput').addEventListener('click', function() {
      document.getElementById('postJobModal').classList.add('active');
      document.body.style.overflow = 'hidden'; // Prevent background scrolling
    });

    // Close modal
    function closePostJobModal() {
      document.getElementById('postJobModal').classList.remove('active');
      document.body.style.overflow = ''; // Restore scrolling
    }

    // Handle form submission via AJAX
    document.getElementById('postJobForm').addEventListener('submit', function(e) {
      e.preventDefault();
      var form = this;
      var formData = new FormData(form);

      // Show loading
      Swal.fire({
        title: 'Posting Job...',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => { Swal.showLoading(); }
      });

      fetch('user_posted_jobs.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => { // Fixed syntax error here
        Swal.close();
        if (data.success) {
          Swal.fire({
            title: 'Success!',
            text: data.message,
            icon: 'success',
            confirmButtonColor: '#4f46e5'
          }).then(() => {
            closePostJobModal();
            form.reset(); // Reset form
            window.location.reload();
          });
        } else {
          Swal.fire({
            title: 'Error!',
            text: data.message || 'An error occurred while posting the job.',
            icon: 'error',
            confirmButtonColor: '#4f46e5'
          });
        }
      })
      .catch(error => {
        console.error('Error:', error);
        Swal.fire({
          title: 'Error!',
          text: 'An error occurred while posting the job.',
          icon: 'error',
          confirmButtonColor: '#4f46e5'
        });
      });
    });

    // Close modal on overlay click
    document.querySelector('.modal-overlay').addEventListener('click', function(e) {
      if (e.target === this) closePostJobModal();
    });

    // Prevent modal close when clicking modal content
    document.querySelector('.modal').addEventListener('click', function(e) {
      e.stopPropagation();
    });
  </script>
  <?php
  // Helper function to format time ago
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