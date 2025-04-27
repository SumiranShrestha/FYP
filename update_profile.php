<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include('server/connection.php');

$user_id = $_SESSION["user_id"];
$user_type = $_SESSION["user_type"] ?? 'customer';

$user_name = trim($_POST['user_name'] ?? '');
$user_email = trim($_POST['user_email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');

if ($user_type === 'doctor') {
    // Update both users and doctors tables for doctors
    $stmt1 = $conn->prepare("UPDATE users SET user_name=?, user_email=?, phone=?, address=?, city=? WHERE id=?");
    $stmt1->bind_param("sssssi", $user_name, $user_email, $phone, $address, $city, $user_id);

    $stmt2 = $conn->prepare("UPDATE doctors SET full_name=?, email=?, phone=?, address=?, city=? WHERE id=?");
    $stmt2->bind_param("sssssi", $user_name, $user_email, $phone, $address, $city, $user_id);

    $success = $stmt1->execute() && $stmt2->execute();
} else {
    // Only update users table for customers
    $stmt = $conn->prepare("UPDATE users SET user_name=?, user_email=?, phone=?, address=?, city=? WHERE id=?");
    $stmt->bind_param("sssssi", $user_name, $user_email, $phone, $address, $city, $user_id);
    $success = $stmt->execute();
}

if ($success) {
    $_SESSION['alert_message'] = "Profile updated successfully.";
    $_SESSION['alert_type'] = "success";
} else {
    $_SESSION['alert_message'] = "Failed to update profile.";
    $_SESSION['alert_type'] = "danger";
}

header("Location: profile.php");
exit();
?>
