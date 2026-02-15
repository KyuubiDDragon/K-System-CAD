# Einsatzleitung

Das Einsatzleitungssystem (Dispatch) von K-Systems bietet eine einfache Drag-and-Drop-Oberflaeche zur Zuweisung von Mitarbeitern und Fahrzeugen zu Einsatzeinheiten mit Echtzeit-WebSocket-Synchronisierung.

## Ueberblick

Funktionen:
- Drag-and-Drop-Zuweisung von Mitarbeitern
- Drag-and-Drop-Zuweisung von Fahrzeugen
- Mehrere Einsatzkarten
- Statusfeld pro Einsatz
- Echtzeit-WebSocket-Updates
- Mitarbeiter- und Fahrzeugsuche
- Farbcodierte Einsatzkopfzeilen
- Mandantenspezifische Isolierung

**Erforderliche Berechtigungen:** `READ_DISPATCH` (Ansicht), `WRITE_DISPATCH` (Zuweisen)

## Zugriff auf die Einsatzleitung

- **Desktop:** Doppelklick auf das **Einsatzleitung**-Symbol
- **Menue:** Startmenue -> Einsatz -> Einsatzleitung
- **Route:** `/dispatch`

## Oberflaeche

### Linke Spalte - Verfuegbare Ressourcen

**Mitarbeiterliste:**
- Suchfeld mit Lupensymbol
- Zaehler-Badge mit Anzahl verfuegbarer Mitarbeiter
- Ziehbare Mitarbeiterkarten mit:
  - Avatar mit erstem Buchstaben
  - Dienstnummer [123]
  - Mitarbeitername
  - Rangbezeichnung (grauer Text)

**Fahrzeugliste:**
- Suchfeld mit Lupensymbol
- Zaehler-Badge mit Anzahl verfuegbarer Fahrzeuge
- Ziehbare Fahrzeugkarten mit:
  - Fahrzeugsymbol
  - Fahrzeugtitel
  - Kennzeichen
  - Rang (falls zutreffend)

**Suchfunktion:**
- Echtzeit-Filterung bei der Eingabe
- Filtert nach Name/Dienstnummer fuer Mitarbeiter
- Filtert nach Titel/Kennzeichen fuer Fahrzeuge

### Rechte Spalte - Einsatzkarten

**Einsatzraster:**
- Responsives Rasterlayout (2-6 Spalten je nach Bildschirmgroesse)
- Jede Einsatzkarte zeigt:
  - Farbcodierte Kopfzeile mit Einsatzname
  - Mitarbeiter-Ablagebereich
  - Fahrzeug-Ablagebereich
  - Status-Textfeld mit Speichern-Button
  - Platzhalter wenn leer

**Aufbau einer Einsatzkarte:**
- **Kopfzeile:** Farbcodierte Werkzeugleiste mit Einsatzname
- **Mitarbeiterbereich:** "Mitarbeiter"-Abschnitt mit Ablagebereich und Platzhalter "Mitarbeiter hierher ziehen"
- **Fahrzeugbereich:** "Fahrzeuge"-Abschnitt mit Ablagebereich und Platzhalter "Fahrzeug hierher ziehen"
- **Status-Fusszeile:** Textfeld fuer Statusaktualisierungen mit Speichern-Symbol

## Ressourcen zuweisen

### Mitarbeiter einem Einsatz zuweisen

**Per Drag-and-Drop:**
1. Mitarbeiter in der linken Mitarbeiterliste finden
2. Mitarbeiterkarte ziehen
3. In den Mitarbeiterbereich einer Einsatzkarte ablegen
4. Mitarbeiter erscheint im Einsatz
5. API wird automatisch aufgerufen
6. WebSocket uebertraegt Update an alle verbundenen Clients

**Klonverhalten:** Mitarbeiter kann mehreren Einsaetzen zugewiesen werden. Das Original bleibt in der Quelliste.

### Fahrzeug einem Einsatz zuweisen

**Per Drag-and-Drop:**
1. Fahrzeug in der linken Fahrzeugliste finden
2. Fahrzeugkarte ziehen
3. In den Fahrzeugbereich einer Einsatzkarte ablegen
4. Fahrzeug erscheint im Einsatz
5. API wird automatisch aufgerufen
6. WebSocket uebertraegt Update

**Klonverhalten:** Fahrzeug kann mehreren Einsaetzen zugewiesen werden.

### Zuweisung entfernen

**Mitarbeiter entfernen:**
1. Mitarbeiterkarte aus dem Einsatz-Mitarbeiterbereich ziehen
2. Zurueck zur Mitarbeiter-Quelliste ablegen
3. Mitarbeiter wird aus dem Einsatz entfernt

