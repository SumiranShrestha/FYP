<?php
session_start();
include("server/connection.php");

if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>Invalid order ID.</div>";
    exit();
}

$order_id = intval($_GET['id']);

// Fetch prescription order details
$query = "
SELECT po.*, u.user_name, u.user_email, p.name AS product_name
FROM prescription_orders po
JOIN users u ON po.user_id = u.id
JOIN products p ON po.product_id = p.id
WHERE po.id = ?
LIMIT 1
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    echo "<div class='alert alert-danger'>Prescription order not found.</div>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prescription Order Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <a href="manage_prescription_orders.php" class="btn btn-secondary mb-3">&larr; Back to Orders</a>
    <h2 class="mb-4"><i class="bi bi-eye me-2"></i>Prescription Order #<?= htmlspecialchars($order['id']) ?></h2>
    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Order Information</h5>
            <div class="row mb-2">
                <div class="col-md-4"><strong>User:</strong> <?= htmlspecialchars($order['user_name']) ?></div>
                <div class="col-md-4"><strong>Email:</strong> <?= htmlspecialchars($order['user_email']) ?></div>
                <div class="col-md-4"><strong>Product:</strong> <?= htmlspecialchars($order['product_name']) ?></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4"><strong>Status:</strong> <span class="badge bg-<?= match ($order['status']) {
                    'draft' => 'secondary',
                    'submitted' => 'info',
                    'processing' => 'warning',
                    'shipped' => 'primary',
                    'delivered' => 'success',
                    default => 'dark'
                } ?>"><?= ucfirst($order['status']) ?></span></div>
                <div class="col-md-4"><strong>Order Type:</strong> <?= ucfirst(str_replace('_', ' ', $order['order_type'])) ?></div>
                <div class="col-md-4"><strong>Created At:</strong> <?= date("M d, Y H:i", strtotime($order['created_at'])) ?></div>
            </div>
            <hr>
            <h5 class="mb-3">Prescription Details</h5>
            <div class="row mb-2">
                <div class="col-md-6">
                    <strong>Right Eye (SPH/CYL/Axis/PD):</strong><br>
                    <?= htmlspecialchars($order['right_eye_sphere']) ?> /
                    <?= htmlspecialchars($order['right_eye_cylinder']) ?> /
                    <?= htmlspecialchars($order['right_eye_axis']) ?> /
                    <?= htmlspecialchars($order['right_eye_pd']) ?>
                </div>
                <div class="col-md-6">
                    <strong>Left Eye (SPH/CYL/Axis/PD):</strong><br>
                    <?= htmlspecialchars($order['left_eye_sphere']) ?> /
                    <?= htmlspecialchars($order['left_eye_cylinder']) ?> /
                    <?= htmlspecialchars($order['left_eye_axis']) ?> /
                    <?= htmlspecialchars($order['left_eye_pd']) ?>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4"><strong>Lens Type:</strong> <?= ucfirst($order['lens_type']) ?></div>
                <div class="col-md-4"><strong>Coating:</strong> <?= str_replace('_', ' ', ucfirst($order['coating_type'])) ?></div>
                <div class="col-md-4"><strong>Frame Color:</strong> <?= htmlspecialchars($order['frame_color']) ?></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4"><strong>Frame Size:</strong> <?= htmlspecialchars($order['frame_size']) ?></div>
                <div class="col-md-4"><strong>Prescription ID:</strong> <?= htmlspecialchars($order['prescription_id']) ?></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-12"><strong>Last Updated:</strong> <?= date("M d, Y H:i", strtotime($order['updated_at'])) ?></div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
