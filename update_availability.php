<?php
session_start();
if (!isset($_SESSION["user_id"]) || ($_SESSION["user_type"] ?? '') !== 'doctor') {
    header("Location: login.php");
    exit();
}

include('server/connection.php');

$user_id = $_SESSION["user_id"];

if (isset($_POST['delete_day'])) {
    $delete_day = trim($_POST['delete_day']);
    // Fetch current availability
    $stmt = $conn->prepare("SELECT availability FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $doctor = $result->fetch_assoc();

    $availability = [];
    if (!empty($doctor['availability'])) {
        $availability = json_decode($doctor['availability'], true);
    }

    if (isset($availability[$delete_day])) {
        unset($availability[$delete_day]);
        $newAvailabilityJson = json_encode($availability);
        $stmt = $conn->prepare("UPDATE doctors SET availability = ? WHERE id = ?");
        $stmt->bind_param("si", $newAvailabilityJson, $user_id);
        if ($stmt->execute()) {
            $_SESSION['alert_message'] = "Availability for $delete_day deleted.";
            $_SESSION['alert_type'] = "success";
        } else {
            $_SESSION['alert_message'] = "Failed to delete availability.";
            $_SESSION['alert_type'] = "danger";
        }
    } else {
        $_SESSION['alert_message'] = "Day not found in availability.";
        $_SESSION['alert_type'] = "danger";
    }
    header("Location: profile.php");
    exit();
}

// ...existing code for add/update...
$day = trim($_POST['day'] ?? '');
$times = trim($_POST['times'] ?? '');

if ($day === '' || $times === '') {
    $_SESSION['alert_message'] = "Day and times are required.";
    $_SESSION['alert_type'] = "danger";
    header("Location: profile.php");
    exit();
}

// Fetch current availability
$stmt = $conn->prepare("SELECT availability FROM doctors WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();

$availability = [];
if (!empty($doctor['availability'])) {
    $availability = json_decode($doctor['availability'], true);
}

// Update or add the day's times
$timeArr = array_map('trim', explode(',', $times));
$availability[$day] = $timeArr;

// Save back to DB
$newAvailabilityJson = json_encode($availability);

$stmt = $conn->prepare("UPDATE doctors SET availability = ? WHERE id = ?");
$stmt->bind_param("si", $newAvailabilityJson, $user_id);

if ($stmt->execute()) {
    $_SESSION['alert_message'] = "Availability updated successfully.";
    $_SESSION['alert_type'] = "success";
} else {
    $_SESSION['alert_message'] = "Failed to update availability.";
    $_SESSION['alert_type'] = "danger";
}

header("Location: profile.php");
exit();
?>
