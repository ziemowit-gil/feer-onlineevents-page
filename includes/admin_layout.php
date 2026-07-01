<?php
/**
 * includes/admin_layout.php — wspólny layout panelu admina
 * Użycie: ustaw $PAGE_TITLE, include ten plik, wypisz treść, include admin_footer.php
 */
$admin = current_admin();
?><!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= h($PAGE_TITLE ?? 'Panel admina') ?> — Wydarzenia Online</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--ev-blue:#1e6dff;}
body{background:#f4f6fb}
.navbar-brand{font-weight:700;color:var(--ev-blue)!important}
.nav-link.active{font-weight:700;color:var(--ev-blue)!important}
.btn-primary{background:var(--ev-blue);border-color:var(--ev-blue)}
.btn-primary:hover{background:#1657cc;border-color:#1657cc}
.badge-source-szo{background:#e0ecff;color:var(--ev-blue)}
.badge-source-manual{background:#f1e9ff;color:#6d28d9}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-4">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="<?= APP_URL ?>/admin/index.php"><i class="bi bi-calendar2-event me-1"></i>Wydarzenia Online</a>
    <div class="d-flex align-items-center gap-3 ms-auto">
      <a class="nav-link d-inline <?= basename($_SERVER['SCRIPT_NAME'])==='index.php'?'active':'' ?>" href="<?= APP_URL ?>/admin/index.php">Wydarzenia</a>
      <a class="nav-link d-inline <?= basename($_SERVER['SCRIPT_NAME'])==='settings.php'?'active':'' ?>" href="<?= APP_URL ?>/admin/settings.php">Ustawienia</a>
      <a class="nav-link d-inline" href="<?= APP_URL ?>/index.php" target="_blank">Strona publiczna <i class="bi bi-box-arrow-up-right"></i></a>
      <span class="text-muted small"><?= h($admin['username'] ?? '') ?></span>
      <a class="btn btn-sm btn-outline-secondary" href="<?= APP_URL ?>/admin/logout.php">Wyloguj</a>
    </div>
  </div>
</nav>
<div class="container-fluid px-4 pb-5">
<?php foreach (flash_get() as $f): ?>
  <div class="alert alert-<?= $f['type']==='error'?'danger':h($f['type']) ?> alert-dismissible fade show" role="alert">
    <?= h($f['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endforeach; ?>
