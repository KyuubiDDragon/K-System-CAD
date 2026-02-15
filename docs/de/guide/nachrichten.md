# Interne Nachrichten

Das K-Systems-Nachrichtensystem bietet interne Kommunikation zwischen Benutzern und Gruppen mit organisatorischen Funktionen.

## Übersicht

Das Nachrichtensystem ermöglicht Ihnen:
- Nachrichten an Benutzer und Gruppen senden
- Nachrichten mit Ordnern organisieren
- Gelesen/Ungelesen-Status verfolgen
- Wichtige Nachrichten anheften
- Auf Nachrichten antworten und weiterleiten
- Persönliche Notizen zu Nachrichten hinzufügen

**Erforderliche Berechtigung:** `READ_MESSAGES`

## Zugriff auf Nachrichten

**Route:** `/message`

**Zugriffsmöglichkeiten:**
- Desktop: Doppelklick auf **Nachrichten**-Symbol
- Menü: Startmenü → Kommunikation → Nachrichten
- Oder direkt zu `/message` navigieren

## Oberflächenlayout

### Navigations-Seitenleiste

Die linke Seitenleiste hat vier Hauptansichten:

1. **Posteingang** - Empfangene Nachrichten (zeigt Anzahl ungelesener an)
2. **Gesendet** - Von Ihnen gesendete Nachrichten
3. **Ordner** - Verwaltung Ihrer Nachrichtenordner
4. **Papierkorb** - Gelöschte Nachrichten (können wiederhergestellt oder dauerhaft gelöscht werden)

### Kontext-Filter

Filtern Sie Nachrichten nach:
- **Persönlich** - Ihre persönlichen Nachrichten (zeigt Anzahl ungelesener im Posteingang)
- **Gruppen-Filter** - Nachrichten für Gruppen, denen Sie angehören (jede zeigt Anzahl ungelesener)

Klicken Sie auf die Filter-Schaltflächen, um nur Nachrichten für diesen Kontext anzuzeigen.

## Nachrichten senden

### Neue Nachricht verfassen

1. Klicken Sie auf **+ Neue Nachricht**
2. **Absender auswählen**:
   - Als Sie selbst senden (Persönlich)
   - Oder als Gruppe senden, der Sie angehören
3. **Empfänger auswählen**:
   - Tippen Sie, um nach Benutzern oder Gruppen zu suchen
   - Wählen Sie mehrere Empfänger aus
   - Jeder Empfänger erhält eine separate Nachricht
4. **Betreff eingeben**: Nachrichtentitel
5. **Nachricht verfassen**: Verwenden Sie den Rich-Text-Editor zum Formatieren Ihrer Nachricht
   - Fett, kursiv, unterstrichen
   - Überschriften, Listen
   - Links
   - Codeblöcke
6. **Optionale Ordnerzuweisung**:
   - Zu Absender-Ordner zuweisen (Ihre Organisation)
   - Zu Empfänger-Ordner zuweisen (Organisation des Empfängers)
7. Klicken Sie auf **Senden**

**Hinweis**: Bei Versand an mehrere Empfänger erhält jede Person eine individuelle Nachricht (keine Gruppenunterhaltung).

## Nachrichten lesen

### Nachricht öffnen

Im Posteingang, Gesendet oder Papierkorb:
1. Klicken Sie auf den Nachrichtentitel, um sie zu öffnen
2. Die Nachricht öffnet sich in Vollbild-Leseansicht

### Nachrichtenleseansicht

**Symbolleisten-Aktionen**:
- **Zurück** - Zurück zur Nachrichtenliste
- **Antworten** - Dem Absender antworten
- **Weiterleiten** - An neue Empfänger weiterleiten
- **Speichern** - Ordner-/Notizänderungen speichern

**Nachrichteninformationen**:
- Absendername (mit Gruppenbadge, wenn als Gruppe gesendet)
- Empfängername(n) (mit Gruppenbadge, wenn an Gruppe gesendet)
- Sendedatum und -uhrzeit

**Nachrichteninhalt**:
- Betreff in Symbolleiste angezeigt
- Vollständiger Nachrichtentext (formatiertes HTML)

**Zusätzliche Funktionen**:
- **Ordnerzuweisung**: Nachricht einem Ordner zuweisen
  - Im Posteingang/Papierkorb: Zu Empfänger-Ordner zuweisen
  - In Gesendet-Ansicht: Zu Absender-Ordner zuweisen
- **Persönliche Notizen**: Private Notizen zur Nachricht hinzufügen
  - Im Posteingang/Papierkorb: Empfängernotizen (Ihre Notizen)
  - In Gesendet-Ansicht: Absendernotizen (Ihre Notizen)
- **Nachricht anheften** (nur Posteingang): Wichtige Nachrichten oben im Posteingang halten
- **Löschen**: Nachricht in Papierkorb verschieben

