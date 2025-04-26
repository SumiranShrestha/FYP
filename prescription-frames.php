<?php
include('header.php');
require_once("server/connection.php");

// Get search query and price filter from the URL (if set)
$search_query = $_GET['search'] ?? '';
$min_price = $_GET['min_price'] ?? 0;
$max_price = $_GET['max_price'] ?? 10000; // Set an arbitrary high price limit for filtering

// Get prescription frame products with filtering based on search and price
$query = "SELECT p.*, b.brand_name 
          FROM products p 
          JOIN brands b ON p.brand_id = b.id
          WHERE p.category_id = 4 
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
    <h2 class="text-center mb-4">Prescription Frames</h2>

    <!-- Search and Price Filter Form -->
    <form class="mb-4" method="GET" action="">
        <div class="row">
            <div class="col-md-4 mb-3">
                <input type="text" name="search" class="form-control" placeholder="Search by name or brand" value="<?= htmlspecialchars($search_query) ?>">
            </div>
            <div class="col-md-3 mb-3">
                <input type="number" name="min_price" class="form-control" placeholder="Min Price" value="<?= htmlspecialchars($min_price) ?>" min="0">
            </div>
            <div class="col-md-3 mb-3">
                <input type="number" name="max_price" class="form-control" placeholder="Max Price" value="<?= htmlspecialchars($max_price) ?>" min="0">
            </div>
            <div class="col-md-2 mb-3">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </div>
    </form>

    <!-- Saved Prescriptions -->
    <?php if (!empty($prescriptions)): ?>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5>Your Saved Prescriptions</h5>
        </div>
        <div class="card-body">
            <div class="row row-cols-1 row-cols-md-3 g-3">
                <?php foreach ($prescriptions as $prescription): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title">Prescription from <?= date('M d, Y', strtotime($prescription['created_at'])) ?></h6>
                            <div class="row small">
                                <div class="col-6">
                                    <p class="mb-1"><strong>Right Eye:</strong></p>
                                    <p>
                                        SPH: <?= $prescription['right_eye_sphere'] ?><br>
                                        CYL: <?= $prescription['right_eye_cylinder'] ?><br>
                                        Axis: <?= $prescription['right_eye_axis'] ?>
                                    </p>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1"><strong>Left Eye:</strong></p>
                                    <p>
                                        SPH: <?= $prescription['left_eye_sphere'] ?><br>
                                        CYL: <?= $prescription['left_eye_cylinder'] ?><br>
                                        Axis: <?= $prescription['left_eye_axis'] ?>
                                    </p>
                                </div>
                            </div>
                            <div class="d-grid mt-2">
                                <a href="prescription_order.php?prescription_id=<?= $prescription['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                   Use This Prescription
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
                                           href="prescription_order.php?product_id=<?= $product['id'] ?>&prescription_id=<?= $prescription['id'] ?>">
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
    }
    .product-card-custom .card-body {
        padding: 1.2rem 1rem 1.5rem 1rem;
    }
    .card-title {
        font-weight: 700;
        color: #444;
        min-height: 48px;
        margin-bottom: 0.5rem;
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
