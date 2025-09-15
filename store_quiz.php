<?php
session_start();
header('Content-Type: application/json');
include "db.php";

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    if (isset($_POST['user_answers'], $_POST['score'], $_POST['total_questions'], $_POST['quiz_type'])) {
        $user_id = $_SESSION['user_id'];
        $user_answers = $_POST['user_answers'];
        $score = (int)$_POST['score'];
        $total_questions = (int)$_POST['total_questions'];
        $quiz_type = $_POST['quiz_type'];

        // You must use a transaction to ensure both inserts are successful or none are.
        $conn->begin_transaction();

        try {
            // Save quiz attempt to quiz_records
            $sql_quiz_record = "INSERT INTO quiz_records (user_id, quiz_type, total_questions, score, answered_questions) VALUES (?, ?, ?, ?, ?)";
            $stmt_quiz_record = $conn->prepare($sql_quiz_record);
            if ($stmt_quiz_record === false) {
                throw new Exception("Failed to prepare statement for quiz records: " . $conn->error);
            }
            $stmt_quiz_record->bind_param("ssiis", $user_id, $quiz_type, $total_questions, $score, $user_answers);
            
            if (!$stmt_quiz_record->execute()) {
                throw new Exception("Failed to execute statement for quiz records: " . $stmt_quiz_record->error);
            }

            $last_inserted_id = $stmt_quiz_record->insert_id;
            $stmt_quiz_record->close();

            $conn->commit();

            $response = ['status' => 'success', 'message' => 'Quiz submitted successfully.', 'session_id' => $last_inserted_id];

        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = "Transaction failed: " . $e->getMessage();
            error_log($response['message']); // Log the detailed error
        }

    } else {
        $response['message'] = 'Incomplete data received.';
    }
} else {
    $response['message'] = 'User not logged in or invalid request.';
}

echo json_encode($response);
$conn->close();
?>