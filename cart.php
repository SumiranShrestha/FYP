<?php
include('header.php'); // Ensure session_start() is already handled in header.php
include('server/connection.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p class='text-center text-danger'>You must be logged in to view your cart.</p>";
    include('footer.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch cart items from database
$stmt = $conn->prepare("SELECT c.product_id, p.name, p.price, p.discount_price, p.images, p.stock, c.quantity 
                        FROM cart c 
                        JOIN products p ON c.product_id = p.id 
                        WHERE c.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
$total_price = 0;

while ($row = $result->fetch_assoc()) {
    $product_price = $row['discount_price'] > 0 ? $row['discount_price'] : $row['price'];
    $total_price += $product_price * $row['quantity'];
    $images = json_decode($row['images'], true);
    $image = $images[0] ?? 'default.jpg';

    $cart_items[] = [
        "id" => $row['product_id'],
        "name" => $row['name'],
        "price" => $product_price,
        "quantity" => $row['quantity'],
        "stock" => $row['stock'],  // Include stock
        "image" => $image
    ];
}

$shipping_cost = 100; // Fixed shipping cost
$final_total = $total_price + $shipping_cost;
?>

<div class="container my-5">
    <h2>Your Cart</h2>

    <!-- Toast Container -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050;">
        <div id="cartToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">Success!</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
        <div id="cartErrorToast" class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">Error!</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <?php if (!empty($cart_items)) { ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item) { ?>
                    <tr id="row-<?php echo $item['id']; ?>">
                        <td><img src="<?php echo htmlspecialchars($item['image']); ?>" width="50" height="50"></td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td>Rs <span id="price-<?php echo $item['id']; ?>"><?php echo number_format((float)$item['price']); ?></span></td>
                        <td>
                            <button class="btn btn-outline-secondary btn-sm update-cart" data-id="<?php echo $item['id']; ?>" data-action="decrease">-</button>
                            <span id="quantity-<?php echo $item['id']; ?>"><?php echo intval($item['quantity']); ?></span>
                            <button class="btn btn-outline-secondary btn-sm update-cart" data-id="<?php echo $item['id']; ?>" data-action="increase" data-stock="<?php echo $item['stock']; ?>">+</button>
                        </td>
                        <td>
                            <button class="btn btn-danger btn-sm remove-item" data-id="<?php echo $item['id']; ?>">Remove</button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <h4>Total: <span id="totalPrice">Rs <?php echo number_format($final_total); ?></span> /-</h4>
        <button class="btn btn-danger" id="clearCart">Clear Cart</button>
        <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
    <?php } else { ?>
        <div class="text-center">
            <img src="images/cart.png" alt="Empty Cart" class="img-fluid" style="max-width: 250px;">
            <div class="mt-3 text-danger" style="font-size:1rem;">Your cart is empty.</div>
        </div>
    <?php } ?>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        function showToast(type, message) {
            let toastEl = document.getElementById(type);
            toastEl.querySelector(".toast-body").textContent = message;
            let toast = new bootstrap.Toast(toastEl);
            toast.show();
        }

        function updateTotalPrice() {
            let total = 0;
            document.querySelectorAll("tr[id^='row-']").forEach(row => {
                let id = row.id.split("-")[1];
                let price = parseFloat(document.getElementById(`price-${id}`).textContent.replace(",", ""));
                let quantity = parseInt(document.getElementById(`quantity-${id}`).textContent);
                total += price * quantity;
            });
            document.getElementById("totalPrice").textContent = `Rs ${total.toLocaleString()}`;
        }

        document.querySelectorAll(".update-cart").forEach(button => {
            button.addEventListener("click", function() {
                let productId = this.getAttribute("data-id");
                let action = this.getAttribute("data-action");
                let stock = parseInt(this.getAttribute("data-stock"));
                let currentQuantity = parseInt(document.getElementById(`quantity-${productId}`).textContent);

                if (action === "increase" && currentQuantity >= stock) {
                    showToast("cartErrorToast", "Not enough stock available!");
                    return;
                }
                if (action === "decrease" && currentQuantity <= 1) {
                    showToast("cartErrorToast", "Quantity cannot be less than 1!");
                    return;
                }

                fetch("server/update_cart.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: `product_id=${productId}&action=${action}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === "success") {
                            document.getElementById(`quantity-${productId}`).textContent = data.quantity;
                            updateTotalPrice();
                            showToast("cartToast", "Cart updated successfully!");
                            // Prevent removing row if quantity is 0 (should never happen now)
                            // if (data.quantity === 0) {
                            //     document.getElementById(`row-${productId}`).remove();
                            // }
                        } else {
                            showToast("cartErrorToast", data.message);
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        showToast("cartErrorToast", "An error occurred!");
                    });
            });
        });

        document.querySelectorAll(".remove-item").forEach(button => {
            button.addEventListener("click", function() {
                let productId = this.getAttribute("data-id");

                fetch("server/remove_from_cart.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: `product_id=${productId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === "success") {
                            document.getElementById(`row-${productId}`).remove();
                            updateTotalPrice();
                            showToast("cartToast", "Item removed from cart!");
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        showToast("cartErrorToast", "An error occurred!");
                    });
            });
        });

        document.getElementById("clearCart").addEventListener("click", function() {
            fetch("server/clear_cart.php", {
                    method: "POST"
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        location.reload();
                    }
                });
        });
    });
</script>

<?php include('footer.php'); ?>
</body>

</html>