<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title = trim($_POST['title'] ?? '');
    if (!$title) { flash_set('error', 'Tytuł jest wymagany.'); redirect(APP_URL . '/admin/event_new.php'); }

    $id = db_insert('events', [
        'source'   => 'manual',
        'title'    => $title,
        'type'     => 'webinar',
        'is_visible' => 1,
    ]);
    flash_set('success', 'Utworzono wpis — uzupełnij szczegóły.');
    redirect(APP_URL . '/admin/event_edit.php?id=' . $id);
}

$PAGE_TITLE = 'Nowy wpis ręczny';
include dirname(__DIR__) . '/includes/admin_layout.php';
?>
<h1 class="h4 mb-3">Nowy wpis ręczny</h1>
<p class="text-muted">Użyj tego, gdy chcesz dodać do archiwum starsze nagranie, którego nie ma w systemie SZO.</p>
<form method="post" class="card shadow-sm" style="max-width:480px">
  <div class="card-body">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label">Tytuł wydarzenia</label>
      <input type="text" name="title" class="form-control" required autofocus>
    </div>
    <button type="submit" class="btn btn-primary">Utwórz i edytuj szczegóły</button>
  </div>
</form>
<?php include dirname(__DIR__) . '/includes/admin_footer.php'; ?>
