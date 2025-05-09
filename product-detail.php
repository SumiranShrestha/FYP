<?php
// product_detail.php

include('header.php');

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

// Database connection
include('server/connection.php');

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch product details
$stmt = $conn->prepare("
    SELECT p.*, b.brand_name, p.facial_structure
    FROM products p 
    JOIN brands b ON p.brand_id = b.id 
    WHERE p.id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

// Ensure product exists
if (!$product) {
  echo "<p class='text-center text-danger'>Product not found.</p>";
  include('footer.php');
  exit();
}

// Decode JSON images
$images = json_decode($product['images'], true);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($product['name']); ?> | Shady Shades</title>

  <!-- Bootstrap CSS & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    /* Floating Cart Icon */
    /* Removed #floatingCart and #cartBadge styles */
    /*
    #floatingCart {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: #28a745;
      color: white;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      cursor: pointer;
      z-index: 1050;
    }

    #cartBadge {
      position: absolute;
      top: 5px;
      right: 5px;
      background: red;
      color: white;
      font-size: 14px;
      font-weight: bold;
      padding: 5px 8px;
      border-radius: 50%;
      display: none;
    }
    */
    /* Offcanvas Cart Drawer */
    .cart-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;
      padding-bottom: 10px;
      border-bottom: 1px solid #ddd;
    }

    .cart-item img {
      width: 50px;
      height: 50px;
      object-fit: contain;
      border-radius: 5px;
      margin-right: 10px;
    }

    .cart-footer {
      border-top: 1px solid #ddd;
      padding-top: 15px;
    }

    /* Quantity Selector */
    .qty-selector-group {
      display: flex;
      gap: 7px;
      align-items: center;
      margin-bottom: 1.5rem;
    }

    .qty-btn,
    #quantitySelect {
      width: 55px;
      height: 55px;
      font-size: 1.5rem;
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      box-shadow: none;
      outline: none;
      transition: border 0.15s;
    }

    .qty-btn {
      cursor: pointer;
      user-select: none;
      font-weight: 400;
      color: #222;
      padding: 0;
    }

    .qty-btn:active {
      border-color: #21b573;
    }

    #quantitySelect {
      pointer-events: none;
      border: 1px solid #e5e7eb;
      background: #fff;
      font-weight: 500;
      color: #222;
      font-size: 1.5rem;
    }

    .btn-out-of-stock {
      background: #f5f6f7 !important;
      color: #b0b7bc !important;
      border: none !important;
      font-weight: 700;
      letter-spacing: 1px;
      pointer-events: none;
      box-shadow: none !important;
    }

    /* Product Detail */
    .product-detail-imagebox {
      width: 100%;
      max-width: 520px;
      aspect-ratio: 1/1;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 2px 16px rgba(0, 0, 0, 0.07);
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }

    .main-product-image {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 16px;
      transition: box-shadow 0.2s;
    }

    .product-thumbs-row {
      margin-top: 10px;
      gap: 18px !important;
    }

    .thumb-wrapper {
      border-radius: 10px;
      overflow: hidden;
      border: 2px solid transparent;
      transition: border 0.18s;
    }

    .product-thumb-img {
      width: 90px;
      height: 90px;
      object-fit: cover;
      border-radius: 10px;
      cursor: pointer;
      border: 2px solid transparent;
      transition: border 0.18s, box-shadow 0.18s;
      background: #fff;
      box-shadow: 0 1px 6px rgba(0, 0, 0, 0.06);
    }

    .product-thumb-img.active-thumb,
    .product-thumb-img:hover {
      border: 2px solid #21b573 !important;
      box-shadow: 0 0 0 2px #b2f5ea;
    }

    @media (max-width: 991px) {

      .main-product-image,
      .product-detail-imagebox {
        max-width: 100vw;
        height: auto;
      }

      .product-thumb-img {
        width: 60px;
        height: 60px;
      }
    }

    .btn-out-of-stock {
      background: #f5f6f7 !important;
      color: #b0b7bc !important;
      border: none !important;
      font-weight: 700;
      letter-spacing: 1px;
      pointer-events: none;
      box-shadow: none !important;
    }
  </style>
</head>

