<?php
include('header.php');
require_once("server/connection.php");

// Get search query and price filter from the URL (if set)
$search_query = $_GET['search'] ?? '';
$min_price = $_GET['min_price'] ?? 0;
$max_price = $_GET['max_price'] ?? 10000; // Set an arbitrary high price limit for filtering

// Handle prescription deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_prescription_id'])) {
    if (isset($_SESSION['user_id'])) {
        $delete_id = intval($_POST['delete_prescription_id']);
        $user_id = $_SESSION['user_id'];

        // First, set prescription_id to NULL in cart for this prescription
        $stmt = $conn->prepare("UPDATE cart SET prescription_id = NULL WHERE prescription_id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();

        // Set prescription_id to NULL in orders for this prescription
        $stmt = $conn->prepare("UPDATE orders SET prescription_id = NULL WHERE prescription_id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();

        // Set prescription_id to NULL in prescription_orders for this prescription
        $stmt = $conn->prepare("UPDATE prescription_orders SET prescription_id = NULL WHERE prescription_id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();

        // Now, delete the prescription
        $stmt = $conn->prepare("DELETE FROM prescription_frames WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $delete_id, $user_id);
        $stmt->execute();
    }
}

// Get prescription frame products with filtering based on search and price
$query = "SELECT p.*, b.brand_name 
          FROM products p 
          JOIN brands b ON p.brand_id = b.id
          WHERE  
            p.prescription_required = 1
          AND p.price BETWEEN ? AND ?
          AND (p.name LIKE ? OR b.brand_name LIKE ?)";
$stmt = $conn->prepare($query);
$search_term = "%" . $search_query . "%"; // Use LIKE for search term
$stmt->bind_param("iiis", $min_price, $max_price, $search_term, $search_term);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);

// Get user's saved prescriptions if logged in
$prescriptions = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM prescription_frames WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<main class="container my-5">
    <div class="row">
        <div class="col-12">
            <div class="page-header mb-4">
                <h2 class="text-primary fw-bold">Prescription Frames</h2>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="mt-3">
                        <a href="customize_prescription.php" class="btn btn-success btn-lg">
                            <i class="bi bi-plus-circle-fill me-2"></i>Add New Prescription
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search Products</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by name or brand" value="<?= htmlspecialchars($search_query) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Min Price</label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="min_price" class="form-control" placeholder="0" value="<?= htmlspecialchars($min_price) ?>" min="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Max Price</label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="max_price" class="form-control" placeholder="10000" value="<?= htmlspecialchars($max_price) ?>" min="0">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel-fill me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Saved Prescriptions Section -->
    <?php if (!empty($prescriptions)): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0"><i class="bi bi-clipboard2-pulse me-2"></i>Your Saved Prescriptions</h5>
            </div>
            <div class="card-body">
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach ($prescriptions as $prescription): ?>
                        <div class="col">
                            <div class="card h-100 prescription-card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h6 class="card-title">
                                            <i class="bi bi-calendar3 me-2"></i>
                                            <?= date('M d, Y', strtotime($prescription['created_at'])) ?>
                                        </h6>
                                        <div class="btn-group">
                                            <a href="edit_prescription.php?prescription_id=<?= $prescription['id'] ?>"
                                                class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="tooltip" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="deletePrescription(<?= $prescription['id'] ?>)"
                                                data-bs-toggle="tooltip" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="prescription-details">
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <div class="eye-section">
                                                    <h6 class="text-primary mb-2">Right Eye</h6>
                                                    <div class="specs-info">
                                                        <p class="mb-1">SPH: <?= $prescription['right_eye_sphere'] ?></p>
                                                        <p class="mb-1">CYL: <?= $prescription['right_eye_cylinder'] ?></p>
                                                        <p class="mb-1">Axis: <?= $prescription['right_eye_axis'] ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="eye-section">
                                                    <h6 class="text-primary mb-2">Left Eye</h6>
                                                    <div class="specs-info">
                                                        <p class="mb-1">SPH: <?= $prescription['left_eye_sphere'] ?></p>
                                                        <p class="mb-1">CYL: <?= $prescription['left_eye_cylinder'] ?></p>
                                                        <p class="mb-1">Axis: <?= $prescription['left_eye_axis'] ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Products Grid -->
    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php foreach ($products as $product):
            $images = json_decode($product['images'], true);
            $main_image = $images[0] ?? 'default-product.jpg';
        ?>
            <div class="col">
                <div class="card h-100 product-card-custom">
                    <div class="position-relative overflow-hidden" style="height: 220px;">
                        <img src="<?= htmlspecialchars($main_image) ?>"
                            class="card-img-top product-image"
                            alt="<?= htmlspecialchars($product['name']) ?>">
                    </div>
                    <div class="card-body text-center">
                        <h5 class="card-title product-title-custom mb-2"><?= htmlspecialchars($product['name']) ?></h5>
                        <div class="mb-2 d-flex justify-content-center align-items-center gap-2">
                            <?php if ($product['discount_price'] > 0): ?>
                                <span class="old-price-custom">
                                    <span class="rupee-custom">₹</span> <?= number_format($product['price']) ?>
                                </span>
                                <span class="new-price-custom">
                                    <span class="rupee-custom">₹</span> <?= number_format($product['discount_price']) ?>
                                </span>
                            <?php else: ?>
                                <span class="new-price-custom">
                                    <span class="rupee-custom">₹</span> <?= number_format($product['price']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($product['discount_price'] > 0): ?>
                            <div class="mb-2">
                                <span class="badge save-badge-custom">
                                    SAVE <?= number_format($product['price'] - $product['discount_price']) ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <div class="d-grid gap-2 mt-3">
                            <a href="customize_prescription.php?product_id=<?= $product['id'] ?>"
                                class="btn btn-primary">
                                Customize with Prescription
                            </a>
                            <?php if (!empty($prescriptions)): ?>
                                <div class="dropdown">
                                    <button class="btn btn-outline-primary dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown">
                                        Use Saved Prescription
                                    </button>
                                    <ul class="dropdown-menu">
                                        <?php foreach ($prescriptions as $prescription): ?>
                                            <li>
                                                <a class="dropdown-item"
                                                    href="customize_prescription.php?product_id=<?= $product['id'] ?>&prescription_id=<?= $prescription['id'] ?>">
                                                    <?= date('M d, Y', strtotime($prescription['created_at'])) ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php include('footer.php'); ?>

<style>
    .product-image {
        width: 100%;
        transition: all .2s ease-in-out;
        height: 270px;
        object-fit: cover;
        background: #fff;
        border-radius: 10px 10px 0 0;
        padding: 10px;
    }

    .product-image:hover {
        transform: scale(1.05);
    }

    .product-card-custom {
        border-radius: 16px;
        border: none;
        margin-bottom: 10px;
        background: #fff;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .product-card-custom:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    .page-header {
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 1rem;
    }

    .prescription-card {
        transition: transform 0.2s ease-in-out;
        border-radius: 12px;
    }

    .prescription-card:hover {
        transform: translateY(-5px);
    }

    .specs-info {
        background-color: #f8f9fa;
        padding: 0.75rem;
        border-radius: 8px;
        font-size: 0.9rem;
    }

    .eye-section h6 {
        font-weight: 600;
    }

    .btn-group {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-radius: 6px;
        overflow: hidden;
    }

    .old-price-custom {
        color: #222;
        opacity: 0.7;
        font-size: 1.1rem;
        text-decoration: line-through;
        font-weight: 700;
        margin-right: 0.5rem;
        display: inline-flex;
        align-items: center;
    }

    .new-price-custom {
        color: #21b573;
        font-size: 1.25rem;
        font-weight: 700;
        margin-left: 0.5rem;
        display: inline-flex;
        align-items: center;
    }

    .rupee-custom {
        font-family: Arial, sans-serif;
        font-weight: 700;
        font-size: 1.1em;
        margin-right: 2px;
    }

    .save-badge-custom {
        background: #4caf50;
        color: #fff;
        font-weight: 700;
        border-radius: 20px;
        padding: 0.5em 1.2em;
        font-size: 1rem;
        letter-spacing: 1px;
        display: inline-block;
    }

    .text-success {
        color: #388e3c !important;
    }

    .text-decoration-line-through {
        color: #222 !important;
        opacity: 0.7;
    }

    mark {
        background-color: yellow;
        padding: 0;
    }

    .card {
        transition: box-shadow 0.3s ease;
    }

    @media (max-width: 991px) {
        .product-image {
            height: 180px;
        }
    }
</style>

<script>
    function deletePrescription(id) {
        if (confirm('Are you sure you want to delete this prescription?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="delete_prescription_id" value="${id}">`;
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Update the price range values dynamically when changed
    function updatePriceRange() {
        var range = document.getElementById('price_range');
        var min_value = document.getElementById('min_value');
        var max_value = document.getElementById('max_value');
        var min_price = document.getElementById('min_price');
        var max_price = document.getElementById('max_price');

        var price_value = range.value;
        min_value.textContent = "Rs " + price_value;
        min_price.value = price_value;
        max_value.textContent = "Rs " + (parseInt(price_value) + 10000);
        max_price.value = parseInt(price_value) + 10000;
    }

    // Initialize price range display
    document.addEventListener('DOMContentLoaded', function() {
        updatePriceRange();
    });
</script>