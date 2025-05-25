<?php
session_start();
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

include('server/connection.php'); // Include database connection

if (isset($_GET['id'])) {
    $doctor_id = $_GET['id'];

    // Delete doctor
    $stmt = $conn->prepare("DELETE FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctor_id);

    if ($stmt->execute()) {
        $_SESSION['alert_message'] = "Doctor deleted successfully";
        $_SESSION['alert_type'] = "success";
    } else {
        $_SESSION['alert_message'] = "Failed to delete doctor";
        $_SESSION['alert_type'] = "danger";
    }
}

header("Location: manage_doctor.php");
exit();
?>