<?php
session_start();

// Check if user is logged in and is a doctor
if (!isset($_SESSION["user_id"]) || (($_SESSION["user_type"] ?? '') !== 'doctor')) {
    header("Location: index.php");
    exit();
}

// Include the connection file
include("server/connection.php");

// Fetch doctor details
$doctor_id = $_SESSION["user_id"];
$stmt = $conn->prepare("SELECT * FROM doctors WHERE id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();

// Fetch all appointments for this doctor
$appointments_stmt = $conn->prepare("
    SELECT * FROM appointments 
    WHERE doctor_id = ?
    ORDER BY appointment_date ASC
");

if (!$appointments_stmt) {
    die("Prepare failed: " . $conn->error);
}

$appointments_stmt->bind_param("i", $doctor_id);
$appointments_stmt->execute();
$appointments = $appointments_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-pending { background-color: #fff3cd; }
        .status-confirmed { background-color: #d1e7dd; }
        .status-completed { background-color: #cfe2ff; }
        .status-cancelled { background-color: #f8d7da; }
    </style>
</head>
<body>
    <?php include("header.php"); ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Doctor Profile -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title">Doctor Profile</h5>
                    </div>
                    <div class="card-body">
                        <h5><?= htmlspecialchars($doctor['full_name'] ?? 'N/A'); ?></h5>
                        <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($doctor['email'] ?? 'N/A'); ?></p>
                        <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($doctor['phone'] ?? 'N/A'); ?></p>
                        <p class="mb-1"><strong>Specialization:</strong> <?= htmlspecialchars($doctor['specialization'] ?? 'N/A'); ?></p>
                        <p class="mb-1"><strong>NMC Number:</strong> <?= htmlspecialchars($doctor['nmc_number'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- All Appointments -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title">All Appointments</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($appointments->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Appointment ID</th>
                                            <th>Date & Time</th>
                                            <th>Status</th>
                                            <th>User ID</th>
                                            <th>Prescription</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($appointment = $appointments->fetch_assoc()): ?>
                                            <tr class="status-<?= $appointment['status'] ?? 'pending' ?>">
                                                <td><?= htmlspecialchars($appointment['id'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php 
                                                    if (!empty($appointment['appointment_date']) && $appointment['appointment_date'] != '0000-00-00 00:00:00') {
                                                        echo date('M j, Y h:i A', strtotime($appointment['appointment_date']));
                                                    } else {
                                                        echo 'Date not set';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        switch($appointment['status'] ?? 'pending') {
                                                            case 'pending': echo 'warning'; break;
                                                            case 'confirmed': echo 'success'; break;
                                                            case 'completed': echo 'primary'; break;
                                                            case 'cancelled': echo 'danger'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?= ucfirst($appointment['status'] ?? 'pending'); ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($appointment['user_id'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php if (!empty($appointment['prescription'])): ?>
                                                        <button class="btn btn-sm btn-info view-prescription" 
                                                                data-prescription="<?= htmlspecialchars($appointment['prescription']) ?>">
                                                            View
                                                        </button>
                                                    <?php else: ?>
                                                        None
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="doctor_appointment.php?id=<?= $appointment['id']; ?>" 
                                                       class="btn btn-sm btn-primary">Manage</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                No appointments found for this doctor.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Prescription Modal -->
    <div class="modal fade" id="prescriptionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Prescription Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="prescriptionContent">
                    <!-- Prescription content will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show prescription in modal
        document.querySelectorAll('.view-prescription').forEach(button => {
            button.addEventListener('click', function() {
                const prescription = this.getAttribute('data-prescription');
                document.getElementById('prescriptionContent').innerHTML = 
                    '<div class="prescription-content">' + 
                    prescription.replace(/\n/g, '<br>') + 
                    '</div>';
                const modal = new bootstrap.Modal(document.getElementById('prescriptionModal'));
                modal.show();
            });
        });
    </script>
</body>
</html> 