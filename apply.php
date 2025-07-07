<?php
session_start(); // Start session to manage user login state
include 'sqlconnection.php'; // Include your database connection file
include 'user_navbar.php'; // Include your navigation bar

// Initialize variables
$job = null;
$notification = ['status' => '', 'message' => ''];
$existing_application = null;

// Get job ID from URL
$job_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch specific job details from the database
if ($job_id > 0) {
    $sql_job = "SELECT *
                FROM job_listings
                WHERE job_id = ?";
    $stmt = $conn->prepare($sql_job);
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result_job = $stmt->get_result();

    if ($result_job->num_rows > 0) {
        $job = $result_job->fetch_assoc();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_id = intval($_POST['job_id']);
    $user_id = $_SESSION['user_id'] ?? null; // Get user ID from session (if logged in)
    $name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $cover_letter = $_POST['cover_letter'];

    // Check if the user has already applied for this job
    $sql_check = "SELECT * FROM job_applications WHERE job_id = ? AND user_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $job_id, $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // User has already applied, so update the existing application
        $existing_application = $result_check->fetch_assoc();
        $old_resume_path = $existing_application['resume_path']; // Store the old resume path

        // Handle file upload
        $resume_path = $old_resume_path; // Default to the old resume path
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/resumes/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $resume_name = basename($_FILES['resume']['name']);
            $resume_path = $upload_dir . $resume_name;

            // Delete the old resume file if it exists
            if ($old_resume_path && file_exists($old_resume_path)) {
                unlink($old_resume_path); // Delete the old file
            }

            // Move the new file to the upload directory
            move_uploaded_file($_FILES['resume']['tmp_name'], $resume_path);
        }

        // Update the existing application
        $sql_update = "UPDATE job_applications
                       SET name = ?, email = ?, phone = ?, resume_path = ?, cover_letter = ?, applied_at = NOW()
                       WHERE job_id = ? AND user_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sssssii", $name, $email, $phone, $resume_path, $cover_letter, $job_id, $user_id);

        if ($stmt_update->execute()) {
            $notification['status'] = 'success';
            $notification['message'] = 'Application updated successfully!';
        } else {
            $notification['status'] = 'error';
            $notification['message'] = 'Error updating application: ' . $stmt_update->error;
        }

        $stmt_update->close();
    } else {
        // User has not applied before, so insert a new application
        // Handle file upload
        $resume_path = '';
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/resumes/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $resume_name = basename($_FILES['resume']['name']);
            $resume_path = $upload_dir . $resume_name;
            move_uploaded_file($_FILES['resume']['tmp_name'], $resume_path);
        }

        // Insert application into the `job_applications` table
        $sql_insert = "INSERT INTO job_applications (job_id, user_id, name, email, phone, resume_path, cover_letter, applied_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iisssss", $job_id, $user_id, $name, $email, $phone, $resume_path, $cover_letter);

        if ($stmt_insert->execute()) {
            $notification['status'] = 'success';
            $notification['message'] = 'Application submitted successfully!';
        } else {
            $notification['status'] = 'error';
            $notification['message'] = 'Error submitting application: ' . $stmt_insert->error;
        }

        $stmt_insert->close();
    }

    $stmt_check->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Job</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/job_info.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .apply-page {
            background: #f6f7fb;
            min-height: 100vh;
            position: fixed;
            width: 100vw;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 0;
            overflow-y: auto;
        }
        body {
            background: transparent !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .apply-page .container {
            max-width: 600px;
            margin: 120px auto 40px auto;
            padding: 0 20px;
        }
        .apply-page .card {
            background: #fcfcfd;
            border-radius: 18px;
            border: 1px solid #ececec;
            box-shadow: 0 8px 32px rgba(30, 58, 138, 0.08), 0 1.5px 6px rgba(30,58,138,0.03);
            padding: 36px 32px 32px 32px;
            position: relative;
            margin-top: 24px;
        }
        .apply-page .back-btn {
            position: absolute;
            top: 24px;
            left: 24px;
            background: #f6f7fb;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
            box-shadow: 0 2px 8px rgba(30,58,138,0.06);
            text-decoration: none !important; /* Remove underline */
        }
        .apply-page .back-btn:hover {
            background: #e8eaf3;
        }
        .apply-page h2 {
            text-align: center;
            color: #181c32;
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0 32px;
            padding-top: 10px;
            letter-spacing: -1px;
        }
        .apply-page .form-group {
            margin-bottom: 22px;
        }
        .apply-page label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #232a3d;
            letter-spacing: 0.01em;
        }
        .apply-page input[type="text"],
        .apply-page input[type="email"],
        .apply-page textarea {
            width: 100%;
            padding: 13px 14px;
            border: 1.5px solid #e3e7ef;
            border-radius: 8px;
            font-size: 1rem;
            background: #f7f9fc;
            color: #232a3d;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }
        .apply-page input[type="text"]:focus,
        .apply-page input[type="email"]:focus,
        .apply-page textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37,99,235,0.10);
            outline: none;
            background: #fff;
        }
        .apply-page textarea {
            resize: vertical;
            min-height: 110px;
            font-family: inherit;
        }
        .apply-page input[type="file"] {
            width: 100%;
            padding: 10px 0;
        }
        .apply-page .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        .apply-page .file-input-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }
        .apply-page .file-input-button {
            display: block;
            padding: 13px;
            background: #f7f9fc;
            border: 1.5px dashed #dbeafe;
            border-radius: 8px;
            text-align: center;
            color: #64748b;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
            font-weight: 500;
        }
        .apply-page .file-input-button:hover {
            background: #e8eaf3;
            border-color: #2563eb;
        }
        .apply-page .file-name {
            margin-top: 8px;
            font-size: 0.95rem;
            color: #64748b;
        }
        .apply-page button[type="submit"] {
            width: 100%;
            padding: 15px;
            background: linear-gradient(90deg, #2563eb 60%, #1e40af 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.08rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
            margin-top: 12px;
            box-shadow: 0 2px 8px rgba(37,99,235,0.08);
            letter-spacing: 0.01em;
        }
        .apply-page button[type="submit"]:hover {
            background: linear-gradient(90deg, #1e40af 60%, #2563eb 100%);
            box-shadow: 0 4px 16px rgba(37,99,235,0.12);
        }
        @media (max-width: 640px) {
            .apply-page .container {
                margin: 90px auto 20px auto;
                padding: 0 4px;
            }
            .apply-page .card {
                padding: 16px 6px 18px 6px;
            }
            .apply-page h2 {
                font-size: 1.3rem;
                margin-top: 18px;
            }
            .apply-page .back-btn {
                top: 10px;
                left: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="apply-page">
        <div class="container">
            <div class="card">
                <?php
                    // If referer is job_info.php, use it; else, build a link to job_info.php with job id and selected=true
                    $referer = $_SERVER['HTTP_REFERER'] ?? '';
                    $job_info_base = 'job_info.php';
                    if (strpos($referer, $job_info_base) !== false) {
                        $back_link = $referer;
                    } else {
                        $back_link = $job_info_base . '?id=' . urlencode($job['job_id']) . '&selected=true';
                    }
                ?>
                <a href="<?php echo htmlspecialchars($back_link); ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h2>Apply for <?php echo htmlspecialchars($job['title']); ?></h2>
                
                <form id="applicationForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo $existing_application['name'] ?? ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo $existing_application['email'] ?? ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" value="<?php echo $existing_application['phone'] ?? ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="cover_letter">Cover Letter</label>
                        <textarea id="cover_letter" name="cover_letter" required><?php echo $existing_application['cover_letter'] ?? ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="resume">Upload Resume (PDF only)</label>
                        <div class="file-input-wrapper">
                            <div class="file-input-button">
                                <i class="fas fa-upload"></i> Choose File
                            </div>
                            <input type="file" id="resume" name="resume" accept="application/pdf" required>
                        </div>
                        <div id="file-name" class="file-name"></div>
                    </div>

                    <button type="submit">Submit Application</button>
                </form>
            </div>
        </div>
    </div>

    <!-- SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Display SweetAlert notification based on PHP response
        <?php if ($notification['status'] === 'success'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?php echo $notification['message']; ?>',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = 'job_info.php?id=<?php echo urlencode($job['job_id']); ?>&selected=true'; // Redirect to job detail
            });
        <?php elseif ($notification['status'] === 'error'): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '<?php echo $notification['message']; ?>',
                confirmButtonText: 'OK'
            });
        <?php endif; ?>

        // Show selected filename
        document.getElementById('resume').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'No file selected';
            document.getElementById('file-name').textContent = fileName;
        });
    </script>
</body>
</html>