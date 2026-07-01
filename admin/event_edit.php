<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$event = db_one("SELECT * FROM events WHERE id=?", [$id]);
if (!$event) { flash_set('error', 'Wydarzenie nie istnieje.'); redirect(APP_URL . '/admin/index.php'); }
$is_manual = $event['source'] === 'manual';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $update = [
        'presenter'           => trim($_POST['presenter'] ?? '') ?: null,
        'accent'              => in_array($_POST['accent'] ?? '', ['blue', 'orange', 'green'], true) ? $_POST['accent'] : null,
        'presentation_status' => in_array($_POST['presentation_status'] ?? '', ['none', 'soon', 'ready'], true) ? $_POST['presentation_status'] : 'none',
        'presentation_url'    => trim($_POST['presentation_url'] ?? '') ?: null,
        'is_visible'          => isset($_POST['is_visible']) ? 1 : 0,
    ];

    if ($is_manual) {
        $update['title']        = trim($_POST['title'] ?? '') ?: $event['title'];
        $update['description']  = trim($_POST['description'] ?? '') ?: null;
        $update['type']         = in_array($_POST['type'] ?? '', ['webinar', 'stationary'], true) ? $_POST['type'] : 'webinar';
        $update['start_at']     = trim($_POST['start_at'] ?? '') ?: null;
        $update['end_at']       = trim($_POST['end_at'] ?? '') ?: null;
        $update['venue']        = trim($_POST['venue'] ?? '') ?: null;
        $update['register_url'] = trim($_POST['register_url'] ?? '') ?: null;
        $update['cover_image']  = trim($_POST['cover_image'] ?? '') ?: null;
    }

    db_update('events', $update, $id);

    // Nagrania: usuń zaznaczone, zaktualizuj istniejące, dodaj nowe
    $labels = $_POST['rec_label'] ?? [];
    $urls   = $_POST['rec_url']   ?? [];
    $recIds = $_POST['rec_id']    ?? [];
    $delete = $_POST['rec_delete'] ?? [];

    foreach ($labels as $i => $label) {
        $label = trim($label);
        $url   = trim($urls[$i] ?? '');
        $recId = (int)($recIds[$i] ?? 0);

        if ($recId && in_array((string)$recId, $delete, true)) {
            db()->prepare("DELETE FROM recordings WHERE id=? AND event_id=?")->execute([$recId, $id]);
            continue;
        }
        if ($label === '' || $url === '') continue;

        if ($recId) {
            db()->prepare("UPDATE recordings SET label=?, url=?, position=? WHERE id=? AND event_id=?")
                ->execute([$label, $url, $i, $recId, $id]);
        } else {
            db_insert('recordings', ['event_id' => $id, 'label' => $label, 'url' => $url, 'position' => $i]);
        }
    }

    flash_set('success', 'Zapisano zmiany.');
    redirect(APP_URL . '/admin/event_edit.php?id=' . $id);
}

$event = db_one("SELECT * FROM events WHERE id=?", [$id]);
$recordings = db_all("SELECT * FROM recordings WHERE event_id=? ORDER BY position ASC, id ASC", [$id]);

$PAGE_TITLE = 'Edycja wydarzenia';
include dirname(__DIR__) . '/includes/admin_layout.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h1 class="h4 mb-0">Edycja: <?= h($event['title']) ?></h1>
  <span class="badge <?= $is_manual ? 'badge-source-manual' : 'badge-source-szo' ?>"><?= $is_manual ? 'wpis ręczny' : 'z SZO' ?></span>
</div>

<form method="post" class="row g-4">
<?= csrf_field() ?>
<input type="hidden" name="id" value="<?= (int)$event['id'] ?>">

