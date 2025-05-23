<?php
session_start();
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

include('server/connection.php');

// Check if appointment ID is provided
if (empty($_GET['id'])) {
    $_SESSION['alert_message'] = "No appointment ID specified.";
    $_SESSION['alert_type']   = "danger";
    header("Location: view_appointment.php");
    exit();
}

$appointment_id = (int) $_GET['id'];

$stmt = $conn->prepare("
    SELECT 
        a.id,
        a.appointment_date,
        a.status,
        u.user_name            AS patient_name,
        u.phone                AS patient_phone,
        d.full_name            AS doctor_name,
        d.specialization       AS doctor_specialization,
        d.phone                AS doctor_phone
    FROM appointments a
    JOIN users   u ON a.user_id   = u.id
    JOIN doctors d ON a.doctor_id = d.id
    WHERE a.id = ?
");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['alert_message'] = "Appointment not found.";
    $_SESSION['alert_type']   = "danger";
    header("Location: view_appointment.php");
    exit();
}

$appointment = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Appointment Details - Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
      <a class="navbar-brand" href="admin_dashboard.php">Admin Dashboard</a>
      <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="manage_users.php">Users</a></li>
          <li class="nav-item"><a class="nav-link" href="manage_products.php">Products</a></li>
          <li class="nav-item"><a class="nav-link" href="manage_doctors.php">Doctors</a></li>
          <li class="nav-item"><a class="nav-link active" href="view_appointment.php">Appointments</a></li>
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

  <div class="container">
    <h2 class="mb-4">Appointment Details</h2>
    <div class="card">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Appointment #<?php echo $appointment['id']; ?></h5>
      </div>
      <div class="card-body">
        <p><strong>Date &amp; Time:</strong>
          <?php echo date('M j, Y h:i A', strtotime($appointment['appointment_date'])); ?>
        </p>
        <p><strong>Status:</strong>
          <span class="badge
            <?php
              switch ($appointment['status']) {
                case 'pending':   echo 'bg-warning'; break;
                case 'confirmed': echo 'bg-success'; break;
                case 'completed': echo 'bg-info';    break;
                case 'cancelled': echo 'bg-danger';  break;
                default:          echo 'bg-secondary';
              }
            ?>">
            <?php echo ucfirst($appointment['status']); ?>
          </span>
        </p>

        <hr>
        <h5>Patient Information</h5>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($appointment['patient_name']); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($appointment['patient_phone']); ?></p>

        <hr>
        <h5>Doctor Information</h5>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
        <p><strong>Specialization:</strong> <?php echo htmlspecialchars($appointment['doctor_specialization']); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($appointment['doctor_phone']); ?></p>
      </div>
      <div class="card-footer text-end">
        <a href="view_appointment.php" class="btn btn-secondary">
          <i class="bi bi-arrow-left me-1"></i>Back to Appointments
        </a>
      </div>
    </div>
  </div>

  <footer class="bg-light py-4 mt-5 text-center">
    &copy; <?php echo date("Y"); ?> Admin Panel. All rights reserved.
  </footer>

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
