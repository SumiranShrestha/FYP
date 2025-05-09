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

// Handle Product Addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_product"])) {
    $name = $_POST["name"];
    $price = $_POST["price"];
    $discount_price = $_POST["discount_price"] ?? 0;
    $description = $_POST["description"] ?? '';
    $category_id = $_POST["category"] ?? null;
    $brand_id = $_POST["brand"] ?? null;
    $prescription_required = isset($_POST["prescription_required"]) ? 1 : 0;
    $stock = $_POST["stock"] ?? 10;
    $facial_structure = $_POST["facial_structure"] ?? 'all';

    // Handle image uploads
    $uploaded_images = [];
    if (!empty($_FILES["images"]["name"][0])) {
        $target_dir = "uploads/";
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

    // Insert product into the database
    $stmt = $conn->prepare("INSERT INTO products (name, price, discount_price, description, 
                           category_id, brand_id, prescription_required, stock, images, facial_structure) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $images_json = json_encode($uploaded_images);
    $stmt->bind_param(
        "sdsssiisss",
        $name,
        $price,
        $discount_price,
        $description,
        $category_id,
        $brand_id,
        $prescription_required,
        $stock,
        $images_json,
        $facial_structure
    );

    if ($stmt->execute()) {
        $_SESSION['alert_message'] = "Product successfully added";
        $_SESSION['alert_type'] = "success";
    } else {
        $_SESSION['alert_message'] = "Error adding product: " . $conn->error;
        $_SESSION['alert_type'] = "danger";
    }

    header("Location: manage_products.php");
    exit();
}

// Handle Product Deletion
if (isset($_GET["delete_product"])) {
    $product_id = $_GET["delete_product"];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);

    if ($stmt->execute()) {
        $_SESSION['alert_message'] = "Product successfully deleted";
        $_SESSION['alert_type'] = "success";
    } else {
        $_SESSION['alert_message'] = "Error deleting product: " . $conn->error;
        $_SESSION['alert_type'] = "danger";
    }

    header("Location: manage_products.php");
    exit();
}

// Fetch All Products with category and brand names
$result = $conn->query("
    SELECT p.*, c.name as category_name, b.brand_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    ORDER BY p.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .img-thumbnail {
            position: relative;
            margin: 5px;
            width: 80px;
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
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 10px;
        }

        .delete-btn:hover {
            background: rgba(255, 0, 0, 1);
        }

        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }
    </style>
</head>

<body>
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
            <h2><i class="bi bi-box-seam me-2"></i>Manage Products</h2>
            <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
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

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="bi bi-plus-circle me-2"></i>Add New Product</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>

                            <div class="mb-3">
                                <label for="brand" class="form-label">Brand</label>
                                <select class="form-select" id="brand" name="brand">
                                    <option value="">Select brand</option>
                                    <?php while ($brand = $brands_result->fetch_assoc()): ?>
                                        <option value="<?= $brand['id']; ?>">
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
                                            <option value="<?= $category['id']; ?>">
                                                <?= htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="price" class="form-label">Price (रू)</label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                            </div>

                            <div class="mb-3">
                                <label for="discount_price" class="form-label">Discount Price (रू)</label>
                                <input type="number" step="0.01" class="form-control" id="discount_price" name="discount_price">
                            </div>

                            <div class="mb-3">
                                <label for="stock" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" id="stock" name="stock" value="10">
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="prescription_required" name="prescription_required">
                                <label class="form-check-label" for="prescription_required">Prescription Required</label>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="facial_structure" class="form-label">Best For Face Shape</label>
                                <select class="form-select" id="facial_structure" name="facial_structure">
                                    <option value="all" selected>All Face Shapes</option>
                                    <option value="round">Round</option>
                                    <option value="oval">Oval</option>
                                    <option value="square">Square</option>
                                    <option value="heart">Heart</option>
                                    <option value="diamond">Diamond</option>
                                    <option value="triangle">Triangle</option>
                                </select>
                                <div class="form-text">Select which face shape this product is best suited for</div>
                            </div>

                            <div class="mb-3">
                                <label for="images" class="form-label">Product Images</label>
                                <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                            </div>

                            <button type="submit" name="add_product" class="btn btn-success w-100">
                                <i class="bi bi-plus-lg me-1"></i>Add Product
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="bi bi-list-ul me-2"></i>Product List</h5>
                        <div class="input-group input-group-sm" style="max-width: 200px;">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search products">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Brand</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Face Shape</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="productTableBody">
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($product = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= $product['id']; ?></td>
                                                <td>
                                                    <?php
                                                    $images = json_decode($product['images'], true);
                                                    if (!empty($images)): ?>
                                                        <img src="<?= $images[0]; ?>" alt="Product Image" class="img-thumbnail">
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($product['name']); ?></td>
                                                <td><?= htmlspecialchars($product['brand_name'] ?? 'N/A'); ?></td>
                                                <td><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                                <td>
                                                    <?php if ($product['discount_price'] > 0): ?>
                                                        <span class="text-danger"><del>रू <?= number_format($product['price'], 2); ?></del></span><br>
                                                        रू <?= number_format($product['discount_price'], 2); ?>
                                                    <?php else: ?>
                                                        रू <?= number_format($product['price'], 2); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $product['stock']; ?></td>
                                                <td>
                                                    <?php
                                                    $faceShape = $product['facial_structure'] ?? 'all';
                                                    if ($faceShape == 'all') {
                                                        echo 'All Shapes';
                                                    } else {
                                                        echo ucfirst($faceShape);
                                                    }
                                                    ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="edit_product.php?id=<?= $product['id']; ?>" class="btn btn-primary">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <a href="view_product.php?id=<?= $product['id']; ?>" class="btn btn-info">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="manage_products.php?delete_product=<?= $product['id']; ?>"
                                                            class="btn btn-danger"
                                                            onclick="return confirm('Are you sure you want to delete this product?')">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4">No products found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <small class="text-muted">Showing <?= $result->num_rows ?> products</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const productTableBody = document.getElementById('productTableBody');
            const rows = productTableBody.getElementsByTagName('tr');

            searchInput.addEventListener('keyup', function() {
                const searchValue = this.value.toLowerCase();

                for (let row of rows) {
                    const cells = row.getElementsByTagName('td');
                    let found = false;

                    for (let cell of cells) {
                        if (cell.textContent.toLowerCase().includes(searchValue)) {
                            found = true;
                            break;
                        }
                    }

                    if (found) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        });
    </script>
</body>

</html>