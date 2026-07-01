#!/usr/bin/env php
<?php
/**
 * cli/seed_adngo_archive.php — jednorazowy seeder: wgrywa archiwalne nagrania
 * webinarów „Akademia Dostępności w NGO” przeniesione ze starej statycznej strony
 * (api.edukacja.cloud/r2StaticPages/ADNGOKrakowWebinary.html).
 * Wpisy są tworzone jako źródło "manual" (nie pochodzą z SZO). Idempotny —
 * pomija wydarzenia o tytule, który już istnieje w bazie.
 *
 * Użycie: php cli/seed_adngo_archive.php
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

db(); // upewnij się, że schemat istnieje

$SEED = [
    [
        'title'     => 'Dostępność w zadaniach publicznych',
        'presenter' => 'Martyna Feliks',
        'start_at'  => '2025-04-22 00:00:00',
        'accent'    => 'blue',
        'recordings' => [
            ['label' => 'Część 1', 'url' => 'https://youtu.be/2ex0vRDs2XE'],
            ['label' => 'Część 2', 'url' => 'https://youtu.be/2ex0vRDs2XE'],
        ],
    ],
    [
        'title'     => 'Jak opisać dostępność w ofercie?',
        'presenter' => 'Ziemowit Gil',
        'start_at'  => '2026-05-05 00:00:00',
        'accent'    => 'orange',
        'recordings' => [
            ['label' => 'Część 1', 'url' => 'https://pub-dfb9d959e801498aac0102c8929760c5.r2.dev/adngo/webinary/Webinar_JakOpisacDostepnoscWofercie_cz1.mp4'],
            ['label' => 'Część 2', 'url' => 'https://pub-dfb9d959e801498aac0102c8929760c5.r2.dev/adngo/webinary/Webinar_JakOpisacDostepnoscWofercie_cz2.mp4'],
            ['label' => 'Część 3', 'url' => 'https://pub-dfb9d959e801498aac0102c8929760c5.r2.dev/adngo/webinary/Webinar_JakOpisacDostepnoscWofercie_cz2.mp4'],
        ],
    ],
    [
        'title'     => 'Dostępność w praktyce',
        'presenter' => 'Martyna Feliks',
        'start_at'  => '2026-05-13 00:00:00',
        'accent'    => 'green',
        'recordings' => [
            ['label' => 'Część 1', 'url' => 'https://8053ede09417ecf524f54c7c5e6a6db1.r2.cloudflarestorage.com/feerns-storage/adngo/webinary/2026-05-13_CEST_Akademia_Doste%CC%A8pnos%CC%81ci_w_NGO__Doste%CC%A8pnos%CC%81c%CC%81_w_praktyce.mp4'],
            ['label' => 'Część 2', 'url' => 'https://8053ede09417ecf524f54c7c5e6a6db1.r2.cloudflarestorage.com/feerns-storage/adngo/webinary/2026-05-13_CEST_Akademia_Doste%CC%A8pnos%CC%81ci_w_NGO__Doste%CC%A8pnos%CC%81c%CC%81_w_praktyce.mp4'],
        ],
    ],
];

$added = 0;
$skipped = 0;

foreach ($SEED as $item) {
    $existing = db_one("SELECT id FROM events WHERE title=? AND source='manual'", [$item['title']]);
    if ($existing) {
        echo "· pomijam (już istnieje): {$item['title']}\n";
        $skipped++;
        continue;
    }

    $id = db_insert('events', [
        'source'              => 'manual',
        'title'               => $item['title'],
        'presenter'           => $item['presenter'],
        'type'                => 'webinar',
        'start_at'            => $item['start_at'],
        'accent'              => $item['accent'],
        'presentation_status' => 'soon',
        'is_visible'          => 1,
    ]);

    foreach ($item['recordings'] as $pos => $rec) {
        db_insert('recordings', [
            'event_id' => $id,
            'label'    => $rec['label'],
            'url'      => $rec['url'],
            'position' => $pos,
        ]);
    }

    echo "✓ dodano: {$item['title']} (" . count($item['recordings']) . " nagr.)\n";
    $added++;
}

echo "\nGotowe — dodano {$added}, pominięto {$skipped}.\n";
