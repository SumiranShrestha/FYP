<?php
session_start();
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

include('server/connection.php');

// Handle adding a new FAQ
if (isset($_POST['add_faq'])) {
    $question = mysqli_real_escape_string($conn, $_POST['question']);
    $answer = mysqli_real_escape_string($conn, $_POST['answer']);
    
    $sql = "INSERT INTO faqs (question, answer) VALUES ('$question', '$answer')";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['success_message'] = 'FAQ added successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to add FAQ. Please try again.';
    }
    header("Location: manage_faqs.php");
    exit();
}

// Handle deleting a FAQ
if (isset($_GET['delete_id'])) {
    $faq_id = $_GET['delete_id'];
    $sql = "DELETE FROM faqs WHERE id = $faq_id";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['success_message'] = 'FAQ deleted successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to delete FAQ. Please try again.';
    }
    header("Location: manage_faqs.php");
    exit();
}

// Fetch all FAQs
$sql = "SELECT * FROM faqs ORDER BY id ASC";
$faqs = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage FAQs</title>
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
                        <a class="nav-link active" href="manage_faqs.php">FAQs</a>
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
        <h2 class="text-center mb-4">Manage FAQs</h2>

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

        <!-- Add FAQ Form -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Add New FAQ</h5>
                <form method="POST" action="manage_faqs.php">
                    <div class="mb-3">
                        <label for="question" class="form-label">Question</label>
                        <input type="text" class="form-control" id="question" name="question" required>
                    </div>
                    <div class="mb-3">
                        <label for="answer" class="form-label">Answer</label>
                        <textarea class="form-control" id="answer" name="answer" rows="4" required></textarea>
                    </div>
                    <button type="submit" name="add_faq" class="btn btn-primary">Add FAQ</button>
                </form>
            </div>
        </div>

        <!-- FAQ List -->
        <h4 class="text-center mb-4">Existing FAQs</h4>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Question</th>
                    <th>Answer</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($faq = mysqli_fetch_assoc($faqs)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($faq['question']); ?></td>
                        <td><?php echo htmlspecialchars($faq['answer']); ?></td>
                        <td>
                            <a href="edit_faqs.php?id=<?php echo $faq['id']; ?>" class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                            <button
                                class="btn btn-danger btn-sm delete-faq-btn"
                                data-id="<?php echo $faq['id']; ?>"
                                data-question="<?php echo htmlspecialchars($faq['question']); ?>">
                                <i class="bi bi-trash me-1"></i>Delete
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date("Y"); ?> Admin Panel. All rights reserved.</p>
        </div>
    </footer>

    <!-- Delete FAQ Confirmation Modal -->
    <div class="modal fade" id="deleteFaqModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="min-width:320px;max-width:350px;margin:auto;">
                <div class="modal-body text-center py-4">
                    <h5 class="fw-bold mb-3">Delete FAQ</h5>
                    <div class="mb-4">
                        Are you sure you want to delete the FAQ: <span id="faqQuestion" class="fw-bold"></span>?
                    </div>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-outline-danger px-4" data-bs-dismiss="modal">Cancel</button>
                        <a href="#" id="confirmDeleteFaqBtn" class="btn btn-primary px-4">Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Delete FAQ modal logic
        document.querySelectorAll('.delete-faq-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var faqId = this.getAttribute('data-id');
                var faqQuestion = this.getAttribute('data-question');
                document.getElementById('faqQuestion').textContent = faqQuestion;
                document.getElementById('confirmDeleteFaqBtn').setAttribute('href', 'manage_faqs.php?delete_id=' + faqId);
                var modal = new bootstrap.Modal(document.getElementById('deleteFaqModal'));
                modal.show();
            });
        });

        // Auto-hide alert messages after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                setTimeout(function() {
                    var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                }, 3000);
            }
        });
    </script>
</body>
</html>
