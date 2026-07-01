<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(APP_URL . '/admin/index.php');
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$ev = db_one("SELECT is_visible FROM events WHERE id=?", [$id]);
if ($ev) {
    db_update('events', ['is_visible' => $ev['is_visible'] ? 0 : 1], $id);
}
$filter = $_POST['filter'] ?? 'all';
redirect(APP_URL . '/admin/index.php?filter=' . urlencode($filter));
