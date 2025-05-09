<?php
session_start();
include("server/connection.php");

if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch all categories and brands from the database
$categories_result = $conn->query("SELECT * FROM categories");
$brands_result = $conn->query("SELECT * FROM brands");

if (isset($_GET["id"])) {
    $product_id = $_GET["id"];

    // Fetch the product details with brand information
    $stmt = $conn->prepare("SELECT p.*, b.brand_name 
                           FROM products p 
                           LEFT JOIN brands b ON p.brand_id = b.id 
                           WHERE p.id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
}

// Handle updating the product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_product"])) {
    $name = $_POST["name"];
    $price = $_POST["price"];
    $discount_price = $_POST["discount_price"] ?? 0;
    $description = $_POST["description"] ?? '';
    $category_id = $_POST["category"] ?? null;
    $brand_id = $_POST["brand"] ?? null;
    $prescription_required = isset($_POST["prescription_required"]) ? 1 : 0;
    $stock = $_POST["stock"] ?? 10;
    $product_id = $_POST["product_id"];
    $facial_structure = $_POST["facial_structure"] ?? 'all';

    // Validate the brand_id if provided
    if ($brand_id !== null) {
        // Check if the brand_id exists in the brands table
        $stmt = $conn->prepare("SELECT id FROM brands WHERE id = ?");
        $stmt->bind_param("i", $brand_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            // Brand does not exist, handle the error
            $_SESSION['alert_message'] = "The selected brand does not exist.";
            $_SESSION['alert_type'] = "danger";
            header("Location: edit_product.php?id=$product_id");
            exit();
        }
    }

    // Validate the category_id if provided
    if ($category_id !== null) {
        // Check if the category_id exists in the categories table
        $stmt = $conn->prepare("SELECT id FROM categories WHERE id = ?");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            // Category does not exist, handle the error
            $_SESSION['alert_message'] = "The selected category does not exist.";
            $_SESSION['alert_type'] = "danger";
            header("Location: edit_product.php?id=$product_id");
            exit();
        }
    }

    // Handle image uploads
    $uploaded_images = [];
    if (!empty($_FILES["images"]["name"][0])) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        foreach ($_FILES["images"]["tmp_name"] as $key => $tmp_name) {
            $file_name = basename($_FILES["images"]["name"][$key]);
            $target_file = $target_dir . uniqid() . "_" . $file_name;

            if (move_uploaded_file($tmp_name, $target_file)) {
                $uploaded_images[] = $target_file;
            }
        }
    }

    // Fetch existing images
    $stmt = $conn->prepare("SELECT images FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_product = $result->fetch_assoc();
    $existing_images = json_decode($existing_product['images'], true);

    // Merge existing and new images
    $updated_images = array_merge($existing_images, $uploaded_images);

    // Update the product in the database
    $stmt = $conn->prepare("UPDATE products SET 
                            name = ?, 
                            price = ?, 
                            discount_price = ?,
                            description = ?, 
                            category_id = ?, 
                            brand_id = ?,
                            prescription_required = ?,
                            stock = ?,
                            images = ?,
                            facial_structure = ? 
                            WHERE id = ?");

    $images_json = json_encode($updated_images);
    $stmt->bind_param(
        "sdsssiisssi",
        $name,
        $price,
        $discount_price,
        $description,
        $category_id,
        $brand_id,
        $prescription_required,
        $stock,
        $images_json,
        $facial_structure,
        $product_id
    );

    if ($stmt->execute()) {
        $_SESSION['alert_message'] = "Product successfully updated";
        $_SESSION['alert_type'] = "success";
    } else {
        $_SESSION['alert_message'] = "Error updating product: " . $conn->error;
        $_SESSION['alert_type'] = "danger";
    }

    header("Location: manage_products.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .img-thumbnail {
            position: relative;
            margin: 5px;
            width: 100px;
            height: auto;
        }

        .delete-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .delete-btn:hover {
            background: rgba(255, 0, 0, 1);
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">Shady Shades Admin</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_orders.php">Orders</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-light me-3">Welcome, <?= htmlspecialchars($_SESSION["admin_username"]); ?></span>
                    <a href="admin_logout.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-pencil me-2"></i>Edit Product</h2>
            <a href="manage_products.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Products
            </a>
        </div>

        <?php if (isset($_SESSION['alert_message'])): ?>
            <div class="alert alert-<?= $_SESSION['alert_type'] ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['alert_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['alert_message']);
            unset($_SESSION['alert_type']); ?>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Product</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" value="<?= $product['id']; ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="<?= htmlspecialchars($product['name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="brand" class="form-label">Brand</label>
                                <select class="form-select" id="brand" name="brand">
                                    <option value="">Select brand</option>
                                    <?php while ($brand = $brands_result->fetch_assoc()): ?>
                                        <option value="<?= $brand['id']; ?>"
                                            <?= $product['brand_id'] == $brand['id'] ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($brand['brand_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">Select category</option>
                                    <?php if ($categories_result->num_rows > 0): ?>
                                        <?php while ($category = $categories_result->fetch_assoc()): ?>
                                            <option value="<?= $category['id']; ?>"
                                                <?= $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="price" class="form-label">Price (रू)</label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price"
                                    value="<?= $product['price']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="facial_structure" class="form-label">Best For Face Shape</label>
                                <select class="form-select" id="facial_structure" name="facial_structure">
                                    <option value="all" <?= (!isset($product['facial_structure']) || $product['facial_structure'] == 'all') ? 'selected' : ''; ?>>All Face Shapes</option>
                                    <option value="round" <?= (isset($product['facial_structure']) && $product['facial_structure'] == 'round') ? 'selected' : ''; ?>>Round</option>
                                    <option value="oval" <?= (isset($product['facial_structure']) && $product['facial_structure'] == 'oval') ? 'selected' : ''; ?>>Oval</option>
                                    <option value="square" <?= (isset($product['facial_structure']) && $product['facial_structure'] == 'square') ? 'selected' : ''; ?>>Square</option>
                                    <option value="heart" <?= (isset($product['facial_structure']) && $product['facial_structure'] == 'heart') ? 'selected' : ''; ?>>Heart</option>
                                    <option value="diamond" <?= (isset($product['facial_structure']) && $product['facial_structure'] == 'diamond') ? 'selected' : ''; ?>>Diamond</option>
                                    <option value="triangle" <?= (isset($product['facial_structure']) && $product['facial_structure'] == 'triangle') ? 'selected' : ''; ?>>Triangle</option>
                                </select>
                                <div class="form-text">Select which face shape this product is best suited for</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="discount_price" class="form-label">Discount Price (रू)</label>
                                <input type="number" step="0.01" class="form-control" id="discount_price" name="discount_price"
                                    value="<?= $product['discount_price']; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="stock" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" id="stock" name="stock"
                                    value="<?= $product['stock']; ?>">
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="prescription_required" name="prescription_required"
                                    <?= $product['prescription_required'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="prescription_required">Prescription Required</label>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5"><?= htmlspecialchars($product['description']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Existing Images</label>
                        <div class="d-flex flex-wrap">
                            <?php
                            $images = json_decode($product['images'], true);
                            foreach ($images as $image): ?>
                                <div style="position: relative; margin: 5px;">
                                    <img src="<?= $image; ?>" alt="Product Image" class="img-thumbnail">
                                    <a href="delete_image.php?product_id=<?= $product['id']; ?>&image_url=<?= urlencode($image); ?>"
                                        class="delete-btn"
                                        onclick="return confirm('Are you sure you want to delete this image?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="images" class="form-label">Add More Images</label>
                        <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                    </div>

                    <button type="submit" name="update_product" class="btn btn-primary w-100">
                        <i class="bi bi-save me-1"></i>Update Product
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>