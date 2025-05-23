<?php
session_start();

// Redirect to login if user not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include('server/connection.php');
include('header.php');

$user_id = $_SESSION["user_id"];

// Retrieve user details
$stmt = $conn->prepare("SELECT user_name, user_email, phone, address, city FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Profile</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .profile-container {
      max-width: 600px;
      margin: 40px auto;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      background: #fff;
    }
  </style>
</head>
<body>
  <div class="container py-5">
    <div class="profile-container">
      <h2 class="mb-4">Edit Profile</h2>

      <?php if (isset($_SESSION['alert_message'])): ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['alert_type']) ?> alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($_SESSION['alert_message']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php 
          unset($_SESSION['alert_message'], $_SESSION['alert_type']);
        ?>
      <?php endif; ?>

      <form method="POST" action="update_profile.php">
        <div class="mb-3">
          <label for="user_name" class="form-label">Full Name</label>
          <input type="text" id="user_name" name="user_name" class="form-control"
                 value="<?= htmlspecialchars($user['user_name'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
          <label for="user_email" class="form-label">Email</label>
          <input type="email" id="user_email" name="user_email" class="form-control"
                 value="<?= htmlspecialchars($user['user_email'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
          <label for="phone" class="form-label">Phone</label>
          <input type="text" id="phone" name="phone" class="form-control"
                 value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                 pattern="\d{10}" maxlength="10" title="Phone number must be exactly 10 digits" required>
        </div>

        <div class="mb-3">
          <label for="address" class="form-label">Address</label>
          <input type="text" id="address" name="address" class="form-control"
                 value="<?= htmlspecialchars($user['address'] ?? '') ?>">
        </div>

        <div class="mb-3">
          <label for="city" class="form-label">City</label>
          <input type="text" id="city" name="city" class="form-control"
                 value="<?= htmlspecialchars($user['city'] ?? '') ?>">
        </div>

        <button type="submit" class="btn btn-primary w-100">
          <i class="bi bi-save me-1"></i> Update Profile
        </button>
      </form>
    </div>
  </div>

  <!-- Bootstrap Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  // Phone number validation for edit profile
  document.addEventListener("DOMContentLoaded", function() {
      var phoneInput = document.getElementById('phone');
      if (phoneInput) {
          phoneInput.addEventListener('input', function() {
              // Remove non-digit characters
              this.value = this.value.replace(/\D/g, '');
          });
          phoneInput.addEventListener('invalid', function() {
              this.setCustomValidity('Phone number must be exactly 10 digits.');
          });
          phoneInput.addEventListener('input', function() {
              this.setCustomValidity('');
          });
      }
  });
  </script>
</body>
</html>

<?php 
$conn->close();
include('footer.php');
?>
