<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include('server/connection.php');
include('header.php');

// Get the order ID from the query string and ensure it is an integer
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$order_id) {
    echo "<div class='container py-5'><div class='alert alert-danger'>Invalid order ID.</div></div>";
    exit();
}

// Get current user's ID from the session
$user_id = $_SESSION["user_id"];

// Retrieve order details ensuring that the order belongs to the logged-in user
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    echo "<div class='container py-5'><div class='alert alert-danger'>Order not found or you do not have permission to view this order.</div></div>";
    exit();
}

$order = $order_result->fetch_assoc();

// Retrieve order items, joining with the products table to fetch product name.
// The 'p.image' column has been removed from the SELECT clause.
$stmt = $conn->prepare("SELECT oi.*, p.name as product_name 
                        FROM order_items oi 
                        LEFT JOIN products p ON oi.product_id = p.id 
                        WHERE oi.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items_result = $stmt->get_result();

$order_items = [];
while ($row = $order_items_result->fetch_assoc()) {
    $order_items[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Order #<?= htmlspecialchars($order['id']); ?> Details</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
      .order-details { margin-top: 20px; }
  </style>
</head>
<body>
  <div class="container py-5">
    <h2>Order Details</h2>
    <div class="card mb-4 order-details">
      <div class="card-header bg-primary text-white">
          Order #<?= htmlspecialchars($order['id']); ?> - <?= date('M j, Y', strtotime($order['created_at'])); ?>
      </div>
      <div class="card-body">
          <p><strong>Total Amount:</strong> Rs <?= number_format($order['total_price'], 2); ?></p>
          <p><strong>Status:</strong>
            <span class="badge 
                <?= strtolower($order['status']) === 'delivered' ? 'bg-success' : (strtolower($order['status']) === 'cancelled' ? 'bg-danger' : (strtolower($order['status']) === 'pending' ? 'bg-warning' : 'bg-secondary')) ?>">
                <?= ucfirst(strtolower($order['status'])) ?>
            </span>
          </p>
          <!-- Add any additional order details you want to display here -->
      </div>
    </div>

    <h4>Order Items</h4>
    <?php if (empty($order_items)) : ?>
      <p class="text-muted">No items found for this order.</p>
    <?php else : ?>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Product</th>
              <th>Description</th>
              <th>Quantity</th>
              <th>Price</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($order_items as $item) : ?>
              <tr>
                <td>
                  <?= htmlspecialchars($item['product_name'] ?? 'Product'); ?>
                </td>
                <td>
                  <?php 
                    // If you have an additional column for a description or options, display it here.
                    // For example: echo htmlspecialchars($item['description'] ?? ''); 
                  ?>
                </td>
                <td><?= htmlspecialchars($item['quantity']); ?></td>
                <td>Rs <?= number_format($item['price'], 2); ?></td>
                <td>Rs <?= number_format($item['quantity'] * $item['price'], 2); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <a href="profile.php" class="btn btn-secondary mt-3">
      <i class="bi bi-arrow-left me-1"></i> Back to Profile
    </a>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php 
$stmt->close();
$conn->close();
include('footer.php'); 
?>
