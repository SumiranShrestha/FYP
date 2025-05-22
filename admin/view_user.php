<?php
session_start();
include("server/connection.php");

if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = "No user ID specified";
    $_SESSION['alert_type'] = "danger";
    header("Location: manage_users.php");
    exit();
}

$user_id = $_GET['id'];

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['alert_message'] = "User not found";
    $_SESSION['alert_type'] = "danger";
    header("Location: manage_users.php");
    exit();
}

$user = $result->fetch_assoc();

// Fetch user's orders
$has_orders = false;
$orders = [];
if ($conn->query("SHOW TABLES LIKE 'orders'")->num_rows > 0) {
    $order_stmt = $conn->prepare("SELECT id, created_at, status, total_price FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $order_stmt->bind_param("i", $user_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    
    if ($order_result->num_rows > 0) {
        $has_orders = true;
        while ($row = $order_result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User | Admin Dashboard</title>
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
                        <a class="nav-link active" href="manage_users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_products.php">Products</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-light me-3">Welcome, <?php echo htmlspecialchars($_SESSION["admin_username"]); ?></span>
                    <a href="admin_logout.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-person-badge me-2"></i>User Profile</h2>
            <a href="manage_users.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Users
            </a>
        </div>

        <!-- User Profile Card -->
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">User Information</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <div class="avatar-circle mx-auto mb-3" style="width: 100px; height: 100px; background-color: #e9ecef; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-person-fill" style="font-size: 3rem; color: #6c757d;"></i>
                            </div>
                            <h4><?= htmlspecialchars($user['user_name']); ?></h4>
                            <p class="text-muted">
                                User ID: <?= $user['id']; ?>
                                <?php if (isset($user['active']) && $user['active'] == 1): ?>
                                    <span class="badge bg-success ms-2">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary ms-2">Inactive</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="list-group list-group-flush text-start mb-4">
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Email</h6>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars($user['user_email']); ?></p>
                            </div>
                            
                            <?php if (isset($user['phone'])): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Phone</h6>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars($user['phone']); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($user['created_at'])): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Registration Date</h6>
                                </div>
                                <p class="mb-1"><?= date('F j, Y', strtotime($user['created_at'])); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($user['last_login'])): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Last Login</h6>
                                </div>
                                <p class="mb-1"><?= date('F j, Y H:i', strtotime($user['last_login'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-grid gap-2">
                       
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal">
                                <i class="bi bi-trash me-1"></i>Delete User
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <!-- Address Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="bi bi-geo-alt me-2"></i>Address Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($user['address']) && !empty($user['address'])): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Shipping Address</h6>
                                    <address>
                                        <?= nl2br(htmlspecialchars($user['address'])); ?>
                                    </address>
                                </div>
                                <div class="col-md-6">
                                    <h6>Billing Address</h6>
                                    <address>
                                        <?= isset($user['billing_address']) ? nl2br(htmlspecialchars($user['billing_address'])) : 'Same as shipping address'; ?>
                                    </address>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No address information available</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="bi bi-bag me-2"></i>Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($has_orders): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Total</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td><?= $order['id']; ?></td>
                                                <td><?= date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                <td>
                                                    <?php 
                                                    switch($order['status']) {
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
                                                    ?>
                                                </td>
                                                <td>रू <?= number_format($order['total_price'], 2); ?></td>
                                                <td>
                                                    <a href="view_order.php?id=<?= $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-3">
                                <a href="user_orders.php?user_id=<?= $user['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                    View All Orders
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No orders found for this user</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the user <strong><?= htmlspecialchars($user['user_name']); ?></strong>?</p>
                    <p class="text-danger">This action cannot be undone and will remove all user data.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="manage_users.php?delete_user=<?= $user['id']; ?>" class="btn btn-danger">
                        Delete User
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date("Y"); ?> Admin Panel. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>