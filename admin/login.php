<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

auth_start();
if (current_admin()) redirect(APP_URL . '/admin/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    if (admin_login($username, $password)) {
        // $next pochodzi z REQUEST_URI (require_admin()) — to już pełna ścieżka
        // od hosta (z ewentualnym podkatalogiem wdrożenia), więc nie doklejamy APP_URL.
        $next = $_POST['next'] ?? '';
        redirect($next && str_starts_with($next, '/') ? $next : APP_URL . '/admin/index.php');
    }
    $error = 'Nieprawidłowy login lub hasło.';
}
$next = $_GET['next'] ?? '';
?><!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Logowanie — Wydarzenia Online</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#f4f6fb;min-height:100vh;display:flex;align-items:center}.card{max-width:380px;margin:0 auto;border-radius:12px}</style>
</head>
<body>
<div class="card shadow-sm p-4 w-100">
  <h1 class="h4 mb-3 text-center">Panel admina</h1>
  <p class="text-center text-muted small mb-4">Wydarzenia Online — FEER</p>
  <?php if ($error): ?><div class="alert alert-danger py-2"><?= h($error) ?></div><?php endif; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= h($next) ?>">
    <div class="mb-3">
      <label for="username" class="form-label">Login</label>
      <input type="text" class="form-control" id="username" name="username" required autofocus>
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Hasło</label>
      <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Zaloguj się</button>
  </form>
</div>
</body>
</html>