**Fahrzeug entfernen:**
1. Fahrzeugkarte aus dem Einsatz-Fahrzeugbereich ziehen
2. Zurueck zur Fahrzeug-Quelliste ablegen
3. Fahrzeug wird aus dem Einsatz entfernt

**Zwischen Einsaetzen verschieben:**
- Mitarbeiter/Fahrzeug direkt zwischen Einsatzkarten ziehen
- Verschiebt die Zuweisung von einem Einsatz zum anderen

## Statusaktualisierungen

### Einsatzstatus aktualisieren

1. In das Status-Textfeld des Einsatzes klicken
2. Statustext eingeben (z.B. "Unterwegs", "Vor Ort", "Rueckkehr")
3. Speichern-Symbol klicken ODER Enter druecken
4. Ladeanzeige waehrend des Speicherns
5. Status wird in der Datenbank gespeichert
6. WebSocket uebertraegt Update an alle Clients

Das Statusfeld ist ein Freitextfeld ohne vordefinierte Optionen.

## Echtzeit-WebSocket-Updates

### Automatische Synchronisierung

Das System ist ueber WebSocket (`/dispatch`-Namespace) verbunden:
- Empfaengt Updates von anderen Benutzern in Echtzeit
- Aktualisiert Einsatzzuweisungen live
- Wenn ein anderer Benutzer Mitarbeiter/Fahrzeuge zuweist, aktualisiert sich Ihre Ansicht automatisch
- Statusaenderungen anderer Benutzer werden sofort angezeigt
- Verhindert Datenkonflikte

**WebSocket-Initialisierung:**
- Wird beim Laden der Komponente initialisiert
- Registriert Update-Handler
- Lauscht auf `dispatch-updated` Ereignisse
- Wird beim Verlassen der Seite bereinigt

## Tipps und Empfehlungen

**Ressourcen organisieren:**
- Suche nutzen, um Mitarbeiter/Fahrzeuge schnell zu finden
- Von Quellisten zu Einsatzkarten ziehen
- Entfernen durch Zurueckziehen zur Quelle
- Einsatzkarten nach Einheitstyp organisiert halten

**Statusaktualisierungen:**
- Einheitliche Statusbegriffe verwenden
- Status bei Aenderung der Situation aktualisieren
- Status fuer alle Benutzer aktuell halten
- Enter druecken fuer schnelles Speichern

**Echtzeit-Zusammenarbeit:**
- Andere Benutzer sehen Ihre Aenderungen sofort
- Widerspruelichere Zuweisungen vermeiden
- Bei groesseren Aenderungen mit dem Team kommunizieren

## Fehlerbehebung

### Mitarbeiter/Fahrzeug kann nicht gezogen werden
- Browser unterstuetzt Drag-and-Drop?
- Mitarbeiter-/Fahrzeugliste geladen?
- Seite aktualisieren
- Browser-Erweiterungen pruefen, die Drag-and-Drop blockieren koennten

### Zuweisung wird nicht gespeichert
- Internetverbindung pruefen
- `WRITE_DISPATCH`-Berechtigung pruefen
- Browser-Konsole auf API-Fehler pruefen
- Backend laeuft?

### Status wird nicht gespeichert
- Speichern-Symbol klicken oder Enter druecken
- Ladeanzeige abwarten
- Internetverbindung pruefen
- `WRITE_DISPATCH`-Berechtigung pruefen

### WebSocket aktualisiert nicht
- WebSocket-Verbindung im Netzwerk-Tab pruefen
- Socket-Server laeuft auf Port 3001?
- Seite aktualisieren fuer Neuverbindung
- Authentifizierungstoken gueltig?

### Ressourcen werden nicht geladen
- Mitarbeiter/Fahrzeuge im System vorhanden?
- `READ_DISPATCH`-Berechtigung pruefen
- Browser-Konsole auf API-Fehler pruefen
- Backend laeuft?

### Einsatzkarten werden nicht angezeigt
- Einsatzkarten muessen im Backend/Datenbank erstellt werden
- Keine UI zum Erstellen von Einsaetzen (nur Backend/Admin)
- Einsaetze fuer Ihre Organisation vorhanden?
- Seite aktualisieren

---

## Verwandte Seiten

- [[Einfuehrung]] - Erste Schritte mit K-Systems
- [[Mitarbeiterverwaltung]] - Mitarbeiter verwalten
- [[Mannschaftsverwaltung]] - Mannschaftsorganisation
- [[Karte]] - Kartenintegration
