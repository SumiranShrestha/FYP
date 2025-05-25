<?php
session_start();
include('server/connection.php'); // Database connection

// Redirect if the user is not an admin
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch all brands
$brands_result = mysqli_query($conn, "SELECT * FROM brands ORDER BY id ASC");

// Handle Add Brand
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_brand'])) {
    $brand_name = mysqli_real_escape_string($conn, $_POST['brand_name']);
    
    if (!empty($brand_name)) {
        $insert_query = "INSERT INTO brands (brand_name) VALUES ('$brand_name')";
        if (mysqli_query($conn, $insert_query)) {
            $_SESSION['alert_message'] = "Brand added successfully!";
            $_SESSION['alert_type'] = "success";
        } else {
            $_SESSION['alert_message'] = "Error adding brand: " . mysqli_error($conn);
            $_SESSION['alert_type'] = "danger";
        }
    } else {
        $_SESSION['alert_message'] = "Brand name cannot be empty!";
        $_SESSION['alert_type'] = "danger";
    }

    header("Location: manage_brands.php");
    exit();
}

// Handle Update Brand
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_brand'])) {
    $brand_id = $_POST['brand_id'];
    $brand_name = mysqli_real_escape_string($conn, $_POST['brand_name']);
    
    $update_query = "UPDATE brands SET brand_name = '$brand_name' WHERE id = $brand_id";
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['alert_message'] = "Brand updated successfully!";
        $_SESSION['alert_type'] = "success";
    } else {
        $_SESSION['alert_message'] = "Error updating brand: " . mysqli_error($conn);
        $_SESSION['alert_type'] = "danger";
    }

    header("Location: manage_brands.php");
    exit();
}

// Handle Delete Brand
if (isset($_GET['delete_brand'])) {
    $brand_id = $_GET['delete_brand'];
    $delete_query = "DELETE FROM brands WHERE id = $brand_id";
    
    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['alert_message'] = "Brand deleted successfully!";
        $_SESSION['alert_type'] = "success";
    } else {
        $_SESSION['alert_message'] = "Error deleting brand: " . mysqli_error($conn);
        $_SESSION['alert_type'] = "danger";
    }

    header("Location: manage_brands.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Brands</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <!-- Navbar Header (full width, consistent with other admin pages) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 w-100">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Shady Shades Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_orders.php">Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_prescription_orders.php">Prescription Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_brands.php">Brands</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_doctors.php">Doctors</a>
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
    <!-- End Navbar Header -->

<div class="container my-4">
    <h2>Manage Brands</h2>

    <!-- Display Success/Error Messages -->
    <?php if (isset($_SESSION['alert_message'])): ?>
        <div class="alert alert-<?= $_SESSION['alert_type'] ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['alert_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['alert_message']); unset($_SESSION['alert_type']); ?>
    <?php endif; ?>

    <!-- Add Brand Form -->
    <div class="mb-4">
        <h4>Add New Brand</h4>
        <form method="POST">
            <div class="mb-3">
                <label for="brand_name" class="form-label">Brand Name</label>
                <input type="text" name="brand_name" id="brand_name" class="form-control" required>
            </div>
            <button type="submit" name="add_brand" class="btn btn-success">Add Brand</button>
        </form>
    </div>

    <!-- Brands Table -->
    <h4>Existing Brands</h4>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Brand Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($brand = mysqli_fetch_assoc($brands_result)): ?>
                <tr>
                    <td><?= $brand['id'] ?></td>
                    <td><?= htmlspecialchars($brand['brand_name']) ?></td>
                    <td>
                        <!-- Update Brand Form -->
                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#updateBrandModal" onclick="fillUpdateForm(<?= $brand['id'] ?>, '<?= htmlspecialchars($brand['brand_name']) ?>')">Edit</button>

                        <!-- Delete Button triggers modal -->
                        <button 
                            class="btn btn-danger delete-brand-btn"
                            data-id="<?= $brand['id']; ?>"
                            data-name="<?= htmlspecialchars($brand['brand_name']); ?>"
                        >
                            Delete
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Update Brand Modal -->
    <div class="modal fade" id="updateBrandModal" tabindex="-1" aria-labelledby="updateBrandModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateBrandModalLabel">Update Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="brand_id" id="brand_id">
                        <div class="mb-3">
                            <label for="update_brand_name" class="form-label">Brand Name</label>
                            <input type="text" name="brand_name" id="update_brand_name" class="form-control" required>
                        </div>
                        <button type="submit" name="update_brand" class="btn btn-primary">Update Brand</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Brand Confirmation Modal -->
    <div class="modal fade" id="deleteBrandModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="min-width:320px;max-width:350px;margin:auto;">
                <div class="modal-body text-center py-4">
                    <h5 class="fw-bold mb-3">Delete Brand</h5>
                    <div class="mb-4">
                        Are you sure you want to delete <span id="brandName" class="fw-bold"></span>?
                    </div>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-outline-danger px-4" data-bs-dismiss="modal">Cancel</button>
                        <a href="#" id="confirmDeleteBrandBtn" class="btn btn-primary px-4">Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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

<script>
    function fillUpdateForm(id, name) {
        document.getElementById('brand_id').value = id;
        document.getElementById('update_brand_name').value = name;
    }

    // Delete brand modal logic
    document.querySelectorAll('.delete-brand-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var brandId = this.getAttribute('data-id');
            var brandName = this.getAttribute('data-name');
            document.getElementById('brandName').textContent = brandName;
            document.getElementById('confirmDeleteBrandBtn').setAttribute('href', 'manage_brands.php?delete_brand=' + brandId);
            var modal = new bootstrap.Modal(document.getElementById('deleteBrandModal'));
            modal.show();
        });
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
