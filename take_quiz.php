<?php
session_start();
date_default_timezone_set("Asia/Kolkata");
include "db.php";

// Fetch quiz configuration from POST request
$num_questions = isset($_POST['num_questions']) ? (int)$_POST['num_questions'] : 10;
$timer_minutes = isset($_POST['timer_minutes']) ? (int)$_POST['timer_minutes'] : 10;
$quiz_type = 'ccc_mcq'; // Set the quiz type

// Validate input
if ($num_questions < 1) {
    $num_questions = 10;
}
if ($timer_minutes < 1 || $timer_minutes > 60) {
    $timer_minutes = 10;
}

// Assign a user ID. If not logged in, create a temporary guest ID.
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 'guest_' . uniqid();
}
$user_id = $_SESSION['user_id'];

// Resetting the session for a new quiz
unset($_SESSION['quiz']);

// Fetch questions already answered by the user to avoid repetition
$answered_ids = [];
$stmt = $conn->prepare("SELECT answered_questions FROM quiz_records WHERE user_id = ? AND quiz_type = ?");
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("ss", $user_id, $quiz_type);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $answered_qs = json_decode($row['answered_questions'], true);
    if (is_array($answered_qs)) {
        foreach ($answered_qs as $q) {
            $answered_ids[] = $q['question_id'];
        }
    }
}
$stmt->close();
$answered_ids = array_unique($answered_ids);

// Prepare query to fetch new questions from the ccc_mcq table
// Added 'question_en' and 'options_en' to the query
$query_parts = ["SELECT id, question_hi, question_en, options_hi, options_en, answer_en, explanation_hi FROM ccc_mcq"];
$params = [];
$types = "";

if (!empty($answered_ids)) {
    $placeholders = implode(',', array_fill(0, count($answered_ids), '?'));
    $query_parts[] = "WHERE id NOT IN ($placeholders)";
    $params = $answered_ids;
    $types = str_repeat('i', count($answered_ids));
}

$query_parts[] = "ORDER BY RAND() LIMIT ?";
$final_query = implode(' ', $query_parts);

if (!empty($params)) {
    $types .= 'i';
    $params[] = $num_questions;
} else {
    $params[] = $num_questions;
    $types = 'i';
}

$stmt = $conn->prepare($final_query);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$questions = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['options_hi'] = json_decode($row['options_hi'], true);
        $row['options_en'] = json_decode($row['options_en'], true); // Decode English options
        $questions[] = $row;
    }
}
$stmt->close();
$conn->close();

if (empty($questions)) {
    echo "<!DOCTYPE html><html lang='hi'><head><title>No Questions</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'></head><body><div class='container mt-5'><div class='alert alert-info'>क्षमा करें, इस क्विज़ के लिए कोई नया सवाल नहीं बचा है। कृपया बाद में प्रयास करें।</div><a href='status.php' class='btn btn-primary'>वापस जाएं</a></div></body></html>";
    exit;
}

// Store quiz data in the session
$_SESSION['quiz'] = [
    'questions' => $questions,
    'num_questions' => count($questions),
    'timer_minutes' => $timer_minutes,
    'user_answers' => [],
    'start_time' => time(),
    'quiz_type' => $quiz_type,
];

