<?php
require_once '../../config/database.php';

// Check if admin is logged in
if (!isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();

$success_message = '';
$error_message = '';

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $product_name = sanitize($_POST['product_name'] ?? '');
            $description = sanitize($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $category_id = (int)($_POST['category_id'] ?? 0);
            $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
            $image = handleImageUpload('image');
            
            if (empty($product_name) || $price <= 0 || $category_id <= 0) {
                $error_message = 'Please fill in all required fields';
            } else {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO product (product_name, description, price, category_id, stock_quantity, image)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$product_name, $description, $price, $category_id, $stock_quantity, $image]);
                    $success_message = 'Product added successfully';
                } catch (Exception $e) {
                    $error_message = 'Error adding product';
                }
            }
            break;
            
        case 'update':
            $product_id = (int)($_POST['product_id'] ?? 0);
            $product_name = sanitize($_POST['product_name'] ?? '');
            $description = sanitize($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $category_id = (int)($_POST['category_id'] ?? 0);
            $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
            // 获取原图片
            $stmt = $pdo->prepare("SELECT image FROM product WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $oldImage = $stmt->fetchColumn();
            if (!$oldImage) $oldImage = null;
            $image = handleImageUpload('image', $oldImage);
            $stmt = $pdo->prepare("
                UPDATE product SET product_name = ?, description = ?, price = ?, category_id = ?, 
                stock_quantity = ?, image = ? WHERE product_id = ?
            ");
            $stmt->execute([$product_name, $description, $price, $category_id, $stock_quantity, $image, $product_id]);
            $success_message = 'Product updated successfully';
            break;
            
        case 'delete':
            $product_id = (int)($_POST['product_id'] ?? 0);
            if ($product_id > 0) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM product WHERE product_id = ?");
                    $stmt->execute([$product_id]);
                    $success_message = 'Product deleted successfully';
                } catch (Exception $e) {
                    $error_message = 'Error deleting product';
                }
            }
            break;
    }
}

// Fetch categories for dropdown
$stmt = $pdo->prepare("SELECT * FROM category WHERE is_active = 1 ORDER BY category_name");
$stmt->execute();
$categories = $stmt->fetchAll();

