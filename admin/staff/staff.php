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
        $role = strtolower(trim($_POST['role'] ?? 'admin'));
        $password = $_POST['password'] ?? '';
        if ($username && $email && $first_name && $password && in_array($role, ['admin','superadmin'], true)) {
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
        $role = strtolower(trim($_POST['role'] ?? 'admin'));
        $is_active = (int)($_POST['is_active'] ?? 1) ? 1 : 0;
        $password = $_POST['password'] ?? '';
        if ($admin_id > 0 && $email && $first_name && in_array($role, ['admin','superadmin'], true)) {
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

// Fetch admins
$stmt = $pdo->prepare("SELECT admin_id, username, email, first_name, last_name, role, is_active, created_at FROM admin_user ORDER BY created_at DESC");
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .modal-actions { text-align:right; margin-top:1rem; }
        .btn.secondary { background:#555; }
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

        <table>
            <thead>
                <tr>
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
                <tr>
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
                <label>Username
                    <input type="text" name="username" id="username">
                </label>
                <label>Email*
                    <input type="email" name="email" id="email" required>
                </label>
                <div style="display:flex; gap:0.7rem;">
                    <label style="flex:1;">First Name*
                        <input type="text" name="first_name" id="first_name" required>
                    </label>
                    <label style="flex:1;">Last Name
                        <input type="text" name="last_name" id="last_name">
                    </label>
                </div>
                <div style="display:flex; gap:0.7rem;">
                    <label style="flex:1;">Role*
                        <select name="role" id="role">
                            <option value="admin">Admin</option>
                            <option value="superadmin">Superadmin</option>
                        </select>
                    </label>
                    <label style="flex:1;">Active
                        <select name="is_active" id="is_active">
                            <option value="1">Active</option>
                            <option value="0">Disabled</option>
                        </select>
                    </label>
                </div>
                <label>Password<?php /* required for create */ ?>
                    <input type="password" name="password" id="password">
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
    function openCreate() {
        document.getElementById('modalTitle').innerText = 'New Admin';
        document.getElementById('formAction').value = 'create';
        document.getElementById('admin_id').value = '';
        document.getElementById('username').value = '';
        document.getElementById('email').value = '';
        document.getElementById('first_name').value = '';
        document.getElementById('last_name').value = '';
        document.getElementById('role').value = 'admin';
        document.getElementById('is_active').value = '1';
        document.getElementById('password').value = '';
        modalBg.style.display = 'block';
    }
    function openEdit(admin) {
        document.getElementById('modalTitle').innerText = 'Edit Admin';
        document.getElementById('formAction').value = 'update';
        document.getElementById('admin_id').value = admin.admin_id;
        document.getElementById('username').value = admin.username;
        document.getElementById('email').value = admin.email;
        document.getElementById('first_name').value = admin.first_name;
        document.getElementById('last_name').value = admin.last_name;
        document.getElementById('role').value = admin.role;
        document.getElementById('is_active').value = admin.is_active ? '1' : '0';
        document.getElementById('password').value = '';
        modalBg.style.display = 'block';
    }
    function closeModal(){ modalBg.style.display = 'none'; }
    modalBg.addEventListener('click', (e)=>{ if(e.target===modalBg) closeModal(); });
    </script>
</body>
</html> 