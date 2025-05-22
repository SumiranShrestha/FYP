<?php
session_start();
include("server/connection.php");

// Check if admin is logged in
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

// Handle User Deletion
if (isset($_GET['delete_user'])) {
    // Validate and sanitize the user ID from GET
    $user_id = $_GET['delete_user'];
    if (!filter_var($user_id, FILTER_VALIDATE_INT)) {
        $_SESSION['alert_message'] = "Invalid user ID.";
        $_SESSION['alert_type'] = "danger";
        header("Location: manage_users.php");
        exit();
    }

    // Delete dependent records in prescription_frames
    $stmt = $conn->prepare("DELETE FROM prescription_frames WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // Delete dependent records in prescription_orders
    $stmt = $conn->prepare("DELETE FROM prescription_orders WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // Now delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['alert_message'] = "User successfully deleted.";
            $_SESSION['alert_type'] = "success";
        } else {
            $_SESSION['alert_message'] = "No user found with that ID.";
            $_SESSION['alert_type'] = "warning";
        }
    } else {
        $_SESSION['alert_message'] = "Error deleting user: " . $stmt->error;
        $_SESSION['alert_type'] = "danger";
    }
    $stmt->close();

    header("Location: manage_users.php");
    exit();
}

// Fetch All Users (including registration_date)
$result = $conn->query("
    SELECT 
      id,
      user_name,
      user_email,
      active,
      registration_date
    FROM users
    ORDER BY id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Users</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
      <a class="navbar-brand" href="#">Admin Dashboard</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link active" href="manage_users.php">Users</a></li>
          <li class="nav-item"><a class="nav-link" href="manage_products.php">Products</a></li>
        </ul>
        <div class="d-flex align-items-center">
          <span class="text-light me-3">
            Welcome, <?= htmlspecialchars($_SESSION["admin_username"] ?? ''); ?>
          </span>
          <a href="admin_logout.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-box-arrow-right me-1"></i>Logout
          </a>
        </div>
      </div>
    </div>
  </nav>

  <div class="container">
    <!-- Header + Back button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2><i class="bi bi-people-fill me-2"></i>Manage Users</h2>
      <a href="admin_dashboard.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
      </a>
    </div>

    <!-- Alert -->
    <?php if (!empty($_SESSION['alert_message'])): ?>
      <div class="alert alert-<?= $_SESSION['alert_type'] ?> alert-dismissible fade show">
        <?= $_SESSION['alert_message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['alert_message'], $_SESSION['alert_type']); ?>
    <?php endif; ?>

    <!-- Search & Add -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="input-group">
          <input id="searchInput" class="form-control" placeholder="Search users...">
          <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
        </div>
      </div>
      <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <a href="add_user.php" class="btn btn-success">
          <i class="bi bi-person-plus-fill me-1"></i>Add New User
        </a>
      </div>
    </div>

    <!-- Users Table -->
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover table-striped">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Registration Date</th>
                <th>Status</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result && $result->num_rows): ?>
                <?php while ($u = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['user_name']) ?></td>
                    <td><?= htmlspecialchars($u['user_email']) ?></td>
                    <td>
                      <?= !empty($u['registration_date'])
                           ? date('M d, Y', strtotime($u['registration_date']))
                           : 'N/A'; ?>
                    </td>
                    <td>
                      <?php if ($u['active']): ?>
                        <span class="badge bg-success">Active</span>
                      <?php else: ?>
                        <span class="badge bg-secondary">Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <div class="btn-group btn-group-sm">
                        <a href="edit_user.php?id=<?= $u['id'] ?>" class="btn btn-primary">
                          <i class="bi bi-pencil"></i>
                        </a>
                        <a href="view_user.php?id=<?= $u['id'] ?>" class="btn btn-info">
                          <i class="bi bi-eye"></i>
                        </a>
                        <a href="manage_users.php?delete_user=<?= $u['id'] ?>"
                           class="btn btn-danger"
                           onclick="return confirm('Delete this user?');">
                          <i class="bi bi-trash"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="text-center py-4">No users found</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-light py-4 mt-5 text-center">
    &copy; <?= date('Y') ?> Admin Panel. All rights reserved.
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  // Simple search filter
  document.getElementById('searchInput').addEventListener('keyup', function() {
    const term = this.value.toLowerCase();
    document.querySelectorAll('tbody tr').forEach(row => {
      row.style.display = [...row.cells].some(td =>
        td.textContent.toLowerCase().includes(term)
      ) ? '' : 'none';
    });
  });

  // Auto-dismiss alerts after 3 seconds
  setTimeout(function() {
    var alert = document.querySelector('.alert-dismissible');
    if (alert) {
      var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
      bsAlert.close();
    }
  }, 3000);
  </script>
</body>
</html>
