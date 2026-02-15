# Nachrichten

Das Nachrichtensystem von K-Systems ermoeglicht die interne Kommunikation zwischen Benutzern und Gruppen mit umfangreichen Organisationsfunktionen.

## Ueberblick

Mit dem Nachrichtensystem koennen Sie:
- Nachrichten an einzelne Benutzer und Gruppen senden
- Nachrichten in Ordnern organisieren
- Gelesen/Ungelesen-Status verfolgen
- Wichtige Nachrichten anheften
- Nachrichten beantworten und weiterleiten
- Persoenliche Notizen zu Nachrichten hinzufuegen

**Erforderliche Berechtigung:** `READ_MESSAGES`

## Zugriff auf Nachrichten

- **Desktop:** Doppelklick auf das **Nachrichten**-Symbol
- **Menue:** Startmenue -> Kommunikation -> Nachrichten
- **Route:** `/message`

## Oberflaeche

### Navigationsleiste (links)

Die linke Seitenleiste hat vier Hauptansichten:

1. **Posteingang** - Empfangene Nachrichten (zeigt Anzahl ungelesener Nachrichten)
2. **Gesendet** - Von Ihnen gesendete Nachrichten
3. **Ordner** - Ihre Nachrichtenordner verwalten
4. **Papierkorb** - Geloeschte Nachrichten (wiederherstellen oder endgueltig loeschen)

### Kontextfilter

Filtern Sie Nachrichten nach:
- **Persoenlich** - Ihre persoenlichen Nachrichten (zeigt Anzahl ungelesener Nachrichten)
- **Gruppenfilter** - Nachrichten fuer Gruppen, denen Sie angehoeren (jeweils mit Anzahl ungelesener Nachrichten)

Klicken Sie auf die Filterschaltflaechen, um nur Nachrichten fuer den jeweiligen Kontext anzuzeigen.

## Nachrichten senden

### Neue Nachricht verfassen

1. Klicken Sie auf **+ Neue Nachricht**
2. **Absender waehlen:**
   - Als Sie selbst senden (Persoenlich)
   - Oder als Gruppe senden, der Sie angehoeren
3. **Empfaenger waehlen:**
   - Tippen Sie, um nach Benutzern oder Gruppen zu suchen
   - Waehlen Sie mehrere Empfaenger aus
   - Jeder Empfaenger erhaelt eine separate Nachricht
4. **Betreff eingeben:** Nachrichtentitel
5. **Nachricht verfassen:** Nutzen Sie den Rich-Text-Editor
   - Fett, Kursiv, Unterstrichen
   - Ueberschriften, Listen
   - Links
   - Code-Bloecke
6. **Optionale Ordnerzuweisung:**
   - Absenderordner zuweisen (Ihre Organisation)
   - Empfaengerordner zuweisen (Organisation des Empfaengers)
7. Klicken Sie auf **Senden**

**Hinweis:** Beim Senden an mehrere Empfaenger erhaelt jede Person eine einzelne Nachricht (keine Gruppenkonversation).

## Nachrichten lesen

### Nachricht oeffnen

Im Posteingang, Gesendet oder Papierkorb:
1. Klicken Sie auf den Nachrichtentitel
2. Die Nachricht oeffnet sich in der Vollansicht

### Leseansicht

**Symbolleiste:**
- **Zurueck** - Zur Nachrichtenliste zurueckkehren
- **Antworten** - Dem Absender antworten
- **Weiterleiten** - An neue Empfaenger weiterleiten
- **Speichern** - Ordner-/Notizenenderungen speichern

**Nachrichteninformationen:**
- Absendername (mit Gruppenabzeichen, falls als Gruppe gesendet)
- Empfaengername(n)
- Datum und Uhrzeit

**Zusaetzliche Funktionen:**
- **Ordnerzuweisung:** Nachricht einem Ordner zuweisen
- **Persoenliche Notizen:** Private Notizen zur Nachricht hinzufuegen
- **Nachricht anheften** (nur Posteingang): Wichtige Nachrichten oben anheften
- **Loeschen:** Nachricht in den Papierkorb verschieben

**Automatisch als gelesen markieren:** Nachrichten werden beim Oeffnen automatisch als gelesen markiert.

## Aktionen im Posteingang

**In der Nachrichtentabelle:**
- **Gelesen/Ungelesen umschalten:** Augensymbol klicken
- **Anheften/Losloesung:** Pinnnadel-Symbol klicken
- **Loeschen:** Papierkorb-Symbol klicken

**In der Leseansicht:**
- **Antworten:** Absender und Betreff werden vorausgefuellt mit "Re: [Originalbetreff]"
- **Weiterleiten:** Neue Empfaenger waehlen, Betreff vorausgefuellt mit "Fwd: [Originalbetreff]"
- **Ordner zuweisen:** Ordner aus Dropdown waehlen und Speichern klicken
- **Notizen hinzufuegen:** Notizen eingeben und Speichern klicken

## Ordner verwalten

### Ordner erstellen

1. Gehen Sie zur **Ordner**-Ansicht
2. Klicken Sie auf **+ Neuer Ordner**
3. Ordnernamen eingeben
4. Eigentuemer waehlen:
   - **Persoenlich:** Ihr persoenlicher Ordner
   - **Gruppe:** Ordner fuer eine bestimmte Gruppe
5. Klicken Sie auf **Speichern**

### Ordner bearbeiten

1. Bearbeitungssymbol neben dem Ordnernamen klicken
2. Ordnernamen aendern
3. **Speichern** klicken

**Hinweis:** Der Ordner-Eigentuemer kann nach der Erstellung nicht geaendert werden.

