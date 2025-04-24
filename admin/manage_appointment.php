<?php
session_start();
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

include('server/connection.php'); 

// Verify that the correct 'action' and 'id' are provided in the query string.
if (!isset($_GET['action']) || $_GET['action'] !== 'edit' || !isset($_GET['id'])) {
    header("Location: view_appointment.php");
    exit();
}

$appointment_id = intval($_GET['id']);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input data.
    $appointment_date_input = $_POST['appointment_date'] ?? '';
    $status = $_POST['status'] ?? '';

    // Basic validation
    if (empty($appointment_date_input) || empty($status)) {
        $error = "All fields are required.";
    } 

    if (!isset($error)) {
        // Convert the HTML5 datetime-local input (format "YYYY-MM-DDTHH:MM") to MySQL DATETIME format ("YYYY-MM-DD HH:MM:SS")
        $appointment_date = str_replace("T", " ", $appointment_date_input) . ":00";
        
        // Prepare and execute the update statement using prepared statements to avoid SQL injection.
        $stmt = $conn->prepare("UPDATE appointments SET appointment_date = ?, status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ssi", $appointment_date, $status, $appointment_id);
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: view_appointment.php?success=Appointment updated successfully.");
                exit();
            } else {
                $error = "Failed to update the appointment.";
            }
        } else {
            $error = "Database error: Failed to prepare the statement.";
        }
    }
}

// Retrieve the existing appointment details
$stmt = $conn->prepare("SELECT * FROM appointments WHERE id = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: view_appointment.php?error=Appointment not found.");
    exit();
}
$appointment = $result->fetch_assoc();
$stmt->close();

// Convert the appointment_date to a format acceptable by an HTML5 datetime-local input.
// The required format is "YYYY-MM-DDTHH:MM".
$datetime_local = date("Y-m-d\TH:i", strtotime($appointment['appointment_date']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Appointment</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- (Optional) Bootstrap Icons if used in the navbar -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <!-- Navbar similar to the Admin Dashboard -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="view_appointment.php">Appointments</a>
                    </li>
                    <!-- Other navigation items can be added here if needed -->
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
        <h2 class="mb-4">Manage Appointment (ID: <?php echo htmlspecialchars($appointment['id']); ?>)</h2>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <!-- Appointment Date & Time -->
            <div class="mb-3">
                <label for="appointment_date" class="form-label">Appointment Date & Time</label>
                <input type="datetime-local" name="appointment_date" id="appointment_date" class="form-control" value="<?php echo htmlspecialchars($datetime_local); ?>" required>
            </div>
            <!-- Appointment Status -->
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select" required>
                    <?php 
                    $statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
                    foreach ($statuses as $s): 
                    ?>
                        <option value="<?php echo $s; ?>" <?php if ($appointment['status'] == $s) echo "selected"; ?>>
                            <?php echo ucfirst($s); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Form Actions -->
            <button type="submit" class="btn btn-primary">Update Appointment</button>
            <a href="view_appointment.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
