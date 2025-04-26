<?php
session_start();
include('server/connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_POST['inputName'] ?? '';
$phone = $_POST['inputPhone'] ?? '';
$city = $_POST['inputCity'] ?? '';
$address = $_POST['inputAddress'] ?? '';
$payment_method = $_POST['payment_method'] ?? '';
$order_note = $_POST['order_note'] ?? '';

// Get the user's email from the users table
$user_stmt = $conn->prepare("SELECT user_email FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

if (!$user) {
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit();
}

$email = $user['user_email']; // Get email from users table

// Calculate grand total (you'll need to implement this based on your cart)
$total_price = $_POST['inputAmount4'] ?? 0; // Use the value sent from the form

// Insert order into the database (using correct column names)
$stmt = $conn->prepare("INSERT INTO orders (user_id, full_name, email, phone, address, city, payment_method, total_price, order_note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issssssds", $user_id, $full_name, $email, $phone, $address, $city, $payment_method, $total_price, $order_note);

if ($stmt->execute()) {
    $order_id = $stmt->insert_id;

    // Redirect to the order confirmation page with the order ID
    header("Location: order-confirmation.php?order_id=" . $order_id);
    exit(); // Ensure no further code execution after the redirect
} else {
    echo json_encode(["status" => "error", "message" => "Failed to place order: " . $conn->error]);
}
?>
