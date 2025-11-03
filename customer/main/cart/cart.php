<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit();
}

// Simple database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=spicefusion;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

$user_id = $_SESSION['user_id'];

// Fetch cart items
try {
    $stmt = $pdo->prepare("
        SELECT sc.cart_id, sc.quantity, sc.special_instructions,
               p.product_id, p.product_name, p.price, p.image, p.description
        FROM shopping_cart sc
        JOIN product p ON sc.product_id = p.product_id
        WHERE sc.user_id = ?
        ORDER BY sc.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll();
} catch (Exception $e) {
    $cart_items = [];
    $error_message = "Error loading cart: " . $e->getMessage();
}

// Calculate totals
$subtotal = 0;
$tax_rate = 0.06; // 6% tax
$delivery_fee = 5.00; // Fixed delivery fee

foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$tax_amount = $subtotal * $tax_rate;
$total = $subtotal + $tax_amount + $delivery_fee;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Spice Fusion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../includes/styles.css">
    <style>
        :root {
            --primary-color: #FF4B2B;
            --secondary-color: #FF416C;
            --background-dark: #1a1a1a;
            --text-light: #ffffff;
            --text-gray: #a0a0a0;
            --card-bg: #2a2a2a;
            --gradient-primary: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            --shadow-soft: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-strong: 0 8px 16px rgba(0, 0, 0, 0.2);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-dark);
            color: var(--text-light);
        }

        .cart-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .cart-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .cart-header h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .cart-grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 2rem;
        }

        .cart-items {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
        }

        .cart-item {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1.5rem;
            padding: 1.5rem;
            border-bottom: 1px solid var(--text-gray);
            align-items: center;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 100px;
            height: 100px;
            border-radius: var(--border-radius);
            object-fit: cover;
        }

        .item-details h3 {
            margin: 0 0 0.5rem;
            color: var(--text-light);
        }

        .item-details p {
            color: var(--text-gray);
            margin: 0;
        }

        .item-price {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.2rem;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .quantity-btn {
            background: var(--background-dark);
            border: none;
            color: var(--text-light);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            transition: var(--transition);
        }

        .quantity-btn:hover {
            background: var(--primary-color);
        }

        .quantity {
            color: var(--text-light);
            font-weight: 500;
        }

        .remove-btn {
            color: var(--text-gray);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: underline;
            transition: var(--transition);
        }

        .remove-btn:hover {
            color: var(--primary-color);
        }

        .cart-summary {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            height: fit-content;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            color: var(--text-gray);
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--text-gray);
            font-weight: 600;
            color: var(--text-light);
        }

        .checkout-btn {
            width: 100%;
            padding: 1rem;
            background: var(--gradient-primary);
            border: none;
            border-radius: var(--border-radius);
            color: var(--text-light);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 1rem;
        }

        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-soft);
        }

        .empty-cart {
            text-align: center;
            padding: 3rem;
            color: var(--text-gray);
        }

        .empty-cart h2 {
            margin-bottom: 1rem;
        }

        .continue-shopping {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--gradient-primary);
            color: var(--text-light);
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .continue-shopping:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-soft);
        }

        @media (max-width: 768px) {
            .cart-grid {
                grid-template-columns: 1fr;
            }
            
            .cart-item {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/header.php'; ?>

    <div class="cart-container">
        <div class="cart-header">
            <h1>Shopping Cart</h1>
            <p>Review your items and proceed to checkout</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div style="background: #dc3545; color: white; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any items to your cart yet.</p>
                <a href="../index/index.php" class="continue-shopping">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-grid">
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <img src="../../../food_images/<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="item-image">
                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                <p><?php echo htmlspecialchars($item['description']); ?></p>
                                <div class="quantity-controls">
                                    <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['quantity'] - 1; ?>)">-</button>
                                    <span class="quantity"><?php echo $item['quantity']; ?></span>
                                    <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['quantity'] + 1; ?>)">+</button>
                                </div>
                            </div>
                            <div class="item-actions">
                                <div class="item-price">RM <?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                                <button class="remove-btn" onclick="removeFromCart(<?php echo $item['cart_id']; ?>)">Remove</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <h3>Order Summary</h3>
                    <div class="summary-item">
                        <span>Subtotal</span>
                        <span>RM <?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Tax (6%)</span>
                        <span>RM <?php echo number_format($tax_amount, 2); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Delivery Fee</span>
                        <span>RM <?php echo number_format($delivery_fee, 2); ?></span>
                    </div>
                    <div class="summary-total">
                        <span>Total</span>
                        <span>RM <?php echo number_format($total, 2); ?></span>
                    </div>
                    <button class="checkout-btn" onclick="window.location.href='../checkout/checkout.php'">Proceed to Checkout</button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>

    <script>
        function updateQuantity(cartId, newQuantity) {
            if (newQuantity <= 0) {
                removeFromCart(cartId);
                return;
            }
            
            fetch('../../api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update',
                    cart_id: cartId,
                    quantity: newQuantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating quantity: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating quantity');
            });
        }

        function removeFromCart(cartId) {
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                fetch('../../api/cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'remove',
                        cart_id: cartId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error removing item: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error removing item');
                });
            }
        }
    </script>
</body>
</html> 