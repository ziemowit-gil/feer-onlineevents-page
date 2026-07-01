<?php
/**
 * install.php — graficzny kreator instalacji (bez potrzeby CLI/SSH).
 * Działa tylko dopóki w bazie nie istnieje żadne konto admina — potem
 * zawsze wyświetla ekran „już zainstalowane", nawet jeśli ktoś wejdzie
 * na ten adres ponownie (ochrona przed przejęciem panelu).
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

auth_start();

$already_installed = (bool)db_one("SELECT id FROM admins LIMIT 1");

$errors = [];
if (!$already_installed && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $password2= (string)($_POST['password2'] ?? '');
    $email    = trim($_POST['email'] ?? '') ?: null;
    $brand    = trim($_POST['site_brand'] ?? '');
    $feed_url = trim($_POST['feed_url'] ?? '');

    if ($username === '') $errors[] = 'Podaj login administratora.';
    if (strlen($password) < 8) $errors[] = 'Hasło musi mieć co najmniej 8 znaków.';
    if ($password !== $password2) $errors[] = 'Hasła nie są identyczne.';
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Nieprawidłowy adres e-mail.';
    if ($feed_url && !filter_var($feed_url, FILTER_VALIDATE_URL)) $errors[] = 'Nieprawidłowy adres feedu SZO.';

    // Bariera bezpieczeństwa — nawet gdyby ktoś zdążył utworzyć konto w międzyczasie.
    if (!$errors && db_one("SELECT id FROM admins LIMIT 1")) {
        $already_installed = true;
    }

    if (!$errors && !$already_installed) {
        $admin_id = db_insert('admins', [
            'username'      => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'email'         => $email,
        ]);
        if ($brand)    setting_set('site_brand', $brand);
        if ($feed_url) setting_set('feed_url', $feed_url);

        admin_login_session((int)$admin_id);
        flash_set('success', 'Instalacja zakończona — witaj w panelu administracyjnym.');
        redirect(APP_URL . '/admin/index.php');
    }
}
?><!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Instalacja — Wydarzenia Online</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--ev-blue:#1e6dff}
body{background:linear-gradient(160deg,#12131a 0%,#161c33 55%,#173163 100%);min-height:100vh;display:flex;align-items:center;padding:2rem 0}
.wizard-card{max-width:560px;margin:0 auto;border-radius:16px;border:none;box-shadow:0 24px 60px -20px rgba(0,0,0,.45)}
.wizard-header{padding:2rem 2rem 0}
.btn-primary{background:var(--ev-blue);border-color:var(--ev-blue)}
.step-badge{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:var(--ev-blue);color:#fff;font-size:.75rem;font-weight:700;margin-right:8px}
</style>
</head>
<body>
<div class="container">
<div class="card wizard-card">
  <div class="wizard-header">
    <h1 class="h4 mb-1"><i class="bi bi-calendar2-event me-2" style="color:var(--ev-blue)"></i>Wydarzenia Online</h1>
    <p class="text-muted small mb-0">Kreator instalacji</p>
  </div>
  <div class="card-body p-4">
    <?php if ($already_installed): ?>
      <div class="alert alert-info">
        <i class="bi bi-check-circle me-1"></i>Instalacja została już wykonana wcześniej.
      </div>
      <a href="<?= APP_URL ?>/admin/login.php" class="btn btn-primary w-100">Przejdź do logowania</a>
    <?php else: ?>
      <?php foreach ($errors as $e): ?>
        <div class="alert alert-danger py-2"><?= h($e) ?></div>
      <?php endforeach; ?>
      <form method="post" novalidate>
        <?= csrf_field() ?>

        <p class="fw-bold mb-3"><span class="step-badge">1</span>Konto administratora</p>
        <div class="mb-3">
          <label class="form-label">Login</label>
          <input type="text" name="username" class="form-control" value="<?= h($_POST['username'] ?? 'admin') ?>" required autofocus>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Hasło</label>
            <input type="password" name="password" class="form-control" required minlength="8">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Powtórz hasło</label>
            <input type="password" name="password2" class="form-control" required minlength="8">
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label">Adres e-mail (opcjonalnie)</label>
          <input type="email" name="email" class="form-control" value="<?= h($_POST['email'] ?? '') ?>" placeholder="np. admin@feer.org.pl">
          <div class="form-text">Umożliwi później logowanie kontem Microsoft 365 (do skonfigurowania w Ustawieniach).</div>
        </div>

        <p class="fw-bold mb-3"><span class="step-badge">2</span>Podstawowa konfiguracja <span class="text-muted fw-normal">(można zmienić później)</span></p>
        <div class="mb-3">
          <label class="form-label">Nazwa serwisu</label>
          <input type="text" name="site_brand" class="form-control" value="<?= h($_POST['site_brand'] ?? 'Wydarzenia Online — FEER') ?>">
        </div>
        <div class="mb-4">
          <label class="form-label">Adres feedu SZO</label>
          <input type="url" name="feed_url" class="form-control" value="<?= h($_POST['feed_url'] ?? SZO_FEED_URL) ?>">
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">
          <i class="bi bi-rocket-takeoff me-1"></i>Zainstaluj i zaloguj
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>
</div>
</body>
</html>
