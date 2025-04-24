<?php
session_start();
require_once("server/connection.php");

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Validate appointment ID
if (!isset($_POST['appointment_id']) || !is_numeric($_POST['appointment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
    exit();
}

$appointment_id = intval($_POST['appointment_id']);
$user_id = $_SESSION['user_id'];

// Verify the appointment belongs to the user
$stmt = $conn->prepare("SELECT id FROM appointments WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found or access denied']);
    exit();
}

// Update appointment status to cancelled
$update_stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
$update_stmt->bind_param("i", $appointment_id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$conn->close();
?>