<?php
// Start session and output buffering to handle redirects properly
session_start();
ob_start();

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
    $save_prescription = isset($_POST['save_prescription']);

    // Handle multiple new prescriptions
    $multiple_prescriptions_json = $_POST['multiple_prescriptions_json'] ?? null;
    $selected_new_prescription_index = $_POST['selected_new_prescription_index'] ?? null;
    $new_prescription_data = null;
    if ($multiple_prescriptions_json && $selected_new_prescription_index !== '') {
        $presc_arr = json_decode($multiple_prescriptions_json, true);
        if (is_array($presc_arr) && isset($presc_arr[$selected_new_prescription_index])) {
            $new_prescription_data = $presc_arr[$selected_new_prescription_index];
        }
    }

    // If using a saved prescription
    if ($prescription_id) {
        if ($product_id) {
            // Add to cart with prescription_id
            $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                // Update existing cart item with prescription
                $cart_id = $row['id'];
                $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + 1, prescription_id = ? WHERE id = ?");
                $stmt->bind_param("ii", $prescription_id, $cart_id);
                $stmt->execute();
            } else {
                // Insert new cart item with prescription
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, prescription_id) VALUES (?, ?, 1, ?)");
                $stmt->bind_param("iii", $user_id, $product_id, $prescription_id);
                $stmt->execute();
            }
            header("Location: checkout.php?prescription_id=$prescription_id&product_id=$product_id");
            exit();
        } else {
            // No product, just save prescription and redirect or show message
            header("Location: prescription-frames.php?msg=prescription_saved");
            exit();
        }
    }

    // If using a new prescription from the multiple list
    if ($new_prescription_data) {
        // Save the prescription first
        $stmt = $conn->prepare("INSERT INTO prescription_frames 
            (user_id, right_eye_sphere, right_eye_cylinder, right_eye_axis, left_eye_sphere, left_eye_cylinder, left_eye_axis) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "issssss",
            $user_id,
            $new_prescription_data['right_eye_sphere'],
            $new_prescription_data['right_eye_cylinder'],
            $new_prescription_data['right_eye_axis'],
            $new_prescription_data['left_eye_sphere'],
            $new_prescription_data['left_eye_cylinder'],
            $new_prescription_data['left_eye_axis']
        );
        $stmt->execute();
        $prescription_id = $conn->insert_id;

        if ($product_id) {
            // Add to cart with new prescription_id
            $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $cart_id = $row['id'];
                $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + 1, prescription_id = ? WHERE id = ?");
                $stmt->bind_param("ii", $prescription_id, $cart_id);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, prescription_id) VALUES (?, ?, 1, ?)");
                $stmt->bind_param("iii", $user_id, $product_id, $prescription_id);
                $stmt->execute();
            }
            header("Location: checkout.php?prescription_id=$prescription_id&product_id=$product_id");
            exit();
        } else {
            // No product, just save prescription and redirect or show message
            header("Location: prescription-frames.php?msg=prescription_saved");
            exit();
        }
    }

    // If filling out a single new prescription
    if (
        $right_eye_sphere !== null && $right_eye_cylinder !== null && $right_eye_axis !== null &&
        $left_eye_sphere !== null && $left_eye_cylinder !== null && $left_eye_axis !== null &&
        $right_eye_sphere !== '' && $right_eye_cylinder !== '' && $right_eye_axis !== '' &&
        $left_eye_sphere !== '' && $left_eye_cylinder !== '' && $left_eye_axis !== ''
    ) {
        // Save the prescription first
        $stmt = $conn->prepare("INSERT INTO prescription_frames 
            (user_id, right_eye_sphere, right_eye_cylinder, right_eye_axis, left_eye_sphere, left_eye_cylinder, left_eye_axis) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "issssss",
            $user_id,
            $right_eye_sphere,
            $right_eye_cylinder,
            $right_eye_axis,
            $left_eye_sphere,
            $left_eye_cylinder,
            $left_eye_axis
        );
        $stmt->execute();
        $prescription_id = $conn->insert_id;

        if ($product_id) {
            // Add to cart with new prescription_id
            $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $cart_id = $row['id'];
                $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + 1, prescription_id = ? WHERE id = ?");
                $stmt->bind_param("ii", $prescription_id, $cart_id);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, prescription_id) VALUES (?, ?, 1, ?)");
                $stmt->bind_param("iii", $user_id, $product_id, $prescription_id);
                $stmt->execute();
            }
            header("Location: checkout.php?prescription_id=$prescription_id&product_id=$product_id");
            exit();
        } else {
            // No product, just save prescription and redirect or show message
            header("Location: prescription-frames.php?msg=prescription_saved");
            exit();
        }
    } else if (!$prescription_id && !$new_prescription_data) {
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
    <style>
        .prescription-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }

        .prescription-card h5 {
            color: #0d6efd;
        }

        .prescription-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .eye-section {
            margin-bottom: 10px;
        }

        .eye-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .loading-spinner {
            display: none;
        }
    </style>
