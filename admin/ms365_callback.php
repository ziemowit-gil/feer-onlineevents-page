<?php
/**
 * admin/ms365_callback.php — odbiór odpowiedzi Microsoft (Azure AD) i logowanie.
 * Dopasowanie konta po e-mailu / microsoft_id w tabeli admins — logowanie NIE
 * tworzy nowych kont automatycznie.
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/ms365.php';

auth_start();

if (!ms365_available()) {
    redirect(APP_URL . '/admin/login.php');
}

$error = '';
$code  = trim($_GET['code']  ?? '');
$state = trim($_GET['state'] ?? '');

if (!empty($_GET['error'])) {
    $error = 'Microsoft: ' . ($_GET['error_description'] ?? $_GET['error']);
}

if (!$error && $state) {
    $expected = $_SESSION['ms365_state'] ?? '';
    if (!$expected || $expected !== $state) {
        try {
            $row = db_one("SELECT * FROM oauth_states WHERE state=? AND created_at > ?", [$state, time() - 900]);
            if ($row) {
                $_SESSION['ms365_state']    = $state;
                $_SESSION['ms365_verifier'] = $row['verifier'];
                $_SESSION['ms365_redirect'] = $row['redirect_to'];
                $expected = $state;
            }
        } catch (\Throwable $e) {}
    }
    if ($state !== $expected) {
        $error = 'Nieprawidłowy parametr state — spróbuj zalogować się ponownie.';
    }
} elseif (!$error) {
    $error = 'Brak parametru state w odpowiedzi Microsoft.';
}

if (!$error && !$code) {
    $error = 'Brak kodu autoryzacyjnego w odpowiedzi Microsoft.';
}

$redirect_after = $_SESSION['ms365_redirect'] ?? (APP_URL . '/admin/index.php');

if (!$error) {
    $tokens = ms365_exchange_code($code);

    try { db()->prepare("DELETE FROM oauth_states WHERE state=?")->execute([$state]); } catch (\Throwable $e) {}
    unset($_SESSION['ms365_state'], $_SESSION['ms365_verifier'], $_SESSION['ms365_redirect']);

    if (empty($tokens['access_token'])) {
        $error = 'Nie udało się uzyskać tokenu: ' . ($tokens['error_description'] ?? $tokens['error'] ?? 'nieznany błąd');
    }
}

if (!$error) {
    $ms_user = ms365_get_user($tokens['access_token']);
    $email   = $ms_user['mail'] ?? $ms_user['userPrincipalName'] ?? '';
    if (!$email) {
        $error = 'Nie udało się pobrać adresu e-mail z konta Microsoft.';
    }
}

if (!$error) {
    $ms_id = $ms_user['id'] ?? '';
    $admin = db_one("SELECT * FROM admins WHERE email = ? OR (microsoft_id != '' AND microsoft_id = ?)", [$email, $ms_id]);

    if (!$admin) {
        $error = 'no_account';
    } else {
        if ($admin['microsoft_id'] !== $ms_id) {
            db_update('admins', ['microsoft_id' => $ms_id], (int)$admin['id']);
        }
        admin_login_session((int)$admin['id']);
        redirect($redirect_after);
    }
}
?><!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Logowanie Microsoft 365 — Wydarzenia Online</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#f4f6fb;min-height:100vh;display:flex;align-items:center}.card{max-width:460px;margin:0 auto;border-radius:12px}</style>
</head>
<body>
<div class="card shadow-sm p-4 w-100">
  <h1 class="h5 mb-3 text-center">Logowanie Microsoft 365</h1>
  <?php if ($error === 'no_account'): ?>
    <div class="alert alert-warning">
      Zalogowano do Microsoft jako <strong><?= h($email ?? '') ?></strong>, ale nie znaleziono
      konta admina powiązanego z tym adresem e-mail. Poproś administratora o dodanie
      tego adresu w Ustawieniach → Twoje konto.
    </div>
  <?php else: ?>
    <div class="alert alert-danger"><?= h($error) ?></div>
  <?php endif; ?>
  <a href="<?= APP_URL ?>/admin/login.php" class="btn btn-primary w-100">Wróć do logowania</a>
</div>
</body>
</html>
