<?php
session_start();

if (isset($_POST['submit_otp'])) {
  $entered_otp = $_POST['otp'];

  // Check for user OTP
  if (isset($_SESSION['otp_expire']) && time() <= $_SESSION['otp_expire'] && $entered_otp == $_SESSION['otp']) {
    $_SESSION['otp_verified'] = true;
    // Redirect to user reset password page
    header("Location: reset_password.php");
    exit; // Ensure the script stops and the redirection happens
  }

  // Check for doctor OTP
  elseif (isset($_SESSION['doctor_otp_expire']) && time() <= $_SESSION['doctor_otp_expire'] && $entered_otp == $_SESSION['doctor_otp']) {
    $_SESSION['doctor_otp_verified'] = true;
    // Redirect to doctor reset password page
    header("Location: doctor_reset_password.php");
    exit; // Ensure the script stops and the redirection happens
  }

  // OTP validation failed
  else {
    $error = "Invalid or expired OTP.";
  }
}
?>

<?php include('../header.php'); ?>

<section class="my-5 py-5">
  <div class="container text-center mt-3 pt-5">
    <h2 class="mb-4">Verify OTP</h2>
    <p class="text-muted">Enter the OTP sent to your registered email address to proceed.</p>
    <hr class="mx-auto w-50">

    <!-- Display error message if OTP is invalid or expired -->
    <?php if (isset($error)): ?>
      <div class="alert alert-danger w-50 mx-auto" role="alert">
        <?php echo $error; ?>
      </div>
    <?php endif; ?>

    <!-- Display success message if OTP is sent -->
    <?php if (isset($_GET['message'])): ?>
      <div class="alert alert-success w-50 mx-auto" role="alert">
        <?php echo $_GET['message']; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="mx-auto container w-50">
    <!-- OTP Verification Form -->
    <form method="POST" action="verify_otp.php" class="shadow p-4 rounded bg-light">
      <div class="form-group mb-3">
        <label for="otp" class="form-label">Enter OTP</label>
        <input type="text" id="otp" class="form-control" name="otp" placeholder="Enter your OTP" required />
      </div>
      <div class="d-grid">
        <button type="submit" name="submit_otp" class="btn btn-primary btn-block">Verify OTP</button>
      </div>
    </form>
  </div>
</section>

<?php include('../footer.php'); ?>