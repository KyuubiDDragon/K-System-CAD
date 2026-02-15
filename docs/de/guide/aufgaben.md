# Aufgabenlisten & Aufgabenverwaltung

Aufgabenverwaltungssystem mit hierarchischen Aufgabenlisten, Unteraufgaben, Benutzerzuweisungen, Prioritäten und Fälligkeitsdaten.

## Überblick

Funktionen:
- Hierarchische Organisation (Ordner → Listen → Aufgaben → Unteraufgaben)
- Benutzerzuweisungen (mehrere Benutzer pro Aufgabe)
- Fälligkeitsdaten mit visuellen Warnungen
- Prioritätsstufen (niedrig, mittel, hoch)
- Unteraufgaben-Checklisten mit Fortschrittsverfolgung
- Abschlussverfolgung mit Filterung
- Inline-Bearbeitung überall

**Erforderliche Berechtigung:** `READ_TODO` (Ansicht), `WRITE_TODO` (Erstellen/Bearbeiten)

## Zugriff auf die Aufgabenverwaltung

**Route:** `/todo`

**Zugriffsmöglichkeiten:**
- Desktop: Doppelklick auf **Todo**-Symbol
- Menü: Startmenü → Produktivität → Aufgabenlisten
- Oder direkt zu `/todo` navigieren

## Oberflächenlayout

Die Aufgabenoberfläche hat ein **dreispaltiges Layout**:

### Spalte 1: Listenpanel (Links)

**Ordnergruppen:**
- Erweiterbare Ordnergruppen mit Unterlisten
- Klicken zum Erweitern/Zusammenklappen von Ordnern
- Doppelklick auf Ordnernamen zum Umbenennen

**Aktionen:**
- **Ordner erstellen** - Klick auf + Schaltfläche in der Symbolleiste
- **Unterliste erstellen** - Tippen im Textfeld innerhalb des Ordners, Enter drücken
- **Unterliste löschen** - Klick auf Papierkorbsymbol neben Unterliste

**Organisation:**
- Listen automatisch alphabetisch sortiert
- Ordner können mehrere Unterlisten enthalten
- Zweistufige Hierarchie: Ordner → Unterlisten

### Spalte 2: Aufgabenpanel (Mitte)

**Listenkopf:**
- Zeigt den Namen der ausgewählten Liste an
- Doppelklick zum Umbenennen der Liste

**Aufgabenelemente:**
- Kontrollkästchen zum Markieren als erledigt/unerledigt
- Aufgabentitel
- Fälligkeitsdatum-Indikator (falls gesetzt)
- Prioritätsflagge-Symbol (farblich nach Wichtigkeit)
- Unteraufgaben-Fortschrittszähler (z.B. "2 / 5 erledigt")

**Symbolleiste:**
- "Erledigte anzeigen" Umschalter - Erledigte Aufgaben ausblenden/anzeigen

**Aufgabe hinzufügen:**
- Textfeld am unteren Rand der Liste
- Aufgabentitel eingeben und Enter drücken zum Erstellen

**Visuelle Indikatoren:**
- **Roter linker Rand** - Überfällig (Fälligkeitsdatum überschritten)
- **Oranger linker Rand** - Heute fällig
- **Durchgestrichen + grau** - Erledigt
- **Grüne Flagge** - Niedrige Priorität
- **Orange Flagge** - Mittlere Priorität
- **Rote Flagge** - Hohe Priorität

### Spalte 3: Aufgabendetails-Panel (Rechts)

**Wenn eine Aufgabe ausgewählt ist:**

**Aufgabentitel:**
- Doppelklick zum Inline-Bearbeiten

**Wichtigkeit (Priorität):**
- Dropdown-Auswahl
- Optionen: Niedrig (grün), Mittel (orange), Hoch (rot)

**Fälligkeitsdatum:**
- Datumsauswahl (Format TT.MM.JJJJ)
- Kann gelöscht/deaktiviert werden

**Zugewiesene Benutzer:**
- Mehrfachauswahl-Dropdown
- Zeigt Benutzer mit Aufgabenberechtigungen für diese Liste
- Mehrere Benutzer können zugewiesen werden
- Benutzer werden als Chips mit Entfernen (X) Schaltflächen angezeigt

**Beschreibung/Notizen:**
- Mehrzeiliges Textfeld
- Optionales Feld für zusätzliche Details

