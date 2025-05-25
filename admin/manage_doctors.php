<?php
session_start();
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

include('server/connection.php'); // Include database connection

// Handle doctor deletion
if (isset($_GET['delete_doctor'])) {
    $doctor_id = $_GET['delete_doctor'];
    $stmt = $conn->prepare("DELETE FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctor_id);
    if ($stmt->execute()) {
        $_SESSION['alert_message'] = "Doctor deleted successfully";
        $_SESSION['alert_type'] = "success";
    } else {
        $_SESSION['alert_message'] = "Failed to delete doctor";
        $_SESSION['alert_type'] = "danger";
    }
    header("Location: manage_doctors.php");
    exit();
}

// Fetch all doctors
$stmt = $conn->prepare("SELECT * FROM doctors");
$stmt->execute();
$result = $stmt->get_result();
$doctors = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors</title>
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
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_doctors.php">Doctors</a>
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
        <?php if (isset($_SESSION['alert_message'])): ?>
            <div class="alert alert-<?= $_SESSION['alert_type'] ?? 'info' ?> alert-dismissible fade show mt-3" role="alert" id="autoDismissAlert">
                <?= htmlspecialchars($_SESSION['alert_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['alert_message'], $_SESSION['alert_type']); ?>
        <?php endif; ?>
        <div class="row">
            <div class="col">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Manage Doctors</h5>
                    </div>
                    <div class="card-body">
                        <a href="add_doctor.php" class="btn btn-primary mb-3">
                            <i class="bi bi-person-plus me-1"></i>Add Doctor
                        </a>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>NMC Number</th>
                                    <th>Specialization</th>
                                    <th>Availability</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($doctors as $doctor) : ?>
                                    <tr>
                                        <td><?= htmlspecialchars($doctor['id']); ?></td>
                                        <td><?= htmlspecialchars($doctor['full_name']); ?></td>
                                        <td><?= htmlspecialchars($doctor['email']); ?></td>
                                        <td><?= htmlspecialchars($doctor['phone']); ?></td>
                                        <td><?= htmlspecialchars($doctor['nmc_number']); ?></td>
                                        <td><?= htmlspecialchars($doctor['specialization']); ?></td>
                                        <td>
                                            <?php
                                            $availability = json_decode($doctor['availability'], true);
                                            if (is_array($availability) && !empty($availability)) {
                                                echo "<ul>";
                                                foreach ($availability as $day => $time) {
                                                    if (is_array($time)) {
                                                        $displayTime = htmlspecialchars(implode(', ', $time));
                                                    } else {
                                                        $displayTime = htmlspecialchars($time);
                                                    }
                                                    echo "<li><strong>" . htmlspecialchars($day) . "</strong>: $displayTime</li>";
                                                }
                                                echo "</ul>";
                                            } else {
                                                echo "No availability data";
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="edit_doctor.php?id=<?= $doctor['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <button
                                                class="btn btn-sm btn-danger delete-doctor-btn"
                                                data-id="<?= $doctor['id']; ?>"
                                                data-name="<?= htmlspecialchars($doctor['full_name']); ?>">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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

    <!-- Delete Doctor Confirmation Modal -->
    <div class="modal fade" id="deleteDoctorModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="min-width:320px;max-width:350px;margin:auto;">
                <div class="modal-body text-center py-4">
                    <h5 class="fw-bold mb-3">Delete Doctor</h5>
                    <div class="mb-4">
                        Are you sure you want to delete Dr. <span id="doctorName" class="fw-bold"></span>?
                    </div>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-outline-danger px-4" data-bs-dismiss="modal">Cancel</button>
                        <a href="#" id="confirmDeleteDoctorBtn" class="btn btn-primary px-4">Delete</a>
                    </div>
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

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alert after 3 seconds
        document.addEventListener('DOMContentLoaded', function () {
            var alert = document.getElementById('autoDismissAlert');
            if (alert) {
                setTimeout(function () {
                    var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                }, 3000);
            }

            // Logout confirmation logic
            const logoutBtn = document.querySelector('a[href="admin_logout.php"].btn-outline-light');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var modal = new bootstrap.Modal(document.getElementById('logoutConfirmModal'));
                    modal.show();
                });
            }

            // Delete doctor modal logic
            document.querySelectorAll('.delete-doctor-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var doctorId = this.getAttribute('data-id');
                    var doctorName = this.getAttribute('data-name');
                    document.getElementById('doctorName').textContent = doctorName;
                    document.getElementById('confirmDeleteDoctorBtn').setAttribute('href', 'manage_doctors.php?delete_doctor=' + doctorId);
                    var modal = new bootstrap.Modal(document.getElementById('deleteDoctorModal'));
                    modal.show();
                });
            });
        });
    </script>
</body>
</html>