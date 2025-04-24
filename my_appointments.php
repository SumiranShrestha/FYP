<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once("server/connection.php");

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to view your appointments.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "SELECT a.id, a.appointment_date, a.status, 
                 d.full_name AS doctor_name, d.specialization
          FROM appointments a
          JOIN doctors d ON a.doctor_id = d.id
          WHERE a.user_id = ?
          ORDER BY a.appointment_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .appointment-card {
            margin-bottom: 20px;
            border-left: 4px solid #0d6efd;
            transition: all 0.3s ease;
        }
        .status-pending {
            color: #ffc107;
        }
        .status-confirmed {
            color: #28a745;
        }
        .status-cancelled {
            color: #dc3545;
        }
        .status-completed {
            color: #17a2b8;
        }
        .removing {
            opacity: 0;
            transform: translateX(-100%);
        }
    </style>
</head>
<body>
    <?php include("header.php"); ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>My Appointments</h2>
            <a href="doctors.php" class="btn btn-primary">Book New Appointment</a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (empty($appointments)): ?>
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">No Appointments Found</h5>
                    <p class="card-text">You haven't booked any appointments yet.</p>
                    <a href="doctors.php" class="btn btn-primary">Book Your First Appointment</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row" id="appointmentsContainer">
                <?php foreach ($appointments as $appointment): 
                    $appointment_date = new DateTime($appointment['appointment_date']);
                    $formatted_date = $appointment_date->format('l, F j, Y');
                    $formatted_time = $appointment_date->format('g:i A');
                ?>
                    <div class="col-md-6 appointment-item" id="appointment-<?= $appointment['id'] ?>">
                        <div class="card appointment-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="card-title">Dr. <?= htmlspecialchars($appointment['doctor_name']) ?></h5>
                                        <p class="card-text text-muted">
                                            <?= htmlspecialchars($appointment['specialization']) ?>
                                        </p>
                                    </div>
                                    <span class="badge status-<?= $appointment['status'] ?>">
                                        <?= ucfirst($appointment['status']) ?>
                                    </span>
                                </div>
                                
                                <hr>
                                
                                <div class="row">
                                    <div class="col-6">
                                        <p class="mb-1"><strong>Date:</strong></p>
                                        <p><?= $formatted_date ?></p>
                                    </div>
                                    <div class="col-6">
                                        <p class="mb-1"><strong>Time:</strong></p>
                                        <p><?= $formatted_time ?></p>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2 mt-2">
                                    <?php if ($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed'): ?>
                                        <button class="btn btn-outline-danger btn-sm cancel-btn" 
                                                data-appointment-id="<?= $appointment['id'] ?>">
                                            Cancel
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($appointment['status'] == 'completed'): ?>
                                        <button class="btn btn-outline-secondary btn-sm" disabled>
                                            Completed
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include("footer.php"); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners to all cancel buttons
            document.querySelectorAll('.cancel-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const appointmentId = this.getAttribute('data-appointment-id');
                    const appointmentElement = document.getElementById(`appointment-${appointmentId}`);
                    
                    if (confirm("Are you sure you want to cancel this appointment?")) {
                        // Add removing class for animation
                        appointmentElement.classList.add('removing');
                        
                        // After animation completes, make the AJAX call
                        setTimeout(() => {
                            fetch('cancel_appointment.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'appointment_id=' + appointmentId
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Remove the appointment card from DOM
                                    appointmentElement.remove();
                                    
                                    // Check if no appointments left
                                    if (document.querySelectorAll('.appointment-item').length === 0) {
                                        showNoAppointmentsMessage();
                                    }
                                } else {
                                    // Remove the animation class if cancellation failed
                                    appointmentElement.classList.remove('removing');
                                    alert("Error: " + (data.message || "Failed to cancel appointment"));
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                appointmentElement.classList.remove('removing');
                                alert("An error occurred while cancelling the appointment");
                            });
                        }, 300); // Match this with CSS transition duration
                    }
                });
            });
        });

        function showNoAppointmentsMessage() {
            const container = document.getElementById('appointmentsContainer');
            container.innerHTML = `
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">No Appointments Found</h5>
                            <p class="card-text">You haven't booked any appointments yet.</p>
                            <a href="doctors.php" class="btn btn-primary">Book Your First Appointment</a>
                        </div>
                    </div>
                </div>
            `;
        }
    </script>
</body>
</html>