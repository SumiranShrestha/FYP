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
      border: 1px solid #eee;
      border-radius: 16px;
      padding: 1.5rem 1.5rem 1rem 1.5rem;
      background: #fff;
      box-shadow: 0 2px 16px 0 rgba(0,0,0,0.04);
    }
    .order-summary .order-item img {
      width: 64px;
      height: 64px;
      object-fit: cover;
      border-radius: 8px;
    }
    .order-summary .order-item {
      position: relative;
    }
    .order-summary .order-item .item-qty-badge {
      position: absolute;
      top: -8px;
      left: 48px;
      background:rgb(212, 66, 195);
      color: #fff;
      border-radius: 50%;
      width: 22px;
      height: 22px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.95rem;
      font-weight: 600;
      border: 2px solid #fff;
    }
    .order-summary hr {
      margin: 1rem 0;
    }
    .order-summary .promo-row {
      display: flex;
      gap: 0.5rem;
      margin-bottom: 1rem;
    }
    .order-summary .promo-row input {
      flex: 1;
    }
    .order-summary .promo-row button {
      min-width: 80px;
      background: #10b6c6;
      color: #fff;
      border: none;
      font-weight: 600;
    }
    .order-summary .promo-row button:disabled {
      opacity: 0.7;
    }
    .order-summary .summary-label {
      color: #222;
    }
    .order-summary .summary-value {
      font-weight: 500;
    }
    .order-summary .fw-bold {
      font-weight: 700 !important;
    }
    .order-summary .place-order-btn {
      margin-top: 1rem;
      width: 100%;
      border-radius: 6px;
      font-size: 1.1rem;
      font-weight: 600;
      background: #10b6c6;
      border: none;
      color: #fff;
      padding: 0.75rem 0;
      transition: background 0.2s;
    }
    .order-summary .place-order-btn:hover {
      background: #0e9bb0;
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
      border: 1.5px solid #ddd;
      border-radius: 12px;
      min-width: 180px;
      min-height: 90px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: #fff;
      box-shadow: 0 2px 8px 0 rgba(0,0,0,0.02);
    }
    .payment-card input:checked + .card {
      border: 2.5px solid #E873DA;
      box-shadow: 0 0 8px rgba(16,182,198,0.12);
    }
    .payment-card img {
      max-height: 40px;
      object-fit: contain;
      margin-bottom: 0.5rem;
    }
    .payment-card .card-title {
      font-size: 1rem;
      font-weight: 600;
      color: #222;
      margin-bottom: 0;
    }
    @media (max-width: 991px) {
      .order-summary {
        margin-top: 2rem;
      }
    }
    /* Form fields */
    .form-label {
      font-weight: 500;
      color: #222;
    }
    .fw-bold {
      font-weight: 700 !important;
    }
    input[type="text"], input[type="email"] {
      font-size: 1rem;
      border-radius: 8px !important;
      border: 1px solid #ddd !important;
      background: #fafbfc;
    }
    input[type="text"]:focus, input[type="email"]:focus {
      border-color: #10b6c6 !important;
      box-shadow: 0 0 0 2px rgba(16,182,198,0.08);
    }
    .input-group-text {
      background: #fafbfc;
      border-radius: 0 8px 8px 0 !important;
      border: 1px solid #ddd !important;
      color: #888;
      font-weight: 600;
    }
    .input-group input {
      border-radius: 8px 0 0 8px !important;
    }
    .form-control::placeholder {
      color: #bdbdbd;
      font-size: 0.98em;
    }
    .mb-3 {
      margin-bottom: 1.2rem !important;
    }
    .mb-1 {
      margin-bottom: 0.5rem !important;
    }
    .mb-2 {
      margin-bottom: 0.8rem !important;
    }
    .mb-4 {
      margin-bottom: 2rem !important;
    }
    .text-danger {
      color: #e53935 !important;
    }
    .btn-info {
      background: #10b6c6 !important;
      color: #fff !important;
      border: none !important;
    }
    .btn-info:disabled {
      opacity: 0.7;
    }
    .btn-success {
      background: #E873DA !important;
      border: none !important;
      color: #fff !important;
    }
    .btn-success:hover {
      background:rgb(212, 66, 195) !important;
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
        <h5 class="mb-3 fw-bold">1. General Information</h5>
        <input type="hidden" name="user_id" value="<?= $user_id; ?>">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input
              type="text"
              name="inputName"
              class="form-control"
              required
              placeholder="eg: Ram Bahadur"
              value="<?= htmlspecialchars($user['user_name'] ?? ''); ?>"
            />
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Email</label>
            <input
              type="email"
              name="inputEmail"
              class="form-control"
              required
              placeholder="eg: john@gmail.com"
              value="<?= htmlspecialchars($user['user_email'] ?? ''); ?>"
            />
          </div>
        </div>
        <div class="row">
          <div class="col-md-8 mb-3">
            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
            <div class="input-group">
              <input
                type="text"
                name="inputPhone"
                class="form-control"
                required
                maxlength="10"
                pattern="\d{10}"
                inputmode="numeric"
                title="Phone number must be exactly 10 digits (0–9)."
                placeholder="eg: 9862200000"
                value="<?= htmlspecialchars($user['phone'] ?? ''); ?>"
              />
              <span class="input-group-text">NP</span>
            </div>
          </div>
          <div class="col-md-4 mb-3"></div>
        </div>
        <div class="mb-3">
          <label class="form-label">Order Note (any message for us)</label>
          <input
            type="text"
            name="order_note"
            class="form-control"
            placeholder="eg: I was searching for this product from so long."
            value="<?= isset($_POST['order_note']) ? htmlspecialchars($_POST['order_note']) : '' ?>"
          />
        </div>

        <!-- 2. Delivery Address -->
        <h5 class="mb-3 fw-bold">2. Delivery Address</h5>
        <div class="mb-3">
          <label class="form-label">City / District <span class="text-danger">*</span></label>
          <input
            type="text"
            name="inputCity"
            class="form-control"
            required
            placeholder="Kathmandu Inside Ring Road"
            value="<?= htmlspecialchars($user['city'] ?? ''); ?>"
          />
        </div>
        <div class="row">
          <div class="col-md-8 mb-3">
            <label class="form-label">Address <span class="text-danger">*</span></label>
            <input
              type="text"
              name="inputAddress"
              class="form-control"
              required
              placeholder="eg: kathmandu, tinkune"
              value="<?= htmlspecialchars($user['address'] ?? ''); ?>"
            />
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Landmark</label>
            <input
              type="text"
              name="landmark"
              class="form-control"
              placeholder="eg: madan bhandari park"
            />
          </div>
        </div>

        <!-- 3. Payment Method -->
        <h5 class="mb-3 fw-bold">3. Payment Method</h5>
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

      </form>
    </div>

    <!-- RIGHT: Order Summary -->
    <div class="col-md-4">
      <div class="order-summary">
        <h5 class="fw-bold mb-3">Order Summary</h5>
        <?php foreach ($cart_items as $item): 
          $imgs       = json_decode($item['images'], true);
          $thumb      = $imgs[0] ?? 'images/default.jpg';
          $unit_price = ($item['discount_price'] > 0 ? $item['discount_price'] : $item['price']);
        ?>
          <div class="d-flex align-items-start mb-3 order-item">
            <div style="position:relative;">
              <img
                src="<?= htmlspecialchars($thumb); ?>"
                alt="Product"
                class="me-2"
              />
              <span class="item-qty-badge"><?= $item['quantity']; ?></span>
            </div>
            <div>
              <div class="fw-bold" style="font-size:1rem;"><?= htmlspecialchars($item['name']); ?></div>
              <div class="text-success fw-bold mb-0" style="font-size:1rem;">
                ₹ <?= number_format($unit_price); ?> <span class="text-muted" style="font-size:0.95em;">× <?= $item['quantity']; ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        <hr />
        <div class="d-flex justify-content-between mb-1">
          <span class="summary-label">Sub-total</span>
          <span class="summary-value">₹ <?= number_format($total_price); ?></span>
        </div>
        <div class="d-flex justify-content-between mb-1">
          <span class="summary-label">Delivery Charge</span>
          <span class="summary-value">₹ <?= number_format($delivery_charge); ?></span>
        </div>
        <div class="d-flex justify-content-between fw-bold mb-3" style="font-size:1.1rem;">
          <span>Total</span>
          <span>₹ <?= number_format($grand_total); ?></span>
        </div>
        <?php if (!empty($_POST['order_note'])): ?>
          <div class="mb-2">
            <span class="fw-bold">Order Note:</span>
            <span><?= htmlspecialchars($_POST['order_note']) ?></span>
          </div>
        <?php endif; ?>
        <?php if (empty($cart_items)): ?>
          <div class="alert alert-warning mb-2">
            Your cart is empty. Please add items to your cart before placing an order.
          </div>
        <?php endif; ?>
        <button
          type="button"
          id="placeOrderBtn"
          class="btn btn-success place-order-btn"
          <?php if (empty($cart_items)) echo 'disabled'; ?>
        >
          Place Order
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  // preserve original submit method
  const realSubmit = HTMLFormElement.prototype.submit;

  function validateAndSubmit() {
    // Prevent submission if cart is empty
    <?php if (empty($cart_items)): ?>
      alert("Your cart is empty. Please add items to your cart before placing an order.");
      return;
    <?php endif; ?>

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
  }

  document.getElementById("placeOrderBtn").addEventListener("click", validateAndSubmit);
  document.getElementById("placeOrderBtnSummary").addEventListener("click", validateAndSubmit);
</script>

</body>
</html>
