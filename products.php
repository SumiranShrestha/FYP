<?php
include('header.php');
include('server/connection.php'); // Database connection

// Get selected brand filter (Always as an array)
$selected_brands = isset($_GET['brand_id']) ? (array)$_GET['brand_id'] : [];

// Get selected price filter
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : '';
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : '';

// Get search query
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Start the base query
$query = "SELECT products.*, brands.brand_name FROM products 
          LEFT JOIN brands ON products.brand_id = brands.id 
          WHERE 1=1";  // Default true condition

// Add filters to the query
if (!empty($selected_brands) && !in_array('all', $selected_brands)) {
    $query .= " AND products.brand_id IN (" . implode(',', array_map('intval', $selected_brands)) . ")";
}

if (!empty($min_price)) {
    $query .= " AND products.price >= $min_price";
}

if (!empty($max_price)) {
    $query .= " AND products.price <= $max_price";
}

// Enhanced search with flexible pattern matching
if (!empty($search_query)) {
    // Split search terms by spaces
    $terms = explode(' ', $search_query);
    
    // Build a more flexible search condition
    $search_conditions = [];
    foreach ($terms as $term) {
        $term = trim($term);
        if (strlen($term) >= 2) {  // Only search for terms with at least 2 characters
            $term = mysqli_real_escape_string($conn, $term);
            $search_conditions[] = "(
                products.name LIKE '%$term%' OR 
                products.description LIKE '%$term%' OR
                brands.brand_name LIKE '%$term%'
            )";
        }
    }
    
    if (!empty($search_conditions)) {
        $query .= " AND (" . implode(' AND ', $search_conditions) . ")";
    }
}

// Execute the query
$result = mysqli_query($conn, $query);

// Get total number of products for display
$total_products = mysqli_num_rows($result);
?>

