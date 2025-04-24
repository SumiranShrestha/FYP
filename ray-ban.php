<?php

include('header.php');

?>

 <style>
        .main-image {
            width: 100%;
            height: auto;
        }

        .thumbnail {
            width: 100px;
            height: 100px;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .thumbnail:hover,
        .thumbnail.active {
            border-color: #007bff;
        }

        .thumbnail-container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
    </style>


    <!-- Product Section -->
    <div class="container my-5">
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-6">
                <img id="mainImage" src="images/RY/pd-image1.webp" alt="Ray-Ban Meta Wayfarer"
                    class="img-fluid mb-3 main-image" />
                <div class="thumbnail-container">
                    <img src="images/RY/pd-image2.webp" alt="Thumb 1" class="thumbnail"
                        onclick="updateMainImage(this)" />
                    <img src="images/RY/pd-image3.webp" alt="Thumb 2" class="thumbnail"
                        onclick="updateMainImage(this)" />
                    <img src="images/RY/pd-image4.webp" alt="Thumb 3" class="thumbnail"
                        onclick="updateMainImage(this)" />
                    <img src="images/RY/pd-image5.webp" alt="Thumb 4" class="thumbnail"
                        onclick="updateMainImage(this)" />
                    <img src="images/RY/pd-image6.webp" alt="Thumb 5" class="thumbnail"
                        onclick="updateMainImage(this)" />
                    <img src="images/RY/pd-image7.webp" alt="Thumb 6" class="thumbnail"
                        onclick="updateMainImage(this)" />
                    <img src="images/RY/pd-image8.webp" alt="Thumb 7" class="thumbnail"
                        onclick="updateMainImage(this)" />
                    <img src="images/RY/pd-image9.webp" alt="Thumb 8" class="thumbnail"
                        onclick="updateMainImage(this)" />
                    <img src="images/RY/pd-image10.webp" alt="Thumb 9" class="thumbnail"
                        onclick="updateMainImage(this)" />
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-6">
                <h1>Ray-Ban | Meta Wayfarer</h1>
                <p>
                    <span class="text-muted text-decoration-line-through">Rs 21,500</span>
                    <span class="ms-2 fw-bold text-success">Rs 17,500</span>
                    <span class="badge bg-info text-dark ms-2">19% OFF</span>
                </p>
                <p>Shipping is calculated at checkout</p>

                <!-- Variants -->
                <label for="variantSelect" class="form-label">Choose Variant</label>
                <select id="variantSelect" class="form-select mb-3">
                    <option>Matte Black</option>
                    <option>Matte Jeans</option>
                </select>

                <!-- Quantity -->
                <div class="d-flex align-items-center mb-3">
                    <label for="quantitySelect" class="form-label me-2">Quantity</label>
                    <input type="number" id="quantitySelect" class="form-control" value="1" min="1"
                        style="width: 80px;" />
                </div>

                <!-- Add to Cart -->
                <button class="btn btn-success btn-lg">Add to Cart</button>

                <h4 class="mt-4">Description</h4>
                <p>
                    An icon of style from Hollywood to hip-hop, the Wayfarer never stands still. Forever embraced by the
                    next
                    generation of culture makers, now its journey continues with AI-enhanced wearable tech. Ray-Ban Meta
                    Wayfarer smart glasses enable you to live in the moment and share how you see the world. Listen,
                    call,
                    capture, and livestream features are seamlessly integrated within the classic frame.
                </p>
                <h4>Features</h4>
                <ul>
                    <li>AI (Region Specific)</li>
                    <li>Camera</li>
                    <li>Audio</li>
                    <li>Controls</li>
                    <li>Charging Case</li>
                </ul>
                <h4>Product Details</h4>
                <p><strong>Frame Shape:</strong> Square</p>
                <p><strong>Lens Color:</strong> Clear</p>
                <p><strong>Connectivity:</strong> Bluetooth 5.2</p>
            </div>

        </div>
    </div>

    <!-- Additional Product Details -->
    <div class="container my-5">
        <h3>Product Specifications</h3>
        <ul>
            <li>Wi-Fi Certified</li>
            <li>Rechargeable Battery</li>
            <li>Touch/Voice Controls</li>
        </ul>
        <img src="images/RY/image1.webp" alt="Person Wearing Glasses" class="img-fluid mt-4" />
    </div>

 
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to update the main image when a thumbnail is clicked
        function updateMainImage(thumbnail) {
            const mainImage = document.getElementById('mainImage');
            const thumbnails = document.querySelectorAll('.thumbnail');

            // Update the main image's source
            mainImage.src = thumbnail.src;

            // Remove active class from all thumbnails
            thumbnails.forEach(img => img.classList.remove('active'));

            // Add active class to the clicked thumbnail
            thumbnail.classList.add('active');
        }
    </script>
</body>

<?php
include('footer.php')
?>