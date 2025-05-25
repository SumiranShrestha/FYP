<?php
session_start();
include("server/connection.php");

// Add PHPMailer for sending emails
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../PHPMailer-master/src/PHPMailer.php';
require_once '../PHPMailer-master/src/SMTP.php';
require_once '../PHPMailer-master/src/Exception.php';

if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = "No order ID specified";
    $_SESSION['alert_type'] = "danger";
    header("Location: manage_users.php");
    exit();
}

$order_id = $_GET['id'];

// Fetch order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['alert_message'] = "Order not found";
    $_SESSION['alert_type'] = "danger";
    header("Location: manage_users.php");
    exit();
}

$order = $result->fetch_assoc();

// If prescription order, fetch prescription details
$prescription = null;
if (!empty($order['prescription_id'])) {
    $stmt = $conn->prepare("SELECT * FROM prescription_frames WHERE id = ?");
    $stmt->bind_param("i", $order['prescription_id']);
    $stmt->execute();
    $presc_result = $stmt->get_result();
    if ($presc_result->num_rows > 0) {
        $prescription = $presc_result->fetch_assoc();
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // Fetch current status from DB to prevent bypass
    $current_status_stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
    $current_status_stmt->bind_param("i", $order_id);
    $current_status_stmt->execute();
    $current_status_result = $current_status_stmt->get_result();
    $block_update = false;
    $send_mail = false;
    if ($current_status_result && $current_status_result->num_rows > 0) {
        $row = $current_status_result->fetch_assoc();
        if ($row['status'] === 'Delivered' || $row['status'] === 'Cancelled') {
            $block_update = true;
        }
        if ($row['status'] !== $_POST['status']) {
            $send_mail = true;
        }
    }
    if ($block_update) {
        $_SESSION['alert_message'] = "Cannot change status. Order is already '{$row['status']}'.";
        $_SESSION['alert_type'] = "warning";
        header("Location: view_order.php?id=" . $order_id);
        exit();
    }

    $new_status = $_POST['status'];
    $update_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_status, $order_id);
    if ($update_stmt->execute()) {
        if ($send_mail) {
            // Fetch user email and name for this order
            $user_stmt = $conn->prepare("SELECT u.user_email, u.user_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
            $user_stmt->bind_param("i", $order_id);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            if ($user_result && $user_result->num_rows > 0) {
                $user = $user_result->fetch_assoc();
                $user_email = $user['user_email'];
                $user_name = $user['user_name'];

                // Send email notification
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'np03cs4s230199@heraldcollege.edu.np'; // Replace with your Gmail
                    $mail->Password = 'gwwj hdus ymxk eluw'; // Replace with Gmail App Password
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('np03cs4s230199@heraldcollege.edu.np', 'Shady Shades');
                    $mail->addAddress($user_email, $user_name);
                    $mail->Subject = 'Your Order Status Has Changed';
                    $mail->Body = "Dear $user_name,\n\nYour order (Order ID: $order_id) status has been updated to '$new_status'.\n\nThank you for shopping with Shady Shades!";

                    $mail->send();
                } catch (Exception $e) {
                    // Optionally log or handle email error, but do not block the process
                }
            }
        }
        $_SESSION['alert_message'] = "Order status updated successfully";
        $_SESSION['alert_type'] = "success";
        // Refresh the page to show the updated status
        header("Location: view_order.php?id=" . $order_id);
        exit();
    } else {
        $_SESSION['alert_message'] = "Failed to update order status";
        $_SESSION['alert_type'] = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order | Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
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
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-light me-3">Welcome, <?php echo htmlspecialchars($_SESSION["admin_username"]); ?></span>
                    <!-- Logout button triggers modal -->
                    <button id="logoutBtn" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-bag me-2"></i>Order Details</h2>
            <a href="manage_orders.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Orders
            </a>
        </div>

        <!-- Order Details -->
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">Order #<?= $order['id']; ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Order Information</h6>
                        <p><strong>Date:</strong> <?= date('F j, Y', strtotime($order['created_at'])); ?></p>
                        <p><strong>Status:</strong> 
                            <?php
                            $status = $order['status'];
                            if (is_null($status) || $status === '') {
                                // Treat NULL or empty as Pending
                                echo '<span class="badge bg-warning text-dark">Pending</span>';
                            } else {
                                switch($status) {
                                    case 'Pending':
                                        echo '<span class="badge bg-warning text-dark">Pending</span>';
                                        break;
                                    case 'Processing':
                                        echo '<span class="badge bg-info text-dark">Processing</span>';
                                        break;
                                    case 'Shipped':
                                        echo '<span class="badge bg-primary">Shipped</span>';
                                        break;
                                    case 'Delivered':
                                        echo '<span class="badge bg-success">Delivered</span>';
                                        break;
                                    case 'Cancelled':
                                        echo '<span class="badge bg-danger">Cancelled</span>';
                                        break;
                                    default:
                                        echo '<span class="badge bg-secondary">Unknown</span>';
                                }
                            }
                            ?>
                        </p>
                        <p><strong>Total Price:</strong> रू <?= number_format($order['total_price'], 2); ?></p>
                        <?php if ($prescription): ?>
                            <div class="alert alert-info mt-3 mb-0">
                                <strong>This is a prescription order.</strong>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6>Customer Information</h6>
                        <p><strong>Name:</strong> <?= htmlspecialchars($order['full_name']); ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($order['email']); ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']); ?></p>
                        <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($order['address'])); ?></p>
                    </div>
                </div>

                <?php if ($prescription): ?>
                <hr>
                <h6 class="mt-4 mb-2 text-primary">Prescription Details</h6>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Right Eye (SPH/CYL/Axis):</strong><br>
                        <?= htmlspecialchars($prescription['right_eye_sphere']) ?> /
                        <?= htmlspecialchars($prescription['right_eye_cylinder']) ?> /
                        <?= htmlspecialchars($prescription['right_eye_axis']) ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Left Eye (SPH/CYL/Axis):</strong><br>
                        <?= htmlspecialchars($prescription['left_eye_sphere']) ?> /
                        <?= htmlspecialchars($prescription['left_eye_cylinder']) ?> /
                        <?= htmlspecialchars($prescription['left_eye_axis']) ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Status Update Form -->
                <form method="POST" action="view_order.php?id=<?= $order['id']; ?>" class="mt-4">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="status" class="form-label"><strong>Update Status:</strong></label>
                            <?php
                            $is_final = ($order['status'] === 'Delivered' || $order['status'] === 'Cancelled');
                            ?>
                            <select name="status" id="status" class="form-select" <?= $is_final ? 'disabled' : '' ?>>
                                <option value="Pending" <?= $order['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Processing" <?= $order['status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="Shipped" <?= $order['status'] === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="Delivered" <?= $order['status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="Cancelled" <?= $order['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <?php if ($is_final): ?>
                                <div class="small text-muted mt-1">Status cannot be changed</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" name="update_status" class="btn btn-primary" <?= $is_final ? 'disabled' : '' ?>>
                                <i class="bi bi-arrow-repeat me-1"></i>Update Status
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date("Y"); ?> Admin Panel. All rights reserved.</p>
        </div>
    </footer>

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

    <!-- Bootstrap JS Bundle with Popper -->
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