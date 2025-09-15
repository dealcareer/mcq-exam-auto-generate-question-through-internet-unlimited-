<?php
// Include the database connection file
include "db.php";

// Set the content type for the response
header('Content-Type: application/json');

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get the raw POST data
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Check if JSON decoding was successful and required data is present
if ($data === null || !isset($data['mcq_id'], $data['selected_option'], $data['correct_option'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid or incomplete JSON data']);
    exit;
}

// Sanitize and get the data
$mcq_id = intval($data['mcq_id']);
$selected_option = htmlspecialchars($data['selected_option'], ENT_QUOTES, 'UTF-8');
$correct_option = htmlspecialchars($data['correct_option'], ENT_QUOTES, 'UTF-8');
$user_ip = $_SERVER['REMOTE_ADDR'];
$user_agent = isset($data['user_agent']) ? htmlspecialchars($data['user_agent'], ENT_QUOTES, 'UTF-8') : 'Unknown';
$is_correct = ($selected_option === $correct_option) ? 1 : 0;

// Prepare the SQL statement to insert the data
// Include created_at explicitly using NOW() since it's in the table schema
$sql = "INSERT INTO mcq_attempts (mcq_id, user_ip, user_agent, selected_option, correct_option, is_correct, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";

try {
    // Check if the database connection object exists
    if (!isset($conn) || !is_a($conn, 'mysqli')) {
        throw new Exception("Database connection object not found or invalid");
    }

    // Prepare the statement
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Failed to prepare the statement: " . $conn->error);
    }

    // Bind the parameters (issssi for mcq_id, user_ip, user_agent, selected_option, correct_option, is_correct)
    $stmt->bind_param("issssi", $mcq_id, $user_ip, $user_agent, $selected_option, $correct_option, $is_correct);

    // Execute the statement
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Attempt saved successfully']);
    } else {
        error_log("Error saving MCQ attempt: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to save attempt: ' . $stmt->error]);
    }

    // Close the statement
    $stmt->close();
} catch (Exception $e) {
    error_log("Exception in save_attempt.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
} finally {
    // Ensure the connection is always closed
    if (isset($conn) && is_a($conn, 'mysqli')) {
        $conn->close();
    }
}
?>