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

// Fetch order
$query = "SELECT po.*, u.user_name, u.user_email, p.name AS product_name
          FROM prescription_orders po
          JOIN users u ON po.user_id = u.id
          JOIN products p ON po.product_id = p.id
          WHERE po.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    echo "<div class='alert alert-danger'>Prescription order not found.</div>";
    exit();
}

// Fetch prescription frame if with_prescription and prescription_id exists
$prescription = null;
if ($order['order_type'] === 'with_prescription' && !empty($order['prescription_id'])) {
    $presc_stmt = $conn->prepare("SELECT * FROM prescription_frames WHERE id = ?");
    $presc_stmt->bind_param("i", $order['prescription_id']);
    $presc_stmt->execute();
    $presc_result = $presc_stmt->get_result();
    if ($presc_result && $presc_result->num_rows > 0) {
        $prescription = $presc_result->fetch_assoc();
    }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $lens_type = $_POST['lens_type'];
    $coating_type = $_POST['coating_type'];
    $frame_color = $_POST['frame_color'];
    $frame_size = $_POST['frame_size'];
    $right_eye_sphere = $_POST['right_eye_sphere'];
    $right_eye_cylinder = $_POST['right_eye_cylinder'];
    $right_eye_axis = $_POST['right_eye_axis'];
    $right_eye_pd = $_POST['right_eye_pd'];
    $left_eye_sphere = $_POST['left_eye_sphere'];
    $left_eye_cylinder = $_POST['left_eye_cylinder'];
    $left_eye_axis = $_POST['left_eye_axis'];
    $left_eye_pd = $_POST['left_eye_pd'];
    $order_type = $_POST['order_type'];

    $update = $conn->prepare("UPDATE prescription_orders SET 
        status=?, lens_type=?, coating_type=?, frame_color=?, frame_size=?, 
        right_eye_sphere=?, right_eye_cylinder=?, right_eye_axis=?, right_eye_pd=?, 
        left_eye_sphere=?, left_eye_cylinder=?, left_eye_axis=?, left_eye_pd=?, order_type=?
        WHERE id=?");
    $update->bind_param(
        "ssssssssssssssi",
        $status, $lens_type, $coating_type, $frame_color, $frame_size,
        $right_eye_sphere, $right_eye_cylinder, $right_eye_axis, $right_eye_pd,
        $left_eye_sphere, $left_eye_cylinder, $left_eye_axis, $left_eye_pd, $order_type, $order_id
    );
    if ($update->execute()) {
        $_SESSION['alert_message'] = "Prescription order updated successfully.";
        $_SESSION['alert_type'] = "success";
        header("Location: manage_prescription_orders.php");
        exit();
    } else {
        $error = "Update failed: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Prescription Order</title>
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
    <h2 class="mb-4">Edit Prescription Order #<?= htmlspecialchars($order['id']) ?></h2>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" class="card card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">User</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($order['user_name']) ?>" disabled>
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($order['user_email']) ?>" disabled>
            </div>
            <div class="col-md-4">
                <label class="form-label">Product</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($order['product_name']) ?>" disabled>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    <?php
                    $statuses = ['draft','submitted','processing','shipped','delivered'];
                    foreach ($statuses as $s) {
                        echo "<option value=\"$s\"".($order['status']==$s?' selected':'').">".ucfirst($s)."</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Lens Type</label>
                <select name="lens_type" class="form-select" required>
                    <?php
                    $lenses = ['single_vision','bifocal','progressive','transition'];
                    foreach ($lenses as $l) {
                        echo "<option value=\"$l\"".($order['lens_type']==$l?' selected':'').">".ucfirst(str_replace('_',' ',$l))."</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Coating</label>
                <select name="coating_type" class="form-select" required>
                    <?php
                    $coatings = ['anti_reflective','blue_light','scratch_resistant','uv_protection'];
                    foreach ($coatings as $c) {
                        echo "<option value=\"$c\"".($order['coating_type']==$c?' selected':'').">".ucfirst(str_replace('_',' ',$c))."</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Order Type</label>
                <select name="order_type" class="form-select" required>
                    <option value="with_prescription" <?= $order['order_type']=='with_prescription'?'selected':'' ?>>With Prescription</option>
                    <option value="without_prescription" <?= $order['order_type']=='without_prescription'?'selected':'' ?>>Without Prescription</option>
                </select>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">Frame Color</label>
                <input type="text" name="frame_color" class="form-control" value="<?= htmlspecialchars($order['frame_color']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Frame Size</label>
                <select name="frame_size" class="form-select">
                    <option value="">Select</option>
                    <option value="small" <?= $order['frame_size']=='small'?'selected':'' ?>>Small</option>
                    <option value="medium" <?= $order['frame_size']=='medium'?'selected':'' ?>>Medium</option>
                    <option value="large" <?= $order['frame_size']=='large'?'selected':'' ?>>Large</option>
                </select>
            </div>
        </div>
        <h5 class="mt-4">Prescription Details</h5>
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">Right Eye Sphere</label>
                <input type="number" step="0.01" name="right_eye_sphere" class="form-control"
                    value="<?= htmlspecialchars(
                        $prescription['right_eye_sphere'] ?? $order['right_eye_sphere']
                    ) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Right Eye Cylinder</label>
                <input type="number" step="0.01" name="right_eye_cylinder" class="form-control"
                    value="<?= htmlspecialchars(
                        $prescription['right_eye_cylinder'] ?? $order['right_eye_cylinder']
                    ) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Right Eye Axis</label>
                <input type="number" name="right_eye_axis" class="form-control"
                    value="<?= htmlspecialchars(
                        $prescription['right_eye_axis'] ?? $order['right_eye_axis']
                    ) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Right Eye PD</label>
                <input type="number" step="0.01" name="right_eye_pd" class="form-control"
                    value="<?= htmlspecialchars(
                        $prescription['right_eye_pd'] ?? $order['right_eye_pd']
                    ) ?>">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">Left Eye Sphere</label>
                <input type="number" step="0.01" name="left_eye_sphere" class="form-control"
                    value="<?= htmlspecialchars(
                        $prescription['left_eye_sphere'] ?? $order['left_eye_sphere']
                    ) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Left Eye Cylinder</label>
                <input type="number" step="0.01" name="left_eye_cylinder" class="form-control"
                    value="<?= htmlspecialchars(
                        $prescription['left_eye_cylinder'] ?? $order['left_eye_cylinder']
                    ) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Left Eye Axis</label>
                <input type="number" name="left_eye_axis" class="form-control"
                    value="<?= htmlspecialchars(
                        $prescription['left_eye_axis'] ?? $order['left_eye_axis']
                    ) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Left Eye PD</label>
                <input type="number" step="0.01" name="left_eye_pd" class="form-control"
                    value="<?= htmlspecialchars(
                        $prescription['left_eye_pd'] ?? $order['left_eye_pd']
                    ) ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Update Order</button>
    </form>
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
