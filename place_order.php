<?php
session_start();
include('server/connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit();
}

// Prevent double order submission
if (isset($_SESSION['order_placed']) && $_SESSION['order_placed'] === true) {
    // Redirect to confirmation if order already placed in this session
    if (isset($_SESSION['last_order_id'])) {
        header("Location: order-confirmation.php?order_id=" . $_SESSION['last_order_id']);
        exit();
    } else {
        echo json_encode(["status" => "error", "message" => "Order already placed."]);
        exit();
    }
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

// Validate total_price
$total_price = floatval($total_price);

// Insert order into the database (using correct column names)
$stmt = $conn->prepare("INSERT INTO orders (user_id, full_name, email, phone, address, city, payment_method, total_price, order_note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issssssds", $user_id, $full_name, $email, $phone, $address, $city, $payment_method, $total_price, $order_note);

if ($stmt->execute()) {
    $order_id = $stmt->insert_id;

    // Set session flag to prevent double submission
    $_SESSION['order_placed'] = true;
    $_SESSION['last_order_id'] = $order_id;

    // Redirect to the order confirmation page with the order ID
    header("Location: order-confirmation.php?order_id=" . $order_id);
    exit(); // Ensure no further code execution after the redirect
} else {
    echo json_encode(["status" => "error", "message" => "Failed to place order: " . $conn->error]);
}
?>
