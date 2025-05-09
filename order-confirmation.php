<?php
session_start();
include('header.php');

// Add PHPMailer use statements at the top
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_GET['order_id'])) {
    header("Location: cart.php");
    exit();
}

$order_id = $_GET['order_id'];

// Always send order confirmation email when this page is loaded with order_id
// Fetch user email and name from session or database
$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';

// If not in session, fetch from database
if (empty($user_email) || empty($user_name)) {
    include("server/connection.php");
    $stmt = $conn->prepare("SELECT u.user_email, u.user_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_email = $user['user_email'];
        $user_name = $user['user_name'];
    }
}

if (!empty($user_email) && !empty($user_name)) {
    // PHPMailer
    require_once 'PHPMailer-master/src/PHPMailer.php';
    require_once 'PHPMailer-master/src/SMTP.php';
    require_once 'PHPMailer-master/src/Exception.php';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'np03cs4s230199@heraldcollege.edu.np';
        $mail->Password = 'gwwj hdus ymxk eluw';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('np03cs4s230199@heraldcollege.edu.np', 'Shady Shades');
        $mail->addReplyTo('np03cs4s230199@heraldcollege.edu.np', 'Shady Shades');
        $mail->addAddress($user_email, $user_name);
        $mail->isHTML(false);

        $mail->Subject = 'Order Confirmation - Shady Shades';
        $mail->Body = "Dear $user_name,\n\nThank you for your order!\nYour order (Order ID: $order_id) has been placed successfully.\n\nWe appreciate your business.\n\nShady Shades Team";

        $mail->send();
    } catch (Exception $e) {
        error_log('Order confirmation mail error: ' . $mail->ErrorInfo);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation | Shady Shades</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                <h2 class="card-title">Order Confirmation</h2>
                <p class="card-text">Your order has been placed successfully!</p>
                <p class="card-text">Order ID: <strong><?= htmlspecialchars($order_id); ?></strong></p>
                <a href="index.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        </div>
    </div>
</body>
</html>