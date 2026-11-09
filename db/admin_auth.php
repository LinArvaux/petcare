<?php
require_once __DIR__ . '/app.php';

function admin_is_logged_in(): bool {
    return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_logged_in']);
}
function admin_require_login(): void {
    if (!admin_is_logged_in()) {
        flash_set('error', 'Please log in to access the admin area.');
        redirect('./login.php');
    }
    $timeout = 1800;
    if (!empty($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity'] > $timeout)) {
        admin_logout();
        flash_set('error', 'Your session expired. Please log in again.');
        redirect('./login.php');
    }
    $_SESSION['admin_last_activity'] = time();
}
function admin_logout(): void {
    $_SESSION['admin_id'] = null;
    $_SESSION['admin_logged_in'] = false;
    session_unset();
    session_destroy();
}
function current_admin(): ?array {
    if (!admin_is_logged_in()) return null;
    $conn = get_db_connection();
    $stmt = $conn->prepare('SELECT id, full_name, email, role FROM admins WHERE id = ? AND is_active = 1');
    $stmt->bind_param('i', $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();
    return $admin ?: null;
}
admin_require_login();