<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_admin();

$filter = $_GET['filter'] ?? 'all';
$where = [];
if ($filter === 'upcoming') {
    $where[] = "start_at IS NOT NULL AND start_at >= datetime('now','localtime')";
} elseif ($filter === 'archive') {
    $where[] = "(start_at IS NULL OR start_at < datetime('now','localtime'))";
} elseif ($filter === 'hidden') {
    $where[] = "is_visible=0";
}
$sql = "SELECT e.*, (SELECT COUNT(*) FROM recordings r WHERE r.event_id=e.id) AS rec_count FROM events e";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY start_at DESC";
$events = db_all($sql);

$last_sync = setting('last_sync_at');
$last_sync_result = setting('last_sync_result');

$PAGE_TITLE = 'Wydarzenia';
include dirname(__DIR__) . '/includes/admin_layout.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
  <h1 class="h4 mb-0">Wydarzenia</h1>
  <div class="d-flex gap-2">
    <form method="post" action="<?= APP_URL ?>/admin/sync.php" class="d-inline">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-repeat me-1"></i>Synchronizuj z SZO</button>
    </form>
    <a href="<?= APP_URL ?>/admin/event_new.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nowy wpis ręczny</a>
  </div>
</div>

<?php if ($last_sync): ?>
<p class="text-muted small mb-3">Ostatnia synchronizacja: <?= h(fmt_datetime($last_sync)) ?> — <?= h($last_sync_result) ?></p>
<?php endif; ?>

<ul class="nav nav-tabs mb-3">
  <?php foreach (['all' => 'Wszystkie', 'upcoming' => 'Nadchodzące', 'archive' => 'Archiwum', 'hidden' => 'Ukryte'] as $k => $label): ?>
  <li class="nav-item"><a class="nav-link <?= $filter===$k?'active':'' ?>" href="?filter=<?= $k ?>"><?= h($label) ?></a></li>
  <?php endforeach; ?>
</ul>

<div class="table-responsive bg-white rounded shadow-sm">
<table class="table table-hover align-middle mb-0">
  <thead class="table-light">
    <tr>
      <th>Termin</th>
      <th>Tytuł</th>
      <th>Źródło</th>
      <th>Nagrania</th>
      <th>Widoczność</th>
      <th class="text-end">Akcje</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$events): ?>
      <tr><td colspan="6" class="text-center text-muted py-4">Brak wydarzeń w tym widoku.</td></tr>
    <?php endif; ?>
    <?php foreach ($events as $ev): ?>
    <tr>
      <td><?= $ev['start_at'] ? h(fmt_datetime($ev['start_at'])) : '<span class="text-muted">—</span>' ?></td>
      <td><?= h($ev['title']) ?></td>
      <td><span class="badge <?= $ev['source']==='szo'?'badge-source-szo':'badge-source-manual' ?>"><?= $ev['source']==='szo' ? 'SZO' : 'ręczny' ?></span></td>
      <td><?= (int)$ev['rec_count'] ?></td>
      <td>
        <form method="post" action="<?= APP_URL ?>/admin/event_toggle.php" class="d-inline">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
          <input type="hidden" name="filter" value="<?= h($filter) ?>">
          <button type="submit" class="btn btn-sm <?= $ev['is_visible'] ? 'btn-success' : 'btn-outline-secondary' ?>">
            <?= $ev['is_visible'] ? 'Widoczne' : 'Ukryte' ?>
          </button>
        </form>
      </td>
      <td class="text-end">
        <a href="<?= APP_URL ?>/admin/event_edit.php?id=<?= (int)$ev['id'] ?>" class="btn btn-sm btn-outline-primary">Edytuj</a>
        <?php if ($ev['source'] === 'manual'): ?>
        <form method="post" action="<?= APP_URL ?>/admin/event_delete.php" class="d-inline" onsubmit="return confirm('Usunąć ten wpis na stałe?');">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
          <button type="submit" class="btn btn-sm btn-outline-danger">Usuń</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<?php include dirname(__DIR__) . '/includes/admin_footer.php'; ?>
