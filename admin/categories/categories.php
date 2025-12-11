<?php
require_once '../../config/database.php';

// 检查是否已登录为管理员
if (!isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_category') {
            $category_name = trim($_POST['category_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
            
            if (empty($category_name)) {
                $error_message = 'Category name is required.';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO category (category_name, description, is_active) VALUES (?, ?, ?)");
                    $stmt->execute([$category_name, $description ?: null, $is_active]);
                    $success_message = 'Category added successfully!';
                } catch (Exception $e) {
                    $error_message = 'Error adding category: ' . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] === 'edit_category') {
            $category_id = (int)($_POST['category_id'] ?? 0);
            $category_name = trim($_POST['category_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
            
            if ($category_id <= 0) {
                $error_message = 'Invalid category ID.';
            } elseif (empty($category_name)) {
                $error_message = 'Category name is required.';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE category SET category_name = ?, description = ?, is_active = ? WHERE category_id = ?");
                    $stmt->execute([$category_name, $description ?: null, $is_active, $category_id]);
                    $success_message = 'Category updated successfully!';
                } catch (Exception $e) {
                    $error_message = 'Error updating category: ' . $e->getMessage();
                }
            }
        }
    }
}

// 查询所有分类
$stmt = $pdo->prepare('SELECT * FROM category ORDER BY created_at DESC');
$stmt->execute();
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management - Spice Fusion Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            max-width: 900px;
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
        .back-btn {
            background: var(--gradient-primary);
            color: var(--text-light);
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
        }
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-soft);
        }
        .categories-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-soft);
        }
        .categories-table th, .categories-table td {
            padding: 1rem;
            text-align: left;
        }
        .categories-table th {
            background: var(--background-dark);
            color: var(--primary-color);
        }
        .categories-table tr:not(:last-child) {
            border-bottom: 1px solid var(--text-gray);
        }
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
        }
        .active { background: var(--success-color); color: #fff; }
        .inactive { background: var(--danger-color); color: #fff; }
        /* Buttons */
        .add-btn, .edit-btn {
            background: var(--gradient-primary);
            color: var(--text-light);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }
        .add-btn:hover, .edit-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-soft);
        }
        .edit-btn {
            background: var(--info-color);
        }
        /* Toast Messages */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            box-shadow: var(--shadow-strong);
            z-index: 2000;
            backdrop-filter: blur(6px);
            animation: slidein .25s ease-out;
            max-width: 400px;
        }
        .toast.success {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }
        .toast.error {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
        }
        @keyframes slidein { 
            from { transform: translateY(-10px); opacity: 0; } 
            to { transform: translateY(0); opacity: 1; } 
        }
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
        }
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .modal-content {
            background: var(--card-bg);
            margin: auto;
            padding: 0;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 600px;
            box-shadow: var(--shadow-strong);
            animation: slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h2 {
            margin: 0;
            color: var(--primary-color);
        }
        .close-btn {
            color: var(--text-gray);
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
        }
        .close-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
        }
        .modal-body {
            padding: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-light);
            font-weight: 500;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            background: var(--background-dark);
            border: 1px solid var(--text-gray);
            border-radius: 6px;
            color: var(--text-light);
            font-size: 0.95rem;
            font-family: inherit;
            transition: var(--transition);
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 75, 43, 0.15);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
        .btn-submit, .btn-cancel {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.95rem;
            transition: var(--transition);
        }
        .btn-submit {
            background: var(--gradient-primary);
            color: var(--text-light);
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-soft);
        }
        .btn-cancel {
            background: var(--background-dark);
            color: var(--text-light);
            border: 1px solid var(--text-gray);
        }
        .btn-cancel:hover {
            background: var(--text-gray);
        }
        @media (max-width: 768px) {
            .container { padding: 0 0.5rem; }
            .categories-table th, .categories-table td { padding: 0.5rem; font-size: 0.95rem; }
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
                <a href="../products/product.php">Products</a>
                <a href="../orders/order.php">Orders</a>
                <a href="../members/member.php">Customers</a>
                <a href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Category Management</h1>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <button class="add-btn" onclick="openCategoryModal()">
                    <i class="fas fa-plus"></i> Add Category
                </button>
                <a href="../dashboard/dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>
        
        <?php if ($success_message): ?>
            <div class="toast success" id="successToast"><?php echo htmlspecialchars($success_message); ?></div>
            <script>
                setTimeout(function(){
                    var t = document.getElementById('successToast');
                    if (t) { t.style.transition = 'opacity .25s ease'; t.style.opacity = '0'; setTimeout(function(){ t.remove(); }, 300); }
                }, 3000);
            </script>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="toast error" id="errorToast"><?php echo htmlspecialchars($error_message); ?></div>
            <script>
                setTimeout(function(){
                    var t = document.getElementById('errorToast');
                    if (t) { t.style.transition = 'opacity .25s ease'; t.style.opacity = '0'; setTimeout(function(){ t.remove(); }, 300); }
                }, 3000);
            </script>
        <?php endif; ?>
        
        <?php if (empty($categories)): ?>
            <p style="color: var(--text-gray);">No category available.</p>
        <?php else: ?>
            <table class="categories-table">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cat['category_name']); ?></td>
                            <td><span class="status-badge <?php echo $cat['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $cat['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($cat['created_at'])); ?></td>
                            <td>
                                <button class="edit-btn" onclick="openCategoryModal(<?php echo (int)$cat['category_id']; ?>, '<?php echo htmlspecialchars($cat['category_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($cat['description'] ?? '', ENT_QUOTES); ?>', <?php echo $cat['is_active'] ? 1 : 0; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <!-- Category Modal -->
        <div id="categoryModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitle">Add Category</h2>
                    <button class="close-btn" onclick="closeCategoryModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="categoryForm" method="POST" action="">
                        <input type="hidden" name="action" id="formAction" value="add_category">
                        <input type="hidden" name="category_id" id="categoryId" value="">
                        
                        <div class="form-group">
                            <label for="category_name">Category Name <span style="color: var(--danger-color);">*</span></label>
                            <input type="text" id="category_name" name="category_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" placeholder="Enter category description (optional)"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="is_active">Status</label>
                            <select id="is_active" name="is_active">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-cancel" onclick="closeCategoryModal()">Cancel</button>
                            <button type="submit" class="btn-submit">Save Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openCategoryModal(categoryId = null, categoryName = '', description = '', isActive = 1) {
            const modal = document.getElementById('categoryModal');
            const form = document.getElementById('categoryForm');
            const modalTitle = document.getElementById('modalTitle');
            const formAction = document.getElementById('formAction');
            const categoryIdInput = document.getElementById('categoryId');
            const nameInput = document.getElementById('category_name');
            const descInput = document.getElementById('description');
            const statusSelect = document.getElementById('is_active');
            
            if (categoryId) {
                // Edit mode
                modalTitle.textContent = 'Edit Category';
                formAction.value = 'edit_category';
                categoryIdInput.value = categoryId;
                nameInput.value = categoryName;
                descInput.value = description;
                statusSelect.value = isActive;
            } else {
                // Add mode
                modalTitle.textContent = 'Add Category';
                formAction.value = 'add_category';
                categoryIdInput.value = '';
                nameInput.value = '';
                descInput.value = '';
                statusSelect.value = 1;
            }
            
            modal.classList.add('show');
        }

        function closeCategoryModal() {
            const modal = document.getElementById('categoryModal');
            modal.classList.remove('show');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('categoryModal');
            if (event.target === modal) {
                closeCategoryModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeCategoryModal();
            }
        });
    </script>
</body>
</html> 