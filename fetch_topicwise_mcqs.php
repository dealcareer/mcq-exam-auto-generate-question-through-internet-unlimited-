<?php
header('Content-Type: application/json');
include "db.php"; // DB connection

// Sanitize topic parameter
$topic = isset($_GET['topic']) ? htmlspecialchars($_GET['topic'], ENT_QUOTES, 'UTF-8') : '';
if (!$topic) {
    echo json_encode(["success" => false, "message" => "Topic not provided"]);
    exit;
}

// Get user IP for attempt tracking
$user_ip = $_SERVER['REMOTE_ADDR'];

// SQL query to fetch MCQs and their latest attempt (if any) for the user
$sql = "
    SELECT 
        m.id, m.topic, m.subtopic, m.question_en, m.question_hi, 
        m.options_en, m.options_hi, m.answer_en, m.explanation_en, m.explanation_hi,
        a.selected_option, a.is_correct
    FROM ccc_mcq m
    LEFT JOIN (
        SELECT mcq_id, selected_option, is_correct
        FROM mcq_attempts
        WHERE user_ip = ?
        AND created_at = (
            SELECT MAX(created_at)
            FROM mcq_attempts
            WHERE mcq_id = mcq_attempts.mcq_id AND user_ip = ?
        )
    ) a ON m.id = a.mcq_id
    WHERE m.topic = ?
    ORDER BY m.id ASC
";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    error_log("Failed to prepare statement: " . $conn->error);
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}

// Bind parameters (user_ip, user_ip, topic)
$stmt->bind_param("sss", $user_ip, $user_ip, $topic);
$stmt->execute();
$result = $stmt->get_result();

$mcqs_by_subtopic = [];

while ($row = $result->fetch_assoc()) {
    $sub = $row['subtopic'] ?: "General";

    // Decode JSON options
    $options_en = json_decode($row['options_en'], true);
    $options_hi = json_decode($row['options_hi'], true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error for mcq_id {$row['id']}: " . json_last_error_msg());
        continue; // Skip invalid JSON
    }

    // Determine if the MCQ was attempted
    $attempted = !is_null($row['selected_option']);
    $selected_option = $row['selected_option'] ?? "";
    $is_correct = $row['is_correct'] !== null ? (bool)$row['is_correct'] : null;

    $mcq = [
        "id" => $row['id'],
        "question_en" => $row['question_en'],
        "question_hi" => $row['question_hi'],
        "options_en" => $options_en,
        "options_hi" => $options_hi,
        "answer_en" => $row['answer_en'],
        "explanation_en" => $row['explanation_en'],
        "explanation_hi" => $row['explanation_hi'],
        "attempted" => $attempted,
        "selected_option" => $selected_option,
        "is_correct" => $is_correct
    ];

    $mcqs_by_subtopic[$sub][] = $mcq;
}

$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "mcqs_by_subtopic" => $mcqs_by_subtopic
]);
?>