**Unteraufgaben (Teilaufgaben):**
- Kontrollkästchenliste von Unteraufgaben
- Neu hinzufügen: Textfeld am unteren Rand, Enter drücken
- Erledigung umschalten: Kontrollkästchen anklicken
- Umbenennen: Doppelklick auf Unteraufgabentext
- Löschen: Papierkorbsymbol anklicken
- "Erledigte Teilaufgaben anzeigen" umschalten zum Ausblenden/Anzeigen erledigter Elemente

**Aktionen:**
- **Aufgabe löschen** - Aufgabe dauerhaft löschen (mit Bestätigung)

## Aufgabenlisten erstellen

### Ordner erstellen

1. Klicken Sie auf die **+** Schaltfläche in der Listenpanel-Symbolleiste
2. Geben Sie den Ordnernamen im Dialog ein
3. Klicken Sie auf **Speichern**
4. Neuer Ordner erscheint in der Liste

### Unterliste erstellen

1. Erweitern Sie einen Ordner
2. Geben Sie den Namen der Unterliste im Textfeld innerhalb des Ordners ein
3. Drücken Sie **Enter**
4. Neue Unterliste wird unter diesem Ordner erstellt

### Liste löschen

1. Klicken Sie auf das Papierkorbsymbol neben dem Namen der Unterliste
2. Bestätigen Sie das Löschen im Dialog
3. Liste und alle ihre Aufgaben werden dauerhaft gelöscht

**Warnung**: Das Löschen einer Liste löscht alle darin enthaltenen Aufgaben. Dies kann nicht rückgängig gemacht werden.

## Aufgaben erstellen und verwalten

### Aufgabe erstellen

**Methode 1: Schnellerstellung**
1. Wählen Sie eine Liste aus dem Listenpanel
2. Geben Sie den Aufgabentitel im Textfeld am unteren Rand des Aufgabenpanels ein
3. Drücken Sie **Enter**
4. Aufgabe mit Standardeinstellungen erstellt (kein Fälligkeitsdatum, niedrige Priorität, nicht zugewiesen)

**Methode 2: Mit Details erstellen**
1. Erstellen Sie eine Aufgabe mit der Schnellmethode
2. Klicken Sie auf die Aufgabe, um sie auszuwählen
3. Bearbeiten Sie Details im rechten Panel (Priorität, Fälligkeitsdatum, Benutzer, Beschreibung, Unteraufgaben)

### Aufgabe bearbeiten

**Titel bearbeiten:**
- Doppelklick auf Aufgabentitel im mittleren Panel
- Neuen Namen eingeben
- Enter drücken oder wegklicken zum Speichern

**Priorität festlegen:**
1. Aufgabe auswählen
2. Im rechten Panel auf Wichtigkeit-Dropdown klicken
3. Wählen: Niedrig, Mittel oder Hoch

**Fälligkeitsdatum festlegen:**
1. Aufgabe auswählen
2. Im rechten Panel auf Fälligkeitsdatum-Auswahl klicken
3. Datum aus Kalender auswählen

**Benutzer zuweisen:**
1. Aufgabe auswählen
2. Im rechten Panel auf Dropdown "Zugewiesene Benutzer" klicken
3. Einen oder mehrere Benutzer auswählen
4. Benutzer erscheinen als Chips
5. X auf Chip klicken zum Entfernen der Zuweisung

**Beschreibung hinzufügen:**
1. Aufgabe auswählen
2. Im rechten Panel im Beschreibungsfeld eingeben
3. Änderungen werden automatisch gespeichert

### Aufgabe erledigen

**Als erledigt markieren:**
- Kontrollkästchen neben Aufgabe im mittleren Panel anklicken
- Aufgabe wird durchgestrichen und ausgegraut

**Als unerledigt markieren:**
- Kontrollkästchen erneut anklicken zum Deaktivieren
- Aufgabe kehrt zu normaler Darstellung zurück

**Erledigte filtern:**
- "Erledigte anzeigen" Umschalter in der Symbolleiste verwenden
- Blendet erledigte Aufgaben aus der Ansicht aus
- Erneut umschalten, um sie anzuzeigen

### Aufgabe löschen

1. Aufgabe auswählen
2. Zum unteren Rand des rechten Panels scrollen
3. Auf **Löschen** Schaltfläche klicken
4. Löschen bestätigen
5. Aufgabe dauerhaft entfernt

## Mit Unteraufgaben arbeiten

### Unteraufgabe hinzufügen

1. Wählen Sie eine Aufgabe aus
2. Im rechten Panel zum Abschnitt Unteraufgaben scrollen
3. Namen der Unteraufgabe im Textfeld eingeben
4. **Enter** drücken
5. Unteraufgabe erscheint in Liste mit Kontrollkästchen

