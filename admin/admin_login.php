<?php
session_start();
include("server/connection.php");

$error = ""; // Initialize error variable

// Redirect if already logged in
if (isset($_SESSION["admin_logged_in"]) && $_SESSION["admin_logged_in"] === true) {
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Prepare and execute SQL query
    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    // Remove debugging code from production
    // if ($admin) {
    //     echo "<p>Admin data fetched: " . print_r($admin, true) . "</p>";
    //     echo "<p>Hashed Password from Database: " . $admin['password'] . "</p>";
    // }

    // Check if the user exists and the password is correct
    if ($admin) {
        if (password_verify($password, $admin['password'])) {
            $_SESSION["admin_logged_in"] = true;
            $_SESSION["admin_username"] = $admin["username"];
            
            // Set a welcome message
            $_SESSION["login_success"] = "Welcome back, " . $admin["username"] . "!";
            
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "No admin found with that username!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Dashboard</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 450px;
            width: 90%;
            padding: 2.5rem;
            border-radius: 12px;
            background-color: white;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }
        .login-header {
            text-align: center;
            margin-bottom: 1.8rem;
        }
        .company-logo {
            width: 80px;
            height: 80px;
            background-color: #3B82F6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            border-radius: 50%;
            margin: 0 auto 1rem;
        }
        .login-title {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        .login-subtitle {
            color: #64748b;
            font-size: 0.95rem;
        }
        .form-control {
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .input-group-text {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-right: none;
            color: #64748b;
        }
        .input-group .form-control {
            border-left: none;
        }
        .btn-primary {
            background-color: #3B82F6;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        .form-check-input:checked {
            background-color: #3B82F6;
            border-color: #3B82F6;
        }
        .forgot-link {
            color: #3B82F6;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        .forgot-link:hover {
            color: #2563eb;
            text-decoration: underline;
        }
        .login-footer {
            text-align: center;
            font-size: 0.9rem;
            color: #64748b;
            margin-top: 2rem;
        }
        .shake {
            animation: shake 0.82s cubic-bezier(.36,.07,.19,.97) both;
        }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
    </style>
</head>
<body>

<div class="login-container <?php if($error) echo 'shake'; ?>">
    <div class="login-header">
        <div class="company-logo">
            <i class="bi bi-shield-lock"></i>
        </div>
        <h2 class="login-title">Admin Dashboard</h2>
        <p class="login-subtitle">Sign in to access your admin panel</p>
    </div>
    
    <!-- Display error message if login failed -->
    <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div><?php echo $error; ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" class="needs-validation" novalidate>
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="username" id="username" class="form-control" placeholder="Enter your username" required>
            </div>
            <div class="invalid-feedback">Please enter your username</div>
        </div>
        
        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-key"></i></span>
                <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                    <i class="bi bi-eye" id="toggleIcon"></i>
                </button>
            </div>
            <div class="invalid-feedback">Please enter your password</div>
        </div>
        
       
        
        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </button>
    </form>
    
    <div class="login-footer">
        <p>&copy; <?php echo date('Y'); ?> Your Company. All rights reserved.</p>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('bi-eye');
            toggleIcon.classList.add('bi-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('bi-eye-slash');
            toggleIcon.classList.add('bi-eye');
        }
    });
    
    // Form validation
    (function () {
        'use strict'
        
        const forms = document.querySelectorAll('.needs-validation');
        
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>

</body>
</html>