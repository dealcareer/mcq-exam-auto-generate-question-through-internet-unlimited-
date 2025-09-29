<?php
include "db.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Computer Course Modules</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .editable-topic, .editable-module-name {
        min-height: 50px;
        border: 1px dashed #ccc;
        padding: 5px 8px;
        border-radius: 4px;
        background: #fff;
        cursor: text;
        transition: background 0.2s;
    }
    .editable-topic:focus, .editable-module-name:focus {
        background: #e8f0fe;
        outline: none;
    }
  </style>
</head>
<body class="bg-light">

<div class="container my-5">
  <h2 class="text-center mb-4">📘 Computer Course Modules</h2>
<div class="alert fw-bold fs-5 text-center d-flex align-items-center justify-content-center mb-4">
    <div class="row g-3 justify-content-center w-100">
        <div class="col-12 col-md-6 d-flex">
            <div class="card shadow-sm w-100 p-3 d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-pc-display text-primary fs-4"></i>
                    <h5 class="mb-0 fw-bold">CCC Quiz</h5>
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
        </div>
    </div>
</div>
  <div class="table-responsive shadow-lg rounded">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-primary text-center">
        <tr>
          <th style="width: 10%;">Module No.</th>
          <th style="width: 25%;">Module Name</th>
          <th style="width: 50%;">Topics Covered</th>
          <th style="width: 15%;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $sql = "SELECT module_no, module_name, topics FROM modules ORDER BY module_no ASC";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $moduleNo = $row['module_no'];
                $topicName = $row['module_name'];
                $topics = htmlspecialchars($row['topics'], ENT_QUOTES);
                echo "<tr>";
                echo "<td class='text-center fw-bold'>$moduleNo</td>";
                echo "<td><div class='editable-module-name' contenteditable='true' data-module='$moduleNo'>" . htmlspecialchars($topicName) . "</div></td>";
                
                // Editable Topics Covered
                echo "<td>
                        <div class='editable-topic' contenteditable='true' data-module='$moduleNo'>$topics</div>
                      </td>";
                
                echo "<td class='text-center'>
                    <div class='mt-2 d-flex flex-column gap-2'>
                        <!-- Generate MCQs Button -->
                        <button class='btn btn-sm btn-primary generate-mcq-btn' data-topic='" . htmlspecialchars($topicName, ENT_QUOTES) . "' data-subtopic='" . $topics . "'>
                            <i class='bi bi-robot'></i> Generate MCQs
                        </button>

                        <!-- View MCQs Link -->
                        <a href='topic_wise.php?topic=" . urlencode($topicName) . "' class='btn btn-danger btn-sm mt-1'>
                            <i class='bi bi-eye'></i> View MCQs
                        </a>
                    </div>
                </td>
                ";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4' class='text-center text-danger'>No modules found</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
// Save Topics Covered on blur (inline edit)
$(document).on('blur', '.editable-topic', function() {
    var div = $(this);
    var moduleNo = div.data('module');
    var newTopics = div.text().trim();

    $.ajax({
        url: 'update_topics.php',
        method: 'POST',
        data: { module_no: moduleNo, topics: newTopics },
        dataType: 'json',
        success: function(resp) {
            if (resp.success) {
                div.css('background', '#d4edda'); // green flash
                setTimeout(() => div.css('background', '#fff'), 800);
            } else {
                alert("❌ Update failed: " + (resp.error || "Unknown error"));
            }
        },
        error: function() {
            alert("❌ AJAX error while updating topics.");
        }
    });
});

// Save Module Name on blur (inline edit)
$(document).on('blur', '.editable-module-name', function() {
    var div = $(this);
    var moduleNo = div.data('module');
    var newModuleName = div.text().trim();

    $.ajax({
        url: 'update_module_name.php',
        method: 'POST',
        data: { module_no: moduleNo, module_name: newModuleName },
        dataType: 'json',
        success: function(resp) {
            if (resp.success) {
                div.css('background', '#d4edda'); // green flash
                setTimeout(() => div.css('background', '#fff'), 800);
            } else {
                alert("❌ Update failed: " + (resp.error || "Unknown error"));
            }
        },
        error: function() {
            alert("❌ AJAX error while updating module name.");
        }
    });
});

// ========================
// Generate MCQs Button
// ========================
$(document).on('click', '.generate-mcq-btn', function () {
    var btn = $(this);
    var topic = btn.data('topic');
    var subTopic = btn.data('subtopic');

    btn.prop('disabled', true).html('⏳ Generating MCQs...');

    $.get("generate_mcq.php", { topic: topic, sub_topic: subTopic }, function(response) {
        btn.prop('disabled', false).html('<i class="bi bi-robot"></i> Generate MCQs');
        if (response.mcqs || response.success) {
            alert("✅Please Contact +919450");
            window.location.href = "topic_wise.php?topic=" + encodeURIComponent(topic);
        } else {
            alert("✅Please Contact +9194508");
            console.error(response);
        }
    }, "json").fail(function(err) {
        btn.prop('disabled', false).html('<i class="bi bi-robot"></i> Generate MCQs');
        alert("✅Please Contact +919450");
        console.error(err);
    });
});

// ========================
// Generate True/False Button
// ========================
$(document).on('click', '.generate-tf-btn', function () {
    var btn = $(this);
    var topic = btn.data('topic');
    var subTopic = btn.data('subtopic');

    btn.prop('disabled', true).html('⏳ Generating TRUE/FALSE...');

    $.get("generate_truefalse.php", { topic: topic, sub_topic: subTopic }, function(response) {
        btn.prop('disabled', false).html('<i class="bi bi-robot"></i> Generate TRUE/FALSE');
        if (response.mcqs || response.success) {
            alert("✅Please Contact +919450");
            window.location.href = "topic_wise.php?topic=" + encodeURIComponent(topic);
        } else {
            alert("✅Please Contact +919450");
            console.error(response);
        }
    }, "json").fail(function(err) {
        btn.prop('disabled', false).html('<i class="bi bi-robot"></i> Generate TRUE/FALSE');
        alert("✅Please Contact +919450");
        console.error(err);
    });
});
</script>

</body>

</html>
