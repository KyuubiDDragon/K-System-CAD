# Gesetze-App

## Einstieg

Im CAD-Desktop über **Gesetze** oder `/laws`; in Social über `#/laws`. Veröffentlichte Fassungen sind standardmäßig ohne Anmeldung lesbar. Die Systemverwaltung kann Gastzugang und Modul unabhängig konfigurieren.

## Zuständigkeit

Das System-CAD mit aktuellem Systemrecht behält vollständigen Zugriff. Unter **Community-Verwaltung → Zugriff auf die gesamte Gesetze-App** vergibt es die App-Zuständigkeit an Behörden: Entwürfe lesen, Bearbeiten, Veröffentlichen und Außer Kraft setzen. Diese Freigabe gilt für alle vorhandenen und zukünftigen Gesetzbücher und aktiviert die CAD-Funktion `laws`.

Mitarbeiter benötigen zusätzlich die passenden CAD-Rollenrechte (`READ_LAWS_DRAFTS`, `WRITE_LAWS_DRAFTS`, `WRITE_LAWS_PUBLICATION`, `WRITE_LAWS_REPEAL`). Die API prüft aktuelle Rollen, App-Freigabe und aktivierte Funktion bei jeder Anfrage. Social-Rechte erlauben keine Redaktion. Redakteure können keine App-Zugriffe verteilen.

Gesetzbücher und Paragraphen sind gemeinschaftliche Inhalte ohne Behördenzuordnung. Autor und Herausgeber bleiben an den Fassungen zur Nachvollziehbarkeit gespeichert.

## Erstellen und lesen

Über **Neues Gesetzbuch** legen Redakteure Bücher an, optional anhand der fünf Titel-/Kapitelvorlagen. Es werden keine Musterregeln automatisch veröffentlicht. Links zeigt das Inhaltsverzeichnis Kapitel und natürlich sortierte Paragraphennummern. Die Buchauswahl wechselt zwischen Büchern. Die globale Suche durchsucht sichtbare Paragraphen und Unterparagraphen.

Der eigene Paragrapheneditor enthält Kapitel, Nummer, Titel, Grundtext und nummerierte Absätze mit optionalen Zwischenüberschriften. Absätze lassen sich umsortieren. Optional können Geldstrafe oder Gebühr als fester Betrag oder Betragsrahmen in RP-Dollar eingetragen werden. Eine Vorschau zeigt die spätere Darstellung.

Änderungen werden als Entwurf mit Begründung gespeichert. Veröffentlichung erfordert ein eigenes Recht und kann sofort oder mit zukünftigem Gültigkeitsdatum erfolgen. Text, Absätze und Beträge gehören gemeinsam zur Fassung und bleiben in der Historie erhalten. Aufhebungen sind ausdrücklich sichtbar. Parallele Änderungen werden über Revisionen abgefangen. Inhalte werden als Text ausgegeben, nicht als ausführbares HTML.

## Technisches

API: `/api/laws/index.php` im CAD und `/api/social/laws.php` in Social. Keine automatische Kontoverknüpfung/SSO. Redaktion benötigt eine gültige CAD-Sitzung.

Migrationen: `0023_laws.sql`, `0024_laws_public.sql`, `0026_laws_structure.sql`, `0027_laws_app_grants.sql`. Die letzte Migration übernimmt bestehende Buchfreigaben mit ihren jeweils stärksten Rechten pro Behörde in `kdd_law_app_grants`; diese gelten anschließend für die gesamte App.

Die unabhängig gebauten Frontends enthalten identische `LawsApp.vue` und `LawsVersion.vue` in `social/src` und `frontend/src/components/laws`. `social/tests/verify.sh` prüft die Übereinstimmung und führt Integrations-, Rechte- und Browserprüfungen in einer wegwerfbaren Installation aus. Testkonten werden niemals in Produktion angelegt.

## CAD-Desktop-Einbettung

Die Gesetze besitzen einen inneren Abstand von 20 Pixeln und passen ihr Layout per Container Query an die tatsächliche Fensterbreite an. Social öffnet über `social-platform` ein internes Desktop-Fenster mit der konfigurierten Social-Adresse. Die Social-Nginx-Vorlage erlaubt als zusätzlichen Frame-Ursprung ausschließlich `CAD_FRAME_ORIGIN` (in den Compose-Dateien die CAD-Domain). Eine bestehende Social-Sitzung wird bei zulässigen Browser-Cookie-Einstellungen weiterverwendet; eine automatische Übernahme der CAD-Identität/SSO ist damit nicht implementiert.
