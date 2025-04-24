<?php
session_start();
include("connection.php");
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (!empty($email) && !empty($password)) {
        // Check if doctor exists (ensure your column names match your DB)
        $stmt = $conn->prepare("SELECT id, full_name, password FROM doctors WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $doctor = $result->fetch_assoc();
            if (password_verify($password, $doctor["password"])) {
                // Set session variables
                $_SESSION["user_id"] = $doctor["id"];
                $_SESSION["user_name"] = $doctor["full_name"];
                $_SESSION["user_type"] = "doctor";
                
                echo json_encode(["status" => "success", "message" => "Login successful", "redirect" => "../doctor_dashboard.php"]);
                exit();
            } else {
                echo json_encode(["status" => "error", "message" => "Invalid email or password!"]);
                exit();
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Doctor not found!"]);
            exit();
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Please fill in all fields!"]);
        exit();
    }
}
?>
