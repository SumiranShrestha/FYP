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

// Fetch all appointments without sorting in PHP (we'll sort with JS)
$query = "SELECT a.id, a.appointment_date, a.status, 
                 d.full_name AS doctor_name, d.specialization
          FROM appointments a
          JOIN doctors d ON a.doctor_id = d.id
          WHERE a.user_id = ?";

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
            transition: all 0.3s ease;
        }

        .btn-cancelling {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }

        /* Enhanced Sort UI Styles */
        .sort-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .sort-btn {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        .sort-btn:hover {
            background-color: #e9ecef;
        }

        .sort-btn.active {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }

        .sort-btn i {
            margin-left: 5px;
        }

        .appointment-container {
            transition: opacity 0.3s ease;
        }

        .appointment-container.sorting {
            opacity: 0.6;
        }
    </style>
</head>

<body>
    <?php include("header.php"); ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>My Appointments</h2>
            <a href="doctors.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Book New Appointment
            </a>
        </div>

        <!-- Enhanced Sorting UI -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Sort Appointments</h5>
                <div class="sort-buttons">
                    <button class="sort-btn active" data-sort="date_desc">
                        Newest First <i class="bi bi-sort-down"></i>
                    </button>
                    <button class="sort-btn" data-sort="date_asc">
                        Oldest First <i class="bi bi-sort-up"></i>
                    </button>
                    <button class="sort-btn" data-sort="status">
                        By Status <i class="bi bi-filter"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast notifications container -->
        <div class="toast-container"></div>

        <?php if (empty($appointments)): ?>
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">No Appointments Found</h5>
                    <p class="card-text">You haven't booked any appointments yet.</p>
                    <a href="doctors.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Book Your First Appointment
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="row appointment-container" id="appointmentsContainer">
                <?php foreach ($appointments as $appointment):
                    $appointment_date = new DateTime($appointment['appointment_date']);
                    $formatted_date = $appointment_date->format('l, F j, Y');
                    $formatted_time = $appointment_date->format('g:i A');
                ?>
                    <div class="col-md-6 appointment-item" id="appointment-<?= $appointment['id'] ?>"
                        data-date="<?= $appointment['appointment_date'] ?>"
                        data-status="<?= $appointment['status'] ?>">
                        <div class="card appointment-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="card-title">Dr. <?= htmlspecialchars($appointment['doctor_name']) ?></h5>
                                        <p class="card-text text-muted">
                                            <i class="bi bi-bandaid"></i> <?= htmlspecialchars($appointment['specialization']) ?>
                                        </p>
                                    </div>
                                </div>

                                <hr>

                                <div class="row">
                                    <div class="col-6">
                                        <p class="mb-1"><strong><i class="bi bi-calendar"></i> Date:</strong></p>
                                        <p><?= $formatted_date ?></p>
                                    </div>
                                    <div class="col-6">
                                        <p class="mb-1"><strong><i class="bi bi-clock"></i> Time:</strong></p>
                                        <p><?= $formatted_time ?></p>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 mt-2">
                                    <?php if ($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed'): ?>
                                        <button class="btn btn-outline-danger btn-sm cancel-btn"
                                            data-appointment-id="<?= $appointment['id'] ?>">
                                            Cancel
                                        </button>
                                    <?php elseif ($appointment['status'] == 'cancelled'): ?>
                                        <button class="btn btn-outline-danger btn-sm" disabled>
                                            Cancelled
                                        </button>
                                    <?php elseif ($appointment['status'] == 'completed'): ?>
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
            // Function to show toast notification
            function showToast(message, type) {
                const toastContainer = document.querySelector('.toast-container');
                const toastId = 'toast-' + Date.now();

                const toast = document.createElement('div');
                toast.className = `toast align-items-center text-white bg-${type} border-0 show`;
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

                // Auto-hide after 5 seconds
                setTimeout(() => {
                    const bsToast = new bootstrap.Toast(toast);
                    bsToast.hide();

                    // Remove from DOM after animation
                    toast.addEventListener('hidden.bs.toast', () => {
                        toast.remove();
                    });
                }, 5000);
            }

            // Sorting functionality
            const sortButtons = document.querySelectorAll('.sort-btn');
            const appointmentsContainer = document.getElementById('appointmentsContainer');

            // Set active sort button and sort appointments
            sortButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Update active button
                    sortButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');

                    // Add sorting class for transition effect
                    appointmentsContainer.classList.add('sorting');

                    // Get sort method
                    const sortMethod = this.getAttribute('data-sort');

                    // Sort after a small delay for transition effect
                    setTimeout(() => {
                        sortAppointments(sortMethod);
                        appointmentsContainer.classList.remove('sorting');
                    }, 300);
                });
            });

            // Function to sort appointments
            function sortAppointments(sortMethod) {
                const appointments = Array.from(document.querySelectorAll('.appointment-item'));

                appointments.sort((a, b) => {
                    if (sortMethod === 'date_asc') {
                        return new Date(a.dataset.date) - new Date(b.dataset.date);
                    } else if (sortMethod === 'date_desc') {
                        return new Date(b.dataset.date) - new Date(a.dataset.date);
                    } else if (sortMethod === 'status') {
                        // Define status order: confirmed, pending, completed, cancelled
                        const statusOrder = {
                            'confirmed': 1,
                            'pending': 2,
                            'completed': 3,
                            'cancelled': 4
                        };

                        const statusA = statusOrder[a.dataset.status] || 5;
                        const statusB = statusOrder[b.dataset.status] || 5;

                        if (statusA === statusB) {
                            // If same status, sort by date (newest first)
                            return new Date(b.dataset.date) - new Date(a.dataset.date);
                        }

                        return statusA - statusB;
                    }
                });

                // Reorder in DOM
                appointments.forEach(appointment => {
                    appointmentsContainer.appendChild(appointment);
                });
            }

            // Add event listeners to all cancel buttons
            document.querySelectorAll('.cancel-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const appointmentId = this.getAttribute('data-appointment-id');
                    const appointmentElement = document.getElementById(`appointment-${appointmentId}`);
                    const originalButtonHTML = this.innerHTML;

                    if (confirm("Are you sure you want to cancel this appointment?")) {
                        // Change button appearance
                        this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cancelling...';
                        this.disabled = true;

                        // Add removing class for animation
                        appointmentElement.classList.add('removing');

                        // Make the AJAX call
                        fetch('server/cancel_appointment.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: `appointment_id=${appointmentId}`
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    // Remove the appointment card from DOM after animation
                                    setTimeout(() => {
                                        appointmentElement.remove();

                                        // Check if no appointments left
                                        if (document.querySelectorAll('.appointment-item').length === 0) {
                                            showNoAppointmentsMessage();
                                        }

                                        // Show success toast
                                        showToast(data.message || 'Appointment cancelled successfully', 'success');
                                    }, 300);
                                } else {
                                    // Remove the animation class if cancellation failed
                                    appointmentElement.classList.remove('removing');
                                    this.innerHTML = originalButtonHTML;
                                    this.disabled = false;
                                    showToast(data.message || "Failed to cancel appointment", 'danger');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                appointmentElement.classList.remove('removing');
                                this.innerHTML = originalButtonHTML;
                                this.disabled = false;
                                showToast("An error occurred while cancelling the appointment", 'danger');
                            });
                    }
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
                                <a href="doctors.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Book Your First Appointment
                                </a>
                            </div>
                        </div>
                    </div>
                `;
            }
        });
    </script>
</body>

</html>