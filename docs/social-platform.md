# Social-Plattform: Einrichtung und Betrieb

Implementierung auf `codex/social-platform`, basierend auf CAD-Stand `c76dd32`. Die Änderungen sind lokal; es wurde keine Produktivdatenbank migriert und kein öffentliches Deployment ausgeführt.

## Enthalten

- Eigene Vue-App mit Plattformauswahl, Stadtpanorama, fünf eigenen Icon-Stilen und hochladbaren Icons. Namen, Farben, Hintergrund, Logo und Header sind konfigurierbar. Heller, dunkler und Systemmodus; responsiver Feed mit Hashtags links und Werbung/Informationen rechts.
- Social-Feed, Fotobereich, Marktplatz und Videobereich mit Kommentaren, Likes/Dislikes und Teilen unter Beibehaltung der ursprünglichen Sichtbarkeit. Profile mit Avatar, Header, Beschreibung und Signatur; Freundschaften, Blockieren, drei Sichtbarkeitsstufen.
- Einzelchats mit Anhängen, Formatierung und Mehrfachversand als getrennte Zustellungen; Suche und Benachrichtigungen. Nachrichten werden regelmäßig abgefragt. Keine Gruppenchats oder Livestreams.
- Unternehmensseiten und berechtigte Mitarbeiter; Werbeplätze, Zeiträume, Vorschau, Entscheidung, getrennte Zahlungsbestätigung in RP-Dollar, Countdown und interne Nachrichtenvorlagen. Keine externen E-Mails oder echte Zahlungsabwicklung.
- Manuelle oder sofortige Kontofreigabe, Wiederherstellungscode, Moderation, Meldungen, zeitweise Sperren, Einsprüche und Auditprotokoll. Technische Administration, Moderation und Werbeverwaltung haben getrennte Rechte.
- Modulverwaltung mit serverseitigen Sperren. Optionales endgültiges Löschen ist nur für technische Administratoren nach Vorschau und Eingabe der Bestätigung möglich. Dateien werden über eine wiederholbare Bereinigungswarteschlange entfernt.
- Eigene Social-Sitzungen und persönliche CAD-Mandanten ohne CAD-Rechte. Kein SSO. Das behördliche Firmenregister ist keine Social-Unternehmensseite.

## Bestehende Installation erweitern

1. Werte aus `.env.social.example` in die bestehende `.env` übernehmen, bestehende Geheimnisse behalten. `SOCIAL_URL` und `SOCIAL_ORIGIN` sind die vollständige HTTPS-Adresse, `SOCIAL_DOMAIN` nur der Hostname. `SOCIAL_ENABLED=true`, `COMPOSE_PROFILES=social` aktivieren die Dienste.
2. Vor der Datenbankänderung eine konsistente Sicherung von Datenbank und Dateien erstellen. Migration `0018_social_platform.sql` wird vom bestehenden Backend-Entrypoint ausgeführt. Sie erweitert den Mandantentyp um `personal` und erzeugt Social-Tabellen. Bestehende Behörden werden nicht automatisch umklassifiziert.
3. Mit der gewählten Compose-Datei bauen und starten, beispielsweise `docker compose -f docker-compose.yml up -d --build`. Beim lokalen Werkzeug ohne Compose-Plugin heißt der Befehl `docker-compose`.
4. Erstes Social-Konto registrieren. Anschließend ausschließlich im Container/Terminal: `docker compose exec backend php social/manage.php admin HANDLE`. Der Handle ist der registrierte Social-Benutzername. Dies gibt keine CAD-Administratorrechte.
5. In der Community-Konfiguration Registrierung, Namen, Module und Branding einstellen. Im Verwaltungsbereich Moderatoren bzw. Werbeverwalter zuweisen, Unternehmensseiten anlegen und Mitarbeiter über die Namenssuche auswählen.

Der CAD-Frontend-Build erhält `VITE_SOCIAL_URL` und `VITE_SOCIAL_ENABLED` aus Compose. Bei aktivem Social ist das Icon fest im CAD-Desktop enthalten. Änderungen dieser Installationswerte erfordern einen Frontend-Neubau.

**Nur CAD:** `SOCIAL_ENABLED=false`, Social-Profil nicht starten. **Nur Social:** `CAD_ENABLED=false`, `COMPOSE_PROFILES=social` und zusätzlich `-f docker-compose.social-only.yml` verwenden. Diese Ergänzung nimmt CAD-Frontend und Socket-Server aus dem Standardstart; das Backend sperrt CAD-Routen. Einzelne Dienste nicht explizit entgegen ihrem Profil starten.

## Medien und Videos

