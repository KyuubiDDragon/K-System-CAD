# Whiteboard

Das Whiteboard von K-Systems bietet eine Echtzeit-Kollaborationsplattform fuer Team-Brainstorming, Diagramme und visuelle Zusammenarbeit mit Zeichenwerkzeugen und gemeinsamen Zugriff.

## Ueberblick

Funktionen:
- Echtzeit-kollaboratives Zeichnen
- Freihandstift und Radierer
- 5 geometrische Formen (Linie, Rechteck, Kreis, Dreieck, Pfeil)
- Farbanpassung (15 Voreinstellungen + benutzerdefinierte Farben)
- 3 Stiftgroessen (Duenn, Mittel, Dick)
- Rueckgaengig/Wiederherstellen
- Als PNG-Bild speichern
- Zugriffssteuerung (Privat, Oeffentlich, Zugangscode)
- Dauerhafte Speicherung

**Erforderliche Berechtigungen:** `READ_WHITEBOARD` (Ansicht), `WRITE_WHITEBOARD` (Erstellen/Bearbeiten), `DELETE_WHITEBOARD` (Loeschen)

## Zugriff auf das Whiteboard

- **Desktop:** Doppelklick auf das **Whiteboard**-Symbol
- **Menue:** Startmenue -> Zusammenarbeit -> Whiteboard
- **Route:** `/whiteboard`

## Oberflaeche

Das Whiteboard hat zwei Hauptmodi:

### Whiteboard-Manager (Listenansicht)

**Drei Tabs:**
1. **Meine Boards** - Ihre Whiteboards
2. **Oeffentliche Boards** - Organisationsweite oeffentliche Whiteboards
3. **Mit Code beitreten** - Zugangscode eingeben fuer private Whiteboards

**Whiteboard-Karten:**
- Whiteboard-Name
- Erstellungsdatum
- Teilnehmeranzahl
- Zugriffstyp (Privat/Oeffentlich/Code)
- Zugangscode (nur fuer Eigentuemer sichtbar)
- Aktionen: Oeffnen, Loeschen (nur Eigentuemer)

### Leinwandansicht (Zeichenmodus)

**Obere Werkzeugleiste:**
- **Werkzeuge:** Stift, Radierer, Formen, Auswahl
- **Farben:** 15 voreingestellte Farben + benutzerdefinierter Farbwaehler
- **Stiftgroessen:** Duenn (2px), Mittel (5px), Dick (10px)
- **Aktionen:** Rueckgaengig, Wiederherstellen, Leinwand loeschen, PNG speichern
- **Beenden:** Zurueck zur Whiteboard-Liste

**Zeichenflaeche:**
- Vollbild-HTML5-Canvas
- Echtzeit-Synchronisierung mit anderen Benutzern
- Touch-faehig fuer Tablets/Mobilgeraete

## Whiteboards erstellen

### Neues Whiteboard

1. **+ Neues Whiteboard** klicken
2. Details ausfuellen:
   - **Name** (erforderlich): Whiteboard-Titel
   - **Hintergrundfarbe:** Aus Farbwaehler waehlen (Standard: Weiss)
   - **Zugriffstyp:**
     - **Privat:** Nur Sie haben Zugriff
     - **Oeffentlich:** Jeder in Ihrer Organisation kann sehen und beitreten
     - **Zugangscode:** Erfordert 6-stelligen Code zum Beitreten
3. **Erstellen** klicken
4. Neues Whiteboard erscheint in "Meine Boards"

### Zugangscodes

Fuer Code-basierte Whiteboards:
- System generiert automatisch einen 6-stelligen Code
- Oder geben Sie einen eigenen Code ein (6 Zeichen)
- Teilen Sie den Code mit Ihren Kollegen
- Code wird auf der Whiteboard-Karte angezeigt (nur fuer Eigentuemer)

## Whiteboards beitreten

### Oeffentlichem Whiteboard beitreten
1. Zum Tab **Oeffentliche Boards** wechseln
2. **Oeffnen** bei einem Whiteboard klicken
3. Leinwandansicht oeffnet sich
4. Mit dem Zeichnen beginnen

### Mit Code beitreten
1. Zum Tab **Mit Code beitreten** wechseln
2. 6-stelligen Zugangscode eingeben
3. **Beitreten** klicken
4. Bei gueltigem Code oeffnet sich die Leinwandansicht
5. Whiteboard erscheint auch in "Meine Boards" fuer schnellen Zugriff

