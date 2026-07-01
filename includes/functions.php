<?php
/**
 * includes/functions.php — helpery ogólne
 */

function h(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}

function csrf_check(): void {
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(403);
        exit('Nieprawidłowy token CSRF. Wróć i spróbuj ponownie.');
    }
}

function flash_set(string $type, string $msg): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_get(): array {
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

function fmt_date(?string $dt): string {
    if (!$dt) return '';
    $ts = strtotime($dt);
    return $ts ? date('d.m.Y', $ts) : '';
}

function fmt_datetime(?string $dt): string {
    if (!$dt) return '';
    $ts = strtotime($dt);
    return $ts ? date('d.m.Y H:i', $ts) : '';
}

const EVENT_ACCENTS = ['blue', 'orange', 'green'];

function event_accent(array $event, int $index): string {
    return $event['accent'] ?: EVENT_ACCENTS[$index % count(EVENT_ACCENTS)];
}

function initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $parts = array_filter($parts);
    if (!$parts) return '?';
    if (count($parts) === 1) return mb_strtoupper(mb_substr($parts[0], 0, 2));
    return mb_strtoupper(mb_substr(reset($parts), 0, 1) . mb_substr(end($parts), 0, 1));
}

const PRESENTATION_STATUS_LABELS = [
    'none'  => null,
    'soon'  => 'Prezentacja – wkrótce',
    'ready' => 'Prezentacja',
];