<body>

  <!-- Offcanvas Cart Drawer -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="cartDrawer">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title">Your Cart</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
      <div id="cartItemsContainer">
        <p class="text-muted text-center">Cart is empty</p>
      </div>
    </div>
    <div class="offcanvas-footer cart-footer p-3">
      <div class="d-flex justify-content-between">
        <strong>Total:</strong>
        <span id="cartTotal">Rs 0</span>
      </div>
      <div class="mt-3">
        <a href="cart.php" class="btn btn-outline-primary w-100">View Cart</a>
        <a href="checkout.php" class="btn btn-success w-100 mt-2">Checkout</a>
      </div>
    </div>
  </div>

  <div class="container mt-5">
    <div class="row justify-content-center align-items-start g-5">
      <!-- Images Left -->
      <div class="col-lg-6 d-flex flex-column align-items-center">
        <div class="product-detail-imagebox mb-3">
          <img
            id="mainProductImage"
            src="<?= htmlspecialchars($images[0]); ?>"
            alt="Product Image"
            class="main-product-image">
        </div>
        <div class="d-flex flex-wrap gap-3 justify-content-center product-thumbs-row">
          <?php foreach ($images as $index => $img): ?>
            <div class="thumb-wrapper">
              <img
                src="<?= htmlspecialchars($img); ?>"
                class="product-thumb-img <?= $index === 0 ? 'active-thumb' : '' ?>"
                onclick="setMainImage(this, '<?= htmlspecialchars($img); ?>')">
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Product Details Right -->
      <div class="col-lg-6">
        <h2 class="fw-bold mb-2" style="font-size:2rem;"><?= htmlspecialchars($product['name']); ?></h2>

        <!-- Price Section -->
        <div class="d-flex align-items-center mb-2 gap-3">
          <?php if ($product['discount_price'] > 0): ?>
            <span class="text-muted text-decoration-line-through" style="font-size:1.15rem;">
              ₹<?= number_format($product['price']) ?>
            </span>
            <span class="fw-bold text-success" style="font-size:2rem;">
              ₹<?= number_format($product['discount_price']) ?>
            </span>
            <span class="badge bg-success rounded-pill ms-2" style="font-size:1rem;font-weight:600;">
              <?= round(100 - ($product['discount_price'] / $product['price'] * 100)) ?>% OFF
            </span>
          <?php else: ?>
            <span class="fw-bold text-success" style="font-size:2rem;">
              ₹<?= number_format($product['price']) ?>
            </span>
          <?php endif; ?>
        </div>
        <div class="mb-2" style="color:#888;font-size:0.95rem;">
          Shipping is calculated at checkout
        </div>
        <!-- Facial Structure Compatibility -->
        <?php if (isset($product['facial_structure']) && $product['facial_structure'] != ''): ?>
          <div class="d-flex align-items-center mb-3 mt-3">
            <div class="fw-bold me-2" style="font-size:1.1rem;">Best For Face Shape:</div>
            <div class="d-flex flex-wrap gap-2">
              <?php
              $faceShape = $product['facial_structure'];
              if ($faceShape == 'all') {
                echo '<span class="badge bg-success p-2">Suits All Face Shapes</span>';
              } else {
                $faceShapes = ['round', 'oval', 'square', 'heart', 'diamond', 'triangle'];
                foreach ($faceShapes as $shape) {
                  $active = ($faceShape == $shape) ? 'bg-success' : 'bg-secondary';
                  echo '<span class="badge ' . $active . ' p-2">' . ucfirst($shape) . '</span>';
                }
              }
              ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Quantity & Add to Cart -->
        <div class="qty-selector-group">
          <button class="qty-btn" type="button" onclick="changeQty(-1)">−</button>
          <input type="number" id="quantitySelect" value="1" min="1" readonly>
          <button class="qty-btn" type="button" onclick="changeQty(1)">+</button>
          <button
            class="btn btn-success flex-grow-1 fw-bold ms-2 add-to-cart-btn"
            data-id="<?= $product_id; ?>"
            data-stock="<?= (int)$product['stock']; ?>"
            style="height:64px;font-size:1.1rem;">
            ADD TO CART
          </button>
        </div>

        <!-- Description -->
        <div class="mb-3">
          <div class="d-flex align-items-center mb-2" style="color:#21b573;font-size:1.1rem;">
            <i class="bi bi-info-circle me-2"></i>
            <span class="fw-semibold">Description</span>
          </div>
          <div style="font-size:1.05rem;">
            <strong><?= htmlspecialchars($product['name']); ?></strong><br>
            <?= nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?>
          </div>
        </div>


      </div>
    </div>
  </div>

  <?php include('footer.php'); ?>

  <!-- Custom JS -->
  <script>
    function setMainImage(el, src) {
      document.getElementById('mainProductImage').src = src;
      document.querySelectorAll('.product-thumb-img').forEach(img => {
        img.classList.remove('active-thumb');
      });
      el.classList.add('active-thumb');
    }

    function changeQty(delta) {
      const qtyInput = document.getElementById('quantitySelect');
      let val = parseInt(qtyInput.value, 10) || 1;
      val = Math.max(1, val + delta);
      qtyInput.value = val;
      const addToCartBtn = document.querySelector('.add-to-cart-btn');
      const availableStock = parseInt(addToCartBtn.getAttribute('data-stock'), 10);

      if (val > availableStock || availableStock === 0) {
        addToCartBtn.textContent = "OUT OF STOCK";
        addToCartBtn.classList.remove("btn-success");
        addToCartBtn.classList.add("btn-out-of-stock");
        addToCartBtn.disabled = true;
      } else {
        addToCartBtn.textContent = "ADD TO CART";
        addToCartBtn.classList.remove("btn-out-of-stock");
        addToCartBtn.classList.add("btn-success");
        addToCartBtn.disabled = false;
      }
    }

    document.addEventListener("DOMContentLoaded", function() {
      function showToast(message, type = "success") {
        const toast = document.createElement("div");
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

      function updateCart() {
        fetch("server/fetch_cart.php")
          .then(res => res.json())
          .then(data => {
            const container = document.getElementById("cartItemsContainer");
            let total = 0;
            container.innerHTML = "";

            if (data.items.length === 0) {
              container.innerHTML = "<p class='text-muted text-center'>Cart is empty</p>";
              document.getElementById("cartTotal").textContent = "Rs 0";
              document.getElementById("cartBadge").style.display = "none";
              return;
            }

            data.items.forEach(item => {
              const price = parseFloat(item.price.replace(/,/g, '')) || 0;
              const qty = parseInt(item.quantity, 10) || 1;
              total += price * qty;
              container.insertAdjacentHTML('beforeend', `
                <div class="cart-item" id="cart-item-${item.id}">
                  <img src="${item.image}" alt="${item.name}">
                  <div>
                    <p class="mb-0">${item.name}</p>
                    <small>Rs ${price.toLocaleString()} x ${qty}</small>
                  </div>
                  <button class="btn btn-sm btn-danger remove-item" data-id="${item.id}">✕</button>
                </div>
              `);
            });

            document.getElementById("cartTotal").textContent = `Rs ${total.toLocaleString()}`;
            const badge = document.getElementById("cartBadge");
            badge.textContent = data.items.length;
            badge.style.display = "inline-block";
          })
          .catch(console.error);
      }

      document.querySelector(".add-to-cart-btn").addEventListener("click", function() {
        const pid = this.dataset.id;
        const qty = document.getElementById("quantitySelect").value;
        fetch("server/add_to_cart.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded"
            },
            body: `product_id=${pid}&quantity=${qty}`
          })
          .then(res => res.json())
          .then(data => {
            if (data.status === "success") {
              showToast("Product added to cart!", "success");
              updateCart();
            } else {
              showToast("Failed to add product.", "danger");
            }
          })
          .catch(() => showToast("Error adding to cart!", "danger"));
      });

      document.getElementById("cartItemsContainer").addEventListener("click", function(e) {
        if (e.target.classList.contains("remove-item")) {
          const pid = e.target.dataset.id;
          fetch("server/remove_from_cart.php", {
              method: "POST",
              headers: {
                "Content-Type": "application/x-www-form-urlencoded"
              },
              body: `product_id=${pid}`
            })
            .then(res => res.json())
            .then(data => {
              if (data.status === "success") {
                showToast("Removed from cart!", "warning");
                updateCart();
              } else {
                showToast("Failed to remove item.", "danger");
              }
            })
            .catch(() => showToast("Error removing item!", "danger"));
        }
      });

      updateCart();
      // On page load, check if out of stock
      changeQty(0);
    });
  </script>

  <!-- Bootstrap JS bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>