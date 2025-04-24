<?php
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
$stmt = $conn->prepare("SELECT p.*, b.brand_name FROM products p JOIN brands b ON p.brand_id = b.id WHERE p.id = ?");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']); ?> | Shady Shades</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <style>
        /* Floating Cart Icon */
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
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
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

        /* Smaller Quantity Selector */
        #quantitySelect {
            width: 60px;
            text-align: center;
            font-size: 14px;
            padding: 5px;
        }

        /* Reduce card size */
        .product-card {
            transform: scale(0.9); /* Reduce size by 10% */
            margin: auto;
            padding: 10px;
        }
    </style>
</head>
<body>

<!-- Floating Cart Icon -->
<div id="floatingCart" data-bs-toggle="offcanvas" data-bs-target="#cartDrawer">
    <i class="bi bi-cart-fill"></i>
    <span id="cartBadge">0</span>
</div>

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
    <div class="row">
        <div class="col-md-6">
            <div id="productCarousel" class="carousel slide border rounded shadow-sm product-card" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php foreach ($images as $index => $img) { ?>
                        <div class="carousel-item <?= $index === 0 ? 'active' : ''; ?>">
                            <img src="<?= htmlspecialchars($img); ?>" alt="Product Image" class="d-block w-100 rounded">
                        </div>
                    <?php } ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            </div>

            <!-- Thumbnail Preview -->
            <div class="mt-3 d-flex justify-content-center">
                <?php foreach ($images as $index => $img) { ?>
                    <img src="<?= htmlspecialchars($img); ?>" class="img-thumbnail mx-1" style="width: 60px; cursor: pointer;" onclick="setCarouselSlide(<?= $index; ?>)">
                <?php } ?>
            </div>
        </div>

        <div class="col-md-6">
            <h1><?= htmlspecialchars($product['name']); ?></h1>
            <p class="text-muted"><?= htmlspecialchars($product['brand_name']); ?></p>
            <p>
                <?php if ($product['discount_price'] > 0): ?>
                    <span class="text-muted text-decoration-line-through">Rs <?= number_format($product['price']); ?></span>
                    <span class="ms-2 fw-bold text-success">Rs <?= number_format($product['discount_price']); ?></span>
                <?php else: ?>
                    <span class="fw-bold text-success">Rs <?= number_format($product['price']); ?></span>
                <?php endif; ?>
            </p>

            <!-- Product Description -->
            <p class="mt-3"><?= nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?></p>

            <div class="input-group mb-3">
                <input type="number" id="quantitySelect" class="form-control" value="1" min="1">
            </div>

            <button class="btn btn-success w-100 add-to-cart-btn" data-id="<?= $product_id; ?>">Add to Cart</button>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script> 
    function setCarouselSlide(index) {
        let carousel = new bootstrap.Carousel(document.getElementById('productCarousel'));
        carousel.to(index);
    }
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

    function updateCart() {
        fetch("server/fetch_cart.php")
            .then(response => response.json())
            .then(data => {
                let container = document.getElementById("cartItemsContainer");
                let total = 0;
                container.innerHTML = "";

                if (data.items.length === 0) {
                    container.innerHTML = "<p class='text-muted text-center'>Cart is empty</p>";
                    document.getElementById("cartTotal").textContent = "Rs 0";
                    document.getElementById("cartBadge").style.display = "none";
                    return;
                }

                data.items.forEach(item => {
                    let itemPrice = parseFloat(item.price.replace(/,/g, '')) || 0;
                    let itemQuantity = parseInt(item.quantity) || 1;
                    let itemTotal = itemPrice * itemQuantity;
                    total += itemTotal;

                    let cartItem = `
                        <div class="cart-item" id="cart-item-${item.id}">
                            <img src="${item.image}" alt="Product Image">
                            <div>
                                <p class="mb-0">${item.name}</p>
                                <small>Rs ${itemPrice.toLocaleString()} x ${itemQuantity}</small>
                            </div>
                            <button class="btn btn-sm btn-danger remove-item" data-id="${item.id}">✕</button>
                        </div>
                    `;
                    container.innerHTML += cartItem;
                });

                document.getElementById("cartTotal").textContent = `Rs ${total.toLocaleString()}`;
                document.getElementById("cartBadge").textContent = data.items.length;
                document.getElementById("cartBadge").style.display = "inline-block";
            })
            .catch(error => console.error("Error fetching cart:", error));
    }

    document.querySelector(".add-to-cart-btn").addEventListener("click", function() {
        let productId = this.getAttribute("data-id");
        let quantity = document.getElementById("quantitySelect").value;

        fetch("server/add_to_cart.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `product_id=${productId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                showToast("Product added to cart!", "success");
                updateCart();
            } else {
                showToast("Failed to add product. Try again!", "danger");
            }
        })
        .catch(() => showToast("Error adding to cart!", "danger"));
    });

    document.getElementById("cartItemsContainer").addEventListener("click", function(event) {
        if (event.target.classList.contains("remove-item")) {
            let productId = event.target.getAttribute("data-id");

            fetch("server/remove_from_cart.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    showToast("Product removed from cart!", "warning");
                    updateCart();
                } else {
                    showToast("Failed to remove item!", "danger");
                }
            })
            .catch(() => showToast("Error removing item!", "danger"));
        }
    });

    updateCart();
});

</script>

<?php include('footer.php'); ?>
</body>
</html>
