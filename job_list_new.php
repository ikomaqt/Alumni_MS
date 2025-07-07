<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Include database connection
include 'sqlconnection.php';

// Get admin details
$admin_id = $_SESSION['admin_id'];
$admin_role = '';
$sql = "SELECT role FROM admin_users WHERE admin_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $admin_role = $row['role'];
}
$stmt->close();

// Initialize variables
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$success_message = '';
$error_message = '';

// Handle AJAX requests
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Get job details for modal
    if ($action === 'get_job_details') {
        $job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
        
        if ($job_id > 0) {
            $sql = "SELECT j.*, a.name as posted_by_name 
                    FROM job_listings j 
                    LEFT JOIN admin_users a ON j.posted_by_id = a.admin_id 
                    WHERE j.job_id = ? AND j.posted_by_type = 'admin'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $job_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $job = $result->fetch_assoc();
                
                // Output job details in a table format
                echo '<table class="job-details-table">
                    <tr>
                        <th>Job Title</th>
                        <td>' . htmlspecialchars($job['title']) . '</td>
                    </tr>
                    <tr>
                        <th>Company</th>
                        <td>' . htmlspecialchars($job['company']) . '</td>
                    </tr>
                    <tr>
                        <th>Employment Type</th>
                        <td>' . htmlspecialchars($job['employment_type']) . '</td>
                    </tr>
                    <tr>
                        <th>Job Type</th>
                        <td>' . htmlspecialchars($job['job_type']) . '</td>
                    </tr>
                    <tr>
                        <th>Salary Range</th>
                        <td>' . htmlspecialchars($job['salary_range']) . '</td>
                    </tr>
                    <tr>
                        <th>Contact Email</th>
                        <td>' . htmlspecialchars($job['contact_email']) . '</td>
                    </tr>
                    <tr>
                        <th>Posted At</th>
                        <td>' . date('F j, Y, g:i a', strtotime($job['posted_at'])) . '</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>' . htmlspecialchars($job['status']) . '</td>
                    </tr>
                    <tr>
                        <th>Posted By</th>
                        <td>' . htmlspecialchars($job['posted_by_name']) . '</td>
                    </tr>
                    <tr>
                        <th>Job Description</th>
                        <td>' . nl2br(htmlspecialchars($job['description'])) . '</td>
                    </tr>
                    <tr>
                        <th>Requirements</th>
                        <td>' . nl2br(htmlspecialchars($job['requirements'])) . '</td>
                    </tr>
                </table>';
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
            $sql = "SELECT * FROM job_listings WHERE job_id = $job_id AND posted_by_type = 'admin'";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $job = $result->fetch_assoc();
                
                // Output job edit form HTML
                echo '<form id="editJobForm" method="POST">
                    <input type="hidden" name="action" value="update_job">
                    <input type="hidden" name="job_id" value="' . $job_id . '">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title">Job Title</label>
                            <input type="text" id="title" name="title" value="' . htmlspecialchars($job['title']) . '" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="company">Company</label>
                            <input type="text" id="company" name="company" value="' . htmlspecialchars($job['company']) . '" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" value="' . htmlspecialchars($job['location']) . '">
                        </div>
                        
                        <div class="form-group">
                            <label for="salary_range">Salary Range</label>
                            <input type="text" id="salary_range" name="salary_range" value="' . htmlspecialchars($job['salary_range']) . '">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="employment_type">Employment Type</label>
                            <select id="employment_type" name="employment_type">
                                <option value="Full-time" ' . ($job['employment_type'] == 'Full-time' ? 'selected' : '') . '>Full-time</option>
                                <option value="Part-time" ' . ($job['employment_type'] == 'Part-time' ? 'selected' : '') . '>Part-time</option>
                                <option value="Contract" ' . ($job['employment_type'] == 'Contract' ? 'selected' : '') . '>Contract</option>
                                <option value="Freelance" ' . ($job['employment_type'] == 'Freelance' ? 'selected' : '') . '>Freelance</option>
                                <option value="Internship" ' . ($job['employment_type'] == 'Internship' ? 'selected' : '') . '>Internship</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="job_type">Job Type</label>
                            <select id="job_type" name="job_type">
                                <option value="On-site" ' . ($job['job_type'] == 'On-site' ? 'selected' : '') . '>On-site</option>
                                <option value="Remote" ' . ($job['job_type'] == 'Remote' ? 'selected' : '') . '>Remote</option>
                                <option value="Hybrid" ' . ($job['job_type'] == 'Hybrid' ? 'selected' : '') . '>Hybrid</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Job Description</label>
                        <textarea id="description" name="description">' . htmlspecialchars($job['description']) . '</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="requirements">Requirements</label>
                        <textarea id="requirements" name="requirements">' . htmlspecialchars($job['requirements']) . '</textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_email">Contact Email</label>
                            <input type="email" id="contact_email" name="contact_email" value="' . htmlspecialchars($job['contact_email']) . '">
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="open" ' . ($job['status'] == 'open' ? 'selected' : '') . '>Open</option>
                                <option value="closed" ' . ($job['status'] == 'closed' ? 'selected' : '') . '>Closed</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <p>Posted at: ' . date('F j, Y, g:i a', strtotime($job['posted_at'])) . '</p>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal(\'editJobModal\')">Cancel</button>
                        <button type="submit" name="update_job" class="btn">Update Job</button>
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
            $sql = "SELECT title, company FROM job_listings WHERE job_id = $job_id AND posted_by_type = 'admin'";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $job = $result->fetch_assoc();
                
                // Get applicants
                $applicants = [];
                $sql = "SELECT * FROM job_applications WHERE job_id = $job_id ORDER BY applied_at DESC";
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $applicants[] = $row;
                    }
                }
                
                // Output applicants HTML
                echo '<div class="applicants-header">
                    <h3>' . htmlspecialchars($job['title']) . ' - ' . htmlspecialchars($job['company']) . '</h3>
                    <p>Total Applicants: ' . count($applicants) . '</p>
                </div>';
                
                if (count($applicants) > 0) {
                    echo '<div class="table-responsive">
                        <table class="applicants-table jobs-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Applied On</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>';
                            
                        foreach ($applicants as $applicant) {
                            echo '<tr>
                                <td>' . htmlspecialchars($applicant['name']) . '</td>
                                <td>' . htmlspecialchars($applicant['email']) . '</td>
                                <td>' . htmlspecialchars($applicant['phone']) . '</td>
                                <td>' . date('M d, Y', strtotime($applicant['applied_at'])) . '</td>
                                <td>
                                    <span class="status ' . strtolower($applicant['status']) . '">
                                        ' . htmlspecialchars($applicant['status']) . '
                                    </span>
                                </td>
                                <td class="actions">
                                    <button class="btn btn-sm" onclick="viewApplicantDetails(' . $applicant['application_id'] . ')">
                                        <i class="fas fa-eye"></i>
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
            $sql = "SELECT * FROM job_applications WHERE application_id = $application_id";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $applicant = $result->fetch_assoc();
                
                // Get job title
                $job_id = $applicant['job_id'];
                $job_title = '';
                $job_sql = "SELECT title FROM job_listings WHERE job_id = $job_id AND posted_by_type = 'admin'";
                $job_result = $conn->query($job_sql);
                
                if ($job_result && $job_result->num_rows > 0) {
                    $job = $job_result->fetch_assoc();
                    $job_title = $job['title'];
                }
                
                // Output applicant details HTML
                echo '<div class="applicant-details">
                    <div class="applicant-header">
                        <div class="applicant-avatar">
                            ' . strtoupper(substr($applicant['name'], 0, 1)) . '
                        </div>
                        <div class="applicant-info">
                            <h3>' . htmlspecialchars($applicant['name']) . '</h3>
                            <p>' . htmlspecialchars($applicant['email']) . '</p>
                            <p>' . htmlspecialchars($applicant['phone']) . '</p>
                        </div>
                        <div class="applicant-status">
                            <span class="status ' . strtolower($applicant['status']) . '">
                                ' . htmlspecialchars($applicant['status']) . '
                            </span>
                        </div>
                    </div>
                    
                    <div class="applicant-section">
                        <h4>Applied For</h4>
                        <p>' . htmlspecialchars($job_title) . '</p>
                    </div>
                    
                    <div class="applicant-section">
                        <h4>Applied On</h4>
                        <p>' . date('F j, Y, g:i a', strtotime($applicant['applied_at'])) . '</p>
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
            $sql = "SELECT * FROM job_applications WHERE application_id = $application_id";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $applicant = $result->fetch_assoc();
                
                // Output status update form HTML
                echo '<form id="updateStatusForm" method="POST">
                    <input type="hidden" name="action" value="update_applicant_status">
                    <input type="hidden" name="application_id" value="' . $application_id . '">
                    
                    <div class="form-group">
                        <label for="applicant_status">Status</label>
                        <select id="applicant_status" name="status" class="form-control">
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
                        <textarea id="notes" name="notes" class="form-control">' . htmlspecialchars($applicant['notes'] ?? '') . '</textarea>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById(\'updateStatusModal\').remove()">Cancel</button>
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
    
    // Handle job deletion
    else if ($action === 'delete_job') {
        $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
        $response = [];
        if ($job_id > 0) {
            // Optionally, delete related applications first if needed
            $conn->query("DELETE FROM job_applications WHERE job_id = $job_id");
            $sql = "DELETE FROM job_listings WHERE job_id = $job_id AND posted_by_type = 'admin'";
            if ($conn->query($sql)) {
                $response['success'] = true;
                $response['message'] = "Job deleted successfully!";
            } else {
                $response['success'] = false;
                $response['message'] = "Error deleting job: " . $conn->error;
            }
        } else {
            $response['success'] = false;
            $response['message'] = "Invalid job ID!";
        }
        header('Content-Type: application/json');
        echo json_encode($response);
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

        // Get current status to check for transition to open
        $current_status = '';
        $result = $conn->query("SELECT status FROM job_listings WHERE job_id = $job_id");
        if ($result && $row = $result->fetch_assoc()) {
            $current_status = $row['status'];
        }

        $set_posted_at = '';
        if ($current_status !== 'open' && $status === 'open') {
            $set_posted_at = ", posted_at = NOW()";
        }

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
                $set_posted_at
                WHERE job_id = $job_id AND posted_by_type = 'admin'";

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
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Add new job
    else if ($_POST['action'] === 'add_job') {
        try {
            // Debug output
            error_log("Attempting to add new job. POST data: " . print_r($_POST, true));
            
            // Validate admin_id
            if (!$admin_id) {
                throw new Exception("Admin ID not found in session");
            }

            // Simple query first to test connection
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
                posted_at,
                posted_by_id,
                posted_by_type,
                created_at,
                updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, 
                'open',
                NOW(),
                ?,
                'admin',
                NOW(),
                NOW()
            )";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param(
                "sssssssssi",
                $_POST['title'],
                $_POST['company'],
                $_POST['location'],
                $_POST['employment_type'],
                $_POST['job_type'],
                $_POST['salary_range'],
                $_POST['description'],
                $_POST['requirements'],
                $_POST['contact_email'],
                $admin_id
            );

            error_log("SQL Query: " . $sql);
            error_log("Admin ID: " . $admin_id);

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            if ($stmt->affected_rows <= 0) {
                throw new Exception("Insert failed - no rows affected");
            }

            $job_id = $stmt->insert_id;
            error_log("Job inserted successfully. Insert ID: " . $job_id);

            // Send email notifications to all users except the poster
            try {
                require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
                require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
                require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                // Get all user emails except the admin poster
                $user_sql = "SELECT email FROM users WHERE acc_status = 'active' AND email IS NOT NULL AND email != ''";
                $user_result = $conn->query($user_sql);
                $user_emails = [];
                
                if ($user_result && $user_result->num_rows > 0) {
                    while ($row = $user_result->fetch_assoc()) {
                        $user_emails[] = $row['email'];
                    }
                }

                if (!empty($user_emails)) {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'nesrac22@gmail.com';
                    $mail->Password = 'cegq qqrk jjdw xwbs';
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->setFrom('nesrac22@gmail.com', 'Alumni MS');

                    // Prepare job info for email
                    $job_title = $_POST['title'];
                    $job_company = $_POST['company'];
                    $job_location = $_POST['location'];
                    $job_type = $_POST['job_type'];
                    $job_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/alumni/job_list_new.php?job_id=" . $job_id;

                    // Send email to each user
                    foreach ($user_emails as $user_email) {
                        try {
                            $mail->clearAddresses();
                            $mail->addAddress($user_email);
                            $mail->isHTML(true);
                            $mail->Subject = "New Job Posted: $job_title at $job_company";
                            $mail->Body = "
                                <h3>New Job Opportunity!</h3>
                                <p><strong>Title:</strong> $job_title<br>
                                <strong>Company:</strong> $job_company<br>
                                <strong>Location:</strong> $job_location<br>
                                <strong>Type:</strong> $job_type</p>
                                <p><a href='$job_url'>View Job Details</a></p>
                                <br>
                                <small>This is an automated notification from Alumni MS.</small>
                            ";
                            $mail->send();
                            error_log("Email sent successfully to: " . $user_email);
                        } catch (Exception $e) {
                            error_log("Failed to send email to {$user_email}: " . $mail->ErrorInfo);
                            continue; // Continue with next email even if one fails
                        }
                    }
                }

                $response = [
                    'success' => true,
                    'message' => "Job posted successfully!",
                    'job_id' => $job_id
                ];

            } catch (Exception $e) {
                error_log("Error in email notification process: " . $e->getMessage());
                // Still return success if job was posted but emails failed
                $response = [
                    'success' => true,
                    'message' => "Job posted successfully! (Email notifications may have been delayed)",
                    'job_id' => $job_id
                ];
            }
        } catch (Exception $e) {
            error_log("Error adding job: " . $e->getMessage());
            $response = [
                'success' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        } finally {
            if (isset($stmt)) {
                $stmt->close();
            }
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Get job data
$job = null;
if ($job_id > 0) {
    $sql = "SELECT * FROM job_listings WHERE job_id = $job_id AND posted_by_type = 'admin'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $job = $result->fetch_assoc();
    } else {
        $error_message = "Job not found!";
    }
}

// Get all jobs for the sidebar
$all_jobs = [];
$sql = "SELECT job_id, title, company, location, employment_type, job_type, status, posted_at 
        FROM job_listings 
        WHERE posted_by_type = 'admin' 
        ORDER BY created_at DESC";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Job Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* CSS Styles */
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
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
        
        .container {
            margin-left: 78px;
            transition: var(--transition);
            min-height: 100vh;
            padding: 20px;
        }
        
        .sidebar.open ~ .container {
            margin-left: 250px;
        }
        
        .main-content {
            width: 100%;
            padding: 30px;
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .header h1 {
            color: var(--dark-bg);
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header h1 i {
            color: var(--primary-color);
        }
        
        .alert {
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
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
        
        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin-bottom: 24px;
        }
        
        .jobs-table-container {
            overflow-x: auto;
            margin-bottom: 24px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
        }
        
        .jobs-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .jobs-table th,
        .jobs-table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .jobs-table th {
            background-color: var(--light-bg);
            font-weight: 600;
            color: var(--dark-bg);
            position: sticky;
            top: 0;
            z-index: 10;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
        }
        
        .jobs-table tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .jobs-table tr:last-child td {
            border-bottom: none;
        }
        
        .jobs-table td.actions {
            text-align: center;
            white-space: nowrap; /* Prevent wrapping of buttons */
        }

        .jobs-table .actions button {
            margin: 0 4px; /* Add consistent spacing between buttons */
            padding: 6px 10px; /* Ensure consistent button size */
        }
        
        .status {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            letter-spacing: 0.05em;
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
        
        .status.new {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }
        
        .status.reviewing {
            background-color: rgba(124, 58, 237, 0.1);
            color: #7c3aed;
        }
        
        .status.interviewed {
            background-color: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
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
            font-weight: 700;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            box-shadow: 0 2px 4px rgba(67, 97, 238, 0.2);
        }
        
        .btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(67, 97, 238, 0.2);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
            border-radius: 6px;
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            box-shadow: 0 2px 4px rgba(107, 114, 128, 0.2);
        }
        
        .btn-secondary:hover {
            background-color: #4b5563;
            box-shadow: 0 4px 8px rgba(107, 114, 128, 0.3);
        }
        
        .btn-success {
            background-color: var(--success-color);
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
        }
        
        .btn-success:hover {
            background-color: #0d9668;
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
            box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            box-shadow: none;
        }
        
        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3);
        }
        
        .no-jobs {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 300px;
            color: var(--text-muted);
            text-align: center;
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 40px;
        }
        
        .no-jobs h2 {
            margin-bottom: 16px;
            color: var(--dark-bg);
            font-weight: 700;
        }
        
        .no-jobs i {
            font-size: 3.5rem;
            margin-bottom: 24px;
            color: var(--primary-color);
            opacity: 0.7;
        }
        
        .no-jobs p {
            max-width: 500px;
            margin-bottom: 24px;
            font-size: 1.1rem;
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
            pointer-events: none; /* Prevent interaction when not active */
        }
        
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
            pointer-events: auto; /* Allow interaction when active */
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
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
            transition: color 0.2s;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .modal-close:hover {
            color: var(--danger-color);
            background-color: rgba(239, 68, 68, 0.1);
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
            margin-bottom: 24px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-bg);
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
            background-color: white;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
            line-height: 1.6;
        }
        
        .form-row {
            display: flex;
            gap: 24px;
            margin-bottom: 24px;
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        /* Applicants Table */
        .applicants-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 24px;
            text-align: left;
            border-radius: 10px;
            overflow: hidden;
        }

        .applicants-table th,
        .applicants-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .applicants-table th {
            background-color: var(--light-bg);
            font-weight: 600;
            color: var(--dark-bg);
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
        }

        .applicants-table tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .applicants-table .actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        /* Applicant Details */
        .applicant-details {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        
        .applicant-header {
            display: flex;
            align-items: center;
            gap: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .applicant-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
        }
        
        .applicant-info h3 {
            font-size: 1.5rem;
            margin-bottom: 8px;
            font-weight: 700;
        }
        
        .applicant-info p {
            color: var(--text-muted);
            margin: 0;
            line-height: 1.5;
        }
        
        .applicant-status {
            margin-left: auto;
        }
        
        .applicant-section {
            border-top: 1px solid var(--border-color);
            padding-top: 20px;
        }
        
        .applicant-section h4 {
            font-size: 1.1rem;
            margin-bottom: 12px;
            color: var(--text-muted);
            font-weight: 600;
        }
        
        .cover-letter {
            background-color: var(--light-bg);
            padding: 20px;
            border-radius: 10px;
            line-height: 1.8;
            font-size: 0.95rem;
        }
        
        /* Job Details */
        .job-details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
            text-align: left;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .job-details-table th,
        .job-details-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        
        .job-details-table th {
            background-color: var(--light-bg);
            font-weight: 600;
            color: var(--dark-bg);
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
        }
        
        .job-details-table tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        /* Search and Filter */
        .search-filter {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-box input {
            padding-left: 44px;
            height: 48px;
            border-radius: 10px;
        }
        
        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.1rem;
        }
        
        .filter-box {
            width: 200px;
        }
        
        .filter-box select {
            height: 48px;
            border-radius: 10px;
        }
        
        /* No Applicants */
        .no-applicants {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            text-align: center;
            color: var(--text-muted);
        }
        
        .no-applicants i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: var(--primary-color);
            opacity: 0.7;
        }
        
        .no-applicants h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var,--dark-bg);
            font-weight: 700;
        }
        
        /* Applicants Header */
        .applicants-header {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .applicants-header h3 {
            font-size: 1.5rem;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--dark-bg);
        }
        
        .applicants-header p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .form-row {
                flex-direction: column;
                gap: 24px;
            }
            
            .search-filter {
                flex-direction: column;
                gap: 16px;
            }
            
            .filter-box {
                width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 12px;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .modal {
                width: 95%;
            }
            
            .jobs-table th:nth-child(3),
            .jobs-table td:nth-child(3),
            .jobs-table th:nth-child(4),
            .jobs-table td:nth-child(4) {
                display: none;
            }
            
            .applicant-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            
            .applicant-status {
                margin-left: 0;
            }
        }
        
        @media (max-width: 576px) {
            .jobs-table th:nth-child(5),
            .jobs-table td:nth-child(5) {
                display: none;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .modal-body {
                padding: 20px;
            }
            
            .modal-header, 
            .modal-footer {
                padding: 16px 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container">
        <div class="main-content">
            <div class="header">
                <h1><i class="fas fa-tasks"></i> Job Management</h1>
                <a href="#" class="btn" id="addNewJobBtn">
                    <i class="fas fa-plus"></i> Add New Job
                </a>
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
                <div class="filter-box">
                    <select id="statusFilter" onchange="filterJobs()">
                        <option value="">All Statuses</option>
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
            </div>
            
            <?php if (count($all_jobs) > 0): ?>
                <div class="jobs-table-container">
                    <table class="jobs-table" id="jobsTable">
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Company</th>
                                <th>Location</th>
                                <th>Employment Type</th>
                                <th>Job Type</th>
                                <th>Status</th>
                                <th>Posted Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_jobs as $job_item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($job_item['title']); ?></td>
                                    <td><?php echo htmlspecialchars($job_item['company']); ?></td>
                                    <td><?php echo htmlspecialchars($job_item['location']); ?></td>
                                    <td><?php echo htmlspecialchars($job_item['employment_type']); ?></td>
                                    <td><?php echo htmlspecialchars($job_item['job_type']); ?></td>
                                    <td>
                                        <span class="status <?php echo strtolower($job_item['status']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($job_item['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($job_item['posted_at'])); ?></td>
                                    <td class="actions" style="text-align: center;">
                                        <button class="btn btn-sm" onclick="viewJob(<?php echo $job_item['job_id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-success" onclick="editJob(<?php echo $job_item['job_id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-secondary" onclick="viewApplicants(<?php echo $job_item['job_id']; ?>)">
                                            <i class="fas fa-users"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteJob(<?php echo $job_item['job_id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-jobs">
                    <i class="fas fa-folder-open"></i>
                    <h2>No Jobs Found</h2>
                    <p>There are no job listings available.</p>
                </div>
            <?php endif; ?>
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
                <h2 class="modal-title"><i class="fas fa-plus"></i> Add New Job</h2>
                <button class="modal-close" onclick="closeModal('addJobModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addJobForm" method="POST">
                    <input type="hidden" name="action" value="add_job">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title">Job Title</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="company">Company</label>
                            <input type="text" id="company" name="company" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location">
                        </div>
                        
                        <div class="form-group">
                            <label for="salary_range">Salary Range</label>
                            <input type="text" id="salary_range" name="salary_range">
                        </div>
                    </div>
                    
                    <div class="form-row">
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
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Job Description</label>
                        <textarea id="description" name="description" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="requirements">Requirements</label>
                        <textarea id="requirements" name="requirements" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_email">Contact Email</label>
                            <input type="email" id="contact_email" name="contact_email" required>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addJobModal')">Cancel</button>
                        <button type="submit" class="btn">Add Job</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Function to filter jobs
        function filterJobs() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
            const table = document.getElementById('jobsTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const titleCell = rows[i].getElementsByTagName('td')[0];
                const companyCell = rows[i].getElementsByTagName('td')[1];
                const statusSpan = rows[i].getElementsByTagName('td')[5]?.querySelector('.status');
                const statusCell = statusSpan ? statusSpan.textContent.trim().toLowerCase() : '';

                if (titleCell && companyCell) {
                    const titleText = titleCell.textContent.toLowerCase();
                    const companyText = companyCell.textContent.toLowerCase();

                    const matchesSearch = titleText.includes(searchInput) || companyText.includes(searchInput);
                    const matchesStatus = statusFilter === '' || statusCell === statusFilter;

                    if (matchesSearch && matchesStatus) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        }
        
        // Function to load job details
        function loadJobDetails(jobId) {
            window.location.href = '?job_id=' + jobId;
        }
        
        // Function to view job details
        function viewJob(jobId) {
            // AJAX request to get job details
            fetch('?action=get_job_details&job_id=' + jobId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('viewJobContent').innerHTML = data;
                    openModal('viewJobModal');
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Error loading job details',
                        icon: 'error',
                        confirmButtonColor: '#4361ee'
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
                    document.getElementById('editJobForm').addEventListener('submit', function(e) {
                        e.preventDefault();
                        updateJob(this);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Error loading job edit form',
                        icon: 'error',
                        confirmButtonColor: '#4361ee'
                    });
                });
        }
        
        // Function to update job
        function updateJob(form) {
            const formData = new FormData(form);
            
            // Show loading state
            Swal.fire({
                title: 'Updating...',
                text: 'Please wait while we update the job',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('?action=update_job', {
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
                        confirmButtonColor: '#4361ee'
                    }).then(() => {
                        closeModal('editJobModal');
                        location.reload();
                    });
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: error.message || 'An error occurred while updating the job',
                    icon: 'error',
                    confirmButtonColor: '#4361ee'
                });
            });
        }
        
        // Function to view applicants
        function viewApplicants(jobId) {
            // AJAX request to get applicants
            fetch('?action=get_applicants&job_id=' + jobId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('applicantsContent').innerHTML = data;
                    openModal('applicantsModal');
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Error loading applicants',
                        icon: 'error',
                        confirmButtonColor: '#4361ee'
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
                                <button class="modal-close" onclick="document.getElementById('applicantDetailsModal').remove()">&times;</button>
                            </div>
                            <div class="modal-body">
                                ${data}
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" onclick="document.getElementById('applicantDetailsModal').remove()">Close</button>
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
                        confirmButtonColor: '#4361ee'
                    });
                });
        }

        // Function to delete a job
        function deleteJob(jobId) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This will permanently delete the job and all its applications.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Deleting...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    fetch('?action=delete_job', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'job_id=' + encodeURIComponent(jobId)
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        if (data.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonColor: '#4361ee'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message,
                                icon: 'error',
                                confirmButtonColor: '#4361ee'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while deleting the job.',
                            icon: 'error',
                            confirmButtonColor: '#4361ee'
                        });
                    });
                }
            });
        }

        // Function to open modal
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        // Function to close modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        
        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const modals = document.querySelectorAll('.modal-overlay');
            modals.forEach(modal => {
                if (modal.classList.contains('active') && event.target === modal) {
                    modal.classList.remove('active');
                    document.body.style.overflow = 'auto';
                }
            });
        });
        
        // Add event listener for new job form
        document.getElementById('addJobForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            // Show loading SweetAlert and disable submit button
            Swal.fire({
                title: 'Posting Job...',
                text: 'Please wait while we post your job.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            fetch('?action=add_job', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (submitBtn) submitBtn.disabled = false;
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#4361ee'
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
                        confirmButtonColor: '#4361ee'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                if (submitBtn) submitBtn.disabled = false;
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while adding the job',
                    icon: 'error',
                    confirmButtonColor: '#4361ee'
                });
            });
        });
        
        // Update the header button click handler
        document.querySelector('.header .btn').addEventListener('click', function(e) {
            e.preventDefault();
            openModal('addJobModal');
        });
        
        // Add event listener for "Add First Job" button
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
                    confirmButtonColor: '#4361ee'
                });
            };
        });
    </script>
</body>
</html>
