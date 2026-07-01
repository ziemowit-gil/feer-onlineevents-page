# Wydarzenia Online — feer-events

Publiczna strona `wydarzeniaonline.feer.org.pl`: lista nadchodzących wydarzeń pobierana
na żywo z systemu SZO + archiwum nagrań webinarów zarządzane lokalnie.

## Architektura

- **SZO** (`feerSZO`) udostępnia publiczny feed JSON pod `events/public/feed.php`
  (tylko wydarzenia `status=published` i `is_public=1`, bez danych wrażliwych).
- **feer-events** (ten projekt) to niezależna aplikacja PHP + SQLite:
  - okresowo pobiera feed i zapisuje wydarzenia lokalnie (`events` + `recordings`),
  - synchronizowane są wyłącznie pola z SZO (tytuł, opis, termin, miejsce, grafika,
    link do rejestracji) — pola zarządzane lokalnie (prelegent, nagrania, prezentacja,
    widoczność) nigdy nie są nadpisywane,
  - wydarzenia znikające z feedu (zakończone/zarchiwizowane w SZO) **nie są usuwane**
    — to jedyne miejsce przechowywania archiwum nagrań,
  - panel admina pozwala ręcznie dodać starsze wpisy spoza SZO.

## Wymagania

- PHP 8.1+ z rozszerzeniami `pdo_sqlite` i `curl`
- zapisywalny katalog `data/`

## Instalacja

```bash
cp config.local.php.example config.local.php
# ustaw SZO_FEED_URL i APP_KEY w config.local.php

php cli/install.php <login> <hasło>   # tworzy bazę + konto admina
```

Wskaż vhost `wydarzeniaonline.feer.org.pl` na katalog główny tego repo
(`index.php` to strona publiczna, `admin/` to panel).

## Synchronizacja z SZO

Ręcznie: przycisk „Synchronizuj z SZO” w panelu admina (`/admin/index.php`).

Automatycznie — dodaj do crontab:

```
*/15 * * * * php /ścieżka/do/feer-events/cli/sync_events.php >> /ścieżka/do/feer-events/data/sync.log 2>&1
```

## Panel admina

`/admin/login.php` — logowanie kontem utworzonym przez `cli/install.php`.

- **Wydarzenia** — lista, filtry (wszystkie/nadchodzące/archiwum/ukryte), edycja,
  przełącznik widoczności na stronie publicznej, dodawanie/edycja nagrań i prezentacji.
- **Ustawienia** — adres feedu SZO, treści strony publicznej (nazwa, stopka, loga,
  informacja o finansowaniu), zmiana hasła.

Wpisy pochodzące z SZO (`źródło: SZO`) mają tytuł/termin/miejsce tylko do odczytu —
te dane edytuje się w SZO. Wpisy ręczne (`źródło: ręczny`) są w pełni edytowalne
i można je usuwać.
