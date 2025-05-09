<?php
session_start();
include('../server/connection.php');

if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_email'])) {
  // If OTP is not verified or reset_email is not set in the session, redirect to forgot password page
  header("Location: ../otp/forgot_password.php");
  exit;
}

if (isset($_POST['reset_password'])) {
  // Get the new password from the form
  $password = $_POST['password'];

  // Password strength validation with RegEx
  $password_pattern = "/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/"; // At least 1 uppercase, 1 number, and 1 special character, minimum 8 characters

  if (!preg_match($password_pattern, $password)) {
    $error = "Password must contain at least one uppercase letter, one special character, and one number, and be at least 8 characters long.";
  } else {
    // If password is strong, hash it
    $password_hashed = password_hash($password, PASSWORD_BCRYPT);
    $email = $_SESSION['reset_email'];

    // Check if the email belongs to a normal user
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE user_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($user_count);
    $stmt->fetch();
    $stmt->close();

    if ($user_count > 0) {
      // If user exists in users table, update password
      $stmt = $conn->prepare("UPDATE users SET user_password = ? WHERE user_email = ?");
      $stmt->bind_param("ss", $password_hashed, $email);
      $stmt->execute();
      $stmt->close();

      $reset_success = true;
      session_destroy(); // End the session after successful password reset

    } else {
      // Check if the email belongs to a doctor
      $stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE doctor_email = ?");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $result = $stmt->get_result();
      $doctor = $result->fetch_assoc();
      $stmt->close();

      if ($doctor) {
        // If doctor exists, update password
        $stmt = $conn->prepare("UPDATE doctors SET doctor_password = ? WHERE doctor_email = ?");
        $stmt->bind_param("ss", $password_hashed, $email);
        if ($stmt->execute()) {
          $reset_success = true;
          session_destroy();
        } else {
          $error = "Failed to reset password for doctor.";
        }
        $stmt->close();
      } else {
        // If user/doctor not found, display error
        $error = "User not found.";
      }
    }
  }
}
?>

<?php include('../header.php'); ?>

<section class="my-5 py-5">
  <div class="container text-center mt-3 pt-5">
    <h2 class="mb-4">Reset Password</h2>
    <p class="text-muted">Enter your new password below to reset your account password.</p>
    <hr class="mx-auto w-50">

    <?php if (isset($error)): ?>
      <div class="alert alert-danger w-50 mx-auto" role="alert">
        <?php echo $error; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="mx-auto container w-50">
    <form method="POST" action="reset_password.php" class="shadow p-4 rounded bg-light">
      <div class="form-group mb-3">
        <label for="password" class="form-label">New Password</label>
        <input type="password" id="password" class="form-control" name="password" placeholder="Enter your new password" required />
      </div>
      <div class="d-grid">
        <button type="submit" name="reset_password" class="btn btn-primary btn-block">Reset Password</button>
      </div>
    </form>
  </div>
</section>

<!-- Toastr CSS/JS (if not already included in header) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
  <?php if (isset($reset_success)): ?>
    toastr.options = {
      "positionClass": "toast-bottom-right",
      "timeOut": 2000,
      "closeButton": true
    };
    toastr.success("Reset password successful!");
    setTimeout(function() {
      window.location.href = "../index.php"; // Redirect to home after successful reset
    }, 2000);
  <?php endif; ?>
</script>

<?php include('../footer.php'); ?>