// Search handling and fetch products
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($search !== '') {
	$like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
	$stmt = $pdo->prepare("
		SELECT p.*, c.category_name 
		FROM product p 
		LEFT JOIN category c ON p.category_id = c.category_id 
		WHERE p.product_name LIKE ? OR c.category_name LIKE ? OR p.description LIKE ? 
		ORDER BY p.product_name
	");
	$stmt->execute([$like, $like, $like]);
	$products = $stmt->fetchAll();
} else {
	$stmt = $pdo->prepare("
		SELECT p.*, c.category_name 
		FROM product p 
		LEFT JOIN category c ON p.category_id = c.category_id 
		ORDER BY p.product_name
	");
	$stmt->execute();
	$products = $stmt->fetchAll();
}

// 处理图片上传
function handleImageUpload($fileInput, $oldImage = null) {
    if (isset($_FILES[$fileInput]) && $_FILES[$fileInput]['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES[$fileInput]['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];
        if (!in_array($ext, $allowed)) return $oldImage;
        $newName = uniqid('food_', true) . '.' . $ext;
        $dest = __DIR__ . '/../../food_images/' . $newName;
        if (move_uploaded_file($_FILES[$fileInput]['tmp_name'], $dest)) {
            return $newName;
        }
    }
    return $oldImage;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Spice Fusion Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #FF4B2B;
            --secondary-color: #FF416C;
            --background-dark: #1a1a1a;
            --text-light: #ffffff;
            --text-gray: #a0a0a0;
            --card-bg: #2a2a2a;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
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

        .admin-header {
            background: var(--card-bg);
            padding: 1rem 2rem;
            box-shadow: var(--shadow-soft);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo h1 {
            color: var(--primary-color);
            margin: 0;
            font-size: 1.5rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-links a {
            color: var(--text-light);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: var(--transition);
        }

        .nav-links a:hover {
            background: var(--primary-color);
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            color: var(--primary-color);
            margin: 0;
        }

        .add-btn {
            background: var(--gradient-primary);
            color: var(--text-light);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
        }

        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-soft);
        }

        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }

        .alert.success {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }

        .alert.error {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .product-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-soft);
            transition: var(--transition);
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-strong);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
        }

        .product-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-light);
        }

        .product-category {
            color: var(--text-gray);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .product-price {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .product-stock {
            color: var(--text-gray);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .stock-ok { color: #9be07a; }
        .stock-low { color: #ffc107; }
        .stock-out { color: var(--danger-color); }

        .product-actions {
            display: flex;
            gap: 0.5rem;
        }

        .edit-btn, .delete-btn {
            flex: 1;
            padding: 0.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            text-align: center;
            font-size: 0.9rem;
        }

        .edit-btn {
            background: var(--info-color);
            color: var(--text-light);
        }

        .edit-btn:hover {
            background: #138496;
        }

        .delete-btn {
            background: var(--danger-color);
            color: var(--text-light);
        }

        .delete-btn:hover {
            background: #c82333;
        }

        .badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 500;
            margin-left: 0.5rem;
        }

        .badge.featured {
            background: var(--warning-color);
            color: #000;
        }

        .badge.available {
            background: var(--success-color);
            color: #fff;
        }

        .badge.unavailable {
            background: var(--danger-color);
            color: #fff;
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }

        .modal-bg {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            max-width: 500px;
            margin: 8vh auto 0;
            padding: 2rem;
            position: relative;
            box-shadow: var(--shadow-strong);
        }
        .modal h2 { color: var(--primary-color); margin-bottom: 1rem; }
        .modal .close-btn {
            position: absolute; top: 1rem; right: 1rem;
            background: none; border: none; color: var(--text-gray); font-size: 1.5rem; cursor: pointer;
        }
        .modal label { display: block; margin-top: 1rem; color: var(--text-light); }
        .modal input, .modal textarea, .modal select {
            width: 100%; padding: 0.5rem; border-radius: 6px; border: 1px solid var(--text-gray);
            background: var(--background-dark); color: var(--text-light); margin-top: 0.3rem;
        }
        .modal .form-row { display: flex; gap: 1rem; }
        .modal .form-row > div { flex: 1; }
        .modal .modal-actions { margin-top: 1.5rem; text-align: right; }
        .modal .modal-actions button { margin-left: 1rem; }
        .modal-btn {
            padding: 0.7rem 2rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
        }
        .modal-btn.save {
            background: var(--gradient-primary);
            color: #fff;
            margin-left: 1rem;
        }
        .modal-btn.save:hover {
            background: var(--primary-color);
        }
        .modal-btn.cancel {
            background: #666;
            color: #fff;
        }
        .modal-btn.cancel:hover {
            background: var(--danger-color);
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="header-content">
            <div class="logo">
                <h1>Spice Fusion Admin</h1>
            </div>
            <div class="nav-links">
                <a href="../dashboard/dashboard.php">Dashboard</a>
                <a href="../products/product.php" style="background: var(--primary-color);">Products</a>
                <a href="../orders/order.php">Orders</a>
                <a href="../members/member.php">Customers</a>
                <a href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Product Management</h1>
            <form method="GET" action="product.php" style="display:flex;align-items:center;gap:0.5rem;">
                <input type="text" name="q" placeholder="Search products..." value="<?php echo htmlspecialchars($search ?? ''); ?>" style="padding:0.6rem 0.8rem;border-radius:6px;border:1px solid #333;background:#111;color:#fff;min-width:220px;" />
                <button type="submit" class="add-btn" style="background:#2e7d32;"><i class="fas fa-search"></i> Search</button>
                <a href="product.php" class="add-btn" style="background:#444;"><i class="fas fa-xmark"></i> Clear</a>
            </form>
            <button class="add-btn" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add Product
            </button>
            <button class="add-btn" onclick="window.location.reload()" style="background:#444;margin-left:0.5rem;">
                <i class="fas fa-rotate-right"></i> Refresh
            </button>
            <button class="add-btn" id="lowStockToggle" style="background:#555;margin-left:0.5rem;">
                <i class="fas fa-filter"></i> Low Stock Only
            </button>
        </div>

        <?php if ($success_message): ?>
            <div class="alert success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card" data-product-id="<?php echo (int)$product['product_id']; ?>" data-stock="<?php echo (int)$product['stock_quantity']; ?>">
                    <img src="../../food_images/<?php echo htmlspecialchars($product['image'] ?: 'default_food.png'); ?>" 
                         alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="product-image">
                    
                    <div class="product-name">
                        <?php echo htmlspecialchars($product['product_name']); ?>
                    </div>
                    
                    <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                    <div class="product-price">RM <?php echo number_format($product['price'], 2); ?></div>
                    <?php 
                        $stock = (int)$product['stock_quantity'];
                        $stockClass = $stock <= 0 ? 'stock-out' : ($stock <= 5 ? 'stock-low' : 'stock-ok');
                        $stockLabel = $stock <= 0 ? 'Out of stock' : ($stock <= 5 ? 'Low stock' : 'In stock');
                    ?>
                    <div class="product-stock <?php echo $stockClass; ?>" data-stock-text>Stock: <span data-qty><?php echo $stock; ?></span> <span class="badge <?php echo $stock <= 0 ? 'unavailable' : 'available'; ?>" data-stock-badge style="margin-left:6px;"><?php echo $stockLabel; ?></span></div>
                    
                    <div class="product-actions">
                        <button class="edit-btn" onclick='editProduct(<?php echo json_encode($product); ?>)'>
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="delete-btn" onclick="deleteProduct(<?php echo $product['product_id']; ?>)">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal for Add/Edit Product -->
    <div class="modal-bg" id="productModalBg">
        <div class="modal">
            <button class="close-btn" onclick="closeModal()">&times;</button>
            <h2 id="modalTitle">Add Product</h2>
            <form id="productForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="product_id" id="product_id">
                <label>Product Name
                    <input type="text" name="product_name" id="product_name" required>
                </label>
                <label>Description
                    <textarea name="description" id="description" rows="2"></textarea>
                </label>
                <div class="form-row">
                    <div>
                        <label>Price (RM)
                            <input type="number" name="price" id="price" min="0" step="0.01" required>
                        </label>
                    </div>
                    <div>
                        <label>Stock
                            <input type="number" name="stock_quantity" id="stock_quantity" min="0" required>
                        </label>
                    </div>
                </div>
                <label>Category
                    <select name="category_id" id="category_id" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Picture
                    <input type="file" name="image" id="image" accept="image/*">
                    <img id="previewImg" src="" style="display:none;max-width:100px;margin-top:0.5rem;border-radius:8px;" />
                </label>
                <div class="modal-actions">
                    <button type="button" class="modal-btn cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="modal-btn save" id="submitBtn">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Modal control
    function openAddModal() {
        document.getElementById('modalTitle').innerText = 'Add Product';
        document.getElementById('formAction').value = 'add';
        document.getElementById('product_id').value = '';
        document.getElementById('product_name').value = '';
        document.getElementById('description').value = '';
        document.getElementById('price').value = '';
        document.getElementById('stock_quantity').value = '';
        document.getElementById('category_id').value = '';
        document.getElementById('productModalBg').style.display = 'block';
        previewImg.style.display = 'none';
        previewImg.src = '';
    }
    function editProduct(product) {
        document.getElementById('modalTitle').innerText = 'Edit Product';
        document.getElementById('formAction').value = 'update';
        document.getElementById('product_id').value = product.product_id;
        document.getElementById('product_name').value = product.product_name;
        document.getElementById('description').value = product.description;
        document.getElementById('price').value = product.price;
        document.getElementById('stock_quantity').value = product.stock_quantity;
        document.getElementById('category_id').value = product.category_id;
        document.getElementById('productModalBg').style.display = 'block';
        if(product.image) {
            previewImg.src = '../../food_images/' + product.image;
            previewImg.style.display = 'block';
        } else {
            previewImg.style.display = 'none';
            previewImg.src = '';
        }
    }
    function closeModal() {
        document.getElementById('productModalBg').style.display = 'none';
    }
    // 点击modal外部关闭
    document.getElementById('productModalBg').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
    // 删除功能已实现
    function deleteProduct(productId) {
        if (confirm('Are you sure you want to delete this product?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="product_id" value="${productId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    // 图片预览
    const imageInput = document.getElementById('image');
    const previewImg = document.getElementById('previewImg');
    if(imageInput) {
        imageInput.addEventListener('change', function() {
            if(this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            } else {
                previewImg.style.display = 'none';
            }
        });
    }
    </script>
    <script>
    // Live stock polling
    function applyStockClasses(card, qty) {
        const stockDiv = card.querySelector('[data-stock-text]');
        const badge = card.querySelector('[data-stock-badge]');
        stockDiv.classList.remove('stock-ok','stock-low','stock-out');
        let cls = 'stock-ok'; let label = 'In stock'; let badgeCls = 'available';
        if (qty <= 0) { cls = 'stock-out'; label = 'Out of stock'; badgeCls = 'unavailable'; }
        else if (qty <= 5) { cls = 'stock-low'; label = 'Low stock'; badgeCls = 'available'; }
        stockDiv.classList.add(cls);
        badge.classList.remove('available','unavailable');
        badge.classList.add(badgeCls);
        badge.textContent = label;
    }

    async function pollStock() {
        try {
            const res = await fetch('stock_api.php', { cache: 'no-store' });
            if (!res.ok) return;
            const data = await res.json();
            const map = new Map(data.map(p => [String(p.product_id), p.stock_quantity]));
            document.querySelectorAll('.product-card').forEach(card => {
                const id = card.getAttribute('data-product-id');
                if (!map.has(id)) return;
                const qty = parseInt(map.get(id), 10);
                const qtyEl = card.querySelector('[data-qty]');
                if (qtyEl) qtyEl.textContent = qty;
                card.setAttribute('data-stock', qty);
                applyStockClasses(card, qty);
            });
            applyLowStockFilter();
        } catch (e) { /* ignore */ }
    }
    setInterval(pollStock, 5000);
    window.addEventListener('load', pollStock);

    // Low stock filter
    let showLowOnly = false;
    function applyLowStockFilter() {
        document.querySelectorAll('.product-card').forEach(card => {
            const qty = parseInt(card.getAttribute('data-stock') || '0', 10);
            card.style.display = (!showLowOnly || qty <= 5) ? '' : 'none';
        });
    }
    document.getElementById('lowStockToggle').addEventListener('click', function(){
        showLowOnly = !showLowOnly;
        this.style.background = showLowOnly ? '#6a1b9a' : '#555';
        this.innerHTML = '<i class="fas fa-filter"></i> ' + (showLowOnly ? 'Showing Low Stock' : 'Low Stock Only');
        applyLowStockFilter();
    });
    </script>
</body>
</html> 