# LokalPay Pro — Laravel 12

Produkcyjny starter wielodostępnej aplikacji SaaS dla wynajmujących i najemców. Projekt używa Laravel 12, PHP 8.2+, Vue 3 Composition API, Bootstrap 5.3, Vite, Chart.js oraz MySQL/MariaDB.

> Repozytorium nie zawiera `composer.lock`, `package-lock.json`, `vendor/`, `node_modules/` ani skompilowanego `public/build/`. W bieżącym środowisku nie ma PHP/Composera ani dostępu do instalacji pakietów. Przed uruchomieniem wymagane są `composer install`, `npm install` i `npm run build`. Po pierwszej instalacji zachowaj wygenerowane lockfile w prywatnym repozytorium.

`APP_URL` musi być kanonicznym publicznym adresem HTTPS. Jeśli hosting działa za reverse proxy, wpisz w `TRUSTED_PROXIES` wyłącznie konkretne adresy IP przekazane przez operatora. Nie używaj `*`.

## Zakres

- organizacje, członkostwa i role `owner`, `admin`, `manager`; niezależna rola portalowa najemcy;
- super administrator platformy bez publicznej możliwości samodzielnego nadania roli;
- nieruchomości, umowy, zaproszenia, harmonogramy i należności `paid/due/overdue/partial/void`;
- miesięczne przychody bez limitu lat, ROI, odzysk kapitału, prognozy i porównania;
- powiadomienia bazodanowe i kolejkowane e-maile;
- Stripe: cykliczne subskrypcje SaaS i jednorazowe płatności czynszu;
- PayU: wyłącznie jednorazowy zakup 30 dni planu oraz jednorazowe płatności czynszu;
- osobne tabele zamówień SaaS i czynszowych, jedna aktywna sesja na należność, idempotentne webhooki;
- dashboard właściciela, portal najemcy i panel super administratora.

Telefon jest wyłącznie metadanym kontaktowym. Aplikacja nie deklaruje ani nie implementuje SMS.

## Instalacja lokalna

