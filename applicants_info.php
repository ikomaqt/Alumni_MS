<?php
session_start();
include 'sqlconnection.php';
include 'user_navbar.php';

// Get application ID from URL
$application_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Updated SQL query to join with job_listings
$sql = "SELECT ja.*, jl.title as job_title, jl.company, jl.posted_by_id 
        FROM job_applications ja 
        JOIN job_listings jl ON ja.job_id = jl.job_id 
        WHERE ja.application_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $application_id);
$stmt->execute();
$result = $stmt->get_result();
$applicant = $result->fetch_assoc();

// Redirect if application not found or user doesn't have permission
if (!$applicant || $applicant['posted_by_id'] != $_SESSION['user_id']) {
    header('Location: user_posted_jobs.php');
    exit();
}

// Function to get initials from name
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return substr($initials, 0, 2);
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'secondary';
        case 'reviewed':
            return 'default';
        case 'interviewed':
            return 'info';
        case 'hired':
            return 'success';
        case 'rejected':
            return 'destructive';
        default:
            return 'outline';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Applicant Profile</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
  <style>
    /* Reset and base styles */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
      line-height: 1.5;
      color: #1f2937;
      background-color: #f9fafb;
    }

    .container {
      max-width: 1200px;  /* Increased from 1000px */
      margin: 0 auto;
      padding: 2rem 1rem;
    }

    /* Typography */
    h1 {
      font-size: 1.875rem;
      font-weight: 700;
      letter-spacing: -0.025em;
      margin-bottom: 0.5rem;
    }

    h2 {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 1rem;
    }

    h3 {
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 0.75rem;
    }

    p {
      margin-bottom: 1rem;
    }

    .text-muted {
      color: #6b7280;
    }

    .text-sm {
      font-size: 0.875rem;
    }

    /* Layout */
    .header {
      display: flex;
      align-items: center;
      margin-bottom: 2rem;
    }

    .back-button {
      margin-right: 1rem;
    }

    .profile-grid {
      display: grid;
      grid-template-columns: 2fr 3fr;  /* Changed from 1fr 2fr */
      gap: 2rem;  /* Increased from 1.5rem */
    }

    @media (max-width: 1024px) {  /* Changed from 768px */
      .profile-grid {
        grid-template-columns: 1fr;
      }
    }

    .card {
      background-color: white;
      border-radius: 0.5rem;
      box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
      margin-bottom: 1.5rem;
      overflow: hidden;
    }

    .card-header {
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid #e5e7eb;
    }

    .card-content {
      padding: 1.5rem;
    }

    .profile-header {
      text-align: center;
      margin-bottom: 2rem;
      padding: 0 1rem;
    }

    .profile-header h3 {
      font-size: 1.5rem;
      margin: 1rem 0 0.5rem;
    }

    .profile-header .badge {
      margin: 0.5rem 0;
    }

    .card-footer {
      padding: 1rem 1.5rem;
      border-top: 1px solid #e5e7eb;
    }

    .flex {
      display: flex;
    }

    .flex-col {
      flex-direction: column;
    }

    .justify-between {
      justify-content: space-between;
    }

    .items-center {
      align-items: center;
    }

    .gap-2 {
      gap: 0.5rem;
    }

    .gap-3 {
      gap: 0.75rem;
    }

    .gap-4 {
      gap: 1rem;
    }

    .mb-1 {
      margin-bottom: 0.25rem;
    }

    .mb-2 {
      margin-bottom: 0.5rem;
    }

    .mb-3 {
      margin-bottom: 0.75rem;
    }

    .mb-4 {
      margin-bottom: 1rem;
    }

    .mb-6 {
      margin-bottom: 1.5rem;
    }

    .mr-1 {
      margin-right: 0.25rem;
    }

    .mr-2 {
      margin-right: 0.5rem;
    }

    .mx-1 {
      margin-left: 0.25rem;
      margin-right: 0.25rem;
    }

    .mt-4 {
      margin-top: 1rem;
    }

    /* Components */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 0.375rem;
      font-weight: 500;
      padding: 0.5rem 1rem;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
    }

    .btn-primary {
      background-color: #3b82f6;
      color: white;
      border: 1px solid #3b82f6;
    }

    .btn-primary:hover {
      background-color: #2563eb;
      border-color: #2563eb;
    }

    .btn-outline {
      background-color: transparent;
      color: #1f2937;
      border: 1px solid #d1d5db;
    }

    .btn-outline:hover {
      background-color: #f3f4f6;
    }

    .badge {
      display: inline-flex;
      align-items: center;
      border-radius: 9999px;
      padding: 0.25rem 0.75rem;
      font-size: 0.75rem;
      font-weight: 500;
    }

    .badge-default {
      background-color: #3b82f6;
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

    .badge-info {
      background-color: #6366f1;
      color: white;
    }

    .badge-warning {
      background-color: #f59e0b;
      color: white;
    }

    .badge-destructive {
      background-color: #ef4444;
      color: white;
    }

    .badge-outline {
      background-color: transparent;
      color: #4b5563;
      border: 1px solid #d1d5db;
    }

    .avatar {
      position: relative;
      width: 6rem;
      height: 6rem;
      border-radius: 9999px;
      overflow: hidden;
      background-color: #e5e7eb;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 500;
      font-size: 2rem;
      color: #4b5563;
      margin: 0 auto 1rem auto;
    }

    .avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .icon {
      width: 1rem;
      height: 1rem;
      display: inline-block;
    }

    .data-item {
      display: flex;
      margin-bottom: 1rem;
      border-bottom: 1px solid #f3f4f6;
      padding-bottom: 0.75rem;
    }

    .data-item:last-child {
      border-bottom: none;
      margin-bottom: 0;
      padding-bottom: 0;
    }

    .data-label {
      width: 35%;  /* Decreased from 40% */
      font-weight: 500;
      color: #4b5563;
    }

    .data-value {
      width: 65%;  /* Increased from 60% */
      word-break: break-word;
    }

    .tabs {
      display: flex;
      border-bottom: 1px solid #e5e7eb;
      margin-bottom: 1.5rem;
    }

    .tab {
      padding: 0.75rem 1rem;
      font-weight: 500;
      cursor: pointer;
      border-bottom: 2px solid transparent;
    }

    .tab.active {
      border-bottom-color: #3b82f6;
      color: #3b82f6;
    }

    .tab-content {
      display: none;
    }

    .tab-content.active {
      display: block;
    }

    .select-status {
      padding: 0.5rem;
      border-radius: 0.375rem;
      border: 1px solid #d1d5db;
      width: 100%;
      font-family: inherit;
      font-size: 0.875rem;
      margin-bottom: 1rem;
    }

    /* Remove these modal styles */
    .modal,
    .modal-content,
    .modal iframe,
    .close-modal {
        display: none;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .card-header-content {
        flex-direction: column;
        align-items: flex-start;
      }

      .card-footer {
        flex-direction: column;
      }

      .card-footer .btn {
        width: 100%;
      }
    }

    .resume-link {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: #3b82f6;
      text-decoration: none;
      font-weight: 500;
      font-size: 0.875rem;
      transition: color 0.2s ease;
    }

    .resume-link:hover {
      color: #2563eb;
    }

    .resume-link svg {
      width: 1rem;
      height: 1rem;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <a href="user_posted_jobs.php" class="btn btn-outline back-button">
        <svg class="icon mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="19" y1="12" x2="5" y2="12"></line>
          <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Back to Job Postings
      </a>
    </div>

    <div class="profile-grid">
      <!-- Left Column - Applicant Info -->
      <div>
        <div class="card">
          <div class="card-content">
            <div class="profile-header">
              <div class="avatar" style="border: 2px solid #e5e7eb;">
                <span><?= getInitials($applicant['name']) ?></span>
              </div>
              <h3><?= htmlspecialchars($applicant['name']) ?></h3>
              <span class="badge badge-<?= getStatusBadgeClass($applicant['status']) ?>">
                <?= ucfirst(htmlspecialchars($applicant['status'])) ?>
              </span>
            </div>


            <div class="data-item">
              <div class="data-label">Email:</div>
              <div class="data-value">
                <a href="mailto:<?= htmlspecialchars($applicant['email']) ?>" 
                   class="btn btn-outline" 
                   style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                  <svg class="icon mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                  </svg>
                  <?= htmlspecialchars($applicant['email']) ?>
                </a>
              </div>
            </div>

            <div class="data-item">
              <div class="data-label">Phone:</div>
              <div class="data-value">
                <a href="tel:<?= htmlspecialchars($applicant['phone']) ?>" 
                   class="btn btn-outline"
                   style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                  <svg class="icon mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                  </svg>
                  <?= htmlspecialchars($applicant['phone']) ?>
                </a>
              </div>
            </div>

            <div class="data-item">
              <div class="data-label">Applied At:</div>
              <div class="data-value"><?= date('M d, Y \a\t h:i A', strtotime($applicant['applied_at'])) ?></div>
            </div>

            <div class="data-item">
              <div class="data-label">Resume:</div>
              <div class="data-value">
                <?php if (!empty($applicant['resume_path'])): ?>
                <a href="<?= htmlspecialchars($applicant['resume_path']) ?>" 
                   class="resume-link"
                   target="_blank"
                   rel="noopener noreferrer">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                  </svg>
                  View Resume
                </a>
                <?php else: ?>
                No resume uploaded
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <div style="width: 100%;">
              <label for="status" class="text-sm font-medium mb-2 block">Update Status:</label>
              <select id="status" class="select-status" onchange="updateApplicationStatus(<?= $applicant['application_id'] ?>, this.value)">
                <option value="pending" <?= $applicant['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="reviewed" <?= $applicant['status'] == 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                <option value="interviewed" <?= $applicant['status'] == 'interviewed' ? 'selected' : '' ?>>Interviewed</option>
                <option value="hired" <?= $applicant['status'] == 'hired' ? 'selected' : '' ?>>Hired</option>
                <option value="rejected" <?= $applicant['status'] == 'rejected' ? 'selected' : '' ?>>Rejected</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column - Application Details -->
      <div>
        <div class="card">
          <div class="card-header">
            <div class="flex justify-between items-center">
              <h2>Application Details</h2>
              <span class="text-sm text-muted">Applied for: <?= htmlspecialchars($applicant['job_title']) ?></span>
            </div>
          </div>
          <div class="card-content">
            <div class="tabs">
              <div class="tab active" data-tab="application-data">Application Data</div>
              <div class="tab" data-tab="cover-letter">Cover Letter</div>
            </div>

            <div id="application-data" class="tab-content active">
              <!-- Application metadata -->
              <?php 
              foreach ($applicant as $key => $value): 
                // Skip sensitive fields and already displayed fields
                if (in_array($key, [
                    'application_id', 
                    'job_id', 
                    'user_id', 
                    'posted_by_id',
                    'cover_letter', 
                    'resume_path'
                ])) continue; ?>
                <div class="data-item">
                  <div class="data-label"><?= ucwords(str_replace('_', ' ', $key)) ?>:</div>
                  <div class="data-value"><?= htmlspecialchars($value) ?></div>
                </div>
              <?php endforeach; ?>
            </div>

            <div id="cover-letter" class="tab-content">
              <div style="white-space: pre-line;">
                <?= nl2br(htmlspecialchars($applicant['cover_letter'])) ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Tab switching functionality
    document.addEventListener('DOMContentLoaded', function() {
      const tabs = document.querySelectorAll('.tab');
      
      tabs.forEach(tab => {
        tab.addEventListener('click', function() {
          // Get the tab content id
          const tabId = this.getAttribute('data-tab');
          
          // Remove active class from all tabs and tab contents
          document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
          document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
          
          // Add active class to clicked tab and corresponding content
          this.classList.add('active');
          document.getElementById(tabId).classList.add('active');
        });
      });
    });

    function getStatusBadgeClass(status) {
      const classes = {
        'pending': 'secondary',
        'reviewed': 'default',
        'interviewed': 'info',
        'hired': 'success',
        'rejected': 'destructive'
      };
      return classes[status] || 'outline';
    }

    function updateApplicationStatus(applicationId, newStatus) {
      fetch('update_application_status.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `application_id=${applicationId}&status=${newStatus}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update the status badge
          const badge = document.querySelector('.badge');
          badge.className = `badge badge-${getStatusBadgeClass(newStatus)}`;
          badge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
        } else {
          alert('Failed to update status');
        }
      });
    }
  </script>
</body>
</html>