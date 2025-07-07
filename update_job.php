<?php
session_start();
include 'sqlconnection.php';

// Set headers for JSON response
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not authenticated');
    }

    if (isset($_POST['action']) && $_POST['action'] === 'toggle_archive_job') {
        $job_id = intval($_POST['job_id']);
        $archive_action = $_POST['archive_action'];

        // Verify job belongs to user
        $check_sql = "SELECT job_id FROM job_listings WHERE job_id = $job_id AND posted_by_id = " . $_SESSION['user_id'];
        $check_result = $conn->query($check_sql);

        if ($check_result && $check_result->num_rows > 0) {
            $new_status = $archive_action === 'archive' ? 'archived' : 'active';

            $sql = "UPDATE job_listings SET 
                    status = '$new_status',
                    updated_at = NOW()
                    WHERE job_id = $job_id AND posted_by_id = " . $_SESSION['user_id'];

            if ($conn->query($sql)) {
                echo json_encode([
                    'success' => true,
                    'message' => $archive_action === 'archive' ? 'Job archived successfully!' : 'Job unarchived successfully!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error updating job status: ' . $conn->error
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'You do not have permission to modify this job'
            ]);
        }
        exit;
    }

    if (!isset($_POST['job_id']) || !isset($_POST['title'])) {
        throw new Exception('Missing required fields');
    }

    $job_id = intval($_POST['job_id']);
    $user_id = intval($_SESSION['user_id']);
    $title = $conn->real_escape_string($_POST['title']);
    $company = $conn->real_escape_string($_POST['company']);
    $location = $conn->real_escape_string($_POST['location']);
    $employment_type = $conn->real_escape_string($_POST['employment_type']);
    $job_type = $conn->real_escape_string($_POST['job_type']);
    $category = $conn->real_escape_string($_POST['category']);
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
            category = '$category',
            salary_range = '$salary_range',
            description = '$description',
            requirements = '$requirements',
            contact_email = '$contact_email',
            status = '$status',
            updated_at = NOW()
            WHERE job_id = $job_id 
            AND posted_by_id = " . $_SESSION['user_id'];

    $response = [];
    
    if ($conn->query($sql)) {
        $response['success'] = true;
        $response['message'] = 'Job updated successfully!';
    } else {
        $response['success'] = false;
        $response['message'] = 'Error updating job: ' . $conn->error;
    }
    
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400); // Set HTTP status code to 400 Bad Request
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
