<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/s3_client.php';
require_once dirname(__DIR__) . '/includes/r2.php';
require_once dirname(__DIR__) . '/includes/ms365.php';

require_admin();

$fields = ['feed_url', 'site_brand', 'site_subtitle', 'footer_text', 'contact_email', 'funding_note', 'feer_url',
           'logo1_src', 'logo1_alt', 'logo2_src', 'logo2_alt', 'logo3_src', 'logo3_alt'];

$r2_fields = ['r2_account_id', 'r2_endpoint', 'r2_region', 'r2_bucket', 'r2_access_key', 'r2_prefix', 'r2_public_base'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (isset($_POST['save_settings'])) {
        foreach ($fields as $f) {
            setting_set($f, trim($_POST[$f] ?? ''));
        }
        flash_set('success', 'Ustawienia zapisane.');
    } elseif (isset($_POST['save_r2'])) {
        foreach ($r2_fields as $f) {
            setting_set($f, trim($_POST[$f] ?? ''));
        }
        $sk = trim((string)($_POST['r2_secret_key'] ?? ''));
        if ($sk !== '') {
            setting_set('r2_secret_key', $sk);
        }
        flash_set('success', 'Konfiguracja R2 zapisana.');
    } elseif (isset($_POST['test_r2'])) {
        $t = s3_test_connection(r2_cfg());
        flash_set($t['ok'] ? 'success' : 'error', $t['msg']);
    } elseif (isset($_POST['save_ms365'])) {
        setting_set('ms_tenant_id', trim($_POST['ms_tenant_id'] ?? ''));
        setting_set('ms_client_id', trim($_POST['ms_client_id'] ?? ''));
        $sk = trim((string)($_POST['ms_client_secret'] ?? ''));
        if ($sk !== '') {
            setting_set('ms_client_secret', $sk);
        }
        flash_set('success', 'Konfiguracja Microsoft 365 zapisana.');
    } elseif (isset($_POST['update_account'])) {
        $admin = current_admin();
        $email = trim($_POST['account_email'] ?? '') ?: null;
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash_set('error', 'Nieprawidłowy adres e-mail.');
        } else {
            $taken = $email ? db_one("SELECT id FROM admins WHERE email=? AND id!=?", [$email, $admin['id']]) : null;
            if ($taken) {
                flash_set('error', 'Ten adres e-mail jest już przypisany do innego konta.');
            } else {
                db_update('admins', ['email' => $email], $admin['id']);
                flash_set('success', 'Adres e-mail zapisany.');
            }
        }
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
  <?php $r2cfg = r2_cfg(); $r2ready = s3_config_ready($r2cfg); ?>
  <form method="post" class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
      Cloudflare R2 (nagrania / prezentacje)
      <span class="badge <?= $r2ready ? 'bg-success' : 'bg-secondary' ?>"><?= $r2ready ? 'skonfigurowany' : 'brak konfiguracji' ?></span>
    </div>
    <div class="card-body">
      <?= csrf_field() ?>
      <p class="text-muted small">Pozwala przeglądać pliki w buckecie R2 bezpośrednio z formularza edycji wydarzenia,
        zamiast ręcznie wklejać długie adresy URL.</p>
      <div class="mb-2">
        <label class="form-label">Cloudflare Account ID</label>
        <input type="text" name="r2_account_id" class="form-control form-control-sm" value="<?= h($r2cfg['account_id']) ?>" placeholder="np. a1b2c3...">
        <div class="form-text">Na tej podstawie budowany jest endpoint, jeśli pole poniżej jest puste.</div>
      </div>
      <div class="mb-2">
        <label class="form-label">Endpoint (opcjonalnie — nadpisuje)</label>
        <input type="text" name="r2_endpoint" class="form-control form-control-sm" value="<?= h(setting('r2_endpoint')) ?>" placeholder="https://&lt;acc&gt;.r2.cloudflarestorage.com">
      </div>
      <div class="row g-2">
        <div class="col-7 mb-2">
          <label class="form-label">Bucket</label>
          <input type="text" name="r2_bucket" class="form-control form-control-sm" value="<?= h($r2cfg['bucket']) ?>">
        </div>
        <div class="col-5 mb-2">
          <label class="form-label">Region</label>
          <input type="text" name="r2_region" class="form-control form-control-sm" value="<?= h($r2cfg['region']) ?>" placeholder="auto">
        </div>
      </div>
      <div class="mb-2">
        <label class="form-label">Access Key ID</label>
        <input type="text" name="r2_access_key" class="form-control form-control-sm" value="<?= h($r2cfg['access_key']) ?>" autocomplete="off">
      </div>
      <div class="mb-2">
        <label class="form-label">Secret Access Key</label>
        <input type="password" name="r2_secret_key" class="form-control form-control-sm" value="" autocomplete="off" placeholder="<?= $r2cfg['secret_key'] !== '' ? '•••••• (zostaw puste, by nie zmieniać)' : '' ?>">
      </div>
      <div class="mb-2">
        <label class="form-label">Prefiks bazowy (opcjonalnie)</label>
        <input type="text" name="r2_prefix" class="form-control form-control-sm" value="<?= h($r2cfg['prefix']) ?>" placeholder="np. adngo/webinary">
      </div>
      <div class="mb-3">
        <label class="form-label">Publiczny adres bazowy</label>
        <input type="text" name="r2_public_base" class="form-control form-control-sm" value="<?= h($r2cfg['public_base']) ?>" placeholder="https://pub-xxxx.r2.dev">
        <div class="form-text">Wymagany, żeby przeglądarka plików mogła podać gotowy link do wklejenia.</div>
      </div>
      <button type="submit" name="save_r2" value="1" class="btn btn-sm btn-primary"><i class="bi bi-save me-1"></i>Zapisz</button>
    </div>
  </form>

  <form method="post" class="mb-4">
    <?= csrf_field() ?>
    <button type="submit" name="test_r2" value="1" class="btn btn-sm btn-outline-secondary w-100" <?= $r2ready ? '' : 'disabled' ?>>
      <i class="bi bi-plug me-1"></i>Testuj połączenie z R2
    </button>
  </form>

  <?php $ms_ready = ms365_available(); ?>
  <form method="post" class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
      Logowanie Microsoft 365
      <span class="badge <?= $ms_ready ? 'bg-success' : 'bg-secondary' ?>"><?= $ms_ready ? 'aktywne' : 'wyłączone' ?></span>
    </div>
    <div class="card-body">
      <?= csrf_field() ?>
      <p class="text-muted small">Pozwala administratorom logować się kontem Microsoft 365 zamiast (lub obok)
        loginu i hasła. Wymaga rejestracji aplikacji w Azure AD.</p>
      <div class="mb-2">
        <label class="form-label">Tenant ID</label>
        <input type="text" name="ms_tenant_id" class="form-control form-control-sm" value="<?= h(setting('ms_tenant_id')) ?>" placeholder="np. a1b2c3d4-...">
      </div>
      <div class="mb-2">
        <label class="form-label">Application (client) ID</label>
        <input type="text" name="ms_client_id" class="form-control form-control-sm" value="<?= h(setting('ms_client_id')) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Client secret</label>
        <input type="password" name="ms_client_secret" class="form-control form-control-sm" value="" autocomplete="off" placeholder="<?= setting('ms_client_secret') !== '' ? '•••••• (zostaw puste, by nie zmieniać)' : '' ?>">
      </div>
      <div class="form-text mb-2">
        Redirect URI do zarejestrowania w Azure AD:<br>
        <code><?= h(ms365_redirect_uri()) ?></code>
      </div>
      <button type="submit" name="save_ms365" value="1" class="btn btn-sm btn-primary"><i class="bi bi-save me-1"></i>Zapisz</button>
    </div>
  </form>

  <?php $me = current_admin(); ?>
  <form method="post" class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-bold">Twoje konto</div>
    <div class="card-body">
      <?= csrf_field() ?>
      <div class="mb-2">
        <label class="form-label">Login</label>
        <input type="text" class="form-control form-control-sm" value="<?= h($me['username']) ?>" disabled>
      </div>
      <div class="mb-2">
        <label class="form-label">Adres e-mail (do logowania Microsoft 365)</label>
        <input type="email" name="account_email" class="form-control form-control-sm" value="<?= h($me['email']) ?>" placeholder="np. jan.kowalski@feer.org.pl">
        <div class="form-text">Musi być zgodny z kontem Microsoft, którym chcesz się logować.</div>
      </div>
      <button type="submit" name="update_account" value="1" class="btn btn-sm btn-outline-primary">Zapisz e-mail</button>
    </div>
  </form>

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
