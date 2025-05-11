<?php
session_start();
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}
require_once("../server/connection.php");

// Fetch prescription orders with user and product info
$sql = "SELECT po.*, u.user_name, p.name AS product_name
        FROM prescription_orders po
        LEFT JOIN users u ON po.user_id = u.id
        LEFT JOIN products p ON po.product_id = p.id
        ORDER BY po.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Prescription Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h2 class="mb-4">Prescription Orders</h2>
        <a href="admin_dashboard.php" class="btn btn-secondary mb-3">&larr; Back to Dashboard</a>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Product</th>
                        <th>Order Type</th>
                        <th>Right Eye (SPH/CYL/Axis/PD)</th>
                        <th>Left Eye (SPH/CYL/Axis/PD)</th>
                        <th>Lens Type</th>
                        <th>Coating</th>
                        <th>Frame Color</th>
                        <th>Frame Size</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td><?= htmlspecialchars($row['user_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($row['product_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($row['order_type']) ?></td>
                            <td>
                                <?= htmlspecialchars($row['right_eye_sphere']) ?>/<?= htmlspecialchars($row['right_eye_cylinder']) ?>/<?= htmlspecialchars($row['right_eye_axis']) ?>/<?= htmlspecialchars($row['right_eye_pd']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['left_eye_sphere']) ?>/<?= htmlspecialchars($row['left_eye_cylinder']) ?>/<?= htmlspecialchars($row['left_eye_axis']) ?>/<?= htmlspecialchars($row['left_eye_pd']) ?>
                            </td>
                            <td><?= htmlspecialchars($row['lens_type']) ?></td>
                            <td><?= htmlspecialchars($row['coating_type']) ?></td>
                            <td><?= htmlspecialchars($row['frame_color']) ?></td>
                            <td><?= htmlspecialchars($row['frame_size']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="12" class="text-center">No prescription orders found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
