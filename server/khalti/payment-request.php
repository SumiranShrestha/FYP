<?php
// server/khalti/payment-request.php

// Enable full error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include(__DIR__ . '/../../server/connection.php');

// 1) Collect & sanitize inputs
$amount_paisa        = floatval($_POST['inputAmount4'] ?? 0) * 100;
$purchase_order_id   = htmlspecialchars($_POST['inputPurchasedOrderId4'] ?? '');
$purchase_order_name = htmlspecialchars($_POST['inputPurchasedOrderName4'] ?? '');
$name                = htmlspecialchars($_POST['inputName'] ?? '');
$email               = filter_var($_POST['inputEmail'] ?? '', FILTER_SANITIZE_EMAIL);
$phone               = preg_replace('/\D+/', '', $_POST['inputPhone'] ?? '');
$city                = htmlspecialchars($_POST['inputCity'] ?? '');
$address             = htmlspecialchars($_POST['inputAddress'] ?? '');
$order_note          = htmlspecialchars($_POST['order_note'] ?? '');
$landmark            = htmlspecialchars($_POST['landmark'] ?? '');

// 2) Validate
$errors = [];
if ($amount_paisa < 100)                         $errors[] = 'Amount must be at least Rs 1';
if (!$purchase_order_id)                         $errors[] = 'Order ID is required';
if (trim($name) === '')                          $errors[] = 'Name is required';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'Valid email is required';
if (strlen($phone) !== 10)                       $errors[] = 'Valid 10‑digit phone is required';
if (trim($city) === '')                          $errors[] = 'City is required';
if (trim($address) === '')                       $errors[] = 'Address is required';

if (!empty($errors)) {
  echo "<h2>Validation Errors:</h2>\n<ul>\n";
  foreach ($errors as $e) {
    echo "<li>" . htmlspecialchars($e) . "</li>\n";
  }
  echo "</ul>";
  echo '<a href="../../checkout.php">Go back to checkout</a>';
  exit;
}

// 3) Store checkout data in session for payment-response.php
$_SESSION['checkout_data'] = [
  'name' => $name,
  'email' => $email,
  'phone' => $phone,
  'city' => $city,
  'address' => $address,
  'order_note' => $order_note,
  'landmark' => $landmark,
  'amount' => $amount_paisa / 100
];

// 4) Validate cart is not empty
$stmt = $conn->prepare("
    SELECT COUNT(*) as count
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($result['count'] == 0) {
  echo "<h2>Your cart is empty. Please add items before placing an order.</h2>";
  echo '<a href="../../cart.php">Go to cart</a>';
  exit;
}

// 5) Initiate Khalti payment
$postFields = [
  'return_url'          => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://')
    . $_SERVER['HTTP_HOST']
    . '/server/khalti/payment-response.php',
  'website_url'         => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://')
    . $_SERVER['HTTP_HOST'] . '/',
  'amount'              => $amount_paisa,
  'purchase_order_id'   => $purchase_order_id,
  'purchase_order_name' => $purchase_order_name,
  'customer_info'       => [
    'name' => $name,
    'email' => $email,
    'phone' => $phone
  ],
];

$ch = curl_init('https://a.khalti.com/api/v2/epayment/initiate/');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST           => true,
  CURLOPT_POSTFIELDS     => json_encode($postFields),
  CURLOPT_HTTPHEADER     => [
    'Authorization: Key 2c12d1e504eb43be8f33b6b5ca6a46c4',
    'Content-Type: application/json'
  ],
  CURLOPT_TIMEOUT        => 15,
  CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$err      = curl_error($ch);
$code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) {
  echo "<h2>cURL Error:</h2><pre>" . htmlspecialchars($err) . "</pre>";
  echo '<a href="../../checkout.php">Go back to checkout</a>';
  exit;
}
if ($code !== 200) {
  echo "<h2>HTTP Error Code: $code</h2>";
  echo "<h3>Response body:</h3><pre>" . htmlspecialchars($response) . "</pre>";
  echo '<a href="../../checkout.php">Go back to checkout</a>';
  exit;
}

$data = json_decode($response, true);
if (!empty($data['payment_url'])) {
  // Store pidx for verification
  $_SESSION['khalti_pidx'] = $data['pidx'] ?? null;

  // Redirect to Khalti
  header("Location: " . $data['payment_url']);
  exit;
}

// Fallback
echo "<h2>Unexpected response from Khalti:</h2><pre>" . htmlspecialchars($response) . "</pre>";
echo '<a href="../../checkout.php">Go back to checkout</a>';
exit;
