<?php
// server/khalti-ePayment-gateway-main/payment-request.php

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

// 2) Validate
$errors = [];
if ($amount_paisa < 100)                         $errors[] = 'Amount must be at least Rs 1';
if (!$purchase_order_id)                         $errors[] = 'Order ID is required';
if (trim($name) === '')                          $errors[] = 'Name is required';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'Valid email is required';
if (strlen($phone) !== 10)                       $errors[] = 'Valid 10‑digit phone is required';

if (!empty($errors)) {
    echo "<h2>Validation Errors:</h2>\n<ul>\n";
    foreach ($errors as $e) {
        echo "<li>" . htmlspecialchars($e) . "</li>\n";
    }
    echo "</ul>";
    exit;
}

// 3) Insert order + items then clear cart
$conn->begin_transaction();
try {
    // orders table
    $o = $conn->prepare("
      INSERT INTO orders
        (user_id, full_name, email, phone, address, city, payment_method, total_price)
      VALUES (?, ?, ?, ?, ?, ?, 'khalti', ?)
    ");
    $rupees = $amount_paisa / 100;
    $o->bind_param("isssssd",
      $_SESSION['user_id'], $name, $email, $phone, $address, $city, $rupees
    );
    $o->execute();
    if ($o->error) {
        throw new Exception("Orders insert error: " . $o->error);
    }
    $order_id = $conn->insert_id;
    $o->close();

    // order_items
    $c = $conn->prepare("
      SELECT c.product_id, c.quantity,
             IF(p.discount_price>0, p.discount_price, p.price) AS unit_price
      FROM cart c
      JOIN products p ON c.product_id=p.id
      WHERE c.user_id=?
    ");
    $c->bind_param("i", $_SESSION['user_id']);
    $c->execute();
    $cartRes = $c->get_result();
    $c->close();

    $i = $conn->prepare("
      INSERT INTO order_items (order_id, product_id, quantity, price)
      VALUES (?, ?, ?, ?)
    ");
    while ($ci = $cartRes->fetch_assoc()) {
        $i->bind_param("iiid",
          $order_id,
          $ci['product_id'],
          $ci['quantity'],
          $ci['unit_price']
        );
        $i->execute();
        if ($i->error) {
            throw new Exception("Order_items insert error: " . $i->error);
        }
    }
    $i->close();

    // clear cart
    $d = $conn->prepare("DELETE FROM cart WHERE user_id=?");
    $d->bind_param("i", $_SESSION['user_id']);
    $d->execute();
    if ($d->error) {
        throw new Exception("Cart delete error: " . $d->error);
    }
    $d->close();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    echo "<h2>Database Error:</h2>\n<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}

// 4) Initiate Khalti
$postFields = [
  'return_url'          => (isset($_SERVER['HTTPS'])?'https://':'http://')
                           .$_SERVER['HTTP_HOST']
                           .'/server/khalti/payment-response.php',
  'website_url'         => (isset($_SERVER['HTTPS'])?'https://':'http://')
                           .$_SERVER['HTTP_HOST'].'/',
  'amount'              => $amount_paisa,
  'purchase_order_id'   => $purchase_order_id,
  'purchase_order_name' => $purchase_order_name,
  'customer_info'       => ['name'=>$name,'email'=>$email,'phone'=>$phone],
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
    exit;
}
if ($code !== 200) {
    echo "<h2>HTTP Error Code: $code</h2>";
    echo "<h3>Response body:</h3><pre>" . htmlspecialchars($response) . "</pre>";
    exit;
}

$data = json_decode($response, true);
if (!empty($data['payment_url'])) {
    // redirect to Khalti
    header("Location: " . $data['payment_url']);
    exit;
}

// fallback
echo "<h2>Unexpected response from Khalti:</h2><pre>" . htmlspecialchars($response) . "</pre>";
exit;
