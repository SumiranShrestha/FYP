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
SELECT 
    po.*, 
    u.user_name, 
    u.user_email, 
    p.name AS product_name,
    pf.right_eye_sphere AS pf_right_eye_sphere,
    pf.right_eye_cylinder AS pf_right_eye_cylinder,
    pf.right_eye_axis AS pf_right_eye_axis,
    pf.right_eye_pd AS pf_right_eye_pd,
    pf.left_eye_sphere AS pf_left_eye_sphere,
    pf.left_eye_cylinder AS pf_left_eye_cylinder,
    pf.left_eye_axis AS pf_left_eye_axis,
    pf.left_eye_pd AS pf_left_eye_pd,
    pf.lens_type AS pf_lens_type,
    pf.coating_type AS pf_coating_type,
    pf.frame_model AS pf_frame_model
FROM prescription_orders po
JOIN users u ON po.user_id = u.id
JOIN products p ON po.product_id = p.id
LEFT JOIN prescription_frames pf ON po.prescription_id = pf.id
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
    <!-- Navbar Header (same as manage_orders.php/manage_prescription_orders.php) -->
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
                    <?php
                        $sph = $order['pf_right_eye_sphere'] ?? $order['right_eye_sphere'] ?? '';
                        $cyl = $order['pf_right_eye_cylinder'] ?? $order['right_eye_cylinder'] ?? '';
                        $axis = $order['pf_right_eye_axis'] ?? $order['right_eye_axis'] ?? '';
                        $pd = $order['pf_right_eye_pd'] ?? $order['right_eye_pd'] ?? '';
                        $right_eye = [];
                        if ($sph !== '' && $sph !== null) $right_eye[] = htmlspecialchars($sph);
                        if ($cyl !== '' && $cyl !== null) $right_eye[] = htmlspecialchars($cyl);
                        if ($axis !== '' && $axis !== null) $right_eye[] = htmlspecialchars($axis);
                        if ($pd !== '' && $pd !== null) $right_eye[] = htmlspecialchars($pd);
                        echo !empty($right_eye) ? implode(' / ', $right_eye) : '<span class="text-muted">N/A</span>';
                    ?>
                </div>
                <div class="col-md-6">
                    <strong>Left Eye (SPH/CYL/Axis/PD):</strong><br>
                    <?php
                        $sph = $order['pf_left_eye_sphere'] ?? $order['left_eye_sphere'] ?? '';
                        $cyl = $order['pf_left_eye_cylinder'] ?? $order['left_eye_cylinder'] ?? '';
                        $axis = $order['pf_left_eye_axis'] ?? $order['left_eye_axis'] ?? '';
                        $pd = $order['pf_left_eye_pd'] ?? $order['left_eye_pd'] ?? '';
                        $left_eye = [];
                        if ($sph !== '' && $sph !== null) $left_eye[] = htmlspecialchars($sph);
                        if ($cyl !== '' && $cyl !== null) $left_eye[] = htmlspecialchars($cyl);
                        if ($axis !== '' && $axis !== null) $left_eye[] = htmlspecialchars($axis);
                        if ($pd !== '' && $pd !== null) $left_eye[] = htmlspecialchars($pd);
                        echo !empty($left_eye) ? implode(' / ', $left_eye) : '<span class="text-muted">N/A</span>';
                    ?>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4"><strong>Lens Type:</strong> <?= htmlspecialchars($order['pf_lens_type'] ?? $order['lens_type'] ?? 'N/A') ?></div>
                <div class="col-md-4"><strong>Coating:</strong> <?= htmlspecialchars(str_replace('_', ' ', ucfirst($order['pf_coating_type'] ?? $order['coating_type'] ?? 'N/A'))) ?></div>
                <div class="col-md-4"><strong>Frame Color:</strong> <?= htmlspecialchars($order['pf_frame_model'] ?? $order['frame_color'] ?? 'N/A') ?></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4"><strong>Frame Size:</strong> <?= htmlspecialchars($order['frame_size'] ?? 'N/A') ?></div>
                <div class="col-md-4"><strong>Prescription ID:</strong> <?= htmlspecialchars($order['prescription_id'] ?? 'N/A') ?></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-12"><strong>Last Updated:</strong> <?= date("M d, Y H:i", strtotime($order['updated_at'])) ?></div>
            </div>
        </div>
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
