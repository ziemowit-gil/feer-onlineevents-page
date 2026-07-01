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
        // Zmienna środowiskowa ma priorytet (np. w Docker)
        if ($env = getenv('APP_URL')) return rtrim($env, '/');
        $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
        // Wykryj podkatalog wdrożenia (np. /webinars-vod), gdy aplikacja nie stoi w document rootcie.
        $docRoot = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
        $appDir  = rtrim(str_replace('\\', '/', realpath(__DIR__)), '/');
        $path    = ($docRoot && str_starts_with($appDir, $docRoot)) ? substr($appDir, strlen($docRoot)) : '';
        return rtrim($scheme . '://' . $host . $path, '/');
    })());
}

if (!defined('DB_PATH'))          define('DB_PATH', __DIR__ . '/data/events.db');
if (!defined('SZO_FEED_URL'))     define('SZO_FEED_URL', getenv('SZO_FEED_URL') ?: 'https://szo.feer.org.pl/events/public/feed.php');
if (!defined('APP_KEY'))          define('APP_KEY', getenv('APP_KEY') ?: 'zmień-to-w-config-local-php');

date_default_timezone_set('Europe/Warsaw');
