<?php
/**
 * includes/auth.php — prosta autoryzacja panelu admina (jedna rola: admin)
 */

function auth_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('feev_session');
        session_start();
    }
}

function current_admin(): ?array {
    auth_start();
    if (empty($_SESSION['admin_id'])) return null;
    static $cache = null;
    if ($cache === null) {
        $cache = db_one("SELECT id, username FROM admins WHERE id=?", [$_SESSION['admin_id']]) ?: false;
    }
    return $cache ?: null;
}

function require_admin(): void {
    auth_start();
    if (!current_admin()) {
        redirect(APP_URL . '/admin/login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
    }
}

function admin_login(string $username, string $password): bool {
    $admin = db_one("SELECT * FROM admins WHERE username=?", [$username]);
    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        return false;
    }
    auth_start();
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$admin['id'];
    return true;
}

function admin_logout(): void {
    auth_start();
    $_SESSION = [];
    session_destroy();
}
