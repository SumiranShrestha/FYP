<?php
session_start();
include('../server/connection.php');

if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_email'])) {
    header("Location: ../otp/forgot_password.php");
    exit;
}

if (isset($_POST['reset_password'])) {
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $email = $_SESSION['reset_email'];

    $stmt = $conn->prepare("UPDATE users SET user_password = ? WHERE user_email = ?");
    $stmt->bind_param("ss", $password, $email);

    if ($stmt->execute()) {
        session_destroy();
        header("Location: ../index.php?message=Password reset successfully"); // Redirect to login page
        exit;
    } else {
        $error = "Failed to reset password.";
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

<?php include('../footer.php'); ?>
