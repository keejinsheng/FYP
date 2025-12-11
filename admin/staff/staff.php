<?php
require_once '../../config/database.php';

// Only superadmin can access
if (!isAdmin()) { redirect('../auth/login.php'); }
if (!isSuperAdmin()) { redirect('../dashboard/dashboard.php'); }

$pdo = getDBConnection();
$success_message = '';
$error_message = '';

// Handle create/update actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $first_name = sanitize($_POST['first_name'] ?? '');
        $last_name = sanitize($_POST['last_name'] ?? '');
        $role = 'Staff'; // new admins default to Staff
        $password = $_POST['password'] ?? '';
        if ($username && $email && $first_name && !empty($password)) {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO admin_user (username, email, password_hash, first_name, last_name, role, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute([$username, $email, $hash, $first_name, $last_name, $role]);
                $success_message = 'Admin created successfully';
            } catch (Exception $e) {
                $error_message = 'Failed to create admin (maybe username/email exists)';
            }
        } else {
            $error_message = 'Please fill all required fields';
        }
    } elseif ($action === 'update') {
        $admin_id = (int)($_POST['admin_id'] ?? 0);
        $email = sanitize($_POST['email'] ?? '');
        $first_name = sanitize($_POST['first_name'] ?? '');
        $last_name = sanitize($_POST['last_name'] ?? '');
        $role = trim($_POST['role'] ?? 'Staff');
        $allowedRoles = ['Staff', 'Manager', 'Super Admin'];
        $is_active = (int)($_POST['is_active'] ?? 1) ? 1 : 0;
        $password = $_POST['password'] ?? '';
        if ($admin_id > 0 && $email && $first_name && in_array($role, $allowedRoles, true)) {
            try {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE admin_user SET email=?, first_name=?, last_name=?, role=?, is_active=?, password_hash=? WHERE admin_id=?");
                    $stmt->execute([$email, $first_name, $last_name, $role, $is_active, $hash, $admin_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE admin_user SET email=?, first_name=?, last_name=?, role=?, is_active=? WHERE admin_id=?");
                    $stmt->execute([$email, $first_name, $last_name, $role, $is_active, $admin_id]);
                }
                $success_message = 'Admin updated successfully';
            } catch (Exception $e) {
                $error_message = 'Failed to update admin';
            }
        } else {
            $error_message = 'Please provide valid admin information';
        }
    }
}

