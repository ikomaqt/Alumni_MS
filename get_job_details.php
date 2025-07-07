<?php
include 'sqlconnection.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    die(json_encode(['error' => 'Unauthorized access']));
}

$job_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM job_listings WHERE job_id = ? AND posted_by_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $job_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($job = $result->fetch_assoc()) {
    echo json_encode($job);
} else {
    echo json_encode(['error' => 'Job not found']);
}

if (isset($_GET['action']) && $_GET['action'] === 'get_job_form') {
    $job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
    
    if ($job_id > 0) {
        $sql = "SELECT * FROM job_listings WHERE job_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $job = $result->fetch_assoc();
            
            // Output job edit form HTML
            echo '<form id="editJobForm" method="POST">
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
                        <label for="employment_type">Employment Type</label>
                        <select id="employment_type" name="employment_type" required>
                            <option value="Full-time" ' . ($job['employment_type'] == 'Full-time' ? 'selected' : '') . '>Full-time</option>
                            <option value="Part-time" ' . ($job['employment_type'] == 'Part-time' ? 'selected' : '') . '>Part-time</option>
                            <option value="Contract" ' . ($job['employment_type'] == 'Contract' ? 'selected' : '') . '>Contract</option>
                            <option value="Freelance" ' . ($job['employment_type'] == 'Freelance' ? 'selected' : '') . '>Freelance</option>
                            <option value="Internship" ' . ($job['employment_type'] == 'Internship' ? 'selected' : '') . '>Internship</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="job_type">Job Type</label>
                        <select id="job_type" name="job_type" required>
                            <option value="On-site" ' . ($job['job_type'] == 'On-site' ? 'selected' : '') . '>On-site</option>
                            <option value="Remote" ' . ($job['job_type'] == 'Remote' ? 'selected' : '') . '>Remote</option>
                            <option value="Hybrid" ' . ($job['job_type'] == 'Hybrid' ? 'selected' : '') . '>Hybrid</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Job Description</label>
                    <textarea id="description" name="description" rows="4" required>' . htmlspecialchars($job['description']) . '</textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal(\'editJobModal\')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Job</button>
                </div>
            </form>';
        } else {
            echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Job not found!</div>";
        }
    }
}
?>