<div class="col-lg-7">
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-bold">Dane wydarzenia</div>
    <div class="card-body">
      <?php if (!$is_manual): ?>
        <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>Ten wpis pochodzi z synchronizacji z SZO — tytuł, opis, termin i miejsce są tam zarządzane i nadpisywane przy każdej synchronizacji.</p>
      <?php endif; ?>
      <div class="mb-3">
        <label class="form-label">Tytuł</label>
        <input type="text" name="title" class="form-control" value="<?= h($event['title']) ?>" <?= $is_manual ? '' : 'readonly' ?>>
      </div>
      <div class="mb-3">
        <label class="form-label">Opis</label>
        <textarea name="description" class="form-control" rows="3" <?= $is_manual ? '' : 'readonly' ?>><?= h($event['description']) ?></textarea>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Typ</label>
          <select name="type" class="form-select" <?= $is_manual ? '' : 'disabled' ?>>
            <option value="webinar" <?= $event['type']==='webinar'?'selected':'' ?>>Webinar</option>
            <option value="stationary" <?= $event['type']==='stationary'?'selected':'' ?>>Stacjonarne</option>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Miejsce</label>
          <input type="text" name="venue" class="form-control" value="<?= h($event['venue']) ?>" <?= $is_manual ? '' : 'readonly' ?>>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Początek</label>
          <input type="datetime-local" name="start_at" class="form-control" value="<?= h($event['start_at'] ? str_replace(' ', 'T', substr($event['start_at'],0,16)) : '') ?>" <?= $is_manual ? '' : 'readonly' ?>>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Koniec</label>
          <input type="datetime-local" name="end_at" class="form-control" value="<?= h($event['end_at'] ? str_replace(' ', 'T', substr($event['end_at'],0,16)) : '') ?>" <?= $is_manual ? '' : 'readonly' ?>>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Link do rejestracji</label>
        <input type="url" name="register_url" class="form-control" value="<?= h($event['register_url']) ?>" <?= $is_manual ? '' : 'readonly' ?>>
      </div>
      <div class="mb-0">
        <label class="form-label">Grafika okładki (URL)</label>
        <input type="url" name="cover_image" class="form-control" value="<?= h($event['cover_image']) ?>" <?= $is_manual ? '' : 'readonly' ?>>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
      Nagrania
      <button type="button" id="addRecBtn" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg"></i> Dodaj</button>
    </div>
    <div class="card-body">
      <div id="recRows">
        <?php foreach ($recordings as $i => $r): ?>
        <div class="row g-2 mb-2 align-items-center rec-row">
          <input type="hidden" name="rec_id[]" value="<?= (int)$r['id'] ?>">
          <div class="col-4"><input type="text" name="rec_label[]" class="form-control form-control-sm" placeholder="np. Część 1" value="<?= h($r['label']) ?>"></div>
          <div class="col-6"><input type="url" name="rec_url[]" class="form-control form-control-sm" placeholder="https://..." value="<?= h($r['url']) ?>"></div>
          <div class="col-2 form-check">
            <input class="form-check-input" type="checkbox" name="rec_delete[]" value="<?= (int)$r['id'] ?>" id="del<?= (int)$r['id'] ?>">
            <label class="form-check-label small" for="del<?= (int)$r['id'] ?>">Usuń</label>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <template id="recRowTpl">
        <div class="row g-2 mb-2 align-items-center rec-row">
          <input type="hidden" name="rec_id[]" value="">
          <div class="col-4"><input type="text" name="rec_label[]" class="form-control form-control-sm" placeholder="np. Część 2"></div>
          <div class="col-6"><input type="url" name="rec_url[]" class="form-control form-control-sm" placeholder="https://..."></div>
          <div class="col-2"></div>
        </div>
      </template>
      <p class="text-muted small mb-0">Puste pola (etykieta lub link) zostaną pominięte przy zapisie.</p>
    </div>
  </div>
</div>

<div class="col-lg-5">
  <div class="card shadow-sm">
    <div class="card-header bg-white fw-bold">Wyświetlanie na stronie</div>
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label">Prelegent / prowadzący</label>
        <input type="text" name="presenter" class="form-control" value="<?= h($event['presenter']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Kolor karty</label>
        <select name="accent" class="form-select">
          <option value="" <?= !$event['accent']?'selected':'' ?>>Automatyczny</option>
          <option value="blue" <?= $event['accent']==='blue'?'selected':'' ?>>Niebieski</option>
          <option value="orange" <?= $event['accent']==='orange'?'selected':'' ?>>Pomarańczowy</option>
          <option value="green" <?= $event['accent']==='green'?'selected':'' ?>>Zielony</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Prezentacja</label>
        <select name="presentation_status" class="form-select">
          <option value="none" <?= $event['presentation_status']==='none'?'selected':'' ?>>Brak</option>
          <option value="soon" <?= $event['presentation_status']==='soon'?'selected':'' ?>>Wkrótce</option>
          <option value="ready" <?= $event['presentation_status']==='ready'?'selected':'' ?>>Gotowa (podaj link)</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Link do prezentacji</label>
        <input type="url" name="presentation_url" class="form-control" value="<?= h($event['presentation_url']) ?>">
      </div>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_visible" id="is_visible" <?= $event['is_visible']?'checked':'' ?>>
        <label class="form-check-label" for="is_visible">Widoczne na stronie publicznej</label>
      </div>
    </div>
  </div>

  <div class="d-grid mt-3">
    <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
  </div>
</div>
</form>

<script>
document.getElementById('addRecBtn').addEventListener('click', function () {
  const tpl = document.getElementById('recRowTpl').content.cloneNode(true);
  document.getElementById('recRows').appendChild(tpl);
});
</script>

<?php include dirname(__DIR__) . '/includes/admin_footer.php'; ?>
