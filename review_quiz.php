<?php
session_start();
date_default_timezone_set("Asia/Kolkata");
include "db.php"; // Ensure this file has your database connection logic

// Get session_id from URL parameter
$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

if ($session_id === 0) {
    echo "<!DOCTYPE html><html lang='hi'><head><title>Error</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'></head><body><div class='container mt-5'><div class='alert alert-danger'>त्रुटि: क्विज़ समीक्षा के लिए कोई सेशन आईडी प्रदान नहीं की गई।</div><a href='status.php' class='btn btn-primary mt-3'>वापस जाएं</a></div></body></html>";
    exit;
}

// Fetch quiz record from quiz_records table
$quiz_record = null;
$stmt = $conn->prepare("SELECT total_questions, score, answered_questions FROM quiz_records WHERE id = ?");
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $session_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $quiz_record = $result->fetch_assoc();
}
$stmt->close();

if ($quiz_record === null) {
    echo "<!DOCTYPE html><html lang='hi'><head><title>Error</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'></head><body><div class='container mt-5'><div class='alert alert-warning'>त्रुटि: इस आईडी के साथ कोई क्विज़ रिकॉर्ड नहीं मिला।</div><a href='status.php' class='btn btn-primary mt-3'>वापस जाएं</a></div></body></html>";
    exit;
}

$answered_questions = json_decode($quiz_record['answered_questions'], true);
$score = $quiz_record['score'];
$total_questions = $quiz_record['total_questions'];

// Extract question IDs to fetch full question details
$question_ids = array_map(function($q) {
    return $q['question_id'];
}, $answered_questions);

$questions_data = [];
if (!empty($question_ids)) {
    $placeholders = implode(',', array_fill(0, count($question_ids), '?'));
    $sql_questions = "SELECT * FROM ccc_mcq WHERE id IN ($placeholders) ORDER BY FIELD(id, $placeholders)";
   
    
    // Create a new prepared statement
    $stmt_questions = $conn->prepare($sql_questions);
    if ($stmt_questions === false) {
        die("Error preparing statement for questions: " . $conn->error);
    }
    
    // Bind parameters dynamically
    $types = str_repeat('i', count($question_ids) * 2);
    $all_params = array_merge($question_ids, $question_ids);
    $stmt_questions->bind_param($types, ...$all_params);
    $stmt_questions->execute();
    $result_questions = $stmt_questions->get_result();
    
    while ($row = $result_questions->fetch_assoc()) {
        $questions_data[$row['id']] = $row;
    }
    $stmt_questions->close();
}
$conn->close();