### Ordner loeschen

1. Loeschsymbol neben dem Ordnernamen klicken
2. Loeschung bestaetigen

**Hinweis:** Das Loeschen eines Ordners loescht nicht die Nachrichten, nur den Ordner selbst.

### Nachrichten filtern

Im Posteingang, Gesendet oder Papierkorb:
1. Ordnerfilter-Dropdown ueber der Nachrichtentabelle verwenden
2. Ordner waehlen, um nur Nachrichten in diesem Ordner zu sehen
3. "Alle" waehlen, um alle Nachrichten zu sehen

## Nachrichten suchen

Verwenden Sie das Suchfeld, um Nachrichten zu finden nach:
- Betreff/Titel
- Nachrichteninhalt
- Absendername
- Empfaengername

Sie koennen mehrere Filter kombinieren:
1. Persoenlichen oder Gruppenkontext waehlen
2. Einen Ordner waehlen
3. Suchtext eingeben
4. Alle Filter werden gemeinsam angewendet

## Antworten und Weiterleiten

### Auf eine Nachricht antworten

1. Nachricht im Posteingang oeffnen
2. **Antworten** klicken
3. Der Verfassen-Dialog oeffnet sich mit:
   - Empfaenger: Urspruenglicher Absender
   - Betreff: "Re: [Originalbetreff]"
   - Nachricht: Originalnachricht zitiert
4. Antwort ueber dem zitierten Text eingeben
5. **Senden** klicken

### Eine Nachricht weiterleiten

1. Beliebige Nachricht oeffnen
2. **Weiterleiten** klicken
3. Neue Empfaenger auswaehlen
4. Eigene Nachricht ueber dem zitierten Inhalt hinzufuegen
5. **Senden** klicken

## Papierkorb

### In den Papierkorb verschieben

Aus dem Posteingang oder Gesendet:
- Loeschsymbol in der Nachrichtentabelle klicken
- Oder **Loeschen** in der Leseansicht klicken
- Nachricht wird in den Papierkorb verschoben

### Wiederherstellen

1. Zur **Papierkorb**-Ansicht gehen
2. Wiederherstellungssymbol klicken
3. Nachricht kehrt zum Posteingang (als Empfaenger) oder Gesendet (als Absender) zurueck

### Endgueltig loeschen

1. Zur **Papierkorb**-Ansicht gehen
2. Endgueltig-Loeschen-Symbol klicken
3. Loeschung bestaetigen

**Warnung:** Endgueltiges Loeschen kann nicht rueckgaengig gemacht werden.

## Gruppen-Funktionen

### Als Gruppe senden

1. Im Verfassen-Dialog den Absender-Dropdown oeffnen
2. Eine Gruppe statt "Persoenlich" waehlen
3. Die Nachricht wird als von der Gruppe gesendet angezeigt

### An eine Gruppe senden

1. Im Verfassen-Dialog Empfaenger auswaehlen
2. Gruppennamen eingeben und suchen
3. Gruppe auswaehlen
4. Alle Gruppenmitglieder erhalten die Nachricht

### Gruppenordner

Erstellen Sie Ordner fuer die Gruppennutzung:
1. Zur **Ordner**-Ansicht gehen
2. **+ Neuer Ordner** klicken
3. Eine Gruppe als Eigentuemer waehlen (erfordert Ordnerverwaltungsberechtigung)
4. Alle Gruppenmitglieder koennen Nachrichten Gruppenordnern zuweisen

## Tipps und Empfehlungen

**Nachrichten organisieren:**
- Erstellen Sie Ordner fuer verschiedene Themen (Projekte, Wichtig, Archiv, etc.)
- Heften Sie dringende Nachrichten im Posteingang an
- Nutzen Sie Notizen fuer Kontext oder Erinnerungen
- Raeumen Sie den Papierkorb regelmaessig auf

**Nachrichten verfassen:**
- Verwenden Sie klare, beschreibende Betreffzeilen
- Formatieren Sie Nachrichten mit Ueberschriften und Listen
- Antworten Sie, um den Kontext beizubehalten, anstatt neue Threads zu starten

**Posteingang verwalten:**
- Markieren Sie Nachrichten als ungelesen, wenn Sie spaeter nachfassen muessen
- Heften Sie wichtige Nachrichten an, die Aufmerksamkeit erfordern
- Nutzen Sie Ordnerfilter, um sich auf bestimmte Themen zu konzentrieren

## Fehlerbehebung

### Nachrichten erscheinen nicht
- Pruefen Sie, ob die Nachricht in Ihrer Gesendet-Ansicht erscheint
- Ueberpruefen Sie den Empfaengernamen
- Bitten Sie den Empfaenger, seine Gruppenfilter zu pruefen
- Seite aktualisieren

### Nachricht nicht auffindbar
- Papierkorb-Ansicht pruefen
- Alle Filter zuruecksetzen (Persoenlich/Gruppe, Ordner)
- Suche nach Betreff oder Absender verwenden

### Ordner wird nicht angezeigt
- Ordner-Ansicht aufrufen und pruefen
- Ordner-Eigentuemer pruefen (Persoenlich vs. Gruppe)
- Seite aktualisieren

---

## Verwandte Seiten

- [[Einfuehrung]] - Erste Schritte mit K-Systems
- [[Desktop-Oberflaeche]] - Desktop-Interface Bedienung
- [[Mitarbeiterverwaltung]] - Mitarbeiter verwalten
- [[Schwarzes-Brett]] - Ankuendigungen
- [[Whiteboard]] - Echtzeit-Zusammenarbeit
