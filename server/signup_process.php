<?php
session_start();
include('connection.php');

header('Content-Type: application/json');

// 1) Grab and trim inputs
$user_name    = trim($_POST['username'] ?? '');
$user_email   = trim($_POST['email'] ?? '');
$password_raw = $_POST['password'] ?? '';

// 2) Validate password strength
$pattern = '/^(?=.*[A-Z])      # at least one uppercase
             (?=.*[a-z])      # at least one lowercase
             (?=.*\d)         # at least one digit
             (?=.*[\W_])      # at least one special char
             .{8,}            # at least 8 total characters
            $/x';
if (!preg_match($pattern, $password_raw)) {
    echo json_encode([
        "status"  => "error",
        "message" => "Password must be at least 8 characters long and include uppercase, lowercase, number, and special character."
    ]);
    exit();
}

// 3) Hash the password
$user_password = password_hash($password_raw, PASSWORD_DEFAULT);

// 4) Check if email exists already
$stmt = $conn->prepare("SELECT id FROM users WHERE user_email = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Email already registered."]);
    $stmt->close();
    exit();
}
$stmt->close();

// 5) Insert new user (including registration_date via NOW())
$stmt = $conn->prepare("
    INSERT INTO users
      (user_name, user_email, user_password, registration_date)
    VALUES
      (?,         ?,          ?,             NOW())
");
$stmt->bind_param("sss", $user_name, $user_email, $user_password);

if ($stmt->execute()) {
    // set session vars
    $_SESSION['user_id']    = $stmt->insert_id;
    $_SESSION['user_name']  = $user_name;
    $_SESSION['user_email'] = $user_email;
    // you can also store registration date in session if needed:
    $_SESSION['registration_date'] = date('Y-m-d H:i:s');

    echo json_encode(["status" => "success", "message" => "Signup successful!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Signup failed."]);
}

$stmt->close();
$conn->close();
?>
