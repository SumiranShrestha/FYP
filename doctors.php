<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once("server/connection.php");

$query = "SELECT id, full_name, specialization, phone, availability FROM doctors";
$result = $conn->query($query);

if (!$result) {
    die("<div class='alert alert-danger'>Database error : " . $conn->error . "</div>");
}

$doctors = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctors - Appointment Booking</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .doctor-card {
            margin-bottom: 30px;
            height: 100%;
            transition: all 0.3s ease;
        }
        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .availability-badge {
            font-size: 0.8rem;
        }
        .availability-times {
            font-size: 0.9rem;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php include("header.php"); ?>

    <div class="container mt-4">
        <h2 class="text-center mb-4">Book an Appointment with Our Doctors</h2>
        
        <?php if (empty($doctors)): ?>
            <div class="alert alert-warning text-center">
                No doctors available at the moment.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($doctors as $doctor): 
                    // Initialize availability data
                    $availability_display = "Not available";
                    $availability_details = [];
                    $is_available = false;
                    
                    // Process availability data
                    if (!empty($doctor['availability'])) {
                        // Clean and decode JSON
                        $cleaned = stripslashes(trim($doctor['availability'], '"'));
                        $availability_data = json_decode($cleaned, true);
                        
                        if (json_last_error() === JSON_ERROR_NONE && is_array($availability_data)) {
                            foreach ($availability_data as $day => $times) {
                                if (is_array($times)) {
                                    $time_slots = $times;
                                } else {
                                    // Handle comma-separated times
                                    $time_slots = explode(',', $times);
                                    $time_slots = array_map('trim', $time_slots);
                                }
                                
                                if (!empty($time_slots)) {
                                    $is_available = true;
                                    $availability_details[$day] = $time_slots;
                                }
                            }
                        }
                    }
                    
                    // Prepare display text
                    if ($is_available) {
                        $availability_display = "Available";
                        // Get first available day for the badge
                        $first_day = array_key_first($availability_details);
                        $first_times = implode(", ", $availability_details[$first_day]);
                    }
                ?>
                    <div class="col-md-4 mb-4">
                        <div class="card doctor-card h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">Dr. <?= htmlspecialchars($doctor['full_name']) ?></h5>
                                <p class="card-text">
                                    <strong>Specialization:</strong> 
                                    <?= htmlspecialchars($doctor['specialization'] ?? 'General') ?>
                                </p>
                                <p class="card-text">
                                    <strong>Contact:</strong> 
                                    <?= htmlspecialchars($doctor['phone']) ?>
                                </p>
                                
                                <div class="mt-auto">
                                    <span class="badge bg-<?= $is_available ? 'success' : 'danger' ?> availability-badge">
                                        <?= $availability_display ?>
                                    </span>
                                    <?php if ($is_available): ?>
                                        <div class="availability-times mt-2">
                                            <?php foreach ($availability_details as $day => $times): ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($day) ?>:</strong>
                                                    <?= htmlspecialchars(implode(", ", $times)) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mt-3">
                                        <?php if ($is_available): ?>
                                            <?php if (isset($_SESSION['user_id']) && (empty($_SESSION['user_type']) || $_SESSION['user_type'] === 'user')): ?>
                                                <a href="book_appointment.php?doctor_id=<?= $doctor['id'] ?>" 
                                                   class="btn btn-primary w-100 bookAppointmentBtn">
                                                   Book Appointment
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-primary w-100 bookAppointmentBtn" type="button">
                                                    Book Appointment
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button class="btn btn-secondary w-100" disabled>
                                                Currently Unavailable
                                            </button>
                                        <?php endif; ?>
                                    </div>
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
    <style>
    #loginToast {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 9999;
        min-width: 280px;
    }
    #loginToast.toast {
        background-color: #e53935 !important;
        color: #fff !important;

    }
    </style>
    <!-- Toast for login required -->
    <div class="toast align-items-center border-0" id="loginToast" role="alert" aria-live="assertive" aria-atomic="true" style="display:none;">
      <div class="d-flex">
        <div class="toast-body">
          You must login to book appointment.
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.bookAppointmentBtn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                <?php if (!isset($_SESSION['user_id']) || (!empty($_SESSION['user_type']) && $_SESSION['user_type'] !== 'user')): ?>
                    e.preventDefault();
                    // Show toast at bottom right
                    var toastEl = document.getElementById('loginToast');
                    toastEl.style.display = 'block';
                    var toast = new bootstrap.Toast(toastEl, { delay: 1800 });
                    toast.show();
                    // After toast, open login modal
                    setTimeout(function() {
                        toast.hide();
                        // If you have a login modal with id 'loginModal'
                        var loginModal = document.getElementById('loginModal');
                        if (loginModal) {
                            var modal = new bootstrap.Modal(loginModal);
                            modal.show();
                        } else {
                            window.location.href = "login.php";
                        }
                    }, 1800);
                <?php endif; ?>
                // If logged in as user, allow booking (do nothing)
            });
        });
    });
    </script>
</body>
</html>