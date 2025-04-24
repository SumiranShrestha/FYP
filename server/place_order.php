<?php
include('connection.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$city = $_POST['city'] ?? '';
$address = $_POST['address'] ?? '';
$payment_method = $_POST['payment_method'] ?? 'Cash on Delivery';

// Validate input
if (empty($full_name) || empty($phone) || empty($city) || empty($address)) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit();
}

// Fetch cart items
$cart_items = [];
$total_price = 0;

$stmt = $conn->prepare("
    SELECT c.*, p.name, p.price, p.discount_price, p.images 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $product_price = ($row['discount_price'] > 0) ? $row['discount_price'] : $row['price'];
    $total_price += $product_price * $row['quantity'];
}

if (empty($cart_items)) {
    echo json_encode(["status" => "error", "message" => "Your cart is empty"]);
    exit();
}

$delivery_charge = 100;
$grand_total = $total_price + $delivery_charge;

// Insert order
$stmt = $conn->prepare("
    INSERT INTO orders (user_id, full_name, email, phone, address, city, payment_method, total_price, order_status, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
");
$stmt->bind_param("issssssd", $user_id, $full_name, $email, $phone, $address, $city, $payment_method, $grand_total);
$stmt->execute();
$order_id = $stmt->insert_id;

// Insert order items
foreach ($cart_items as $item) {
    $product_price = ($item['discount_price'] > 0) ? $item['discount_price'] : $item['price'];
    $stmt = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $product_price);
    $stmt->execute();
}

// Clear the cart
$stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();

// Send success response
echo json_encode([
    "status" => "success",
    "message" => "Order placed successfully!",
    "order_id" => $order_id
]);
exit();
?>
