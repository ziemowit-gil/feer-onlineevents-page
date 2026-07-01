<?php
/**
 * admin/ms365_login.php — start logowania Microsoft 365 (przekierowanie do Azure AD)
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/ms365.php';

auth_start();
if (current_admin()) redirect(APP_URL . '/admin/index.php');

if (!ms365_available()) {
    flash_set('error', 'Logowanie Microsoft 365 nie jest skonfigurowane.');
    redirect(APP_URL . '/admin/login.php');
}

$next = $_GET['next'] ?? '';
$redirect_after = ($next && str_starts_with($next, '/')) ? $next : (APP_URL . '/admin/index.php');

redirect(ms365_auth_url($redirect_after));
