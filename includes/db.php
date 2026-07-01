<?php
/**
 * includes/db.php — SQLite + schema
 */

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $isNew = !file_exists(DB_PATH);
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');

    db_migrate($pdo);
    if ($isNew) { /* nop — schemat tworzony przez db_migrate */ }

    return $pdo;
}

function db_migrate(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        key   TEXT PRIMARY KEY,
        value TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        username      TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        created_at    DATETIME DEFAULT (datetime('now','localtime'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS events (
        id                   INTEGER PRIMARY KEY AUTOINCREMENT,
        source               TEXT NOT NULL DEFAULT 'manual', -- szo | manual
        szo_slug             TEXT UNIQUE,
        title                TEXT NOT NULL,
        description          TEXT,
        type                 TEXT NOT NULL DEFAULT 'webinar', -- webinar | stationary
        presenter            TEXT,
        start_at             DATETIME,
        end_at               DATETIME,
        venue                TEXT,
        cover_image          TEXT,
        register_url         TEXT,
        is_visible           INTEGER NOT NULL DEFAULT 1,
        presentation_url     TEXT,
        presentation_status  TEXT NOT NULL DEFAULT 'none', -- none | soon | ready
        accent               TEXT,
        position             INTEGER NOT NULL DEFAULT 0,
        last_synced_at       DATETIME,
        created_at           DATETIME DEFAULT (datetime('now','localtime')),
        updated_at           DATETIME DEFAULT (datetime('now','localtime'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS recordings (
        id        INTEGER PRIMARY KEY AUTOINCREMENT,
        event_id  INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
        label     TEXT NOT NULL,
        url       TEXT NOT NULL,
        position  INTEGER NOT NULL DEFAULT 0
    )");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_recordings_event ON recordings(event_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_events_start ON events(start_at)");
}

function db_one(string $sql, array $params = []): ?array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

function db_all(string $sql, array $params = []): array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function db_insert(string $table, array $data): int {
    $cols = implode(', ', array_keys($data));
    $ph   = ':' . implode(', :', array_keys($data));
    db()->prepare("INSERT INTO {$table} ({$cols}) VALUES ({$ph})")->execute($data);
    return (int) db()->lastInsertId();
}

function db_update(string $table, array $data, int $id): void {
    $set = implode(', ', array_map(fn($k) => "{$k} = :{$k}", array_keys($data)));
    $data['id'] = $id;
    db()->prepare("UPDATE {$table} SET {$set} WHERE id = :id")->execute($data);
}

// ── Settings ────────────────────────────────────────────────────────────────

function setting(string $key, string $default = ''): string {
    $row = db_one("SELECT value FROM settings WHERE key=?", [$key]);
    return $row['value'] ?? $default;
}

function setting_set(string $key, string $value): void {
    db()->prepare("INSERT INTO settings (key, value) VALUES (?, ?)
                    ON CONFLICT(key) DO UPDATE SET value=excluded.value")
        ->execute([$key, $value]);
}
