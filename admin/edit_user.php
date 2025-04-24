<?php
session_start();
include("server/connection.php");

// Check if the admin is logged in
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

// Check if a user ID is provided
if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

// Fetch user data from the database
$user_id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: manage_users.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_name = $_POST['user_name'];
    $user_email = $_POST['user_email'];
    $active = isset($_POST['active']) ? 1 : 0;

    // Update user data in the database
    $stmt = $conn->prepare("UPDATE users SET user_name = ?, user_email = ?, active = ? WHERE id = ?");
    $stmt->bind_param("ssii", $user_name, $user_email, $active, $user_id);
    $stmt->execute();

    // Set success message
    $_SESSION['alert_message'] = "User successfully updated";
    $_SESSION['alert_type'] = "success";

    // Redirect back to the manage users page
    header("Location: manage_users.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_products.php">Products</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-light me-3">
                        Welcome, <?php echo htmlspecialchars($_SESSION["admin_username"]); ?>
                    </span>
                    <a href="admin_logout.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Edit User</h2>
            <a href="manage_users.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Users
            </a>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['alert_message'])): ?>
            <div class="alert alert-<?= $_SESSION['alert_type'] ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['alert_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php 
                // Clear the message after displaying
                unset($_SESSION['alert_message']);
                unset($_SESSION['alert_type']); 
            ?>
        <?php endif; ?>

        <!-- Edit User Form -->
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="edit_user.php?id=<?= $user['id']; ?>">
                    <div class="mb-3">
                        <label for="user_name" class="form-label">Name</label>
                        <input type="text" id="user_name" name="user_name" class="form-control" value="<?= htmlspecialchars($user['user_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="user_email" class="form-label">Email</label>
                        <input type="email" id="user_email" name="user_email" class="form-control" value="<?= htmlspecialchars($user['user_email']); ?>" required>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="active" name="active" <?= $user['active'] == 1 ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="active">Active</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date("Y"); ?> Admin Panel. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
