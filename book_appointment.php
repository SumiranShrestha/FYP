<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once("server/connection.php");

// Check login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to book an appointment.";
    header("Location: login.php");
    exit();
}

// Validate doctor_id
if (!isset($_GET['doctor_id']) || !is_numeric($_GET['doctor_id'])) {
    $_SESSION['error'] = "Invalid doctor selection.";
    header("Location: doctors.php");
    exit();
}

$doctor_id = intval($_GET['doctor_id']);
$user_id = $_SESSION['user_id'];

/**
 * Get the next date for the given day and time.
 * Example: ("Monday", "9:00 AM") returns a DateTime object for the next Monday at 9:00 AM.
 */
function getNextDayTime($day, $time) {
    // Normalize day name
    $day = ucfirst(strtolower($day));
    // Get current day number (1=Monday, 7=Sunday)
    $currentDayNum = date('N');
    // Get target day number based on day name
    $targetDayNum = date('N', strtotime($day));
    // Calculate days to add; if target is today, decide based on time
    $daysToAdd = ($targetDayNum - $currentDayNum + 7) % 7;
    $now = new DateTime();
    if ($daysToAdd == 0) {
        // If the target day is today, check if the time has already passed
        $targetTime = DateTime::createFromFormat("g:i A", $time);
        if (!$targetTime) {
            $targetTime = DateTime::createFromFormat("H:i", $time);
        }
        if ($targetTime) {
            // Set target time to today
            $targetTime->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));
            if ($targetTime < $now) {
                $daysToAdd = 7; // schedule for next week if time already passed
            }
        }
    }
    $nextDate = new DateTime();
    $nextDate->modify("+{$daysToAdd} days");
    // Parse the provided time
    $timeObj = DateTime::createFromFormat("g:i A", $time);
    if (!$timeObj) {
        $timeObj = DateTime::createFromFormat("H:i", $time);
    }
    if (!$timeObj) {
        return false;
    }
    $nextDate->setTime((int)$timeObj->format("H"), (int)$timeObj->format("i"), 0);
    return $nextDate;
}

