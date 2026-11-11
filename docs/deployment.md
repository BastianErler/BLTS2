# BLTS2: CI, Releases und Betrieb

## Festgestellter Bestand (lesend geprüft)

GitHub Actions ist vorhanden. `main` ist der Repository-Standardbranch und der
ausgecheckte Branch auf Produktion; der produktive Commit war bei der Prüfung
`9eec89e`, also älter als der aktuelle `main`. Der Debian-12-LXC `blts` hat
Nginx 1.22, PHP 8.2-FPM, MariaDB 10.11 und einen laufenden GitHub-Runner
`blts-lxc` als Benutzer `github-runner`. Docker wurde dort nicht gefunden.
Vor der freigegebenen Erstumstellung bediente Nginx die SPA aus
`/var/www/BLTS2/resources/vue/dist` und Laravel über
`/var/www/BLTS2/public/index.php`. Dieser ursprüngliche Pfad bleibt als echtes
Git-Verzeichnis bestehen. Dort liegen unversionierte Assets; nicht löschen.
`storage` und `.env` gehören derzeit `www-data`; die `.env` war bei der Prüfung
für andere lokale Benutzer lesbar (`0644`) und sollte bei der gesondert
freizugebenden Einrichtung restriktiver abgesichert werden. Kein Laravel-Queue-Prozess und
kein Laravel-Croneintrag wurden gefunden; Cron selbst läuft. Diese Beobachtungen
sind eine Momentaufnahme, kein Beweis, dass keine externen Jobs existieren.
Der Runner hat bereits einen passwortlosen sudo-Eintrag für `systemctl reload nginx`
und allgemeine sudo-Berechtigung mit Passwort. Für das vorgesehene FPM-Reload
besteht bisher kein passwortloser Eintrag; bestehende Runner-Rechte sollten bei der
Freigabe auf das tatsächlich Notwendige reduziert werden.

`cloudflared` läuft als tokenverwalteter Tunnel; ob genau dieser Tunnel die
öffentliche Route bedient, muss in Cloudflare verifiziert werden. Die öffentliche
Domain `https://blts.feos.dev` antwortet über Cloudflare. `/assets/` wird mit
`public, immutable, max-age=31536000` ausgeliefert; `/api/me` antwortete privat
und dynamisch. **`/sw.js` wurde mit `max-age=14400` ausgeliefert**: Die
Cloudflare-Cache-Regeln müssen vor dem ersten Deployment geprüft und korrigiert
werden. Keine AWS-Infrastruktur wird verwendet.

## Bereits freigegebene Erstumstellung

Nginx zeigt jetzt auf `/srv/blts2/current`; dieses initiale Release enthält noch
den bisherigen Produktionscode `9eec89e328a25d0c4248ba17187de584aa35e39f`.
Origin und öffentliche Domain lieferten nach der Umstellung dasselbe HTML wie
vorher; `/api/me` antwortete jeweils mit 401. Nginx-Konfigurationstest und
gezielter Reload waren erfolgreich. Der neue Origin liefert für HTML, Service
Worker und Manifest restriktive Cache-Header, Cloudflare lieferte beim Nachtest
`sw.js` aber weiterhin als Cache-HIT mit `max-age=14400` aus.
Ein Laravel-Datenbanktest als `www-data` war erfolgreich. Der Bootstrap-Cache des
initialen Releases wurde für `www-data` und `github-runner` beschreibbar gemacht.

`/srv/blts2/shared/storage` zeigt bewusst auf das bisherige
`/var/www/BLTS2/storage`, damit Sitzungen und Uploads erhalten bleiben. **Dieses
ursprüngliche Verzeichnis darf daher auch nach der Umstellung nicht gelöscht
werden.** Die gemeinsam genutzte `.env` liegt separat mit Modus 0640 vor. Der
Runner hat jetzt ein geprüftes sudo-Recht ausschließlich für den gezielten Reload
von `php8.2-fpm.service`; vorhandene andere sudo-Regeln wurden nicht verändert.
Eine root-private Dateikopie und ein konsistenter InnoDB-Dump wurden unter
`/root/blts2-backups/pre-cutover-rMWy3tDW` erstellt und auf Lesbarkeit geprüft.
Das ist **kein getesteter Restore** und keine unabhängige Off-Host-Sicherung;
Dateikopie und Datenbankdump entstanden während des laufenden Betriebs.

