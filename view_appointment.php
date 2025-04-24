<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include('server/connection.php');
include('header.php');

$user_id = $_SESSION["user_id"];
$user_type = $_SESSION["user_type"] ?? 'customer'; // Default to customer if not set

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch doctor details if user is a doctor
$doctor = [];
if ($user_type === 'doctor') {
    $stmt = $conn->prepare("SELECT * FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $doctor_result = $stmt->get_result();
    $doctor = $doctor_result->fetch_assoc();
}

// Fetch user's orders
$orders = [];
$stmt = $conn->prepare("SELECT o.*, 
                       (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count 
                       FROM orders o 
                       WHERE user_id = ? 
                       ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$order_result = $stmt->get_result();
while ($row = $order_result->fetch_assoc()) {
    $orders[] = $row;
}

// Fetch user's appointments
$appointments = [];
if ($user_type === 'customer') {
    $stmt = $conn->prepare("SELECT a.*, d.full_name as doctor_name 
                          FROM appointments a 
                          JOIN doctors d ON a.doctor_id = d.id 
                          WHERE a.user_id = ? 
                          ORDER BY a.appointment_date DESC");
} else {
    // For doctors, select patient name from users table
    $stmt = $conn->prepare("SELECT a.*, u.user_name as patient_name 
                          FROM appointments a 
                          JOIN users u ON a.user_id = u.id 
                          WHERE a.doctor_id = ? 
                          ORDER BY a.appointment_date DESC");
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointment_result = $stmt->get_result();
while ($row = $appointment_result->fetch_assoc()) {
    $appointments[] = $row;
}

// Check for success or error messages
$alert_message = $_SESSION['alert_message'] ?? null;
$alert_type = $_SESSION['alert_type'] ?? null;

// Clear the messages from the session
unset($_SESSION['alert_message']);
unset($_SESSION['alert_type']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .profile-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .appointment-card {
            transition: all 0.3s ease;
        }
        .appointment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        /* Update nav-pills active tab color and text color */
        .nav-pills .nav-link.active {
            background-color: #E673DE !important;
            color: #ffffff !important;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <!-- Display Success or Error Messages -->
        <?php if ($alert_message) : ?>
            <div class="alert alert-<?= $alert_type; ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($alert_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Left column: Profile Information -->
            <div class="col-lg-4">
                <div class="card profile-card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Profile Information</h4>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                        </div>
                        
                        <h5 class="card-title"><?= htmlspecialchars($user['user_name'] ?? 'User') ?></h5>
                        <p class="text-muted mb-1"><i class="bi bi-envelope"></i> <?= htmlspecialchars($user['user_email'] ?? 'No email') ?></p>
                        <p class="text-muted mb-1"><i class="bi bi-telephone"></i> <?= htmlspecialchars($user['phone'] ?? 'No phone') ?></p>
                        <p class="text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars(($user['address'] ?? 'No address') . ', ' . ($user['city'] ?? '')) ?></p>
                        
                        <?php if ($user_type === 'doctor' && !empty($doctor)): ?>
                            <hr>
                            <h6 class="card-subtitle mb-2 text-muted">Professional Information</h6>
                            <p><strong>NMC Number:</strong> <?= htmlspecialchars($doctor['nmc_number'] ?? 'Not available') ?></p>
                            <p><strong>Specialization:</strong> <?= htmlspecialchars($doctor['specialization'] ?? 'Not specified') ?></p>
                        <?php endif; ?>
                        
                        <a href="edit_profile.php" class="btn btn-outline-primary mt-3 w-100">Edit Profile</a>
                    </div>
                </div>
            </div>
            
            <!-- Right column: Tabs for Profile, Orders, and Appointments -->
            <div class="col-lg-8">
                <ul class="nav nav-pills mb-4" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="pill" data-bs-target="#profile" type="button" role="tab">
                            Profile Details
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="orders-tab" data-bs-toggle="pill" data-bs-target="#orders" type="button" role="tab">
                            My Orders
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="appointments-tab" data-bs-toggle="pill" data-bs-target="#appointments" type="button" role="tab">
                            <?= $user_type === 'doctor' ? 'Patient Appointments' : 'My Appointments' ?>
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="profileTabsContent">
                    <!-- Profile Details Tab -->
                    <div class="tab-pane fade show active" id="profile" role="tabpanel">
                        <div class="card profile-card">
                            <div class="card-body">
                                <form method="POST" action="update_profile.php">
                                    <div class="mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="user_name" class="form-control" value="<?= htmlspecialchars($user['user_name'] ?? ''); ?>" required />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="user_email" class="form-control" value="<?= htmlspecialchars($user['user_email'] ?? ''); ?>" required />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? ''); ?>" />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($user['address'] ?? ''); ?>" />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">City</label>
                                        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($user['city'] ?? ''); ?>" />
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i>Update Profile
                                    </button>
                                </form>

                                <hr class="my-4">

                                <h5 class="mb-3">Change Password</h5>
                                <form method="POST" action="server/change_password.php">
                                    <div class="mb-3">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" name="current_password" class="form-control" required />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="new_password" class="form-control" required />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" name="confirm_new_password" class="form-control" required />
                                    </div>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-key me-1"></i>Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Tab -->
                    <div class="tab-pane fade" id="orders" role="tabpanel">
                        <div class="card profile-card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Your Orders</h5>
                                <?php if (empty($orders)) : ?>
                                    <p class="text-muted">No orders found.</p>
                                <?php else : ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Order ID</th>
                                                    <th>Date</th>
                                                    <th>Items</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($orders as $order) : ?>
                                                    <tr class="appointment-card">
                                                        <td>#<?= htmlspecialchars($order['id']); ?></td>
                                                        <td><?= date('M j, Y', strtotime($order['created_at'])); ?></td>
                                                        <td><?= htmlspecialchars($order['item_count']); ?></td>
                                                        <td>Rs <?= number_format($order['total_price'], 2); ?></td>
                                                        <td>
                                                            <span class="badge 
                                                                <?= $order['status'] === 'delivered' ? 'bg-success' : 
                                                                   ($order['status'] === 'cancelled' ? 'bg-danger' : 'bg-warning') ?>">
                                                                <?= ucfirst($order['status']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="view_order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Appointments Tab -->
                    <div class="tab-pane fade" id="appointments" role="tabpanel">
                        <div class="card profile-card">
                            <div class="card-body">
                                <h5 class="card-title mb-4"><?= $user_type === 'doctor' ? 'Patient Appointments' : 'My Appointments' ?></h5>
                                <?php if (empty($appointments)) : ?>
                                    <p class="text-muted">No appointments found.</p>
                                <?php else : ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date & Time</th>
                                                    <th><?= $user_type === 'doctor' ? 'Patient' : 'Doctor' ?></th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($appointments as $appointment) : ?>
                                                    <tr class="appointment-card">
                                                        <td><?= date('M j, Y g:i A', strtotime($appointment['appointment_date'])); ?></td>
                                                        <td><?= htmlspecialchars($appointment[$user_type === 'doctor' ? 'patient_name' : 'doctor_name']); ?></td>
                                                        <td>
                                                            <span class="badge 
                                                                <?= $appointment['status'] === 'completed' ? 'bg-success' : 
                                                                   ($appointment['status'] === 'cancelled' ? 'bg-danger' : 'bg-warning') ?>">
                                                                <?= ucfirst($appointment['status']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="my_appointments.php?id=<?= $appointment['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                            <?php if ($appointment['status'] === 'pending'): ?>
                                                                <a href="cancel_appointment.php?id=<?= $appointment['id'] ?>" class="btn btn-sm btn-outline-danger">Cancel</a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div> <!-- End of tab-content -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tab functionality
        const profileTabs = document.querySelector('#profileTabs');
        if (profileTabs) {
            const tab = new bootstrap.Tab(profileTabs.querySelector('button[data-bs-target="#profile"]'));
            tab.show();
        }
    </script>
</body>
</html>

<?php 
$conn->close();
include('footer.php'); 
?>
