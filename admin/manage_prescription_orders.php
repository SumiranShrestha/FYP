<?php
session_start();
include("server/connection.php");

if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch all prescription orders with user, product and prescription info
$query = "
SELECT po.*, u.user_name, u.user_email, p.name AS product_name 
FROM prescription_orders po
JOIN users u ON po.user_id = u.id
JOIN products p ON po.product_id = p.id
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
</head>

<body>
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
                            <td colspan="9" class="text-center py-4">No prescription orders found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>