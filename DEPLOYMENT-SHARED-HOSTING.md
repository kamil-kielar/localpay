# Wdrożenie LokalPay Pro na hostingu współdzielonym

## 1. Sprawdź hosting

Wymagane są PHP 8.2+, MySQL/MariaDB, HTTPS, możliwość ustawienia document root, cron oraz SMTP. Zalecane są SSH i możliwość uruchomienia procesów CLI. Rozszerzenia PHP: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pdo_mysql`, `session`, `tokenizer`, `xml`.

Ustaw dokładne `APP_URL=https://twoja-domena.pl`. `TRUSTED_PROXIES` pozostaw puste, chyba że operator poda konkretne adresy swojego proxy. Aplikacja odrzuca żądania z innym nagłówkiem hosta, aby chronić linki resetowania hasła i weryfikacji e-mail.

## 2. Zbuduj aplikację lokalnie

Jeśli hosting nie ma Composer/npm, wykonaj na zaufanym komputerze z systemem zgodnym z serwerem:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm install
npm run build
```

Paczka źródłowa nie zawiera `composer.lock`, `package-lock.json`, `vendor/`, `node_modules/` ani `public/build/`, ponieważ w środowisku generowania nie były dostępne PHP, Composer ani instalacja bibliotek. Pierwszy build wykonany na zaufanym komputerze wygeneruje pliki blokad; zachowaj je w swoim prywatnym repozytorium, aby kolejne wydania używały identycznych wersji.

Prześlij cały projekt razem z wygenerowanymi `vendor/` i `public/build/`, ale bez `node_modules/`, `.git/`, testów i lokalnego `.env`. Nie kopiuj `vendor` pomiędzy niezgodnymi wersjami PHP/platformami. Bez `public/build/manifest.json` interfejs Vue nie uruchomi się.

## 3. Układ katalogów i document root

Zalecany układ:

```text
/home/konto/apps/lokalpay/       pełny projekt
/home/konto/public_html/         document root domeny lub dowiązanie do public/
```

Najbezpieczniej ustawić document root domeny bezpośrednio na:

```text
/home/konto/apps/lokalpay/public
```

Serwer WWW nie może wystawiać katalogu projektu. Pliki `.env`, `vendor/`, `storage/`, `database/` i `app/` nie mogą być dostępne przez HTTP.

Jeśli panel hostingowy wymusza `public_html`, przenieś zawartość `public/` do `public_html` i zmień w `public_html/index.php` dwie ścieżki do `storage/framework/maintenance.php`, `vendor/autoload.php` i `bootstrap/app.php`. To rozwiązanie jest mniej wygodne i trzeba je powtarzać po aktualizacji. Nie kopiuj całego projektu do `public_html`.

## 4. Konfiguracja środowiska

W katalogu projektu:

```bash
cp .env.example .env
php artisan key:generate
```

Bez SSH wygeneruj `APP_KEY` lokalnie w kopii projektu i wprowadź go przez bezpieczny menedżer plików. Nigdy nie używaj przykładowego klucza.

Minimalne ustawienia:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.example.com
APP_TIMEZONE=Europe/Warsaw
APP_LOCALE=pl
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
QUEUE_CONNECTION=database
CACHE_STORE=database
```

Ustaw prawa `.env` na `600` lub najbardziej restrykcyjne dostępne. `storage/` i `bootstrap/cache/` muszą być zapisywalne przez PHP, ale nie globalnie zapisywalne.

## 5. Baza danych

Utwórz osobną bazę i użytkownika. W `.env` ustaw `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`. Następnie:

```bash
php artisan migrate --force
php artisan db:seed --class=PlanSeeder --force
```

Nie ustawiaj `DEMO_SEED=true` w produkcji. Przy braku CLI możesz uruchomić migracje lokalnie na zgodnej kopii bazy i zaimportować SQL, ale CLI na serwerze jest bezpieczniejsze.

## 6. Optymalizacja

Po każdej zmianie `.env` lub kodu:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`config:cache` oznacza, że kod aplikacji nie powinien wywoływać `env()` poza plikami `config/`.

## 7. Cron i kolejka

Scheduler co minutę:

