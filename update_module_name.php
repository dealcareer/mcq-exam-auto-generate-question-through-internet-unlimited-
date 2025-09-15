<?php
include "db.php";

header('Content-Type: application/json');

$response = ['success' => false, 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $module_no = isset($_POST['module_no']) ? (int)$_POST['module_no'] : 0;
    $module_name = isset($_POST['module_name']) ? trim($_POST['module_name']) : '';

    if ($module_no <= 0 || empty($module_name)) {
        $response['error'] = 'Invalid module number or module name';
        echo json_encode($response);
        exit;
    }

    // Prepare and execute the update query
    $sql = "UPDATE modules SET module_name = ? WHERE module_no = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $response['error'] = 'Database prepare error: ' . $conn->error;
        echo json_encode($response);
        exit;
    }

    $stmt->bind_param("si", $module_name, $module_no);
    if ($stmt->execute()) {
        $response['success'] = true;
    } else {
        $response['error'] = 'Database update error: ' . $stmt->error;
    }

    $stmt->close();
} else {
    $response['error'] = 'Invalid request method';
}

echo json_encode($response);
$conn->close();
?>