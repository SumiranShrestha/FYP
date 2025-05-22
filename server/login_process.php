<?php
session_start();
include('connection.php');
header('Content-Type: application/json');


$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Adjust column names as per your database
$stmt = $conn->prepare("SELECT id, user_name, user_password, active FROM users WHERE user_email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(["status" => "error", "message" => "Invalid email or password"]);
    exit();
}

// Check password first
if (!password_verify($password, $user['user_password'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid email or password"
    ]);
    exit;
}

// Now check if inactive (only after password is correct)
if ($user['active'] == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Your account is inactive"
    ]);
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['user_name'];
$_SESSION['user_email'] = $email;

echo json_encode(["status" => "success", "message" => "Login successful", "redirect" => "index.php"]);
exit();

?>
