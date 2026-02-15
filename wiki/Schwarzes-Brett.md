# Schwarzes Brett

Das Schwarze Brett von K-Systems ist ein einfaches Bulletin-Board-System zum Veroeffentlichen von Ankuendigungen mit Rich-Text-Formatierung und Lesebestaetigungen.

## Ueberblick

Funktionen:
- Beitraege mit Titel und Rich-Text-Inhalt erstellen
- Drei Board-Typen (Admin, Mitarbeiter, Global)
- Wichtige Beitraege oben anheften
- Lesebestaetigungen ("Lesebestaetigung")
- Farbcodierte Eintraege
- TiptapEditor mit umfangreicher Formatierung
- YouTube/Twitch Video-Einbettung
- Mandantenspezifische Isolierung

**Erforderliche Berechtigungen:** `READ_BLACKBOARD_[TYP]` (Ansicht), `WRITE_BLACKBOARD_[TYP]` (Erstellen/Bearbeiten)

## Zugriff auf das Schwarze Brett

- **Desktop:** Doppelklick auf das **Schwarzes Brett**-Symbol
- **Menue:** Startmenue -> Kommunikation -> Schwarzes Brett
- **Routen:**
  - `/blackboard/admin` - Admin-Board
  - `/blackboard/employee` - Mitarbeiter-Board
  - `/blackboard/global` - Globales Board

## Board-Typen

### Admin-Board (`WRITE_BLACKBOARD_ADMIN`)
- Administrative Ankuendigungen
- Managementbeitraege
- Erfordert Admin-Berechtigungen
- Lesebestaetigungen verfuegbar

### Mitarbeiter-Board (`WRITE_BLACKBOARD_EMPLOYEE`)
- Mitarbeiter-Ankuendigungen
- Abteilungsupdates
- Lesebestaetigungen verfuegbar

### Globales Board (`WRITE_BLACKBOARD_GLOBAL`)
- Organisationsweite Ankuendigungen
- Unternehmensneuigkeiten
- Keine Lesebestaetigungen

**Berechtigungsstruktur:** Jeder Board-Typ hat separate LESE-/SCHREIB-Berechtigungen. Format: `WRITE_BLACKBOARD_ADMIN`, `READ_BLACKBOARD_EMPLOYEE`, etc.

## Oberflaeche

### Hauptansicht

**Oberer Bereich:**
- Info-Hinweis mit Erklaerung des aktuellen Board-Typs
- **Eintrag hinzufuegen**-Button (bei WRITE-Berechtigung)

**Eintragsanzeige:**
- Titel und Autor
- Veroeffentlichungsdatum (TT.MM.JJJJ HH:MM Format)
- Farbiger linker Rand (anpassbar)
- Rich-Text-Inhalt mit eingebetteten Videos
- Pinnadel-Symbol bei angehefteten Eintraegen
- Lesebestaetigungs-Bereich (nur Admin/Mitarbeiter-Boards)
- Aktionsschaltflaechen: Anheften/Losloesung, Bearbeiten, Loeschen

**Reihenfolge:**
- Angeheftete Beitraege immer oben
- Innerhalb jedes Abschnitts nach Datum absteigend sortiert

## Beitraege erstellen

### Neuer Eintrag

1. **Eintrag hinzufuegen** klicken (erfordert `WRITE_BLACKBOARD_[TYP]`)
2. Dialog oeffnet sich mit Formular

**Formularfelder:**

- **Titel** (erforderlich): Klarer, beschreibender Titel
- **Autor** (erforderlich): Automatisch mit Ihrem Benutzernamen ausgefuellt, kann bearbeitet werden
- **Inhalt** (erforderlich): TiptapEditor Rich-Text-Editor mit:
  - Fett, Kursiv, Unterstrichen
  - Listen (Aufzaehlungs- und nummerierte Listen)
  - Links und Ueberschriften
  - Textausrichtung und Code-Bloecke
  - Video-Einbettung (YouTube und Twitch)
- **Randfarbe:** Farbwaehlur mit vordefinierten Farben
  - Blautoene, Gruentoene, Orangetoene, Rottoeune, Lilatoene, Graustufen
  - Standard: #212121 (dunkelgrau)
  - Erscheint als 4px linker Rand auf der Eintragskarte

**Optionen:**
- **Lesebestaetigung erforderlich?** (Kontrollkaestchen)
  - Nur verfuegbar fuer Admin/Mitarbeiter-Boards (NICHT global)
  - Zeigt "Als gelesen markieren"-Button
  - Verfolgt, wer den Beitrag gelesen hat
- **Anheften?** (Kontrollkaestchen)
  - Angeheftete Beitraege erscheinen oben auf dem Board
  - Pinnadel-Symbol wird angezeigt

3. **Speichern** klicken zum Veroeffentlichen
4. Oder **Abbrechen** zum Verwerfen

## Beitraege bearbeiten

1. **Bearbeiten**-Symbol (Stift) am Eintrag klicken
2. Dialog oeffnet sich mit vorausgefuellten Daten
3. Felder aendern: Titel, Autor, Inhalt, Randfarbe, Lesebestaetigung, Anheftstatus
4. **Speichern** klicken

**Berechtigung erforderlich:** `WRITE_BLACKBOARD_[TYP]`

