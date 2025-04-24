// script.js

// 1) Base Price
const basePrice = 4800; // e.g., final discounted price

// 2) Grab references to main page elements
const quantitySelect = document.getElementById('quantitySelect');
const addToCartBtn = document.getElementById('addToCartBtn');

// (Optional) If you want to show dynamic price on the main page:
const mainPriceElement = document.getElementById('mainPrice');

// Function to update main page price (optional)
function updateMainPrice() {
    const qty = parseInt(quantitySelect.value, 10) || 1;
    const total = basePrice * qty;
    mainPriceElement.textContent = `₹${total.toLocaleString()}`;
}

// Listen for changes in the main quantity (if you want real-time update)
quantitySelect.addEventListener('input', updateMainPrice);
updateMainPrice(); // init on load

// 3) Cart Offcanvas Elements
let cartQuantity = 1; // default, but we’ll override from localStorage if it exists
const cartMinusBtn = document.getElementById('cartMinusBtn');
const cartPlusBtn = document.getElementById('cartPlusBtn');
const cartQuantitySpan = document.getElementById('cartQuantitySpan');
const cartItemPrice = document.getElementById('cartItemPrice');
const cartTotal = document.getElementById('cartTotal');

// ===============================================
// ========== Local Storage Logic ================
// ===============================================

// Check localStorage on page load
// If we have a saved quantity, parse it and use that instead of 1
const savedCartQty = localStorage.getItem('cartQuantity');
if (savedCartQty) {
    cartQuantity = parseInt(savedCartQty, 10);
} else {
    cartQuantity = 1; // or 0 if you prefer an empty default
}

// Function to recalc cart totals AND store in localStorage
function updateCartTotals() {
    cartQuantitySpan.textContent = cartQuantity;

    const itemTotal = cartQuantity * basePrice;
    cartItemPrice.textContent = `₹${itemTotal.toLocaleString()}`;
    cartTotal.textContent = `₹${itemTotal.toLocaleString()}`;

    // Update localStorage so other pages / refreshes see the same quantity
    localStorage.setItem('cartQuantity', cartQuantity);
}

// 4) On “Add to Cart” click
addToCartBtn.addEventListener('click', () => {
    const userQty = parseInt(quantitySelect.value, 10) || 1;
    cartQuantity = userQty;
    updateCartTotals();
});

// 5) Plus/Minus in the cart
cartMinusBtn.addEventListener('click', () => {
    if (cartQuantity > 1) {
        cartQuantity--;
        updateCartTotals();
    }
});

cartPlusBtn.addEventListener('click', () => {
    cartQuantity++;
    updateCartTotals();
});

// Initialize cart on page load
updateCartTotals();


