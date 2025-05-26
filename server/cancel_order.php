<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    exit;
}

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

require_once('connection.php');

// Check if order exists, belongs to user, and is Pending (or NULL)
$stmt = $conn->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found.']);
    $stmt->close();
    $conn->close();
    exit;
}

$row = $result->fetch_assoc();
$status = $row['status'];

if ($status !== 'Pending' && $status !== null) {
    echo json_encode(['success' => false, 'message' => 'Only pending orders can be cancelled.']);
    $stmt->close();
    $conn->close();
    exit;
}

// Update order status to Cancelled
$stmt->close();
$stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
if ($stmt->execute()) {
    // Restock products
    $items_stmt = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    while ($item = $items_result->fetch_assoc()) {
        $stock_stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
        $stock_stmt->execute();
        $stock_stmt->close();
    }
    $items_stmt->close();

    echo json_encode(['success' => true, 'message' => 'Order cancelled Successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel order.']);
}
$stmt->close();
$conn->close();