Wymagania: PHP 8.2+ z typowymi rozszerzeniami Laravel (`ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pdo`, `session`, `tokenizer`, `xml`), Composer 2, Node.js/npm oraz MySQL 8+/MariaDB 10.6+.

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
```

Utwórz bazę i ustaw `DB_*` w `.env`, następnie:

```bash
php artisan migrate --seed
npm run build
php artisan serve
php artisan queue:work --tries=3 --timeout=90
```

W osobnym procesie deweloperskim można użyć `npm run dev`. Dane demonstracyjne są domyślnie wyłączone. Aby załadować je wyłącznie lokalnie:

```dotenv
DEMO_SEED=true
```

Po zmianie wartości uruchom `php artisan db:seed`.

## Uwierzytelnianie i bezpieczeństwo

Rejestracja, logowanie, wylogowanie, weryfikacja e-mail, reset hasła, hashowanie haseł, CSRF i sesje korzystają ze standardowych mechanizmów Laravel. Logowanie, rejestracja, reset i checkout są limitowane. Ustaw w produkcji:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.example.com
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

Organizacja jest rozwiązywana wyłącznie spośród członkostw użytkownika. Kontrolery dodatkowo filtrują zasoby po `organization_id`, a polityki wymagają roli zarządzającej. Identyfikatory publiczne są UUID. Nieruchomości, umowy, należności, organizacje i użytkownicy używają soft delete. Operacje biznesowe tworzą wpisy audytowe.

Zaproszenie najemcy zawiera jednocześnie czasowy podpis Laravel i losowy token przechowywany wyłącznie jako SHA-256. Szybki dostęp można wygenerować po stronie serwera przez `URL::temporarySignedRoute('tenant.quick', ...)`.

## Super administrator

Nie istnieje publiczny endpoint promocji. Są dwie bezpieczne ścieżki:

1. ustaw `LOKALPAY_SUPER_ADMIN_EMAIL`; konto o dokładnie tym, zweryfikowanym adresie otrzyma rolę po obsłużeniu podpisanego linku weryfikacyjnego;
2. uruchom zaufane polecenie CLI:

```bash
php artisan lokalpay:promote-super-admin admin@example.com
php artisan lokalpay:promote-super-admin admin@example.com --revoke
```

Polecenie odmawia promocji konta bez zweryfikowanego e-maila.

## Plany i uprawnienia

| Plan | Cena | Limit |
|---|---:|---:|
| Free | 0 PLN | 3 nieruchomości |
| Growth | 49 PLN/mies. | 10 nieruchomości |
| Pro | 129 PLN/mies. | 50 nieruchomości |

Konfiguracja funkcji jest w `config/lokalpay.php`, a źródłem danych wykonywalnych jest tabela `plans`. `PlanEntitlementService` egzekwuje limit w transakcji z blokadą organizacji. Uprawnienia analityczne są zwracane do UI, ale muszą być również sprawdzane po stronie serwera przy rozbudowie endpointów premium.

## Stripe

```dotenv
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PRICE_GROWTH=price_...
STRIPE_PRICE_PRO=price_...
```

Ceny Stripe powinny być miesięcznymi cenami w PLN: 4900 i 12900 groszy. Webhook:

```text
POST https://app.example.com/webhooks/stripe
```

Zdarzenia:

- `checkout.session.completed`
- `checkout.session.async_payment_succeeded`
- `checkout.session.expired`
- `customer.subscription.created`
- `customer.subscription.updated`
- `customer.subscription.deleted`

Webhook sprawdza podpis z tolerancją 300 sekund, lokalny identyfikator zamówienia, identyfikator sesji, kwotę i walutę. Powrót przeglądarki od operatora nigdy nie aktywuje płatności.

## PayU

```dotenv
PAYU_ENVIRONMENT=sandbox
PAYU_POS_ID=
PAYU_CLIENT_ID=
PAYU_CLIENT_SECRET=
PAYU_SECOND_KEY=
```

Webhook:

```text
POST https://app.example.com/webhooks/payu
```

Sprawdzane są: `OpenPayU-Signature`, `merchantPosId`, `orderId`, `extOrderId`, status `COMPLETED`, kwota i waluta. Zakup planu PayU nie jest subskrypcją: przedłuża lokalny dostęp o 30 dni.

## Kolejka i harmonogram

Domyślne sterowniki to kolejka i cache w bazie. Worker:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

Cron uruchamiany co minutę:

```cron
* * * * * cd /home/user/lokalpay && php artisan schedule:run >> /dev/null 2>&1
```

Codzienny scheduler wykonuje `lokalpay:send-rent-reminders`, aktualizuje zaległości i kolejkuje przypomnienia. Na hostingu bez stałego workera uruchamiaj co minutę:

```cron
* * * * * cd /home/user/lokalpay && php artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```

## Poczta

Skonfiguruj SMTP w `MAIL_*`, zweryfikuj domenę nadawcy, SPF, DKIM i DMARC. Zaproszenia, przypomnienia i potwierdzenia implementują `ShouldQueue`. Nie używaj sterownika `log` w produkcji.

## Testy i jakość

Po instalacji zależności:

```bash
php artisan test
./vendor/bin/pint --test
npm run build
```

Testy obejmują izolację organizacji, limit nieruchomości, podpisane zaproszenia, integralność kwoty checkoutu, autoryzację super administratora, idempotencję webhooków, uprawnienia planów, ROI i prognozy.

## Kopie zapasowe

Codziennie kopiuj bazę po wykonaniu spójnego dumpu (`mysqldump --single-transaction`), pliki `.env` i ewentualne pliki użytkowników. Szyfruj backup, przechowuj kopię poza serwerem, stosuj retencję i testuj odtworzenie przynajmniej kwartalnie.

## Wdrożenie współdzielone

Pełna procedura znajduje się w [DEPLOYMENT-SHARED-HOSTING.md](DEPLOYMENT-SHARED-HOSTING.md). Kluczowa zasada: dokument root domeny musi wskazywać `public/`, nigdy katalog główny projektu.

## Lista kontrolna produkcji

- `APP_DEBUG=false`, silny `APP_KEY`, HTTPS i secure cookies;
- osobny użytkownik bazy z minimalnymi uprawnieniami;
- brak sekretów w repozytorium i JavaScript;
- web root ustawiony na `public/`;
- poprawne trusted proxies i wymuszony HTTPS;
- działające SMTP, cron, kolejka i monitoring failed jobs;
- podpisy webhooków przetestowane w sandboxie;
- ograniczone uprawnienia plików `.env`, `storage/`, `bootstrap/cache/`;
- regularne aktualizacje Composer/npm, backupy i test odtworzenia;
- logi bez payloadów zawierających sekrety lub pełne dane płatnicze.
