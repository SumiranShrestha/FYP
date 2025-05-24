<?php
session_start();
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

include('server/connection.php'); // Include database connection

// Handle doctor creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_doctor'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $re_password = $_POST['re_password'];
    $phone = $_POST['phone'];
    $nmc_number = $_POST['nmc_number'];
    $specialization = $_POST['specialization'];

    // Password match validation
    if ($password !== $re_password) {
        $_SESSION['alert_message'] = "Passwords do not match. Please re-enter.";
        $_SESSION['alert_type'] = "danger";
        header("Location: add_doctor.php");
        exit();
    }

    $password_hashed = password_hash($password, PASSWORD_DEFAULT);

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['alert_message'] = "Please enter a valid email address.";
        $_SESSION['alert_type'] = "danger";
        header("Location: add_doctor.php");
        exit();
    }

    // Phone number validation: exactly 10 digits, numbers only
    if (!preg_match('/^\d{10}$/', $phone)) {
        $_SESSION['alert_message'] = "Phone number must be exactly 10 digits and contain only numbers.";
        $_SESSION['alert_type'] = "danger";
        header("Location: add_doctor.php");
        exit();
    }

    // NMC number validation: alphanumeric, 6-12 chars (adjust as needed)
    if (!preg_match('/^[a-zA-Z0-9]{6,12}$/', $nmc_number)) {
        $_SESSION['alert_message'] = "NMC Number must be 6-12 alphanumeric characters.";
        $_SESSION['alert_type'] = "danger";
        header("Location: add_doctor.php");
        exit();
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM doctors WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $_SESSION['alert_message'] = "Email is already registered for another doctor.";
        $_SESSION['alert_type'] = "danger";
    } else {
        // Process availability (same as edit_doctor.php)
        $availability = isset($_POST['availability']) ? $_POST['availability'] : [];
        $availability_times = isset($_POST['availability_times']) ? $_POST['availability_times'] : [];
        $availability_data = [];
        $time_format_regex = '/^(0?[1-9]|1[0-2]):[0-5][0-9]\s?(AM|PM)$/i';
        foreach ($availability as $day => $checked) {
            if (isset($availability_times[$day])) {
                $times = $availability_times[$day];
                if (!is_array($times)) {
                    $slots = array_filter(array_map('trim', explode(',', $times)));
                } else {
                    $slots = array_filter(array_map('trim', $times));
                }
                // Validate each slot for correct format
                foreach ($slots as $slot) {
                    if (!preg_match($time_format_regex, $slot)) {
                        $_SESSION['alert_message'] = "Invalid time format for $day: '$slot'. Please use format like 9:00 AM.";
                        $_SESSION['alert_type'] = "danger";
                        header("Location: add_doctor.php");
                        exit();
                    }
                }
                if (!empty($slots)) {
                    $availability_data[$day] = $slots;
                }
            }
        }
        $availability_json = json_encode($availability_data);

        // Insert doctor into the `doctors` table
        $stmt = $conn->prepare("INSERT INTO doctors (full_name, email, password, phone, nmc_number, specialization, availability) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $full_name, $email, $password_hashed, $phone, $nmc_number, $specialization, $availability_json);

        if ($stmt->execute()) {
            $_SESSION['alert_message'] = "Doctor created successfully";
            $_SESSION['alert_type'] = "success";
            header("Location: manage_doctors.php");
            exit();
        } else {
            $_SESSION['alert_message'] = "Failed to create doctor";
            $_SESSION['alert_type'] = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Doctor</title>
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
                        <a class="nav-link active" href="add_doctor.php">Add Doctor</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_doctors.php">Doctors</a>
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

    <!-- Alert Message -->
    <?php if (isset($_SESSION['alert_message'])): ?>
        <div class="container mt-3">
            <div class="alert alert-<?= $_SESSION['alert_type'] ?? 'info' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['alert_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php unset($_SESSION['alert_message'], $_SESSION['alert_type']); ?>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Add New Doctor</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="full_name" class="form-control" required />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Enter a valid email address" />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password *</label>
                                <input type="password" name="password" class="form-control" required />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Re-enter Password *</label>
                                <input type="password" name="re_password" class="form-control" required />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone Number *</label>
                                <input type="text" name="phone" class="form-control" required pattern="\d{10}" maxlength="10" inputmode="numeric" title="Enter a 10-digit phone number (numbers only)" />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">NMC Number *</label>
                                <input type="text" name="nmc_number" class="form-control" required pattern="[a-zA-Z0-9]{6,12}" maxlength="12" title="6-12 alphanumeric characters" />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Specialization</label>
                                <input type="text" name="specialization" class="form-control" />
                            </div>
                            <!-- Availability Section for Doctor -->
                            <hr>
                            <h6 class="card-subtitle mb-2 text-muted">Availability</h6>
                            <p>Select the days the doctor is available and enter the time.</p>
                            <?php 
                            $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday',];
                            foreach ($daysOfWeek as $day): ?>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="availability_<?= $day ?>" name="availability[<?= $day ?>]" onclick="toggleTimeInput('<?= $day ?>')">
                                    <label class="form-check-label" for="availability_<?= $day ?>"><?= $day ?></label>
                                    <input type="text" class="form-control mt-2" id="time_<?= $day ?>" name="availability_times[<?= $day ?>]" placeholder="Enter time slots, e.g. 9:00 AM, 10:00 AM" disabled>
                                    <small class="text-muted">Separate multiple time slots with a comma.</small>
                                </div>
                            <?php endforeach; ?>
                            <button type="submit" name="create_doctor" class="btn btn-primary">
                                <i class="bi bi-person-plus me-1"></i>Create Doctor
                            </button>
                        </form>
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
    <script>
        // Enable or Disable time input when checkbox is checked/unchecked
        function toggleTimeInput(day) {
            const timeInput = document.getElementById('time_' + day);
            const checkbox = document.getElementById('availability_' + day);
            if (checkbox.checked) {
                timeInput.disabled = false;
            } else {
                timeInput.disabled = true;
            }
        }
        // Initialize the availability time input based on initial checkbox state
        document.addEventListener('DOMContentLoaded', function () {
            const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            daysOfWeek.forEach(day => {
                toggleTimeInput(day);
            });
        });
    </script>
</body>
</html>