// Fetch admins (exclude Super Admin)
$stmt = $pdo->prepare("SELECT admin_id, username, email, first_name, last_name, role, is_active, created_at FROM admin_user WHERE role != 'Super Admin' ORDER BY created_at DESC");
$stmt->execute();
$admins = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - Superadmin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body { font-family: 'Inter', sans-serif; margin:0; background:#1a1a1a; color:#fff; }
        .admin-header { background:#2a2a2a; padding:1rem 2rem; }
        .header-content { max-width:1200px; margin:0 auto; display:flex; justify-content:space-between; align-items:center; }
        .nav a { color:#fff; text-decoration:none; margin-left:1rem; }
        .container { max-width:1200px; margin:2rem auto; padding:0 2rem; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; }
        .btn { background:#FF4B2B; color:#fff; border:none; padding:0.6rem 1rem; border-radius:6px; cursor:pointer; }
        table { width:100%; border-collapse:collapse; background:#2a2a2a; border-radius:8px; overflow:hidden; }
        th, td { padding:0.8rem; border-bottom:1px solid rgba(255,255,255,0.08); text-align:left; }
        th { background:#1a1a1a; color:#FF4B2B; }
        .badge { padding:0.2rem 0.5rem; border-radius:10px; font-size:0.75rem; }
        .badge.on { background:#28a745; color:#fff; }
        .badge.off { background:#dc3545; color:#fff; }
        .modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); }
        .modal { background:#2a2a2a; width:520px; margin:8vh auto; padding:1.2rem 1.4rem; border-radius:10px; }
        .modal label { display:block; margin-top:0.7rem; }
        .modal input, .modal select { width:100%; padding:0.5rem; background:#1a1a1a; color:#fff; border:1px solid #444; border-radius:6px; margin-top:0.3rem; }
        .modal input[readonly] { background:#2a2a2a; cursor:not-allowed; opacity:0.7; }
        .modal-actions { text-align:right; margin-top:1rem; }
        .btn.secondary { background:#555; }
        .form-row { display:flex; gap:1.2rem; flex-wrap:wrap; }
        .form-row > label { flex:1; margin-top:0.7rem; }

        .search-container {
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: flex-end;
        }
        .search-box {
            position: relative;
            width: 100%;
            max-width: 320px;
        }
        .search-box input {
            width: 100%;
            padding: 0.6rem 1rem 0.6rem 2.4rem;
            border-radius: 8px;
            border: 1px solid #555;
            background: #1a1a1a;
            color: #fff;
        }
        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 75, 43, 0.2);
        }
        .search-box i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }
        .no-results {
            text-align: center;
            color: #a0a0a0;
            padding: 1rem;
            display: none;
        }
        .no-results.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="header-content">
            <h2>Superadmin</h2>
            <div class="nav">
                <a href="../dashboard/dashboard.php">Dashboard</a>
                <a href="staff.php" style="background:#FF4B2B; padding:0.4rem 0.7rem; border-radius:6px;">Admins</a>
                <a href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="page-header">
            <h1>Admin Management</h1>
            <button class="btn" onclick="openCreate()"><i class="fas fa-user-plus"></i> New Admin</button>
        </div>
        <?php if ($success_message): ?><div style="color:#28a745; margin-bottom:1rem;"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
        <?php if ($error_message): ?><div style="color:#dc3545; margin-bottom:1rem;"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>

        <div class="search-container">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="adminSearch" placeholder="Search by ID, username, name, or email..." onkeyup="filterAdmins()">
            </div>
        </div>
        <div class="no-results" id="noAdminResults">No admins found.</div>
        <table id="adminTable">
            <thead>
                <tr>
                    <th>Admin ID</th>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $a): ?>

                <tr data-admin-id="<?php echo (int)$a['admin_id']; ?>"
                    data-admin-username="<?php echo htmlspecialchars(strtolower($a['username'])); ?>"
                    data-admin-name="<?php echo htmlspecialchars(strtolower(trim($a['first_name'] . ' ' . $a['last_name']))); ?>"
                    data-admin-email="<?php echo htmlspecialchars(strtolower($a['email'])); ?>">
                    <td><?php echo (int)$a['admin_id']; ?></td>
                    <td><?php echo htmlspecialchars($a['username']); ?></td>
                    <td><?php echo htmlspecialchars($a['first_name'] . ' ' . $a['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($a['email']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($a['role'])); ?></td>
                    <td><span class="badge <?php echo $a['is_active'] ? 'on' : 'off'; ?>"><?php echo $a['is_active'] ? 'Active' : 'Disabled'; ?></span></td>
                    <td>
                        <button class="btn" onclick='openEdit(<?php echo json_encode($a); ?>)'><i class="fas fa-edit"></i> Edit</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="modal-bg" id="modalBg">
        <div class="modal">
            <h3 id="modalTitle">New Admin</h3>
            <form method="POST" id="adminForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="admin_id" id="admin_id">
                <label>Username<span id="usernameRequired" style="color:#dc3545;">*</span>
                    <input type="text" name="username" id="username" required>
                </label>
                <label>Email*
                    <input type="email" name="email" id="email" required>
                </label>
                <div class="form-row">
                    <label>First Name*
                        <input type="text" name="first_name" id="first_name" required>
                    </label>
                    <label>Last Name
                        <input type="text" name="last_name" id="last_name">
                    </label>
                </div>
                <div class="form-row" id="roleRow">
                    <label>Role*
                        <select name="role" id="role">
                            <option value="Staff">Staff</option>
                            <option value="Manager">Manager</option>
                            <option value="Super Admin">Super Admin</option>
                        </select>
                    </label>
                    <label>Active
                        <select name="is_active" id="is_active">
                            <option value="1">Active</option>
                            <option value="0">Disabled</option>
                        </select>
                    </label>
                </div>
                <label>Password<span id="passwordRequired" style="color:#dc3545;">*</span>
                    <input type="password" name="password" id="password" required>
                    <small style="color:#888; font-size:0.85rem; display:block; margin-top:0.3rem;" id="passwordHint">Leave blank to keep current password (edit mode only)</small>
                </label>
                <div class="modal-actions">
                    <button type="button" class="btn secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const modalBg = document.getElementById('modalBg');
    const form = document.getElementById('adminForm');
    const roleRow = document.getElementById('roleRow');
    const roleSelect = document.getElementById('role');
    const activeSelect = document.getElementById('is_active');
    function openCreate() {
        document.getElementById('modalTitle').innerText = 'New Admin';
        document.getElementById('formAction').value = 'create';
        document.getElementById('admin_id').value = '';
        const usernameField = document.getElementById('username');
        usernameField.value = '';
        usernameField.removeAttribute('readonly');
        usernameField.required = true;
        document.getElementById('usernameRequired').style.display = 'inline';
        document.getElementById('email').value = '';
        document.getElementById('first_name').value = '';
        document.getElementById('last_name').value = '';
        roleSelect.value = 'Staff';
        roleSelect.disabled = true;
        activeSelect.value = '1';
        activeSelect.disabled = true;
        const passwordField = document.getElementById('password');
        passwordField.value = '';
        passwordField.required = true;
        document.getElementById('passwordRequired').style.display = 'inline';
        document.getElementById('passwordHint').style.display = 'none';
        roleRow.style.display = 'none';
        modalBg.style.display = 'block';
    }
    function openEdit(admin) {
        document.getElementById('modalTitle').innerText = 'Edit Admin';
        document.getElementById('formAction').value = 'update';
        document.getElementById('admin_id').value = admin.admin_id;
        const usernameField = document.getElementById('username');
        usernameField.value = admin.username;
        usernameField.setAttribute('readonly', 'readonly');
        usernameField.required = false;
        document.getElementById('usernameRequired').style.display = 'none';
        document.getElementById('email').value = admin.email;
        document.getElementById('first_name').value = admin.first_name;
        document.getElementById('last_name').value = admin.last_name;
        roleSelect.value = admin.role;
        roleSelect.disabled = false;
        activeSelect.value = admin.is_active ? '1' : '0';
        activeSelect.disabled = false;
        const passwordField = document.getElementById('password');
        passwordField.value = '';
        passwordField.required = false;
        document.getElementById('passwordRequired').style.display = 'none';
        document.getElementById('passwordHint').style.display = 'block';
        roleRow.style.display = 'flex';
        modalBg.style.display = 'block';
    }
    function closeModal(){ modalBg.style.display = 'none'; }
    modalBg.addEventListener('click', (e)=>{ if(e.target===modalBg) closeModal(); });

    
    function filterAdmins() {
        const input = document.getElementById('adminSearch');
        const filter = input.value.toLowerCase().trim();
        const table = document.getElementById('adminTable');
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        const noResults = document.getElementById('noAdminResults');
        let found = false;

        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const adminId = (row.getAttribute('data-admin-id') || '').toLowerCase();
            const adminUsername = row.getAttribute('data-admin-username') || '';
            const adminName = row.getAttribute('data-admin-name') || '';
            const adminEmail = row.getAttribute('data-admin-email') || '';
            const idPrefix = `admin${adminId}`;
            const hashId = `#${adminId}`;
            const idLabel = `id ${adminId}`;
            const adminIdLabel = `admin id ${adminId}`;

            const searchText = [
                adminId,
                adminUsername,
                adminName,
                adminEmail,
                idPrefix,
                hashId,
                idLabel,
                adminIdLabel
            ].join(' ');

            if (filter === '' || searchText.includes(filter)) {
                row.style.display = '';
                found = true;
            } else {
                row.style.display = 'none';
            }
        }

        if (found || filter === '') {
            noResults.classList.remove('show');
        } else {
            noResults.classList.add('show');
        }
    }
    </script>
</body>
</html> 