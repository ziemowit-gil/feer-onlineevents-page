<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$brand    = setting('site_brand', 'Wydarzenia Online — FEER');
$subtitle = setting('site_subtitle', 'Fundacja Edukacji Empatii Rozwoju „FEER"');
$footer   = setting('footer_text', '© ' . date('Y') . ' Fundacja Edukacji Empatii Rozwoju „FEER"');
$contact  = setting('contact_email', 'kontakt@feer.org.pl');
$funding  = setting('funding_note', '');
$feer_url = setting('feer_url', 'https://feer.org.pl');

$upcoming = db_all(
    "SELECT * FROM events WHERE is_visible=1 AND start_at IS NOT NULL AND start_at >= datetime('now','localtime')
     ORDER BY start_at ASC"
);
$archive = db_all(
    "SELECT * FROM events WHERE is_visible=1 AND (start_at IS NULL OR start_at < datetime('now','localtime'))
     ORDER BY start_at DESC"
);

$recordings_by_event = [];
if ($archive) {
    $ids = array_column($archive, 'id');
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    foreach (db_all("SELECT * FROM recordings WHERE event_id IN ($ph) ORDER BY position ASC, id ASC", $ids) as $r) {
        $recordings_by_event[$r['event_id']][] = $r;
    }
}

$logos = [];
foreach ([1, 2, 3] as $i) {
    $src = setting("logo{$i}_src"); $alt = setting("logo{$i}_alt");
    if ($src) $logos[] = ['src' => $src, 'alt' => $alt ?: 'Logo'];
}

function ev_type_label(string $type): string {
    return $type === 'stationary' ? 'Stacjonarne' : 'Webinar';
}
?><!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= h($brand) ?></title>
<link rel="stylesheet" href="assets/site.css?v=<?= filemtime(__DIR__ . '/assets/site.css') ?>">
</head>
<body>

<a class="skip-link" href="#main-content">Przejdź do treści</a>

<div class="topbar">
  <span class="topbar-brand"><?= h($brand) ?></span>
  <a class="link-btn" href="<?= h($feer_url) ?>">← feer.org.pl</a>
</div>

<div class="container">
  <header class="page-header">
    <h1>Wydarzenia online</h1>
    <p class="subtitle"><?= h($subtitle) ?></p>
  </header>

  <main id="main-content">

    <section class="events-section" aria-labelledby="upcoming-title">
      <h2 class="section-title" id="upcoming-title"><span class="dot" style="background:var(--blue)"></span>Nadchodzące wydarzenia</h2>
      <?php if (!$upcoming): ?>
        <p class="empty-note">Brak zaplanowanych wydarzeń — zapraszamy wkrótce.</p>
      <?php else: ?>
      <ul class="grid">
        <?php foreach ($upcoming as $i => $ev): $accent = event_accent($ev, $i); ?>
        <li class="card <?= $accent !== 'blue' ? h($accent) : '' ?>">
          <div class="card-accent"></div>
          <div class="card-body">
            <span class="card-date">
              <time datetime="<?= h(date('Y-m-d', strtotime($ev['start_at']))) ?>"><?= h(fmt_datetime($ev['start_at'])) ?></time>
              <span class="card-type-badge"><?= h(ev_type_label($ev['type'])) ?></span>
            </span>
            <h2 class="card-title"><?= h($ev['title']) ?></h2>
            <?php if ($ev['presenter']): ?><p class="card-presenter"><?= h($ev['presenter']) ?></p><?php endif; ?>
            <?php if ($ev['type'] === 'stationary' && $ev['venue']): ?><p class="card-venue"><?= h($ev['venue']) ?></p><?php endif; ?>
          </div>
          <div class="card-footer">
            <?php if ($ev['register_url']): ?>
              <a href="<?= h($ev['register_url']) ?>" class="btn btn-register" target="_blank" rel="noopener">Zapisz się</a>
            <?php endif; ?>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </section>

    <section class="events-section" aria-labelledby="archive-title">
      <h2 class="section-title" id="archive-title"><span class="dot" style="background:var(--green)"></span>Archiwum nagrań</h2>
      <?php if (!$archive): ?>
        <p class="empty-note">Archiwum jest jeszcze puste.</p>
      <?php else: ?>
      <ul class="grid">
        <?php foreach ($archive as $i => $ev): $accent = event_accent($ev, $i); $recs = $recordings_by_event[$ev['id']] ?? []; ?>
        <li class="card <?= $accent !== 'blue' ? h($accent) : '' ?>">
          <div class="card-accent"></div>
          <div class="card-body">
            <span class="card-date">
              <?php if ($ev['start_at']): ?><time datetime="<?= h(date('Y-m-d', strtotime($ev['start_at']))) ?>"><?= h(fmt_date($ev['start_at'])) ?></time><?php endif; ?>
            </span>
            <h2 class="card-title"><?= h($ev['title']) ?></h2>
            <?php if ($ev['presenter']): ?><p class="card-presenter"><?= h($ev['presenter']) ?></p><?php endif; ?>
          </div>
          <div class="card-footer">
            <?php foreach ($recs as $r): ?>
              <a href="<?= h($r['url']) ?>" class="btn btn-play" target="_blank" rel="noopener">
                <svg aria-hidden="true" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                <?= h($r['label']) ?>
              </a>
            <?php endforeach; ?>
            <?php if ($ev['presentation_status'] === 'ready' && $ev['presentation_url']): ?>
              <a href="<?= h($ev['presentation_url']) ?>" class="btn btn-slides" target="_blank" rel="noopener">
                <svg aria-hidden="true" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="14" rx="2"/><line x1="7" y1="21" x2="17" y2="21"/></svg>
                Prezentacja
              </a>
            <?php elseif ($ev['presentation_status'] === 'soon'): ?>
              <span class="btn-soon">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Prezentacja – wkrótce
              </span>
            <?php endif; ?>
            <?php if (!$recs && $ev['presentation_status'] === 'none'): ?>
              <span class="btn-soon">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Nagranie – wkrótce
              </span>
            <?php endif; ?>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </section>

    <?php if ($logos): ?>
    <div class="logos">
      <?php foreach ($logos as $l): ?>
        <img src="<?= h($l['src']) ?>" alt="<?= h($l['alt']) ?>">
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($funding): ?>
      <p class="project-info"><?= nl2br(h($funding)) ?></p>
    <?php endif; ?>

    <div class="info-box">
      <p>Nagrania otwierają się w oknie przeglądarki. Strona działa najlepiej w <strong>Firefox</strong> i <strong>Chrome</strong>.</p>
      <p style="margin-top:8px">Problemy z odtwarzaniem? <a href="mailto:<?= h($contact) ?>"><?= h($contact) ?></a></p>
    </div>
  </main>
</div>

<footer class="site-footer"><?= h($footer) ?></footer>

</body>
</html>
