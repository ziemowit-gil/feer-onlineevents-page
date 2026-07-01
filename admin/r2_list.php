<?php
/**
 * admin/r2_list.php — AJAX: lista plików w buckecie R2 (do modala wyboru pliku)
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/s3_client.php';
require_once dirname(__DIR__) . '/includes/r2.php';

require_admin();
header('Content-Type: application/json; charset=utf-8');

$cfg = r2_cfg();
if (!s3_config_ready($cfg)) {
    echo json_encode(['ok' => false, 'error' => 'Magazyn R2 nie jest skonfigurowany — uzupełnij dane w Ustawieniach.']);
    exit;
}

$prefix = trim((string)($_GET['prefix'] ?? $cfg['prefix']), '/');
$r = s3_list_objects($cfg, $prefix, 200);
if (!$r['ok']) {
    echo json_encode(['ok' => false, 'error' => $r['msg']]);
    exit;
}

$files = array_map(fn($o) => [
    'key'  => $o['key'],
    'size' => $o['size'],
    'url'  => r2_public_url($cfg, $o['key']),
], $r['keys']);

usort($files, fn($a, $b) => strcmp($b['key'], $a['key']));

echo json_encode(['ok' => true, 'files' => $files, 'public_base' => $cfg['public_base']], JSON_UNESCAPED_UNICODE);