// Get doctor details with availability
$stmt = $conn->prepare("SELECT id, full_name, specialization, availability FROM doctors WHERE id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();

if (!$doctor) {
    $_SESSION['error'] = "Doctor not found.";
    header("Location: doctors.php");
    exit();
}

// DEBUG: Show raw availability data
echo "<!-- RAW AVAILABILITY DATA: " . htmlspecialchars($doctor['availability']) . " -->";

// Process availability - your format is like: {"Monday":"9:00,10:00","Wednesday":"14:00,15:00"}
$availability = [];
if (!empty($doctor['availability'])) {
    $cleaned = stripslashes($doctor['availability']);
    $decoded = json_decode($cleaned, true);
    if ($decoded && is_array($decoded)) {
        foreach ($decoded as $day => $times) {
            if (is_array($times)) {
                // Already an array of time slots
                $availability[$day] = array_map('trim', $times);
            } else {
                // String, possibly comma-separated
                $timeSlots = explode(',', $times);
                $availability[$day] = array_map('trim', $timeSlots);
            }
        }
    } else {
        // Fallback sample data
        $availability = ['Monday' => ['9:00 AM', '10:00 AM']];
    }
}

// DEBUG: Show processed availability
echo "<!-- PROCESSED AVAILABILITY: " . print_r($availability, true) . " -->";

// Get existing appointments (assumed stored as DATETIME strings)
$existing_appointments = [];
$appt_stmt = $conn->prepare("SELECT appointment_date FROM appointments WHERE doctor_id = ? AND status != 'cancelled'");
$appt_stmt->bind_param("i", $doctor_id);
$appt_stmt->execute();
$appt_result = $appt_stmt->get_result();
while ($row = $appt_result->fetch_assoc()) {
    $existing_appointments[] = $row['appointment_date'];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected = $_POST['appointment_time']; // expected format: "Monday 9:00 AM"
    if (empty($selected)) {
        $error = "Please select an appointment time.";
    } else {
        // Expecting the format "Day Time" e.g., "Monday 9:00 AM"
        $parts = explode(" ", $selected, 2);
        if (count($parts) < 2) {
            $error = "Invalid appointment time format.";
        } else {
            $day = $parts[0];
            $time = $parts[1];
            $dateTimeObj = getNextDayTime($day, $time);
            if (!$dateTimeObj) {
                $error = "Invalid time format.";
            } else {
                $appointment_date = $dateTimeObj->format("Y-m-d H:i:s");
                if (in_array($appointment_date, $existing_appointments)) {
                    $error = "This time slot has already been booked.";
                } else {
                    $status = 'pending';
                    $stmt = $conn->prepare("INSERT INTO appointments (user_id, doctor_id, appointment_date, status) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiss", $user_id, $doctor_id, $appointment_date, $status);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Appointment booked successfully!";
                        header("Location: my_appointments.php");
                        exit();
                    } else {
                        $error = "Failed to book appointment. Please try again. " . $stmt->error;
                    }
                }
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment with Dr. <?= htmlspecialchars($doctor['full_name']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .time-slot {
            margin: 5px;
            min-width: 100px;
            padding: 8px 12px;
        }
        .time-slot.booked {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .time-slot.selected {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }
        .doctor-header {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 20px;
            margin-bottom: 25px;
        }
        #timeSlots {
            min-height: 120px;
        }
    </style>
</head>
<body>
    <?php include("header.php"); ?>
    <div class="container mt-4">
        <div class="doctor-header">
            <h2>Book Appointment</h2>
            <h4 class="text-primary">Dr. <?= htmlspecialchars($doctor['full_name']) ?></h4>
            <p class="text-muted mb-0">Specialization: <?= htmlspecialchars($doctor['specialization']) ?></p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-body">
                <?php if (empty($availability)): ?>
                    <div class="alert alert-warning">
                        This doctor currently has no available time slots.
                    </div>
                <?php else: ?>
                    <form method="POST" id="appointmentForm">
                        <input type="hidden" name="appointment_time" id="appointment_time">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Select Day</label>
                            <select class="form-select" id="day_select" required>
                                <option value="">-- Select a day --</option>
                                <?php foreach ($availability as $day => $times): ?>
                                    <option value="<?= htmlspecialchars($day) ?>"><?= htmlspecialchars($day) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Available Time Slots</label>
                            <div id="timeSlots" class="d-flex flex-wrap gap-2"></div>
                            <div id="noSlots" class="text-muted mt-2" style="display: none;">
                                No available slots for this day
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary px-4" id="submitBtn" disabled>
                                Confirm Appointment
                            </button>
                            <a href="doctors.php" class="btn btn-outline-secondary px-4">
                                Back to Doctors
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include("footer.php"); ?>
    <script>
        // Availability data from PHP
        const availability = <?= !empty($availability) ? json_encode($availability) : '{"Monday":["9:00 AM","10:00 AM"],"Wednesday":["2:00 PM","3:00 PM"]}' ?>;
        const existingAppointments = <?= json_encode($existing_appointments) ?>;
        
        console.log("DEBUG - Availability Data:", availability);
        console.log("DEBUG - Existing Appointments:", existingAppointments);

        document.getElementById('day_select').addEventListener('change', function() {
            const day = this.value;
            const container = document.getElementById('timeSlots');
            const noSlots = document.getElementById('noSlots');
            container.innerHTML = '';
            document.getElementById('submitBtn').disabled = true;
            noSlots.style.display = 'none';
            
            console.log("DEBUG - Selected Day:", day);
            console.log("DEBUG - Times for Day:", availability[day]);

            if (!day || !availability[day] || availability[day].length === 0) {
                noSlots.style.display = 'block';
                return;
            }
            
            // Create time slot buttons
            availability[day].forEach(time => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-outline-primary time-slot';
                btn.textContent = time;
                
                // Create datetime string in format "Monday 9:00 AM"
                const datetimeString = day + ' ' + time;
                
                // Check if booked (simple check based on string match)
                const isBooked = existingAppointments.some(apt => apt.includes(day) && apt.includes(time));
                
                if (isBooked) {
                    btn.disabled = true;
                    btn.textContent += ' (Booked)';
                    btn.classList.add('booked');
                } else {
                    btn.addEventListener('click', function() {
                        document.querySelectorAll('.time-slot').forEach(el => {
                            el.classList.remove('selected', 'btn-primary');
                            el.classList.add('btn-outline-primary');
                        });
                        
                        this.classList.add('selected', 'btn-primary');
                        this.classList.remove('btn-outline-primary');
                        document.getElementById('appointment_time').value = datetimeString;
                        document.getElementById('submitBtn').disabled = false;
                    });
                }
                
                container.appendChild(btn);
            });
        });

        // Trigger change event if day is preselected
        const daySelect = document.getElementById('day_select');
        if (daySelect.value) {
            daySelect.dispatchEvent(new Event('change'));
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>