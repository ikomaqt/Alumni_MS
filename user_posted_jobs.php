<?php
// Start session at the beginning of the file
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
include 'sqlconnection.php';

// Get user role from the database
$user_query = "SELECT role FROM users WHERE user_id = " . $_SESSION['user_id'];
$user_result = $conn->query($user_query);
$user_role = '';

if ($user_result && $user_result->num_rows > 0) {
    $user_data = $user_result->fetch_assoc();
    $user_role = $user_data['role'];
}

// Add helper functions for generating options
function generateEmploymentTypeOptions($selected) {
    $types = ['Full-time', 'Part-time', 'Contract', 'Freelance', 'Internship'];
    $options = '';
    foreach ($types as $type) {
        $options .= '<option value="' . $type . '"' . ($selected == $type ? ' selected' : '') . '>' . $type . '</option>';
    }
    return $options;
}

function generateJobTypeOptions($selected) {
    $types = ['On-site', 'Remote', 'Hybrid'];
    $options = '';
    foreach ($types as $type) {
        $options .= '<option value="' . $type . '"' . ($selected == $type ? ' selected' : '') . '>' . $type . '</option>';
    }
    return $options;
}

// Initialize variables
$success_message = '';
$error_message = '';

// Handle AJAX requests
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Get job details for modal
    if ($action === 'get_job_details') {
        $job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
        
        if ($job_id > 0) {
            $sql = "SELECT * FROM job_listings WHERE job_id = $job_id AND posted_by_id = " . $_SESSION['user_id'];
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $job = $result->fetch_assoc();
                
                // Output job details in a modern card format
                echo '<div class="job-details">
                    <div class="job-details-header">
                        <h2>' . htmlspecialchars($job['title']) . '</h2>
                        <span class="status ' . strtolower($job['status']) . '">' . ucfirst(htmlspecialchars($job['status'])) . '</span>
                    </div>
                    
                    <div class="job-details-grid">
                        <div class="job-details-column">
                            <div class="job-detail-item">
                                <i class="fas fa-building"></i>
                                <span>' . htmlspecialchars($job['company']) . '</span>
                            </div>
                            
                            <div class="job-detail-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>' . htmlspecialchars($job['location']) . '</span>
                            </div>
                            
                            <div class="job-detail-item">
                                <i class="fas fa-clock"></i>
                                <span>Posted on ' . date('F j, Y', strtotime($job['posted_at'])) . '</span>
                            </div>
                            
                            <div class="job-detail-item">
                                <i class="fas fa-envelope"></i>
                                <span>' . htmlspecialchars($job['contact_email']) . '</span>
                            </div>
                        </div>
                        
                        <div class="job-details-column">
                            <div class="job-detail-item">
                                <i class="fas fa-briefcase"></i>
                                <span>' . htmlspecialchars($job['employment_type']) . '</span>
                            </div>
                            
                            <div class="job-detail-item">
                                <i class="fas fa-briefcase"></i>
                                <span>' . htmlspecialchars($job['job_type']) . '</span>
                            </div>
                            
                            <div class="job-detail-item">
                                <i class="fas fa-dollar-sign"></i>
                                <span>' . htmlspecialchars($job['salary_range']) . '</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="job-details-section">
                        <h3>Job Description</h3>
                        <div class="job-details-content">' . nl2br(htmlspecialchars($job['description'])) . '</div>
                    </div>
                    
                    <div class="job-details-section">
                        <h3>Requirements</h3>
                        <div class="job-details-content">' . nl2br(htmlspecialchars($job['requirements'])) . '</div>
                    </div>
                </div>';
            } else {
                echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Job not found!</div>";
            }
        } else {
            echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Invalid job ID!</div>";
        }
        
        exit;
    }
    
    // Get job edit form for modal
    else if ($action === 'get_job_form') {
        $job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
        
        if ($job_id > 0) {
            $sql = "SELECT * FROM job_listings WHERE job_id = $job_id AND posted_by_id = " . $_SESSION['user_id'];
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $job = $result->fetch_assoc();
                
                // Update the job edit form HTML
                echo '<form id="editJobForm" method="POST">
                    <input type="hidden" name="action" value="update_job">
                    <input type="hidden" name="job_id" value="' . $job_id . '">
                    <input type="hidden" name="posted_by_type" value="user">
                    
                    <div class="form-grid">
                        <div class="form-column">
                            <div class="form-group">
                                <label for="title">Job Title *</label>
                                <input type="text" id="title" name="title" value="' . htmlspecialchars($job['title']) . '" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="company">Company Name *</label>
                                <input type="text" id="company" name="company" value="' . htmlspecialchars($job['company']) . '" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="location">Location</label>
                                <input type="text" id="location" name="location" value="' . htmlspecialchars($job['location']) . '" placeholder="e.g. Manila, Philippines">
                            </div>
                            
                            <div class="form-group">
                                <label for="salary_range">Salary Range</label>
                                <input type="text" id="salary_range" name="salary_range" value="' . htmlspecialchars($job['salary_range']) . '" placeholder="e.g. ₱25,000 - ₱35,000">
                            </div>
                        </div>
                        
                        <div class="form-column">
                            <div class="form-group">
                                <label for="employment_type">Employment Type *</label>
                                <select id="employment_type" name="employment_type" required>
                                    ' . generateEmploymentTypeOptions($job['employment_type']) . '
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="job_type">Job Type *</label>
                                <select id="job_type" name="job_type" required>
                                    ' . generateJobTypeOptions($job['job_type']) . '
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_email">Contact Email *</label>
                                <input type="email" id="contact_email" name="contact_email" value="' . htmlspecialchars($job['contact_email']) . '" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Job Description *</label>
                        <textarea id="description" name="description" rows="5" required>' . htmlspecialchars($job['description']) . '</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="requirements">Requirements *</label>
                        <textarea id="requirements" name="requirements" rows="5" required>' . htmlspecialchars($job['requirements']) . '</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="active" ' . ($job['status'] == 'active' ? 'selected' : '') . '>Active</option>
                            <option value="draft" ' . ($job['status'] == 'draft' ? 'selected' : '') . '>Draft</option>
                            <option value="closed" ' . ($job['status'] == 'closed' ? 'selected' : '') . '>Closed</option>
                        </select>
                    </div>
                    
                    <div class="form-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal(\'editJobModal\')">Cancel</button>
                        <button type="submit" class="btn">Update Job</button>
                    </div>
                </form>';
            } else {
                echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Job not found!</div>";
            }
        } else {
            echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Invalid job ID!</div>";
        }
        
        exit;
    }
    
    // Get applicants for modal
    else if ($action === 'get_applicants') {
        $job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
        
        if ($job_id > 0) {
            // Get job details first
            $sql = "SELECT j.*, u.email as posted_by_email 
                    FROM job_listings j 
                    LEFT JOIN users u ON j.posted_by_id = u.user_id 
                    WHERE j.job_id = $job_id AND j.posted_by_id = " . $_SESSION['user_id'];
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $job = $result->fetch_assoc();
                
                // Get applicants from job_applications table
                $sql = "SELECT ja.*, ja.name as applicant_name 
                        FROM job_applications ja 
                        WHERE ja.job_id = $job_id 
                        ORDER BY ja.applied_at DESC";
                $result = $conn->query($sql);
                
                // Output applicants HTML
                echo '<div class="applicants-header">
                    <h3>' . htmlspecialchars($job['title']) . ' - ' . htmlspecialchars($job['company']) . '</h3>
                </div>';
                
                if ($result && $result->num_rows > 0) {
                    echo '<p class="applicants-count">Total Applicants: ' . $result->num_rows . '</p>';
                    echo '<div class="table-responsive">
                        <table class="applicants-table">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Contact</th>
                                    <th>Applied On</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>';
                    
                    while ($applicant = $result->fetch_assoc()) {
                        $avatar = !empty($applicant['profile_picture']) 
                            ? '<img src="' . htmlspecialchars($applicant['profile_picture']) . '" alt="Profile" class="applicant-avatar">'
                            : '<div class="applicant-avatar">' . strtoupper(substr($applicant['applicant_name'], 0, 1)) . '</div>';
                            
                        echo '<tr>
                            <td>
                                <div class="applicant-name">
                                    ' . $avatar . '
                                    <span>' . htmlspecialchars($applicant['applicant_name']) . '</span>
                                </div>
                            </td>
                            <td>
                                <div class="applicant-contact">
                                    <div><i class="fas fa-envelope"></i> ' . htmlspecialchars($applicant['email']) . '</div>
                                </div>
                            </td>
                            <td>' . date('M d, Y', strtotime($applicant['applied_at'])) . '</td>
                            <td><span class="status ' . strtolower($applicant['status']) . '">' 
                                . ucfirst(htmlspecialchars($applicant['status'])) . '</span></td>
                            <td class="actions">
                                <button class="btn btn-sm" onclick="viewApplicantDetails(' . $applicant['application_id'] . ')">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>';
                    }
                    
                    echo '</tbody>
                        </table>
                    </div>';
                } else {
                    echo '<div class="no-applicants">
                        <i class="fas fa-user-slash"></i>
                        <h3>No Applicants Yet</h3>
                        <p>There are no applicants for this job listing yet.</p>
                    </div>';
                }
            } else {
                echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Job not found!</div>";
            }
        } else {
            echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Invalid job ID!</div>";
        }
        
        exit;
    }
    
    // Get applicant details for modal
    else if ($action === 'get_applicant_details') {
        $application_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($application_id > 0) {
            $sql = "SELECT a.*, j.title, j.posted_by_id 
                    FROM job_applications a 
                    JOIN job_listings j ON a.job_id = j.job_id 
                    WHERE a.application_id = $application_id 
                    AND j.posted_by_id = " . $_SESSION['user_id'];
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $applicant = $result->fetch_assoc();
                
                // Output applicant details HTML
                echo '<div class="applicant-details">
                    <div class="applicant-header">
                        <div class="applicant-info">
                            <div class="applicant-avatar">
                                ' . strtoupper(substr($applicant['name'], 0, 1)) . '
                            </div>
                            <div>
                                <h3>' . htmlspecialchars($applicant['name']) . '</h3>
                                <p>' . htmlspecialchars($applicant['email']) . '</p>
                                <p>' . htmlspecialchars($applicant['phone']) . '</p>
                            </div>
                        </div>
                        <div class="applicant-status">
                            <span class="status ' . strtolower($applicant['status']) . '">
                                ' . ucfirst(htmlspecialchars($applicant['status'])) . '
                            </span>
                        </div>
                    </div>
                    
                    <div class="applicant-grid">
                        <div class="applicant-section">
                            <h4>Applied For</h4>
                            <p>' . htmlspecialchars($applicant['title']) . '</p>
                        </div>
                        
                        <div class="applicant-section">
                            <h4>Applied On</h4>
                            <p>' . date('F j, Y, g:i a', strtotime($applicant['applied_at'])) . '</p>
                        </div>
                    </div>
                    
                    <div class="applicant-section">
                        <h4>Cover Letter</h4>
                        <div class="cover-letter">
                            ' . nl2br(htmlspecialchars($applicant['cover_letter'])) . '
                        </div>
                    </div>
                    
                    <div class="applicant-section">
                        <h4>Resume</h4>
                        <a href="' . htmlspecialchars($applicant['resume_path']) . '" class="btn btn-sm" target="_blank">
                            <i class="fas fa-download"></i> View Resume
                        </a>
                    </div>
                    
                    ' . (!empty($applicant['notes']) ? '
                    <div class="applicant-section">
                        <h4>Notes</h4>
                        <div class="notes">
                            ' . nl2br(htmlspecialchars($applicant['notes'])) . '
                        </div>
                    </div>' : '') . '
                    
                    <div class="applicant-footer">
                        <button class="btn btn-secondary" onclick="closeApplicantDetails()">Back</button>
                        <button class="btn" onclick="updateApplicantStatus(' . $applicant['application_id'] . ')">Update Status</button>
                    </div>
                </div>';
            } else {
                echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Applicant not found!</div>";
            }
        } else {
            echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Invalid applicant ID!</div>";
        }
        
        exit;
    }
    
    // Get applicant status update form
    else if ($action === 'get_status_form') {
        $application_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($application_id > 0) {
            $sql = "SELECT a.*, j.posted_by_id 
                    FROM job_applications a 
                    JOIN job_listings j ON a.job_id = j.job_id 
                    WHERE a.application_id = $application_id 
                    AND j.posted_by_id = " . $_SESSION['user_id'];
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $applicant = $result->fetch_assoc();
                
                // Output status update form HTML
                echo '<form id="updateStatusForm" method="POST">
                    <input type="hidden" name="action" value="update_applicant_status">
                    <input type="hidden" name="application_id" value="' . $application_id . '">
                    
                    <div class="form-group">
                        <label for="applicant_status">Status</label>
                        <select id="applicant_status" name="status">
                            <option value="New" ' . ($applicant['status'] == 'New' ? 'selected' : '') . '>New</option>
                            <option value="Reviewing" ' . ($applicant['status'] == 'Reviewing' ? 'selected' : '') . '>Reviewing</option>
                            <option value="Interviewed" ' . ($applicant['status'] == 'Interviewed' ? 'selected' : '') . '>Interviewed</option>
                            <option value="Shortlisted" ' . ($applicant['status'] == 'Shortlisted' ? 'selected' : '') . '>Shortlisted</option>
                            <option value="Rejected" ' . ($applicant['status'] == 'Rejected' ? 'selected' : '') . '>Rejected</option>
                            <option value="Hired" ' . ($applicant['status'] == 'Hired' ? 'selected' : '') . '>Hired</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="4">' . htmlspecialchars($applicant['notes'] ?? '') . '</textarea>
                    </div>
                    
                    <div class="form-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">Cancel</button>
                        <button type="submit" class="btn">Update Status</button>
                    </div>
                </form>';
            } else {
                echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Applicant not found!</div>";
            }
        } else {
            echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Invalid applicant ID!</div>";
        }
        
        exit;
    }
    
    // Get archived jobs for modal
    else if ($action === 'get_archived_jobs') {
        $sql = "SELECT j.*, 
                (SELECT COUNT(*) FROM job_applications a WHERE a.job_id = j.job_id) as applicants_count 
                FROM job_listings j 
                WHERE j.posted_by_id = " . $_SESSION['user_id'] . " 
                AND j.status = 'closed'
                ORDER BY j.updated_at DESC";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            echo '
            <div style="padding-bottom:1.5rem;">
                <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem;">
                    <i class="fas fa-archive" style="color:#4f46e5;font-size:1.5rem;"></i>
                    <h3 style="font-size:1.25rem;font-weight:700;color:#1f2937;margin:0;">Archived Jobs</h3>
                </div>
                <p style="color:#6b7280;margin-bottom:1.5rem;">These are jobs you have archived (closed). You can restore them anytime.</p>
            </div>
            <div class="table-responsive" style="margin-bottom:0;">
                <table class="archived-jobs-list" style="background:#f8fafc;">
                    <thead>
                        <tr style="background:#f3f4f6;">
                            <th style="padding:1rem 1.25rem;font-size:0.95rem;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb;">Job</th>
                            <th style="padding:1rem 1.25rem;font-size:0.95rem;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb;">Archived Info</th>
                            <th style="padding:1rem 1.25rem;font-size:0.95rem;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>';
            while ($job = $result->fetch_assoc()) {
                echo '<tr style="transition:background 0.2s;">
                    <td style="width: 40%;padding:1.25rem 1rem;vertical-align:top;">
                        <div class="archived-job-title" style="font-weight:600;font-size:1.05rem;color:#1f2937;margin-bottom:0.25rem;">' . htmlspecialchars($job['title']) . '</div>
                        <div class="archived-job-company" style="display:flex;align-items:center;gap:0.5rem;color:#6b7280;font-size:0.95rem;">
                            <i class="fas fa-building" style="color:#4f46e5;"></i>
                            <span>' . htmlspecialchars($job['company']) . '</span>
                        </div>
                    </td>
                    <td style="width: 35%;padding:1.25rem 1rem;vertical-align:top;">
                        <div class="archived-job-meta" style="margin-bottom:0.5rem;color:#6b7280;">
                            <i class="fas fa-archive" style="color:#4f46e5;"></i> Archived on <span style="font-weight:500;color:#374151;">' . date('M d, Y', strtotime($job['updated_at'])) . '</span>
                        </div>
                        <div class="archived-job-meta" style="color:#6b7280;">
                            <i class="fas fa-users" style="color:#4f46e5;"></i> <span style="font-weight:500;color:#374151;">' . $job['applicants_count'] . '</span> ' . 
                            ($job['applicants_count'] == 1 ? 'Applicant' : 'Applicants') . '
                        </div>
                    </td>
                    <td style="padding:1.25rem 1rem;vertical-align:top;">
                        <div class="actions-cell" style="display:flex;gap:0.5rem;">
                            <button class="btn btn-sm btn-outline" style="min-width:90px;" onclick="viewJob(' . $job['job_id'] . ')">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn btn-sm btn-restore-soft restore-job-btn" style="min-width:90px;" data-job-id="' . $job['job_id'] . '">
                                <i class="fas fa-undo"></i> Restore
                            </button>
                            <button class="btn btn-sm btn-danger-soft delete-job-btn" style="min-width:90px;" data-job-id="' . $job['job_id'] . '">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </td>
                </tr>';
            }
            echo '</tbody>
                </table>
            </div>';
        } else {
            echo '<div class="empty-state" style="margin:2rem 0;">
                <div class="empty-state-icon">
                    <i class="fas fa-archive"></i>
                </div>
                <h2 class="empty-state-title">No Archived Jobs</h2>
                <p class="empty-state-description">You don\'t have any archived jobs yet. Archived jobs will appear here after you close them.</p>
            </div>';
        }
        exit;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'update_job') {
        $job_id = intval($_POST['job_id']);
        $title = $conn->real_escape_string($_POST['title']);
        $company = $conn->real_escape_string($_POST['company']);
        $location = $conn->real_escape_string($_POST['location']);
        $employment_type = $conn->real_escape_string($_POST['employment_type']);
        $job_type = $conn->real_escape_string($_POST['job_type']);
        $salary_range = $conn->real_escape_string($_POST['salary_range']);
        $description = $conn->real_escape_string($_POST['description']);
        $requirements = $conn->real_escape_string($_POST['requirements']);
        $contact_email = $conn->real_escape_string($_POST['contact_email']);
        $status = $conn->real_escape_string($_POST['status']);
        
        $sql = "UPDATE job_listings SET 
                title = '$title',
                company = '$company',
                location = '$location',
                employment_type = '$employment_type',
                job_type = '$job_type',
                salary_range = '$salary_range',
                description = '$description',
                requirements = '$requirements',
                contact_email = '$contact_email',
                status = '$status',
                updated_at = NOW()
                WHERE job_id = $job_id AND posted_by_id = " . $_SESSION['user_id'];
        
        if ($conn->query($sql)) {
            echo json_encode([
                'success' => true,
                'message' => 'Job updated successfully!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error updating job: ' . $conn->error
            ]);
        }
        exit;
    }
    
    // Update applicant status
    else if ($_POST['action'] === 'update_applicant_status') {
        $application_id = intval($_POST['application_id']);
        $status = $conn->real_escape_string($_POST['status']);
        $notes = $conn->real_escape_string($_POST['notes']);
        
        // Verify the applicant belongs to a job posted by the current user
        $check_sql = "SELECT j.job_id 
                      FROM job_applications a 
                      JOIN job_listings j ON a.job_id = j.job_id 
                      WHERE a.application_id = $application_id 
                      AND j.posted_by_id = " . $_SESSION['user_id'];
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            // Update applicant status in database
            $sql = "UPDATE job_applications SET 
                    status = '$status',
                    notes = '$notes',
                    updated_at = NOW()
                    WHERE application_id = $application_id";
            
            $response = [];
            
            if ($conn->query($sql) === TRUE) {
                $response['success'] = true;
                $response['message'] = "Applicant status updated successfully!";
            } else {
                $response['success'] = false;
                $response['message'] = "Error updating applicant status: " . $conn->error;
            }
        } else {
            $response['success'] = false;
            $response['message'] = "You don't have permission to update this applicant.";
        }
        
        // Return JSON response
        echo json_encode($response);
        exit;
    }
    
    // Add new job
    else if ($_POST['action'] === 'add_job') {
        $title = $conn->real_escape_string($_POST['title']);
        $company = $conn->real_escape_string($_POST['company']);
        $location = $conn->real_escape_string($_POST['location']);
        $employment_type = $conn->real_escape_string($_POST['employment_type']);
        $job_type = $conn->real_escape_string($_POST['job_type']);
        $salary_range = $conn->real_escape_string($_POST['salary_range']);
        $description = $conn->real_escape_string($_POST['description']);
        $requirements = $conn->real_escape_string($_POST['requirements']);
        $contact_email = $conn->real_escape_string($_POST['contact_email']);
        // Always set status to "open" for new jobs
        $status = 'open';

        // Insert new job into database
        $sql = "INSERT INTO job_listings (
                title, 
                company, 
                location, 
                employment_type, 
                job_type, 
                salary_range, 
                description, 
                requirements, 
                contact_email, 
                status, 
                posted_by_id,
                posted_by_type,
                posted_at, 
                created_at
            ) VALUES (
                '$title',
                '$company',
                '$location',
                '$employment_type',
                '$job_type',
                '$salary_range',
                '$description',
                '$requirements',
                '$contact_email',
                '$status',
                " . $_SESSION['user_id'] . ",
                '$user_role',
                NOW(),
                NOW()
            )";
        
        $response = [];
        
        if ($conn->query($sql) === TRUE) {
            $response['success'] = true;
            $response['message'] = "Job added successfully!";

            // --- Email notification to all users except poster ---
            $new_job_id = $conn->insert_id;
            $jobQuery = "SELECT * FROM job_listings WHERE job_id = $new_job_id";
            $jobResult = $conn->query($jobQuery);
            $job = $jobResult ? $jobResult->fetch_assoc() : null;

            if ($job) {
                $usersQuery = "SELECT email, first_name FROM users WHERE user_id != " . $_SESSION['user_id'];
                $usersResult = $conn->query($usersQuery);

                if ($usersResult) {
                    require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
                    require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
                    require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';
                    foreach ($usersResult as $user) {
                        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                        try {
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'nesrac22@gmail.com';
                            $mail->Password = 'cegq qqrk jjdw xwbs';
                            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port = 587;

                            $mail->setFrom('nesrac22@gmail.com', 'Alumni MS');
                            $mail->addAddress($user['email'], $user['first_name']);

                            $mail->isHTML(true);
                            $mail->Subject = 'New Job Opportunity: ' . $job['title'];
                            $mail->Body = "
                                <h3>New Job Posted: {$job['title']}</h3>
                                <p><strong>Company:</strong> {$job['company']}</p>
                                <p><strong>Location:</strong> {$job['location']}</p>
                                <p><strong>Description:</strong> {$job['description']}</p>
                                <p><a href='https://yourdomain.com/alumni/job_info.php?id={$job['job_id']}'>View Job Details</a></p>
                            ";
                            $mail->send();
                        } catch (Exception $e) {
                            // Optionally log error: $mail->ErrorInfo
                        }
                    }
                }
            }
            // --- end email notification ---
        } else {
            $response['success'] = false;
            $response['message'] = "Error adding job: " . $conn->error;
        }
        
        // Return JSON response
        echo json_encode($response);
        exit;
    }
    
    // Quick status update for job cards
    else if ($_POST['action'] === 'update_job_status') {
        $job_id = intval($_POST['job_id']);
        $status = $conn->real_escape_string($_POST['status']);
        $sql = "UPDATE job_listings SET status = '$status', updated_at = NOW() WHERE job_id = $job_id AND posted_by_id = " . $_SESSION['user_id'];
        if ($conn->query($sql)) {
            echo json_encode([
                'success' => true,
                'message' => 'Job status updated successfully!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error updating job status: ' . $conn->error
            ]);
        }
        exit;
    }
    // --- Add this block for deleting a job ---
    else if ($_POST['action'] === 'delete_job') {
        $job_id = intval($_POST['job_id']);
        // Only allow deletion if the job belongs to the current user and is closed
        $check_sql = "SELECT job_id FROM job_listings WHERE job_id = $job_id AND posted_by_id = " . $_SESSION['user_id'] . " AND status = 'closed'";
        $check_result = $conn->query($check_sql);
        if ($check_result && $check_result->num_rows > 0) {
            // Optionally, delete related applications first
            $conn->query("DELETE FROM job_applications WHERE job_id = $job_id");
            $sql = "DELETE FROM job_listings WHERE job_id = $job_id";
            if ($conn->query($sql)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Job deleted successfully!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error deleting job: ' . $conn->error
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => "You don't have permission to delete this job."
            ]);
        }
        exit;
    }
}

