#!/usr/bin/env php
<?php
/**
 * cli/install.php — tworzy bazę SQLite i pierwsze konto admina.
 * Użycie: php cli/install.php <login> <hasło> [e-mail]
 * E-mail (opcjonalny) pozwala od razu włączyć logowanie Microsoft 365 dla tego konta.
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

$username = $argv[1] ?? null;
$password = $argv[2] ?? null;
$email    = $argv[3] ?? null;

if (!$username || !$password) {
    fwrite(STDERR, "Użycie: php cli/install.php <login> <hasło> [e-mail]\n");
    exit(1);
}
if (strlen($password) < 8) {
    fwrite(STDERR, "Hasło musi mieć co najmniej 8 znaków.\n");
    exit(1);
}
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Nieprawidłowy adres e-mail.\n");
    exit(1);
}

db(); // tworzy schemat, jeśli nie istnieje

$existing = db_one("SELECT id FROM admins WHERE username=?", [$username]);
if ($existing) {
    $update = ['password_hash' => password_hash($password, PASSWORD_DEFAULT)];
    if ($email) $update['email'] = $email;
    db_update('admins', $update, (int)$existing['id']);
    echo "Zaktualizowano konto: {$username}\n";
} else {
    db_insert('admins', [
        'username'      => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'email'         => $email,
    ]);
    echo "Utworzono konto admina: {$username}\n";
}

echo "Baza danych: " . DB_PATH . "\n";
echo "Gotowe. Zaloguj się pod /admin/login.php\n";
