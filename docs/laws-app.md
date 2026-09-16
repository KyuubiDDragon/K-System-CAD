# Gesetze-App

## Einstieg

- Im CAD-Desktop: **Gesetze**. Alternativ `/laws` auf der CAD-Domain.
- In Social: **Gesetze** in der Plattformauswahl bzw. auf der Anmeldeseite. `#/laws` öffnet die Leseansicht.
- CAD und Social zeigen dieselben Gesetzbücher. Redaktionelle Befugnisse kommen ausschließlich aus einer validierten CAD-Sitzung. Ein Social-Administrator wird dadurch nicht zum Gesetzgeber. Die separate Kontoverknüpfung/SSO ist nicht Bestandteil dieser Änderung.

## Einrichtung durch die technische CAD-Administration

1. Gesetze öffnen und unter **Community-Verwaltung** ein Gesetzbuch anlegen.
2. Im Gesetzbuch **Gesetzbuch und Fraktionszuständigkeiten** öffnen. Für jede gewünschte Fraktion Entwürfe lesen, Bearbeiten, Veröffentlichen und/oder Außer Kraft setzen freigeben. Die Freigabe aktiviert auch die CAD-Funktion `laws` für diese Fraktion.
3. In der bestehenden CAD-Rollenverwaltung die passenden Rechte an Mitarbeiterrollen vergeben: `READ_LAWS_DRAFTS`, `WRITE_LAWS_DRAFTS`, `WRITE_LAWS_PUBLICATION`, `WRITE_LAWS_REPEAL`.

Eine Rolle allein reicht nicht: Jeder schreibende Zugriff erfordert die Fraktionsfreigabe für genau dieses Gesetzbuch, die aktivierte Funktion und das passende aktuelle Rollenrecht. Eine nicht zuständige Fraktion kann sich über ihre Rollenverwaltung keine Gesetzgebungsbefugnis verschaffen. Rechte werden pro Anfrage aus der Datenbank gelesen.

Veröffentlichte Gesetze sind für angemeldete CAD- bzw. aktive Social-Nutzer lesbar. Der separate Schalter **Veröffentlichte Gesetze ohne Anmeldung lesbar** gibt sie zusätzlich Gästen frei; Social-Feed-Gastzugang und Gesetze-Gastzugang sind unabhängig. Gastlesen ist bei Installation ausgeschaltet. Die App lässt sich zentral deaktivieren; Inhalte werden dabei erhalten, öffentliche Direktzugriffe gesperrt, die technische Verwaltung bleibt erreichbar.

## Fassungen

Ein Paragraph besitzt eine feste Nummer innerhalb seines Gesetzbuchs, Kapitel, Titel und Text. Bearbeitungen sind Entwürfe mit Begründung. Sie ersetzen die geltende Fassung erst nach gesonderter Veröffentlichung. Mit einem zukünftigen Gültigkeitsdatum bleibt die bisherige Fassung zunächst in Kraft, die bereits veröffentlichte zukünftige Fassung ist als solche sichtbar. Pro Paragraph ist eine zukünftige Veröffentlichung gleichzeitig möglich.

Veröffentlichte Textfassungen können nicht überschrieben oder gelöscht werden. Eine Aufhebung hinterlegt Datum, Begründung und Akteur, ohne eine ältere Fassung wieder in Kraft zu setzen. Solange eine zukünftige Fassung aussteht, ist eine Aufhebung gesperrt. Korrekturen erfolgen über eine neue Fassung. Ein Änderungsprotokoll erfasst die maßgeblichen Aktionen. Parallele Bearbeitungen werden durch Versionsnummern abgesichert und veraltete Schreibversuche abgelehnt.

Die Suche durchsucht die sichtbaren Paragraphen des gewählten Gesetzbuchs. Direktlinks und frühere Fassungen beachten dieselben Zugriffsregeln. Texte werden als Text ausgegeben, nicht als ausführbares HTML.

## Absicherung der Rollenverwaltung

Erstellen, Ändern und Löschen von Rollen prüfen aktuelle Rechte statt nur den JWT-Inhalt. Eine fremde Mandantenrolle darf nicht bearbeitet werden. Normale Rollenadministratoren dürfen keine Rechte vergeben, die sie selbst nicht besitzen, und keine höher berechtigten Rollen verändern. Kategoriezuweisungen bleiben ebenfalls im aktuellen Mandanten. Gelöschte Rollen werden beim Laden der Rechte ignoriert; unbekannte Aktionen werden abgewiesen.

## Technik und Prüfung

Migration: `0023_laws.sql`. Eigenständige Tabellen `kdd_law_*` bilden das gemeinsame Gesetzbuch ab; es gibt keine Kopie pro Mandant. Die Zuordnungstabelle begrenzt die redaktionellen Zuständigkeiten. API: `/api/laws/index.php` im CAD, `/api/social/laws.php` als Social-Einstieg mit dessen pfadgebundenem Sitzungscookie.

Die beiden unabhängig gebauten Frontends enthalten denselben Gesetze-Baustein unter `social/src/LawsApp.vue` und `frontend/src/components/laws/LawsApp.vue`. Bei Änderungen beide synchron halten; `social/tests/verify.sh` prüft die Übereinstimmung. Der CAD-Wrapper verwendet den konfigurierten CAD-API-Endpunkt.

`bash social/tests/verify.sh` baut eine wegwerfbare Installation aus dem vollständigen CAD-Grundschema, führt Social-API-Prüfungen, Gesetzes-/Rollenprüfungen und die Browserprüfungen aus. `backend/social/tests/laws.php` verweigert die Ausführung auf anderen Datenbanken als `social_test`. Testkonten und Testsitzungen werden ausschließlich in dieser temporären Installation erzeugt.
