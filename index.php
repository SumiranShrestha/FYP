<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include('server/connection.php');

// Fetch banners
$banners = $conn->query("SELECT * FROM banners ORDER BY id ASC");

// Fetch products (Stock Clearance Sale)
$products = $conn->query("
    SELECT p.*, b.brand_name 
    FROM products p 
    LEFT JOIN brands b ON p.brand_id = b.id 
    ORDER BY p.created_at DESC 
    LIMIT 8
");

// Fetch categories
$categories = $conn->query("SELECT * FROM categories ORDER BY id ASC");

// Fetch featured brands
$brands = $conn->query("SELECT * FROM brands ORDER BY id ASC LIMIT 3");

// Fetch FAQs
$faqs = $conn->query("SELECT * FROM faqs ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shady Shades - Home</title>

    <!-- Bootstrap & Toastr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <style>
        /* Sticky Header */
        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 1020;
            background: white;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Hero Banner */
        #hero-section {
            height: 400px;
            overflow: hidden;
            background: black;
        }
        #hero-section .carousel-item {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 400px;
        }
        #hero-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
    </style>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    

<?php include('header.php'); ?>

<!-- Hero Banners -->
<section id="hero-section" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
        <?php $active = true; while ($banner = $banners->fetch_assoc()): ?>
            <div class="carousel-item <?= $active ? 'active' : ''; ?>">
                <img src="<?= htmlspecialchars($banner['image_url']); ?>" class="d-block w-100" alt="<?= htmlspecialchars($banner['heading']); ?>">
            </div>
            <?php $active = false; endwhile; ?>
    </div>
</section>

<!-- Stock Clearance Sale -->
<section class="container product-section py-5">
    <h2 class="text-center mb-4">🔥 Stock Clearance Sale 🔥</h2>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
        <?php while ($product = $products->fetch_assoc()): 
            $images = json_decode($product['images'], true);
            $productImage = !empty($images) ? htmlspecialchars($images[0]) : "default.jpg"; // Fallback Image
        ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <a href="product-detail.php?id=<?= $product['id']; ?>">
                        <img src="<?= $productImage; ?>" class="card-img-top product-image" alt="<?= htmlspecialchars($product['name']); ?>">
                    </a>
                    <div class="card-body text-center">
                        <h5 class="card-title"><?= htmlspecialchars($product['name']); ?></h5>
                        <p class="text-muted"><?= htmlspecialchars($product['brand_name']); ?></p>
                        <p class="card-text">
                            <?php if ($product['discount_price'] > 0): ?>
                                <span class="text-muted text-decoration-line-through">रू <?= number_format($product['price']); ?></span>
                                <span class="ms-2 fw-bold text-success">रू <?= number_format($product['discount_price']); ?></span>
                            <?php else: ?>
                                <span class="fw-bold text-success">रू <?= number_format($product['price']); ?></span>
                            <?php endif; ?>
                        </p>
                        <button class="btn btn-success mt-2 addToCartBtn" data-product-id="<?= $product['id']; ?>">Add to Cart</button>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</section>

</body><script>
document.addEventListener("DOMContentLoaded", function() {
    function showToast(message, type = "success") {
        let toast = document.createElement("div");
        toast.className = `toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
        toast.style.zIndex = "1050";
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        document.body.appendChild(toast);
        new bootstrap.Toast(toast).show();
        setTimeout(() => toast.remove(), 3000);
    }

    function updateCartCount() {
        fetch("server/fetch_cart.php")
            .then(response => response.json())
            .then(data => {
                document.getElementById("cartBadge").textContent = data.total_items;
                document.getElementById("cartBadge").style.display = data.total_items > 0 ? "inline-block" : "none";
            })
            .catch(error => console.error("Error updating cart:", error));
    }

    document.querySelectorAll(".addToCartBtn").forEach(button => {
        button.addEventListener("click", function() {
            let productId = this.getAttribute("data-product-id");

            fetch("server/add_to_cart.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    showToast("Product added to cart!", "success");
                    updateCartCount();
                } else {
                    showToast(data.message, "danger");
                }
            })
            .catch(() => showToast("Error adding to cart!", "danger"));
        });
    });

    updateCartCount();
});
</script>

<!-- FAQ Section -->
<section class="faq-section">
    <div class="container">
        <h2 class="text-center mb-4">Frequently Asked Questions</h2>
        <div class="accordion" id="faqAccordion">
            <?php while ($faq = $faqs->fetch_assoc()): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?= $faq['id']; ?>">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $faq['id']; ?>" aria-expanded="true" aria-controls="collapse<?= $faq['id']; ?>">
                            <?= htmlspecialchars($faq['question']); ?>
                        </button>
                    </h2>
                    <div id="collapse<?= $faq['id']; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $faq['id']; ?>" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <?= htmlspecialchars($faq['answer']); ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<?php include('footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
