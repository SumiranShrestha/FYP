<?php
session_start();
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Dashboard</a>
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
                        <a class="nav-link" href="manage_faqs.php">FAQs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_doctors.php">Doctors</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_brands.php">Brands</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-light me-3">Welcome, <?php echo htmlspecialchars($_SESSION["admin_username"]); ?></span>
                    <a href="admin_logout.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <div class="card bg-light">
                    <div class="card-body">
                        <h4 class="card-title">Admin Control Panel</h4>
                        <p class="card-text">Welcome to your administrative dashboard. Use the cards below to manage your site.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Users Management Card -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-people-fill text-primary" style="font-size: 3rem;"></i>
                        <h5 class="card-title mt-3">Manage Users</h5>
                        <p class="card-text">Add, edit, or remove user accounts and manage permissions.</p>
                        <a href="manage_users.php" class="btn btn-primary">
                            <i class="bi bi-person-gear me-1"></i>Manage Users
                        </a>
                    </div>
                </div>
            </div>

            <!-- Products Management Card -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-box-seam text-primary" style="font-size: 3rem;"></i>
                        <h5 class="card-title mt-3">Manage Products</h5>
                        <p class="card-text">Add new products, update inventory, and manage product categories.</p>
                        <a href="manage_products.php" class="btn btn-primary">
                            <i class="bi bi-box-seam me-1"></i>Manage Products
                        </a>
                    </div>
                </div>
            </div>

            <!-- FAQ Management Card -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-question-circle-fill text-primary" style="font-size: 3rem;"></i>
                        <h5 class="card-title mt-3">Manage FAQs</h5>
                        <p class="card-text">Add, edit, or remove frequently asked questions for your site.</p>
                        <a href="manage_faqs.php" class="btn btn-primary">
                            <i class="bi bi-question-circle me-1"></i>Manage FAQs
                        </a>
                    </div>
                </div>
            </div>

            <!-- Add Doctor Card -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-person-plus text-primary" style="font-size: 3rem;"></i>
                        <h5 class="card-title mt-3">Add Doctor</h5>
                        <p class="card-text">Add new doctors to the system with their details and availability.</p>
                        <a href="add_doctor.php" class="btn btn-primary">
                            <i class="bi bi-person-plus me-1"></i>Add Doctor
                        </a>
                    </div>
                </div>
            </div>

            <!-- View Doctors Card -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-person-lines-fill text-primary" style="font-size: 3rem;"></i>
                        <h5 class="card-title mt-3">View Doctors</h5>
                        <p class="card-text">View and manage all doctors in the system.</p>
                        <a href="view_doctors.php" class="btn btn-primary">
                            <i class="bi bi-person-lines-fill me-1"></i>View Doctors
                        </a>
                    </div>
                </div>
            </div>

            <!-- View Appointments Card -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-check text-primary" style="font-size: 3rem;"></i>
                        <h5 class="card-title mt-3">View Appointments</h5>
                        <p class="card-text">Check and manage all appointments scheduled in the system.</p>
                        <a href="view_appointment.php" class="btn btn-primary">
                            <i class="bi bi-calendar-check me-1"></i>View Appointments
                        </a>
                    </div>
                </div>
            </div>

            <!-- View Brands Card -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-archive text-primary" style="font-size: 3rem;"></i>
                        <h5 class="card-title mt-3">Manage Brands</h5>
                        <p class="card-text">View, add, update, or delete product brands.</p>
                        <a href="manage_brands.php" class="btn btn-primary">
                            <i class="bi bi-archive me-1"></i>Manage Brands
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date("Y"); ?> Admin Panel. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