**Automatisch als gelesen markieren**: Nachrichten werden automatisch als gelesen markiert, wenn Sie sie öffnen (nur Posteingang).

## Nachrichtenaktionen

### Im Posteingang

**Aus Nachrichtentabelle**:
- **Gelesen/Ungelesen umschalten**: Klicken Sie auf Augensymbol, um als gelesen/ungelesen zu markieren
- **Anheften/Lösen**: Klicken Sie auf Pinnnadel-Symbol, um Nachricht oben im Posteingang zu halten
- **Löschen**: Klicken Sie auf Papierkorbsymbol, um in Papierkorb zu verschieben

**Aus Leseansicht**:
- **Antworten**: Befüllt Absender und Betreff mit "Re: [Original-Betreff]", enthält zitierte Originalnachricht
- **Weiterleiten**: Neue Empfänger auswählen, Betreff vorbefüllt mit "Fwd: [Original-Betreff]", enthält zitierte Originalnachricht
- **Ordner zuweisen**: Ordner aus Dropdown wählen und auf Speichern klicken
- **Notizen hinzufügen**: Notizen im Notizfeld eingeben und auf Speichern klicken
- **Anheften/Lösen**: Anheftestatus umschalten
- **Löschen**: In Papierkorb verschieben

### In Gesendet-Ansicht

**Aus Nachrichtentabelle**:
- **Löschen**: In Papierkorb verschieben

**Aus Leseansicht**:
- **Weiterleiten**: An neue Empfänger weiterleiten
- **Ordner zuweisen**: Zu Absender-Ordner zuweisen
- **Notizen hinzufügen**: Absendernotizen hinzufügen
- **Löschen**: In Papierkorb verschieben

### In Papierkorb-Ansicht

**Aus Nachrichtentabelle**:
- **Wiederherstellen**: Nachricht zurück in Posteingang/Gesendet verschieben
- **Dauerhaft löschen**: Aus Datenbank entfernen (kann nicht rückgängig gemacht werden)

**Aus Leseansicht**:
- **Wiederherstellen**: Nachricht zurück in Posteingang/Gesendet
- **Dauerhaft löschen**: Dauerhaft entfernen

## Organisation mit Ordnern

### Ordner anzeigen

1. Klicken Sie auf **Ordner** in der Seitenleiste
2. Sehen Sie alle Ihre persönlichen und Gruppen-Ordner

### Ordner erstellen

1. Gehen Sie zur **Ordner**-Ansicht
2. Klicken Sie auf **+ Neuer Ordner**
3. Geben Sie Ordnernamen ein
4. Wählen Sie Eigentümer:
   - **Persönlich**: Ihr persönlicher Ordner
   - **Gruppe**: Ordner für eine bestimmte Gruppe (nur Gruppen, bei denen Sie Ordnerverwaltungsberechtigung haben)
5. Klicken Sie auf **Speichern**

### Ordner bearbeiten

1. Klicken Sie auf Bearbeiten-Symbol neben Ordnernamen
2. Ändern Sie Ordnernamen
3. Klicken Sie auf **Speichern**

**Hinweis**: Ordnereigentümer (persönlich vs. Gruppe) kann nach Erstellung nicht geändert werden.

### Ordner löschen

1. Klicken Sie auf Löschen-Symbol neben Ordnernamen
2. Bestätigen Sie Löschung

**Hinweis**: Das Löschen eines Ordners löscht keine Nachrichten, nur den Ordner selbst. Nachrichten zeigen keine Ordnerzuweisung mehr an.

### Nachrichten zu Ordnern zuweisen

**Aus Leseansicht**:
1. Öffnen Sie eine Nachricht
2. Wählen Sie einen Ordner aus dem Ordner-Dropdown
3. Klicken Sie auf **Speichern**

**Ordnerzuweisungskontext**:
- **Posteingang/Papierkorb**: Zu Empfänger-Ordnern zuweisen (Ihre Ordner)
- **Gesendet**: Zu Absender-Ordnern zuweisen (Ihre Ordner)

### Nach Ordner filtern

Im Posteingang, Gesendet oder Papierkorb:
1. Verwenden Sie das Ordner-Filter-Dropdown über der Nachrichtentabelle
2. Wählen Sie einen Ordner, um nur Nachrichten in diesem Ordner zu sehen
3. Wählen Sie "Alle", um alle Nachrichten zu sehen

## Nachrichtenstatus-Funktionen

### Gelesen/Ungelesen-Status

**Ungelesene Nachrichten**:
- Fett dargestellt in Nachrichtenliste
- Anzahl ungelesener auf Posteingang-Seitenleistenelement angezeigt
- Anzahl ungelesener für jeden Persönlich/Gruppen-Filter angezeigt

