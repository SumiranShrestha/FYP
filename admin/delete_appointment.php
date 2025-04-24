<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

// Include database connection
include('server/connection.php');

// Check if the delete action and appointment id is provided in the URL
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $appointment_id = intval($_GET['id']);

    // Use a prepared statement to safely delete the appointment
    $stmt = mysqli_prepare($conn, "DELETE FROM appointments WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $appointment_id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: view_appointment.php?success=Appointment deleted successfully");
            exit();
        } else {
            // If execution fails, clean up and redirect with an error
            mysqli_stmt_close($stmt);
            header("Location: view_appointment.php?error=Error deleting appointment");
            exit();
        }
    } else {
        // Failed to prepare the SQL statement
        header("Location: view_appointment.php?error=Error preparing deletion");
        exit();
    }
} else {
    // Redirect if the required parameters are not provided
    header("Location: view_appointment.php");
    exit();
}
?>
