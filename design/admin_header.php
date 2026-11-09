<?php
$current = basename($_SERVER['SCRIPT_NAME']);
function nav_active($file, $current) { return $file === $current ? 'active' : ''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? h($page_title) . ' | ' : '' ?>PetCare Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../design/css/style.css">
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="brand"><span class="paw">🐾</span> PetCare Admin</div>
        <nav>
            <a href="/pet_system_real/admin/dashboard.php" class="<?= nav_active('dashboard.php', $current) ?>">Dashboard</a>
            <div class="section-label">Knowledge Base</div>
            <a href="/pet_system_real/admin/conditions.php" class="<?= nav_active('conditions.php', $current) ?>">Pet Conditions</a>
            <div class="section-label">Image Model</div>
            <a href="/pet_system_real/admin/image-review.php" class="<?= nav_active('image-review.php', $current) ?>">Image AI & Dataset</a>
            <div class="section-label">Cost Model</div>
            <a href="/pet_system_real/admin/cost-data.php" class="<?= nav_active('cost-data.php', $current) ?>">Cost Estimates & Model Training</a>
        </nav>
        <div class="sidebar-footer">
            Signed in as<br>
            <strong><?= h($admin['full_name'] ?? '') ?></strong><br>
            <span style="color: var(--moss-300)"><?= h($admin['role'] ?? '') ?></span>
            <form method="POST" action="/pet_system_real/admin/logout.php">
                <button type="submit" class="btn btn-outline btn-sm" style="width:100%; margin-top:8px; color:#fff; border-color: rgba(255,255,255,0.3);">Log out</button>
            </form>
        </div>
    </aside>
    <main class="admin-main">