<?php
require_once '../../../config/database.php';

// Get database connection
$pdo = getDBConnection();

// Fetch all categories
$stmt = $pdo->prepare("SELECT * FROM category WHERE is_active = 1");
$stmt->execute();
$categories = $stmt->fetchAll();

// Fetch all products
$stmt = $pdo->prepare("SELECT p.*, c.category_name 
    FROM product p 
    LEFT JOIN category c ON p.category_id = c.category_id 
    WHERE p.is_available = 1
    ORDER BY p.product_name
");
$stmt->execute();
$products = $stmt->fetchAll();
// Category fallback images for consistent visuals
$categoryImages = [
    'Main Course' => 'rendang_beef.png',
    'Appetizers' => 'siew_mai.png',
    'Beverages' => 'coffee.png',
    'Desserts' => 'crepe.png',
    'Rice Dishes' => 'yeung_chow_fried_rice.png',
    'Noodles' => 'mie_goreng.png',
    'Soups' => 'wantan_soup.png',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Spice Fusion - Menu">
    <title>Menu - Spice Fusion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../includes/styles.css">
    <link rel="stylesheet" href="menu.css">
    <style>
        .menu-img { width: 100%; aspect-ratio: 1/1; object-fit: cover; border-radius: 12px; box-shadow: 0 6px 16px rgba(0,0,0,0.35); background:#1f1f1f; }
        .add-to-cart { position: relative; overflow: hidden; padding: 0.6rem 1rem; border-radius: 999px; background: linear-gradient(45deg,#FF4B2B,#FF416C); color:#fff; border:none; cursor:pointer; font-weight:600; display:inline-flex; align-items:center; gap:.5rem; transition: transform .15s ease, box-shadow .15s ease, opacity .2s ease; box-shadow:0 4px 12px rgba(255,65,108,0.3); }
        .add-to-cart:hover { transform: translateY(-1px); box-shadow:0 6px 16px rgba(255,65,108,0.45); }
        .add-to-cart:active { transform: translateY(0); }
        .add-to-cart[disabled] { opacity:.65; cursor:not-allowed; }
        .add-to-cart .spinner { width:16px; height:16px; border:2px solid rgba(255,255,255,.35); border-top-color:#fff; border-radius:50%; display:none; animation: spin .8s linear infinite; }
        .add-to-cart.is-loading .spinner { display:inline-block; }
        .add-to-cart.is-loading .btn-text { opacity:0; }
        @keyframes spin { to { transform: rotate(360deg);} }
        .add-to-cart .ripple { position:absolute; border-radius:50%; transform: scale(0); background: rgba(255,255,255,0.35); animation: ripple 600ms linear; pointer-events:none; }
        @keyframes ripple { to { transform: scale(3); opacity:0; } }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/header.php'; ?>

    <main class="container">
        <section class="menu-hero">
            <h1>Our Menu</h1>
            <p>Discover our delicious selection of authentic Asian fusion cuisine</p>
        </section>

        <section class="menu-filters">
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search dishes...">
                <button id="searchBtn"><i class="fas fa-search"></i></button>
            </div>
            <div class="filter-buttons">
                <button class="filter-btn active" data-category="all">All</button>
                <?php foreach ($categories as $category): ?>
                    <button class="filter-btn" data-category="<?php echo strtolower($category['category_name']); ?>">
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="sort-options">
                <select id="sortSelect">
                    <option value="name-asc">Name (A-Z)</option>
                    <option value="name-desc">Name (Z-A)</option>
                    <option value="price-asc">Price (Low to High)</option>
                    <option value="price-desc">Price (High to Low)</option>
                </select>
            </div>
        </section>

        <section class="menu-grid">
            <?php foreach ($products as $product): ?>
                <div class="menu-item" data-category="<?php echo strtolower($product['category_name']); ?>" data-product-id="<?php echo $product['product_id']; ?>">
                    <?php 
                        $imageFile = $product['image'];
                        if (!$imageFile || !trim($imageFile)) {
                            $imageFile = $categoryImages[$product['category_name']] ?? 'user.jpg';
                        }
                    ?>
                    <img src="../../../food_images/<?php echo htmlspecialchars($imageFile); ?>" 
                         alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="menu-img">
                    <div class="menu-info">
                        <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        <p><?php echo htmlspecialchars($product['description']); ?></p>
                        <div class="menu-footer">
                            <span class="price">RM <?php echo number_format($product['price'], 2); ?></span>
                            <button class="add-to-cart" onclick="enhancedAddToCart(<?php echo $product['product_id']; ?>, this)" aria-label="Add <?php echo htmlspecialchars($product['product_name']); ?> to cart">
                                <span class="btn-text"><i class="fas fa-cart-plus"></i> Add to Cart</span>
                                <span class="spinner" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    </main>

    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Menu items data from PHP
        const menuItems = <?php echo json_encode($products); ?>;

        document.addEventListener('DOMContentLoaded', function() {
            // Setup event listeners
            setupEventListeners();
        });

        function setupEventListeners() {
            // Filter buttons
            const filterBtns = document.querySelectorAll('.filter-btn');
            filterBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    filterAndDisplayItems();
                });
            });

            // Search input
            const searchInput = document.getElementById('searchInput');
            searchInput.addEventListener('input', filterAndDisplayItems);

            // Sort select
            const sortSelect = document.getElementById('sortSelect');
            sortSelect.addEventListener('change', filterAndDisplayItems);
        }

        function filterAndDisplayItems() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const activeCategory = document.querySelector('.filter-btn.active').dataset.category;
            const sortValue = document.getElementById('sortSelect').value;

            const menuItems = document.querySelectorAll('.menu-item');
            
            menuItems.forEach(item => {
                const name = item.querySelector('h3').textContent.toLowerCase();
                const description = item.querySelector('p').textContent.toLowerCase();
                const category = item.dataset.category;
                
                const matchesSearch = name.includes(searchTerm) || description.includes(searchTerm);
                const matchesCategory = activeCategory === 'all' || category === activeCategory;
                
                if (matchesSearch && matchesCategory) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function enhancedAddToCart(productId, btnEl) {
            if (btnEl) {
                btnEl.classList.add('is-loading');
                btnEl.setAttribute('disabled', 'disabled');
                const rect = btnEl.getBoundingClientRect();
                const ripple = document.createElement('span');
                ripple.className = 'ripple';
                ripple.style.left = (rect.width/2) + 'px';
                ripple.style.top = (rect.height/2) + 'px';
                ripple.style.width = ripple.style.height = Math.max(rect.width, rect.height) + 'px';
                btnEl.appendChild(ripple);
                setTimeout(() => { if (ripple.parentNode) ripple.parentNode.removeChild(ripple); }, 600);
            }
            <?php if (isLoggedIn()): ?>
                // Show quantity selector
                showQuantitySelector(productId);
                if (btnEl) { btnEl.classList.remove('is-loading'); btnEl.removeAttribute('disabled'); }
            <?php else: ?>
                window.location.href = '../login/login.php';
            <?php endif; ?>
        }

        function showQuantitySelector(productId) {
            // Create quantity selector modal
            const modal = document.createElement('div');
            modal.className = 'quantity-modal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
            `;

            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: #2a2a2a;
                padding: 2rem;
                border-radius: 12px;
                text-align: center;
                color: white;
                min-width: 300px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            `;

            // Get product name
            const productElement = document.querySelector(`[data-product-id="${productId}"]`);
            const productName = productElement ? productElement.querySelector('h3').textContent : 'Product';

            modalContent.innerHTML = `
                <h3 style="margin-bottom: 1rem; color: #FF4B2B;">Add to Cart</h3>
                <p style="margin-bottom: 1.5rem; color: #a0a0a0;">${productName}</p>
                <div style="display: flex; align-items: center; justify-content: center; gap: 1rem; margin-bottom: 2rem;">
                    <button id="decreaseQty" style="
                        background: #FF4B2B;
                        color: white;
                        border: none;
                        width: 40px;
                        height: 40px;
                        border-radius: 50%;
                        font-size: 1.2rem;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    ">-</button>
                    <span id="quantityDisplay" style="
                        font-size: 1.5rem;
                        font-weight: bold;
                        min-width: 50px;
                        display: inline-block;
                    ">1</span>
                    <button id="increaseQty" style="
                        background: #FF4B2B;
                        color: white;
                        border: none;
                        width: 40px;
                        height: 40px;
                        border-radius: 50%;
                        font-size: 1.2rem;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    ">+</button>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <button id="cancelBtn" style="
                        background: #666;
                        color: white;
                        border: none;
                        padding: 0.8rem 1.5rem;
                        border-radius: 25px;
                        cursor: pointer;
                        font-weight: 500;
                    ">Cancel</button>
                    <button id="addBtn" style="
                        background: linear-gradient(45deg, #FF4B2B, #FF416C);
                        color: white;
                        border: none;
                        padding: 0.8rem 1.5rem;
                        border-radius: 25px;
                        cursor: pointer;
                        font-weight: 500;
                    ">Add to Cart</button>
                </div>
            `;

            modal.appendChild(modalContent);
            document.body.appendChild(modal);

            // Quantity controls
            let quantity = 1;
            const quantityDisplay = document.getElementById('quantityDisplay');
            const decreaseBtn = document.getElementById('decreaseQty');
            const increaseBtn = document.getElementById('increaseQty');
            const cancelBtn = document.getElementById('cancelBtn');
            const addBtn = document.getElementById('addBtn');

            function updateQuantity() {
                quantityDisplay.textContent = quantity;
                decreaseBtn.disabled = quantity <= 1;
                decreaseBtn.style.opacity = quantity <= 1 ? '0.5' : '1';
            }

            decreaseBtn.addEventListener('click', () => {
                if (quantity > 1) {
                    quantity--;
                    updateQuantity();
                }
            });

            increaseBtn.addEventListener('click', () => {
                quantity++;
                updateQuantity();
            });

            cancelBtn.addEventListener('click', () => {
                document.body.removeChild(modal);
            });

            addBtn.addEventListener('click', () => {
                addToCartWithQuantity(productId, quantity);
                document.body.removeChild(modal);
            });

            // Close modal when clicking outside
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    document.body.removeChild(modal);
                }
            });

            updateQuantity();
        }

        function addToCartWithQuantity(productId, quantity) {
            fetch('../../api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add',
                    product_id: productId,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count display
                    updateCartCount(data.cart_count);
                    // Show success message
                    showSuccessMessage(`${quantity} item(s) added to cart successfully!`);
                } else {
                    alert('Error adding item to cart: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding item to cart');
            });
        }

        function updateCartCount(count) {
            const cartCountElement = document.querySelector('.cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = count;
                // Add a small animation to highlight the update
                cartCountElement.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    cartCountElement.style.transform = 'scale(1)';
                }, 200);
            }
        }

        function showSuccessMessage(message) {
            // Create success message element
            const successDiv = document.createElement('div');
            successDiv.className = 'success-message';
            successDiv.textContent = message;
            successDiv.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: #4CAF50;
                color: white;
                padding: 15px 20px;
                border-radius: 5px;
                z-index: 1000;
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                animation: slideIn 0.3s ease-out;
            `;

            // Add animation styles
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                @keyframes slideOut {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);

            // Add to page
            document.body.appendChild(successDiv);

            // Remove after 3 seconds
            setTimeout(() => {
                successDiv.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => {
                    if (successDiv.parentNode) {
                        successDiv.parentNode.removeChild(successDiv);
                    }
                }, 300);
            }, 3000);
        }
    </script>
</body>
</html> 