<?php
require_once __DIR__ . '/../db/app.php';

if (!empty($_SESSION['admin_id']) && !empty($_SESSION['admin_logged_in'])) {
    redirect('dashboard.php');
}
$errors = [];
if (is_post()) {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($email === '' || $password === '') {
            $errors[] = 'Please enter both email and password.';
        } else {
            $conn = get_db_connection();
            $stmt = $conn->prepare('SELECT id, full_name, email, password_hash, role, is_active FROM admins WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$admin || !$admin['is_active']) {
                $errors[] = 'Invalid credentials or inactive account.';
            } elseif (!password_verify($password, $admin['password_hash'])) {
                $errors[] = 'Invalid credentials.';
            } else {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_last_activity'] = time();
                $upd = $conn->prepare('UPDATE admins SET last_login = NOW() WHERE id = ?');
                $upd->bind_param('i', $admin['id']);
                $upd->execute();
                $upd->close();
                redirect('dashboard.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | PetCare AI</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../design/css/style.css">
</head>
<body>
<div class="admin-login-wrap" style="display:flex; justify-content:center; align-items:center; height:100vh;">
    <div class="admin-login-card panel" style="width: 100%; max-width: 400px; padding: 40px;">
        <h1 style="text-align:center;">Admin Login</h1>
        <p style="text-align:center; color:var(--bark-700);">Restricted area</p>
        <?php if ($msg = flash_get('error')): ?>
            <div class="alert alert-error"><?= h($msg) ?></div>
        <?php endif; ?>
        <?php foreach ($errors as $err): ?>
            <div class="alert alert-error"><?= h($err) ?></div>
        <?php endforeach; ?>
        <form method="POST" action="login.php" novalidate>
            <?= csrf_field() ?>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autofocus value="<?= h($_POST['email'] ?? '') ?>">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:18px;">Log In</button>
        </form>
        <!-- <div class="alert alert-success" style="margin-top:20px; font-size: 0.85rem;">
            Demo account: <strong>admin@petcare.local</strong> / <strong>Admin@12345</strong>
        </div> -->
    </div>
</div>
</body>
</html>