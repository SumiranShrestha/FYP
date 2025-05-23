<?php
session_start();
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

include('server/connection.php');

// Check if an FAQ ID is provided for editing
if (isset($_GET['id'])) {
    $faq_id = $_GET['id'];
    
    // Fetch the FAQ data
    $sql = "SELECT * FROM faqs WHERE id = $faq_id";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        $faq = mysqli_fetch_assoc($result);
    } else {
        $_SESSION['error_message'] = 'FAQ not found.';
        header("Location: manage_faqs.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = 'Invalid FAQ ID.';
    header("Location: manage_faqs.php");
    exit();
}

// Handle the form submission for editing the FAQ
if (isset($_POST['edit_faqs'])) {
    $question = mysqli_real_escape_string($conn, $_POST['question']);
    $answer = mysqli_real_escape_string($conn, $_POST['answer']);
    
    $sql = "UPDATE faqs SET question = '$question', answer = '$answer' WHERE id = $faq_id";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['success_message'] = 'FAQ updated successfully!';
        header("Location: manage_faqs.php");
        exit();
    } else {
        $_SESSION['error_message'] = 'Failed to update FAQ. Please try again.';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit FAQ</title>
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
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_faqs.php">FAQs</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-light me-3">Welcome, <?php echo htmlspecialchars($_SESSION["admin_username"]); ?></span>
                    <!-- Logout button triggers modal -->
                    <button id="logoutBtn" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <h2 class="text-center mb-4">Edit FAQ</h2>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php elseif (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Edit FAQ Form -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Edit FAQ</h5>
                <form method="POST" action="edit_faqs.php?id=<?php echo $faq_id; ?>">
                    <div class="mb-3">
                        <label for="question" class="form-label">Question</label>
                        <input type="text" class="form-control" id="question" name="question" value="<?php echo htmlspecialchars($faq['question']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="answer" class="form-label">Answer</label>
                        <textarea class="form-control" id="answer" name="answer" rows="4" required><?php echo htmlspecialchars($faq['answer']); ?></textarea>
                    </div>
                    <button type="submit" name="edit_faqs" class="btn btn-primary">Update FAQ</button>
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

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Logout confirmation logic
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        var modal = new bootstrap.Modal(document.getElementById('logoutConfirmModal'));
        modal.show();
    });
    </script>
</body>
</html>
