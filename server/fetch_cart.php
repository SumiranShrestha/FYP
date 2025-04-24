<?php
session_start();
header('Content-Type: application/json');
include('connection.php');

function fetchCartData($conn, $user_id) {
    $stmt = $conn->prepare("SELECT c.product_id, p.name, p.price, p.discount_price, p.images, c.quantity 
                            FROM cart c 
                            JOIN products p ON c.product_id = p.id 
                            WHERE c.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    $total = 0;

    while ($row = $result->fetch_assoc()) {
        $product_price = ($row['discount_price'] > 0) ? (float) $row['discount_price'] : (float) $row['price'];
        $quantity = (int) $row['quantity'];

        $total += $product_price * $quantity;

        // Ensure images are properly handled
        $images = json_decode($row['images'], true);
        $image = isset($images[0]) ? $images[0] : 'default.jpg';

        $items[] = [
            "id" => $row['product_id'],
            "name" => $row['name'],
            "price" => $product_price,
            "quantity" => $quantity,
            "image" => $image
        ];
    }

    return [
        "items" => $items,
        "total" => $total,
        "count" => count($items)
    ];
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["items" => [], "total" => 0, "count" => 0]);
} else {
    $cartData = fetchCartData($conn, $_SESSION['user_id']);
    echo json_encode($cartData);
}

exit();
?>
