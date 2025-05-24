<?php
session_start();

// 1) Only doctors may view
if (!isset($_SESSION["user_id"]) || (
    $_SESSION["user_type"] ?? ''
) !== 'doctor') {
    header("Location: index.php");
    exit();
}

// 2) Must have an appointment ID
if (!isset($_GET["id"])) {
    header("Location: doctor_appointments.php");
    exit();
}

// 3) Include the DB connection and header/footer
require_once __DIR__ . '/server/connection.php';

// Add PHPMailer for sending emails
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';
require_once __DIR__ . '/PHPMailer-master/src/Exception.php';

// 5) Handle POST: update appointment and send mail if status changed
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["appointment_id"])) {
    $appointment_id = (int)$_POST["appointment_id"];
    $doctor_id = $_SESSION["user_id"];
    $new_status = $_POST["status"];

    // Fetch current status, patient info, and doctor name
    $stmt = $conn->prepare(
        "SELECT a.status, u.user_email, u.user_name, d.full_name AS doctor_name
         FROM appointments a
         JOIN users u ON a.user_id = u.id
         JOIN doctors d ON a.doctor_id = d.id
         WHERE a.id = ? AND a.doctor_id = ?"
    );
    $stmt->bind_param("ii", $appointment_id, $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $send_mail = false;
    $user_email = '';
    $user_name = '';
    $doctor_name = '';
    $old_status = '';
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $old_status = $row['status'];
        $user_email = $row['user_email'];
        $user_name = $row['user_name'];
        $doctor_name = $row['doctor_name'];
        if ($old_status !== $new_status) {
            $send_mail = true;
        }
    }
    $stmt->close();

    // Update appointment
    $stmt = $conn->prepare("UPDATE appointments SET status = ?, prescription = ? WHERE id = ? AND doctor_id = ?");
    // Parse prescription fields from POST and encode as JSON
    $prescription_data = [
        'right_eye_sphere' => $_POST['right_eye_sphere'] ?? '',
        'right_eye_cylinder' => $_POST['right_eye_cylinder'] ?? '',
        'right_eye_axis' => $_POST['right_eye_axis'] ?? '',
        'left_eye_sphere' => $_POST['left_eye_sphere'] ?? '',
        'left_eye_cylinder' => $_POST['left_eye_cylinder'] ?? '',
        'left_eye_axis' => $_POST['left_eye_axis'] ?? ''
    ];
    $new_prescription = json_encode($prescription_data);
    $stmt->bind_param("ssii", $new_status, $new_prescription, $appointment_id, $doctor_id);
    if ($stmt->execute()) {
        if ($send_mail && $user_email) {
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
                $mail->Subject = 'Your Appointment Status Has Changed';
                $body = "Dear $user_name,\n\nYour appointment (ID: $appointment_id) status has been updated to '$new_status'.";
                if ($new_status === 'completed') {
                    // Add prescription details if present
                    $presc = json_decode($new_prescription, true);
                    if ($presc && (
                        $presc['right_eye_sphere'] || $presc['right_eye_cylinder'] || $presc['right_eye_axis'] ||
                        $presc['left_eye_sphere'] || $presc['left_eye_cylinder'] || $presc['left_eye_axis']
                    )) {
                        $body .= "\n\nPrescription:\n";
                        $body .= "Right Eye:\n";
                        $body .= "  Sphere (SPH): " . ($presc['right_eye_sphere'] ?: '-') . "\n";
                        $body .= "  Cylinder (CYL): " . ($presc['right_eye_cylinder'] ?: '-') . "\n";
                        $body .= "  Axis: " . ($presc['right_eye_axis'] ?: '-') . "\n";
                        $body .= "Left Eye:\n";
                        $body .= "  Sphere (SPH): " . ($presc['left_eye_sphere'] ?: '-') . "\n";
                        $body .= "  Cylinder (CYL): " . ($presc['left_eye_cylinder'] ?: '-') . "\n";
                        $body .= "  Axis: " . ($presc['left_eye_axis'] ?: '-') . "\n";
                    }
                    $body .= "\n\nDoctor,\n$doctor_name";
                    $body .= "\n\nThank you for choosing Shady Shades!";
                } else {
                    $body .= "\n\nDoctor,\n$doctor_name";
                }
                $mail->Body = $body;

                $mail->send();
            } catch (Exception $e) {
                // Optionally log or handle email error, but do not block the process
            }
        }
        $_SESSION['alert_message'] = "Appointment updated successfully";
        $_SESSION['alert_type'] = "success";
    } else {
        $_SESSION['alert_message'] = "Error updating appointment: " . $conn->error;
        $_SESSION['alert_type'] = "danger";
    }
    $stmt->close();
    $conn->close();
    header("Location: doctor_appointment.php?id=" . $appointment_id);
    exit();
}

$appointment_id = (int) $_GET["id"];
$doctor_id      = $_SESSION["user_id"];

// 4) Fetch appointment + patient info
$stmt = $conn->prepare(
    "SELECT 
        a.*, 
        u.user_name   AS patient_name, 
        u.user_email  AS patient_email, 
        u.phone       AS patient_phone
     FROM appointments a
     JOIN users u ON a.user_id = u.id
     WHERE a.id = ? AND a.doctor_id = ?"
);
$stmt->bind_param("ii", $appointment_id, $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // No such appointment for this doctor
    header("Location: doctor_appointments.php");
    exit();
}

