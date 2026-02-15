# Kalender & Termine

Das Kalendersystem ermöglicht es Ihnen, Termine zu planen, Teilnehmer zuzuweisen und Team-Kalender mit Kalendergruppen zu organisieren.

## Übersicht

Funktionen:
- Terminplanung mit Datum/Uhrzeit
- Benutzerzuweisungen (Teilnehmer)
- Kalendergruppen mit Mitgliederverwaltung
- Grundlegende Wiederholungstermine (täglich, monatlich)
- Datenschutzeinstellungen (öffentlich/privat)
- Farbcodierte Termine
- Drag-and-Drop-Terminverschiebung
- Terminfilterung nach Teilnehmern

**Erforderliche Berechtigung:** `READ_CALENDAR` (ansehen), `WRITE_CALENDAR` (erstellen/bearbeiten)

## Zugriff auf Kalender

**Route:** `/calendar`

**Zugriffsmöglichkeiten:**
- Desktop: Doppelklick auf **Kalender**-Symbol
- Menü: Startmenü -> Kommunikation -> Kalender
- Oder direkt zu `/calendar` navigieren

## Kalenderansicht

### Monatsansicht

Der Kalender wird **nur in Monatsansicht** angezeigt. Diese Ansicht zeigt:
- Vollständiges Monatsraster
- Termine in Tageszellen angezeigt (bis zu 5 sichtbar)
- Farbcodierte Termine
- "Mehr anzeigen"-Link bei mehr als 5 Terminen pro Tag

**Hinweis**: Wochen-, Tages- und Agendaansichten sind in der aktuellen Implementierung nicht verfügbar.

### Navigation

- **Vorheriger/Nächster Monat**: Pfeiltasten in Symbolleiste verwenden
- **Heute**: Auf "Heute"-Schaltfläche klicken, um zum aktuellen Monat zu springen
- **Datumsauswahl**: Auf Monat/Jahr klicken, um spezifisches Datum auszuwählen

## Termine erstellen

### Schnellerfassung

1. Auf eine leere Tageszelle klicken
2. Der neue Termin-Dialog öffnet sich mit vorausgefüllten Start-/Enddaten
3. Termindetails eingeben
4. Auf **Erstellen** klicken

**Zeitrundung**: Beim Erstellen von Terminen werden Minuten auf 15-Minuten-Intervalle gerundet (0, 15, 30, 45).

### Neues Termin-Formular

Auf **+ Neuer Termin** klicken oder auf Tageszelle klicken, um das Terminerstellungsformular zu öffnen:

#### **Grundinformationen**

- **Titel** (erforderlich): Terminname
- **Untertitel**: Kurzbeschreibung
- **Beschreibung**: Vollständige Termindetails (unterstützt mehrere Zeilen)

#### **Datum & Uhrzeit**

- **Startdatum/-zeit**: Wann der Termin beginnt
- **Enddatum/-zeit**: Wann der Termin endet
- Verwendet Datetime-Picker mit Datums- und Zeitauswahl

#### **Teilnehmer**

- Benutzer aus Dropdown suchen und auswählen
- Mehrere Teilnehmer können zugewiesen werden
- Ausgewählte Benutzer erscheinen als Chips
- Alle zugewiesenen Benutzer können den Termin sehen

**Hinweis**: Das Zuweisen von Teilnehmern oder einer Gruppe markiert den Termin automatisch als privat.

#### **Kalendergruppe**

- Termin einer Kalendergruppe zuweisen (optional)
- Nur Gruppen, denen Sie angehören, erscheinen in der Liste
- Gruppentermine sind für alle Gruppenmitglieder sichtbar
- Leer lassen für persönlichen Termin

**Hinweis**: Das Zuweisen zu einer Gruppe markiert den Termin automatisch als privat.

#### **Wiederholungstermin**

Grundlegende Wiederholungsoptionen:
- **Keine**: Einmaliger Termin (Standard)
- **Täglich**: Termin wiederholt sich jeden Tag
- **Monatlich**: Termin wiederholt sich am selben Datum jeden Monat

**Einschränkungen**: Wöchentliche und jährliche Wiederholungen sind nicht verfügbar.

#### **Datenschutz**

