<?php
session_start();
date_default_timezone_set("Asia/Kolkata");
include "db.php";

// Check if a user session exists, although this page will now show all records
// This check can be kept for security/access control purposes if needed.
if (!isset($_SESSION['user_id'])) {
    echo "<!DOCTYPE html><html lang='hi'><head><title>Status</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'></head><body><div class='container mt-5'><div class='alert alert-danger'>आप लॉग इन नहीं हैं। कृपया एक क्विज़ शुरू करें।</div><a href='index.php' class='btn btn-primary'>वापस जाएं</a></div></body></html>";
    exit;
}

// Fetch all quiz records for ALL users
$stmt = $conn->prepare("SELECT * FROM quiz_records ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();

$quiz_records = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $quiz_records[] = $row;

    }
}
$stmt->close();
$conn->close();

?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .container { margin-top: 50px; }
        .card { box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 10px; }
        .card-header { background-color: #0d6efd; color: white; border-top-left-radius: 10px; border-top-right-radius: 10px; }
        .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>

    <div class="container">
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
        <div class="card">
            <div class="card-header text-center py-3">
                <h4 class="mb-0 fw-bold"><i class="bi bi-bar-chart-line me-2"></i> सभी क्विज़ परिणाम</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($quiz_records)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover text-center">
                            <thead class="table-primary">
                                <tr>
                                    <th scope="col">यूजर आईडी</th>
                                    <th scope="col">क्विज़ का प्रकार</th>
                                    <th scope="col">कुल प्रश्न</th>
                                    <th scope="col">स्कोर</th>
                                    <th scope="col">प्रतिशत</th>
                                    <th scope="col">दिनांक</th>
                                    <th scope="col">समीक्षा</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quiz_records as $record):
                                //echo $id = $record['id'];
                                 ?>

                                    <tr>
                                        <td><?= htmlspecialchars($_SESSION['user_id']); ?></td>
                                        <td>MCQ Quiz</td>
                                        <td><?= htmlspecialchars($record['total_questions']); ?></td>
                                        <td><?= htmlspecialchars($record['score']); ?></td>
                                        <td>
                                            <?php
                                                $percentage = ($record['total_questions'] > 0) ? round(($record['score'] / $record['total_questions']) * 100, 2) : 0;
                                                echo $percentage . '%';
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars(date('d-m-Y H:i A', strtotime($record['created_at']))); ?></td>
                                        <td>
                                            <a href="review_quiz.php?session_id=<?= htmlspecialchars($record['id']); ?>" class="btn btn-sm btn-info text-white">
                                                <i class="bi bi-eye-fill"></i> देखें
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        कोई क्विज़ रिकॉर्ड नहीं मिला। अभी एक क्विज़ शुरू करें!
                    </div>
                <?php endif; ?>

                <div class="d-grid gap-2 mt-4">
                    <a href="index.php" class="btn btn-primary"><i class="bi bi-house me-1"></i> वापस जाएं</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>