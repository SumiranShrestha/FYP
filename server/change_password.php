<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include('connection.php'); // Include database connection

$user_id = $_SESSION["user_id"];
$user_type = $_SESSION["user_type"] ?? 'customer';

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_new_password = $_POST['confirm_new_password'] ?? '';

// Validate input
if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
    $_SESSION['alert_message'] = "All fields are required";
    $_SESSION['alert_type'] = "danger";
    header("Location: ../profile.php");
    exit();
}

if ($new_password !== $confirm_new_password) {
    $_SESSION['alert_message'] = "New passwords do not match";
    $_SESSION['alert_type'] = "danger";
    header("Location: ../profile.php");
    exit();
}

// Fetch user's current password
if ($user_type === 'doctor') {
    $stmt = $conn->prepare("SELECT password FROM doctors WHERE id = ?");
} else {
    $stmt = $conn->prepare("SELECT user_password FROM users WHERE id = ?");
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows !== 1) {
    $_SESSION['alert_message'] = "User not found";
    $_SESSION['alert_type'] = "danger";
    header("Location: ../profile.php");
    exit();
}

if ($user_type === 'doctor') {
    $stmt->bind_result($db_password);
} else {
    $stmt->bind_result($db_password);
}
$stmt->fetch();

if (!password_verify($current_password, $db_password)) {
    $_SESSION['alert_message'] = "Current password is incorrect";
    $_SESSION['alert_type'] = "danger";
    header("Location: ../profile.php");
    exit();
}

// Hash the new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update password
if ($user_type === 'doctor') {
    $stmt = $conn->prepare("UPDATE doctors SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);
    $success = $stmt->execute();
    // Also update users table if exists
    $stmt2 = $conn->prepare("UPDATE users SET user_password = ? WHERE id = ?");
    $stmt2->bind_param("si", $hashed_password, $user_id);
    $stmt2->execute();
    $stmt2->close();
} else {
    $stmt = $conn->prepare("UPDATE users SET user_password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);
    $success = $stmt->execute();
}

if ($success) {
    $_SESSION['alert_message'] = "Password updated successfully";
    $_SESSION['alert_type'] = "success";
} else {
    $_SESSION['alert_message'] = "Failed to update password";
    $_SESSION['alert_type'] = "danger";
}

header("Location: ../profile.php");
exit();
?>