<?php
require_once '../../../config/database.php';

// Get database connection
$pdo = getDBConnection();

// Fetch featured products for recommendations (show all, even if offline)
$stmt = $pdo->prepare("SELECT p.*, c.category_name FROM product p 
                       LEFT JOIN category c ON p.category_id = c.category_id 
                       WHERE p.is_featured = 1
                       ORDER BY p.created_at DESC LIMIT 5");
$stmt->execute();
$featured_products = $stmt->fetchAll();

// Fetch all categories
$stmt = $pdo->prepare("SELECT * FROM category WHERE is_active = 1");
$stmt->execute();
$categories = $stmt->fetchAll();

// Fetch products for menu section (show all products, even if offline)
$stmt = $pdo->prepare("SELECT p.*, c.category_name FROM product p 
                       LEFT JOIN category c ON p.category_id = c.category_id 
                       ORDER BY p.product_name");
$stmt->execute();
$products = $stmt->fetchAll();

// Fetch recent reviews with product and user information
$stmt = $pdo->prepare("
    SELECT r.*, 
           p.product_name, p.image as product_image,
           u.first_name, u.last_name, u.username
    FROM review r
    JOIN product p ON r.product_id = p.product_id
    JOIN user u ON r.user_id = u.user_id
    WHERE r.is_approved = 1
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute();
$reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Spice Fusion - Authentic Asian Fusion Cuisine">
    <title>Spice Fusion - Black Edition</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/DWP/customer/includes/styles.css">
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="profile.css">
    <style>
        .landing-hero-bg {
            min-height: 100vh;
            width: 100vw;
            background: linear-gradient(rgba(26,26,26,0.85), rgba(26,26,26,0.85)), url('../../../images/landing_page.png') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 0 1rem;
        }
        .landing-hero-content {
            z-index: 2;
            max-width: 700px;
            margin: 0 auto;
        }
        .landing-hero-content h1 {
            font-size: 2.8rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }
        .landing-hero-content p {
            color: var(--text-gray);
            font-size: 1.2rem;
            margin-bottom: 2.5rem;
        }
        .shop-now-btn {
            padding: 1rem 2.5rem;
            font-size: 1.2rem;
            border: none;
            border-radius: 50px;
            background: var(--gradient-primary);
            color: var(--text-light);
            font-weight: 700;
            cursor: pointer;
            box-shadow: var(--shadow-strong);
            transition: var(--transition);
            margin-bottom: 2.5rem;
            display: inline-block;
        }
        .shop-now-btn:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
        .shop-now-btn:hover {
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 8px 32px rgba(255,75,43,0.15);
        }
        .recommend-carousel {
            margin: 0 auto;
            max-width: 600px;
            background: rgba(42,42,42,0.95);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-strong);
            padding: 2rem 1rem 1.5rem 1rem;
            position: relative;
        }
        .recommend-carousel h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-size: 2rem;
        }
        .recommendation-list {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            width: 100%;
            min-height: 260px;
        }
        .recommendation-item {
            min-width: 220px;
            max-width: 350px;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-soft);
            padding: 1rem;
            display: none;
            flex-direction: column;
            align-items: center;
            position: absolute;
            left: 0; right: 0; margin: auto;
        }
        .recommendation-item.active {
            display: flex;
            position: relative;
        }
        .recommendation-item img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
        }
        .recommendation-item h3 {
            color: var(--text-light);
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .recommendation-item p {
            color: var(--text-gray);
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }
        .recommendation-item .price {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .carousel-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: var(--gradient-primary);
            border: none;
            color: var(--text-light);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            z-index: 10;
            opacity: 0.85;
            transition: background 0.2s;
        }
        .carousel-arrow:hover {
            background: var(--primary-color);
        }
        .carousel-arrow.left { left: -20px; }
        .carousel-arrow.right { right: -20px; }
        @media (max-width: 600px) {
            .landing-hero-content h1 { font-size: 2rem; }
            .recommend-carousel { padding: 1.2rem 0.2rem; }
            .carousel-arrow.left { left: 0; }
            .carousel-arrow.right { right: 0; }
        }

        /* Reviews Section Styles */
        .reviews-section {
            background: var(--background-dark);
            padding: 4rem 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .reviews-section h2 {
            text-align: center;
            font-size: 2.5rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 3rem;
        }

        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .review-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-soft);
            transition: var(--transition);
        }

        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-strong);
        }

        .review-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .review-product-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
        }

        .review-product-info {
            flex: 1;
        }

        .review-product-name {
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 0.25rem;
            font-size: 1rem;
        }

        .review-user-name {
            color: var(--text-gray);
            font-size: 0.9rem;
        }

        .review-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .review-stars {
            color: #ffc107;
            font-size: 1rem;
        }

        .review-rating-text {
            color: var(--text-gray);
            font-size: 0.9rem;
        }

        .review-comment {
            color: var(--text-light);
            line-height: 1.6;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .review-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .review-date {
            color: var(--text-gray);
            font-size: 0.85rem;
        }

        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .no-reviews {
            text-align: center;
            color: var(--text-gray);
            padding: 3rem;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .reviews-grid {
                grid-template-columns: 1fr;
            }
            
            .reviews-section h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/header.php'; ?>
    <main>
        <section class="landing-hero-bg">
            <div class="landing-hero-content">
                <h1>Welcome to Spice Fusion</h1>
                <p>Experience the best of Asian fusion cuisine, blending the rich flavors of Malaysia, Indonesia, and China. Enjoy our signature dishes, fresh ingredients, and a modern dining experience—delivered to your door or ready for takeaway. Start your culinary journey with us today!</p>
                <a href="../menu/menu.php" class="shop-now-btn" tabindex="0">Shop Now</a>
                <div class="recommend-carousel">
                    <h2>Today's Recommendations</h2>
                    <button class="carousel-arrow left" aria-label="Scroll left"><i class="fas fa-chevron-left"></i></button>
                    <div class="recommendation-list" id="recommendationList">
                        <?php foreach ($featured_products as $i => $product): ?>
                            <div class="recommendation-item<?php if ($i === 0) echo ' active'; ?>">
                                <img src="../../../food_images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                <p><?php echo htmlspecialchars($product['description']); ?></p>
                                <div class="price">RM <?php echo number_format($product['price'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-arrow right" aria-label="Scroll right"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </section>

        <section class="reviews-section">
            <h2><i class=""></i> Customer Reviews</h2>
            <?php if (!empty($reviews)): ?>
                <div class="reviews-grid">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <img src="../../../food_images/<?php echo htmlspecialchars($review['product_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($review['product_name']); ?>" 
                                     class="review-product-image">
                                <div class="review-product-info">
                                    <div class="review-product-name"><?php echo htmlspecialchars($review['product_name']); ?></div>
                                    <div class="review-user-name">
                                        <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="review-rating">
                                <div class="review-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="review-rating-text"><?php echo $review['rating']; ?>/5</span>
                            </div>
                            
                            <?php if (!empty($review['comment'])): ?>
                                <div class="review-comment">
                                    "<?php echo htmlspecialchars($review['comment']); ?>"
                                </div>
                            <?php endif; ?>
                            
                            <div class="review-footer">
                                <div class="review-date">
                                    <i class="far fa-clock"></i> <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                </div>
                                <?php if ($review['is_verified_purchase']): ?>
                                    <span class="verified-badge">
                                        <i class="fas fa-check-circle"></i> Verified Purchase
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-reviews">
                    <i class="fas fa-comment-slash" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p>No reviews yet. Be the first to review our products!</p>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>
    <script>
    // Carousel logic: only show one item at a time, no scroll/drag
    const recItems = document.querySelectorAll('.recommendation-item');
    let recIndex = 0;
    function showRec(idx) {
        recItems.forEach((item, i) => {
            item.classList.toggle('active', i === idx);
        });
    }
    document.querySelector('.carousel-arrow.left').addEventListener('click', () => {
        recIndex = (recIndex - 1 + recItems.length) % recItems.length;
        showRec(recIndex);
    });
    document.querySelector('.carousel-arrow.right').addEventListener('click', () => {
        recIndex = (recIndex + 1) % recItems.length;
        showRec(recIndex);
    });
    // Optional: keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            recIndex = (recIndex - 1 + recItems.length) % recItems.length;
            showRec(recIndex);
        } else if (e.key === 'ArrowRight') {
            recIndex = (recIndex + 1) % recItems.length;
            showRec(recIndex);
        }
    });
    </script>
</body>
</html> 