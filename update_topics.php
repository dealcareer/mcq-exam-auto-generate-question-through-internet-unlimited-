<?php
include "db.php";
header('Content-Type: application/json');

$moduleNo = $_POST['module_no'] ?? '';
$topics = $_POST['topics'] ?? '';

if (!$moduleNo) {
    echo json_encode(['success'=>false,'error'=>'Module No missing']);
    exit;
}

$stmt = $conn->prepare("UPDATE modules SET topics=? WHERE module_no=?");
$stmt->bind_param("si", $topics, $moduleNo);
if ($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'error'=>$stmt->error]);
}
$stmt->close();
