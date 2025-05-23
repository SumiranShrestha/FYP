<?php
    session_start();
    include("server/connection.php");

    if (!isset($_SESSION["admin_logged_in"])) {
        header("Location: admin_login.php");
        exit();
    }

    if (!isset($_GET["id"])) {
        header("Location: manage_products.php");
        exit();
    }

    $product_id = $_GET["id"];

    // Fetch the product details with category and brand information
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name, b.brand_name 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        header("Location: manage_products.php");
        exit();
    }

    $product = $result->fetch_assoc();
    $images = json_decode($product['images'], true);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .product-image {
            max-height: 400px;
            width: auto;
            object-fit: contain;
        }
        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid #dee2e6;
        }
        .thumbnail:hover, .thumbnail.active {
            border-color: #0d6efd;
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
                    <!-- Logout button triggers modal -->
                    <button id="logoutBtn" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-eye me-2"></i>View Product</h2>
            <a href="manage_products.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Products
            </a>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0"><?= htmlspecialchars($product['name']); ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <!-- Main Product Image -->
                        <div class="text-center mb-3">
                            <img id="mainImage" src="<?= $images[0]; ?>" alt="Product Image" class="product-image img-fluid rounded">
                        </div>
                        
                        <!-- Thumbnail Gallery -->
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($images as $index => $image): ?>
                                <img src="<?= $image; ?>" alt="Product Thumbnail" 
                                    class="thumbnail <?= $index === 0 ? 'active' : ''; ?>"
                                    onclick="changeMainImage('<?= $image; ?>', this)">
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <h4 class="text-primary">
                                <?php if ($product['discount_price'] > 0): ?>
                                    <span class="text-danger"><del>रू <?= number_format($product['price'], 2); ?></del></span>
                                    रू <?= number_format($product['discount_price'], 2); ?>
                                <?php else: ?>
                                    रू <?= number_format($product['price'], 2); ?>
                                <?php endif; ?>
                            </h4>
                        </div>
                        
                        <div class="mb-3">
                            <p><strong>Brand:</strong> <?= htmlspecialchars($product['brand_name'] ?? 'N/A'); ?></p>
                            <p><strong>Category:</strong> <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
                            <p><strong>Stock:</strong> <?= $product['stock']; ?></p>
                            <p><strong>Prescription Required:</strong> 
                                <?= $product['prescription_required'] ? 'Yes' : 'No'; ?>
                            </p>
                        </div>
                        
                        <div class="mb-3">
                            <h5>Description</h5>
                            <p><?= nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <a href="edit_product.php?id=<?= $product['id']; ?>" class="btn btn-primary">
                                <i class="bi bi-pencil me-1"></i>Edit Product
                            </a>
                            <!-- Delete Button triggers modal -->
                            <button 
                                class="btn btn-danger" 
                                id="deleteProductBtn"
                                data-id="<?= $product['id']; ?>"
                                data-name="<?= htmlspecialchars($product['name']); ?>"
                            >
                                <i class="bi bi-trash me-1"></i>Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Product Confirmation Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="min-width:320px;max-width:350px;margin:auto;">
                <div class="modal-body text-center py-4">
                    <h5 class="fw-bold mb-3">Delete Product</h5>
                    <div class="mb-4">
                        Are you sure you want to delete <span id="productName" class="fw-bold"></span>?
                    </div>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-outline-danger px-4" data-bs-dismiss="modal">Cancel</button>
                        <a href="#" id="confirmDeleteProductBtn" class="btn btn-primary px-4">Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutConfirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="min-width:320px;max-width:350px;margin:auto;">
                <div class="modal-body text-center py-4">
                    <h5 class="fw-bold mb-3">Logout</h5>
                    <div class="mb-4">Are you sure you want to logout?</div>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-outline-danger px-4" data-bs-dismiss="modal">Cancel</button>
                        <a href="admin_logout.php" class="btn btn-primary px-4">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeMainImage(src, element) {
            document.getElementById('mainImage').src = src;
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            element.classList.add('active');
        }

        // Delete product modal logic
        document.getElementById('deleteProductBtn').addEventListener('click', function() {
            var productId = this.getAttribute('data-id');
            var productName = this.getAttribute('data-name');
            document.getElementById('productName').textContent = productName;
            document.getElementById('confirmDeleteProductBtn').setAttribute('href', 'manage_products.php?delete_product=' + productId);
            var modal = new bootstrap.Modal(document.getElementById('deleteProductModal'));
            modal.show();
        });

        // Logout confirmation logic
        document.getElementById('logoutBtn').addEventListener('click', function(e) {
            e.preventDefault();
            var modal = new bootstrap.Modal(document.getElementById('logoutConfirmModal'));
            modal.show();
        });
    </script>
</body>
</html>