Das automatische Deployment bleibt deaktiviert. Runner-Label, GitHub-Environment,
Cloudflare-Regeln und Purge-Token sind noch einzurichten. Die Einführung einer
neuen Anwendungsversion und Datenbankmigrationen benötigen weiterhin eine eigene
Freigabe.

## Schutz vor unbeabsichtigtem Deployment

PRs laufen ausschließlich auf GitHub-gehosteten Runnern, ohne Produktions-Secrets.
Tests laufen mit SQLite und MariaDB 10.11, dazu Typecheck, Produktionsbuild und
Release-Skript-Tests. Ein Push auf `main` und manuelles `workflow_dispatch` bauen
ein Artefakt aus genau diesem Commit mit PHP 8.2, Node 22 und den Lockfiles.
Composer-Produktionspakete und Vite werden **in CI** gebaut. `VITE_API_URL=/api`
ist öffentliche Konfiguration; niemals Secrets als `VITE_*` oder in Artefakte legen.

Der Produktionsjob ist standardmäßig durch die **Repository-Variable**
`BLTS_DEPLOY_ENABLED` gesperrt. Erst nach expliziter Freigabe, Einrichtung und
Validierung auf `true` setzen. Die GitHub-Environment `production` mit
Branch-Beschränkung auf `main` und, soweit im GitHub-Tarif verfügbar, verpflichtender
Freigabe einrichten. `main` durch Branch Protection und PR-Review schützen.
Der Runner benötigt das explizite Label `blts-production`; es wird im Repository
nicht automatisch vergeben. Unvertrauenswürdige Workflows dürfen ihn niemals
verwenden: Runnergruppe/Repository-Rechte entsprechend einschränken, insbesondere
Fork-PR- und Workflow-Änderungen prüfen. Ein Runner direkt auf Produktion hat
grundsätzlich Zugriff auf produktive Daten; ein separater dedizierter LAN-Runner
mit eingeschränktem Zugang wäre sicherer. Keine Routerports öffnen.

## Erstmalige Einrichtung / Nachvollziehbarkeit

Die oben dokumentierte Erstumstellung ist erfolgt; verbleibende Schritte und jede
zukünftige Änderung an Produktion oder Cloudflare benötigen separate Freigabe.
Vor dem ersten Release den Commit-Unterschied zu `9eec89e` und sämtliche offenen
Migrationen prüfen; der Versionssprung ist erheblich. Ein konsistentes MariaDB-Backup
und eine getestete Wiederherstellung sowie ein Backup von `.env`, `storage`,
Upload-Dateien, bestehendem Code und *allen* alten Vite-Assets anfertigen.
Webserver- und PHP-FPM-Erweiterungen mit dem Artefakt und
`composer check-platform-reqs --no-dev` abgleichen. CLI und FPM können abweichen.

1. Einen geeigneten absoluten `BLTS_DEPLOY_ROOT` festlegen und dort `releases/`,
   `shared/storage/` und `shared/assets/` vorbereiten. Der Runner darf nur diese
   Release- und Shared-Pfade verwalten. PHP-FPM (`www-data`) benötigt Lesezugriff
   auf Code und Schreibzugriff auf `shared/storage` und Release-`bootstrap/cache`;
   passende dedizierte Gruppe/ACL und restriktive `umask`, kein `chmod 777`.
   Eine produktive `.env` als `shared/.env` außerhalb von Releases sicher ablegen;
   nicht ins Repository oder Artefakt kopieren. Webserver und Runner benötigen nur
   die jeweils notwendigen Rechte. Vorhandene Dateien bleiben erhalten.
