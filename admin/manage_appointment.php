<?php
session_start();
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

include('server/connection.php'); 

// Add PHPMailer for sending emails
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer-master/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer-master/src/Exception.php';

// Verify that the correct 'action' and 'id' are provided in the query string.
if (!isset($_GET['action']) || $_GET['action'] !== 'edit' || !isset($_GET['id'])) {
    header("Location: view_appointment.php");
    exit();
}

$appointment_id = intval($_GET['id']);

// Retrieve the existing appointment details (including user and doctor info)
$stmt = $conn->prepare(
    "SELECT a.*, u.user_email, u.user_name, d.full_name AS doctor_name, d.email AS doctor_email
     FROM appointments a
     JOIN users u ON a.user_id = u.id
     JOIN doctors d ON a.doctor_id = d.id
     WHERE a.id = ?"
);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: view_appointment.php?error=Appointment not found.");
    exit();
}
$appointment = $result->fetch_assoc();
$stmt->close();

// Convert the appointment_date to a format acceptable by an HTML5 datetime-local input.
// The required format is "YYYY-MM-DDTHH:MM".
$datetime_local = date("Y-m-d\TH:i", strtotime($appointment['appointment_date']));

// Determine if status change should be disabled
$disable_status_change = in_array($appointment['status'], ['confirmed', 'completed']);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input data.
    $appointment_date_input = $_POST['appointment_date'] ?? '';
    $status = $_POST['status'] ?? '';

    // Basic validation
    if (empty($appointment_date_input) || empty($status)) {
        $error = "All fields are required.";
    } 

    if (!isset($error)) {
        // Convert the HTML5 datetime-local input (format "YYYY-MM-DDTH:MM") to MySQL DATETIME format ("YYYY-MM-DD HH:MM:SS")
        $appointment_date = str_replace("T", " ", $appointment_date_input) . ":00";
        
        // Prepare and execute the update statement using prepared statements to avoid SQL injection.
        $stmt = $conn->prepare("UPDATE appointments SET appointment_date = ?, status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ssi", $appointment_date, $status, $appointment_id);
            if ($stmt->execute()) {
                $stmt->close();

                // Send email to user and doctor if status changed
                $old_status = $appointment['status'];
                $user_email = $appointment['user_email'];
                $user_name = $appointment['user_name'];
                $doctor_name = $appointment['doctor_name'];
                $doctor_email = $appointment['doctor_email'];
                $admin_name = $_SESSION["admin_username"] ?? "Admin";

                if ($old_status !== $status) {
                    // Prepare email body (same for both)
                    $subject = 'Appointment Status Updated';
                    $body = "Dear {NAME},\n\nThe appointment (ID: $appointment_id) status has been updated to '$status'. ";
                    $body .= "\n\nUpdated by Admin: $admin_name";
                    $body .= "\n\nThank you for using Shady Shades!";

                    // Send to user
                    if ($user_email) {
                        $mail = new PHPMailer(true);
                        try {
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'np03cs4s230199@heraldcollege.edu.np'; // Replace with your Gmail
                            $mail->Password = 'gwwj hdus ymxk eluw'; // Replace with Gmail App Password
                            $mail->SMTPSecure = 'tls';
                            $mail->Port = 587;

                            $mail->setFrom('np03cs4s230199@heraldcollege.edu.np', 'Shady Shades');
                            $mail->addAddress($user_email, $user_name);
                            $mail->Subject = $subject;
                            $mail->Body = str_replace('{NAME}', $user_name, $body);
                            $mail->send();
                        } catch (Exception $e) {
                            // Optionally log or handle email error
                        }
                    }

                    // Send to doctor
                    if ($doctor_email) {
                        $mail = new PHPMailer(true);
                        try {
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'np03cs4s230199@heraldcollege.edu.np'; // Replace with your Gmail
                            $mail->Password = 'gwwj hdus ymxk eluw'; // Replace with Gmail App Password
                            $mail->SMTPSecure = 'tls';
                            $mail->Port = 587;

                            $mail->setFrom('np03cs4s230199@heraldcollege.edu.np', 'Shady Shades');
                            // Change greeting for doctor
                            $doctor_body = str_replace('Dear {NAME},', 'Dear Dr. ' . $doctor_name . ',', $body);
                            $mail->Body = $doctor_body;
                            $mail->addAddress($doctor_email, $doctor_name);
                            $mail->Subject = $subject;
                            $mail->setFrom('np03cs4s230199@heraldcollege.edu.np', 'Shady Shades');
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'np03cs4s230199@heraldcollege.edu.np';
                            $mail->Password = 'gwwj hdus ymxk eluw';
                            $mail->SMTPSecure = 'tls';
                            $mail->Port = 587;
                            $mail->send();
                        } catch (Exception $e) {
                            // Optionally log or handle email error
                        }
                    }
                }

                header("Location: view_appointment.php?success=Appointment updated successfully.");
                exit();
            } else {
                $error = "Failed to update the appointment.";
            }
        } else {
            $error = "Database error: Failed to prepare the statement.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Appointment</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- (Optional) Bootstrap Icons if used in the navbar -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <!-- Navbar similar to the Admin Dashboard -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="view_appointment.php">Appointments</a>
                    </li>
                    <!-- Other navigation items can be added here if needed -->
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-light me-3">Welcome, <?php echo htmlspecialchars($_SESSION["admin_username"]); ?></span>
                    <!-- Logout button triggers modal -->
                    <button id="logoutBtn" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <h2 class="mb-4">Manage Appointment (ID: <?php echo htmlspecialchars($appointment['id']); ?>)</h2>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <!-- Appointment Date & Time -->
            <div class="mb-3">
                <label for="appointment_date" class="form-label">Appointment Date & Time</label>
                <input type="datetime-local" name="appointment_date" id="appointment_date" class="form-control" value="<?php echo htmlspecialchars($datetime_local); ?>" required <?php if($disable_status_change) echo 'disabled'; ?>>
            </div>
            <!-- Appointment Status -->
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select" required <?php if($disable_status_change) echo 'disabled'; ?>>
                    <?php 
                    $statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
                    foreach ($statuses as $s): 
                    ?>
                        <option value="<?php echo $s; ?>" <?php if ($appointment['status'] == $s) echo "selected"; ?>>
                            <?php echo ucfirst($s); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if($disable_status_change): ?>
                    <div class="form-text text-danger">Status cannot be changed when appointment is Confirmed or Completed.</div>
                <?php endif; ?>
            </div>
            <!-- Form Actions -->
            <button type="submit" class="btn btn-primary" <?php if($disable_status_change) echo 'disabled'; ?>>Update Appointment</button>
            <a href="view_appointment.php" class="btn btn-secondary">Cancel</a>
        </form>
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
    // Logout confirmation logic
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        var modal = new bootstrap.Modal(document.getElementById('logoutConfirmModal'));
        modal.show();
    });
    </script>
</body>
</html>