**Als gelesen markieren**:
- Automatisch markiert, wenn Sie eine Nachricht öffnen
- Manuell umschalten durch Klicken auf Augensymbol in Nachrichtentabelle

**Als ungelesen markieren**:
- Klicken Sie auf Augensymbol, um gelesene Nachricht als ungelesen zu markieren (nur Posteingang)

### Nachrichten anheften

**Nachricht anheften** (nur Posteingang):
- Aus Tabelle: Klicken Sie auf Pinnnadel-Symbol
- Aus Leseansicht: Klicken Sie auf Anheften-Schaltfläche

**Angeheftete Nachrichten**:
- Erscheinen oben im Posteingang
- Angeheftete Nachrichten zuerst nach neuesten sortiert
- Normale Nachrichten erscheinen unter angehefteten Nachrichten
- Pinnnadel-Symbol in Nachrichtentabelle angezeigt

**Nachricht lösen**:
- Klicken Sie erneut auf Pinnnadel-Symbol/-Schaltfläche

## Suchen und Filtern

### Nachrichten suchen

Verwenden Sie das Suchfeld, um Nachrichten zu finden nach:
- Betreff/Titel
- Nachrichteninhalt
- Absendername
- Empfängername

### Filter kombinieren

Sie können mehrere Filter kombinieren:
1. Wählen Sie Persönlich oder Gruppen-Kontext
2. Wählen Sie einen Ordner
3. Geben Sie Suchtext ein
4. Alle Filter werden zusammen angewendet

## Antworten und Weiterleiten

### Auf Nachricht antworten

1. Öffnen Sie eine Nachricht im Posteingang
2. Klicken Sie auf **Antworten**
3. Der Verfassen-Dialog öffnet sich mit:
   - Empfänger: Ursprünglicher Absender
   - Betreff: "Re: [Original-Betreff]"
   - Nachricht: Originalnachricht unten zitiert
4. Geben Sie Ihre Antwort über der zitierten Nachricht ein
5. Klicken Sie auf **Senden**

### Nachricht weiterleiten

1. Öffnen Sie eine beliebige Nachricht
2. Klicken Sie auf **Weiterleiten**
3. Der Verfassen-Dialog öffnet sich mit:
   - Empfänger: Leer (neue Empfänger auswählen)
   - Betreff: "Fwd: [Original-Betreff]"
   - Nachricht: Originalnachricht zitiert
4. Empfänger hinzufügen
5. Ihre Nachricht über dem zitierten Inhalt hinzufügen
6. Klicken Sie auf **Senden**

## Löschverwaltung

### Soft-Delete (In Papierkorb verschieben)

**Aus Posteingang oder Gesendet**:
- Klicken Sie auf Löschen-Symbol in Nachrichtentabelle
- Oder klicken Sie auf **Löschen** in Leseansicht
- Nachricht wird in Papierkorb-Ansicht verschoben

**Nachverfolgung gelöschter Nachrichten**:
- System verfolgt separat, ob Absender oder Empfänger die Nachricht gelöscht hat
- Löschen aus Posteingang setzt `deleted_by_recipient = true`
- Löschen aus Gesendet setzt `deleted_by_sender = true`
- Nachricht erscheint im Papierkorb für die Person, die sie gelöscht hat

### Aus Papierkorb wiederherstellen

1. Gehen Sie zur **Papierkorb**-Ansicht
2. Klicken Sie auf Wiederherstellen-Symbol in Nachrichtentabelle
3. Oder öffnen Sie Nachricht und klicken Sie auf **Wiederherstellen**
4. Nachricht kehrt zurück in Posteingang (wenn Sie Empfänger sind) oder Gesendet (wenn Sie Absender sind)

### Dauerhaft löschen

1. Gehen Sie zur **Papierkorb**-Ansicht
2. Klicken Sie auf Dauerhaft-Löschen-Symbol in Nachrichtentabelle
3. Oder öffnen Sie Nachricht und klicken Sie auf **Dauerhaft löschen**
4. Bestätigen Sie Löschung
5. Nachricht aus Datenbank entfernt

**Warnung**: Dauerhaftes Löschen kann nicht rückgängig gemacht werden.

## Gruppennachrichten-Funktionen

### Als Gruppe senden

Wenn Sie Gruppen angehören:
1. Im Verfassen-Dialog, wählen Sie Absender-Dropdown
2. Wählen Sie eine Gruppe statt "Persönlich"
3. Nachricht wird als von der Gruppe gesendet angezeigt
4. Gruppenbadge erscheint neben Absendernamen

### An Gruppe senden

1. Im Verfassen-Dialog, wählen Sie Empfänger
2. Geben Sie Gruppennamen ein, um zu suchen
3. Wählen Sie die Gruppe
4. Alle Gruppenmitglieder erhalten die Nachricht

### Gruppen-Ordner

