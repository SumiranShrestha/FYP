<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('server/connection.php');

// 1) Collect & sanitize inputs
$user_id   = $_SESSION['user_id'];
$name      = trim($_POST['user_name']   ?? '');
$email_raw = $_POST['user_email']       ?? '';
$email     = filter_var($email_raw, FILTER_VALIDATE_EMAIL);
$phone_raw = $_POST['phone']            ?? '';
$phone     = preg_replace('/\D+/', '', $phone_raw);
$address   = trim($_POST['address']     ?? '');
$city      = trim($_POST['city']        ?? '');

// 2) Validation
if (!$name || !$email) {
    $_SESSION['alert_type']    = 'danger';
    $_SESSION['alert_message'] = 'Name and a valid email are required.';
    header("Location: edit_profile.php");
    exit();
}

try {
    // 3) Update query
    $stmt = $conn->prepare("
        UPDATE users
           SET user_name  = ?,
               user_email = ?,
               phone      = ?,
               address    = ?,
               city       = ?
         WHERE id         = ?
    ");
    $stmt->bind_param(
        "sssssi",
        $name,
        $email,
        $phone,
        $address,
        $city,
        $user_id
    );

    if ($stmt->execute()) {
        $_SESSION['alert_type']    = 'success';
        $_SESSION['alert_message'] = 'Profile updated successfully.';
    } else {
        $_SESSION['alert_type']    = 'danger';
        $_SESSION['alert_message'] = 'Update failed: ' . $stmt->error;
    }

    $stmt->close();
} catch (Exception $e) {
    $_SESSION['alert_type']    = 'danger';
    $_SESSION['alert_message'] = 'An unexpected error occurred.';
}

$conn->close();

// 4) Redirect back
header("Location: profile.php");
exit();
