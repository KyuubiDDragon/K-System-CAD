# Dateimanager

Der Dateimanager von K-Systems bietet ein Dateiverwaltungssystem zum Organisieren von Dateien in Ordnern mit Upload, Download und grundlegenden Verwaltungsfunktionen.

## Ueberblick

Funktionen:
- Ordnernavigation mit Breadcrumb
- Datei-Upload (Mehrfach-Upload)
- Ordnererstellung (verschachtelte Ordner)
- Datei-Download
- Dateisuche nach Name
- Raster- und Listenansicht
- Bildvorschau
- Dateiloeschung

**Erforderliche Berechtigungen:** `READ_FILEMANAGER` (Ansicht), `WRITE_FILEMANAGER` (Upload/Erstellen), `DELETE_FILEMANAGER` (Loeschen)

## Zugriff auf den Dateimanager

- **Desktop:** Doppelklick auf das **Dateimanager**-Symbol
- **Menue:** Startmenue -> Dateien -> Dateimanager
- **Route:** `/filemanager`

## Oberflaeche

### Kopfzeile
- Titel mit Ordnersymbol
- Suchfeld (oben rechts)

### Aktionsleiste
- **Zurueck-Button:** Zum uebergeordneten Ordner zurueckkehren (innerhalb eines Ordners)
- **Breadcrumb-Navigation:** Root -> Ordner1 -> Ordner2... (Segmente anklickbar)
- **Ordner erstellen:** Neuen Unterordner erstellen (bei Bearbeitungsberechtigung)
- **Datei-Upload-Eingabe:** Dateien zum Hochladen auswaehlen
- **Upload-Button:** Ausgewaehlte Dateien hochladen

### Inhaltsbereich

**Ordnerbereich:**
- Raster aus Ordnerkarten
- Ordnersymbol und Name
- Klicken zum Navigieren in den Ordner

**Dateibereich:**
- Ansichtsmodus-Umschalter (Raster/Liste)
- **Rasteransicht:** Kartenbasiertes Layout mit Vorschauen
- **Listenansicht:** Kompakte tabellenaehnliche Zeilen

### Dateikarten (Rasteransicht)
- Dateityp-Symbol oder Bildvorschau
- Dateiname und Dateigroesse (B, KB, MB, GB)
- Dateityp-Badge mit Farbcodierung
- Hover-Overlay mit Aktionsschaltflaechen:
  - Download (oeffnet in neuem Tab)
  - Link kopieren (URL in Zwischenablage)
  - Loeschen (bei Loeschberechtigung)

## Ordner erstellen

1. **Ordner erstellen** (Ordner+-Symbol) klicken
2. Dialog oeffnet sich
3. Ordnernamen eingeben
4. **Erstellen** klicken
5. Neuer Ordner erscheint am aktuellen Speicherort

**Verschachtelte Ordner:**
- In einen Ordner navigieren
- Neuen Ordner erstellen
- Erstellt Unterordner mit Eltern-Kind-Beziehung

**Hinweis:** Ordner koennen nach der Erstellung nicht ueber die UI umbenannt oder geloescht werden.

## Dateien hochladen

1. Datei-Eingabefeld oder **Upload**-Button klicken
2. Dateiauswahl-Dialog oeffnet sich
3. Eine oder mehrere Dateien auswaehlen
4. **Oeffnen** klicken
5. Dateien werden in den aktuellen Ordner hochgeladen
6. Toast-Benachrichtigung bestaetigt den Upload

**Mehrfach-Upload:** Mehrere Dateien im Dateiauswahl-Dialog waehlen (Strg+Klick oder Umschalt+Klick)

### Unterstuetzte Dateitypen

**Frontend erlaubt:**
- Bilder: jpg, jpeg, png, gif, bmp, svg
- Dokumente: pdf, doc, docx, xls, xlsx, ppt, pptx
- Design: psd (Photoshop)

**Zusaetzlich (Backend):**
- Text: txt
- Archive: zip, rar
- Videos: mp4, mov, avi, wmv

**Dateigroessenlimit:** 50 MB pro Datei

**Upload-Speicherort:** Dateien werden in den aktuell geoeffneten Ordner hochgeladen.

## Datei-Operationen

### Datei herunterladen

**Methode 1:**
- Mit der Maus ueber die Dateikarte fahren
- **Download**-Button klicken
- Datei oeffnet sich in neuem Browser-Tab
- Browser-Download-Option zum Speichern verwenden

**Methode 2:**
- Rechtsklick auf Dateiname
- "Link speichern unter..." im Browsermenue waehlen

### Link kopieren

1. Mit der Maus ueber die Dateikarte fahren
2. **Link kopieren** (Ketten-Symbol) klicken
3. Datei-URL wird in die Zwischenablage kopiert
4. URL zum direkten Teilen einfuegen