Ordner für Gruppennutzung erstellen:
1. Gehen Sie zur **Ordner**-Ansicht
2. Klicken Sie auf **+ Neuer Ordner**
3. Wählen Sie eine Gruppe als Eigentümer (erfordert "can_manage_folders"-Berechtigung für diese Gruppe)
4. Alle Gruppenmitglieder können Nachrichten zu Gruppen-Ordnern zuweisen

### Gruppen-Filterung

- Jede Gruppe, der Sie angehören, erscheint als Filter-Schaltfläche
- Klicken Sie auf Gruppen-Filter, um nur Nachrichten für diesen Gruppen-Kontext zu sehen
- Anzahl ungelesener wird für jede Gruppe angezeigt

## Tipps & Best Practices

**Nachrichten organisieren**:
- Erstellen Sie Ordner für verschiedene Themen (Projekte, Wichtig, Archiv, etc.)
- Heften Sie dringende Nachrichten im Posteingang an
- Verwenden Sie Notizen, um Kontext oder Erinnerungen hinzuzufügen
- Räumen Sie regelmäßig den Papierkorb auf, um Speicherplatz freizugeben

**Nachrichten schreiben**:
- Verwenden Sie klare, beschreibende Betreffe
- Formatieren Sie Nachrichten mit Überschriften und Listen für bessere Lesbarkeit
- Antworten Sie, um Kontext zu behalten, anstatt neue Threads zu starten
- Prüfen Sie Empfängerliste vor dem Senden an mehrere Personen

**Posteingang verwalten**:
- Markieren Sie Nachrichten als ungelesen, wenn Sie später nachfassen müssen
- Heften Sie wichtige Nachrichten an, die Aufmerksamkeit benötigen
- Verwenden Sie Ordner-Filter, um sich auf bestimmte Themen zu konzentrieren
- Archivieren Sie in Ordnern, anstatt zu löschen, wenn möglich

## Fehlerbehebung

### Nachrichten erscheinen nicht

**Problem**: Nachricht gesendet, aber Empfänger sieht sie nicht

**Lösungen**:
1. Prüfen Sie, ob Nachricht in Ihrer Gesendet-Ansicht erscheint
2. Verifizieren Sie, dass Empfängername korrekt war
3. Bitten Sie Empfänger, Gruppen-Filter zu prüfen (wenn an Gruppe gesendet)
4. Seite aktualisieren

### Nachricht kann nicht gefunden werden

**Problem**: Suche nach Nachricht, kann sie aber nicht finden

**Lösungen**:
1. Prüfen Sie, ob sie in Papierkorb-Ansicht ist
2. Löschen Sie alle Filter (Persönlich/Gruppe, Ordner)
3. Verwenden Sie Suche, um nach Betreff oder Absender zu suchen
4. Prüfen Sie, ob versehentlich als gelesen markiert (wenn nach ungelesen gesucht)

### Ordner wird nicht angezeigt

**Problem**: Ordner erstellt, aber kann ihn nicht in Dropdown sehen

**Lösungen**:
1. Gehen Sie zur Ordner-Ansicht, um zu verifizieren, dass er erstellt wurde
2. Prüfen Sie, ob Ordner für richtigen Eigentümer ist (Persönlich vs. Gruppe)
3. Seite aktualisieren
4. Wenn Gruppen-Ordner, verifizieren Sie, dass Sie Ordnerverwaltungsberechtigung haben

### Kann nicht an Gruppe senden

**Problem**: Sehe Gruppe nicht in Empfängerliste

**Lösungen**:
1. Verifizieren Sie, dass Sie Mitglied dieser Gruppe sind
2. Prüfen Sie mit Administrator, ob Gruppe noch existiert
3. Seite aktualisieren, um Gruppenliste neu zu laden

## Administration

### Admin-Konfiguration

**Route:** `/admin/messages`

**Erforderliche Berechtigung:** `ADMIN_READ_MESSAGES`

Administratoren können:
- Nachrichtengruppen verwalten
- Gruppenmitglieder hinzufügen/entfernen
- Gruppenberechtigungen festlegen
- Gruppen-Ordnerberechtigungen konfigurieren

Siehe [Admin: Nachrichtengruppen](/de/guide/admin/messages) für administrative Funktionen.

## Verwandte Dokumentation

- [Erste Schritte](/de/guide/erste-schritte) - Lernen Sie die Grundlagen von K-Systems
- [Mitarbeiterverwaltung](/de/guide/mitarbeiterverwaltung) - Verwaltung von Benutzerkonten
- [Admin: Nachrichtengruppen](/de/guide/admin/messages) - Administrative Konfiguration

---

**Zuletzt aktualisiert:** 2025-10-01
**Version:** 2.0.0 (Korrigiert, um der tatsächlichen Implementierung zu entsprechen)
