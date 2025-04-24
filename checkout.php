<?php
// checkout.php

// Start output buffering to avoid header already sent errors
ob_start();
include('header.php');

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include('server/connection.php'); // Database connection

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch cart items and calculate totals
$cart_items      = [];
$total_price     = 0;
$delivery_charge = 100;

$stmt = $conn->prepare("
    SELECT c.*, p.name, p.price, p.discount_price, p.images
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $unit_price   = $row['discount_price'] > 0 ? $row['discount_price'] : $row['price'];
    $total_price += $unit_price * $row['quantity'];
}
$stmt->close();

$grand_total = $total_price + $delivery_charge;

// Generate a unique purchase order ID
$purchase_order_id   = 'order_' . time();
$purchase_order_name = 'Shady Shades Order';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Checkout | Shady Shades</title>

  <!-- Bootstrap CSS -->
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
  />

  <style>
    body {
      background-color: #fff;
    }
    .checkout-container {
      margin-top: 2rem;
      margin-bottom: 2rem;
    }
    .checkout-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1rem;
    }
    .checkout-header a {
      text-decoration: none;
      color: #16a34a;
      font-size: 1.2rem;
    }
    .order-summary {
      border: 1px solid #ddd;
      border-radius: 4px;
      padding: 1rem;
    }
    .order-summary .order-item img {
      width: 64px;
      height: auto;
      object-fit: cover;
    }
    .place-order-btn {
      width: 100%;
    }
    /* Payment card styles */
    .payment-options {
      display: flex;
      gap: 1rem;
    }
    .payment-card input {
      display: none;
    }
    .payment-card .card {
      cursor: pointer;
      transition: border-color 0.2s, box-shadow 0.2s;
      border: 1px solid #ddd;
    }
    .payment-card input:checked + .card {
      border: 2px solid #16a34a;
      box-shadow: 0 0 8px rgba(22,163,74,0.3);
    }
    .payment-card img {
      max-height: 80px;
      object-fit: contain;
      padding: 1rem;
    }
  </style>
</head>
<body>

<div class="container checkout-container">
  <div class="checkout-header">
    <a href="cart.php" aria-label="Go back">←</a>
    <h2 class="mb-0">Checkout</h2>
  </div>

  <div class="row">
    <!-- LEFT: Form -->
    <div class="col-md-8">
      <form id="checkoutForm" method="post">
        <!-- Hidden fields for Khalti request -->
        <input type="hidden" name="submit" value="1">
        <input type="hidden" name="inputAmount4" value="<?= $grand_total ?>">
        <input type="hidden" name="inputPurchasedOrderId4" value="<?= $purchase_order_id ?>">
        <input type="hidden" name="inputPurchasedOrderName4" value="<?= $purchase_order_name ?>">

        <!-- 1. General Information -->
        <h5 class="mb-3">1. General Information</h5>
        <input type="hidden" name="user_id" value="<?= $user_id; ?>">
        <div class="mb-3">
          <label class="form-label">Full Name *</label>
          <input
            type="text"
            name="inputName"
            class="form-control"
            required
            value="<?= htmlspecialchars($user['full_name'] ?? ''); ?>"
          />
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input
            type="email"
            name="inputEmail"
            class="form-control"
            required
            value="<?= htmlspecialchars($user['email'] ?? ''); ?>"
          />
        </div>
        <div class="mb-3">
          <label class="form-label">Phone Number *</label>
          <div class="input-group">
            <span class="input-group-text">NP</span>
            <input
              type="text"
              name="inputPhone"
              class="form-control"
              required
              maxlength="10"
              pattern="\d{10}"
              inputmode="numeric"
              title="Phone number must be exactly 10 digits (0–9)."
              value="<?= htmlspecialchars($user['phone'] ?? ''); ?>"
            />
          </div>
        </div>

        <!-- 2. Delivery Address -->
        <h5 class="mb-3">2. Delivery Address</h5>
        <div class="mb-3">
          <label class="form-label">City / District *</label>
          <input
            type="text"
            name="inputCity"
            class="form-control"
            required
            value="<?= htmlspecialchars($user['city'] ?? ''); ?>"
          />
        </div>
        <div class="mb-3">
          <label class="form-label">Address *</label>
          <input
            type="text"
            name="inputAddress"
            class="form-control"
            required
            value="<?= htmlspecialchars($user['address'] ?? ''); ?>"
          />
        </div>

        <!-- 3. Payment Method -->
        <h5 class="mb-3">3. Payment Method</h5>
        <div class="payment-options mb-4">
          <!-- Khalti Card -->
          <label class="payment-card">
            <input
              type="radio"
              name="payment_method"
              value="khalti"
              required
            />
            <div class="card text-center">
              <img
                src="assets/khalti.png"
                alt="Khalti"
              />
              <div class="card-body">
                <h6 class="card-title">Khalti</h6>
              </div>
            </div>
          </label>
          <!-- Cash on Delivery Card -->
          <label class="payment-card">
            <input
              type="radio"
              name="payment_method"
              value="cod"
              checked
            />
            <div class="card text-center">
              <img
                src="assets/cod.png"
                alt="Cash on Delivery"
              />
              <div class="card-body">
                <h6 class="card-title">Cash on Delivery</h6>
              </div>
            </div>
          </label>
        </div>

        <button
          type="button"
          id="placeOrderBtn"
          class="btn btn-success place-order-btn"
        >
          Place Order
        </button>
      </form>
    </div>

    <!-- RIGHT: Order Summary -->
    <div class="col-md-4">
      <div class="order-summary">
        <h5>Order Summary</h5>
        <?php foreach ($cart_items as $item): 
          $imgs       = json_decode($item['images'], true);
          $thumb      = $imgs[0] ?? 'images/default.jpg';
          $unit_price = ($item['discount_price'] > 0 ? $item['discount_price'] : $item['price']);
        ?>
          <div class="d-flex align-items-start mb-3 order-item">
            <img
              src="<?= htmlspecialchars($thumb); ?>"
              alt="Product"
              class="me-2 rounded"
            />
            <div>
              <p class="fw-bold"><?= htmlspecialchars($item['name']); ?>
                <small class="text-muted">(x<?= $item['quantity']; ?>)</small>
              </p>
              <p class="text-success fw-bold mb-0">
                ₹ <?= number_format($unit_price * $item['quantity']); ?>
              </p>
            </div>
          </div>
        <?php endforeach; ?>
        <hr />
        <div class="d-flex justify-content-between">
          <span>Delivery Charge</span>
          <span>₹ <?= number_format($delivery_charge); ?></span>
        </div>
        <div class="d-flex justify-content-between fw-bold">
          <span>Total</span>
          <span>₹ <?= number_format($grand_total); ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // preserve original submit method
  const realSubmit = HTMLFormElement.prototype.submit;

  document.getElementById("placeOrderBtn").addEventListener("click", () => {
    const form = document.getElementById("checkoutForm");
    const phoneField = form.elements.inputPhone;
    const phone = phoneField.value.trim();

    // Validate exactly 10 digits
    if (!/^\d{10}$/.test(phone)) {
      phoneField.setCustomValidity("Phone number must be exactly 10 digits.");
      phoneField.reportValidity();
      return;
    } else {
      phoneField.setCustomValidity("");
    }

    // HTML5 validation
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    // Choose action based on payment method
    const method = document.querySelector('input[name="payment_method"]:checked').value;
    form.action = method === 'khalti'
      ? 'server/khalti/payment-request.php'
      : 'place_order.php';

    // submit form
    realSubmit.call(form);
  });
</script>

</body>
</html>
