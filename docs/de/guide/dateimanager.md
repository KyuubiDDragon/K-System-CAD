# Dateimanager

Zentralisiertes Dateibrowser- und Dokumentenverwaltungssystem zum Organisieren, Teilen und gemeinsamen Bearbeiten von Dateien innerhalb der Authorität.

## Überblick

Funktionen:
- Dateibrowser-Oberfläche
- Ordnerstruktur
- Datei-Upload/Download
- Dateifreigabe
- Versionskontrolle
- Dateisuche
- Dateiberechtigungen
- Speicherverwaltung
- Papierkorb/Wiederherstellung

## Voraussetzungen

**Erforderliche Berechtigung:** `READ_FILEMANAGER` (Anzeigen), `WRITE_FILEMANAGER` (Hochladen/Bearbeiten)

## Dateimanager öffnen

**Desktop:** Doppelklick auf **Dateimanager**-Symbol
**Menü:** Startmenü → Dateien → Dateimanager
**Route:** `/filemanager`

## Oberflächenlayout

**Linke Seitenleiste:**
- Meine Dateien
- Für mich freigegeben
- Letzte Dateien
- Mit Stern markiert
- Papierkorb
- Speichernutzung

**Hauptbereich:**
- Breadcrumb-Navigation
- Datei-/Ordner-Raster- oder Listenansicht
- Dateivorschau
- Aktionssymbolleiste

**Rechter Bereich (wenn Datei ausgewählt):**
- Dateidetails
- Vorschau
- Versionsverlauf
- Freigabeeinstellungen
- Aktivitätsprotokoll

## Ordnerstruktur

**Standardordner:**
- Dokumente
- Fotos
- Berichte
- Vorlagen
- Abteilungsordner
- Freigegebene Ordner

**Ordner erstellen:**
1. Klicken Sie auf **+ Neuer Ordner**
2. Ordner benennen
3. Berechtigungen festlegen
4. Speichern

**Ordneraktionen:**
- Umbenennen
- Verschieben
- Freigeben
- Farbe/Symbol festlegen
- Löschen

## Dateien hochladen

**Hochladen:**
1. Klicken Sie auf **Hochladen**-Schaltfläche
2. Dateien auswählen oder Drag & Drop
3. Dateien werden mit Fortschrittsbalken hochgeladen

**Upload-Limits:**
- Max. Dateigröße: 100MB (konfigurierbar)
- Mehrere Dateien unterstützt
- Ordner-Upload unterstützt (Chrome)

**Unterstützte Formate:**
- Dokumente: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX
- Bilder: JPG, PNG, GIF, SVG, WebP
- Archive: ZIP, RAR, 7Z
- Text: TXT, CSV, JSON, XML, MD
- Sonstiges: Alle Dateitypen (sofern nicht blockiert)

## Dateiverwaltung

**Dateiaktionen:**
- **Herunterladen**: Datei herunterladen
- **Freigeben**: Mit Benutzern teilen
- **Verschieben**: In Ordner verschieben
- **Kopieren**: Datei duplizieren
- **Umbenennen**: Dateinamen ändern
- **Löschen**: In Papierkorb verschieben
- **Stern**: Zu Favoriten hinzufügen
- **Info**: Details anzeigen

**Massenaktionen:**
- Mehrere Dateien auswählen (Strg+Klick)
- Als ZIP herunterladen
- Alle verschieben
- Alle freigeben
- Alle löschen

## Dateifreigabe

**Datei freigeben:**
1. Rechtsklick auf Datei → **Freigeben**
2. Benutzer/Gruppen hinzufügen:
   - Benutzer auswählen
   - Abteilungen auswählen
   - Rollen auswählen
3. Berechtigungen festlegen:
   - Kann anzeigen
   - Kann herunterladen
   - Kann bearbeiten
   - Kann freigeben
4. Ablauf festlegen (optional)
5. Benachrichtigung senden
6. Freigeben

**Link freigeben:**
- Öffentlichen Link generieren
- Passwortschutz festlegen
- Ablaufdatum festlegen
- Aufrufe/Downloads verfolgen
- Link jederzeit widerrufen

**Für mich freigegeben:**
- Mit Ihnen geteilte Dateien anzeigen
- Freigaben annehmen/ablehnen
- Zugriff auf Dateien anfordern