## Beitraege loeschen

1. **Loeschen**-Symbol (Papierkorb) am Eintrag klicken
2. Bestaetigung im Dialog
3. **Loeschen** klicken zum Bestaetigen

**Warnung:** Loeschung ist dauerhaft. Eintrag kann nicht wiederhergestellt werden.

## Beitraege anheften / losloesung

### Anheften
1. Graues **Pinnadel**-Symbol am Eintrag klicken
2. Eintrag wird sofort angeheftet
3. Bewegt sich an die Spitze des Boards
4. Pinnadel-Symbol wechselt zur Primaerfarbe

### Losloesung
1. Farbiges **Pinnadel-aus**-Symbol am angehefteten Eintrag klicken
2. Eintrag wird sofort losgeloest
3. Kehrt zur chronologischen Reihenfolge zurueck

**Empfehlungen:**
- Nur kritische Informationen anheften
- Losloesung wenn nicht mehr dringend
- Angeheftete Beitraege regelmaessig ueberpruefen
- Maximal 3-5 angeheftete Beitraege

## Lesebestaetigungen

### Als gelesen markieren

**Verfuegbar auf:** Admin-Board und Mitarbeiter-Board (NICHT auf globalem Board)

**Wenn aktiviert:**
1. Eintrag zeigt Lesebestaetigungs-Bereich
2. Zeigt "Gelesen von:" mit kommaseparierter Liste der Leser
3. Oder "Noch niemand" wenn keine Leser
4. **Als gelesen markieren**-Button rechts

**Beitrag als gelesen markieren:**
1. Zum Lesebestaetigungs-Bereich scrollen
2. **Als gelesen markieren** klicken
3. Button zeigt Ladezustand
4. Ihr Benutzername wird zur Leserliste hinzugefuegt
5. Erfolgs-Benachrichtigung

**Anwendungsfaelle:**
- Pflichtlektuer-Bestaetigung
- Compliance-Nachverfolgung
- Wichtige Ankuendigungen
- Richtlinien-Bestaetigungen

## Video-Einbettung

### Unterstuetzte Plattformen

**YouTube:**
- Vollstaendige Video-URLs: `https://www.youtube.com/watch?v=VIDEO_ID`
- Kurz-URLs: `https://youtu.be/VIDEO_ID`
- Wird automatisch als eingebettetes Video konvertiert

**Twitch:**
- Clips: `https://clips.twitch.tv/CLIP_ID`
- Videos: `https://twitch.tv/videos/VIDEO_ID`
- Live-Kanaele werden nicht unterstuetzt

Videos werden inline im Eintragsinhalt gerendert mit responsiver Groesse.

## Tipps und Empfehlungen

**Beitraege erstellen:**
- Klare, beschreibende Titel verwenden
- Inhalt kurz und uebersichtlich halten
- Formatierung fuer Lesbarkeit nutzen
- Aussagekraeftige Randfarben waehlen
- Lesebestaetigung nur fuer wichtige Eintraege aktivieren
- Nur kritische Informationen anheften

**Inhaltsleitlinien:**
- Ein Thema pro Beitrag
- Ueberschriften zur Strukturierung verwenden
- Relevante Links einfuegen
- Videos fuer Demonstrationen einbetten
- Vor dem Veroeffentlichen Korrektur lesen

**Verwaltung:**
- Beitraege losloesung wenn nicht mehr dringend
- Veraltete Informationen loeschen
- Einheitliche Farbcodierung verwenden
- Board regelmaessig ueberpruefen

## Fehlerbehebung

### Beitrag kann nicht erstellt werden
- `WRITE_BLACKBOARD_[TYP]`-Berechtigung pruefen
- Korrekter Board-Typ in der URL?
- Funktion fuer Ihre Organisation aktiviert?
- Seite aktualisieren

### Beitrag wird nicht gespeichert
- Alle Pflichtfelder ausgefuellt? (Titel, Autor, Inhalt)
- Validierungsmeldungen pruefen
- Internetverbindung pruefen

### Video wird nicht eingebettet
- URL-Format korrekt?
- Nur YouTube und Twitch werden unterstuetzt
- Twitch-Live-Kanaele nicht unterstuetzt
- Browser-Konsole auf Konvertierungsfehler pruefen

### Lesebestaetigung funktioniert nicht
- Funktion nur auf Admin/Mitarbeiter-Boards verfuegbar (nicht global)
- Beitrag muss "Lesebestaetigung" aktiviert haben
- Internetverbindung pruefen
- Sind Sie bereits in der Leserliste?
- Seite aktualisieren

### Anheften funktioniert nicht
- `WRITE_BLACKBOARD_[TYP]`-Berechtigung pruefen
- Internetverbindung pruefen
- Seite aktualisieren

### Eintraege werden nicht geladen
- Board-Typ korrekt gesetzt?
- READ-Berechtigung fuer den Board-Typ vorhanden?
- Backend laeuft?
- Seite aktualisieren

---

## Verwandte Seiten

- [[Einfuehrung]] - Erste Schritte mit K-Systems
- [[Nachrichten]] - Internes Nachrichtensystem
- [[Kalender]] - Terminverwaltung
- [[Whiteboard]] - Echtzeit-Zusammenarbeit
- [[Dokumente]] - Dokumentenverwaltung
