<?php
include('header.php');
include('server/connection.php'); // Database connection

// Get selected brand filter (Always as an array)
$selected_brands = isset($_GET['brand_id']) ? (array)$_GET['brand_id'] : [];

// Get selected facial structure filter
$selected_face_shapes = isset($_GET['face_shape']) ? (array)$_GET['face_shape'] : [];

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

// Since facial_structure column doesn't exist, we'll filter based on product name/description
if (!empty($selected_face_shapes) && !in_array('all', $selected_face_shapes)) {
    $face_shape_conditions = [];

    // Define which face shapes match which product characteristics
    $face_shape_mappings = [
        'round' => ['round', 'circle', 'circular'],
        'oval' => ['oval', 'ellipse'],
        'square' => ['square', 'angular', 'rectangular'],
        'heart' => ['heart', 'cat-eye', 'aviator'],
        'diamond' => ['diamond', 'geometric'],
        'triangle' => ['triangle', 'wayfarer']
    ];

    foreach ($selected_face_shapes as $face_shape) {
        if ($face_shape != 'all' && isset($face_shape_mappings[$face_shape])) {
            $terms = $face_shape_mappings[$face_shape];
            $term_conditions = [];
            foreach ($terms as $term) {
                $term = mysqli_real_escape_string($conn, $term);
                $term_conditions[] = "(products.name LIKE '%$term%' OR products.description LIKE '%$term%')";
            }
            $face_shape_conditions[] = "(" . implode(' OR ', $term_conditions) . ")";
        }
    }

    if (!empty($face_shape_conditions)) {
        $query .= " AND (" . implode(' OR ', $face_shape_conditions) . ")";
    }
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
    <?php if (!empty($search_query) || (!empty($selected_brands) && !in_array('all', $selected_brands)) || !empty($min_price) || !empty($max_price) || (!empty($selected_face_shapes) && !in_array('all', $selected_face_shapes))): ?>
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
            <?php if (!empty($selected_face_shapes) && !in_array('all', $selected_face_shapes)): ?>
                <p><strong>Face Shape:</strong>
                    <?php
                    $face_shape_names = [];
                    foreach ($selected_face_shapes as $face_shape) {
                        if ($face_shape != 'all') {
                            $face_shape_names[] = ucfirst($face_shape);
                        }
                    }
                    echo implode(', ', $face_shape_names);
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
                <?php if (!empty($selected_face_shapes)): ?>
                    <?php foreach ($selected_face_shapes as $face_shape): ?>
                        <input type="hidden" name="face_shape[]" value="<?= htmlspecialchars($face_shape) ?>">
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
                            <input class="form-check-input" type="checkbox" name="brand_id[]" value="all" id="brand_all" <?= empty($selected_brands) || in_array('all', $selected_brands) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="brand_all">All Brands</label>
                        </div>
                        <?php
                        // Fetch all brands
                        $brandQuery = "SELECT * FROM brands";
                        $brandResult = mysqli_query($conn, $brandQuery);
                        while ($brand = mysqli_fetch_assoc($brandResult)) {
                            $brandID = $brand['id'];
                            $brandName = $brand['brand_name'];
                            $checked = in_array((string)$brandID, $selected_brands) ? "checked" : "";
                            echo "<div class='form-check'>
                                    <input class='form-check-input' type='checkbox' name='brand_id[]' value='$brandID' id='brand_$brandID' $checked>
                                    <label class='form-check-label' for='brand_$brandID'>$brandName</label>
                                  </div>";
                        }
                        ?>
                    </div>
                </div>

                <!-- Face Shape Filter -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Filter By Face Shape</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="face_shape[]" value="all" id="face_all" <?= empty($selected_face_shapes) || in_array('all', $selected_face_shapes) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="face_all">All Face Shapes</label>
                        </div>
                        <?php
                        // Define face shapes
                        $face_shapes = [
                            'round' => 'Round',
                            'oval' => 'Oval',
                            'square' => 'Square',
                            'heart' => 'Heart',
                            'diamond' => 'Diamond',
                            'triangle' => 'Triangle'
                        ];

                        foreach ($face_shapes as $value => $label) {
                            $checked = in_array($value, $selected_face_shapes) ? "checked" : "";
                            echo "<div class='form-check'>
                                    <input class='form-check-input' type='checkbox' name='face_shape[]' value='$value' id='face_$value' $checked>
                                    <label class='form-check-label' for='face_$value'>$label</label>
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
                <?php if (!empty($selected_brands) || !empty($min_price) || !empty($max_price) || !empty($search_query) || !empty($selected_face_shapes)): ?>
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

                        // Determine face shape for display (based on product name/description)
                        $face_shape_display = '';
                        $face_shape_mappings = [
                            'round' => ['round', 'circle', 'circular'],
                            'oval' => ['oval', 'ellipse'],
                            'square' => ['square', 'angular', 'rectangular'],
                            'heart' => ['heart', 'cat-eye', 'aviator'],
                            'diamond' => ['diamond', 'geometric'],
                            'triangle' => ['triangle', 'wayfarer']
                        ];

                        $matched_shapes = [];
                        foreach ($face_shape_mappings as $shape => $terms) {
                            foreach ($terms as $term) {
                                if (stripos($product['name'] . ' ' . $product['description'], $term) !== false) {
                                    $matched_shapes[] = $shape;
                                    break;
                                }
                            }
                        }

                        if (!empty($matched_shapes)) {
                            $face_shape_display = 'Best for ' . ucfirst(implode('/', array_unique($matched_shapes))) . ' Faces';
                        }
                    ?>
                        <div class="col">
                            <div class="card h-100 product-card-custom">
                                <a href="product-detail.php?id=<?= $product['id'] ?>">
                                    <img src="<?= $images[0] ?>" class="card-img-top product-image" alt="<?= htmlspecialchars($product['name']) ?>" />
                                </a>
                                <div class="card-body text-center">
                                    <h5 class="card-title fw-bold mb-2" style="font-size: 1.2rem;"><?= $product_name ?></h5>
                                    <div class="mb-2">
                                        <span class="text-muted text-decoration-line-through" style="font-size: 1.1rem;">₹ <?= number_format($product['price']) ?></span>
                                        <span class="ms-2 fw-bold text-success" style="font-size: 1.2rem;">₹ <?= number_format($product['discount_price']) ?></span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge save-badge-custom">SAVE <?= number_format($product['price'] - $product['discount_price']) ?></span>
                                    </div>
                                 
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

        // Handle "All" checkboxes
        document.getElementById('face_all').addEventListener('change', function() {
            var faceCheckboxes = document.querySelectorAll('input[name="face_shape[]"]');
            if (this.checked) {
                faceCheckboxes.forEach(function(checkbox) {
                    checkbox.checked = true;
                });
            }
        });

        document.getElementById('brand_all').addEventListener('change', function() {
            var brandCheckboxes = document.querySelectorAll('input[name="brand_id[]"]');
            if (this.checked) {
                brandCheckboxes.forEach(function(checkbox) {
                    checkbox.checked = true;
                });
            }
        });

        // Uncheck "All" if any individual option is unchecked
        var faceCheckboxes = document.querySelectorAll('input[name="face_shape[]"]:not(#face_all)');
        faceCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                if (!this.checked) {
                    document.getElementById('face_all').checked = false;
                }
            });
        });

        var brandCheckboxes = document.querySelectorAll('input[name="brand_id[]"]:not(#brand_all)');
        brandCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                if (!this.checked) {
                    document.getElementById('brand_all').checked = false;
                }
            });
        });
    });
</script>

<?php include('footer.php'); ?>