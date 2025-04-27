<?php
session_start();
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

include('server/connection.php'); // Include database connection

// Fetch all doctors
$stmt = $conn->prepare("SELECT * FROM doctors");
$stmt->execute();
$result = $stmt->get_result();
$doctors = $result->fetch_all(MYSQLI_ASSOC);

// Check for success or error messages
$alert_message = $_SESSION['alert_message'] ?? null;
$alert_type = $_SESSION['alert_type'] ?? null;

// Clear the messages from the session
unset($_SESSION['alert_message']);
unset($_SESSION['alert_type']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Doctors</title>
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
                        <a class="nav-link" href="add_doctor.php">Add Doctor</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="view_doctors.php">Doctors</a>
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
        <!-- Display Success or Error Messages -->
        <?php if ($alert_message) : ?>
            <div class="alert alert-<?= $alert_type; ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($alert_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">View Doctors</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>NMC Number</th>
                                    <th>Specialization</th>
                                    <th>Availability</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($doctors as $doctor) : ?>
                                    <tr>
                                        <td><?= htmlspecialchars($doctor['id']); ?></td>
                                        <td><?= htmlspecialchars($doctor['full_name']); ?></td>
                                        <td><?= htmlspecialchars($doctor['email']); ?></td>
                                        <td><?= htmlspecialchars($doctor['phone']); ?></td>
                                        <td><?= htmlspecialchars($doctor['nmc_number']); ?></td>
                                        <td><?= htmlspecialchars($doctor['specialization']); ?></td>

                                        <!-- Decode availability JSON and display it in a readable format -->
                                        <td>
                                            <?php 
                                            $availability = json_decode($doctor['availability'], true);
                                            
                                            // Check if availability is a valid array
                                            if (is_array($availability) && !empty($availability)) {
                                                echo "<ul>";
                                                foreach ($availability as $day => $time) {
                                                    if (is_array($time)) {
                                                        // Join multiple time slots with comma
                                                        $displayTime = htmlspecialchars(implode(', ', $time));
                                                    } else {
                                                        $displayTime = htmlspecialchars($time);
                                                    }
                                                    echo "<li><strong>" . htmlspecialchars($day) . "</strong>: $displayTime</li>";
                                                }
                                                echo "</ul>";
                                            } else {
                                                echo "No availability data";
                                            }
                                            ?>
                                        </td>

                                        <td>
                                            <!-- Edit Button -->
                                            <a href="edit_doctor.php?id=<?= $doctor['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <!-- Delete Button -->
                                            <a href="delete_doctor.php?id=<?= $doctor['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this doctor?');">
                                                <i class="bi bi-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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
