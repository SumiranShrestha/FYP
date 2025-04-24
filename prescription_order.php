<?php
session_start();

// Start output buffering to avoid headers already sent issue
ob_start();

include('header.php');
require_once("server/connection.php");

// Check if the product_id and prescription_id are set in the URL
$product_id = $_GET['product_id'] ?? null;
$prescription_id = $_GET['prescription_id'] ?? null;

// If either product_id or prescription_id is not set, redirect the user
if (!$product_id || !$prescription_id) {
    header("Location: cart.php");
    exit();
}

// Fetch the product details
$stmt = $conn->prepare("SELECT p.*, b.brand_name FROM products p 
                        JOIN brands b ON p.brand_id = b.id WHERE p.id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

// Check if product exists
if (!$product) {
    header("Location: cart.php");
    exit(); // Stop further execution if product doesn't exist
}

// Fetch the prescription details
$stmt = $conn->prepare("SELECT * FROM prescription_frames WHERE id = ?");
$stmt->bind_param("i", $prescription_id);
$stmt->execute();
$prescription = $stmt->get_result()->fetch_assoc();

// Check if prescription exists
if (!$prescription) {
    header("Location: cart.php");
    exit(); // Stop further execution if prescription doesn't exist
}

?>

<main class="container my-5">
    <h2 class="text-center mb-4">Order Confirmation</h2>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5>Order Details</h5>
        </div>
        <div class="card-body">
            <h6>Product:</h6>
            <p><?= htmlspecialchars($product['name']) ?> (<?= htmlspecialchars($product['brand_name']) ?>)</p>
            <p><strong>Price:</strong> Rs <?= number_format($product['price']) ?></p>

            <h6>Prescription:</h6>
            <p><strong>Right Eye:</strong> SPH: <?= htmlspecialchars($prescription['right_eye_sphere'] ?? 'N/A') ?>, CYL: <?= htmlspecialchars($prescription['right_eye_cylinder'] ?? 'N/A') ?>, Axis: <?= htmlspecialchars($prescription['right_eye_axis'] ?? 'N/A') ?></p>
            <p><strong>Left Eye:</strong> SPH: <?= htmlspecialchars($prescription['left_eye_sphere'] ?? 'N/A') ?>, CYL: <?= htmlspecialchars($prescription['left_eye_cylinder'] ?? 'N/A') ?>, Axis: <?= htmlspecialchars($prescription['left_eye_axis'] ?? 'N/A') ?></p>

            <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                <a href="place_order.php?prescription_id=<?= $prescription_id ?>&product_id=<?= $product_id ?>" class="btn btn-primary me-md-2">Place Order</a>
            </div>
        </div>
    </div>
</main>

<?php include('footer.php'); ?>

<?php
// End output buffering and flush it to the browser
ob_end_flush();
?>
