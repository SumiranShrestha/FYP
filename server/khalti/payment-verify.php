<?php
session_start();

// Get the pidx from the URL
$pidx = $_GET['pidx'] ?? null;

if ($pidx) {
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://a.khalti.com/api/v2/epayment/lookup/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode(['pidx' => $pidx]),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Key live_secret_key_68791341fdd94846a146f0457ff7b455',
            'Content-Type: application/json',
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    if ($response) {
        $responseArray = json_decode($response, true);
        
        // Include database connection
        require_once '../../config/db.php';
        
        switch ($responseArray['status']) {
            case 'Completed':
                // Update order status in database
                $order_id = $responseArray['purchase_order_id'];
                $stmt = $conn->prepare("UPDATE orders SET order_status = 'Paid' WHERE order_id = ?");
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                $stmt->close();
                
                // Clear cart
                unset($_SESSION['cart']);
                unset($_SESSION['total']);

                $_SESSION['transaction_msg'] = '<script>
                        Swal.fire({
                            icon: "success",
                            title: "Payment successful!",
                            text: "Your order has been placed.",
                            showConfirmButton: false,
                            timer: 3000
                        });
                    </script>';
                header("Location: ../../order-confirmation.php?order_id=$order_id");
                exit();
                break;
                
            default:
                // Failed or expired payment
                $_SESSION['transaction_msg'] = '<script>
                        Swal.fire({
                            icon: "error",
                            title: "Payment failed",
                            text: "Please try again.",
                            showConfirmButton: true
                        });
                    </script>';
                header("Location: ../../checkout.php");
                exit();
                break;
        }
    } else {
        $_SESSION['transaction_msg'] = '<script>
                Swal.fire({
                    icon: "error",
                    title: "Payment verification failed",
                    text: "Please contact support.",
                    showConfirmButton: true
                });
            </script>';
        header("Location: ../../checkout.php");
        exit();
    }
} else {
    header("Location: ../../checkout.php");
    exit();
}
?>