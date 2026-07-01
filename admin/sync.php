<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/sync.php';

require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(APP_URL . '/admin/index.php');
csrf_check();

try {
    $result = ev_sync_run();
    flash_set('success', "Zsynchronizowano: dodano {$result['added']}, zaktualizowano {$result['updated']} (z {$result['total']} w feedzie).");
} catch (\Throwable $e) {
    flash_set('error', 'Błąd synchronizacji: ' . $e->getMessage());
}
redirect(APP_URL . '/admin/index.php');
