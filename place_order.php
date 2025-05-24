<?php
session_start();
include("server/connection.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];

    // Get form data (only for COD orders)
    $full_name = trim($_POST['inputName'] ?? '');
    $email = trim($_POST['inputEmail'] ?? '');
    $phone = trim($_POST['inputPhone'] ?? '');
    $address = trim($_POST['inputAddress'] ?? '');
    $city = trim($_POST['inputCity'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'cod';
    $order_note = trim($_POST['order_note'] ?? '');

    // Validate required fields
    if (empty($full_name) || empty($phone) || empty($address) || empty($city)) {
        header("Location: checkout.php?error=missing_fields");
        exit();
    }

    // Get cart items with prescription info
    $stmt = $conn->prepare("
        SELECT c.*, p.name, p.price, p.discount_price, p.prescription_required
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($cart_items)) {
        header("Location: cart.php?error=empty_cart");
        exit();
    }

    // Calculate total
    $total_price = 0;
    $delivery_charge = 100;
    foreach ($cart_items as $item) {
        $unit_price = $item['discount_price'] > 0 ? $item['discount_price'] : $item['price'];
        $total_price += $unit_price * $item['quantity'];
    }
    $grand_total = $total_price + $delivery_charge;

    // Get prescription_id from the first prescription item (if any)
    $prescription_id = null;
    foreach ($cart_items as $item) {
        if (!empty($item['prescription_id'])) {
            $prescription_id = $item['prescription_id'];
            break;
        }
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Create order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, full_name, email, phone, address, city, payment_method, total_price, prescription_id, order_note, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("issssssdis", $user_id, $full_name, $email, $phone, $address, $city, $payment_method, $grand_total, $prescription_id, $order_note);
        $stmt->execute();
        $order_id = $conn->insert_id;

        // Add order items
        foreach ($cart_items as $item) {
            $unit_price = $item['discount_price'] > 0 ? $item['discount_price'] : $item['price'];
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, payment_method) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiisd", $order_id, $item['product_id'], $item['quantity'], $unit_price, $payment_method);
            $stmt->execute();

            // If this is a prescription product, add to prescription_orders
            if ($item['prescription_required'] == 1 && !empty($item['prescription_id'])) {
                // Check if already exists to avoid duplicates
                $checkStmt = $conn->prepare("SELECT id FROM prescription_orders WHERE user_id = ? AND product_id = ? AND prescription_id = ?");
                $checkStmt->bind_param("iii", $user_id, $item['product_id'], $item['prescription_id']);
                $checkStmt->execute();
                $exists = $checkStmt->get_result()->num_rows > 0;
                $checkStmt->close();

                if (!$exists) {
                    $stmt = $conn->prepare("INSERT INTO prescription_orders (user_id, product_id, prescription_id, order_type, status) VALUES (?, ?, ?, 'with_prescription', 'submitted')");
                    $stmt->bind_param("iii", $user_id, $item['product_id'], $item['prescription_id']);
                    $stmt->execute();
                }
            }
        }

        // Clear cart
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Store user info in session for email
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $full_name;

        $conn->commit();

        header("Location: order-confirmation.php?order_id=" . $order_id);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("COD order creation error: " . $e->getMessage());
        header("Location: checkout.php?error=order_failed");
        exit();
    }
} else {
    header("Location: checkout.php");
    exit();
}
