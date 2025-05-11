<?php
// Start session and output buffering to handle redirects properly
session_start();
ob_start(); // Start output buffering to prevent header errors

include('header.php');
require_once("server/connection.php");

// Get the product_id from the URL
$product_id = $_GET['product_id'] ?? null;

// Check if the product exists and get details
$product = [];
if ($product_id) {
    $stmt = $conn->prepare("SELECT p.*, b.brand_name FROM products p
                            JOIN brands b ON p.brand_id = b.id WHERE p.id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
}

// Get saved prescriptions for the logged-in user
$prescriptions = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM prescription_frames WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Handle form submission for prescription frames
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Collect prescription data from the form
    $right_eye_sphere = $_POST['right_eye_sphere'] ?? null;
    $right_eye_cylinder = $_POST['right_eye_cylinder'] ?? null;
    $right_eye_axis = $_POST['right_eye_axis'] ?? null;
    $left_eye_sphere = $_POST['left_eye_sphere'] ?? null;
    $left_eye_cylinder = $_POST['left_eye_cylinder'] ?? null;
    $left_eye_axis = $_POST['left_eye_axis'] ?? null;
    $prescription_id = $_POST['prescription_id'] ?? null;

    // If using a saved prescription
    if ($prescription_id) {
        // Redirect to the prescription order page
        header("Location: prescription_order.php?prescription_id=$prescription_id&product_id=$product_id");
        exit();
    } 

    // If filling out a new prescription
    if ($right_eye_sphere && $right_eye_cylinder && $right_eye_axis && 
        $left_eye_sphere && $left_eye_cylinder && $left_eye_axis) {
        
        // Check if the product requires a prescription
        if ($product['prescription_required']) {
            // Insert prescription into the prescription_frames table (shared table for both types)
            $stmt = $conn->prepare("INSERT INTO prescription_frames 
                                    (user_id, product_id, right_eye_sphere, right_eye_cylinder, 
                                     right_eye_axis, left_eye_sphere, left_eye_cylinder, left_eye_axis) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiisssss", $user_id, $product_id, $right_eye_sphere, $right_eye_cylinder, 
                              $right_eye_axis, $left_eye_sphere, $left_eye_cylinder, $left_eye_axis);
            $stmt->execute();

            // Redirect to the prescription order page
            header("Location: prescription_order.php?prescription_id=" . $conn->insert_id . "&product_id=" . $product_id);
            exit();
        }
    } else {
        // If any required field is missing, display an error message
        echo '<div class="alert alert-danger">Please fill in all prescription details.</div>';
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
    <div class="container my-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0"><?= htmlspecialchars($product['name']) ?> Order</h2>
            </div>
            <div class="card-body">
                <h5 class="mb-4">Customize your prescription details for the selected frame</h5>

                <form id="prescriptionForm" action="" method="POST">
                    <input type="hidden" name="product_id" value="<?= htmlspecialchars($product_id) ?>">

                    <!-- Option to select a saved prescription -->
                    <?php if (!empty($prescriptions)): ?>
                        <div class="mb-3">
                            <label for="prescription_id" class="form-label">Choose Your Saved Prescription</label>
                            <select class="form-select" name="prescription_id" id="prescription_id" required>
                                <option value="">Select a Prescription</option>
                                <?php foreach ($prescriptions as $prescription): ?>
                                    <option value="<?= $prescription['id'] ?>">
                                        Prescription from <?= date('M d, Y', strtotime($prescription['created_at'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php if ($product['frame_types_available'] == 'prescription' || $product['frame_types_available'] == 'both'): ?>
                    <!-- Prescription Frame Fields -->
                    <div class="mb-3">
                        <label for="right_eye_sphere" class="form-label">Right Eye - SPH</label>
                        <input type="text" class="form-control" id="right_eye_sphere" name="right_eye_sphere" required>
                    </div>
                    <div class="mb-3">
                        <label for="right_eye_cylinder" class="form-label">Right Eye - CYL</label>
                        <input type="text" class="form-control" id="right_eye_cylinder" name="right_eye_cylinder" required>
                    </div>
                    <div class="mb-3">
                        <label for="right_eye_axis" class="form-label">Right Eye - Axis</label>
                        <input type="text" class="form-control" id="right_eye_axis" name="right_eye_axis" required>
                    </div>
                    <div class="mb-3">
                        <label for="left_eye_sphere" class="form-label">Left Eye - SPH</label>
                        <input type="text" class="form-control" id="left_eye_sphere" name="left_eye_sphere" required>
                    </div>
                    <div class="mb-3">
                        <label for="left_eye_cylinder" class="form-label">Left Eye - CYL</label>
                        <input type="text" class="form-control" id="left_eye_cylinder" name="left_eye_cylinder" required>
                    </div>
                    <div class="mb-3">
                        <label for="left_eye_axis" class="form-label">Left Eye - Axis</label>
                        <input type="text" class="form-control" id="left_eye_axis" name="left_eye_axis" required>
                    </div>
                    <?php else: ?>
                    <!-- Normal Frame Purchase -->
                    <p>This frame doesn't require a prescription. You can proceed with a regular order.</p>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary"><?= $product['frame_types_available'] == 'prescription' ? 'Submit Prescription' : 'Order Now' ?></button>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast for not logged in -->
    <div class="toast align-items-center border-0 bg-danger text-white" id="orderToast" role="alert" aria-live="assertive" aria-atomic="true" style="display:none; position: fixed; bottom: 30px; right: 30px; z-index: 9999; min-width: 280px;">
      <div class="d-flex">
        <div class="toast-body">
          You must login to order.
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Output prescriptions as JS object
    <?php
    if (!empty($prescriptions)) {
        $prescriptionsById = [];
        foreach ($prescriptions as $p) {
            $prescriptionsById[$p['id']] = $p;
        }
        echo "const prescriptionsData = " . json_encode($prescriptionsById) . ";";
    } else {
        echo "const prescriptionsData = {};";
    }
    ?>
    document.addEventListener("DOMContentLoaded", function() {
        var form = document.getElementById('prescriptionForm');
        // Populate fields when a saved prescription is selected
        var prescriptionSelect = document.getElementById('prescription_id');
        if (prescriptionSelect) {
            prescriptionSelect.addEventListener('change', function() {
                var selectedId = this.value;
                if (prescriptionsData[selectedId]) {
                    document.getElementById('right_eye_sphere').value = prescriptionsData[selectedId]['right_eye_sphere'] || '';
                    document.getElementById('right_eye_cylinder').value = prescriptionsData[selectedId]['right_eye_cylinder'] || '';
                    document.getElementById('right_eye_axis').value = prescriptionsData[selectedId]['right_eye_axis'] || '';
                    document.getElementById('left_eye_sphere').value = prescriptionsData[selectedId]['left_eye_sphere'] || '';
                    document.getElementById('left_eye_cylinder').value = prescriptionsData[selectedId]['left_eye_cylinder'] || '';
                    document.getElementById('left_eye_axis').value = prescriptionsData[selectedId]['left_eye_axis'] || '';
                } else {
                    document.getElementById('right_eye_sphere').value = '';
                    document.getElementById('right_eye_cylinder').value = '';
                    document.getElementById('right_eye_axis').value = '';
                    document.getElementById('left_eye_sphere').value = '';
                    document.getElementById('left_eye_cylinder').value = '';
                    document.getElementById('left_eye_axis').value = '';
                }
            });
        }
        form.addEventListener('submit', function(e) {
            <?php if (!isset($_SESSION['user_id'])): ?>
                e.preventDefault();
                var toastEl = document.getElementById('orderToast');
                toastEl.style.display = 'block';
                var toast = new bootstrap.Toast(toastEl, { delay: 1800 });
                toast.show();
                setTimeout(function() {
                    toast.hide();
                }, 1800);
            <?php endif; ?>
        });
    });
    </script>
</body>
</html>

<?php include('footer.php'); ?>

<?php
// End output buffering and flush it to the browser
ob_end_flush();
?>
