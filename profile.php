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
            <div class="alert alert-<?= $alert_type; ?> alert-dismissible fade show position-fixed end-0 bottom-0 m-4" style="z-index: 1055; min-width:300px; max-width:350px;" role="alert" id="profileAlert">
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

                        <?php if ($user_type === 'doctor' && !empty($doctor)): ?>
                            <h5 class="card-title"><?= htmlspecialchars($doctor['full_name'] ?? 'Doctor') ?></h5>
                            <p class="text-muted mb-1"><i class="bi bi-envelope"></i> <?= htmlspecialchars($doctor['email'] ?? 'No email') ?></p>
                            <p class="text-muted mb-1"><i class="bi bi-telephone"></i> <?= htmlspecialchars($doctor['phone'] ?? 'No phone') ?></p>
                            <p class="text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars(($doctor['address'] ?? 'No address') . ', ' . ($doctor['city'] ?? '')) ?></p>
                            <hr>
                            <h6 class="card-subtitle mb-2 text-muted">Professional Information</h6>
                            <p><strong>NMC Number:</strong> <?= htmlspecialchars($doctor['nmc_number'] ?? 'Not available') ?></p>
                            <p><strong>Specialization:</strong> <?= htmlspecialchars($doctor['specialization'] ?? 'Not specified') ?></p>
                        <?php else: ?>
                            <h5 class="card-title"><?= htmlspecialchars($user['user_name'] ?? 'User') ?></h5>
                            <p class="text-muted mb-1"><i class="bi bi-envelope"></i> <?= htmlspecialchars($user['user_email'] ?? 'No email') ?></p>
                            <p class="text-muted mb-1"><i class="bi bi-telephone"></i> <?= htmlspecialchars($user['phone'] ?? 'No phone') ?></p>
                            <p class="text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars(($user['address'] ?? 'No address') . ', ' . ($user['city'] ?? '')) ?></p>
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
                                <?php if ($user_type === 'doctor' && !empty($doctor)): ?>
                                    <h5 class="mb-3">Doctor Profile Details</h5>
                                    <div class="mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($doctor['full_name'] ?? ''); ?>" readonly />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" value="<?= htmlspecialchars($doctor['email'] ?? ''); ?>" readonly />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($doctor['phone'] ?? ''); ?>" readonly />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($doctor['address'] ?? ''); ?>" readonly />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">City</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($doctor['city'] ?? ''); ?>" readonly />
                                    </div>
                                <?php else: ?>
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
                                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? ''); ?>" pattern="\d{10}" maxlength="10" title="Phone number must be exactly 10 digits" required id="phoneInput" />
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
                                <?php endif; ?>
                                <?php if ($user_type === 'doctor' && !empty($doctor)): ?>
                                    <hr class="my-4">
                                    <h5 class="mb-3">Update Availability</h5>
                                    <?php
                                    // Parse availability JSON if present
                                    $availability = [];
                                    if (!empty($doctor['availability'])) {
                                        $availability = json_decode($doctor['availability'], true);
                                    }
                                    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                    ?>
                                    <form method="POST" action="update_availability.php" id="availabilityForm">
                                        <div class="mb-3">
                                            <label class="form-label">Day</label>
                                            <select name="day" class="form-select" required>
                                                <option value="">Select Day</option>
                                                <?php foreach ($days as $day): ?>
                                                    <option value="<?= $day ?>"><?= $day ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Available Time(s) <span class="text-muted">(e.g. 9:00 AM, 2:00 PM)</span></label>
                                            <input type="text" name="times" class="form-control" placeholder="e.g. 9:00 AM, 2:00 PM" required id="timeSlotsInput">
                                            <div class="form-text text-danger" id="timeFormatError" style="display:none;">Please enter time(s) in the format: 9:00 AM, 2:00 PM</div>
                                        </div>
                                        <button type="submit" class="btn btn-success mb-2">Add/Update Availability</button>
                                    </form>
                                    <script>
                                        // Auto-format time slots to "AM"/"PM" (capitalize) before validation and submission
                                        document.getElementById('availabilityForm').addEventListener('submit', function(e) {
                                            var inputElem = document.getElementById('timeSlotsInput');
                                            var input = inputElem.value.trim();
                                            if (!input) return; // required attribute will catch empty

                                            // Replace lowercase am/pm with uppercase
                                            input = input.replace(/\b(am|pm)\b/gi, function(match) {
                                                return match.toUpperCase();
                                            });
                                            inputElem.value = input;

                                            // Regex for "h:mm AM/PM" or "hh:mm AM/PM", comma separated
                                            var regex = /^(\d{1,2}:\d{2}\s?(AM|PM))(,\s*\d{1,2}:\d{2}\s?(AM|PM))*$/i;
                                            if (!regex.test(input)) {
                                                document.getElementById('timeFormatError').style.display = 'block';
                                                e.preventDefault();
                                            } else {
                                                document.getElementById('timeFormatError').style.display = 'none';
                                            }
                                        });
                                    </script>
                                    <?php if (!empty($availability)): ?>
                                        <div class="mt-3">
                                            <h6>Current Availability:</h6>
                                            <ul class="list-group">
                                                <?php foreach ($availability as $day => $times): ?>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span>
                                                            <strong><?= htmlspecialchars($day) ?>:</strong>
                                                            <?= is_array($times) ? htmlspecialchars(implode(', ', $times)) : htmlspecialchars($times) ?>
                                                        </span>
                                                        <button 
                                                            type="button"
                                                            class="btn btn-sm btn-danger delete-availability-btn"
                                                            data-day="<?= htmlspecialchars($day) ?>"
                                                        >
                                                            Delete
                                                        </button>
                                                        <form method="POST" action="update_availability.php" class="d-none delete-availability-form">
                                                            <input type="hidden" name="delete_day" value="<?= htmlspecialchars($day) ?>">
                                                        </form>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

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
                                                            <?php
                                                                $status = $order['status'];
                                                                if ($status === null) {
                                                                    // Treat NULL as "Pending"
                                                                    $status = 'Pending';
                                                                }
                                                                $badgeClass = 'bg-secondary';
                                                                switch ($status) {
                                                                    case 'Delivered':
                                                                        $badgeClass = 'bg-success';
                                                                        break;
                                                                    case 'Cancelled':
                                                                        $badgeClass = 'bg-danger';
                                                                        break;
                                                                    case 'Pending':
                                                                        $badgeClass = 'bg-warning text-dark';
                                                                        break;
                                                                    case 'Processing':
                                                                        $badgeClass = 'bg-info text-dark';
                                                                        break;
                                                                    case 'Shipped':
                                                                        $badgeClass = 'bg-primary';
                                                                        break;
                                                                }
                                                                echo '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($status) . '</span>';
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <a href="view_order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                            <?php if ($status === 'Pending'): ?>
                                                                <button class="btn btn-sm btn-outline-danger cancel-order-btn" data-order-id="<?= $order['id'] ?>">
                                                                    Cancel
                                                                </button>
                                                            <?php elseif ($status === 'Cancelled'): ?>
                                                                <button class="btn btn-sm btn-outline-danger" disabled>
                                                                    Cancelled
                                                                </button>
                                                            <?php elseif ($status === 'Delivered'): ?>
                                                                <button class="btn btn-sm btn-outline-secondary" disabled>
                                                                    Delivered
                                                                </button>
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
                                                                <?= $appointment['status'] === 'completed' ? 'bg-success' : ($appointment['status'] === 'cancelled' ? 'bg-danger' : ($appointment['status'] === 'pending' ? 'bg-warning' : ($appointment['status'] === 'confirmed' ? 'bg-success' : 'bg-secondary'))) ?>">
                                                                <?= ucfirst($appointment['status']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $detailPage = $user_type === 'doctor'
                                                                ? 'doctor_appointment.php'
                                                                : 'my_appointments.php';
                                                            ?>
                                                            <a href="<?= $detailPage ?>?id=<?= $appointment['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                                View
                                                            </a>
                                                            <?php if ($appointment['status'] === 'pending' || $appointment['status'] === 'confirmed'): ?>
                                                                <button class="btn btn-sm btn-outline-danger cancel-btn"
                                                                    data-appointment-id="<?= $appointment['id'] ?>">
                                                                    Cancel
                                                                </button>
                                                            <?php elseif ($appointment['status'] == 'cancelled'): ?>
                                                                <button class="btn btn-sm btn-outline-danger" disabled>
                                                                    Cancelled
                                                                </button>
                                                            <?php elseif ($appointment['status'] == 'completed'): ?>
                                                                <button class="btn btn-sm btn-outline-secondary" disabled>
                                                                    Completed
                                                                </button>
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

    <!-- Delete Availability Confirmation Modal -->
    <div class="modal fade" id="deleteAvailabilityModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="min-width:320px;max-width:350px;margin:auto;">
                <div class="modal-body text-center py-4">
                    <h5 class="fw-bold mb-3">Delete Availability</h5>
                    <div class="mb-4">
                        Are you sure you want to delete availability for <span id="availabilityDay" class="fw-bold"></span>?
                    </div>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-outline-danger px-4" data-bs-dismiss="modal">Cancel</button>
                        <button id="confirmDeleteAvailabilityBtn" class="btn btn-primary px-4">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1100';
        document.body.appendChild(toastContainer);

        // Handle cancel button clicks
        document.querySelectorAll('.cancel-btn').forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                let btn = this;
                let appointmentId = btn.getAttribute('data-appointment-id');
                //display appointment id
                console.log('Appointment ID:', appointmentId);
                // Validate appointment ID
                if (!appointmentId || isNaN(appointmentId)) {
                    showToast('Invalid appointment selected. Please refresh the page and try again.', 'danger');
                    return;
                }
                let row = btn.closest('tr');
                let originalButtonHTML = btn.innerHTML;

                if (confirm("Are you sure you want to cancel this appointment?")) {
                    // Change button appearance
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cancelling...';
                    btn.disabled = true;

                    // Make AJAX request
                    fetch('server/cancel_appointment.php?id=' + encodeURIComponent(appointmentId), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Show success message
                                showToast(data.message, 'success');

                                // Update the row appearance
                                row.querySelector('.badge').className = 'badge bg-danger';
                                row.querySelector('.badge').textContent = 'Cancelled';
                                btn.remove();

                                // Add cancelled button
                                const cancelledBtn = document.createElement('button');
                                cancelledBtn.className = 'btn btn-sm btn-outline-danger';
                                cancelledBtn.disabled = true;
                                cancelledBtn.innerHTML = '<i class="bi bi-slash-circle"></i> Cancelled';
                                row.querySelector('td:last-child').appendChild(cancelledBtn);
                            } else {
                                // Reset button state
                                btn.innerHTML = originalButtonHTML;
                                btn.disabled = false;
                                showToast(data.message, 'danger');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            btn.innerHTML = originalButtonHTML;
                            btn.disabled = false;
                            showToast('An error occurred while cancelling the appointment', 'danger');
                        });
                }
            });
        });

        // Delete availability modal logic (like manage_products.php)
        document.querySelectorAll('.delete-availability-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var day = this.getAttribute('data-day');
                document.getElementById('availabilityDay').textContent = day;
                // Store the button reference for later
                document.getElementById('confirmDeleteAvailabilityBtn').setAttribute('data-day', day);
                var modal = new bootstrap.Modal(document.getElementById('deleteAvailabilityModal'));
                modal.show();
            });
        });

        document.getElementById('confirmDeleteAvailabilityBtn').addEventListener('click', function() {
            var day = this.getAttribute('data-day');
            // Find the corresponding hidden form and submit it
            document.querySelectorAll('.delete-availability-form').forEach(function(form) {
                if (form.querySelector('input[name="delete_day"]').value === day) {
                    form.submit();
                }
            });
        });

        function showToast(message, type) {
            const toastId = 'toast-' + Date.now();
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.id = toastId;

            toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

            toastContainer.appendChild(toast);

            // Show the toast
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();

            // Auto-hide after 5 seconds
            setTimeout(() => {
                bsToast.hide();
            }, 5000);
        }
        // Persist active tab using localStorage
        document.addEventListener("DOMContentLoaded", function() {
            const profileTabs = document.querySelector('#profileTabs');
            const tabButtons = profileTabs ? profileTabs.querySelectorAll('button[data-bs-toggle="pill"]') : [];
            const tabContent = document.querySelector('#profileTabsContent');
            // Restore last active tab
            const lastTab = localStorage.getItem('profileActiveTab');
            if (lastTab && tabButtons.length) {
                const tabBtn = profileTabs.querySelector(`button[data-bs-target="${lastTab}"]`);
                if (tabBtn) {
                    const bsTab = new bootstrap.Tab(tabBtn);
                    bsTab.show();
                }
            }
            // Save tab on change
            tabButtons.forEach(btn => {
                btn.addEventListener('shown.bs.tab', function(e) {
                    localStorage.setItem('profileActiveTab', this.getAttribute('data-bs-target'));
                });
            });
        });

        // Auto-hide alert after 3 seconds
        document.addEventListener("DOMContentLoaded", function() {
            var alertBox = document.getElementById('profileAlert');
            if (alertBox) {
                setTimeout(function() {
                    var alert = bootstrap.Alert.getOrCreateInstance(alertBox);
                    alert.close();
                }, 3000);
            }
        });

        // Phone number validation for profile update
        document.addEventListener("DOMContentLoaded", function() {
            var phoneInput = document.getElementById('phoneInput');
            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    // Remove non-digit characters
                    this.value = this.value.replace(/\D/g, '');
                });
                phoneInput.addEventListener('invalid', function() {
                    this.setCustomValidity('Phone number must be exactly 10 digits.');
                });
                phoneInput.addEventListener('input', function() {
                    this.setCustomValidity('');
                });
            }
        });

        // Handle order cancel button clicks
        document.querySelectorAll('.cancel-order-btn').forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                let btn = this;
                let orderId = btn.getAttribute('data-order-id');
                if (!orderId || isNaN(orderId)) {
                    showToast('Invalid order selected. Please refresh the page and try again.', 'danger');
                    return;
                }
                let row = btn.closest('tr');
                let originalButtonHTML = btn.innerHTML;

                if (confirm("Are you sure you want to cancel this order?")) {
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cancelling...';
                    btn.disabled = true;

                    fetch('server/cancel_order.php?id=' + encodeURIComponent(orderId), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message, 'success');
                            // Update the row appearance
                            row.querySelector('.badge').className = 'badge bg-danger';
                            row.querySelector('.badge').textContent = 'Cancelled';
                            btn.remove();
                            // Add cancelled button
                            const cancelledBtn = document.createElement('button');
                            cancelledBtn.className = 'btn btn-sm btn-outline-danger';
                            cancelledBtn.disabled = true;
                            cancelledBtn.innerHTML = 'Cancelled';
                            row.querySelector('td:last-child').appendChild(cancelledBtn);
                        } else {
                            btn.innerHTML = originalButtonHTML;
                            btn.disabled = false;
                            showToast(data.message, 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        btn.innerHTML = originalButtonHTML;
                        btn.disabled = false;
                        showToast('An error occurred while cancelling the order', 'danger');
                    });
                }
            });
        });
    </script>
</body>

</html>

<?php
$conn->close();
include('footer.php');
?>