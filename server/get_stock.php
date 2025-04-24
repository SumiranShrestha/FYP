<?php
include('connection.php');
header("Content-Type: application/json");

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

$stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if ($product) {
    echo json_encode(["stock" => $product['stock']]);
} else {
    echo json_encode(["stock" => 0]);
}
?>