2. Bestehende Live-Version einschließlich **unversionierter Assets** in ein
   erstes Release-Verzeichnis übernehmen, den vollständigen ursprünglichen Commit
   als `RELEASE_SHA` hinterlegen und `FRONTEND_SHA` als SHA-256 von
   `resources/vue/dist/index.html` schreiben. `shared/storage` muss alle bisherigen
   persistenten Daten enthalten. Da während des Kopierens geschrieben werden kann,
   ist für die abschließende Synchronisierung ein abgestimmtes Wartungsfenster oder
   ein nachweislich konsistentes Verfahren nötig. Nicht `rsync --delete` verwenden.
   Alte und neue gehashte Assets werden beim Release zusätzlich in `shared/assets`
   gesammelt und niemals automatisch gelöscht.
   **Vor dem Nginx-Wechsel** müssen auch im initialen Release `.env` und `storage`
   auf die vorbereiteten Shared-Ziele zeigen und ein vorhandener `public/storage`-
   Link dorthin auflösbar sein. Andernfalls würden zwischen Erstumstellung und
   erstem Deployment neue Daten in einem anderen Verzeichnis landen. Das nur an
   der Release-Kopie vorbereiten, nicht am noch aktiven Originalverzeichnis.
3. `current` als Symlink auf das initiale Release anlegen. Nginx muss anschließend
   auf `<BLTS_DEPLOY_ROOT>/current/resources/vue/dist` und für **beide** FastCGI-
   Routen auf `<BLTS_DEPLOY_ROOT>/current/public/index.php` zeigen. Dies ist eine
   Änderung an der aktiven Konfiguration und braucht vorherige Freigabe. Bestehende
   Tunnel-, Forwarded-Header- und HTTPS-Behandlung unverändert übernehmen und
   prüfen; `nginx -t` vor gezieltem Reload. Die Beispiel-Locations in
   `scripts/deploy/nginx-cache.conf.example` nur geprüft in den bestehenden
   Serverblock übernehmen, nicht blind einbinden oder doppelt definieren.
4. In Cloudflare für `/api/*` und `/sanctum/*` gemeinsames Caching deaktivieren,
   Cookies, erforderliche Header und Query-Parameter unverändert an den Origin
   weiterleiten und HTTPS/Sanctum mit der tatsächlichen Origin-Struktur prüfen.
   `/`, `/index.html`, `/sw.js`, `/registerSW.js`, `/manifest.webmanifest` und
   `/offline.html` dürfen nicht länger als veraltete Version festgehalten werden;
   Regeln müssen Origin-`no-store` respektieren bzw. Cache umgehen. Gehashte
   `/assets/*` bleiben langfristig cachebar. Cloudflare-Regeln und Tunnel nicht
   ohne separate Freigabe ändern.
5. Dem Runner ausschließlich einen gezielten, passwortlosen Aufruf
   `/usr/bin/systemctl reload php8.2-fpm.service` erlauben; keine allgemeine
   `sudo`- oder Schreibberechtigung für Systemd/Nginx. Die Anwendungspfade und
   PHP-FPM-/OPcache-Konfiguration so einrichten, dass Reload tatsächlich neuen
   Code lädt. Queue/Horizon/andere langlebige Prozesse inventarisieren: Der Job
   führt `queue:restart` aus, konfiguriert aber keinen Supervisor und startet
   keinen Scheduler. Einen vorhandenen Scheduler nicht duplizieren; falls benötigt,
   gesondert genau einen Scheduler einrichten und überwachen.
6. GitHub-Environment-Variablen setzen: `BLTS_DEPLOY_ROOT` (absoluter vorbereiteter
   Pfad), `BLTS_ORIGIN_URL` (Origin direkt vom LAN-Runner, z. B. nach Prüfung
   `http://127.0.0.1`), `BLTS_HOST_HEADER` (`blts.feos.dev`), `BLTS_PUBLIC_URL`
   (`https://blts.feos.dev`) und `CLOUDFLARE_ZONE_ID`. Environment-Secret
   `CLOUDFLARE_API_TOKEN`: nur **Cache Purge** für die betreffende Zone. Cloudflare
   bietet hier kein AWS-OIDC; ein begrenztes, rotierbares Token ist die Alternative.
   Keine SSH-Keys oder AWS-Zugangsdaten für diesen lokalen Runner erforderlich.

