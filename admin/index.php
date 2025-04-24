<?php
session_start();
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 20px; }
        .container { max-width: 600px; margin: auto; }
        .card { padding: 20px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        a { text-decoration: none; color: white; padding: 10px 20px; display: block; margin: 10px 0; border-radius: 5px; }
        .btn-users { background: #007bff; }
        .btn-products { background: #28a745; }
        .btn-logout { background: #dc3545; }
    </style>
</head>
<body>

    <div class="container">
        <h1>Welcome, Admin</h1>
        <p>Manage your website from here.</p>

        <div class="card">
            <h3>Manage Users</h3>
            <a href="admin/manage_users.php" class="btn-users">Go to User Management</a>
        </div>

        <div class="card">
            <h3>Manage Products</h3>
            <a href="admin/manage_products.php" class="btn-products">Go to Product Management</a>
        </div>

        <div class="card">
            <h3>Logout</h3>
            <a href="admin/admin_logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

</body>
</html>