### Unteraufgabe erledigen

- Kontrollkästchen neben Unteraufgabe anklicken
- Unteraufgabe wird durchgestrichen
- Fortschrittszähler aktualisiert sich (z.B. "1 / 5 erledigt")

### Unteraufgabe bearbeiten

- Doppelklick auf Unteraufgabentext
- Inline bearbeiten
- Enter drücken oder wegklicken zum Speichern

### Unteraufgabe löschen

1. Papierkorbsymbol neben Unteraufgabe anklicken
2. Löschen bestätigen
3. Unteraufgabe entfernt
4. Fortschrittszähler aktualisiert sich

### Erledigte Unteraufgaben anzeigen/ausblenden

- "Erledigte Teilaufgaben anzeigen" Umschalter im Abschnitt Unteraufgaben verwenden
- Blendet erledigte Unteraufgaben aus
- Fortschrittszähler zeigt weiterhin alle (erledigte und unerledigte)

## Sortierung und Organisation

### Aufgabensortierung

Aufgaben werden automatisch sortiert nach:
1. **Erledigungsstatus** - Unerledigte zuerst, erledigte zuletzt
2. **Fälligkeitsdatum** - Am zeitnächsten fällig zuerst (überfällige ganz oben)
3. **Wichtigkeit** - Hoch → Mittel → Niedrig

Diese Sortierung kann nicht angepasst werden.

### Listensortierung

- Ordner und Unterlisten alphabetisch sortiert
- Automatische Sortierung, kann nicht manuell neu geordnet werden

## Visuelle Indikatoren

### Fälligkeitsdatum-Warnungen

**Überfällige Aufgaben:**
- Roter linker Rand
- Fälligkeitsdatum in rot angezeigt

**Heute fällig:**
- Oranger linker Rand
- Fälligkeitsdatum in orange angezeigt

**Zukünftige Fälligkeitsdaten:**
- Kein spezieller Rand
- Datum in normaler Farbe angezeigt

### Prioritätsindikatoren

**Hohe Priorität:**
- Rotes Flaggensymbol
- Flagge erscheint neben Aufgabentitel

**Mittlere Priorität:**
- Oranges Flaggensymbol

**Niedrige Priorität:**
- Grünes Flaggensymbol

**Keine Priorität gesetzt:**
- Standardmäßig niedrige Priorität (grüne Flagge)

### Fortschrittsindikatoren

**Unteraufgaben-Fortschritt:**
- Angezeigt als "X / Y erledigt" bei Aufgabe
- Beispiel: "2 / 5 erledigt" bedeutet 2 von 5 Unteraufgaben erledigt
- Aktualisiert sich automatisch, wenn Unteraufgaben umgeschaltet werden

## Inline-Bearbeitung

Die meisten Elemente können inline mit **Doppelklick** bearbeitet werden:

**Bearbeitbare Elemente:**
- Ordnernamen
- Listennamen
- Aufgabentitel
- Unteraufgabentext

**Wie zu bearbeiten:**
1. Doppelklick auf den Text
2. Text wird zu bearbeitbarem Eingabefeld
3. Neuen Wert eingeben
4. Enter drücken oder wegklicken zum Speichern
5. Änderungen werden sofort aktualisiert

## Berechtigungssystem

**Leseberechtigung** (`READ_TODO`):
- Aufgabenlisten anzeigen
- Aufgaben und Unteraufgaben anzeigen
- Zugewiesene Benutzer anzeigen

**Schreibberechtigung** (`WRITE_TODO`):
- Ordner und Listen erstellen
- Aufgaben und Unteraufgaben erstellen
- Alle Aufgabendetails bearbeiten
- Aufgaben und Listen löschen
- Benutzer zuweisen

**Benutzerzuweisung:**
- Nur Benutzer mit Aufgabenberechtigungen für eine bestimmte Liste können zugewiesen werden
- Backend filtert verfügbare Benutzer über `getUsersWithTodoPermissions` Endpunkt

## Deep Linking

Sie können direkt zu einer bestimmten Aufgabe verlinken:

**URL-Format:**
```
/todo?id=123
```

**Verhalten:**
- Öffnet Aufgabenansicht
- Wählt die angegebene Aufgabe aus
- Zeigt Aufgabendetails im rechten Panel

## Tipps & Best Practices

**Aufgaben organisieren:**
- Erstellen Sie Ordner für Projekte oder Kategorien
- Verwenden Sie Unterlisten für verschiedene Bereiche innerhalb eines Projekts
- Halten Sie Listennamen kurz und klar