Die neue Anwendung verwendet Sanctum-Sitzungscookies. Produktion muss
`APP_ENV=production`, `APP_DEBUG=false`, sichere Cookies und die korrekte
`SANCTUM_STATEFUL_DOMAINS`-Liste in der bereits vorhandenen `.env` haben. Der
Reverse Proxy muss `/api` und `/sanctum` unter derselben HTTPS-Origin bereitstellen.
Bei der lesenden Prüfung war `SESSION_SECURE_COOKIE` nicht ausdrücklich aktiviert
und `blts.feos.dev` nicht in der wirksamen Sanctum-Stateful-Liste. Vor einem neuen
Release müssen diese Einstellungen in der Shared-`.env` nach gesonderter Freigabe
geprüft bzw. ergänzt werden; die `.env` wurde nicht überschrieben.
Konkrete Werte und bestehende Cloudflare-Regeln vor Umstellung prüfen.

## Ablauf, Prüfung und Rollback

Nach der Freigabe startet ein Push/Merge auf `main` nach erfolgreicher CI den
Produktionsjob. `workflow_dispatch` auf `main` bietet `deploy`, `purge` und
`rollback`. Der Job ist durch GitHub-Concurrency und `flock` serialisiert.
Er validiert das Prüfsummenartefakt, erstellt ein neues Release, verlinkt Shared-
Daten, prüft PHP-Erweiterungen, baut Laravel-Caches und führt `migrate --force`
vor dem atomaren Symlinkwechsel aus. Danach werden PHP-FPM gezielt neu geladen,
Queue-Worker über `queue:restart` zum Neustart aufgefordert und der Origin direkt
geprüft: HTML-Hash, Laravel-Release-SHA und Datenbankverbindung. Erst danach
erfolgen gezielte Cloudflare-Purges für veränderliche Dateien und die gleiche
Prüfung über die öffentliche Domain. Der öffentliche Check meldet auch veralteten
Cache; er beweist allein nicht, dass jeder Edge-Standort bereits aktualisiert ist.
Vor dem Release prüft der Job Pfade, notwendige Variablen und das gezielte
FPM-Reload-Recht. Das prüft nur, ob das Cloudflare-Token vorhanden und formal
plausibel ist, **nicht**, ob es im Cloudflare-Konto gültig ist.

Bei Fehler nach dem Symlinkwechsel **vor bestandener Origin-Prüfung** stellt das
Skript den vorherigen Code-Symlink wieder her und lädt FPM erneut. Ein Fehler beim
Cloudflare-Purge oder öffentlichen Check ist sichtbar, löst aber keinen automatischen
Code-Rollback aus: per `workflow_dispatch` mit `purge` erneut versuchen und Cache-
Regeln prüfen. Releases und gehashte Assets bleiben erhalten; es erfolgt bewusst
keine automatische Bereinigung. Speicherplatz überwachen und Aufbewahrung erst nach
Festlegung einer sicheren Frist und nur nach erfolgreichem Release manuell prüfen.
Ein fehlgeschlagen vorbereitetes Release bleibt zur Untersuchung unter seiner SHA
liegen; ein erneutes Deploy derselben SHA wird absichtlich verweigert. Erst nach
Prüfung von Schema, aktivem Symlink und Inhalt ein nicht aktives Fehlverzeichnis
gezielt archivieren oder entfernen. Die Datenbank wird dabei nicht zurückgesetzt.

Für Rollback `workflow_dispatch` → `rollback` und vollständigen SHA eines bereits
vorhandenen, kompatiblen Releases angeben. Origin wird geprüft; danach folgen Purge
und öffentlicher Check. Das **ursprüngliche Bootstrap-Release vor Einführung des
Health-Endpunkts** kann dieser automatisierte Rollback nicht verifizieren: Für
diesen Sonderfall ist ein vorab geübter manueller Rückwechsel des Symlinks, gezielter
FPM-Reload und gesonderte Prüfung von SPA, API und Datenbank notwendig.

Ein Code-Rollback macht **keine** Datenbankmigration rückgängig. Niemals pauschal
`migrate:rollback` ausführen. Nur rückwärtskompatible Expand-/Contract-Änderungen
automatisch deployen; erst nach Ablauf der Rollback-Frist alte Spalten/Verhalten in
einem separaten Release entfernen. Ein Symlinkwechsel verhindert keine Konflikte
mit laufenden Requests oder Workern. Bei inkompatiblen Migrationen: abgestimmtes
Wartungsfenster und geprüfter Datenbank-Restore-/Forward-Fix-Plan statt Automation.
