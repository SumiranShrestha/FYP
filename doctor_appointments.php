<?php
session_start();

// Check if user is logged in and is a doctor
if (!isset($_SESSION["user_id"]) || (($_SESSION["user_type"] ?? '') !== 'doctor')) {
    header("Location: ../index.php");
    exit();
}

include("server/connection.php");

$doctor_id = $_SESSION["user_id"];

// Fetch all appointments
$stmt = $conn->prepare("
    SELECT a.*, u.user_name, u.user_email, u.phone 
    FROM appointments a
    JOIN users u ON a.user_id = u.id
    WHERE a.doctor_id = ?
    ORDER BY a.appointment_date DESC
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$appointments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include("header.php"); ?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title">Manage Appointments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($appointment = $appointments->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($appointment['user_name']); ?></td>
                                    <td><?= date('M j, Y h:i A', strtotime($appointment['appointment_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?=
                                            $appointment['status'] === 'pending' ? 'warning' : 
                                            ($appointment['status'] === 'confirmed' ? 'success' : 
                                            ($appointment['status'] === 'completed' ? 'info' : 'secondary'))
                                        ?>">
                                            <?= ucfirst($appointment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($appointment['phone']); ?><br>
                                        <?= htmlspecialchars($appointment['user_email']); ?>
                                    </td>
                                    <td>
                                        <a href="doctor_appointment.php?id=<?= $appointment['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