**Prioritäten verwenden:**
- Hoch (rot) - Dringend, muss heute erledigt werden
- Mittel (orange) - Wichtig, diese Woche erledigen
- Niedrig (grün) - Kann warten, erledigen wenn Zeit vorhanden

**Fälligkeitsdaten festlegen:**
- Setzen Sie immer Fälligkeitsdaten für zeitkritische Aufgaben
- Nutzen Sie visuelle Warnungen, um überfällige Elemente schnell zu erkennen
- Heutiges Datum zeigt orangen Rand für einfaches Scannen

**Unteraufgaben verwenden:**
- Teilen Sie komplexe Aufgaben in kleinere Unteraufgaben auf
- Haken Sie Unteraufgaben ab, während Sie sie erledigen
- Verfolgen Sie den Fortschritt mit Zähler (X / Y erledigt)
- Blenden Sie erledigte Unteraufgaben aus, um Unordnung zu reduzieren

**Benutzer zuweisen:**
- Weisen Sie Aufgaben bestimmten Teammitgliedern zu
- Mehrere Personen können kollaborativen Aufgaben zugewiesen werden
- Zugewiesene können Aufgaben in ihren Listen sehen

**Aufgaben erledigen:**
- Markieren Sie Aufgaben als erledigt, wenn sie fertig sind
- Verwenden Sie "Erledigte anzeigen" Umschalter zum Ausblenden erledigter Elemente
- Überprüfen Sie erledigte Aufgaben regelmäßig
- Erledigte Aufgaben können bei Wiedereröffnung deaktiviert werden

## Keyboard Shortcuts

**When creating todo/subtask:**
- **Enter** - Save and create item

**In inline edit mode:**
- **Enter** - Save changes
- **Esc** - Cancel edit

## Fehlerbehebung

### Liste kann nicht erstellt werden

**Problem**: Neue Listen-Schaltfläche funktioniert nicht

**Lösungen:**
1. Überprüfen Sie, ob Sie die `WRITE_TODO` Berechtigung haben
2. Verifizieren Sie, dass die Aufgabenfunktion für Ihre Organisation aktiviert ist
3. Seite aktualisieren
4. Versuchen Sie, Unterliste innerhalb eines vorhandenen Ordners zu erstellen

### Aufgabe wird nicht gespeichert

**Problem**: Bearbeitete Aufgabe speichert Änderungen nicht

**Lösungen:**
1. Internetverbindung überprüfen
2. Nach Fehlermeldungen suchen
3. Versuchen Sie, Seite zu aktualisieren und erneut zu bearbeiten
4. Überprüfen Sie, ob Sie Schreibberechtigung haben

### Benutzer kann nicht zugewiesen werden

**Problem**: Benutzer erscheint nicht im Zuweisungs-Dropdown

**Lösungen:**
1. Überprüfen Sie, ob Benutzer Aufgabenberechtigungen hat
2. Prüfen Sie, ob Benutzer zur richtigen Liste gehört
3. Seite aktualisieren, um Benutzer neu zu laden
4. Administrator kontaktieren, wenn Benutzer Zugriff haben sollte

### Unteraufgaben werden nicht angezeigt

**Problem**: Erstellte Unteraufgaben verschwinden

**Lösungen:**
1. Prüfen Sie, ob "Erledigte Teilaufgaben anzeigen" Umschalter sie nicht ausblendet
2. Überprüfen Sie, ob Unteraufgabe gespeichert wurde (auf Bestätigung prüfen)
3. Seite aktualisieren, um Daten neu zu laden
4. Prüfen Sie, ob Unteraufgabe versehentlich gelöscht wurde

### Fälligkeitsdatum-Warnungen werden nicht angezeigt

**Problem**: Überfällige Aufgaben haben keinen roten Rand

**Lösungen:**
1. Überprüfen Sie, ob Fälligkeitsdatum tatsächlich in der Vergangenheit liegt
2. Prüfen Sie, ob Datumsformat korrekt ist (TT.MM.JJJJ)
3. Seite aktualisieren
4. Browser-Cache leeren

## Verwandte Dokumentation

- [Erste Schritte](/guide/getting-started) - Lernen Sie die Grundlagen von K-Systems
- [Kalender](/guide/calendar) - Kalenderereignisse und Terminplanung
- [Nachrichten](/guide/messages) - Teamkommunikation

---

**Zuletzt aktualisiert:** 2025-10-01
**Version:** 2.0.0 (Korrigiert entsprechend der tatsächlichen Implementierung)