`SOCIAL_STORAGE=local` speichert Medien im privaten Volume `social_data`; sie liegen nicht unter öffentlichen CAD-Uploads. Mit `SOCIAL_STORAGE=s3` und den `SOCIAL_S3_*`-Werten werden neue Dateien in einem privaten Bucket gespeichert. Downloads laufen nach Zugriffsprüfung durch das Backend. Bereits gespeicherte Medien behalten ihren Provider: ein Umschalten migriert keine Bestandsdateien, alte Speicher müssen erreichbar bleiben. Ein Wechsel des Buckets erfordert eine separate Datenübernahme.

Der Worker verarbeitet Videos mit FFmpeg zu MP4 und bereinigt gelöschte Dateien. Verarbeitung und redaktionelle Freigabe sind getrennte Zustände. Eine neue/ersetzte Videodatei wird nicht automatisch veröffentlicht. Größen-, Dauer- und Kontingentgrenzen sind konfigurierbar. Standard: 20 MB Bilder/Anhänge, 200 MB Videos, zehn Minuten Video, 1 GB je Profil. Zulässige Anhänge sind Bilder, PDF und Text; Bilder werden serverseitig neu kodiert.

Datenbank und `social_data` bzw. S3-Objekte müssen gemeinsam gesichert und bei einer Wiederherstellung konsistent zurückgespielt werden. Dazu das vorhandene Backup-Verfahren der Installation erweitern; diese Änderung ersetzt dessen Zeitplanung nicht. Während einer konsistenten Sicherung Uploads und Worker anhalten. Eine Wiederherstellung zuerst in einer getrennten Installation prüfen.

## Lokale Prüfung

Die Testdatenbank enthält ausschließlich Fixtures und liegt in einem temporären Container-Dateisystem. Kein Test benutzt die Produktivdatenbank.

```sh
npm --prefix social ci
(cd social && npx playwright install chromium)
bash social/tests/verify.sh
```

Der Prüflauf benötigt `docker-compose`, erzeugt einen eigenen Projektnamen und räumt ausschließlich diesen Teststack anschließend auf. Die laufende Vorschau auf 5174/8088 bleibt erhalten. Standardports für den Test sind 5186/8087; bei Bedarf `SOCIAL_TEST_UI_PORT` und `SOCIAL_TEST_PORT` setzen. Geprüft werden persönliche Mandanten, Rollen, Sichtbarkeit, Medien, Videofreigabe, Werbung, Nachrichten und Modulabschaltung sowie reale Browserformulare, Themes und mobile Breite.

Eine vollständige Neuinstallation mit dem bestehenden CAD-Grundschema und dem echten Migrationseinstieg lässt sich separat prüfen:

```sh
docker-compose -f backend/social/tests/install.yml up -d --wait
docker-compose -f backend/social/tests/install.yml exec -T backend php run_migrations.php
docker-compose -f backend/social/tests/install.yml down
```

Diese Installation besitzt ebenfalls nur eine temporäre Datenbank. Der zweite Migrationslauf muss „All migrations are already applied“ melden.

## Grenzen und Betriebsabnahme

- Ein Konto entspricht zunächst einer RP-Identität. Eine spätere Charakterauswahl ist noch nicht vereinbart.
- Bestehende Behörden müssen anhand ihrer tatsächlichen Funktion typisiert werden. Das geschieht nicht anhand vermuteter Namen.
- S3 benötigt eine Prüfung mit dem Bucket des Betreibers; die lokale Integrationssuite verwendet den lokalen Speicher.
- Feed und Suche arbeiten mit begrenzten, paginierten Abfragen. Belastungstests mit den erwarteten Nutzerzahlen und Medienmengen stehen vor dem Produktivstart an.
- Der bestehende CAD-Composer-Lock enthält weiterhin den bereits vorhandenen niedrigen `firebase/php-jwt`-Auditbefund. Die Social-Sitzungen nutzen diese Bibliothek nicht; ein CAD-Auth-Upgrade gehört in eine getrennt geprüfte Änderung.

Asset-Herkunft: [social-assets.md](social-assets.md). Vereinbarter Produktumfang: [social-implementation-scope.md](social-implementation-scope.md).

## Lokale Demo

Nach einem frischen Start des Teststacks erstellt `node social/tests/seed-demo.mjs` ausschließlich auf `127.0.0.1:8088` Beispielkonten und Beiträge. Vorschau: `http://127.0.0.1:5174`. Demo-Login: `demo`, Passwort `Nur-lokale-Demo-2026!`. Diese Zugangsdaten sind bewusst nur für die wegwerfbare lokale Testinstallation vorgesehen. Die Demo aktiviert Gastlesen und sofortige Registrierung; Produktionsdefaults bleiben Gastlesen aus und manuelle Freigabe. Integrationstests anschließend wieder mit einer frischen Testdatenbank ausführen.

