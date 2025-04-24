<?php
// server/khalti-ePayment-gateway-main/payment-response.php

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include(__DIR__ . '/../../server/connection.php');

// 1) Ensure we have a lookup token
if (!isset($_GET['pidx'])) {
    header('Location: ../../checkout.php');
    exit();
}
$pidx = $_GET['pidx'];

// 2) Call Khalti’s lookup API to verify payment
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
    // Lookup failed at HTTP level
    die("Khalti lookup HTTP error ({$httpCode}): " . htmlspecialchars($response));
}

$data = json_decode($response, true);
if (empty($data['status']) || $data['status'] !== 'Completed') {
    // Payment not completed
    die('Payment not completed. Status: ' . htmlspecialchars($data['status'] ?? 'unknown'));
}

// 3) (Optional) update your DB order record to “paid” here, using $pidx or $data['idx']…

// 4) Prepare to hand off to place_order.php
//    We'll pull the user’s checkout details out of session.
$cd = $_SESSION['checkout_data'] ?? [];
$full_name = $cd['name']    ?? '';
$phone     = $cd['phone']   ?? '';
$city      = $cd['city']    ?? '';
$address   = $cd['address'] ?? '';

// 5) Clean up session if you like
unset($_SESSION['cart'], $_SESSION['checkout_data']);

// 6) Build the absolute URL to place_order.php
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host   = $_SERVER['HTTP_HOST'];
$actionUrl = $scheme . $host . '/place_order.php';

// 7) Emit an auto‑submitting POST form
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Finalizing Your Order…</title>
</head>
<body>
  <form id="redirectForm" action="<?= htmlspecialchars($actionUrl) ?>" method="post">
    <input type="hidden" name="full_name" value="<?= htmlspecialchars($full_name) ?>">
    <input type="hidden" name="phone"     value="<?= htmlspecialchars($phone) ?>">
    <input type="hidden" name="city"      value="<?= htmlspecialchars($city) ?>">
    <input type="hidden" name="address"   value="<?= htmlspecialchars($address) ?>">
  </form>
  <script>
    document.getElementById('redirectForm').submit();
  </script>
</body>
</html>