$questions_json = json_encode($questions);
$timer_seconds = $timer_minutes * 60;
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCC MCQ Quiz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .main-container { display: flex; gap: 20px; }
        .quiz-content { flex: 1; }
        .sidebar { width: 300px; position: sticky; top: 20px; height: calc(100vh - 40px); overflow-y: auto; background-color: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .timer-container { padding: 15px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 20px; background-color: #f8f9fa; }
        .timer-text { font-size: 1.5rem; font-weight: bold; color: #212529; }
        .progress { height: 10px; margin-top: 10px; }
        .question-nav-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-top: 20px; }
        .question-nav-btn { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background-color: #e9ecef; color: #495057; font-weight: bold; text-decoration: none; transition: background-color 0.2s, transform 0.2s; border: 1px solid #dee2e6; }
        .question-nav-btn:hover { transform: scale(1.1); }
        .question-nav-btn.answered { background-color: #198754; color: #fff; }
        .question-nav-btn.current { background-color: #0d6efd; color: #fff; }
        .quiz-card { border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .quiz-card-header { background-color: #0d6efd; color: white; border-top-left-radius: 10px; border-top-right-radius: 10px; }
        .question-number { font-size: 1.25rem; font-weight: bold; }
        .option-label { cursor: pointer; padding: 10px; border-radius: 8px; border: 1px solid #dee2e6; transition: background-color 0.2s, border-color 0.2s; margin-bottom: 10px; display: block; }
        .option-label:hover { background-color: #e9ecef; }
        .option-label.selected { background-color: #e2f4ff; border-color: #0d6efd; font-weight: bold; }
        .form-check-input { display: none; }
        .submit-btn { padding: 10px 30px; font-size: 1.1rem; }
        .nav-arrow-container {
            position: fixed;
            right: 340px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            opacity: 1;
            transition: opacity 0.5s ease-in-out;
        }
        .nav-arrow-container.hidden {
            opacity: 0;
            pointer-events: none;
        }
        .nav-arrow-btn {
            background-color: #d9534f;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .nav-arrow-btn:hover {
            background-color: #c9302c;
        }
        .nav-arrow-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        @media (max-width: 991px) { .main-container { flex-direction: column; } .sidebar { width: 100%; height: auto; position: static; margin-bottom: 20px; } }
        @media (max-width: 576px) { .question-nav-grid { grid-template-columns: repeat(4, 1fr); } }
        .question-hi, .question-en { font-weight: bold; font-size: 1.2rem; margin-bottom: 5px; }
        .option-hi, .option-en { font-weight: normal; font-size: 1rem; }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <div class="main-container">
        <div class="quiz-content" id="quizContent">
            <div class="card quiz-card">
                <div class="card-header quiz-card-header fw-bold">
                    <i class="bi bi-patch-question me-2"></i> CCC MCQ Quiz
                </div>
                <div class="card-body">
                    <form action="store_quiz.php" method="post" id="quizForm">
                        <div id="questionsContainer">
                        </div>
                        <div class="d-grid mt-4">
                            <button type="button" id="finalSubmitBtn" class="btn btn-danger submit-btn">
                                <i class="bi bi-check-circle-fill me-1"></i> Final Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="sidebar">
            <div class="nav-arrow-container" id="nav-arrow-container">
                <button id="scroll-top-btn" class="nav-arrow-btn" title="ऊपर जाएं"><i class="bi bi-chevron-double-up"></i></button>
                <button id="prev-question-btn" class="nav-arrow-btn" title="पिछला प्रश्न"><i class="bi bi-arrow-up"></i></button>
                <button id="next-question-btn" class="nav-arrow-btn" title="अगला प्रश्न"><i class="bi bi-arrow-down"></i></button>
                <button id="scroll-bottom-btn" class="nav-arrow-btn" title="नीचे जाएं"><i class="bi bi-chevron-double-down"></i></button>
            </div>

            <div class="timer-container text-center">
                <h6 class="fw-bold">शेष समय</h6>
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="bi bi-clock me-2" style="font-size: 1.5rem;"></i>
                    <div class="timer-text" id="quizTimer">--:--</div>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar" role="progressbar" id="timerProgressBar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
            
            <h6 class="fw-bold mt-4">प्रश्नों की स्थिति</h6>
            <p class="small text-muted mb-3">🟢 Attempted, ⚪ Unattempted</p>
            <div class="question-nav-grid" id="questionNavGrid">
            </div>
            <div class="d-grid mt-4">
                <button type="button" id="sidebarSubmitBtn" class="btn btn-danger submit-btn">
                    <i class="bi bi-check-circle-fill me-1"></i> Final Submit
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="timeUpModal" tabindex="-1" aria-labelledby="timeUpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="timeUpModalLabel">समय समाप्त!</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-center">आपका समय समाप्त हो गया है। आपका क्विज़ अपने आप सबमिट हो रहा है।</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">ठीक है</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const questionsData = <?php echo $questions_json; ?>;
    const totalQuestions = questionsData.length;
    const totalDuration = <?php echo $timer_seconds; ?>;
    const quizForm = document.getElementById('quizForm');
    const questionsContainer = document.getElementById('questionsContainer');
    const timerDisplay = document.getElementById('quizTimer');
    const timerProgressBar = document.getElementById('timerProgressBar');
    const questionNavGrid = document.getElementById('questionNavGrid');
    const finalSubmitBtn = document.getElementById('finalSubmitBtn');
    const sidebarSubmitBtn = document.getElementById('sidebarSubmitBtn');
    const quizContent = document.getElementById('quizContent');
    const scrollTopBtn = document.getElementById('scroll-top-btn');
    const prevQuestionBtn = document.getElementById('prev-question-btn');
    const nextQuestionBtn = document.getElementById('next-question-btn');
    const scrollBottomBtn = document.getElementById('scroll-bottom-btn');
    const navArrowContainer = document.getElementById('nav-arrow-container');

    let timeRemaining = totalDuration;
    let quizTimerId;
    let currentQuestionIndex = 0;
    let userAnswers = {};
    let activityTimer;

    function renderQuestions() {
        questionsContainer.innerHTML = questionsData.map((question, index) => {
            const optionsHtml = question.options_hi.map((option_hi, key) => {
                const cleanedOption_hi = option_hi.replace(/^[A-D][).]\s*/, '');
                const cleanedOption_en = question.options_en[key].replace(/^[A-D][).]\s*/, '');
                return `
                    <div class="form-check">
                        <input class="form-check-input question-radio" type="radio" name="q_${question.id}" id="option_${question.id}_${key}" value="${String.fromCharCode(65 + key)}">
                        <label class="form-check-label option-label" for="option_${question.id}_${key}">
                            <div class="option-hi">${String.fromCharCode(65 + key)}. ${cleanedOption_hi}</div>
                            <div class="option-en text-muted mt-1" style="font-size: 0.9rem;">${String.fromCharCode(65 + key)}. ${cleanedOption_en}</div>
                        </label>
                    </div>
                `;
            }).join('');

            return `
                <div class="mb-5 question-container" id="question_${index + 1}">
                    <div class="question-number">Question ${index + 1}.</div>
                    <div class="question-hi">${question.question_hi}</div>
                    <div class="question-en text-muted mb-3">${question.question_en}</div>
                    ${optionsHtml}
                </div>
            `;
        }).join('');

        questionNavGrid.innerHTML = questionsData.map((_, index) => `
            <a href="#question_${index + 1}" class="question-nav-btn" data-question-id="${index + 1}">${index + 1}</a>
        `).join('');
    }

    function formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
    }

    function updateTimer() {
        if (timeRemaining <= 0) {
            timerDisplay.textContent = '00:00';
            timerProgressBar.style.width = '0%';
            timerProgressBar.classList.remove('bg-success', 'bg-warning');
            timerProgressBar.classList.add('bg-danger');
            clearInterval(quizTimerId);
            
            const timeUpModal = new bootstrap.Modal(document.getElementById('timeUpModal'));
            timeUpModal.show();
            setTimeout(() => {
                submitQuiz();
            }, 2000);
            return;
        }

        timerDisplay.textContent = formatTime(timeRemaining);
        const progressPercentage = (timeRemaining / totalDuration) * 100;
        timerProgressBar.style.width = `${progressPercentage}%`;

        if (progressPercentage <= 20) {
            timerProgressBar.classList.remove('bg-success', 'bg-warning');
            timerProgressBar.classList.add('bg-danger');
        } else if (progressPercentage <= 50) {
            timerProgressBar.classList.remove('bg-success', 'bg-danger');
            timerProgressBar.classList.add('bg-warning');
        } else {
            timerProgressBar.classList.remove('bg-warning', 'bg-danger');
            timerProgressBar.classList.add('bg-success');
        }
        
        timeRemaining--;
    }

    function highlightCurrentQuestion() {
        document.querySelectorAll('.question-nav-btn').forEach(btn => btn.classList.remove('current'));
        const currentBtn = document.querySelector(`.question-nav-btn[data-question-id='${currentQuestionIndex + 1}']`);
        if (currentBtn) {
            currentBtn.classList.add('current');
        }
        updateNavigationButtons();
    }
    
    function handleScroll() {
        const questionContainers = document.querySelectorAll('.question-container');
        let minDistance = Infinity;
        let newCurrentIndex = currentQuestionIndex;

        questionContainers.forEach((container, index) => {
            const rect = container.getBoundingClientRect();
            const distance = Math.abs(rect.top + rect.height / 2 - window.innerHeight / 2);
            if (distance < minDistance) {
                minDistance = distance;
                newCurrentIndex = index;
            }
        });

        if (newCurrentIndex !== currentQuestionIndex) {
            currentQuestionIndex = newCurrentIndex;
            highlightCurrentQuestion();
        }
    }

    function updateNavigationButtons() {
        prevQuestionBtn.disabled = currentQuestionIndex === 0;
        nextQuestionBtn.disabled = currentQuestionIndex === totalQuestions - 1;
    }

    function scrollToQuestion(index) {
        const questionElement = document.getElementById(`question_${index + 1}`);
        if (questionElement) {
            const rect = questionElement.getBoundingClientRect();
            const headerOffset = document.querySelector('nav') ? document.querySelector('nav').offsetHeight : 60;
            const viewportHeight = window.innerHeight;
            const questionHeight = rect.height;
            const scrollOffset = rect.top + window.scrollY - headerOffset - ((viewportHeight - questionHeight) / 2);

            window.scrollTo({
                top: scrollOffset > 0 ? scrollOffset : 0,
                behavior: 'smooth'
            });
        }
    }

    function submitQuiz() {
        clearInterval(quizTimerId);
        
        const answeredQuestions = [];
        let score = 0;
        
        questionsData.forEach(question => {
            const selectedOption = document.querySelector(`input[name="q_${question.id}"]:checked`);
            const user_answer_key = selectedOption ? selectedOption.value : null;
            const is_correct = user_answer_key !== null && user_answer_key === question.answer_en;
            if (is_correct) {
                score++;
            }
            
            let user_answer_text_hi = null;
            if (user_answer_key !== null) {
                const optionIndex = user_answer_key.charCodeAt(0) - 'A'.charCodeAt(0);
                user_answer_text_hi = question.options_hi[optionIndex];
            }
            
            answeredQuestions.push({
                question_id: question.id,
                user_answer_key: user_answer_key,
                user_answer_text_hi: user_answer_text_hi,
                is_correct: is_correct
            });
        });

        const formData = new FormData();
        formData.append('score', score);
        formData.append('total_questions', totalQuestions);
        formData.append('user_answers', JSON.stringify(answeredQuestions));
        formData.append('quiz_type', 'ccc_mcq');

        fetch('store_quiz.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Redirect to the review page with the session ID
                window.location.href = `review_quiz.php?session_id=${data.session_id}`;
            } else {
                console.error('Server error:', data.message);
                alert('Error submitting quiz: ' + data.message + '. Please check the console for details.');
            }
        })
        .catch(error => {
            console.error('Error submitting quiz:', error);
            alert('Error submitting quiz: ' + error.message + '. Please check the console for details.');
        });
    }

    function hideNavArrows() {
        if (navArrowContainer) {
            navArrowContainer.classList.add('hidden');
        }
    }

    function showNavArrows() {
        if (navArrowContainer) {
            navArrowContainer.classList.remove('hidden');
            clearTimeout(activityTimer);
            activityTimer = setTimeout(hideNavArrows, 1000); // Hide after 1 second
        }
    }

    quizForm.addEventListener('change', (event) => {
        if (event.target.classList.contains('question-radio')) {
            const questionContainer = event.target.closest('.question-container');
            questionContainer.querySelectorAll('.option-label').forEach(label => label.classList.remove('selected'));
            const label = event.target.nextElementSibling;
            if (label) {
                label.classList.add('selected');
            }
            const questionIndex = parseInt(questionContainer.id.split('_')[1], 10) - 1;
            const navButton = document.querySelector(`.question-nav-btn[data-question-id='${questionIndex + 1}']`);
            if (navButton) {
                navButton.classList.add('answered');
            }
            showNavArrows();
        }
    });

    questionNavGrid.addEventListener('click', (event) => {
        const btn = event.target.closest('.question-nav-btn');
        if (btn) {
            const questionId = parseInt(btn.getAttribute('data-question-id'), 10) - 1;
            if (questionId >= 0 && questionId < totalQuestions) {
                currentQuestionIndex = questionId;
                scrollToQuestion(currentQuestionIndex);
                highlightCurrentQuestion();
                showNavArrows();
            }
        }
    });

    finalSubmitBtn.addEventListener('click', () => {
        submitQuiz();
    });

    sidebarSubmitBtn.addEventListener('click', () => {
        submitQuiz();
    });

    scrollTopBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        showNavArrows();
    });

    prevQuestionBtn.addEventListener('click', () => {
        if (currentQuestionIndex > 0) {
            currentQuestionIndex--;
            scrollToQuestion(currentQuestionIndex);
            highlightCurrentQuestion();
            showNavArrows();
        }
    });

    nextQuestionBtn.addEventListener('click', () => {
        if (currentQuestionIndex < totalQuestions - 1) {
            currentQuestionIndex++;
            scrollToQuestion(currentQuestionIndex);
            highlightCurrentQuestion();
            showNavArrows();
        }
    });

    scrollBottomBtn.addEventListener('click', () => {
        window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        showNavArrows();
    });

    document.addEventListener('DOMContentLoaded', () => {
        renderQuestions();
        quizTimerId = setInterval(updateTimer, 1000);
        window.addEventListener('scroll', () => {
            handleScroll();
            showNavArrows();
        });
        handleScroll();
        if (navArrowContainer) {
            showNavArrows();
            document.addEventListener('mousemove', showNavArrows);
            document.addEventListener('keypress', showNavArrows);
            document.addEventListener('click', showNavArrows);
            document.addEventListener('touchstart', showNavArrows);
        } else {
            console.error('Navigation arrow container not found');
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>