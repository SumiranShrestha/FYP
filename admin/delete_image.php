<?php
session_start();
include("server/connection.php");

if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

if (isset($_GET['product_id']) && isset($_GET['image_url'])) {
    $product_id = intval($_GET['product_id']);
    $image_url = $_GET['image_url'];

    // Fetch current images
    $stmt = $conn->prepare("SELECT images FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if ($product) {
        $images = json_decode($product['images'], true);
        if (($key = array_search($image_url, $images)) !== false) {
            unset($images[$key]);
            // Remove file from server
            if (file_exists($image_url)) {
                unlink($image_url);
            }
            // Update images in DB
            $images_json = json_encode(array_values($images));
            $stmt = $conn->prepare("UPDATE products SET images = ? WHERE id = ?");
            $stmt->bind_param("si", $images_json, $product_id);
            $stmt->execute();
            $_SESSION['alert_message'] = "Image deleted successfully.";
            $_SESSION['alert_type'] = "success";
        }
    }
}

// Always redirect back to edit_product.php
header("Location: edit_product.php?id=" . $product_id);
exit();
?>