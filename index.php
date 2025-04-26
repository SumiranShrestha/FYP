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
  <meta name="viewport" content="width=device-width, initial-scale=1">
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
      box-shadow: 0px 4px 6px rgba(0,0,0,0.1);
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

    /* FAQ section wrapper */
    .faq-section {
      background-color: #f0fff4;
      padding: 60px 0;
    }

    /* Remove default accordion borders & spacing */
    .faq-section .accordion-item {
      border: none;
      margin-bottom: 16px;
      background: transparent;
    }

    /* Style the question button */
    .faq-section .accordion-button {
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.08);
      color: #111;
      font-weight: 500;
      padding: 1rem 1.5rem;
      transition: background-color .2s, box-shadow .2s;
    }

    /* Rotate the default arrow */
    .faq-section .accordion-button::after {
      transition: transform .2s;
    }

    /* Collapsed state (arrow points down) */
    .faq-section .accordion-button.collapsed::after {
      transform: rotate(0deg);
    }

    /* Expanded state styling */
    .faq-section .accordion-button:not(.collapsed) {
      background-color:rgb(255, 255, 255);
      box-shadow: none;
    }

    /* Style the revealed answer */
    .faq-section .accordion-collapse .accordion-body {
      background-color:rgb(255, 255, 255);
      border-radius: 0 0 8px 8px;
      margin-top: -1px; /* tuck under the header */
      padding: 1rem 1.5rem;
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
        <img src="<?= htmlspecialchars($banner['image_url']); ?>"
             class="d-block w-100"
             alt="<?= htmlspecialchars($banner['heading']); ?>">
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
      $productImage = !empty($images) ? htmlspecialchars($images[0]) : "default.jpg";
    ?>
      <div class="col">
        <div class="card h-100 product-card-custom">
          <a href="product-detail.php?id=<?= $product['id']; ?>">
            <img src="<?= $productImage; ?>"
                 class="card-img-top product-image"
                 alt="<?= htmlspecialchars($product['name']); ?>">
          </a>
          <div class="card-body text-center">
            <h5 class="card-title fw-bold mb-2" style="font-size: 1.1rem;"><?= htmlspecialchars($product['name']); ?></h5>
            <!-- Brand name removed -->
            <div class="mb-2">
              <?php if ($product['discount_price'] > 0): ?>
                <span class="text-muted text-decoration-line-through" style="font-size: 1.05rem;">
                  रू <?= number_format($product['price']); ?>
                </span>
                <span class="ms-2 fw-bold text-success" style="font-size: 1.15rem;">
                  रू <?= number_format($product['discount_price']); ?>
                </span>
              <?php else: ?>
                <span class="fw-bold text-success" style="font-size: 1.15rem;">
                  रू <?= number_format($product['price']); ?>
                </span>
              <?php endif; ?>
            </div>
            <?php if ($product['discount_price'] > 0): ?>
              <div class="mb-2">
                <span class="badge save-badge-custom">
                  SAVE <?= number_format($product['price'] - $product['discount_price']) ?>
                </span>
              </div>
            <?php endif; ?>
            <button class="btn btn-success mt-2 addToCartBtn"
                    data-product-id="<?= $product['id']; ?>">
              Add to Cart
            </button>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</section>
<style>
.product-image {
    width: 100%;
    transition: all .2s ease-in-out;
    height: 270px;
    object-fit: cover;
    background: #fff;
    border-radius: 10px 10px 0 0;
    padding: 10px;
    display: block;
}
.product-card-custom {
    border-radius: 16px;
    border: none !important;
    margin-bottom: 10px;
    background: #fff;
    overflow: hidden;

}
.product-card-custom .card-body {
    padding: 1.2rem 1rem 1.5rem 1rem;
}
.card-title {
    font-weight: 700;
    color: #444;
    min-height: 48px;
    margin-bottom: 0.5rem;
}
.save-badge-custom {
    background: #4caf50;
    color: #fff;
    font-weight: 700;
    border-radius: 20px;
    padding: 0.5em 1.2em;
    font-size: 1rem;
    letter-spacing: 1px;
    display: inline-block;
}
.text-success {
    color: #388e3c !important;
}
.text-decoration-line-through {
    color: #222 !important;
    opacity: 0.7;
}
.card {
    transition: box-shadow 0.3s ease;
}
.product-image:hover {
    transform: scale(1.05);
    z-index: 1;
}
@media (max-width: 991px) {
    .product-image {
        height: 180px;
    }
}
</style>

<script>
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
      .then(res => res.json())
      .then(data => {
        let badge = document.getElementById("cartBadge");
        badge.textContent = data.total_items;
        badge.style.display = data.total_items > 0 ? "inline-block" : "none";
      });
  }

  document.querySelectorAll(".addToCartBtn").forEach(btn => {
    btn.addEventListener("click", function() {
      let pid = this.dataset.productId;
      fetch("server/add_to_cart.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `product_id=${pid}&quantity=1`
      })
      .then(res => res.json())
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
            <button class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapse<?= $faq['id']; ?>"
                    aria-expanded="false"
                    aria-controls="collapse<?= $faq['id']; ?>">
              <?= htmlspecialchars($faq['question']); ?>
            </button>
          </h2>
          <div id="collapse<?= $faq['id']; ?>"
               class="accordion-collapse collapse"
               aria-labelledby="heading<?= $faq['id']; ?>"
               data-bs-parent="#faqAccordion">
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

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
