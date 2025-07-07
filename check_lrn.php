<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'sqlconnection.php';

    $lrn = $_POST['lrn'];

    // Check if LRN exists in the users table
    $query = "SELECT lrn FROM users WHERE lrn = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $lrn);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['exists' => false, 'duplicate' => true, 'message' => 'This LRN is already registered in our system']);
        $stmt->close();
        $conn->close();
        exit;
    }

    // Check LRN in alumni_data
    $query = "SELECT sheet_data FROM alumni_data";
    $result = $conn->query($query);
    $found = false;
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sheetData = json_decode($row['sheet_data'], true);
            foreach ($sheetData as $record) {
                if (isset($record['LRN']) && trim($record['LRN']) === trim($lrn)) {
                    $found = true;
                    break 2;
                }
            }
        }
    }

    if ($found) {
        echo json_encode(['exists' => true, 'duplicate' => false, 'message' => 'LRN verified']);
    } else {
        echo json_encode(['exists' => false, 'duplicate' => false, 'message' => 'LRN not found in alumni records']);
    }
    $conn->close();
}
?>
