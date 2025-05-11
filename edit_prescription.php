<?php
include('header.php');
require_once("server/connection.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$prescription_id = isset($_GET['prescription_id']) ? intval($_GET['prescription_id']) : 0;

// Fetch prescription
$stmt = $conn->prepare("SELECT * FROM prescription_frames WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $prescription_id, $user_id);
$stmt->execute();
$prescription = $stmt->get_result()->fetch_assoc();

if (!$prescription) {
    echo "<div class='container my-5'><div class='alert alert-danger'>Prescription not found or access denied.</div></div>";
    include('footer.php');
    exit;
}

// Handle update
$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $right_eye_sphere = $_POST['right_eye_sphere'];
    $right_eye_cylinder = $_POST['right_eye_cylinder'];
    $right_eye_axis = $_POST['right_eye_axis'];
    $left_eye_sphere = $_POST['left_eye_sphere'];
    $left_eye_cylinder = $_POST['left_eye_cylinder'];
    $left_eye_axis = $_POST['left_eye_axis'];

    $stmt = $conn->prepare("UPDATE prescription_frames SET right_eye_sphere=?, right_eye_cylinder=?, right_eye_axis=?, left_eye_sphere=?, left_eye_cylinder=?, left_eye_axis=? WHERE id=? AND user_id=?");
    $stmt->bind_param("ssssssii", $right_eye_sphere, $right_eye_cylinder, $right_eye_axis, $left_eye_sphere, $left_eye_cylinder, $left_eye_axis, $prescription_id, $user_id);
    if ($stmt->execute()) {
        $success = true;
        // Refresh data
        $prescription['right_eye_sphere'] = $right_eye_sphere;
        $prescription['right_eye_cylinder'] = $right_eye_cylinder;
        $prescription['right_eye_axis'] = $right_eye_axis;
        $prescription['left_eye_sphere'] = $left_eye_sphere;
        $prescription['left_eye_cylinder'] = $left_eye_cylinder;
        $prescription['left_eye_axis'] = $left_eye_axis;
    } else {
        $error = "Failed to update prescription. Please try again.";
    }
}
?>

<main class="container my-5" style="max-width: 600px;">
    <h2 class="mb-4 text-center">Edit Prescription</h2>
    <?php if ($success): ?>
        <div class="alert alert-success">Prescription updated successfully.</div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" class="card p-4 shadow-sm">
        <h5 class="mb-3">Right Eye</h5>
        <div class="row mb-3">
            <div class="col">
                <label class="form-label">Sphere (SPH)</label>
                <input type="text" name="right_eye_sphere" class="form-control" required value="<?= htmlspecialchars($prescription['right_eye_sphere']) ?>">
            </div>
            <div class="col">
                <label class="form-label">Cylinder (CYL)</label>
                <input type="text" name="right_eye_cylinder" class="form-control" required value="<?= htmlspecialchars($prescription['right_eye_cylinder']) ?>">
            </div>
            <div class="col">
                <label class="form-label">Axis</label>
                <input type="text" name="right_eye_axis" class="form-control" required value="<?= htmlspecialchars($prescription['right_eye_axis']) ?>">
            </div>
        </div>
        <h5 class="mb-3">Left Eye</h5>
        <div class="row mb-3">
            <div class="col">
                <label class="form-label">Sphere (SPH)</label>
                <input type="text" name="left_eye_sphere" class="form-control" required value="<?= htmlspecialchars($prescription['left_eye_sphere']) ?>">
            </div>
            <div class="col">
                <label class="form-label">Cylinder (CYL)</label>
                <input type="text" name="left_eye_cylinder" class="form-control" required value="<?= htmlspecialchars($prescription['left_eye_cylinder']) ?>">
            </div>
            <div class="col">
                <label class="form-label">Axis</label>
                <input type="text" name="left_eye_axis" class="form-control" required value="<?= htmlspecialchars($prescription['left_eye_axis']) ?>">
            </div>
        </div>
        <div class="d-flex justify-content-between">
            <a href="prescription-frames.php" class="btn btn-secondary">Back</a>
            <button type="submit" class="btn btn-primary">Update Prescription</button>
        </div>
    </form>
</main>

<?php include('footer.php'); ?>
