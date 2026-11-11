# Einrichtung und Betrieb

## Entwicklung

Voraussetzungen: PHP 8.2 oder neuer mit den laut Composer benötigten Erweiterungen,
Composer 2, Node.js 20.19+ oder 22.12+, npm und MySQL/MariaDB. Das Projekt
verwendet Laravel 12 und Vue 3. Alternativ kann der Devcontainer verwendet werden.

1. `composer install` und `npm ci` ausführen.
2. `.env.example` nach `.env` kopieren, Datenbankzugang konfigurieren und
   `php artisan key:generate` ausführen.
3. Eine **neue, leere** Datenbank anlegen und `php artisan migrate --seed` ausführen.
   Die SQL-Dateien im Repository sind separate Alt-/Migrationsdaten und werden
   nicht automatisch importiert.
4. `php artisan serve` und in einem zweiten Terminal `npm run dev` starten.
   Die Standardports sind 8000 (API) und 5173 (Vite).
5. `resources/vue/.env.example` nach `resources/vue/.env` kopieren. Die SPA nutzt
   `VITE_API_URL=/api`; Vite leitet `/api` und `/sanctum` an Port 8000 weiter.
   Für einen abweichenden Backend-Host kann beim Start von Vite die Variable
   `API_PROXY_TARGET` gesetzt werden. Den tatsächlich benutzten Browser-Host samt
   Port in `SANCTUM_STATEFUL_DOMAINS` eintragen.

Der Standard-Seeder legt Teams, aber keine Benutzer oder Saison an. Der optionale
DevelopmentSeeder enthält ein bekanntes Testpasswort und darf nicht in einer
öffentlich erreichbaren oder produktiven Umgebung ausgeführt werden.

## Prüfungen

```sh
php artisan test
npm run typecheck
npm run build
composer audit
npm audit
```

`npm run build` schreibt nach `resources/vue/dist`; dieses Verzeichnis ist derzeit
teilweise versioniert. Generierte Änderungen vor einem Commit prüfen.

## Produktion

Entwicklungsserver nicht öffentlich betreiben. HTTPS, einen Webserver mit
Document-Root `public/`, eine getrennte gebaute SPA mit History-Fallback sowie
einen API-Reverse-Proxy einrichten. `APP_ENV=production`, `APP_DEBUG=false`,
einen einzigartigen `APP_KEY`, restriktive Dateirechte und gesicherte, regelmäßig
getestete Datenbank-Backups verwenden. Secrets nicht einchecken.

Den Laravel-Scheduler zuverlässig starten (z. B. `php artisan schedule:run`
minütlich per Cron); nur in `routes/console.php` registrierte Aufgaben laufen
automatisch. Falls Jobs über die Datenbank-Queue verwendet werden, zusätzlich
einen überwachten `php artisan queue:work`-Prozess betreiben. Push-Benachrichtigungen
benötigen HTTPS, gültige VAPID-Schlüssel und eine vollständige Subscription-Integration;
die vorhandenen Push-Controller allein aktivieren diese Funktion noch nicht.

Frontend und API müssen über dieselbe HTTPS-Origin erreichbar sein; der Reverse
Proxy leitet `/api` und `/sanctum` an Laravel weiter. Das Browser-Login verwendet
eine HttpOnly-Sitzung und Sanctums CSRF-Schutz. In Produktion `SESSION_SECURE_COOKIE=true`
und `SANCTUM_STATEFUL_DOMAINS` auf die verwendete Domain setzen. Mehrere Geräte
können gleichzeitig angemeldet sein; Logout beendet die aktuelle Sitzung.
Für Push gültige VAPID-Schlüssel setzen und auf jedem Gerät Push aktivieren;
Push benötigt HTTPS oder localhost. `notifications:dispatch` wird bei gesetzten
VAPID-Schlüsseln vom Scheduler minütlich ausgeführt.
