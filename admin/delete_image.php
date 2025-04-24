<?php
session_start();
include("server/connection.php");

if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

if (isset($_GET["product_id"]) && isset($_GET["image_url"])) {
    $product_id = $_GET["product_id"];
    $image_url = $_GET["image_url"];

    // Fetch the current images for the product
    $stmt = $conn->prepare("SELECT images FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if ($product) {
        $images = json_decode($product['images'], true);

        // Remove the image from the array
        $updated_images = array_filter($images, function($img) use ($image_url) {
            return $img !== $image_url;
        });

        // Update the product with the new images
        $stmt = $conn->prepare("UPDATE products SET images = ? WHERE id = ?");
        $updated_images_json = json_encode(array_values($updated_images)); // Re-index array
        $stmt->bind_param("si", $updated_images_json, $product_id);

        if ($stmt->execute()) {
            $_SESSION['alert_message'] = "Image successfully deleted";
            $_SESSION['alert_type'] = "success";
        } else {
            $_SESSION['alert_message'] = "Error deleting image";
            $_SESSION['alert_type'] = "danger";
        }
    }
}

header("Location: manage_products.php");
exit();
?>