<?php
session_start();
include("server/connection.php");

if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch all orders from the database along with user and product details
$orders_result = $conn->query("SELECT o.id AS order_id, 
                                      u.user_email, 
                                      u.user_name,  -- Fetch the user's name
                                      o.full_name,
                                      o.total_price,
                                      o.status, 
                                      o.created_at,
                                      GROUP_CONCAT(p.name SEPARATOR ', ') AS products,
                                      GROUP_CONCAT(oi.quantity SEPARATOR ', ') AS quantities
                               FROM orders o
                               JOIN users u ON o.user_id = u.id
                               LEFT JOIN order_items oi ON o.id = oi.order_id
                               LEFT JOIN products p ON oi.product_id = p.id
                               GROUP BY o.id
                               ORDER BY o.id DESC");

if (!$orders_result) {
    die("Error fetching orders: " . $conn->error);
}

// Handle Order Status Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_order_status"])) {
    $order_id = $_POST["order_id"];
    $status = $_POST["status"];

    // Prepare the update query
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);

    if ($stmt->execute()) {
        $_SESSION['alert_message'] = "Order status updated successfully";
        $_SESSION['alert_type'] = "success";
    } else {
        $_SESSION['alert_message'] = "Error updating order status: " . $conn->error;
        $_SESSION['alert_type'] = "danger";
    }

    // Refresh the page to show the updated status
    header("Location: manage_orders.php");
    exit();
}

// Handle Order Deletion
if (isset($_GET["delete_order"])) {
    $order_id = $_GET["delete_order"];
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);

    if ($stmt->execute()) {
        $_SESSION['alert_message'] = "Order deleted successfully";
        $_SESSION['alert_type'] = "success";
    } else {
        $_SESSION['alert_message'] = "Error deleting order: " . $conn->error;
        $_SESSION['alert_type'] = "danger";
    }

    header("Location: manage_orders.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">Shady Shades Admin</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_orders.php">Orders</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-light me-3">Welcome, <?= htmlspecialchars($_SESSION["admin_username"]); ?></span>
                    <a href="admin_logout.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-cart-check me-2"></i>Manage Orders</h2>
            <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>

        <?php if (isset($_SESSION['alert_message'])): ?>
            <div class="alert alert-<?= $_SESSION['alert_type'] ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['alert_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['alert_message']); unset($_SESSION['alert_type']); ?>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Products</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders_result->num_rows > 0): ?>
                        <?php while ($order = $orders_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $order['order_id']; ?></td>
                                <td>
                                    <?= htmlspecialchars($order['user_name']); ?><br>
                                    <?= htmlspecialchars($order['user_email']); ?>
                                </td>
                                <td>
                                    <!-- Split the products and quantities for display -->
                                    <?php 
                                    $products = explode(',', $order['products']);
                                    $quantities = explode(',', $order['quantities']);
                                    $product_display = '';
                                    foreach ($products as $index => $product) {
                                        $product_display .= htmlspecialchars($product) . " (x" . htmlspecialchars($quantities[$index]) . "), ";
                                    }
                                    echo rtrim($product_display, ", ");
                                    ?>
                                </td>
                                <td>रू <?= number_format($order['total_price'], 2); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="update_order_status" value="1">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id']; ?>">
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="view_order.php?id=<?= $order['order_id']; ?>" class="btn btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="manage_orders.php?delete_order=<?= $order['order_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this order?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">No orders found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>