## Seitenboxen und Banner

Der Feed hat einen Bildbanner (konfigurierbarer Header, sonst das mitgelieferte Stadtpanorama) und eingefasste Seitenboxen. `operator_name` legt den Anzeigenamen der Social-Media-Betreiberfirma fest; ohne Eintrag wird der Plattformname verwendet. Der zusätzliche Anmeldehinweis im Feed entfällt.

Links stehen Highlights aus den aktuell abrufbaren Beiträgen, Hashtags und geöffnete Unternehmen. Berechtigte Mitarbeiter und Moderatoren können auf einer Unternehmensseite den Öffnungsstatus für zwei Stunden setzen oder sofort beenden; die API erlaubt maximal zwölf Stunden. Die Anzeige läuft automatisch ab. Migration `0019_social_discovery.sql` ergänzt dafür eine eigene Tabelle. Rechts erscheinen das neueste sichtbare, freigegebene und fertig verarbeitete Video sowie die bestehenden buchbaren Sidebar-Werbeflächen. Private Videos werden dadurch nicht öffentlich.

## Beitrag verfassen

Der Editor unterstützt Fett, Kursiv, Durchstreichen und Listen mit einer formatierten Vorschau. Die optionale Kennzeichnung „KI-generiert“ wird gespeichert und am Beitrag angezeigt; sie ist eine Angabe des Verfassers, keine automatische Erkennung. Migration `0020_social_post_metadata.sql` ergänzt das Feld mit Standardwert aus.

Social-, Foto- und Videobeiträge verwenden die in den Community-Einstellungen gepflegte Kategorienliste (1–30 Einträge, maximal 60 Zeichen je Kategorie). Die erste Kategorie ist die Vorgabe für neue Beiträge. Entfernte Kategorien bleiben an alten Beiträgen erhalten; neue Beiträge müssen eine aktuelle Kategorie verwenden. Marktplatzkategorien bleiben separat. Der Feed lässt sich nach Kategorie filtern.

## Lesezeichen und Unternehmensverzeichnis

Über „Merken“ lassen sich Beiträge persönlich speichern und unter „Gespeicherte Beiträge“ wiederfinden. Es werden keine öffentlichen Favoritenlisten oder Benachrichtigungen an Autoren erzeugt. Bei jedem Abruf werden Sichtbarkeit und Modulfreigabe erneut geprüft; ein Lesezeichen gewährt keinen zusätzlichen Zugriff.

Das Unternehmensverzeichnis zeigt sämtliche Social-Unternehmensseiten mit Beschreibung, Standort, Kontakt und Öffnungsstatus. Verifizierungen vergeben ausschließlich Moderatoren bzw. technische Administratoren über die Unternehmensverwaltung. Mitarbeiter dürfen Beschreibung, Standort, Kontakt und Öffnungsstatus selbst pflegen, aber keine Verifizierung setzen oder entfernen. Das Verifizierungsabzeichen erscheint auch an Unternehmensbeiträgen. Migration: `0021_social_bookmarks_directory.sql`.

## Erweiterte Unternehmensprofile und Ansichtsoptionen

Die Unternehmens-App ist über die Plattformauswahl erreichbar. Neu angelegte Unternehmensseiten erscheinen automatisch im Verzeichnis. Mitarbeiter bzw. Moderatoren pflegen Foto, Leistungen, Einsatzgebiet, Kontakt, textuelle Öffnungszeiten und einen Kartenpunkt. Öffnungszeiten sind die reguläre Verfügbarkeit; der aktuelle Geöffnet/Geschlossen-Status wird weiterhin separat gepflegt. Migration `0022_social_company_profiles.sql` ergänzt diese Angaben.

Die GTA-Karte verwendet die bereits im CAD enthaltenen Atlas-Kacheln (Zoomstufe 2, unverändert nach `social/public/gta-map` kopiert). Ein Standort wird per Klick oder über zugängliche Zahlenfelder gesetzt und als relative Position auf dieser Karte gespeichert; es gibt keine Live-Ortung. Das Foto wird wie andere Medien privat gespeichert und erst über seine Zuordnung zur öffentlichen Unternehmensseite zugänglich.

Kategorien sind zusätzlich als Filterchips oberhalb des Feeds erreichbar und werden unterhalb des Beitrags angezeigt. Die Formatierungsleiste kennzeichnet aktive Formatierungen mit Rahmen und `aria-pressed`; erneutes Betätigen entfernt die Formatierung der Auswahl. „Ohne Werbung“ filtert Beiträge mit Kategorie „Werbung“ oder dem eigenständigen Hashtag #Werbung (unabhängig von Groß-/Kleinschreibung), auch beim Teilen solcher Beiträge. Gebuchte Header-, Feed- und Seitenwerbung bleibt sichtbar. Die Filterung gilt für Feeds, Suche, Lesezeichen und Vorschläge; direkte Beitragslinks bleiben erreichbar. Die Auswahl wird lokal im Browser gespeichert und verändert keine Buchungen.

