<?php
session_start();
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

include('server/connection.php'); // Include database connection

if (isset($_GET['id'])) {
    $doctor_id = $_GET['id'];

    // Fetch doctor details
    $stmt = $conn->prepare("SELECT * FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $doctor = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_doctor'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $nmc_number = $_POST['nmc_number'];
    $specialization = $_POST['specialization'];

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['alert_message'] = "Please enter a valid email address.";
        $_SESSION['alert_type'] = "danger";
        header("Location: edit_doctor.php?id=" . urlencode($doctor_id));
        exit();
    }

    // Phone number validation: exactly 10 digits, numbers only
    if (!preg_match('/^\d{10}$/', $phone)) {
        $_SESSION['alert_message'] = "Phone number must be exactly 10 digits and contain only numbers.";
        $_SESSION['alert_type'] = "danger";
        header("Location: edit_doctor.php?id=" . urlencode($doctor_id));
        exit();
    }

    // NMC number validation: alphanumeric, 6-12 chars
    if (!preg_match('/^[a-zA-Z0-9]{6,12}$/', $nmc_number)) {
        $_SESSION['alert_message'] = "NMC Number must be 6-12 alphanumeric characters.";
        $_SESSION['alert_type'] = "danger";
        header("Location: edit_doctor.php?id=" . urlencode($doctor_id));
        exit();
    }

    // Get availability data from the form
    $availability = isset($_POST['availability']) ? $_POST['availability'] : [];
    $availability_times = isset($_POST['availability_times']) ? $_POST['availability_times'] : [];

    // Format the availability data as a JSON string (support multiple time slots per day)
    $availability_data = [];
    foreach ($availability as $day => $checked) {
        if (isset($availability_times[$day])) {
            // Accept comma-separated or array input, always store as array
            $times = $availability_times[$day];
            if (!is_array($times)) {
                // Split by comma and trim
                $slots = array_filter(array_map('trim', explode(',', $times)));
            } else {
                $slots = array_filter(array_map('trim', $times));
            }
            if (!empty($slots)) {
                $availability_data[$day] = $slots;
            }
        }
    }
    $availability_json = json_encode($availability_data);

    // Update doctor
    $stmt = $conn->prepare("UPDATE doctors SET full_name = ?, email = ?, phone = ?, nmc_number = ?, specialization = ?, availability = ? WHERE id = ?");
    $stmt->bind_param("ssssssi", $full_name, $email, $phone, $nmc_number, $specialization, $availability_json, $doctor_id);

    if ($stmt->execute()) {
        $_SESSION['alert_message'] = "Doctor updated successfully";
        $_SESSION['alert_type'] = "success";
        header("Location: manage_doctors.php");
        exit();
    } else {
        $_SESSION['alert_message'] = "Failed to update doctor";
        $_SESSION['alert_type'] = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Doctor</title>
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
                        <a class="nav-link active" href="manage_doctors.php">Doctors</a>
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
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Edit Doctor</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($doctor['full_name']); ?>" required />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($doctor['email']); ?>" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Enter a valid email address" />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone Number *</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($doctor['phone']); ?>" required pattern="\d{10}" maxlength="10" inputmode="numeric" title="Enter a 10-digit phone number (numbers only)" />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">NMC Number *</label>
                                <input type="text" name="nmc_number" class="form-control" value="<?= htmlspecialchars($doctor['nmc_number']); ?>" required pattern="[a-zA-Z0-9]{6,12}" maxlength="12" title="6-12 alphanumeric characters" />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Specialization</label>
                                <input type="text" name="specialization" class="form-control" value="<?= htmlspecialchars($doctor['specialization']); ?>" />
                            </div>

                            <!-- Availability Section for Doctor -->
                            <hr>
                            <h6 class="card-subtitle mb-2 text-muted">Availability</h6>
                            <p>Select the days the doctor is available and enter the time.</p>

                            <?php
                            $availability = json_decode($doctor['availability'] ?? '{}', true) ?? [];
                            $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                            ?>

                            <!-- Checkboxes for days of the week -->
                            <?php foreach ($daysOfWeek as $day):
                                $isAvailable = isset($availability[$day]) ? 'checked' : '';
                                $timeArr = $availability[$day] ?? [];
                                $timeValue = is_array($timeArr) ? implode(', ', $timeArr) : $timeArr;
                            ?>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="availability_<?= $day ?>" name="availability[<?= $day ?>]" <?= $isAvailable ?> onclick="toggleTimeInput('<?= $day ?>')">
                                    <label class="form-check-label" for="availability_<?= $day ?>"><?= $day ?></label>
                                    <!-- Input field for time slots, comma separated -->
                                    <input type="text" class="form-control mt-2" id="time_<?= $day ?>" name="availability_times[<?= $day ?>]" value="<?= htmlspecialchars($timeValue) ?>" placeholder="Enter time slots, e.g. 9:00 AM, 10:00 AM" <?= !$isAvailable ? 'disabled' : '' ?>>
                                    <small class="text-muted">Separate multiple time slots with a comma.</small>
                                </div>
                            <?php endforeach; ?>

                            <button type="submit" name="update_doctor" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Update Doctor
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
        // Enable or Disable time input when checkbox is checked/unchecked
        function toggleTimeInput(day) {
            const timeInput = document.getElementById('time_' + day);
            const checkbox = document.getElementById('availability_' + day);

            // Enable/Disable the time input based on checkbox status
            if (checkbox.checked) {
                timeInput.disabled = false;
            } else {
                timeInput.disabled = true;
            }
        }

        // Initialize the availability time input based on initial checkbox state
        document.addEventListener('DOMContentLoaded', function() {
            const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            daysOfWeek.forEach(day => {
                toggleTimeInput(day); // Initialize each checkbox time input
            });
        });

        // Logout confirmation logic
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('logoutBtn').addEventListener('click', function(e) {
                e.preventDefault();
                var modal = new bootstrap.Modal(document.getElementById('logoutConfirmModal'));
                modal.show();
            });
        });
    </script>
</body>

</html>