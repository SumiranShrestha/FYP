$(document).ready(function () {
    $(".addToCartBtn").click(function () {
        var productId = $(this).data("product-id");
        var quantity = $("#quantitySelect").val();

        $.ajax({
            url: "add_to_cart.php",
            type: "POST",
            data: { product_id: productId, quantity: quantity },
            dataType: "json",
            success: function (response) {
                alert(response.message);
            }
        });
    });
});
