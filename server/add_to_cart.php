<?php
session_start();
header('Content-Type: application/json');
include('connection.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "You must be logged in to add items to cart"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? 0;
$quantity = $_POST['quantity'] ?? 1;

// Fetch product details from database
$stmt = $conn->prepare("SELECT id, price, discount_price, stock FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo json_encode(["status" => "error", "message" => "Product not found"]);
    exit();
}

$product_price = $product['discount_price'] > 0 ? $product['discount_price'] : $product['price'];

// Check if the product is already in the user's cart
$stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update quantity in cart
    $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("iii", $quantity, $user_id, $product_id);
} else {
    // Insert new cart entry
    $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $user_id, $product_id, $quantity);
}

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Added to cart"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to add item"]);
}

exit();
?>