- **Öffentlich**: Termin für alle sichtbar (Standard für persönliche Termine ohne Teilnehmer)
- **Privat**: Termin nur für Ersteller, Teilnehmer und Gruppenmitglieder sichtbar
  - Automatisch aktiviert, wenn Teilnehmer oder Gruppe zugewiesen
  - Schlosssymbol auf privaten Terminen angezeigt

#### **Farbe**

Terminfarbe zur visuellen Identifikation wählen:
- Vordefinierte Muster: Grün (#008000), Orange (#FFA500), Rot (#FF0000)
- Benutzerdefinierter Farbwähler verfügbar
- Termine werden in ausgewählter Farbe im Kalender angezeigt

### Termin speichern

Auf **Erstellen** klicken, um den Termin zu speichern. Der Kalender aktualisiert sich automatisch.

## Termine verwalten

### Termindetails anzeigen

**Doppelklick auf einen Termin**, um die Detailansicht mit vollständigen Informationen zu öffnen:
- Titel, Untertitel, Beschreibung
- Start- und Enddatum/-zeit
- Zugewiesene Teilnehmer
- Kalendergruppe (falls vorhanden)
- Wiederholungsmuster
- Datenschutzstatus
- Farbe

### Termin bearbeiten

1. Doppelklick auf Termin, um Details zu öffnen
2. Beliebige Felder ändern
3. Auf **Speichern** klicken, um zu aktualisieren

**Berechtigungen**: Sie können Termine bearbeiten, die Sie erstellt haben, oder Termine in Gruppen, in denen Sie Bearbeitungsberechtigung haben.

### Termin löschen

1. Termindetails öffnen
2. Auf **Löschen**-Schaltfläche klicken
3. Löschung im Dialog bestätigen
4. Termin wird dauerhaft entfernt

**Berechtigungen**: Sie können Termine löschen, die Sie erstellt haben, oder Termine in Gruppen, in denen Sie Löschberechtigung haben.

### Termin verschieben/neu planen

**Drag and Drop:**
1. Auf Termin klicken und halten
2. Auf neues Datum ziehen
3. Loslassen zum Ablegen
4. Terminuhrzeit bleibt erhalten, nur Datum ändert sich
5. Termin wird automatisch aktualisiert

**Oder Bearbeiten:**
1. Termindetails öffnen
2. Start-/Enddatum/-zeit ändern
3. Auf **Speichern** klicken

## Mehr Termine anzeigen

Wenn ein Tag mehr als 5 Termine hat:
1. Erste 5 Termine werden in der Tageszelle angezeigt
2. "Mehr anzeigen"-Link erscheint
3. Auf Link klicken, um Popup-Dialog zu öffnen
4. Dialog zeigt alle Termine für diesen Tag
5. Auf beliebigen Termin im Popup klicken, um Details anzuzeigen

## Terminfilterung

### Nach Teilnehmern filtern

Verwenden Sie die Filter-Seitenleiste, um nur Termine anzuzeigen, die bestimmten Benutzern zugewiesen sind:
1. Filterpanel öffnen (falls verfügbar)
2. Benutzer aus "Zugewiesen an"-Dropdown auswählen
3. Kalender zeigt nur Termine an, die zu ausgewählten Benutzern passen
4. Filter löschen, um alle Termine anzuzeigen

**Hinweis**: Andere Filteroptionen (Gruppen, Kategorien, Datumsbereiche) sind derzeit nicht verfügbar.

## Kalendergruppen

Kalendergruppen ermöglichen es Teams, Kalender zu teilen und bei Terminen zusammenzuarbeiten.

### Zugriff auf Gruppenverwaltung

1. Auf **Gruppen**-Schaltfläche in Kalender-Symbolleiste klicken (oder Seitenleisten-Toggle)
2. Gruppenverwaltungs-Panel öffnet sich
3. Alle Kalendergruppen anzeigen

### Gruppe erstellen

**Wenn Sie Berechtigung haben:**
1. Gruppenverwaltung öffnen
2. Auf **+ Neue Gruppe** klicken
3. Gruppendetails ausfüllen:
   - **Name** (erforderlich): Gruppenname
   - **Beschreibung**: Optionale Gruppenbeschreibung
   - **Farbe**: Aus 24 vordefinierten Farben zur visuellen Identifikation wählen
4. Auf **Speichern** klicken

### Gruppenmitglieder verwalten

**Mitglieder hinzufügen:**
1. Gruppe bearbeiten
2. Im Mitglieder-Bereich Benutzer aus Dropdown auswählen
3. Mehrere Benutzer können ausgewählt werden
4. Auf **Speichern** klicken

**Mitgliedsberechtigungen festlegen:**
Jedes Mitglied kann diese Berechtigungen haben:
- **Kann bearbeiten**: Mitglied kann Gruppentermine bearbeiten
- **Kann löschen**: Mitglied kann Gruppentermine löschen
- **Kann Mitglieder verwalten**: Mitglied kann andere Mitglieder hinzufügen/entfernen

**Mitglieder entfernen:**
1. Gruppe bearbeiten
2. Auf X bei Mitglieds-Chip klicken
3. Auf **Speichern** klicken

### Gruppe bearbeiten

1. Gruppenverwaltung öffnen
2. Auf Bearbeiten-Symbol neben Gruppennamen klicken
3. Name, Beschreibung, Farbe oder Mitglieder ändern
4. Auf **Speichern** klicken

### Gruppe löschen

1. Gruppenverwaltung öffnen
2. Auf Löschen-Symbol neben Gruppennamen klicken
3. Löschung bestätigen
4. Gruppe und Mitgliedschaft entfernt (Termine bleiben, aber nicht der Gruppe zugewiesen)

### Gruppen in Terminen verwenden

Beim Erstellen oder Bearbeiten eines Termins:
1. Gruppe aus "Kalendergruppe"-Dropdown auswählen
2. Alle Gruppenmitglieder sehen den Termin
3. Termin wird in Gruppenfarbe angezeigt (es sei denn, benutzerdefinierte Farbe gesetzt)
4. Termin automatisch als privat markiert

## Termin-Datenschutz

### Öffentliche Termine

- Standard für persönliche Termine ohne Teilnehmer
- Für alle Benutzer im System sichtbar
- Vollständige Details sichtbar

### Private Termine

- Nur sichtbar für:
  - Terminersteller
  - Zugewiesene Teilnehmer
  - Gruppenmitglieder (wenn Gruppe zugewiesen)
- Schlosssymbol im Kalender angezeigt
- Automatisch aktiviert, wenn:
  - Teilnehmer zugewiesen sind
  - Termin einer Kalendergruppe zugewiesen ist

### Datenschutz umschalten

1. Termindetails öffnen
2. "Privater Termin"-Kontrollkästchen umschalten
3. Auf **Speichern** klicken

**Hinweis**: Vertrauliche Datenschutzstufe ist nicht verfügbar.

## Wiederholungstermine

### Wiederholung einrichten

1. Beim Erstellen oder Bearbeiten eines Termins **Wiederholung**-Bereich erweitern
2. Wiederholungsmuster auswählen:
   - **Keine**: Einzeltermin (Standard)
   - **Täglich**: Wiederholt sich jeden Tag
   - **Monatlich**: Wiederholt sich am selben Datum jeden Monat
3. Auf **Speichern** klicken

**Einschränkungen**:
- Keine wöchentliche Wiederholungsoption
- Keine jährliche Wiederholungsoption
- Keine Enddatum- oder Vorkommensgrenzeinstellungen
- Wiederholungstermine wiederholen sich unbegrenzt

### Wiederholungstermine bearbeiten

Wenn Sie einen Wiederholungstermin bearbeiten:
- Änderungen gelten für alle Vorkommen
- Keine Option zum Bearbeiten eines einzelnen Vorkommens

### Wiederholungstermine löschen

Wenn Sie einen Wiederholungstermin löschen:
- Gesamte Serie wird gelöscht
- Keine Option zum Löschen eines einzelnen Vorkommens

## Berechtigungssystem

Kalender respektiert das K-Systems-Berechtigungssystem:

### Kalender ansehen
- Erfordert: `READ_CALENDAR`
- Alle Benutzer haben dies normalerweise standardmäßig

### Termine erstellen
- Erfordert: `WRITE_CALENDAR`
- Kann persönliche Termine und Gruppentermine erstellen (wenn Gruppenmitglied)

### Termine bearbeiten
- Muss Terminersteller sein ODER
- Muss `can_edit`-Berechtigung in der Gruppe des Termins haben

### Termine löschen
- Muss Terminersteller sein ODER
- Muss `can_delete`-Berechtigung in der Gruppe des Termins haben

### Gruppen verwalten
- Gruppenersteller kann immer verwalten
- Mitglieder mit `can_manage_members`-Berechtigung können Mitglieder hinzufügen/entfernen

## Tipps & Best Practices

**Termine erstellen:**
- Verwenden Sie klare, beschreibende Titel
- Fügen Sie Teilnehmer hinzu, um alle zu informieren
- Verwenden Sie Kalendergruppen für Team-Termine
- Wählen Sie aussagekräftige Farben zur schnellen visuellen Identifikation
- Fügen Sie vollständige Beschreibung für komplexe Termine hinzu

**Kalender verwalten:**
- Verwenden Sie Drag-and-Drop für schnelles Umplanen
- Weisen Sie Termine Gruppen zu für Team-Sichtbarkeit
- Markieren Sie sensible Termine als privat
- Verwenden Sie Wiederholungstermine für regelmäßige Meetings (täglich/monatlich)

**Gruppen verwenden:**
- Erstellen Sie Gruppen für Abteilungen, Teams oder Projekte
- Setzen Sie angemessene Mitgliedsberechtigungen
- Verwenden Sie Gruppenfarben, um verschiedene Teams visuell zu unterscheiden
- Fügen Sie Beschreibungen hinzu, um Gruppenzweck zu klären

## Tastaturkürzel

Wenn Kalender fokussiert ist:
- **Esc**: Geöffnete Dialoge schließen

## Fehlerbehebung

### Kann keinen Termin erstellen

**Problem**: Neuer Termin-Schaltfläche funktioniert nicht oder Formular speichert nicht

**Lösungen:**
1. Verifizieren Sie, dass Sie `WRITE_CALENDAR`-Berechtigung haben
2. Prüfen Sie, dass Kalenderfunktion für Ihre Authority aktiviert ist
3. Stellen Sie sicher, dass Startzeit vor Endzeit liegt
4. Füllen Sie Pflichtfeld aus (Titel)

### Termin wird nicht angezeigt

**Problem**: Erstellter Termin erscheint nicht im Kalender

**Lösungen:**
1. Prüfen Sie, ob Datumsfilter aktiv ist
2. Verifizieren Sie, dass Termin nicht in anderem Monat erstellt wurde
3. Löschen Sie alle Teilnehmerfilter
4. Seite aktualisieren

### Kann Termin nicht bearbeiten/löschen

**Problem**: Keine Bearbeiten/Löschen-Schaltflächen oder sie sind deaktiviert

**Lösungen:**
1. Verifizieren Sie, dass Sie den Termin erstellt haben
2. Falls Gruppentermin, prüfen Sie, dass Sie Bearbeiten/Löschen-Berechtigung in dieser Gruppe haben
3. Prüfen Sie, dass Sie `WRITE_CALENDAR`-Berechtigung haben

### Gruppe erscheint nicht

**Problem**: Erstellte Gruppe erscheint nicht in Dropdown

**Lösungen:**
1. Seite aktualisieren, um Gruppen neu zu laden
2. Verifizieren Sie, dass Sie Mitglied der Gruppe sind
3. Gruppenverwaltung prüfen, um zu bestätigen, dass Gruppe existiert

### Drag-and-Drop funktioniert nicht

**Problem**: Kann Termine nicht auf neue Daten ziehen

**Lösungen:**
1. Stellen Sie sicher, dass Sie Bearbeitungsberechtigung für den Termin haben
2. Versuchen Sie, länger zu klicken und zu halten, bevor Sie ziehen
3. Stellen Sie sicher, dass Sie auf eine gültige Datumszelle ziehen
4. Falls Problem weiterhin besteht, verwenden Sie Bearbeitungsformular zum Ändern des Datums

## Verwandte Dokumentation

- [[Einführung]] - Lernen Sie die Grundlagen von K-Systems
- [[Nachrichten]] - Internes Nachrichtensystem
- [[Mitarbeiterverwaltung]] - Verwaltung von Benutzern, die Terminen zugewiesen werden können