## Farben der Oberfläche

Technische Administratoren können unter Community-Einstellungen die Farben für Hell- und Dunkelmodus unabhängig setzen: Seitenhintergrund, Karten/Dialoge, Hoverflächen, Haupt- und Sekundärtext, Rahmen, Markierungen sowie Navigationshintergrund und Navigationstext. Eine Vorschau zeigt das Ergebnis vor dem Speichern. Jeder Modus kann separat auf die Standardfarben zurückgesetzt werden. Die bestehende Akzentfarbe bleibt separat einstellbar. Die Konfiguration liegt in `theme_colors` in den vorhandenen JSON-Einstellungen; eine zusätzliche Datenbankmigration ist nicht nötig. Das Backend akzeptiert nur bekannte Farbrollen und sechsstellige Hex-Farben.


## Integrationsprüfung, 16. September 2026

- 19 API-Prüfungen bestanden; Browserprüfung von Launcher, Branding, Beitrag, Hell/Dunkel und Mobilansicht bestanden.
- Social-Typprüfung und Vite-Build bestanden; Backend- und Social-Docker-Images gebaut.
- Frisches vollständiges CAD-Schema: fünf neue Migrationen erfolgreich, Wiederholung ohne Änderungen. Social-only liefert Social aus und sperrt CAD-Pfade.
- Worker und Social warten auf den gesunden Backendstart einschließlich Migrationen. Die Entwicklungs-DB-Zugangsdaten des Workers stimmen mit dem Backend überein.
- Social erlaubt technisch Uploads bis zum konfigurierbaren Maximum von 1024 MiB, mit zusätzlichem Platz für Multipart-Metadaten; kleinere Datei- und Kontolimits gelten weiterhin in der API. Vorgeschaltete Proxys müssen entsprechende Grenzen erlauben.
- `npm --prefix frontend run type-check` prüft jetzt ausdrücklich das CAD-App-Projekt. Diese vollständige Prüfung meldet derzeit zahlreiche CAD-Typfehler (u. a. API-Header und Desktop-Typen). Der bisherige Aufruf auf die Referenzkonfiguration war kein belastbarer Nachweis. Die CAD-Typfehler sind vor einer vollständigen Produktivabnahme noch zu beheben; der bestehende CAD-Dockerbuild führt weiterhin nur Vite aus.
- Kein Produktivdeployment und keine Änderung bestehender Behördenklassifizierungen erfolgt.

Für die lokale Docker-Entwicklung `SOCIAL_URL=http://localhost:5174` und `SOCIAL_ORIGIN=http://localhost:5174` setzen; alle Zugriffe dann über dieselbe Adresse führen. Die Beispielwerte in `.env.social.example` sind für eine eigene HTTPS-Domain gedacht.

## Coolify-Erweiterung einer bestehenden CAD-Installation

In derselben Compose-Anwendung `docker-compose.coolify.yml` verwenden und die bestehenden Datenbank- und Upload-Volumes beibehalten. Keine zweite leere CAD-Anwendung als Ersatz erstellen.

- `COMPOSE_PROFILES=social`, `SOCIAL_ENABLED=true`, `CAD_ENABLED=true` setzen. Sicherstellen, dass Coolify das Compose-Profil beim Start aktiviert.
- `SOCIAL_URL` und `SOCIAL_ORIGIN` auf die vollständige eigene HTTPS-Social-Adresse setzen.
- Im Coolify-Service `social` diese Domain mit Containerport 80 konfigurieren. Der Dienst liefert die Oberfläche und leitet `/api/social/` intern an `backend` weiter.
- Backend-, CAD- und Socket-Domains unverändert lassen. Der Worker bekommt keine Domain und keinen HTTP-Healthcheck.
- Backend, CAD-Frontend, Social und Worker aus demselben Commit neu bauen. Die Desktop-Verknüpfung wird zur Buildzeit konfiguriert.
- Nach Sicherung die Migrationen beim Backendstart kontrollieren, anschließend HTTPS, Anmeldung, Desktop-Verknüpfung und einen freizugebenden Video-Upload prüfen.
- Technisches Social-Administratorkonto erst nach Registrierung über `php social/manage.php admin HANDLE` im Backend-Container zuweisen. Es gibt keine automatisch eingerichteten Produktions-Demokonten.
