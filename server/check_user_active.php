<?php
session_start();
include('connection.php');
header('Content-Type: application/json');

$user_id = intval($_POST['user_id'] ?? 0);

if (!$user_id) {
    echo json_encode(['status' => 'error']);
    exit;
}

$stmt = $conn->prepare("SELECT active FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($active);
$stmt->fetch();
$stmt->close();

if ($active == 1) {
    echo json_encode(['status' => 'active']);
} else {
    echo json_encode(['status' => 'inactive']);
}
?>
