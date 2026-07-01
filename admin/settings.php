<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_admin();

$fields = ['feed_url', 'site_brand', 'site_subtitle', 'footer_text', 'contact_email', 'funding_note', 'feer_url',
           'logo1_src', 'logo1_alt', 'logo2_src', 'logo2_alt', 'logo3_src', 'logo3_alt'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (isset($_POST['save_settings'])) {
        foreach ($fields as $f) {
            setting_set($f, trim($_POST[$f] ?? ''));
        }
        flash_set('success', 'Ustawienia zapisane.');
    } elseif (isset($_POST['change_password'])) {
        $admin = current_admin();
        $current = (string)($_POST['current_password'] ?? '');
        $new1    = (string)($_POST['new_password'] ?? '');
        $new2    = (string)($_POST['new_password2'] ?? '');
        $row = db_one("SELECT password_hash FROM admins WHERE id=?", [$admin['id']]);
        if (!password_verify($current, $row['password_hash'])) {
            flash_set('error', 'Obecne hasło jest nieprawidłowe.');
        } elseif (strlen($new1) < 8) {
            flash_set('error', 'Nowe hasło musi mieć co najmniej 8 znaków.');
        } elseif ($new1 !== $new2) {
            flash_set('error', 'Nowe hasła nie są identyczne.');
        } else {
            db_update('admins', ['password_hash' => password_hash($new1, PASSWORD_DEFAULT)], $admin['id']);
            flash_set('success', 'Hasło zmienione.');
        }
    }
    redirect(APP_URL . '/admin/settings.php');
}

$values = [];
foreach ($fields as $f) $values[$f] = setting($f);

$PAGE_TITLE = 'Ustawienia';
include dirname(__DIR__) . '/includes/admin_layout.php';
?>
<h1 class="h4 mb-4">Ustawienia</h1>

<div class="row g-4">
<div class="col-lg-7">
  <form method="post" class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-bold">Synchronizacja z SZO</div>
    <div class="card-body">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Adres feedu SZO</label>
        <input type="url" name="feed_url" class="form-control" value="<?= h($values['feed_url'] ?: SZO_FEED_URL) ?>">
        <div class="form-text">Publiczny endpoint JSON, np. https://szo.feer.org.pl/events/public/feed.php</div>
      </div>
    </div>
  </form>

  <form method="post" class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-bold">Strona publiczna</div>
    <div class="card-body">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Nazwa serwisu (tytuł strony, pasek górny)</label>
        <input type="text" name="site_brand" class="form-control" value="<?= h($values['site_brand']) ?>" placeholder="Wydarzenia Online — FEER">
      </div>
      <div class="mb-3">
        <label class="form-label">Podtytuł</label>
        <input type="text" name="site_subtitle" class="form-control" value="<?= h($values['site_subtitle']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Link do feer.org.pl w pasku górnym</label>
        <input type="url" name="feer_url" class="form-control" value="<?= h($values['feer_url'] ?: 'https://feer.org.pl') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">E-mail kontaktowy</label>
        <input type="email" name="contact_email" class="form-control" value="<?= h($values['contact_email']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Informacja o finansowaniu</label>
        <textarea name="funding_note" class="form-control" rows="2"><?= h($values['funding_note']) ?></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Stopka</label>
        <input type="text" name="footer_text" class="form-control" value="<?= h($values['footer_text']) ?>">
      </div>
      <hr>
      <p class="fw-bold small mb-2">Loga (do 3, pokazywane pod treścią)</p>
      <?php foreach ([1, 2, 3] as $i): ?>
      <div class="row g-2 mb-2">
        <div class="col-8"><input type="url" name="logo<?= $i ?>_src" class="form-control form-control-sm" placeholder="URL grafiki" value="<?= h($values["logo{$i}_src"]) ?>"></div>
        <div class="col-4"><input type="text" name="logo<?= $i ?>_alt" class="form-control form-control-sm" placeholder="Tekst alternatywny" value="<?= h($values["logo{$i}_alt"]) ?>"></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="card-footer bg-white">
      <button type="submit" name="save_settings" value="1" class="btn btn-primary">Zapisz ustawienia</button>
    </div>
  </form>
</div>

<div class="col-lg-5">
  <form method="post" class="card shadow-sm">
    <div class="card-header bg-white fw-bold">Zmiana hasła</div>
    <div class="card-body">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Obecne hasło</label>
        <input type="password" name="current_password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Nowe hasło</label>
        <input type="password" name="new_password" class="form-control" required minlength="8">
      </div>
      <div class="mb-3">
        <label class="form-label">Powtórz nowe hasło</label>
        <input type="password" name="new_password2" class="form-control" required minlength="8">
      </div>
    </div>
    <div class="card-footer bg-white">
      <button type="submit" name="change_password" value="1" class="btn btn-outline-primary">Zmień hasło</button>
    </div>
  </form>
</div>
</div>

<?php include dirname(__DIR__) . '/includes/admin_footer.php'; ?>
