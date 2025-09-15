<?php
include "db.php";

header('Content-Type: text/html; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['table_name'])) {
    $table_name = trim($_POST['table_name']);
    $response = ['success' => false, 'message' => ''];

    // Validate table name to prevent SQL injection
    if (empty($table_name) || !preg_match('/^[a-zA-Z0-9_]+$/', $table_name)) {
        $response['message'] = 'Invalid table name';
        echo json_encode($response);
        exit;
    }

    // Check if the table exists
    $check_table = $conn->query("SHOW TABLES LIKE '$table_name'");
    if ($check_table->num_rows === 0) {
        $response['message'] = "Table '$table_name' does not exist";
        echo json_encode($response);
        exit;
    }

    // Truncate the table
    $sql = "TRUNCATE TABLE `$table_name`";
    if ($conn->query($sql) === TRUE) {
        $response['success'] = true;
        $response['message'] = "Table '$table_name' has been emptied successfully";
    } else {
        $response['message'] = "Error truncating table: " . $conn->error;
    }

    echo json_encode($response);
    exit;
}

// Fetch all tables from the database
$tables = [];
$result = $conn->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Empty Database Table</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .container { max-width: 600px; }
    .alert { display: none; }
  </style>
</head>
<body class="bg-light">
<div class="container my-5">
  <h2 class="text-center mb-4">🗑️ Empty Database Table</h2>
  <div class="alert alert-success" id="success-message" role="alert"></div>
  <div class="alert alert-danger" id="error-message" role="alert"></div>
  <div class="card shadow-sm p-4">
    <form id="truncate-form">
      <div class="mb-3">
        <label for="table_name" class="form-label">Select Table</label>
        <select name="table_name" id="table_name" class="form-select" required>
          <option value="">-- Select a Table --</option>
          <?php foreach ($tables as $table): ?>
            <option value="<?php echo htmlspecialchars($table); ?>"><?php echo htmlspecialchars($table); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-danger w-100">
        <i class="bi bi-trash-fill me-1"></i> Empty Table
      </button>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
  $('#truncate-form').on('submit', function(e) {
    e.preventDefault();
    var tableName = $('#table_name').val();
    
    if (!tableName) {
      $('#error-message').text('Please select a table').show();
      return;
    }

    if (!confirm('Are you sure you want to empty the table "' + tableName + '"? This action cannot be undone.')) {
      return;
    }

    $.ajax({
      url: 'truncate_table.php',
      method: 'POST',
      data: { table_name: tableName },
      dataType: 'json',
      success: function(resp) {
        if (resp.success) {
          $('#success-message').text(resp.message).show();
          $('#error-message').hide();
          setTimeout(() => $('#success-message').fadeOut(), 3000);
        } else {
          $('#error-message').text(resp.message).show();
          $('#success-message').hide();
        }
      },
      error: function() {
        $('#error-message').text('AJAX error while truncating table').show();
        $('#success-message').hide();
      }
    });
  });
});
</script>
</body>
</html>

<?php $conn->close(); ?>