</head>

<body>
    <div class="container my-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0">
                    <?php
                    if (!empty($product) && isset($product['name'])) {
                        echo htmlspecialchars($product['name']) . " Order";
                    } else {
                        echo "Add Prescription";
                    }
                    ?>
                </h2>
            </div>
            <div class="card-body">
                <h5 class="mb-4">
                    <?php
                    if (!empty($product) && isset($product['name'])) {
                        echo "Customize your prescription details for the selected frame";
                    } else {
                        echo "Add your prescription details below";
                    }
                    ?>
                </h5>

                <form id="prescriptionForm" action="" method="POST">
                    <input type="hidden" name="product_id" value="<?= htmlspecialchars($product_id ?? '') ?>">

                    <!-- Option to select a saved prescription -->
                    <?php if (!empty($prescriptions)): ?>
                        <div class="mb-4">
                            <h5>Your Saved Prescriptions</h5>
                            <div class="row" id="savedPrescriptionsContainer">
                                <?php foreach ($prescriptions as $prescription): ?>
                                    <div class="col-md-6">
                                        <div class="prescription-card">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="prescription_id"
                                                    id="prescription_<?= $prescription['id'] ?>"
                                                    value="<?= $prescription['id'] ?>">
                                                <label class="form-check-label" for="prescription_<?= $prescription['id'] ?>">
                                                    <h5>Prescription from <?= date('M d, Y', strtotime($prescription['created_at'])) ?></h5>
                                                </label>
                                            </div>
                                            <div class="prescription-details mt-2">
                                                <div class="eye-section">
                                                    <div class="eye-title">Right Eye</div>
                                                    <div>SPH: <?= $prescription['right_eye_sphere'] ?></div>
                                                    <div>CYL: <?= $prescription['right_eye_cylinder'] ?></div>
                                                    <div>Axis: <?= $prescription['right_eye_axis'] ?></div>
                                                </div>
                                                <div class="eye-section">
                                                    <div class="eye-title">Left Eye</div>
                                                    <div>SPH: <?= $prescription['left_eye_sphere'] ?></div>
                                                    <div>CYL: <?= $prescription['left_eye_cylinder'] ?></div>
                                                    <div>Axis: <?= $prescription['left_eye_axis'] ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-center my-3">
                                <p class="text-muted">OR</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Always show prescription form if no product, or if product allows prescription
                    $show_prescription_form = false;
                    $frame_types_available = $product['frame_types_available'] ?? null;
                    if (!$product_id) {
                        $show_prescription_form = true;
                    } elseif ($frame_types_available === 'prescription' || $frame_types_available === 'both') {
                        $show_prescription_form = true;
                    }
                    ?>
                    <?php if ($show_prescription_form): ?>
                        <!-- Prescription Frame Fields -->
                        <h5 class="mb-3">Enter New Prescription Details</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-center">Right Eye</h6>
                                <div class="mb-3">
                                    <label for="right_eye_sphere" class="form-label">SPH (Sphere)</label>
                                    <input type="text" class="form-control" id="right_eye_sphere" name="right_eye_sphere">
                                </div>
                                <div class="mb-3">
                                    <label for="right_eye_cylinder" class="form-label">CYL (Cylinder)</label>
                                    <input type="text" class="form-control" id="right_eye_cylinder" name="right_eye_cylinder">
                                </div>
                                <div class="mb-3">
                                    <label for="right_eye_axis" class="form-label">Axis</label>
                                    <input type="text" class="form-control" id="right_eye_axis" name="right_eye_axis">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-center">Left Eye</h6>
                                <div class="mb-3">
                                    <label for="left_eye_sphere" class="form-label">SPH (Sphere)</label>
                                    <input type="text" class="form-control" id="left_eye_sphere" name="left_eye_sphere">
                                </div>
                                <div class="mb-3">
                                    <label for="left_eye_cylinder" class="form-label">CYL (Cylinder)</label>
                                    <input type="text" class="form-control" id="left_eye_cylinder" name="left_eye_cylinder">
                                </div>
                                <div class="mb-3">
                                    <label for="left_eye_axis" class="form-label">Axis</label>
                                    <input type="text" class="form-control" id="left_eye_axis" name="left_eye_axis">
                                </div>
                            </div>
                        </div>

                        <!-- Option to save prescription for future use -->
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="save_prescription" name="save_prescription">
                            <label class="form-check-label" for="save_prescription">Save this prescription for future orders</label>
                        </div>

                        <!-- Add/Manage Multiple Prescriptions -->
                        <div class="mb-4">
                            <button type="button" class="btn btn-outline-primary" id="addPrescriptionBtn">Add Prescription</button>
                        </div>
                        <div id="newPrescriptionsList" class="mb-4"></div>
                        <input type="hidden" name="multiple_prescriptions_json" id="multiple_prescriptions_json">
                        <input type="hidden" name="selected_new_prescription_index" id="selected_new_prescription_index">
                    <?php else: ?>
                        <!-- Normal Frame Purchase -->
                        <p>This frame doesn't require a prescription. You can proceed with a regular order.</p>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="prescription-frames.php" class="btn btn-outline-secondary">Back to Product</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span class="loading-spinner spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            <span class="btn-text">
                                <?php
                                if (!$product_id) {
                                    echo "Save Prescription";
                                } else {
                                    echo ($frame_types_available === 'prescription' ? 'Submit Prescription' : 'Order Now');
                                }
                                ?>
                            </span>
                        </button>
                    </div>
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

    <!-- Toast for cart success -->
    <div class="toast align-items-center border-0 bg-success text-white" id="cartSuccessToast" role="alert" aria-live="assertive" aria-atomic="true" style="display:none; position: fixed; bottom: 30px; right: 30px; z-index: 9999; min-width: 280px;">
        <div class="d-flex">
            <div class="toast-body">
                Item added to cart successfully!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>

    <!-- Toast for cart error -->
    <div class="toast align-items-center border-0 bg-danger text-white" id="cartErrorToast" role="alert" aria-live="assertive" aria-atomic="true" style="display:none; position: fixed; bottom: 30px; right: 30px; z-index: 9999; min-width: 280px;">
        <div class="d-flex">
            <div class="toast-body" id="cartErrorMessage">
                Failed to add item to cart.
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

        // Output product data
        const productData = <?= json_encode($product) ?>;
        const productId = <?= json_encode($product_id) ?>;

        // Function to add item to cart via AJAX
        function addToCart(productId, quantity = 1) {
            return new Promise((resolve, reject) => {
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('quantity', quantity);

                fetch('server/add_to_cart.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            resolve(data);
                        } else {
                            reject(data);
                        }
                    })
                    .catch(error => {
                        reject({
                            status: 'error',
                            message: 'Network error occurred'
                        });
                    });
            });
        }

        // Function to show toast messages
        function showToast(toastId, message = null) {
            const toastEl = document.getElementById(toastId);
            if (message && toastId === 'cartErrorToast') {
                document.getElementById('cartErrorMessage').textContent = message;
            }
            toastEl.style.display = 'block';
            const toast = new bootstrap.Toast(toastEl, {
                delay: 3000
            });
            toast.show();
        }

        // Function to toggle loading state
        function toggleLoading(isLoading) {
            const submitBtn = document.getElementById('submitBtn');
            const spinner = submitBtn.querySelector('.loading-spinner');
            const btnText = submitBtn.querySelector('.btn-text');

            if (isLoading) {
                spinner.style.display = 'inline-block';
                submitBtn.disabled = true;
            } else {
                spinner.style.display = 'none';
                submitBtn.disabled = false;
            }
        }

        // --- Multiple prescription JS ---
        let newPrescriptions = [];
        let selectedNewPrescriptionIndex = null;

        function renderNewPrescriptions() {
            const listDiv = document.getElementById('newPrescriptionsList');
            listDiv.innerHTML = '';
            newPrescriptions.forEach((presc, idx) => {
                const card = document.createElement('div');
                card.className = 'prescription-card mb-2';
                card.innerHTML = `
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="prescription_id" id="new_prescription_${idx}" value="" ${selectedNewPrescriptionIndex == idx ? 'checked' : ''} onclick="selectNewPrescription(${idx})">
                        <label class="form-check-label" for="new_prescription_${idx}">
                            <h5>New Prescription #${idx + 1}</h5>
                        </label>
                        <button type="button" class="btn btn-sm btn-danger float-end" onclick="removeNewPrescription(${idx})">Remove</button>
                    </div>
                    <div class="prescription-details mt-2">
                        <div class="eye-section">
                            <div class="eye-title">Right Eye</div>
                            <div>SPH: ${presc.right_eye_sphere}</div>
                            <div>CYL: ${presc.right_eye_cylinder}</div>
                            <div>Axis: ${presc.right_eye_axis}</div>
                        </div>
                        <div class="eye-section">
                            <div class="eye-title">Left Eye</div>
                            <div>SPH: ${presc.left_eye_sphere}</div>
                            <div>CYL: ${presc.left_eye_cylinder}</div>
                            <div>Axis: ${presc.left_eye_axis}</div>
                        </div>
                    </div>
                `;
                listDiv.appendChild(card);
            });
            document.getElementById('multiple_prescriptions_json').value = JSON.stringify(newPrescriptions);
            document.getElementById('selected_new_prescription_index').value = selectedNewPrescriptionIndex !== null ? selectedNewPrescriptionIndex : '';
        }

        function selectNewPrescription(idx) {
            selectedNewPrescriptionIndex = idx;
            renderNewPrescriptions();
            // Unselect saved prescription radios
            document.querySelectorAll('input[name="prescription_id"]').forEach(radio => {
                if (radio.id.startsWith('prescription_')) radio.checked = false;
            });
        }

        function removeNewPrescription(idx) {
            newPrescriptions.splice(idx, 1);
            if (selectedNewPrescriptionIndex == idx) selectedNewPrescriptionIndex = null;
            else if (selectedNewPrescriptionIndex > idx) selectedNewPrescriptionIndex--;
            renderNewPrescriptions();
        }

        document.addEventListener("DOMContentLoaded", function() {
            var form = document.getElementById('prescriptionForm');

            // Auto-fill form when a saved prescription is selected
            var prescriptionRadios = document.querySelectorAll('input[name="prescription_id"]');
            prescriptionRadios.forEach(function(radio) {
                radio.addEventListener('change', function() {
                    if (this.checked && this.id.startsWith('prescription_')) {
                        var selectedId = this.value;
                        if (prescriptionsData[selectedId]) {
                            document.getElementById('right_eye_sphere').value = prescriptionsData[selectedId]['right_eye_sphere'] || '';
                            document.getElementById('right_eye_cylinder').value = prescriptionsData[selectedId]['right_eye_cylinder'] || '';
                            document.getElementById('right_eye_axis').value = prescriptionsData[selectedId]['right_eye_axis'] || '';
                            document.getElementById('left_eye_sphere').value = prescriptionsData[selectedId]['left_eye_sphere'] || '';
                            document.getElementById('left_eye_cylinder').value = prescriptionsData[selectedId]['left_eye_cylinder'] || '';
                            document.getElementById('left_eye_axis').value = prescriptionsData[selectedId]['left_eye_axis'] || '';
                        }
                        // Unselect new prescription radios
                        selectedNewPrescriptionIndex = null;
                        renderNewPrescriptions();
                    }
                });
            });

            // Clear saved prescription selection when editing fields manually
            var prescriptionFields = [
                'right_eye_sphere', 'right_eye_cylinder', 'right_eye_axis',
                'left_eye_sphere', 'left_eye_cylinder', 'left_eye_axis'
            ];
            prescriptionFields.forEach(function(fieldId) {
                document.getElementById(fieldId).addEventListener('input', function() {
                    var selectedRadio = document.querySelector('input[name="prescription_id"]:checked');
                    if (selectedRadio && selectedRadio.id.startsWith('prescription_')) {
                        selectedRadio.checked = false;
                    }
                    selectedNewPrescriptionIndex = null;
                    renderNewPrescriptions();
                });
            });

            // Add new prescription button
            var addBtn = document.getElementById('addPrescriptionBtn');
            if (addBtn) {
                addBtn.addEventListener('click', function() {
                    // Validate fields
                    var right_eye_sphere = document.getElementById('right_eye_sphere').value.trim();
                    var right_eye_cylinder = document.getElementById('right_eye_cylinder').value.trim();
                    var right_eye_axis = document.getElementById('right_eye_axis').value.trim();
                    var left_eye_sphere = document.getElementById('left_eye_sphere').value.trim();
                    var left_eye_cylinder = document.getElementById('left_eye_cylinder').value.trim();
                    var left_eye_axis = document.getElementById('left_eye_axis').value.trim();
                    if (!right_eye_sphere || !right_eye_cylinder || !right_eye_axis || !left_eye_sphere || !left_eye_cylinder || !left_eye_axis) {
                        alert('Please fill in all prescription details before adding.');
                        return;
                    }
                    newPrescriptions.push({
                        right_eye_sphere,
                        right_eye_cylinder,
                        right_eye_axis,
                        left_eye_sphere,
                        left_eye_cylinder,
                        left_eye_axis,
                        save_prescription: document.getElementById('save_prescription').checked ? 1 : 0
                    });
                    selectedNewPrescriptionIndex = newPrescriptions.length - 1;
                    renderNewPrescriptions();
                    // Clear fields
                    prescriptionFields.forEach(fid => document.getElementById(fid).value = '');
                    document.getElementById('save_prescription').checked = false;
                });
            }

            renderNewPrescriptions();

            form.addEventListener('submit', function(e) {
                <?php if (!isset($_SESSION['user_id'])): ?>
                    e.preventDefault();
                    showToast('orderToast');
                <?php else: ?>
                    console.log('Product Data:', productData);
                    console.log('Product ID:', productId);
                    console.log('Frame Types Available:', productData?.frame_types_available);
                    // Check if this is a normal frame order (no prescription required)
                    const frameTypesAvailable = productData?.frame_types_available;
                    const isNormalFrameOrder = productId && (frameTypesAvailable === 'normal' || !frameTypesAvailable);

                    // Also check if no prescription data is being submitted
                    const hasSelectedPrescription = document.querySelector('input[name="prescription_id"]:checked');
                    const hasNewPrescriptionData = selectedNewPrescriptionIndex !== null;
                    const hasManualPrescriptionData = document.getElementById('right_eye_sphere')?.value.trim() &&
                        document.getElementById('left_eye_sphere')?.value.trim();

                    const shouldAddToCartOnly = isNormalFrameOrder || (productId && !hasSelectedPrescription && !hasNewPrescriptionData && !hasManualPrescriptionData);

                    if (shouldAddToCartOnly) {
                        e.preventDefault();
                        toggleLoading(true);

                        // Add to cart first, then redirect
                        addToCart(productId, 1)
                            .then(response => {
                                showToast('cartSuccessToast');
                                // Redirect to checkout after a short delay
                                setTimeout(() => {
                                    window.location.href = `checkout.php?product_id=${productId}`;
                                }, 1000);
                            })
                            .catch(error => {
                                toggleLoading(false);
                                showToast('cartErrorToast', error.message || 'Failed to add item to cart');
                            });
                        return false;
                    }

                    // For prescription frames, continue with normal form submission
                    // If user has added new prescriptions, ensure one is selected
                    if (newPrescriptions.length > 0) {
                        if (selectedNewPrescriptionIndex === null) {
                            e.preventDefault();
                            alert('Please select one of your added prescriptions to submit.');
                            return false;
                        }
                        // Unselect saved prescription radios so only new prescription is submitted
                        document.querySelectorAll('input[name="prescription_id"]').forEach(radio => radio.checked = false);
                    }
                <?php endif; ?>
            });
        });
    </script>
    <script>
        // Expose select/remove for inline event handlers
        window.selectNewPrescription = selectNewPrescription;
        window.removeNewPrescription = removeNewPrescription;
    </script>
</body>

</html>

<?php include('footer.php'); ?>

<?php
// End output buffering and flush it to the browser
ob_end_flush();
?>