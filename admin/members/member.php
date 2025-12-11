<?php
require_once '../../config/database.php';

// 检查是否已登录为管理员
if (!isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();

// 查询所有客户（不区分角色，直接查所有用户）
$stmt = $pdo->prepare("SELECT * FROM user ORDER BY created_at DESC");
$stmt->execute();
$customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - Spice Fusion Admin</title>
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
        .customers-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-soft);
        }
        .customers-table th, .customers-table td {
            padding: 1rem;
            text-align: left;
        }
        .customers-table th {
            background: var(--background-dark);
            color: var(--primary-color);
        }
        .customers-table tr:not(:last-child) {
            border-bottom: 1px solid var(--text-gray);
        }
        /* Search Box */
        .search-container {
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            align-items: center;
        }
        .search-box {
            position: relative;
            max-width: 350px;
            width: 100%;
        }
        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            background: var(--card-bg);
            border: 1px solid var(--text-gray);
            border-radius: var(--border-radius);
            color: var(--text-light);
            font-size: 0.95rem;
            transition: var(--transition);
        }
        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 75, 43, 0.15);
        }
        .search-box i {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-gray);
        }
        .search-box input::placeholder {
            color: var(--text-gray);
        }
        .no-results {
            text-align: center;
            padding: 2rem;
            color: var(--text-gray);
            display: none;
        }
        .no-results.show {
            display: block;
        }
        /* Account Status Badge */
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }
        .status-badge.active {
            background: var(--success-color);
            color: #fff;
        }
        .status-badge.inactive {
            background: var(--danger-color);
            color: #fff;
        }
        /* View Details Button */
        .view-details-btn {
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
        .view-details-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-soft);
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
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
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
            position: sticky;
            top: 0;
            background: var(--card-bg);
            z-index: 10;
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
        .customer-detail-section {
            margin-bottom: 2rem;
        }
        .customer-detail-section:last-child {
            margin-bottom: 0;
        }
        .customer-detail-section h3 {
            color: var(--primary-color);
            margin: 0 0 1rem 0;
            font-size: 1.1rem;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: var(--text-gray);
            font-weight: 500;
        }
        .detail-value {
            color: var(--text-light);
            text-align: right;
        }
        .profile-image-container {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            box-shadow: var(--shadow-soft);
        }
        .status-form {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
        }
        .status-select {
            appearance: none;
            -webkit-appearance: none;
            background: #1f1f1f url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="%23a0a0a0"><path d="M5.516 7.548l4.484 4.487 4.484-4.487 1.516 1.516-6 6-6-6z"/></svg>') no-repeat right .55rem center/16px;
            color: #fff;
            border: 1px solid #555;
            border-radius: 8px;
            padding: .5rem 2rem .5rem .8rem;
            cursor: pointer;
            transition: border .2s, box-shadow .2s;
            min-width: 150px;
        }
        .status-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 75, 43, 0.15);
        }
        .update-status-btn {
            background: var(--gradient-primary);
            color: var(--text-light);
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: var(--transition);
        }
        .update-status-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-soft);
        }
        .update-status-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .loading {
            text-align: center;
            padding: 2rem;
            color: var(--text-gray);
        }
        .error-message {
            text-align: center;
            padding: 2rem;
            color: var(--danger-color);
        }
        .success-message {
            text-align: center;
            padding: 1rem;
            color: var(--success-color);
            background: rgba(40, 167, 69, 0.1);
            border-radius: 6px;
            margin-bottom: 1rem;
            display: none;
        }
        .success-message.show {
            display: block;
        }
        @media (max-width: 768px) {
            .container { padding: 0 0.5rem; }
            .customers-table th, .customers-table td { padding: 0.5rem; font-size: 0.95rem; }
            .search-container {
                justify-content: center;
            }
            .search-box {
                max-width: 100%;
            }
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
                <a href="member.php" style="background: var(--primary-color);">Customers</a>
                <a href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Customer Management</h1>
            <a href="../dashboard/dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        <?php if (empty($customers)): ?>
            <p style="color: var(--text-gray);">No customers found.</p>
        <?php else: ?>
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="customerSearch" placeholder="Search by ID or Name..." onkeyup="filterCustomers()">
                </div>
            </div>
            <div class="no-results" id="noResults">No customers found matching your search.</div>
            <table class="customers-table" id="customersTable">
                <thead>
                    <tr>
                        <th>Customer ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Account Status</th>
                        <th>Registered At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $c): ?>
                        <tr data-customer-id="<?php echo (int)$c['user_id']; ?>" 
                            data-customer-name="<?php echo htmlspecialchars(strtolower($c['first_name'] . ' ' . $c['last_name'])); ?>"
                            data-customer-email="<?php echo htmlspecialchars(strtolower($c['email'])); ?>">
                            <td><?php echo (int)$c['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($c['email']); ?></td>
                            <td><?php echo htmlspecialchars($c['phone']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $c['is_active'] ? 'active' : 'inactive'; ?>" id="status-badge-<?php echo (int)$c['user_id']; ?>">
                                    <?php echo $c['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($c['created_at'])); ?></td>
                            <td>
                                <button class="view-details-btn" onclick="openCustomerModal(<?php echo (int)$c['user_id']; ?>)">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Customer Details Modal -->
    <div id="customerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Customer Details</h2>
                <button class="close-btn" onclick="closeCustomerModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading customer details...
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentCustomerId = null;

        function openCustomerModal(userId) {
            currentCustomerId = userId;
            const modal = document.getElementById('customerModal');
            const modalBody = document.getElementById('modalBody');
            
            // Show modal with loading state
            modal.classList.add('show');
            modalBody.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading customer details...</div>';
            
            // Fetch customer details
            fetch(`get_customer_details.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        modalBody.innerHTML = `<div class="error-message">${data.error}</div>`;
                        return;
                    }
                    
                    // Populate modal with customer details
                    modalBody.innerHTML = buildCustomerDetailsHTML(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = '<div class="error-message">Failed to load customer details. Please try again.</div>';
                });
        }

        function closeCustomerModal() {
            const modal = document.getElementById('customerModal');
            modal.classList.remove('show');
            currentCustomerId = null;
        }

        function buildCustomerDetailsHTML(customer) {
            // Format date of birth
            const dob = customer.date_of_birth ? new Date(customer.date_of_birth).toLocaleDateString() : 'N/A';
            const createdDate = new Date(customer.created_at).toLocaleString();
            
            // Profile image path - adjust path based on your actual file structure
            const profileImagePath = customer.profile_image && customer.profile_image !== 'user.jpg' 
                ? `../../images/${customer.profile_image}` 
                : `../../images/user.jpg`;
            
            let html = `
                <div class="success-message" id="statusSuccessMessage">Account status updated successfully!</div>
                
                <!-- Profile Image -->
                <div class="profile-image-container">
                    <img src="${profileImagePath}" alt="Profile" class="profile-image" onerror="this.src='../../images/user.jpg'; this.onerror=null;">
                </div>

                <!-- Personal Information -->
                <div class="customer-detail-section">
                    <h3><i class="fas fa-user"></i> Personal Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Customer ID:</span>
                        <span class="detail-value">${escapeHtml(customer.user_id)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Username:</span>
                        <span class="detail-value">${escapeHtml(customer.username || 'N/A')}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">First Name:</span>
                        <span class="detail-value">${escapeHtml(customer.first_name || 'N/A')}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Last Name:</span>
                        <span class="detail-value">${escapeHtml(customer.last_name || 'N/A')}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Full Name:</span>
                        <span class="detail-value">${escapeHtml((customer.first_name || '') + ' ' + (customer.last_name || ''))}</span>
                    </div>
                    ${customer.date_of_birth ? `
                    <div class="detail-row">
                        <span class="detail-label">Date of Birth:</span>
                        <span class="detail-value">${escapeHtml(dob)}</span>
                    </div>
                    ` : ''}
                    ${customer.gender ? `
                    <div class="detail-row">
                        <span class="detail-label">Gender:</span>
                        <span class="detail-value">${escapeHtml(customer.gender)}</span>
                    </div>
                    ` : ''}
                </div>

                <!-- Contact Information -->
                <div class="customer-detail-section">
                    <h3><i class="fas fa-envelope"></i> Contact Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value">${escapeHtml(customer.email || 'N/A')}</span>
                    </div>
                    ${customer.phone ? `
                    <div class="detail-row">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value">${escapeHtml(customer.phone)}</span>
                    </div>
                    ` : ''}
                </div>

                <!-- Account Information -->
                <div class="customer-detail-section">
                    <h3><i class="fas fa-cog"></i> Account Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Account Status:</span>
                        <span class="detail-value">
                            <span class="status-badge ${customer.is_active ? 'active' : 'inactive'}" id="modalStatusBadge">
                                ${customer.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Registered At:</span>
                        <span class="detail-value">${escapeHtml(createdDate)}</span>
                    </div>
                    
                    <!-- Status Update Form -->
                    <form class="status-form" id="statusForm" onsubmit="updateCustomerStatus(event)">
                        <select name="is_active" id="statusSelect" class="status-select">
                            <option value="1" ${customer.is_active == 1 ? 'selected' : ''}>Active</option>
                            <option value="0" ${customer.is_active == 0 ? 'selected' : ''}>Inactive</option>
                        </select>
                        <button type="submit" class="update-status-btn" id="updateStatusBtn">
                            <i class="fas fa-save"></i> Update Status
                        </button>
                    </form>
                </div>
            `;
            
            return html;
        }

        function updateCustomerStatus(event) {
            event.preventDefault();
            
            if (!currentCustomerId) {
                return;
            }
            
            const statusSelect = document.getElementById('statusSelect');
            const updateBtn = document.getElementById('updateStatusBtn');
            const successMessage = document.getElementById('statusSuccessMessage');
            const isActive = parseInt(statusSelect.value);
            
            // Disable button during update
            updateBtn.disabled = true;
            updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            
            // Create form data
            const formData = new FormData();
            formData.append('user_id', currentCustomerId);
            formData.append('is_active', isActive);
            
            // Update status
            fetch('update_customer_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                } else if (data.success) {
                    // Update modal badge
                    const modalBadge = document.getElementById('modalStatusBadge');
                    modalBadge.className = `status-badge ${data.is_active ? 'active' : 'inactive'}`;
                    modalBadge.textContent = data.is_active ? 'Active' : 'Inactive';
                    
                    // Update table badge
                    const tableBadge = document.getElementById(`status-badge-${currentCustomerId}`);
                    if (tableBadge) {
                        tableBadge.className = `status-badge ${data.is_active ? 'active' : 'inactive'}`;
                        tableBadge.textContent = data.is_active ? 'Active' : 'Inactive';
                    }
                    
                    // Show success message
                    if (successMessage) {
                        successMessage.classList.add('show');
                        setTimeout(() => {
                            successMessage.classList.remove('show');
                        }, 3000);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update status. Please try again.');
            })
            .finally(() => {
                // Re-enable button
                updateBtn.disabled = false;
                updateBtn.innerHTML = '<i class="fas fa-save"></i> Update Status';
            });
        }

        function escapeHtml(text) {
            if (text == null) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('customerModal');
            if (event.target === modal) {
                closeCustomerModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeCustomerModal();
            }
        });

        // Search functionality
        function filterCustomers() {
            const input = document.getElementById('customerSearch');
            const filter = input.value.toLowerCase().trim();
            const table = document.getElementById('customersTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            const noResults = document.getElementById('noResults');
            let found = false;

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const customerId = (row.getAttribute('data-customer-id') || '').toLowerCase();
                const customerName = row.getAttribute('data-customer-name') || '';
                const customerEmail = row.getAttribute('data-customer-email') || '';
                const prefixedId = `customer${customerId}`;
                const hashId = `#${customerId}`;
                const customerIdWithSpace = `customer ${customerId}`;
                const customerIdLabel = `customer id ${customerId}`;
                const idLabel = `id ${customerId}`;
                const idCompact = `id${customerId}`;
                
                const searchText = [
                    customerId,
                    customerName,
                    customerEmail,
                    prefixedId,
                    hashId,
                    customerIdWithSpace,
                    customerIdLabel,
                    idLabel,
                    idCompact
                ].join(' ');
                
                if (searchText.includes(filter)) {
                    row.style.display = '';
                    found = true;
                } else {
                    row.style.display = 'none';
                }
            }

            // Show/hide no results message
            if (found || filter === '') {
                noResults.classList.remove('show');
            } else {
                noResults.classList.add('show');
            }
        }
    </script>
</body>
</html> 