// Combine answered questions with full question data
$review_questions = [];
foreach ($answered_questions as $answered_q) {
    $q_id = $answered_q['question_id'];
    if (isset($questions_data[$q_id])) {
        $full_question = $questions_data[$q_id];
        $full_question['user_answer_key'] = $answered_q['user_answer_key'];
        $full_question['is_correct'] = $answered_q['is_correct'];
        $review_questions[] = $full_question;
    }
}
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .card { box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 10px; margin-bottom: 20px; }
        .card-header { background-color: #0d6efd; color: white; border-top-left-radius: 10px; border-top-right-radius: 10px; }
        .score-box { background-color: #e9ecef; padding: 20px; border-radius: 10px; text-align: center; }
        .score-text { font-size: 2.5rem; font-weight: bold; }
        .question-box { padding: 20px; border-bottom: 1px solid #dee2e6; }
        .question-hi { font-weight: bold; font-size: 1.25rem; margin-bottom: 15px; }
        .option-label { display: block; padding: 10px; border-radius: 8px; margin-bottom: 10px; }
        .correct-option { background-color: #d4edda; border: 1px solid #c3e6cb; }
        .incorrect-option { background-color: #f8d7da; border: 1px solid #f5c6cb; }
        .correct-answer-icon::before { content: "✔"; color: #155724; font-size: 1.2em; margin-right: 5px; }
        .incorrect-answer-icon::before { content: "✖"; color: #721c24; font-size: 1.2em; margin-right: 5px; }
        .explanation { background-color: #f2f2f2; padding: 15px; border-radius: 8px; margin-top: 15px; }
    </style>
</head>
<body>

<div class="container py-4">
                <div class="alert fw-bold fs-5 text-center d-flex align-items-center justify-content-center mb-4">
        <div class="row g-3 justify-content-center w-100">
        <div class="col-12 col-md-6 d-flex">
            <div class="card shadow-sm w-100 p-3 d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-pc-display text-primary fs-4"></i>
                    <h5 class="mb-0 fw-bold">MCQ Quiz</h5>
                </div>
                <form action="take_quiz.php" method="post" class="d-flex flex-column flex-md-row align-items-center gap-2 w-100">
                    <div class="input-group flex-fill">
                        <span class="input-group-text">प्रश्न</span>
                        <input type="number" name="num_questions" class="form-control" value="10" min="1" max="100" required>
                    </div>
                    <div class="input-group flex-fill">
                        <span class="input-group-text">टाइमर</span>
                        <input type="number" name="timer_minutes" class="form-control" value="10" min="1" max="60" required>
                    </div>
                    <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center mt-2 mt-md-0 flex-shrink-0">
                        <i class="bi bi-play-fill me-1"></i> शुरू करें
                    </button>
                </form>
            </div>
        </div>
        
        <div class="col-12 col-md-6 d-flex">
            <div class="card shadow-sm w-100 p-3 d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-bar-chart-line text-success fs-4"></i>
                    <h5 class="mb-0 fw-bold">अपनी प्रगति देखें</h5>
                </div>
                <a href="status.php" class="btn btn-success d-flex align-items-center justify-content-center mt-2 mt-md-0 flex-shrink-0">
                    <i class="bi bi-eye-fill me-1"></i> स्थिति देखें
                </a>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
    </div>
</div>
    <h2 class="text-center mb-4">क्विज़ की समीक्षा</h2>
    <div class="score-box mb-4">
        <h5 class="mb-2">आपका स्कोर:</h5>
        <p class="score-text text-primary"><?= htmlspecialchars($score) ?> / <?= htmlspecialchars($total_questions) ?></p>
        <?php
        $percentage = ($total_questions > 0) ? ($score / $total_questions) * 100 : 0;
        $alert_class = 'alert-info';
        if ($percentage >= 80) {
            $alert_class = 'alert-success';
        } elseif ($percentage >= 50) {
            $alert_class = 'alert-warning';
        } else {
            $alert_class = 'alert-danger';
        }
        ?>
        <div class="alert <?= $alert_class ?> mt-3">
            स्कोर प्रतिशत: <strong><?= number_format($percentage, 2) ?>%</strong>
        </div>
    </div>
    
     <div class="card">
        <div class="card-body">
            <?php foreach ($review_questions as $index => $question): ?>
                <div class="question-box">
                    <p class="question-hi">प्रश्न <?= $index + 1 ?>: <?= htmlspecialchars($question['question_hi']) ?></p>
                    <p class="question-en text-muted">Question <?= $index + 1 ?>: <?= htmlspecialchars($question['question_en']) ?></p>
                    <?php
                    $options_hi = json_decode($question['options_hi'], true);
                    $options_en = json_decode($question['options_en'], true);
                    $correct_key = $question['answer_en'];
                    $user_key = $question['user_answer_key'];
                    
                    foreach ($options_hi as $key => $option_hi):
                        $option_key = chr(65 + $key);
                        $class = '';
                        $icon = '';
                        $option_en = $options_en[$key];
                        
                        if ($option_key === $correct_key) {
                            $class = 'correct-option';
                            $icon = '<span class="correct-answer-icon"></span>';
                        } elseif ($option_key === $user_key) {
                            $class = 'incorrect-option';
                            $icon = '<span class="incorrect-answer-icon"></span>';
                        }
                    ?>
                    <div class="option-label <?= $class ?>">
                        <?= $icon ?>
                        <div class="option-hi">
                            <?= htmlspecialchars($option_key) ?>. <?= htmlspecialchars(str_replace($option_key . '. ', '', $option_hi)) ?>
                        </div>
                        <div class="option-en text-muted">
                            <?= htmlspecialchars($option_key) ?>. <?= htmlspecialchars(str_replace($option_key . '. ', '', $option_en)) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="explanation mt-3">
                        <p class="fw-bold">व्याख्या / Explanation:</p>
                        <p><?= nl2br(htmlspecialchars($question['explanation_hi'])) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="d-grid gap-2 col-6 mx-auto mt-4">
        <a href="index.php" class="btn btn-primary btn-lg">वापस जाएं / Go Back</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>