$appointment = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Prepare prescription fields for display
$prescription_fields = [
    'right_eye_sphere' => '',
    'right_eye_cylinder' => '',
    'right_eye_axis' => '',
    'left_eye_sphere' => '',
    'left_eye_cylinder' => '',
    'left_eye_axis' => ''
];
if (!empty($appointment['prescription'])) {
    $decoded = json_decode($appointment['prescription'], true);
    if (is_array($decoded)) {
        $prescription_fields = array_merge($prescription_fields, $decoded);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Appointment Details | Shady Shades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc3545;
        }
        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s ease-in-out;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            border-bottom: none;
        }
        .prescription-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }
        .form-control, .form-select {
            border-radius: 8px;
        }
        .btn {
            border-radius: 8px;
            padding: 8px 20px;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 6px 12px;
            border-radius: 20px;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/header.php'; ?>

    <div class="container my-5">
        <?php if (isset($_SESSION['alert_message'])): ?>
            <div class="alert alert-<?= $_SESSION['alert_type'] ?> alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                <?= $_SESSION['alert_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['alert_message'], $_SESSION['alert_type']); ?>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-calendar2-check me-2"></i>Appointment Details</h2>
            <a href="doctor_appointments.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Appointments
            </a>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="card-title mb-0"><i class="bi bi-person-circle me-2"></i>Patient Information</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3">
                                <i class="bi bi-person me-2"></i>
                                <strong>Name:</strong> <?= htmlspecialchars($appointment['patient_name']) ?>
                            </li>
                            <li class="mb-3">
                                <i class="bi bi-envelope me-2"></i>
                                <strong>Email:</strong> <?= htmlspecialchars($appointment['patient_email']) ?>
                            </li>
                            <li>
                                <i class="bi bi-telephone me-2"></i>
                                <strong>Phone:</strong> <?= htmlspecialchars($appointment['patient_phone']) ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-secondary text-white py-3">
                        <h5 class="card-title mb-0"><i class="bi bi-calendar2-week me-2"></i>Appointment Details</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3">
                                <i class="bi bi-clock me-2"></i>
                                <strong>Date & Time:</strong> 
                                <?= date('F j, Y h:i A', strtotime($appointment['appointment_date'])) ?>
                            </li>
                            <li>
                                <i class="bi bi-journal-check me-2"></i>
                                <strong>Status:</strong>
                                <?php
                                    $badgeClass = match($appointment['status']) {
                                        'pending' => 'warning',
                                        'confirmed' => 'success',
                                        'completed' => 'info',
                                        'cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>
                                <span class="badge bg-<?= $badgeClass ?> status-badge">
                                    <?= ucfirst($appointment['status']) ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="" class="card shadow-sm">
            <div class="card-body">
                <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="status" class="form-label fw-bold">
                            <i class="bi bi-toggle2-on me-2"></i>Update Status
                        </label>
                        <select id="status" name="status" class="form-select">
                            <?php foreach (['pending','confirmed','completed','cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $appointment['status'] === $s ? 'selected' : '' ?>>
                                    <?= ucfirst($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="prescription-card mb-4">
                    <h5 class="mb-4"><i class="bi bi-file-medical me-2"></i>Prescription Details</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-eye me-2"></i>Right Eye</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col">
                                            <label class="form-label" style="font-size:0.9rem;">Sphere (SPH)</label>
                                            <input type="text" name="right_eye_sphere" class="form-control form-control-sm" value="<?= htmlspecialchars($prescription_fields['right_eye_sphere']) ?>">
                                        </div>
                                        <div class="col">
                                            <label class="form-label" style="font-size:0.9rem;">Cylinder (CYL)</label>
                                            <input type="text" name="right_eye_cylinder" class="form-control form-control-sm" value="<?= htmlspecialchars($prescription_fields['right_eye_cylinder']) ?>">
                                        </div>
                                        <div class="col">
                                            <label class="form-label" style="font-size:0.9rem;">Axis</label>
                                            <input type="text" name="right_eye_axis" class="form-control form-control-sm" value="<?= htmlspecialchars($prescription_fields['right_eye_axis']) ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-eye me-2"></i>Left Eye</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col">
                                            <label class="form-label" style="font-size:0.9rem;">Sphere (SPH)</label>
                                            <input type="text" name="left_eye_sphere" class="form-control form-control-sm" value="<?= htmlspecialchars($prescription_fields['left_eye_sphere']) ?>">
                                        </div>
                                        <div class="col">
                                            <label class="form-label" style="font-size:0.9rem;">Cylinder (CYL)</label>
                                            <input type="text" name="left_eye_cylinder" class="form-control form-control-sm" value="<?= htmlspecialchars($prescription_fields['left_eye_cylinder']) ?>">
                                        </div>
                                        <div class="col">
                                            <label class="form-label" style="font-size:0.9rem;">Axis</label>
                                            <input type="text" name="left_eye_axis" class="form-control form-control-sm" value="<?= htmlspecialchars($prescription_fields['left_eye_axis']) ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>

    <?php require_once __DIR__ . '/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
