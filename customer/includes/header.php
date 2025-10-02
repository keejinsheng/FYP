<?php
// Use __DIR__ to build an absolute path to database.php
require_once __DIR__ . '/../../config/database.php';

// Get cart count for logged in user
$cart_count = 0;
if (isLoggedIn()) {
    $pdo = getDBConnection();
    // Get total quantity of all items in cart
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM shopping_cart WHERE user_id = ?");
    $stmt->execute([getCurrentUserId()]);
    $count = $stmt->fetchColumn();
    if ($count) {
        $cart_count = $count;
    }
}
?>
<!DOCTYPE html>
<link rel="stylesheet" href="../includes/styles.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<header class="main-header">
    <nav class="nav-container">
        <div class="logo">
            <a href="../index/index.php">
                <img src="../../../images/logo.jpg" alt="Spice Fusion Logo">
                <span>Spice Fusion</span>
            </a>
        </div>
        
        <ul class="nav-links">
            <li><a href="../index/index.php">Home</a></li>
            <li><a href="../menu/menu.php">Menu</a></li>
            <li><a href="../aboutus/aboutus.php">About Us</a></li>
            <li><a href="../contactus/contactus.php">Contact Us</a></li>
        </ul>

        <div class="nav-right">
            <div class="cart-icon">
                <a href="../cart/cart.php" class="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
            </div>
            <div class="user-menu">
                <?php if (isLoggedIn()): ?>
                    <a href="../profile/profile.php" class="profile-link">
                        <i class="fas fa-user"></i>
                        Profile
                    </a>
                    <a href="../dashboard/Cdashboard.php" class="dashboard-link">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                    <a href="/dwp5431/Group%201/DWP/customer/auth/logout.php" class="logout-link">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                <?php else: ?>
                    <a href="../login/login.php" class="login-link">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </a>
                    <a href="../register/register.php" class="register-link">
                        <i class="fas fa-user-plus"></i>
                        Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>
<style>
/* Header specific styles */
.main-header {
    background-color: var(--background-dark);
    padding: 1rem 2rem;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.nav-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo a {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: var(--text-light);
    gap: 1rem;
}

.logo img {
    height: 40px;
    width: auto;
}

.logo span {
    font-size: 1.5rem;
    font-weight: 700;
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.nav-links {
    display: flex;
    gap: 2rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-links a {
    color: var(--text-light);
    text-decoration: none;
    font-weight: 500;
    transition: var(--transition);
}

.nav-links a:hover {
    color: var(--primary-color);
}

.nav-right {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.user-menu {
    display: flex;
    gap: 1rem;
}

.user-menu a {
    color: var(--text-light);
    text-decoration: none;
    font-weight: 500;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.user-menu a:hover {
    color: var(--primary-color);
}

.cart-icon {
    position: relative;
}

.cart-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: var(--text-light);
    transition: var(--transition);
}

.cart-link:hover {
    color: var(--primary-color);
}

.cart-icon i {
    font-size: 1.5rem;
}

.cart-count {
    position: absolute;
    top: -5px;
    right: -5px;
    background: var(--gradient-primary);
    color: var(--text-light);
    font-size: 0.8rem;
    padding: 0.2rem 0.5rem;
    border-radius: 50%;
    min-width: 20px;
    text-align: center;
}

@media (max-width: 768px) {
    .nav-links {
        display: none;
    }
}
</style> 