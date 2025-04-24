<?php
session_start();
header('Content-Type: application/json');
include('connection.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? 0;
$action = $_POST['action'] ?? "";

if ($action === "increase") {
    $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
} elseif ($action === "decrease") {
    $stmt = $conn->prepare("UPDATE cart SET quantity = quantity - 1 WHERE user_id = ? AND product_id = ? AND quantity > 1");
    $stmt->bind_param("ii", $user_id, $product_id);
}

if ($stmt->execute()) {
    $stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    echo json_encode(["status" => "success", "quantity" => $row['quantity'] ?? 0]);
} else {
    echo json_encode(["status" => "error"]);
}

exit();
?>
