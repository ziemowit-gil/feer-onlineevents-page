#!/usr/bin/env php
<?php
/**
 * cli/install.php — tworzy bazę SQLite i pierwsze konto admina.
 * Użycie: php cli/install.php <login> <hasło>
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

$username = $argv[1] ?? null;
$password = $argv[2] ?? null;

if (!$username || !$password) {
    fwrite(STDERR, "Użycie: php cli/install.php <login> <hasło>\n");
    exit(1);
}
if (strlen($password) < 8) {
    fwrite(STDERR, "Hasło musi mieć co najmniej 8 znaków.\n");
    exit(1);
}

db(); // tworzy schemat, jeśli nie istnieje

$existing = db_one("SELECT id FROM admins WHERE username=?", [$username]);
if ($existing) {
    db()->prepare("UPDATE admins SET password_hash=? WHERE id=?")
        ->execute([password_hash($password, PASSWORD_DEFAULT), $existing['id']]);
    echo "Zaktualizowano hasło dla konta: {$username}\n";
} else {
    db_insert('admins', [
        'username'      => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    echo "Utworzono konto admina: {$username}\n";
}

echo "Baza danych: " . DB_PATH . "\n";
echo "Gotowe. Zaloguj się pod /admin/login.php\n";