// Get all jobs for the current user
$all_jobs = [];
$sql = "SELECT j.*, 
        (SELECT COUNT(*) FROM job_applications a WHERE a.job_id = j.job_id) as applicants_count 
        FROM job_listings j 
        WHERE j.posted_by_id = " . $_SESSION['user_id'] . " 
        ORDER BY j.created_at DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $all_jobs[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Posted Jobs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Modern CSS Styles */
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --secondary-color: #6b7280;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --light-bg: #f9fafb;
            --dark-bg: #1f2937;
            --border-color: #e5e7eb;
            --text-color: #374151;
            --text-muted: #6b7280;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--light-bg);
            color: var(--text-color);
            line-height: 1.6;
            font-size: 16px;
        }
        
        /* Prevent background shift when modal is open */
        body.modal-open {
            overflow: hidden !important;
            /* Adjust this value if your scrollbar is wider/narrower */
            padding-right: 15px !important;
        }
        @media (max-width: 768px) {
            body.modal-open {
                padding-right: 0 !important;
            }
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .main-content {
            background-color: white;
            border-radius: 1rem;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background-color: #f8fafc;
        }
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-bg);
        }
        
        .header-title i {
            color: var(--primary-color);
            font-size: 1.5rem;
        }
        
        .header-actions {
            display: flex;
            gap: 1rem;
        }
        
        .search-filter {
            display: flex;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            flex-wrap: wrap;
            background-color: white;
            position: relative;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .filter-button {
            background: none;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 0.75rem;
            color: var(--text-color);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .filter-button:hover {
            background-color: #f8fafc;
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .filter-dropdown {
            position: absolute;
            top: 100%;
            right: 1.5rem;
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            box-shadow: var(--card-shadow);
            min-width: 200px;
            z-index: 100;
            display: none;
            margin-top: 0.5rem;
        }

        .filter-dropdown.active {
            display: block;
        }

        .filter-dropdown-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-dropdown-item:hover {
            background-color: #f8fafc;
            color: var(--primary-color);
        }

        .filter-dropdown-item.active {
            background-color: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
        }

        .filter-dropdown-item i {
            width: 1rem;
            text-align: center;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 0.95rem;
            transition: var(--transition);
            background-color: #f8fafc;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        
        .content {
            padding: 1.5rem;
        }
        
        .job-cards {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }
        
        .job-card {
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            overflow: hidden;
            transition: var(--transition);
            background-color: white;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .job-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .job-card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background-color: rgba(79, 70, 229, 0.03);
        }
        
        .job-card-content {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .job-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--dark-bg);
            margin-bottom: 0.5rem;
            line-height: 1.4;
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 100%;
        }
        
        .job-company {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            font-size: 0.95rem;
        }
        
        .job-company i {
            color: var(--primary-color);
            font-size: 1rem;
        }
        
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin: 1.25rem 0;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .job-meta-item i {
            color: var(--primary-color);
            font-size: 0.9rem;
        }
        
        .job-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 500;
            white-space: nowrap;
            background-color: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
        }
        
        .job-applicants {
            margin-top: auto;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        .job-applicants i {
            color: var(--primary-color);
        }
        
        .job-actions {
            margin-top: 1.25rem;
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        .job-actions .btn {
            flex: 1;
            min-width: fit-content;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
        }
        
        .btn-secondary:hover {
            background-color: #4b5563;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-color);
        }
        
        .btn-outline:hover {
            background-color: var(--light-bg);
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-danger-soft {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border: 1px solid transparent;
            padding: 0.4rem 1rem;
            transition: all 0.2s ease;
        }

        .btn-danger-soft:hover {
            background-color: var(--danger-color);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);
        }

        .btn-danger-soft:active {
            transform: translateY(0);
        }

        .btn-danger-soft i {
            font-size: 0.9rem;
        }
        
        .btn-restore-soft {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid transparent;
            padding: 0.4rem 1rem;
            transition: all 0.2s ease;
        }

        .btn-restore-soft:hover {
            background-color: var(--success-color);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
        }

        .btn-restore-soft:active {
            transform: translateY(0);
        }

        .btn-restore-soft i {
            font-size: 0.9rem;
        }
        
        .status {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .status.active {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }
        
        .status.draft {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }
        
        .status.closed {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }
        
        .status.archived {
            background-color: rgba(107, 114, 128, 0.1);
            color: var(--secondary-color);
        }
        
        .status.new {
            background-color: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
        }
        
        .status.reviewing {
            background-color: rgba(124, 58, 237, 0.1);
            color: #7c3aed;
        }
        
        .status.interviewed {
            background-color: rgba(79, 70, 229, 0.1);
            color: #4f46e5;
        }
        
        .status.shortlisted {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }
        
        .status.rejected {
            background-color: rgba(239, 68, 68, 0.1);
            color: var,--danger-color);
        }
        
        .status.hired {
            background-color: rgba(16, 185, 129, 0.2);
            color: var(--success-color);
            font-weight: 600;
        }
        
        .no-jobs {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 2rem;
            text-align: center;
            background-color: white;
            border-radius: 0.75rem;
        }
        
        .no-jobs i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }
        
        .no-jobs h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-bg);
            margin-bottom: 0.75rem;
        }
        
        .no-jobs p {
            color: var(--text-muted);
            max-width: 500px;
            margin-bottom: 1.5rem;
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
            backdrop-filter: blur(4px);
        }
        
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        #viewJobModal.modal-overlay.active {
            z-index: 1100; /* Higher than regular modals */
        }
        
        #archivedJobsModal.modal-overlay.active {
            z-index: 1000; /* Base modal z-index */
        }
        
        .modal {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
            position: relative;
        }
        
        .modal-overlay.active .modal {
            transform: translateY(0);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            background-color: white;
            z-index: 10;
            border-radius: 12px 12px 0 0;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-bg);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-title i {
            color: var(--primary-color);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 26px; /* Slightly larger for better visibility */
            cursor: pointer;
            color: var(--text-muted);
            transition: all 0.2s ease;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            padding: 0;
            margin: -8px;
            line-height: 0; /* Reset line height */
            text-align: center; /* Ensure text centering */
            font-family: Arial, sans-serif; /* Use Arial for better × symbol alignment */
        }
        
        .modal-close:hover {
            color: var(--danger-color);
            background-color: rgba(239, 68, 68, 0.1);
            transform: scale(1.1);
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            position: sticky;
            bottom: 0;
            background-color: white;
            border-radius: 0 0 12px 12px;
        }
        
        /* Form Styles */
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
            color: var(--dark-bg);
        }
        
        input, select, textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 0.95rem;
            transition: var(--transition);
            background-color: #f8fafc;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
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
        
        /* Job Details Styles */
        .job-details {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .job-details-header {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        @media (min-width: 640px) {
            .job-details-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: flex-start;
            }
        }
        
        .job-details-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-bg);
        }
        
        .job-details-grid {
            display: grid;
            gap: 1.5rem;
            background-color: #f8fafc;
            padding: 1.5rem;
            border-radius: 0.75rem;
        }
        
        @media (min-width: 768px) {
            .job-details-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        .job-detail-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        
        .job-detail-item i {
            color: var(--primary-color);
            width: 1rem;
            text-align: center;
        }
        
        .job-details-section {
            margin-top: 1rem;
        }
        
        .job-details-section h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-bg);
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .job-details-content {
            background-color: #f8fafc;
            padding: 1rem;
            border-radius: 0.5rem;
            line-height: 1.7;
            white-space: pre-line;
        }
        
        /* Applicants Styles */
        .applicants-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .applicants-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-bg);
            margin-bottom: 0.5rem;
        }
        
        .applicants-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }
        
        .applicants-table th,
        .applicants-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .applicants-table th {
            background-color: #f8fafc;
            font-weight: 600;
            color: var(--dark-bg);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        
        .applicants-table tr:last-child td {
            border-bottom: none;
        }
        
        .applicants-table tr:hover {
            background-color: rgba(79, 70, 229, 0.05);
        }
        
        .applicant-name {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .applicant-avatar {
            width: 3rem;
            height: 2rem;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .no-applicants {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
            text-align: center;
            background-color: #f8fafc;
            border-radius: 0.75rem;
        }
        
        .no-applicants i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .no-applicants h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-bg);
            margin-bottom: 0.5rem;
        }
        
        .no-applicants p {
            color: var(--text-muted);
            max-width: 400px;
        }
        
        /* Applicant Details Styles */
        .applicant-details {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .applicant-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .applicant-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .applicant-info .applicant-avatar {
            width: 3.5rem;
            height: 3.5rem;
            font-size: 1.5rem;
        }
        
        .applicant-info h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-bg);
            margin-bottom: 0.25rem;
        }
        
        .applicant-info p {
            color: var(--text-muted);
            margin: 0;
        }
        
        .applicant-grid {
            display: grid;
            gap: 1.5rem;
            background-color: #f8fafc;
            padding: 1.5rem;
            border-radius: 0.75rem;
        }
        
        @media (min-width: 768px) {
            .applicant-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        .applicant-section {
            margin-bottom: 1rem;
        }
        
        .applicant-section h4 {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .cover-letter, .notes {
            background-color: #f8fafc;
            padding: 1rem;
            border-radius: 0.5rem;
            line-height: 1.7;
            white-space: pre-line;
            border: 1px solid var(--border-color);
        }
        
        .applicant-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
        
        /* Alert Styles */
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .search-filter {
                flex-direction: column;
            }
            
            .job-actions {
                justify-content: flex-start;
                margin-top: 1rem;
            }
            
            .job-cards {
                grid-template-columns: 1fr;
            }
        }
        
        /* Fix for dynamically created modals */
        #applicantDetailsModal, #updateStatusModal {
            z-index: 10001; /* Higher than other modals */
        }
        
        /* Table responsive fix */
        .table-responsive {
            overflow-x: auto;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
            box-shadow: var(--card-shadow);
        }
        
        /* Archived Jobs Styles */
        .archived-jobs-list {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 1rem 0;
        }

        /* Empty State Styles */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 2rem;
            text-align: center;
            background-color: #f8fafc;
            border-radius: 1rem;
            border: 2px dashed #e5e7eb;
        }
        
        .empty-state-icon {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(79, 70, 229, 0.1);
            border-radius: 50%;
            margin-bottom: 1.5rem;
        }
        
        .empty-state-icon i {
            font-size: 2.5rem;
            color: var(--primary-color);
        }
        
        .empty-state-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-bg);
            margin-bottom: 0.75rem;
        }
        
        .empty-state-description {
            color: var(--text-muted);
            max-width: 400px;
            margin-bottom: 2rem;
            font-size: 1.1rem;
            line-height: 1.6;
        }
        
        .empty-state-action {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        /* Enhanced Mobile Responsiveness */
        @media (max-width: 576px) {
            .container {
                padding: 1rem 0.5rem;
            }
            
            .main-content {
                border-radius: 0.5rem;
            }
            
            .header {
                padding: 1rem;
            }
            
            .header-title h1 {
                font-size: 1.25rem;
            }
            
            .job-cards {
                gap: 1rem;
            }
            
            .job-card {
                border-radius: 0.5rem;
            }
            
            .job-card-header {
                padding: 1rem;
            }
            
            .job-card-content {
                padding: 1rem;
            }
            
            .job-title {
                font-size: 1rem;
            }
            
            .job-meta {
                flex-direction: column;
                gap: 0.5rem;
                margin: 0.75rem 0;
            }
            
            .job-badges {
                margin-bottom: 0.75rem;
            }
            
            .job-actions {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .job-actions .btn {
                width: 100%;
            }
            
            .modal {
                width: 95%;
                max-height: 85vh;
                border-radius: 0.75rem;
            }
            
            .modal-header {
                padding: 1rem;
            }
            
            .modal-body {
                padding: 1rem;
            }
            
            .modal-footer {
                padding: 1rem;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .modal-footer .btn {
                width: 100%;
            }
            
            .form-footer {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .form-footer .btn {
                width: 100%;
            }
            
            .applicants-table th,
            .applicants-table td {
                padding: 0.5rem;
                font-size: 0.85rem;
            }
            
            .applicant-avatar {
                width: 2rem;
                height: 2rem;
                font-size: 0.85rem;
            }
            
            /* Improve touch targets for better mobile experience */
            .btn, 
            .filter-button,
            .modal-close,
            input, 
            select, 
            textarea {
                min-height: 44px; /* Minimum touch target size */
            }
            
            .btn-sm {
                min-height: 38px;
            }
            
            /* Fix for modals on mobile */
            .modal-overlay.active .modal {
                margin-top: 1rem;
                margin-bottom: 1rem;
            }
            
            /* Improve form layout on mobile */
            .form-group {
                margin-bottom: 1rem;
            }
            
            /* Fix table overflow on mobile */
            .table-responsive {
                margin: 0 -1rem;
                width: calc(100% + 2rem);
                border-radius: 0;
            }
            
            /* Adjust applicant details for mobile */
            .applicant-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .applicant-status {
                align-self: flex-start;
            }
            
            /* Fix for filter dropdown on mobile */
            .filter-dropdown {
                right: 0;
                width: 100%;
                max-width: 250px;
            }
        }

        /* Fix for Samsung S21 FE specific issues */
        @media (max-width: 380px) {
            .header-actions {
                flex-direction: column;
                width: 100%;
                gap: 0.5rem;
            }
            
            .header-actions .btn {
                width: 100%;
            }
            
            .search-filter {
                padding: 0.75rem;
            }
            
            .search-box input {
                padding: 0.6rem 1rem 0.6rem 2.5rem;
            }
            
            /* Adjust font sizes for better readability */
            body {
                font-size: 14px;
            }
            
            .job-title {
                font-size: 0.95rem;
            }
            
            .job-company,
            .job-meta-item {
                font-size: 0.85rem;
            }
            
            /* Improve touch feedback */
            .btn:active,
            .filter-button:active,
            .job-card:active {
                transform: scale(0.98);
            }
        }

        /* Fix for modal scrolling on mobile */
        .modal {
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }

        /* Improve form inputs on mobile */
        @media (max-width: 768px) {
            input, select, textarea {
                font-size: 16px !important; /* Prevents iOS zoom on focus */
            }
            
            /* Adjust modal for keyboard appearance */
            .modal-overlay.active .modal {
                max-height: 80vh;
            }
        }
    </style>
</head>
<body>
    <?php include 'user_navbar.php'; ?>
    
    <div class="container">
        <div class="main-content">
            <div class="header">
                <div class="header-title">
                    <i class="fas fa-briefcase"></i>
                    <h1>My Posted Jobs</h1>
                </div>
                <div class="header-actions" style="display: flex; gap: 1rem;">
                    <button class="btn btn-outline" onclick="viewArchivedJobs()">
                        <i class="fas fa-archive"></i> View Archives
                    </button>
                    <button class="btn" id="addNewJobBtn">
                        <i class="fas fa-plus"></i> Post New Job
                    </button>
                </div>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="search-filter">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search jobs..." onkeyup="filterJobs()">
                </div>
                <button class="filter-button" onclick="toggleFilterDropdown()">
                    <i class="fas fa-filter"></i>
                </button>
                <div class="filter-dropdown" id="filterDropdown">
                    <div class="filter-dropdown-item active" onclick="sortJobs('newest')">
                        <i class="fas fa-clock"></i> Newest First
                    </div>
                    <div class="filter-dropdown-item" onclick="sortJobs('oldest')">
                        <i class="fas fa-history"></i> Oldest First
                    </div>
                </div>
            </div>
            
            <div class="content">
                <?php
                // Count jobs that are not closed
                $visible_jobs = 0;
                foreach ($all_jobs as $job) {
                    if ($job['status'] !== 'closed') {
                        $visible_jobs++;
                    }
                }
                ?>
                <?php if ($visible_jobs > 0): ?>
                    <div class="job-cards" id="jobsContainer">
                        <?php foreach ($all_jobs as $job): ?>
                            <?php if ($job['status'] !== 'closed'): // Skip jobs with status "closed" ?>
                                <div class="job-card" data-title="<?php echo strtolower(htmlspecialchars($job['title'])); ?>" data-company="<?php echo strtolower(htmlspecialchars($job['company'])); ?>" data-status="<?php echo strtolower($job['status']); ?>">
            <div class="job-card-header">
                <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                <div class="job-company">
                    <i class="fas fa-building"></i>
                    <span><?php echo htmlspecialchars($job['company']); ?></span>
                </div>
            </div>
            <div class="job-card-content">
                <div class="job-meta">
                    <div class="job-meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($job['location']); ?></span>
                    </div>
                    <div class="job-meta-item">
                        <i class="fas fa-clock"></i>
                        <span>Posted <?php echo date('M d, Y', strtotime($job['posted_at'])); ?></span>
                    </div>
                </div>
                <div class="job-badges">
                    <span class="badge"><?php echo htmlspecialchars($job['employment_type']); ?></span>
                    <span class="badge"><?php echo htmlspecialchars($job['job_type']); ?></span>
                    <span class="status <?php echo strtolower($job['status']); ?>"><?php echo ucfirst($job['status']); ?></span>
                    <span class="badge badge-outline"><?php echo htmlspecialchars($job['job_type']); ?></span>
                </div>
                <div class="job-applicants">
                    <i class="fas fa-users"></i>
                    <span>
                        <strong><?php echo $job['applicants_count']; ?></strong> 
                        <?php echo $job['applicants_count'] == 1 ? 'Applicant' : 'Applicants'; ?>
                    </span>
                </div>
                <div class="job-actions">
                    <button class="btn btn-sm btn-outline" onclick="viewJob(<?php echo $job['job_id']; ?>)">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="btn btn-sm btn-outline" onclick="editJob(<?php echo $job['job_id']; ?>)">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-sm" onclick="viewApplicants(<?php echo $job['job_id']; ?>)">
                        <i class="fas fa-users"></i> Applicants
                    </button>
                    <?php if ($job['status'] !== 'closed'): ?>
                        <button class="btn btn-sm btn-danger-soft archive-job-btn" data-job-id="<?php echo $job['job_id']; ?>">
                            <i class="fas fa-archive"></i> Archive
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($visible_jobs === 0): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h2 class="empty-state-title">No Jobs Found</h2>
                        <p class="empty-state-description">You haven't posted any jobs yet. Click the button below to post your first job listing and start connecting with potential candidates.</p>
                        <button class="btn empty-state-action" id="addFirstJobBtn">
                            <i class="fas fa-plus"></i> Post New Job
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- View Job Modal -->
    <div class="modal-overlay" id="viewJobModal">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title"><i class="fas fa-eye"></i> Job Details</h2>
                <button class="modal-close" onclick="closeModal('viewJobModal')">&times;</button>
            </div>
            <div class="modal-body" id="viewJobContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('viewJobModal')">Close</button>
            </div>
        </div>
    </div>
    
    <!-- Edit Job Modal -->
    <div class="modal-overlay" id="editJobModal">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title"><i class="fas fa-edit"></i> Edit Job</h2>
                <button class="modal-close" onclick="closeModal('editJobModal')">&times;</button>
            </div>
            <div class="modal-body" id="editJobContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
    
    <!-- View Applicants Modal -->
    <div class="modal-overlay" id="applicantsModal">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title"><i class="fas fa-users"></i> Job Applicants</h2>
                <button class="modal-close" onclick="closeModal('applicantsModal')">&times;</button>
            </div>
            <div class="modal-body" id="applicantsContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('applicantsModal')">Close</button>
            </div>
        </div>
    </div>
    
    <!-- Add New Job Modal -->
    <div class="modal-overlay" id="addJobModal">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title"><i class="fas fa-plus"></i> Post New Job</h2>
                <button class="modal-close" onclick="closeModal('addJobModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addJobForm" method="POST">
                    <input type="hidden" name="action" value="add_job">
                    
                    <div class="form-grid">
                        <div class="form-column">
                            <div class="form-group">
                                <label for="title">Job Title</label>
                                <input type="text" id="title" name="title" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="company">Company</label>
                                <input type="text" id="company" name="company" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="location">Location</label>
                                <input type="text" id="location" name="location" placeholder="e.g. New York, NY">
                            </div>
                            
                            <div class="form-group">
                                <label for="salary_range">Salary Range</label>
                                <input type="text" id="salary_range" name="salary_range" placeholder="e.g. $50,000 - $70,000">
                            </div>
                        </div>
                        
                        <div class="form-column">
                            <div class="form-group">
                                <label for="employment_type">Employment Type</label>
                                <select id="employment_type" name="employment_type">
                                    <option value="Full-time">Full-time</option>
                                    <option value="Part-time">Part-time</option>
                                    <option value="Contract">Contract</option>
                                    <option value="Freelance">Freelance</option>
                                    <option value="Internship">Internship</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="job_type">Job Type</label>
                                <select id="job_type" name="job_type">
                                    <option value="On-site">On-site</option>
                                    <option value="Remote">Remote</option>
                                    <option value="Hybrid">Hybrid</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_email">Contact Email</label>
                                <input type="email" id="contact_email" name="contact_email" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Job Description</label>
                        <textarea id="description" name="description" rows="5" required placeholder="Describe the job responsibilities and expectations..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="requirements">Requirements</label>
                        <textarea id="requirements" name="requirements" rows="5" required placeholder="List the skills, qualifications, and experience required..."></textarea>
                    </div>
                    
                    <div class="form-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addJobModal')">Cancel</button>
                        <button type="submit" class="btn">Post Job</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Archived Jobs Modal -->
    <div class="modal-overlay" id="archivedJobsModal">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title"><i class="fas fa-archive"></i> Archived Jobs</h2>
                <button class="modal-close" onclick="closeModal('archivedJobsModal')">&times;</button>
            </div>
            <div class="modal-body" id="archivedJobsContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('archivedJobsModal')">Close</button>
            </div>
        </div>
    </div>
    
    <script>
        // Add these new functions to your existing JavaScript
        function toggleFilterDropdown() {
            const dropdown = document.getElementById('filterDropdown');
            dropdown.classList.toggle('active');
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function closeDropdown(e) {
                if (!e.target.closest('.filter-button') && !e.target.closest('.filter-dropdown')) {
                    dropdown.classList.remove('active');
                    document.removeEventListener('click', closeDropdown);
                }
            });
        }

        function sortJobs(order) {
            const jobsContainer = document.getElementById('jobsContainer');
            const jobs = Array.from(jobsContainer.getElementsByClassName('job-card'));
            
            // Update active state in dropdown
            document.querySelectorAll('.filter-dropdown-item').forEach(item => {
                item.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            jobs.sort((a, b) => {
                const dateA = new Date(a.querySelector('.job-meta').textContent.match(/Posted (.+)/)[1]);
                const dateB = new Date(b.querySelector('.job-meta').textContent.match(/Posted (.+)/)[1]);
                
                return order === 'newest' ? dateB - dateA : dateA - dateB;
            });
            
            // Clear and re-append sorted jobs
            jobs.forEach(job => jobsContainer.appendChild(job));
            
            // Close dropdown
            document.getElementById('filterDropdown').classList.remove('active');
        }

        // Function to filter jobs
        function filterJobs() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const jobCards = document.querySelectorAll('.job-card');
            
            jobCards.forEach(card => {
                const title = card.getAttribute('data-title');
                const company = card.getAttribute('data-company');
                
                const matchesSearch = title.includes(searchInput) || company.includes(searchInput);
                
                if (matchesSearch) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show "no jobs" message if all cards are hidden
            const visibleCards = document.querySelectorAll('.job-card[style=""]').length;
            const noJobsContainer = document.querySelector('.no-jobs');
            
            if (visibleCards === 0 && jobCards.length > 0) {
                // Create and show "no results" message if it doesn't exist
                if (!noJobsContainer) {
                    const content = document.querySelector('.content');
                    const noResults = document.createElement('div');
                    noResults.className = 'no-jobs';
                    noResults.innerHTML = `
                        <i class="fas fa-search"></i>
                        <h2>No Jobs Found</h2>
                        <p>No jobs match your current search criteria. Try adjusting your filters.</p>
                    `;
                    content.appendChild(noResults);
                }
            } else {
                // Remove "no results" message if it exists
                const noResults = document.querySelector('.no-jobs');
                if (noResults && jobCards.length > 0) {
                    noResults.remove();
                }
            }
        }
        
        // Function to view job details
        function viewJob(jobId) {
            fetch('?action=get_job_details&job_id=' + jobId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('viewJobContent').innerHTML = data;
                    
                    // Get all modals and set their z-index lower
                    document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                        modal.style.zIndex = '1000';
                    });
                    
                    // Set the view job modal to higher z-index
                    const viewJobModal = document.getElementById('viewJobModal');
                    viewJobModal.style.zIndex = '1100';
                    openModal('viewJobModal');
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Error loading job details',
                        icon: 'error',
                        confirmButtonColor: '#4f46e5'
                    });
                });
        }
        
        // Function to edit job
        function editJob(jobId) {
            // AJAX request to get job edit form
            fetch('?action=get_job_form&job_id=' + jobId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('editJobContent').innerHTML = data;
                    openModal('editJobModal');
                    // Add event listener to the form
                    setTimeout(function() {
                        const form = document.getElementById('editJobForm');
                        if (form) {
                            form.addEventListener('submit', function(e) {
                                e.preventDefault();
                                updateJob(this);
                            });
                        }
                    }, 100);
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Error loading job edit form',
                        icon: 'error',
                        confirmButtonColor: '#4f46e5'
                    });
                });
        }
        
        // Function to update job
        function updateJob(form) {
            const formData = new FormData(form);
            
            // Disable submit button to prevent double submission
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            
            fetch('update_job.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal first
                    closeModal('editJobModal');
                    // Then show success message
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#4f46e5'
                    }).then(() => {
                        // Reload page only after user clicks OK
                        window.location.reload();
                    });
                } else {
                    // Enable submit button if error
                    submitButton.disabled = false;
                    // Show error message
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Failed to update job',
                        icon: 'error',
                        confirmButtonColor: '#4f46e5'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Enable submit button if error
                submitButton.disabled = false;
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while updating the job',
                    icon: 'error',
                    confirmButtonColor: '#4f46e5'
                });
            });
        }
        
        // Function to view applicants
        function viewApplicants(jobId) {
            // Store last viewed jobId for refresh after status update
            window.lastViewedJobId = jobId;
            // AJAX request to get applicants
            fetch('?action=get_applicants&job_id=' + jobId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('applicantsContent').innerHTML = data;
                    // Optionally, set a data-job-id attribute for later use
                    document.getElementById('applicantsContent').setAttribute('data-job-id', jobId);
                    openModal('applicantsModal');
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Error loading applicants',
                        icon: 'error',
                        confirmButtonColor: '#4f46e5'
                    });
                });
        }
        
        // Function to view applicant details
        function viewApplicantDetails(applicationId) {
            // AJAX request to get applicant details
            fetch('?action=get_applicant_details&id=' + applicationId)
                .then(response => response.text())
                .then(data => {
                    // Create a new modal for applicant details
                    const modal = document.createElement('div');
                    modal.className = 'modal-overlay active';
                    modal.id = 'applicantDetailsModal';
                    modal.innerHTML = `
                        <div class="modal">
                            <div class="modal-header">
                                <h2 class="modal-title"><i class="fas fa-user"></i> Applicant Details</h2>
                                <button class="modal-close" onclick="closeApplicantDetails()">&times;</button>
                            </div>
                            <div class="modal-body">
                                ${data}
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Error loading applicant details',
                        icon: 'error',
                        confirmButtonColor: '#4f46e5'
                    });
                });
        }
        
        // Function to close applicant details modal
        function closeApplicantDetails() {
            const modal = document.getElementById('applicantDetailsModal');
            if (modal) {
                modal.remove();
                document.body.classList.remove('modal-open');
                document.body.style.paddingRight = '';
            }
        }
        
        // Function to update applicant status
        function updateApplicantStatus(applicationId) {
            // AJAX request to get status update form
            fetch('?action=get_status_form&id=' + applicationId)
                .then(response => response.text())
                .then(data => {
                    // Find job_id from the applicants table row (if available)
                    let jobId = null;
                    // Try to get job_id from a hidden field in the applicants modal (if present)
                    const applicantsModal = document.getElementById('applicantsModal');
                    if (applicantsModal) {
                        // Try to find a data-job-id attribute or similar
                        const jobCards = document.querySelectorAll('.job-card');
                        if (jobCards.length > 0) {
                            // fallback: get the first visible job card's job_id
                            for (const card of jobCards) {
                                if (card.style.display !== 'none') {
                                    jobId = card.getAttribute('data-job-id');
                                    break;
                                }
                            }
                        }
                    }
                    // If not found, try to extract from applicantsContent (if you render job_id as a hidden field, prefer that)
                    // Create a new modal for status update
                    const modal = document.createElement('div');
                    modal.className = 'modal-overlay active';
                    modal.id = 'updateStatusModal';
                    modal.innerHTML = `
                        <div class="modal">
                            <div class="modal-header">
                                <h2 class="modal-title"><i class="fas fa-edit"></i> Update Status</h2>
                                <button class="modal-close" onclick="closeStatusModal()">&times;</button>
                            </div>
                            <div class="modal-body">
                                ${data}
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);

                    // Add event listener to the form
                    setTimeout(() => {
                        document.getElementById('updateStatusForm').addEventListener('submit', function(e) {
                            e.preventDefault();
                            const formData = new FormData(this);
                            // Try to get job_id from a hidden field in the form (if present)
                            let jobIdForRefresh = jobId;
                            // If not found, try to get from applicantsModal
                            if (!jobIdForRefresh) {
                                // Try to get from applicantsModal header
                                const header = document.querySelector('#applicantsModal .applicants-header h3');
                                if (header) {
                                    // Not reliable, but you could parse job title/company if needed
                                }
                            }
                            fetch('', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: data.message,
                                        icon: 'success',
                                        confirmButtonColor: '#4f46e5'
                                    }).then(() => {
                                        closeStatusModal();
                                        closeApplicantDetails();
                                        // Refresh applicants list
                                        // Try to get job_id from a hidden field in the form
                                        let jobIdInput = document.querySelector('#applicantsModal [data-job-id]');
                                        if (jobIdInput) {
                                            viewApplicants(jobIdInput.getAttribute('data-job-id'));
                                        } else if (window.lastViewedJobId) {
                                            viewApplicants(window.lastViewedJobId);
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: data.message,
                                        icon: 'error',
                                        confirmButtonColor: '#4f46e5'
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.close();
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'An error occurred while updating the status',
                                    icon: 'error',
                                    confirmButtonColor: '#4f46e5'
                                });
                            });
                        });
                    }, 100);
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Error loading status update form',
                        icon: 'error',
                        confirmButtonColor: '#4f46e5'
                    });
                });
        }
        
        // Function to close status modal
        function closeStatusModal() {
            const modal = document.getElementById('updateStatusModal');
            if (modal) {
                modal.remove();
                document.body.classList.remove('modal-open');
                document.body.style.paddingRight = '';
            }
        }
        
        // Function to open modal
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            // Calculate scrollbar width and set padding-right to prevent layout shift
            const scrollBarWidth = window.innerWidth - document.documentElement.clientWidth;
            document.body.classList.add('modal-open');
            if (scrollBarWidth > 0) {
                document.body.style.paddingRight = scrollBarWidth + 'px';
            }
        }
        
        // Function to close modal
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                document.body.classList.remove('modal-open');
                document.body.style.paddingRight = '';
                
                // Clear content if it's the applicants modal
                if (modalId === 'applicantsModal') {
                    document.getElementById('applicantsContent').innerHTML = '';
                }
            }
        }
        
        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const modals = document.querySelectorAll('.modal-overlay');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.classList.remove('active');
                    document.body.classList.remove('modal-open');
                    document.body.style.paddingRight = '';
                }
            });
        });
        
        // Add event listener for new job form
        document.getElementById('addJobForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Show loading state
            Swal.fire({
                title: 'Posting Job...',
                text: 'Please wait while we post your job',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#4f46e5'
                    }).then(() => {
                        closeModal('addJobModal');
                        // Reload page to show new job
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message,
                        icon: 'error',
                        confirmButtonColor: '#4f46e5'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while adding the job',
                    icon: 'error',
                    confirmButtonColor: '#4f46e5'
                });
            });
        });
        
        // Add event listeners for "Add Job" buttons
        document.getElementById('addNewJobBtn').addEventListener('click', function(e) {
            e.preventDefault();
            openModal('addJobModal');
        });
        
        if (document.getElementById('addFirstJobBtn')) {
            document.getElementById('addFirstJobBtn').addEventListener('click', function(e) {
                e.preventDefault();
                openModal('addJobModal');
            });
        }
        
        // Initialize SweetAlert for all alerts
        document.addEventListener('DOMContentLoaded', function() {
            // Replace default alerts with SweetAlert
            window.alert = function(message) {
                Swal.fire({
                    text: message,
                    icon: 'info',
                    confirmButtonColor: '#4f46e5'
                });
            };
        });
        
        // Archive job button handler
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.archive-job-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const jobId = this.getAttribute('data-job-id');
                    Swal.fire({
                        title: 'Archive Job?',
                        text: 'This will mark the job as closed.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, archive it!',
                        confirmButtonColor: '#4f46e5'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Swal.fire({
                                title: 'Archiving...',
                                allowOutsideClick: false,
                                showConfirmButton: false,
                                willOpen: () => { Swal.showLoading(); }
                            });
                            const formData = new FormData();
                            formData.append('action', 'update_job_status');
                            formData.append('job_id', jobId);
                            formData.append('status', 'closed');
                            fetch('', {
                                method: 'POST',
                                body: formData
                            })
                            .then(res => res.json())
                            .then(data => {
                                Swal.close();
                                if (data.success) {
                                    Swal.fire({
                                        title: 'Archived!',
                                        text: data.message,
                                        icon: 'success',
                                        confirmButtonColor: '#4f46e5'
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: data.message,
                                        icon: 'error',
                                        confirmButtonColor: '#4f46e5'
                                    });
                                }
                            })
                            .catch(() => {
                                Swal.close();
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'An error occurred while archiving the job',
                                    icon: 'error',
                                    confirmButtonColor: '#4f46e5'
                                });
                            });
                        }
                    });
                });
            });
        });
        
        // Function to view archived jobs
        function viewArchivedJobs() {
            fetch('?action=get_archived_jobs')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('archivedJobsContent').innerHTML = data;
                    openModal('archivedJobsModal');
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!', 
                        text: 'Error loading archived jobs',
                        icon: 'error',
                        confirmButtonColor: '#4f46e5'
                    });
                });
        }
        
        // Restore job button handler
        document.addEventListener('click', function(e) {
            if (e.target && e.target.closest('.restore-job-btn')) {
                const btn = e.target.closest('.restore-job-btn');
                const jobId = btn.getAttribute('data-job-id');
                restoreJob(jobId);
            }
        });

        // Function to restore job
        function restoreJob(jobId) {
            Swal.fire({
                title: 'Restore Job?',
                text: 'This will make the job active again.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, restore it!',
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'update_job_status');
                    formData.append('job_id', jobId);
                    formData.append('status', 'active');
                    
                    Swal.fire({
                        title: 'Restoring...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => { Swal.showLoading(); }
                    });

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        Swal.close();
                        if (data.success) {
                            Swal.fire({
                                title: 'Restored!',
                                text: 'Job has been restored successfully.',
                                icon: 'success',
                                confirmButtonColor: '#10b981'
                            }).then(() => {
                                closeModal('archivedJobsModal');
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message,
                                icon: 'error',
                                confirmButtonColor: '#4f46e5'
                            });
                        }
                    })
                    .catch(() => {
                        Swal.close();
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while restoring the job',
                            icon: 'error',
                            confirmButtonColor: '#4f46e5'
                        });
                    });
                }
            });
        }

        // Delete job button handler
        document.addEventListener('click', function(e) {
            if (e.target && e.target.closest('.delete-job-btn')) {
                const btn = e.target.closest('.delete-job-btn');
                const jobId = btn.getAttribute('data-job-id');
                deleteJob(jobId);
            }
        });

        // Function to delete job
        function deleteJob(jobId) {
            Swal.fire({
                title: 'Delete Job?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'delete_job');
                    formData.append('job_id', jobId);
                    
                    Swal.fire({
                        title: 'Deleting...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => { Swal.showLoading(); }
                    });

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        Swal.close();
                        if (data.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: 'Job has been deleted successfully.',
                                icon: 'success',
                                confirmButtonColor: '#10b981'
                            }).then(() => {
                                // Instead of closing the modal, just refresh the archived jobs list
                                viewArchivedJobs();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message || 'Failed to delete job',
                                icon: 'error',
                                confirmButtonColor: '#4f46e5'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.close();
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while deleting the job',
                            icon: 'error',
                            confirmButtonColor: '#4f46e5'
                        });
                    });
                }
            });
        }

        // --- Add this block at the end of the script ---
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-open applicant modal if view_applicant param is present
            const urlParams = new URLSearchParams(window.location.search);
            const viewApplicantId = urlParams.get('view_applicant');
            if (viewApplicantId) {
                // Find the job_id for this application
                fetch('?action=get_applicant_details&id=' + viewApplicantId)
                    .then(response => response.text())
                    .then(data => {
                        // Try to extract job_id from the applicant details (if needed)
                        // But we can just open the applicant details modal directly
                        viewApplicantDetails(viewApplicantId);
                    });
            }
        });

        // Mobile-specific enhancements
        document.addEventListener('DOMContentLoaded', function() {
            // Add touch feedback to all buttons and cards
            const touchElements = document.querySelectorAll('.btn, .job-card, .filter-button');
            touchElements.forEach(el => {
                el.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.98)';
                }, { passive: true });
                
                el.addEventListener('touchend', function() {
                    this.style.transform = '';
                }, { passive: true });
            });
            
            // Improve modal scrolling on mobile
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.addEventListener('touchmove', function(e) {
                    e.stopPropagation();
                }, { passive: true });
            });
            
            // Fix for iOS input focus issues
            const inputs = document.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    // Small timeout to ensure the viewport adjusts
                    setTimeout(() => {
                        // Scroll the input into view
                        this.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 300);
                });
            });
            
            // Detect Samsung browser and add specific fixes if needed
            const isSamsungBrowser = navigator.userAgent.match(/SamsungBrowser/i);
            if (isSamsungBrowser) {
                document.body.classList.add('samsung-browser');
                
                // Add Samsung-specific CSS
                const style = document.createElement('style');
                style.textContent = `
                    .samsung-browser .modal {
                        -webkit-transform: translateZ(0);
                        transform: translateZ(0);
                    }
                    .samsung-browser input, 
                    .samsung-browser select, 
                    .samsung-browser textarea {
                        -webkit-appearance: none;
                        border-radius: 0.5rem;
                    }
                `;
                document.head.appendChild(style);
            }
        });
    </script>

    <script>
    // Add FastClick to eliminate the 300ms delay on mobile devices
    document.addEventListener('DOMContentLoaded', function() {
        // Simple FastClick implementation
        function attachFastClick(element) {
            let startX, startY, startTime;
            const threshold = 10; // Threshold in pixels for movement
            const timeThreshold = 200; // Time threshold in ms
            
            element.addEventListener('touchstart', function(e) {
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
                startTime = Date.now();
            }, { passive: true });
            
            element.addEventListener('touchend', function(e) {
                const endX = e.changedTouches[0].clientX;
                const endY = e.changedTouches[0].clientY;
                const endTime = Date.now();
                
                // Check if it was a tap (not a scroll or long press)
                if (Math.abs(endX - startX) < threshold && 
                    Math.abs(endY - startY) < threshold &&
                    endTime - startTime < timeThreshold) {
                    // Create and dispatch a click event
                    const clickEvent = new MouseEvent('click', {
                        view: window,
                        bubbles: true,
                        cancelable: true
                    });
                    e.target.dispatchEvent(clickEvent);
                }
            }, { passive: true });
        }
        
        // Attach to all clickable elements
        document.querySelectorAll('button, a, .job-card, .filter-dropdown-item').forEach(attachFastClick);
    });
</script>
</body>
</html>