<?php
session_start();
include("server/connection.php");

if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch all prescription orders with user, product and prescription info
$query = "
SELECT 
    po.*, 
    u.user_name, 
    u.user_email, 
    p.name AS product_name, 
    o.id AS order_table_id
FROM prescription_orders po
JOIN users u ON po.user_id = u.id
JOIN products p ON po.product_id = p.id
LEFT JOIN orders o ON po.prescription_id = o.prescription_id AND po.user_id = o.user_id
WHERE p.prescription_required = 1
ORDER BY po.created_at DESC
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Prescription Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>

<body>
    <!-- Navbar Header (copied and adapted from manage_orders.php) -->
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
                        <a class="nav-link" href="manage_orders.php">Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_prescription_orders.php">Prescription Orders</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-light me-3">Welcome, <?= htmlspecialchars($_SESSION["admin_username"]); ?></span>
                    <!-- Logout button triggers modal -->
                    <button id="logoutBtn" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </button>
                </div>
            </div>
        </div>
    </nav>
    <!-- End Navbar Header -->

    <div class="container mt-5">
        <h2 class="mb-4"><i class="bi bi-file-medical me-2"></i>Manage Prescription Orders</h2>

        <?php if (isset($_SESSION['alert_message'])): ?>
            <div class="alert alert-<?= $_SESSION['alert_type'] ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['alert_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['alert_message'], $_SESSION['alert_type']); ?>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Order ID</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Product</th>
                        <th>Status</th>
                        <th>Lens Type</th>
                        <th>Coating</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($order = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $order['id'] ?></td>
                                <td><?= $order['order_table_id'] ? htmlspecialchars($order['order_table_id']) : '<span class="text-muted">N/A</span>' ?></td>
                                <td><?= htmlspecialchars($order['user_name']) ?></td>
                                <td><?= htmlspecialchars($order['user_email']) ?></td>
                                <td><?= htmlspecialchars($order['product_name']) ?></td>
                                <td>
                                    <span class="badge bg-<?= match ($order['status']) {
                                                                'draft' => 'secondary',
                                                                'submitted' => 'info',
                                                                'processing' => 'warning',
                                                                'shipped' => 'primary',
                                                                'delivered' => 'success',
                                                                default => 'dark'
                                                            } ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </td>
                                <td><?= ucfirst($order['lens_type']) ?></td>
                                <td><?= str_replace('_', ' ', ucfirst($order['coating_type'])) ?></td>
                                <td><?= date("M d, Y", strtotime($order['created_at'])) ?></td>
                                <td>
                                    <a href="view_prescription_order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-info">View</a>
                                    <a href="edit_prescription_order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-4">No prescription orders found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutConfirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="min-width:320px;max-width:350px;margin:auto;">
                <div class="modal-body text-center py-4">
                    <h5 class="fw-bold mb-3">Logout</h5>
                    <div class="mb-4">Are you sure you want to logout?</div>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-outline-danger px-4" data-bs-dismiss="modal">Cancel</button>
                        <a href="admin_logout.php" class="btn btn-primary px-4">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Logout confirmation logic
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        var modal = new bootstrap.Modal(document.getElementById('logoutConfirmModal'));
        modal.show();
    });
    </script>
</body>
</html>