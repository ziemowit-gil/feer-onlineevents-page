#!/usr/bin/env php
<?php
/**
 * cli/sync_events.php — synchronizacja wydarzeń z SZO (do crontab).
 * Przykład crontab (co 15 minut):
 *   * /15 * * * *  php /ścieżka/do/feer-events/cli/sync_events.php >> /ścieżka/do/feer-events/data/sync.log 2>&1
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/sync.php';

try {
    $result = ev_sync_run();
    printf("[%s] OK — dodano %d, zaktualizowano %d (z %d w feedzie)\n",
        date('Y-m-d H:i:s'), $result['added'], $result['updated'], $result['total']);
} catch (\Throwable $e) {
    fwrite(STDERR, sprintf("[%s] BŁĄD — %s\n", date('Y-m-d H:i:s'), $e->getMessage()));
    exit(1);
}
