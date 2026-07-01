<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(APP_URL . '/admin/index.php');
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$ev = db_one("SELECT source FROM events WHERE id=?", [$id]);
if ($ev && $ev['source'] === 'manual') {
    db()->prepare("DELETE FROM events WHERE id=?")->execute([$id]);
    flash_set('success', 'Wpis usunięty.');
} else {
    flash_set('error', 'Można usuwać tylko wpisy ręczne — wydarzenia z SZO synchronizują się ponownie.');
}
redirect(APP_URL . '/admin/index.php');
