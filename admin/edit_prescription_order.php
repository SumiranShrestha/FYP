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
                <input type="number" step="0.01" name="right_eye_sphere" class="form-control" value="<?= htmlspecialchars($order['right_eye_sphere']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Right Eye Cylinder</label>
                <input type="number" step="0.01" name="right_eye_cylinder" class="form-control" value="<?= htmlspecialchars($order['right_eye_cylinder']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Right Eye Axis</label>
                <input type="number" name="right_eye_axis" class="form-control" value="<?= htmlspecialchars($order['right_eye_axis']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Right Eye PD</label>
                <input type="number" step="0.01" name="right_eye_pd" class="form-control" value="<?= htmlspecialchars($order['right_eye_pd']) ?>">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">Left Eye Sphere</label>
                <input type="number" step="0.01" name="left_eye_sphere" class="form-control" value="<?= htmlspecialchars($order['left_eye_sphere']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Left Eye Cylinder</label>
                <input type="number" step="0.01" name="left_eye_cylinder" class="form-control" value="<?= htmlspecialchars($order['left_eye_cylinder']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Left Eye Axis</label>
                <input type="number" name="left_eye_axis" class="form-control" value="<?= htmlspecialchars($order['left_eye_axis']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Left Eye PD</label>
                <input type="number" step="0.01" name="left_eye_pd" class="form-control" value="<?= htmlspecialchars($order['left_eye_pd']) ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Update Order</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