### Eigenes Whiteboard oeffnen
1. Zum Tab **Meine Boards** wechseln
2. **Oeffnen** beim Whiteboard klicken
3. Leinwandansicht oeffnet sich

## Zeichenwerkzeuge

### Stift-Werkzeug
1. **Stift**-Werkzeug (Bleistiftsymbol) waehlen
2. Farbe aus der Palette waehlen
3. Stiftgroesse waehlen (Duenn/Mittel/Dick)
4. Auf der Leinwand klicken und ziehen zum Zeichnen
5. Linien erscheinen in Echtzeit fuer alle Benutzer

**Touch-Unterstuetzung:** Mit Finger oder Stift zeichnen, Multi-Touch-Gesten werden unterstuetzt.

### Radierer-Werkzeug
1. **Radierer**-Werkzeug (Radiersymbol) waehlen
2. Radierer ist 3x groesser als die gewaehlte Stiftgroesse
3. Ueber Elemente klicken und ziehen zum Loeschen
4. Geloeschte Elemente werden fuer alle Benutzer in Echtzeit entfernt

**Hinweis:** Der Radierer entfernt ganze Striche/Formen, keine Teilbereiche.

### Formen-Werkzeug
1. **Formen**-Werkzeug (Quadratsymbol) waehlen
2. Formtyp aus Dropdown waehlen:
   - **Linie** - Gerade Linie
   - **Rechteck** - Rechteckumriss
   - **Kreis** - Kreisumriss
   - **Dreieck** - Dreieckumriss
   - **Pfeil** - Linie mit Pfeilspitze
3. Farbe und Linienbreite waehlen
4. Klicken und ziehen zum Zeichnen der Form:
   - Klick = Startpunkt
   - Ziehen = Endpunkt / Groesse
   - Loslassen = Form abschliessen

**Form-Eigenschaften:** Nur Umriss (keine Fuellfarbe), Linienbreite basiert auf Stiftgroessen-Einstellung.

### Auswahl-Werkzeug
Das Auswahl-Werkzeug ist in der Werkzeugleiste vorhanden, hat aber eingeschraenkte Funktionalitaet. Verschieben, Groessenaenderung und Kopieren/Einfuegen werden derzeit nicht unterstuetzt.

## Anpassungsoptionen

### Farben

**15 voreingestellte Farben:**
Schwarz, Weiss, Rot, Gruen, Blau, Gelb, Magenta, Cyan, Orange, Lila, Braun, Grau, Hellrosa, Hellgruen, Hellblau

**Benutzerdefinierte Farbe:**
- Farbwaehler-Symbol klicken
- Beliebige Hex-Farbe waehlen
- Farbe wird fuer die aktuelle Sitzung gespeichert

### Stiftgroessen

- **Duenn** - 2px Breite (feine Details)
- **Mittel** - 5px Breite (Standard, allgemeine Verwendung)
- **Dick** - 10px Breite (fette Striche, Ueberschriften)

Groesse gilt fuer Stift- und Formenwerkzeug. Radierer ist 3x so gross wie die gewaehlte Groesse.

## Leinwand-Aktionen

### Rueckgaengig
- **Rueckgaengig**-Button klicken
- Letzte Aktion wird rueckgaengig gemacht
- Mehrfach moeglich

### Wiederherstellen
- **Wiederherstellen**-Button klicken
- Rueckgaengig gemachte Aktion wird wiederhergestellt
- Wird zurueckgesetzt bei neuer Aktion

### Leinwand loeschen
1. **Leinwand loeschen** (Papierkorb-Symbol) klicken
2. Im Dialog bestaetigen
3. Alle Elemente werden fuer alle Benutzer entfernt
4. Kann nicht rueckgaengig gemacht werden

**Warnung:** Loeschen der Leinwand ist dauerhaft und betrifft alle Mitarbeiter.

### Als PNG speichern
1. **Speichern** (Download-Symbol) klicken
2. Leinwand wird auf zwei Arten gespeichert:
   - **PNG-Download** - Bild wird auf Ihr Geraet heruntergeladen
   - **Snapshot in Datenbank** - Bild wird auf dem Server fuer spaeteren Zugriff gespeichert

## Echtzeit-Zusammenarbeit

### Funktionsweise

**Synchronisierung:**
- Alle Zeichenaktionen werden in Echtzeit ueber Socket.io uebertragen
- Aenderungen erscheinen sofort fuer alle verbundenen Benutzer
- Dualer Ansatz: API + Socket.io fuer Zuverlaessigkeit
- Jeder Strich/jede Form wird einzeln synchronisiert

