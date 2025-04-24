<?php
session_start();

// Check if order details exist
if (!isset($_SESSION['order_details'])) {
    header("Location: ../../checkout.php");
    exit();
}

$order = $_SESSION['order_details'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Biku Bike Rentals</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #dc3545; /* Red as primary color */
        }
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .btn-outline-secondary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-outline-secondary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        .table th {
            background-color: #f1f1f1;
        }
        h2, h4, h5 {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
<section class="my-5 py-5">
    <div class="container text-center mt-3 pt-5">
        <h2 class="font-weight-bold">Order Confirmed</h2>
        <hr class="mx-auto" style="border-top: 2px solid var(--primary-color); width: 100px;">
    </div>
    <div class="mx-auto container" style="max-width: 1200px;">
        <div class="card shadow">
            <div class="card-body">
                <div class="alert alert-success">
                    <h4><i class="fas fa-check-circle"></i> Payment Successful</h4>
                    <p>Thank you for your purchase! We've received your payment.</p>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h5>Order Summary</h5>
                        <table class="table">
                            <tr>
                                <th>Transaction ID:</th>
                                <td><?php echo $order['transaction_id']; ?></td>
                            </tr>
                            <tr>
                                <th>Date:</th>
                                <td>
                                    <?php 
                                    $formattedDate = strtotime($order['date']) ? date('F j, Y, g:i a', strtotime($order['date'])) : 'Invalid Date';
                                    echo $formattedDate; 
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Total Paid:</th>
                                <td>Rs. <?php echo number_format($order['amount'], 2); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Customer Information</h5>
                        <table class="table">
                            <tr>
                                <th>Name:</th>
                                <td><?php echo htmlspecialchars($order['customer']['name']); ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo htmlspecialchars($order['customer']['email']); ?></td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td><?php echo htmlspecialchars($order['customer']['phone']); ?></td>
                            </tr>
                            <tr>
                                <th>Address:</th>
                                <td><?php echo htmlspecialchars($order['customer']['address']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h5>Order Items</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['products'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo $item['product_quantity']; ?></td>
                                <td>Rs. <?php echo number_format($item['product_price'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="text-center mt-4">
                    <a href="../../index.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i> Continue Shopping
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-secondary ml-2">
                        <i class="fas fa-print"></i> Print Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php 
// Clear the order details after displaying
unset($_SESSION['order_details']);
?>