<?php
session_start();
require_once('connection.php');

// Enable MySQLi exceptions for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    respond(false, "Please login to cancel appointments");
}

// Detect if appointment ID is provided (support both AJAX POST and normal GET)
$appointment_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id']) && is_numeric($_POST['appointment_id'])) {
    $appointment_id = (int) $_POST['appointment_id'];
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $appointment_id = (int) $_GET['id'];
} else {
    respond(false, "Invalid appointment ID");
}

$user_id = $_SESSION["user_id"];
$user_type = $_SESSION["user_type"] ?? 'customer';

$conn->begin_transaction();

try {
    // Verify the appointment exists and belongs to the user
    $query = ($user_type === 'doctor')
        ? "SELECT * FROM appointments WHERE id = ? AND doctor_id = ? FOR UPDATE"
        : "SELECT * FROM appointments WHERE id = ? AND user_id = ? FOR UPDATE";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $appointment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Appointment not found or you don't have permission to cancel it");
    }

    $appointment = $result->fetch_assoc();

    // Only pending or confirmed appointments can be cancelled
    if (!in_array($appointment['status'], ['pending', 'confirmed'])) {
        throw new Exception("Only pending or confirmed appointments can be cancelled");
    }

    // Update status
    $update_stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
    $update_stmt->bind_param("i", $appointment_id);
    $update_stmt->execute();

    $conn->commit();

    respond(true, "Appointment cancelled successfully");
} catch (Exception $e) {
    $conn->rollback();
    respond(false, $e->getMessage());
} finally {
    $conn->close();
}

// Helper response function
function respond($success, $message)
{
    if (isAjax()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
    } else {
        $_SESSION['alert_message'] = $message;
        $_SESSION['alert_type'] = $success ? "success" : "danger";

        $redirect = strpos($_SERVER['HTTP_REFERER'] ?? '', 'profile.php') !== false ? 'profile.php' : 'my_appointments.php';
        header("Location: $redirect");
    }
    exit();
}

function isAjax()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}