### Datei loeschen

1. Mit der Maus ueber die Dateikarte fahren
2. **Loeschen** (Papierkorb-Symbol) klicken
3. Loeschung im Dialog bestaetigen

**Berechtigung erforderlich:** `DELETE_FILEMANAGER`

## Navigation

### Ordnernavigation

- **Ordner oeffnen:** Auf Ordnerkarte klicken
- **Zurueck:** Zurueck-Button klicken (Pfeil-Symbol) - zurueck zum uebergeordneten Ordner
- **Breadcrumb:** Beliebiges Segment im Breadcrumb-Pfad anklicken fuer direkten Sprung

Beispiel: Root -> Dokumente -> Berichte
- "Dokumente" anklicken springt zum Dokumenten-Ordner
- "Root" anklicken kehrt zur obersten Ebene zurueck

## Suche

- Suchleiste in der Kopfzeile (oben rechts)
- Sucht nach Datei- und Ordnernamen
- Echtzeit-Filterung bei der Eingabe
- Gross-/Kleinschreibung wird ignoriert
- Suche loeschen, um alle Eintraege zu sehen

**Hinweis:** Suche funktioniert nur nach Name. Keine Suche nach Dateityp, Groesse, Datum oder Inhalt moeglich.

## Ansichtsmodi

### Rasteransicht (Standard)
- Groessere Datei-/Ordnerkarten
- Bildvorschauen fuer Fotos
- Symbolbasierte Vorschau fuer andere Dateien
- Dateityp-Badges
- Ideal zum Durchstoebern von Bildern

### Listenansicht
- Kompaktes Layout
- Dateisymbol + Name + Groesse
- Mehr Dateien pro Bildschirm sichtbar
- Ideal fuer viele Dateien

Umschalten durch Klick auf den Ansichtsmodus-Button im Dateibereich.

## Dateityp-Symbole

- **PDF:** Roter Badge mit PDF-Symbol
- **Word** (doc, docx): Blauer Badge mit Word-Symbol
- **Excel** (xls, xlsx): Gruener Badge mit Excel-Symbol
- **PowerPoint** (ppt, pptx): PowerPoint-Symbol
- **Bilder** (jpg, png, etc.): Lila Badge mit Bildsymbol
- **Text** (txt): Dokumentsymbol
- **Archive** (zip, rar, 7z): Zip-Symbol
- **Unbekannt:** Generisches Dateisymbol

## Tipps und Empfehlungen

**Dateien organisieren:**
- Ordner fuer verschiedene Kategorien erstellen (Berichte, Bilder, Dokumente)
- Beschreibende Ordnernamen verwenden
- Breadcrumbs fuer schnellen Zugriff nutzen
- Suche verwenden, um Dateien nach Name zu finden

**Dateien hochladen:**
- Mehrere Dateien gleichzeitig hochladen fuer Effizienz
- Dateigroesse vor Upload pruefen (50 MB Limit)
- In den richtigen Ordner navigieren vor dem Upload
- Unterstuetzte Dateitypen fuer beste Kompatibilitaet verwenden

**Dateien verwalten:**
- Link kopieren zum direkten Teilen von Dateien
- Unnoetige Dateien loeschen um Speicherplatz freizugeben
- Unterordner erstellen fuer Organisation innerhalb von Ordnern
- Rasteransicht fuer Bilder, Listenansicht fuer Dokumente

## Fehlerbehebung

### Upload schlaegt fehl
- Dateigroesse pruefen (max. 50 MB)
- Dateityp unterstuetzt?
- `WRITE_FILEMANAGER`-Berechtigung pruefen
- Kleinere Dateien zuerst versuchen
- Internetverbindung pruefen

### Ordner kann nicht erstellt werden
- `WRITE_FILEMANAGER`-Berechtigung pruefen
- Anderen Ordnernamen versuchen
- Seite aktualisieren

### Datei wird nicht heruntergeladen
- Browser erlaubt Popups von dieser Seite?
- Rechtsklick -> "Link speichern unter" versuchen
- Datei existiert noch (nicht geloescht)?
- Anderen Browser versuchen

### Datei kann nicht geloescht werden
- `DELETE_FILEMANAGER`-Berechtigung pruefen
- Sind Sie der Dateieigentuemer?
- Seite aktualisieren

### Suche funktioniert nicht
- Rechtschreibung des Dateinamens pruefen
- Teilweisen Dateinamen versuchen
- Suche loeschen und manuell durchstoebern

---

## Verwandte Seiten

- [[Einfuehrung]] - Erste Schritte mit K-Systems
- [[Dokumente]] - Dokumentenverwaltungssystem
- [[Berichte]] - Berichtsverwaltung
- [[Nachrichten]] - Internes Nachrichtensystem