**Verbindungsstatus:**
- Warnung bei Verbindungsverlust
- Neuverbindungs-Button verfuegbar
- Automatische Wiederverbindungsversuche

**Was wird synchronisiert:**
- Stiftstriche (Freihandzeichnung)
- Radierer-Aktionen
- Formen (alle 5 Typen)
- Leinwand-Loeschaktionen

**Was wird NICHT synchronisiert:**
- Benutzercursor (keine Live-Cursorpositionen)
- Rueckgaengig/Wiederherstellen (nur lokal)
- Benutzeranwesenheitsanzeiger

**Mehrbenutzer-Zeichnen:** Mehrere Benutzer koennen gleichzeitig zeichnen. Keine Zeichenkonflikte.

## Whiteboards verwalten

### Whiteboard loeschen

**Nur fuer Eigentuemer:**
1. Whiteboard in "Meine Boards" finden
2. **Loeschen** (Papierkorb-Symbol) klicken
3. Loeschung bestaetigen

**Hinweis:** Nur der Whiteboard-Eigentuemer kann loeschen. Loeschung betrifft alle Benutzer mit Zugriff.

## Zugriffssteuerung

### Private Whiteboards
- Nur der Ersteller hat Zugriff
- Nicht sichtbar fuer andere Benutzer
- Kein Zugangscode
- Volle Eigentuemerkontrole

### Oeffentliche Whiteboards
- Sichtbar fuer alle Organisationsbenutzer
- Jeder kann oeffnen und zeichnen
- Erscheint im Tab "Oeffentliche Boards"
- Kein Zugangscode erforderlich

### Code-basierte Whiteboards
- Erfordert 6-stelligen Zugangscode
- Code mit bestimmten Kollegen teilen
- Nicht in oeffentlichen Boards sichtbar
- Code wird dem Eigentuemer auf der Whiteboard-Karte angezeigt

## Tipps und Empfehlungen

**Effektive Zusammenarbeit:**
- Leinwand vor neuer Sitzung loeschen
- Kontrastfarben fuer Sichtbarkeit waehlen
- Formen fuer strukturierte Diagramme verwenden
- Stift fuer Freihandskizzen und Anmerkungen
- Snapshots regelmaessig als Backup speichern

**Zeichentipps:**
- Duenner Stift fuer Details, dicker fuer Betonung
- Pfeile fuer Beziehungen zwischen Elementen
- Kreise zum Hervorheben von Bereichen
- Rechtecke zum Umrahmen von Inhalten

**Zugriffssteuerung:**
- Privat fuer persoenliches Brainstorming
- Oeffentlich fuer organisationsweite Zusammenarbeit
- Code-basiert fuer teamspezifische Sitzungen
- Codes sicher teilen (nicht oeffentlich posten)

**Leistung:**
- Uebermassige Details vermeiden (Tausende von Strichen)
- Leinwand loeschen bei neuem Thema
- Neues Whiteboard erstellen fuer groessere Aenderungen
- Gleichzeitige Benutzer auf ca. 10 begrenzen fuer beste Leistung

## Fehlerbehebung

### Verbindung verloren
- **Neuverbindung**-Button in der Warnung klicken
- Internetverbindung pruefen
- Browser-Seite aktualisieren
- Whiteboard erneut oeffnen

### Zeichnungen werden nicht synchronisiert
- Verbindungsstatus pruefen (Warnung sichtbar?)
- `WRITE_WHITEBOARD`-Berechtigung vorhanden?
- Whiteboard fuer andere Benutzer geoeffnet?
- Alle Browser aktualisieren

### Beitreten mit Code funktioniert nicht
- Code genau 6 Zeichen?
- Code mit Whiteboard-Eigentuemer ueberpruefen
- Whiteboard nicht geloescht?
- Code kopieren/einfuegen statt tippen

### Speichern funktioniert nicht
- Browser erlaubt Downloads?
- `WRITE_WHITEBOARD`-Berechtigung pruefen
- Anderen Browser versuchen

### Whiteboard kann nicht geloescht werden
- Sind Sie der Whiteboard-Eigentuemer?
- `DELETE_WHITEBOARD`-Berechtigung pruefen
- Seite aktualisieren

---

## Verwandte Seiten

- [[Einfuehrung]] - Erste Schritte mit K-Systems
- [[Nachrichten]] - Internes Nachrichtensystem
- [[Schwarzes-Brett]] - Ankuendigungen
- [[Kalender]] - Terminplanung fuer Whiteboard-Sitzungen
- [[Dokumente]] - Dokumentenverwaltung