```cron
* * * * * cd /home/konto/apps/lokalpay && /usr/local/bin/php artisan schedule:run >> /home/konto/logs/lokalpay-cron.log 2>&1
```

Jeżeli hosting oferuje Supervisor/systemd, uruchom stały worker:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90 --max-time=3600
```

Na hostingu bez procesów stałych dodaj:

```cron
* * * * * cd /home/konto/apps/lokalpay && /usr/local/bin/php artisan queue:work --stop-when-empty --tries=3 --timeout=90 >> /home/konto/logs/lokalpay-queue.log 2>&1
```

Po wdrożeniu kodu stały worker wymaga `php artisan queue:restart`. Monitoruj tabelę `failed_jobs` i okresowo uruchamiaj `php artisan queue:failed`.

## 8. SMTP

Ustaw `MAIL_MAILER=smtp`, host, port, login, hasło, szyfrowanie i zweryfikowany adres nadawcy. Dodaj SPF, DKIM i DMARC. Wyślij test rejestracji, resetu hasła i zaproszenia najemcy. Kolejka musi działać, inaczej e-maile pozostaną w tabeli `jobs`.

## 9. Stripe

Dodaj klucze live, sekret webhooka i identyfikatory miesięcznych cen Growth/Pro. Endpoint:

```text
https://app.example.com/webhooks/stripe
```

Włącz zdarzenia opisane w README. Najpierw wykonaj pełny test sandbox/test mode. Sprawdź, że powrót success bez webhooka nie aktywuje planu ani czynszu.

## 10. PayU

Ustaw dane POS i endpoint:

```text
https://app.example.com/webhooks/payu
```

Przetestuj podpis `OpenPayU-Signature`, błędną kwotę, błędną walutę i powtórzone powiadomienie. PayU daje jednorazowy dostęp do planu na 30 dni; aplikacja nie przedstawia go jako subskrypcji.

## 11. Super administrator

Ustaw serwerowe `LOKALPAY_SUPER_ADMIN_EMAIL`, zarejestruj konto i zweryfikuj je podpisanym linkiem albo wykonaj:

```bash
php artisan lokalpay:promote-super-admin admin@example.com
```

Po promocji sprawdź panel `/administrator`. Nie udostępniaj dostępu do CLI osobom niezaufanym.

## 12. HTTPS i nagłówki

Wymuś HTTPS w panelu hostingu. Jeśli TLS kończy reverse proxy, sprawdź przekazywanie `X-Forwarded-Proto`. Dodaj na poziomie serwera HSTS dopiero po potwierdzeniu poprawnego HTTPS. Zablokuj listowanie katalogów, pliki ukryte i kopie `.env`.

## 13. Aktualizacja

1. wykonaj backup;
2. włącz maintenance: `php artisan down --retry=60`;
3. prześlij kod, `vendor/` i `public/build/`;
4. `php artisan migrate --force`;
5. `php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache`;
6. `php artisan queue:restart`;
7. `php artisan up`;
8. sprawdź `/up`, logowanie, dashboard, kolejkę i webhooki.

## 14. Backup i odtworzenie

Codziennie:

```bash
mysqldump --single-transaction --routines --triggers -u USER -p DATABASE | gzip > lokalpay-YYYY-MM-DD.sql.gz
```

Nie zapisuj hasła w komendzie cron. Użyj chronionego pliku klienta MySQL albo mechanizmu backupu hostingu. Szyfruj kopie i przechowuj co najmniej jedną poza kontem hostingowym. Testuj odtworzenie do osobnej bazy.

## 15. Odbiór wdrożenia

- `/up` odpowiada poprawnie;
- document root wskazuje `public/`;
- debug jest wyłączony;
- migracje i seeder planów zakończone;
- rejestracja, weryfikacja i reset e-mail działają;
- organizacja A nie widzi zasobów organizacji B;
- limit Free/Growth/Pro jest egzekwowany;
- kolejka i dzienny scheduler pracują;
- webhooki odrzucają zły podpis, kwotę i walutę;
- duplikat webhooka nie tworzy drugiej wpłaty;
- wykonano test Stripe i PayU w środowisku testowym;
- backup został odtworzony testowo.