## Dateiversionen

**Versionsverlauf:**
- Automatische Versionierung
- Alle Versionen anzeigen
- Vorherige Version wiederherstellen
- Versionen vergleichen
- Spezifische Version herunterladen
- Alte Versionen löschen

**Versionsaktionen:**
- **Wiederherstellen**: Diese Version zur aktuellen machen
- **Herunterladen**: Diese Version herunterladen
- **Vorschau**: Diese Version anzeigen
- **Löschen**: Diese Version entfernen

## Dateisuche

**Suchfunktionen:**
- Nach Dateinamen suchen
- Nach Inhalt suchen (Textdateien)
- Nach Dateityp suchen
- Nach Datum suchen
- Nach Eigentümer suchen
- Nach Ordner suchen

**Erweiterte Filter:**
- Dateityp
- Datumsbereich
- Dateigröße
- Freigegeben/nicht freigegeben
- Geändert von
- Tags

## Dateivorschau

**Vorschau unterstützt:**
- PDF: Vollständige Vorschau
- Bilder: Vollständige Vorschau
- Office-Dokumente: Vorschau (falls konfiguriert)
- Textdateien: Inhalt anzeigen
- Videos: Videoplayer
- Audio: Audioplayer

**Vorschauaktionen:**
- Vollbild
- Herunterladen
- Freigeben
- Drucken
- Bearbeiten (falls unterstützt)

## Berechtigungen

**Berechtigungsstufen:**
- **Eigentümer**: Volle Kontrolle
- **Editor**: Bearbeiten/Hochladen/Löschen
- **Mitwirkender**: Nur hochladen
- **Betrachter**: Nur anzeigen/herunterladen
- **Kommentator**: Anzeigen und kommentieren

**Ordnerberechtigungen:**
- Von übergeordnetem Element erben
- Benutzerdefinierte Berechtigungen
- Rollenbasierter Zugriff
- Abteilungszugriff

## Speicherverwaltung

**Speicherinformationen:**
- Zugewiesener Gesamtspeicher
- Genutzter Speicher
- Speicher nach Dateityp
- Größte Dateien
- Älteste Dateien
- Duplikate

**Bereinigungstools:**
- Große Dateien finden
- Alte Dateien finden
- Duplikate finden
- Papierkorb leeren
- Dateien komprimieren
- Alte Dateien archivieren

## Papierkorb & Wiederherstellung

**Papierkorb:**
- Gelöschte Dateien 30 Tage aufbewahrt
- Alle gelöschten Dateien anzeigen
- Nach Löschdatum filtern
- Nach Größe/Name/Datum sortieren

**Dateien wiederherstellen:**
1. Papierkorb öffnen
2. Datei(en) auswählen
3. Klicken Sie auf **Wiederherstellen**
4. Datei kehrt zum ursprünglichen Ort zurück

**Endgültig löschen:**
- Papierkorb leeren (alle Dateien)
- Bestimmte Dateien löschen
- Kann nicht rückgängig gemacht werden

## Best Practices

**Tun Sie:**
✅ Dateien in Ordnern organisieren
✅ Aussagekräftige Dateinamen verwenden
✅ Freigeben statt große Dateien per E-Mail versenden
✅ Alte Dateien regelmäßig aufräumen
✅ Versionskontrolle verwenden
✅ Angemessene Berechtigungen festlegen

**Tun Sie nicht:**
❌ Sensible Daten ohne Verschlüsselung hochladen
❌ Ohne Passwort öffentlich freigeben
❌ Unnötige Dateien aufbewahren
❌ Speicherlimits ignorieren
❌ Ohne Verschieben in Papierkorb löschen

## Tastaturkürzel
- **Strg+U**: Datei hochladen
- **Strg+N**: Neuer Ordner
- **Strg+F**: Suchen
- **Strg+A**: Alles auswählen
- **Strg+C**: Kopieren
- **Strg+V**: Einfügen
- **Strg+X**: Ausschneiden
- **Entf**: In Papierkorb verschieben
- **Rücktaste**: Eine Ebene nach oben

---

## Nächste Schritte
- [📄 Dokumente](/guide/documents)
- [📋 Berichte](/guide/reports)
- [📁 Personenakten](/guide/person-files)
