<?php
session_start();
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

include('server/connection.php'); 

// Fetch appointments with the correct user_name column
$query = "
    SELECT 
        a.id,
        u.user_name       AS patient_name,
        u.phone           AS patient_phone,
        d.full_name       AS doctor_name,
        d.specialization,
        a.appointment_date,
        a.status
    FROM appointments a
    JOIN users   u ON a.user_id   = u.id
    JOIN doctors d ON a.doctor_id = d.id
    ORDER BY a.appointment_date DESC
";
$result = mysqli_query($conn, $query);
if (!$result) {
    die("Database query failed: " . mysqli_error($conn));
}
$appointments = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>View Appointments - Admin Dashboard</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
      <a class="navbar-brand" href="admin_dashboard.php">Admin Dashboard</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
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
          <a href="admin_logout.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-box-arrow-right me-1"></i>Logout
          </a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="container">
    <div class="card mb-4">
      <div class="card-header bg-primary text-white">
        <h4 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Manage Appointments</h4>
      </div>
      <div class="card-body">
        <?php if (empty($appointments)): ?>
          <div class="alert alert-info">No appointments found.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-bordered table-hover">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Patient</th>
                  <th>Doctor</th>
                  <th>Specialization</th>
                  <th>Date &amp; Time</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($appointments as $appt): ?>
                  <tr class="status-<?php echo $appt['status']; ?>">
                    <td><?php echo $appt['id']; ?></td>
                    <td>
                      <?php echo htmlspecialchars($appt['patient_name']); ?><br>
                      <small class="text-muted"><?php echo htmlspecialchars($appt['patient_phone']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($appt['doctor_name']); ?></td>
                    <td><?php echo htmlspecialchars($appt['specialization']); ?></td>
                    <td><?php echo date('M j, Y h:i A', strtotime($appt['appointment_date'])); ?></td>
                    <td>
                      <span class="badge 
                        <?php 
                          switch ($appt['status']) {
                            case 'pending':   echo 'bg-warning'; break;
                            case 'confirmed': echo 'bg-success'; break;
                            case 'completed': echo 'bg-info';    break;
                            case 'cancelled': echo 'bg-danger';  break;
                            default:          echo 'bg-secondary';
                          }
                        ?>">
                        <?php echo ucfirst($appt['status']); ?>
                      </span>
                    </td>
                    <td>
                      <div class="d-flex gap-1">
                        <a href="manage_appointment.php?action=edit&id=<?php echo $appt['id']; ?>" class="btn btn-sm btn-primary">
                          <i class="bi bi-pencil"></i>
                        </a>
                        <a href="view_appointment_details.php?id=<?php echo $appt['id']; ?>" class="btn btn-sm btn-info">
                          <i class="bi bi-eye"></i>
                        </a>
                        <a href="delete_appointment.php?action=delete&id=<?php echo $appt['id']; ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Are you sure you want to delete this appointment?');">
                          <i class="bi bi-trash"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-light py-4 mt-5 text-center">
    &copy; <?php echo date("Y"); ?> Admin Panel. All rights reserved.
  </footer>

  <!-- Bootstrap JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
