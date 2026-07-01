# Wydarzenia Online — feer-events

Publiczna strona `wydarzeniaonline.feer.org.pl`: lista nadchodzących wydarzeń pobierana
na żywo z systemu SZO + archiwum nagrań webinarów zarządzane lokalnie.

## Szybki start (lokalnie)

Wymagany PHP 8.1+ z rozszerzeniami `pdo_sqlite` i `curl`.

```bash
git clone <adres-repo> feer-events && cd feer-events
cp config.local.php.example config.local.php
# w config.local.php ustaw SZO_FEED_URL na działający feed (albo zostaw domyślny)

php cli/install.php admin haslo1234      # tworzy bazę SQLite + konto admina
php -S 127.0.0.1:8098                    # wbudowany serwer PHP
```

Otwórz:
- strona publiczna → http://127.0.0.1:8098/
- panel admina → http://127.0.0.1:8098/admin/login.php (login: `admin`, hasło jak wyżej)

W panelu kliknij „Synchronizuj z SZO”, żeby pobrać wydarzenia (albo poczekaj na cron).

## Instalacja produkcyjna

```bash
cp config.local.php.example config.local.php
# ustaw SZO_FEED_URL (np. https://szo.feer.org.pl/events/public/feed.php) i APP_KEY

php cli/install.php <login> <hasło>      # tworzy bazę + konto admina
chmod -R u+w data/                       # katalog musi być zapisywalny
```

Wskaż vhost `wydarzeniaonline.feer.org.pl` na katalog główny tego repo
(`index.php` — strona publiczna, `admin/` — panel).

### Synchronizacja z SZO

Ręcznie: przycisk „Synchronizuj z SZO” w panelu (`/admin/index.php`).

Automatycznie — dodaj do crontab:

```
*/15 * * * * php /ścieżka/do/feer-events/cli/sync_events.php >> /ścieżka/do/feer-events/data/sync.log 2>&1
```

### Zmiana hasła / dodanie kolejnego admina

```bash
php cli/install.php <login> <nowe-hasło>   # istniejący login = reset hasła, nowy = nowe konto
```

lub w panelu: **Ustawienia → Zmiana hasła** (tylko własne konto).

## Jak to działa

- SZO udostępnia publiczny feed JSON pod `events/public/feed.php` (tylko wydarzenia
  `status=published` i `is_public=1`, bez danych wrażliwych).
- Ta aplikacja okresowo pobiera feed i zapisuje wydarzenia lokalnie (`events` + `recordings`).
  Synchronizowane są wyłącznie pola z SZO (tytuł, opis, termin, miejsce, grafika, link
  do rejestracji) — pola zarządzane lokalnie (prelegent, nagrania, prezentacja, widoczność)
  nigdy nie są nadpisywane.
- Wydarzenia znikające z feedu (zakończone/zarchiwizowane w SZO) **nie są usuwane** —
  to jedyne miejsce przechowywania archiwum nagrań.
- Panel admina pozwala też ręcznie dodać starsze wpisy spoza SZO.

## Panel admina

- **Wydarzenia** — lista, filtry (wszystkie/nadchodzące/archiwum/ukryte), edycja,
  przełącznik widoczności na stronie publicznej, dodawanie/edycja nagrań i prezentacji.
- **Ustawienia** — adres feedu SZO, treści strony publicznej (nazwa, stopka, loga,
  informacja o finansowaniu), zmiana hasła.

Wpisy z SZO (`źródło: SZO`) mają tytuł/termin/miejsce tylko do odczytu — te dane
edytuje się w SZO. Wpisy ręczne (`źródło: ręczny`) są w pełni edytowalne i można je usuwać.
