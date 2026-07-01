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
chmod -R u+w data/                       # katalog musi być zapisywalny
```

Wskaż vhost (lub podkatalog na współdzielonym hostingu) na katalog główny tego repo
(`index.php` — strona publiczna, `admin/` — panel, `install.php` — kreator instalacji).

Konto admina załóż jednym z dwóch sposobów:

**A) Graficzny kreator (bez SSH)** — wejdź w przeglądarce na `/install.php`.
Formularz zakłada konto admina, opcjonalnie ustawia nazwę serwisu i adres feedu SZO,
po czym od razu loguje i przekierowuje do panelu. Kreator **blokuje się trwale**
po pierwszej udanej instalacji (dopóki w bazie istnieje choć jedno konto admina) —
nie da się go użyć ponownie do przejęcia panelu.

**B) CLI** — `php cli/install.php <login> <hasło> [e-mail]` (e-mail opcjonalny,
przydatny do późniejszego logowania Microsoft 365).

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

### Logowanie Microsoft 365 (opcjonalne)

Administratorzy mogą logować się kontem Microsoft 365 zamiast (lub obok) loginu i hasła.

1. Zarejestruj aplikację w [Azure Portal → App registrations](https://portal.azure.com).
2. Redirect URI (Web): `https://<twoja-domena>/admin/ms365_callback.php`.
3. API permissions: `User.Read` (Microsoft Graph, delegated) — wystarczy domyślne.
4. Utwórz Client secret (Certificates & secrets).
5. W panelu: **Ustawienia → Logowanie Microsoft 365** — wklej Tenant ID, Application
   (client) ID i Client secret.
6. W **Ustawienia → Twoje konto** ustaw adres e-mail zgodny z kontem Microsoft, którym
   chcesz się logować (dopasowanie następuje po e-mailu — logowanie MS365 nigdy nie
   tworzy nowych kont automatycznie).

Po zapisaniu danych na ekranie logowania pojawi się przycisk „Zaloguj przez Microsoft 365”.

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
  informacja o finansowaniu), konfiguracja Cloudflare R2, logowanie Microsoft 365,
  e-mail konta, zmiana hasła.

Wpisy z SZO (`źródło: SZO`) mają tytuł/termin/miejsce tylko do odczytu — te dane
edytuje się w SZO. Wpisy ręczne (`źródło: ręczny`) są w pełni edytowalne i można je usuwać.
