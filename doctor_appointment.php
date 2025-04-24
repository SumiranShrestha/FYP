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
require_once __DIR__ . '/header.php';

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Appointment Details</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?php require_once __DIR__ . '/header.php'; ?>

  <div class="container mt-5">
    <h2 class="mb-4">Appointment Details</h2>

    <div class="card mb-4">
      <div class="card-header bg-primary text-white">
        Patient Information
      </div>
      <div class="card-body">
        <p><strong>Name:</strong>  <?= htmlspecialchars($appointment['patient_name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($appointment['patient_email']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($appointment['patient_phone']) ?></p>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header bg-secondary text-white">
        Appointment Details
      </div>
      <div class="card-body">
        <p><strong>Date & Time:</strong> <?= date('M j, Y h:i A', strtotime($appointment['appointment_date'])) ?></p>
        <p><strong>Status:</strong> 
          <?php
            switch ($appointment['status']) {
              case 'pending':    $badge = 'warning'; break;
              case 'confirmed':  $badge = 'success'; break;
              case 'completed':  $badge = 'info';    break;
              default:           $badge = 'secondary';
            }
          ?>
          <span class="badge bg-<?= $badge ?>">
            <?= ucfirst($appointment['status']) ?>
          </span>
        </p>
      </div>
    </div>

    <?php if (!empty($appointment['prescription'])): ?>
      <div class="card mb-4">
        <div class="card-header bg-success text-white">
          Prescription
        </div>
        <div class="card-body">
          <?= nl2br(htmlspecialchars($appointment['prescription'])) ?>
        </div>
      </div>
    <?php endif; ?>

    <form method="POST" action="server/update_appointment.php">
      <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">

      <div class="mb-3">
        <label for="status" class="form-label">Update Status</label>
        <select id="status" name="status" class="form-select">
          <?php foreach (['pending','confirmed','completed','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $appointment['status'] === $s ? 'selected' : '' ?>>
              <?= ucfirst($s) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label for="prescription" class="form-label">Prescription</label>
        <textarea id="prescription" name="prescription" rows="5" class="form-control"><?= htmlspecialchars($appointment['prescription'] ?? '') ?></textarea>
      </div>

      <button type="submit" class="btn btn-primary">Save Changes</button>
      <a href="doctor_appointments.php" class="btn btn-secondary">← Back</a>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php require_once __DIR__ . '/footer.php'; ?>
