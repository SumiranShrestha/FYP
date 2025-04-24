<?php
session_start();

// Only a logged-in doctor can update appointments
if (!isset($_SESSION["user_id"]) || ($_SESSION["user_type"] ?? '') !== 'doctor') {
    header("Location: ../index.php");
    exit();
}

// Verify required POST fields
if (!isset($_POST['appointment_id'], $_POST['status'])) {
    $_SESSION['alert_message'] = 'Invalid request.';
    $_SESSION['alert_type']    = 'danger';
    header("Location: ../doctor_appointments.php");
    exit();
}

require_once __DIR__ . '/connection.php';

$appointment_id = (int) $_POST['appointment_id'];
$status         = $_POST['status'];
$prescription   = trim($_POST['prescription'] ?? '');
$doctor_id      = $_SESSION['user_id'];

// 1) Confirm this appointment belongs to the logged-in doctor
$check = $conn->prepare(
    "SELECT id FROM appointments WHERE id = ? AND doctor_id = ?"
);
$check->bind_param('ii', $appointment_id, $doctor_id);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    $_SESSION['alert_message'] = 'Appointment not found or access denied.';
    $_SESSION['alert_type']    = 'danger';
    $check->close();
    $conn->close();
    header("Location: ../doctor_appointments.php");
    exit();
}
$check->close();

// 2) Perform the update
$update = $conn->prepare(
    "UPDATE appointments
        SET status = ?,
            prescription = ?
      WHERE id = ?"
);
$update->bind_param('ssi', $status, $prescription, $appointment_id);

if ($update->execute()) {
    $_SESSION['alert_message'] = 'Appointment updated successfully.';
    $_SESSION['alert_type']    = 'success';
} else {
    $_SESSION['alert_message'] = 'Failed to update: ' . $update->error;
    $_SESSION['alert_type']    = 'danger';
}
$update->close();
$conn->close();

// Redirect back to the appointment details page
header("Location: ../doctor_appointment.php?id={$appointment_id}");
exit();
?>
