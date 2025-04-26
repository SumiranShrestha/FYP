<?php
session_start();
include('../server/connection.php');

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';
require '../PHPMailer-master/src/Exception.php';

if (isset($_POST['submit_email'])) {
    $email = $_POST['email'];

    // Check if user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['reset_email'] = $email;
        $_SESSION['otp_expire'] = time() + 300;

        $mail = new PHPMailer(true);

        try {
            // SMTP settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'np03cs4s230199@heraldcollege.edu.np';       // replace with your Gmail
            $mail->Password = 'gwwj hdus ymxk eluw';          // replace with Gmail App Password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('np03cs4s230199@heraldcollege.edu.np', 'Shady Shades');
            $mail->addAddress($email);
            $mail->Subject = 'Your OTP Code';
            $mail->Body = "Your Shady Shades Account code is $otp. Do not share this code with anyone. It is valid for 5 minutes.";

            $mail->send();
            header("Location: verify_otp.php?message=OTP Sent to your email");
            exit;
        } catch (Exception $e) {
            $error = "Mailer Error: " . $mail->ErrorInfo;
        }
    } else {
        $error = "Email not registered.";
    }
}
?>

<?php include('../header.php'); ?>

<section class="my-5 py-5">
  <div class="container text-center mt-3 pt-5">
    <h2 class="mb-4">Forgot Password</h2>
    <p class="text-muted">Enter your registered email address to receive an OTP for password reset.</p>
    <hr class="mx-auto w-50">
    <?php if (isset($error)): ?>
      <div class="alert alert-danger w-50 mx-auto" role="alert">
        <?php echo $error; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="mx-auto container w-50">
    <form method="POST" action="forgot_password.php" class="shadow p-4 rounded bg-light">
      <div class="form-group mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" id="email" class="form-control" name="email" placeholder="Enter your email" required />
      </div>
      <div class="d-grid">
        <button type="submit" name="submit_email" class="btn btn-primary btn-block">Send Code</button>
      </div>
    </form>
  </div>
</section>

<?php include('../footer.php'); ?>
