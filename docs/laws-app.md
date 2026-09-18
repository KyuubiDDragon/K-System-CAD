# Gesetze-App

## Einstieg

Im CAD-Desktop öffnet **Gesetze** (`/laws`) ausschließlich die Lese-App. **Gesetze-Redaktion** (`/laws-editor`) öffnet die getrennte Redaktion; der Desktop-Eintrag wird für passende System-/Gesetzerollen angezeigt und die API prüft zusätzlich die App-Zuständigkeit. In Social bleibt `#/laws` die öffentliche Lese-App. Veröffentlichte Fassungen sind standardmäßig ohne Anmeldung lesbar. Die Systemverwaltung kann Gastzugang und Modul unabhängig konfigurieren.

## Zuständigkeit

Das System-CAD mit aktuellem Systemrecht behält vollständigen Zugriff. In der Redaktion unter **Zugriffe und Einstellungen → Community-Verwaltung → Zugriff auf die gesamte Gesetze-App** vergibt es die App-Zuständigkeit an Behörden: Entwürfe lesen, Bearbeiten, Veröffentlichen und Außer Kraft setzen. Diese Freigabe gilt für alle vorhandenen und zukünftigen Gesetzbücher und aktiviert die CAD-Funktion `laws`.

Mitarbeiter benötigen zusätzlich die passenden CAD-Rollenrechte (`READ_LAWS_DRAFTS`, `WRITE_LAWS_DRAFTS`, `WRITE_LAWS_PUBLICATION`, `WRITE_LAWS_REPEAL`). Die API prüft aktuelle Rollen, App-Freigabe und aktivierte Funktion bei jeder Anfrage. Social-Rechte erlauben keine Redaktion. Redakteure können keine App-Zugriffe verteilen.

Gesetzbücher und Paragraphen sind gemeinschaftliche Inhalte ohne Behördenzuordnung. Autor und Herausgeber bleiben an den Fassungen zur Nachvollziehbarkeit gespeichert.

## Erstellen und lesen

Über **Neues Gesetzbuch** legen Redakteure Bücher an, optional anhand der fünf Titel-/Kapitelvorlagen. Es werden keine Musterregeln automatisch veröffentlicht. Die Lese-App startet mit Gesetzbuchkarten. Erst nach Auswahl erscheinen links das Inhaltsverzeichnis und rechts der ausgewählte Paragraph. Über „Alle Gesetzbücher“ geht es zur Auswahl zurück. Auch Systemnutzer erhalten hier weder Entwürfe noch Bearbeitungsaktionen; `view=reader` unterdrückt Entwürfe serverseitig in Buchabruf und Suche. Die globale Suche durchsucht sichtbare Paragraphen und Unterparagraphen.

Der eigene Paragrapheneditor enthält Kapitel, Nummer, Titel, Grundtext und nummerierte Absätze mit optionalen Zwischenüberschriften. Absätze lassen sich umsortieren. Optional können Geldstrafe oder Gebühr als fester Betrag oder Betragsrahmen in Dollar eingetragen werden. Eine Vorschau zeigt die spätere Darstellung.

Änderungen werden als Entwurf mit Begründung gespeichert. Veröffentlichung erfordert ein eigenes Recht und kann sofort oder mit zukünftigem Gültigkeitsdatum erfolgen. Text, Absätze und Beträge gehören gemeinsam zur Fassung und bleiben in der Historie erhalten. Aufhebungen sind ausdrücklich sichtbar. Parallele Änderungen werden über Revisionen abgefangen. Inhalte werden als Text ausgegeben, nicht als ausführbares HTML.

## Technisches

API: `/api/laws/index.php` im CAD und `/api/social/laws.php` in Social. Keine automatische Kontoverknüpfung/SSO. Redaktion benötigt eine gültige CAD-Sitzung.

Migrationen: `0023_laws.sql`, `0024_laws_public.sql`, `0026_laws_structure.sql`, `0027_laws_app_grants.sql`. Die letzte Migration übernimmt bestehende Buchfreigaben mit ihren jeweils stärksten Rechten pro Behörde in `kdd_law_app_grants`; diese gelten anschließend für die gesamte App.

Die unabhängig gebauten Frontends enthalten identische `LawsApp.vue`, `LawsReader.vue` und `LawsVersion.vue` in `social/src` und `frontend/src/components/laws`. `social/tests/verify.sh` prüft die Übereinstimmung und führt Integrations-, Rechte- und Browserprüfungen in einer wegwerfbaren Installation aus. Testkonten werden niemals in Produktion angelegt.

## CAD-Desktop-Einbettung

Die Gesetze besitzen einen inneren Abstand von 20 Pixeln und passen ihr Layout per Container Query an die tatsächliche Fensterbreite an. Social öffnet über `social-platform` ein internes Desktop-Fenster mit der konfigurierten Social-Adresse. Die Social-Nginx-Vorlage erlaubt als zusätzlichen Frame-Ursprung ausschließlich `CAD_FRAME_ORIGIN` (in den Compose-Dateien die CAD-Domain). Eine bestehende Social-Sitzung wird bei zulässigen Browser-Cookie-Einstellungen weiterverwendet; eine automatische Übernahme der CAD-Identität/SSO ist damit nicht implementiert.

## Gestaltungsprüfung

Mobbin-Referenzen:
- [Mintlify – Dokumentation](https://mobbin.com/screens/731c0653-266e-4c03-aae6-df4d07631ebd): ruhiger Textbereich, klarer Navigationszustand, Gliederung.
- [Plain – Artikelredaktion](https://mobbin.com/screens/94e93db2-c0d6-4192-83e8-6342c5a245df): getrennte redaktionelle Oberfläche.
- [GitBook – Editor und Vorschau](https://mobbin.com/screens/9ce94944-1ecc-4780-b562-333db92e0936): explizite Trennung zwischen Lesen/Vorschau und Redaktion.

Befund: Die vorherige Ansicht vermischte Recherche, Inhaltserstellung und Rechteverwaltung. Die neue Struktur trennt diese Aufgaben in zwei Anwendungen. In der Redaktion liegen Inhalte sowie Zugriffe/Einstellungen zusätzlich in getrennten Bereichen. Keine Markenelemente oder Screenshots aus Mobbin werden ausgeliefert. Automatisch erzeugte Oberflächentexte und Betragsangaben der Gesetze-App enthalten keine RP-Bezeichnungen. Bereits gespeicherte individuelle Gesetzestexte werden nicht umgeschrieben.

## Bedienung und Veröffentlichung von Büchern

Neue Gesetzbücher starten unveröffentlicht. Erst mit Veröffentlichungsrecht wird das gesamte Buch öffentlich geschaltet; einzelne Paragraphen brauchen weiterhin ihre eigene Veröffentlichung. Zurückziehen entfernt das Buch einschließlich Suche und öffentlich zugänglicher Historie. Bestehende Bücher behalten bei Migration 0028 ihren öffentlichen Status.

Der Leser speichert Buch und Paragraph in der Adresse, unterstützt Neuladen sowie Browser-Zurück/Vorwärts und weiterhin alte Paragraphenlinks. Ungespeicherte Paragraphenänderungen werden beim Abbrechen, Seitenwechsel, Neuladen, Schließen des Desktop-Fensters und Verlassen des Desktop-Modus abgefragt. Entwurf verwerfen benötigt eine Bestätigung.
