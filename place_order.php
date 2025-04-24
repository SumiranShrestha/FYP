<?php
session_start();
include('server/connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_POST['full_name'] ?? '';
$phone = $_POST['phone'] ?? '';
$city = $_POST['city'] ?? '';
$address = $_POST['address'] ?? '';

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
$grand_total = 0; // Replace with actual calculation from cart

// Insert order into the database (using correct column names)
$stmt = $conn->prepare("INSERT INTO orders (user_id, full_name, phone, address, city, total_price, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'pending')");
$stmt->bind_param("issssd", $user_id, $full_name, $phone, $address, $city, $grand_total);

if ($stmt->execute()) {
    $order_id = $stmt->insert_id;

    // Redirect to the order confirmation page with the order ID
    header("Location: order-confirmation.php?order_id=" . $order_id);
    exit(); // Ensure no further code execution after the redirect
} else {
    echo json_encode(["status" => "error", "message" => "Failed to place order: " . $conn->error]);
}
?>
