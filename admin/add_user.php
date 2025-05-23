<?php
session_start();
include("server/connection.php");

// Check if admin is logged in
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

$errors = [];

// Process form when submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and assign form inputs
    $user_name       = trim($_POST["user_name"]);
    $user_email      = trim($_POST["user_email"]);
    $user_password   = trim($_POST["user_password"]);
    $confirm_password= trim($_POST["confirm_password"]);

    // Validate inputs
    if (empty($user_name)) {
        $errors[] = "Name is required.";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $user_name)) {
        $errors[] = "Name must contain only letters and spaces.";
    }
    if (empty($user_email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL) || !preg_match('/@.+\.com$/', $user_email)) {
        $errors[] = "Email must be valid and contain @...com";
    }
    if (empty($user_password)) {
        $errors[] = "Password is required.";
    }
    if ($user_password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // If no error so far, check if the email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE user_email = ?");
        $stmt->bind_param("s", $user_email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "A user with that email already exists.";
        }
        $stmt->close();
    }

    // If validations passed, insert the new user
    if (empty($errors)) {
        // Hash the password for security
        $hashed_password = password_hash($user_password, PASSWORD_DEFAULT);
        // Set default active status (1 means active)
        $active = 1;
        
        // Updated query without the created_at column
        $stmt = $conn->prepare("INSERT INTO users (user_name, user_email, user_password, active) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            $errors[] = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("sssi", $user_name, $user_email, $hashed_password, $active);

            if ($stmt->execute()) {
                $_SESSION['alert_message'] = "User successfully added";
                $_SESSION['alert_type'] = "success";
                header("Location: manage_users.php");
                exit();
            } else {
                $errors[] = "Error adding user. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add New User</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
          <li class="nav-item">
            <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="manage_users.php">Users</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="manage_products.php">Products</a>
          </li>
        </ul>
        <div class="d-flex align-items-center">
          <span class="text-light me-3">
            Welcome, <?php echo isset($_SESSION["admin_username"]) ? htmlspecialchars($_SESSION["admin_username"]) : 'Guest'; ?>
          </span>
          <a href="admin_logout.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-box-arrow-right me-1"></i>Logout
          </a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="container">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>
        <i class="bi bi-person-plus-fill me-2"></i>Add New User
      </h2>
      <a href="manage_users.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Manage Users
      </a>
    </div>

    <!-- Display Errors -->
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $error): ?>
            <li><?php echo $error; ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Add User Form -->
    <div class="card shadow-sm">
      <div class="card-body">
        <form action="" method="POST">
          <div class="mb-3">
            <label for="userName" class="form-label">Name</label>
            <input 
              type="text" 
              class="form-control" 
              id="userName" 
              name="user_name" 
              value="<?php echo isset($_POST['user_name']) ? htmlspecialchars($_POST['user_name']) : ''; ?>" 
              required>
          </div>
          <div class="mb-3">
            <label for="userEmail" class="form-label">Email</label>
            <input 
              type="email" 
              class="form-control" 
              id="userEmail" 
              name="user_email" 
              value="<?php echo isset($_POST['user_email']) ? htmlspecialchars($_POST['user_email']) : ''; ?>" 
              required>
          </div>
          <div class="mb-3">
            <label for="userPassword" class="form-label">Password</label>
            <input type="password" class="form-control" id="userPassword" name="user_password" required>
          </div>
          <div class="mb-3">
            <label for="confirmPassword" class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
          </div>
          <!-- Uncomment below if you wish to set active status manually
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="activeCheck" name="active" checked>
            <label class="form-check-label" for="activeCheck">Active</label>
          </div>
          -->
          <button type="submit" class="btn btn-success">
            <i class="bi bi-check-circle me-1"></i>Add User
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-light py-4 mt-5">
    <div class="container text-center">
      <p class="mb-0">&copy; <?php echo date("Y"); ?> Admin Panel. All rights reserved.</p>
    </div>
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

  <!-- Bootstrap JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  // Logout confirmation logic
  document.addEventListener('DOMContentLoaded', function () {
    var logoutBtn = document.querySelector('.btn-outline-light.btn-sm');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', function(e) {
        e.preventDefault();
        var modal = new bootstrap.Modal(document.getElementById('logoutConfirmModal'));
        modal.show();
      });
    }
  });
  </script>
</body>
</html>
