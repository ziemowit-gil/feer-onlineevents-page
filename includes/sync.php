<?php
/**
 * includes/sync.php — pobiera wydarzenia z publicznego feedu SZO i zapisuje lokalnie.
 * Synchronizowane są WYŁĄCZNIE pola pochodzące z SZO (tytuł, opis, termin, miejsce,
 * grafika, link do rejestracji). Pola zarządzane lokalnie (prelegent, nagrania,
 * prezentacja, widoczność, kolejność) nigdy nie są nadpisywane.
 * Wydarzenia, które zniknęły z feedu (zakończone/zarchiwizowane w SZO), NIE są
 * usuwane ani ukrywane — to jedyne źródło archiwum nagrań.
 */

function ev_fetch_feed(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($body === false) {
        throw new RuntimeException("Błąd połączenia z feedem SZO: {$err}");
    }
    if ($code !== 200) {
        throw new RuntimeException("Feed SZO zwrócił kod HTTP {$code}.");
    }
    $data = json_decode($body, true);
    if (!is_array($data) || empty($data['ok']) || !isset($data['events'])) {
        throw new RuntimeException('Nieprawidłowa odpowiedź feedu SZO.');
    }
    return $data['events'];
}

function ev_sync_run(): array {
    $url = setting('feed_url', SZO_FEED_URL);
    $events = ev_fetch_feed($url);

    $added = 0;
    $updated = 0;

    foreach ($events as $e) {
        $slug = trim($e['slug'] ?? '');
        if (!$slug) continue;

        $existing = db_one("SELECT id FROM events WHERE szo_slug=?", [$slug]);

        $core = [
            'title'          => $e['title'] ?? '',
            'description'    => $e['description'] ?? null,
            'type'           => in_array($e['type'] ?? '', ['webinar', 'stationary'], true) ? $e['type'] : 'webinar',
            'start_at'       => $e['start_at'] ?? null,
            'end_at'         => $e['end_at'] ?? null,
            'venue'          => $e['venue'] ?? null,
            'cover_image'    => $e['cover_image'] ?? null,
            'register_url'   => $e['register_url'] ?? null,
            'last_synced_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            db_update('events', $core, (int)$existing['id']);
            $updated++;
        } else {
            db_insert('events', $core + [
                'source'   => 'szo',
                'szo_slug' => $slug,
            ]);
            $added++;
        }
    }

    setting_set('last_sync_at', date('Y-m-d H:i:s'));
    setting_set('last_sync_result', "dodano {$added}, zaktualizowano {$updated}");

    return ['added' => $added, 'updated' => $updated, 'total' => count($events)];
}
