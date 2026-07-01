<?php
/**
 * config.php — wydarzeniaonline.feer.org.pl
 * Skopiuj do config.local.php i dostosuj, albo nadpisz zmiennymi środowiskowymi.
 */

if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

if (!defined('APP_URL')) {
    define('APP_URL', (function () {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return rtrim($scheme . '://' . $host, '/');
    })());
}

if (!defined('DB_PATH'))          define('DB_PATH', __DIR__ . '/data/events.db');
if (!defined('SZO_FEED_URL'))     define('SZO_FEED_URL', getenv('SZO_FEED_URL') ?: 'https://szo.feer.org.pl/events/public/feed.php');
if (!defined('APP_KEY'))          define('APP_KEY', getenv('APP_KEY') ?: 'zmień-to-w-config-local-php');

date_default_timezone_set('Europe/Warsaw');
