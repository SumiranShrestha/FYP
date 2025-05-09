<?php
session_start();
include('../server/connection.php');

if (!isset($_SESSION['doctor_otp_verified']) || !isset($_SESSION['doctor_reset_email'])) {
    header("Location: ../otp/forgot_password.php");
    exit;
}

if (isset($_POST['reset_password'])) {
    // Validate the password strength using RegEx
    $password = $_POST['password'];
    $password_pattern = "/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/"; // At least 1 uppercase, 1 number, and 1 special character, minimum 8 characters

    if (!preg_match($password_pattern, $password)) {
        $error = "Password must contain at least one uppercase letter, one special character, and one number, and be at least 8 characters long.";
    } else {
        // If password is strong, hash it
        $password_hashed = password_hash($password, PASSWORD_BCRYPT);
        $email = $_SESSION['doctor_reset_email'];

        // Update doctor password
        $stmt = $conn->prepare("UPDATE doctors SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $password_hashed, $email);
        $stmt->execute();
        $stmt->close();

        // Set success flag
        $reset_success = true;

        // Destroy the session after successful password reset
        session_destroy();

        // Redirect to the home page (index.php)
        header("Location: ../index.php");
        exit;
    }
}
?>

<?php include('../header.php'); ?>

<section class="my-5 py-5">
    <div class="container text-center mt-3 pt-5">
        <h2 class="mb-4">Doctor Reset Password</h2>
        <p class="text-muted">Enter your new password below to reset your account password.</p>
        <hr class="mx-auto w-50">

        <?php if (isset($error)): ?>
            <div class="alert alert-danger w-50 mx-auto" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($reset_success)): ?>
            <div class="alert alert-success w-50 mx-auto" role="alert">
                Password reset successful. You will be redirected shortly.
            </div>
        <?php endif; ?>
    </div>

    <div class="mx-auto container w-50">
        <form method="POST" action="doctor_reset_password.php" class="shadow p-4 rounded bg-light">
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