<!-- Main Section -->
<main class="container my-4">
    <h2 class="text-center mb-4">All Products <?= !empty($search_query) ? ' - Search Results' : '' ?></h2>
    
    <!-- Search and Filter Summary -->
    <?php if (!empty($search_query) || (!empty($selected_brands) && !in_array('all', $selected_brands)) || !empty($min_price) || !empty($max_price)): ?>
    <div class="alert alert-info mb-4">
        <?php if (!empty($search_query)): ?>
            <p><strong>Search:</strong> "<?= htmlspecialchars($search_query) ?>"</p>
        <?php endif; ?>
        <?php if (!empty($selected_brands) && !in_array('all', $selected_brands)): ?>
            <p><strong>Brands:</strong> 
                <?php 
                $brand_names = [];
                foreach ($selected_brands as $brand_id) {
                    // Make sure brand_id is a valid integer
                    $brand_id = intval($brand_id);
                    if ($brand_id > 0) {
                        $brand_result = mysqli_query($conn, "SELECT brand_name FROM brands WHERE id = $brand_id");
                        if ($brand_result && $brand = mysqli_fetch_assoc($brand_result)) {
                            $brand_names[] = $brand['brand_name'];
                        }
                    }
                }
                echo implode(', ', $brand_names);
                ?>
            </p>
        <?php endif; ?>
        <?php if (!empty($min_price) || !empty($max_price)): ?>
            <p><strong>Price Range:</strong> 
                Rs <?= !empty($min_price) ? number_format($min_price) : '0' ?> 
                - 
                Rs <?= !empty($max_price) ? number_format($max_price) : 'Any' ?>
            </p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Search Section (Top) -->
        <div class="col-md-12 mb-4">
            <form method="GET" class="d-flex justify-content-between">
                <?php if (!empty($selected_brands)): ?>
                    <?php foreach ($selected_brands as $brand_id): ?>
                        <input type="hidden" name="brand_id[]" value="<?= htmlspecialchars($brand_id) ?>">
                    <?php endforeach; ?>
                <?php endif; ?>
                <input type="hidden" name="min_price" value="<?= $min_price ?>">
                <input type="hidden" name="max_price" value="<?= $max_price ?>">
                <div class="form-group w-75">
                    <input type="text" name="search" class="form-control" placeholder="Search for products..." value="<?= htmlspecialchars($search_query) ?>">
                </div>
                <button type="submit" class="btn btn-primary w-25 ms-2">Search</button>
            </form>
        </div>

        <!-- Filter Section (Brand + Price) -->
        <div class="col-md-3">
            <form method="GET">
                <!-- Preserve search query in filters -->
                <?php if (!empty($search_query)): ?>
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                <?php endif; ?>
                
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Filter By Brand</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="brand_id[]" value="all" <?= empty($selected_brands) || in_array('all', $selected_brands) ? 'checked' : ''; ?>>
                            <label class="form-check-label">All Brands</label>
                        </div>
                        <?php
                        // Fetch all brands
                        $brandQuery = "SELECT * FROM brands";
                        $brandResult = mysqli_query($conn, $brandQuery);
                        while ($brand = mysqli_fetch_assoc($brandResult)) {
                            $brandID = $brand['id'];
                            $brandName = $brand['brand_name'];
                            $checked = in_array($brandID, $selected_brands) || empty($selected_brands) ? "checked" : "";
                            echo "<div class='form-check'>
                                    <input class='form-check-input' type='checkbox' name='brand_id[]' value='$brandID' $checked>
                                    <label class='form-check-label'>$brandName</label>
                                  </div>";
                        }
                        ?>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Filter By Price</h5>
                        <label for="price_range" class="form-label">Price Range</label>
                        <input type="range" id="price_range" class="form-range" min="0" max="10000" step="100" value="<?= $min_price ? $min_price : 0 ?>" onchange="updatePriceRange()">
                        <div class="d-flex justify-content-between">
                            <span id="min_value">Rs <?= number_format($min_price ? $min_price : 0) ?></span>
                            <span id="max_value">Rs <?= number_format($max_price ? $max_price : 10000) ?></span>
                        </div>
                        <input type="hidden" name="min_price" id="min_price" value="<?= $min_price ? $min_price : 0 ?>">
                        <input type="hidden" name="max_price" id="max_price" value="<?= $max_price ? $max_price : 10000 ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                <?php if (!empty($selected_brands) || !empty($min_price) || !empty($max_price) || !empty($search_query)): ?>
                    <a href="products.php" class="btn btn-outline-secondary w-100 mt-2">Reset All Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Products Grid -->
        <div class="col-md-9">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php while ($product = mysqli_fetch_assoc($result)): 
                        $images = json_decode($product['images'], true); // Convert JSON to array
                        
                        // Highlight search terms in product name if search was performed
                        $product_name = $product['name'];
                        if (!empty($search_query)) {
                            foreach ($terms as $term) {
                                if (strlen($term) >= 2) {
                                    $product_name = preg_replace("/(" . preg_quote($term, '/') . ")/i", "<mark>$0</mark>", $product_name);
                                }
                            }
                        }
                    ?>
                        <div class="col">
                            <div class="card h-100">
                                <a href="product-detail.php?id=<?= $product['id'] ?>">
                                    <img src="<?= $images[0] ?>" class="card-img-top product-image" alt="<?= htmlspecialchars($product['name']) ?>" />
                                </a>
                                <div class="card-body text-center">
                                    <h5 class="card-title"><?= $product_name ?></h5>
                                    <p class="text-muted"><?= htmlspecialchars($product['brand_name']) ?></p>
                                    <p class="card-text">
                                        <span class="text-muted text-decoration-line-through">Rs <?= number_format($product['price']) ?></span>
                                        <span class="ms-2 fw-bold text-success">Rs <?= number_format($product['discount_price']) ?></span>
                                    </p>
                                    <span class="badge bg-primary">SAVE Rs <?= number_format($product['price'] - $product['discount_price']) ?></span>
                                    <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-success mt-2">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning text-center">
                    <h4>No products found matching your criteria</h4>
                    <p>Try adjusting your search or filters</p>
                    <a href="products.php" class="btn btn-primary">Show All Products</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Image Hover Effect -->
<style>
    .product-image {
        transition: transform 0.3s ease-in-out;
        height: 200px;
        object-fit: contain;
    }
    .product-image:hover {
        transform: scale(1.05);
    }
    mark {
        background-color: yellow;
        padding: 0;
    }
    .card {
        transition: box-shadow 0.3s ease;
    }
    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
</style>

<script>
    // Update the price range values
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

<?php include('footer.php'); ?>