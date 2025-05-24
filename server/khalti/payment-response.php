<?php
// server/khalti/payment-response.php

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include(__DIR__ . '/../../server/connection.php');

// 1) Ensure we have a lookup token from GET request (Khalti redirects with pidx)
if (!isset($_GET['pidx'])) {
  header('Location: ../../checkout.php?error=no_pidx');
  exit();
}
$pidx = $_GET['pidx'];

// 2) Call Khalti's lookup API to verify payment
$lookupPayload = ['pidx' => $pidx];
$ch = curl_init('https://a.khalti.com/api/v2/epayment/lookup/');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST           => true,
  CURLOPT_POSTFIELDS     => json_encode($lookupPayload),
  CURLOPT_HTTPHEADER     => [
    'Authorization: Key 2c12d1e504eb43be8f33b6b5ca6a46c4',
    'Content-Type: application/json',
  ],
  CURLOPT_TIMEOUT        => 15,
  CURLOPT_SSL_VERIFYPEER => true,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
  header('Location: ../../checkout.php?error=payment_verification_failed');
  exit();
}

$data = json_decode($response, true);
if (empty($data['status']) || $data['status'] !== 'Completed') {
  header('Location: ../../checkout.php?error=payment_not_completed&status=' . urlencode($data['status'] ?? 'unknown'));
  exit();
}

// 3) Get checkout data from session
$cd = $_SESSION['checkout_data'] ?? [];
if (empty($cd)) {
  header('Location: ../../checkout.php?error=session_expired');
  exit();
}

// 4) Process the order directly here since payment is confirmed
if (!isset($_SESSION['user_id'])) {
  header("Location: ../../login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

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
  header("Location: ../../cart.php?error=empty_cart");
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
  // Create order with 'Processing' status since payment is confirmed
  $payment_method = 'khalti';
  $status = 'Processing';

  $stmt = $conn->prepare("INSERT INTO orders (user_id, full_name, email, phone, address, city, payment_method, total_price, prescription_id, order_note, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param(
    "issssssdiss",
    $user_id,
    $cd['name'],
    $cd['email'],
    $cd['phone'],
    $cd['address'],
    $cd['city'],
    $payment_method,
    $grand_total,
    $prescription_id,
    $cd['order_note'],
    $status
  );
  $stmt->execute();
  $order_id = $conn->insert_id;

  // Add order items
  foreach ($cart_items as $item) {
    $unit_price = $item['discount_price'] > 0 ? $item['discount_price'] : $item['price'];
    $payment_method_item = 'khalti';

    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, payment_method) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisd", $order_id, $item['product_id'], $item['quantity'], $unit_price, $payment_method_item);
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
  $_SESSION['user_email'] = $cd['email'];
  $_SESSION['user_name'] = $cd['name'];

  // Clear checkout data from session
  unset($_SESSION['checkout_data']);
  unset($_SESSION['khalti_pidx']);

  $conn->commit();

  // Redirect to order confirmation
  header("Location: ../../order-confirmation.php?order_id=" . $order_id);
  exit();
} catch (Exception $e) {
  $conn->rollback();
  error_log("Khalti order creation error: " . $e->getMessage());
  header("Location: ../../checkout.php?error=order_creation_failed");
  exit();
}
