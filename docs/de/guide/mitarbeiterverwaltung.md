# Mitarbeiterverwaltung

Das Mitarbeiterverwaltungsmodul ermöglicht Ihnen die Verwaltung aller Mitarbeiterdatensätze, einschließlich persönlicher Informationen, Abteilungen, Dienstgrade, Schulungen, Lizenzen und mehr.

## Überblick

Das Mitarbeiterverwaltungssystem bietet:
- Vollständige Mitarbeiterprofile
- Abteilungszuweisungen
- Dienstgrad- und Beförderungsverfolgung
- Schulungs- und Zertifizierungsverwaltung
- Lizenzverfolgung
- Urlaubsverwaltung
- Unternehmenszuweisungen
- Benutzerdefinierte Felder pro Behörde

## Anforderungen

**Erforderliche Berechtigungen:**
- `READ_EMPLOYEE` (zum Anzeigen von Mitarbeitern)
- `WRITE_EMPLOYEE` (zum Erstellen/Bearbeiten)
- `DELETE_EMPLOYEE` (zum Löschen)
- `ADMIN_READ_EMPLOYEE` (für Admin-Konfiguration)

**Admin-Einstellungen:**
- Mitarbeiterkonfiguration: Administration → Mitarbeiter → Einstellungen (`/admin/employees`)
- Abteilungen, Dienstgrade, Lizenzen im Admin-Panel konfiguriert

## Mitarbeiterverwaltung öffnen

### Vom Desktop
1. Doppelklicken Sie auf das **Mitarbeiter**-Symbol auf dem Desktop
2. Oder klicken Sie auf Startmenü → Mitarbeiterverwaltung → Mitarbeiter

### Admin-Konfigurationsmenü
Um die Systemeinstellungen für Mitarbeiter zu konfigurieren:
1. **Desktop:** Start → Administration → Mitarbeiter → Einstellungen
2. **Direkter Pfad:** `/admin/employees`
3. **Konfigurieren:**
   - Abteilungen
   - Dienstgrade und Positionen
   - Lizenztypen
   - Benutzerdefinierte Felder
   - Mitarbeiternummernformat

## Mitarbeiterlistenansicht

### Hauptoberfläche

Die Mitarbeiterliste zeigt alle Mitarbeiter in einer Datentabelle mit:
- **ID**: Mitarbeiter-Identifikationsnummer
- **Name**: Vor- und Nachname
- **Dienstgrad**: Aktueller Dienstgrad/Position
- **Abteilung**: Zugewiesene Abteilung(en)
- **Status**: Aktiv/Inaktiv
- **Aktionen**: Bearbeiten-, Anzeigen-, Löschen-Buttons

### Toolbar-Aktionen

**Obere Toolbar:**
- 🔍 **Suchen**: Suche nach Name, ID oder Abteilung
- ➕ **Mitarbeiter hinzufügen**: Neuen Mitarbeiter erstellen
- 🔄 **Aktualisieren**: Mitarbeiterliste neu laden
- 📊 **Exportieren**: Nach Excel/PDF exportieren
- 🔽 **Filter**: Nach Abteilung, Dienstgrad, Status filtern

### Mitarbeiter filtern

**Nach Abteilung:**
1. Klicken Sie auf **Filter**-Dropdown
2. Wählen Sie **Abteilung**
3. Wählen Sie eine oder mehrere Abteilungen
4. Klicken Sie auf **Anwenden**

**Nach Dienstgrad:**
1. Klicken Sie auf **Filter**-Dropdown
2. Wählen Sie **Dienstgrad**
3. Wählen Sie Dienstgradlevel
4. Klicken Sie auf **Anwenden**

**Nach Status:**
1. Klicken Sie auf **Filter**-Dropdown
2. Wählen Sie **Status**
3. Wählen Sie Aktiv/Inaktiv/Alle
4. Klicken Sie auf **Anwenden**

**Filter löschen:**
- Klicken Sie auf die Schaltfläche **Alle Filter löschen**

### Sortierung

Klicken Sie auf eine beliebige Spaltenüberschrift zum Sortieren:
- Erster Klick: Aufsteigend (A-Z, 0-9)
- Zweiter Klick: Absteigend (Z-A, 9-0)
- Dritter Klick: Sortierung entfernen

### Paginierung

Steuerung am unteren Rand der Tabelle:
- **Elemente pro Seite**: 10, 25, 50, 100
- **Seitennavigation**: Zurück/Weiter/Seitennummer
- **Gesamtzahl**: Zeigt Gesamtzahl der Mitarbeiter

## Neuen Mitarbeiter erstellen

### Schritt 1: Erstellungsformular öffnen

1. Klicken Sie auf die Schaltfläche **➕ Mitarbeiter hinzufügen**
2. Mitarbeiter-Erstellungsformular öffnet sich

### Schritt 2: Basisinformationen

**Pflichtfelder:**
- **Vorname**: Vorname des Mitarbeiters
- **Nachname**: Nachname des Mitarbeiters
- **Mitarbeiter-ID**: Eindeutige Kennung (automatisch generiert oder manuell)

**Optionale Felder:**
- **E-Mail**: E-Mail-Adresse
- **Telefon**: Telefonnummer
- **Mobil**: Mobiltelefonnummer
- **Geburtsdatum**: Geburtsdatum (Datumsauswahl)
- **Einstellungsdatum**: Beginn der Beschäftigung
- **Foto**: Mitarbeiterfoto hochladen

**Um ein Foto hochzuladen:**
1. Klicken Sie auf die Schaltfläche **Foto hochladen**
2. Wählen Sie eine Bilddatei aus (JPG, PNG)
3. Maximale Größe: 5MB
4. Foto erscheint in der Vorschau

### Schritt 3: Adressinformationen

**Felder:**
- **Straße**: Straßenadresse
- **Hausnummer**: Haus-/Gebäudenummer
- **Postleitzahl**: Postleitzahl
- **Stadt**: Stadtname
- **Land**: Land (Dropdown)

### Schritt 4: Abteilungszuweisung

**Zu Abteilung(en) zuweisen:**
1. Klicken Sie auf die Schaltfläche **Abteilung hinzufügen**
2. Wählen Sie Abteilung aus Dropdown
3. Wählen Sie Zuweisungsdatum
4. Klicken Sie auf **Hinzufügen**
5. Wiederholen Sie für mehrere Abteilungen

**Primäre Abteilung:**
- Aktivieren Sie **Primär** für Hauptabteilung
- Nur eine kann primär sein

**Abteilung entfernen:**
- Klicken Sie auf ❌ neben der Abteilung

### Schritt 5: Dienstgradzuweisung

**Dienstgrad zuweisen:**
1. Wählen Sie **Dienstgrad** aus Dropdown
2. Optionen: Beamter, Sergeant, Leutnant, Hauptmann, etc.
3. Geben Sie **Dienstgraddatum** ein: Wann der Dienstgrad zugewiesen wurde
4. Geben Sie **Dienstgradnummer** ein: Abzeichen-/ID-Nummer (falls zutreffend)

### Schritt 6: Lizenzzuweisung

**Lizenzen hinzufügen:**
1. Klicken Sie auf die Schaltfläche **Lizenz hinzufügen**
2. Wählen Sie Lizenztyp (Führerschein, Zertifizierung, etc.)
3. Geben Sie **Ausstellungsdatum** ein
4. Geben Sie **Ablaufdatum** ein
5. Laden Sie **Lizenzdokument** hoch (optional)
6. Klicken Sie auf **Hinzufügen**

**Lizenzen verwalten:**
- **Bearbeiten**: Klicken Sie auf Stiftsymbol
- **Löschen**: Klicken Sie auf Papierkorbsymbol
- **Dokument anzeigen**: Klicken Sie auf Dokumentsymbol

**Lizenzablauf-Benachrichtigung:**
- System sendet Benachrichtigung 30 Tage vor Ablauf
- Roter Indikator zeigt abgelaufene Lizenzen

### Schritt 7: Schulungszuweisung

**Schulung hinzufügen:**
1. Klicken Sie auf die Schaltfläche **Schulung hinzufügen**
2. Wählen Sie Schulung aus Dropdown (erstellt unter Admin → Schulung)
3. Geben Sie **Abschlussdatum** ein
4. Geben Sie **Punktzahl** ein (falls zutreffend)
5. Laden Sie **Zertifikat** hoch (optional)
6. Klicken Sie auf **Hinzufügen**

**Schulungsstatus:**
- ✅ **Abgeschlossen**: Schulung beendet
- 🔄 **In Bearbeitung**: Derzeit in Schulung
- ❌ **Nicht bestanden**: Nicht bestanden
- ⏰ **Geplant**: Zukünftige Schulung

### Schritt 8: Unternehmenszuweisung

**Zu Unternehmen zuweisen:**
1. Klicken Sie auf die Schaltfläche **Unternehmen hinzufügen**
2. Wählen Sie Unternehmen aus Dropdown
3. Geben Sie **Startdatum** ein
4. Geben Sie **Enddatum** ein (optional - für temporäre Zuweisungen)
5. Wählen Sie **Rolle** im Unternehmen
6. Klicken Sie auf **Hinzufügen**

**Mehrere Unternehmen:**
- Mitarbeiter kann mehreren Unternehmen zugewiesen werden
- Jede Zuweisung hat eigene Daten und Rolle

### Schritt 9: Benutzerdefinierte Felder

Wenn Ihre Behörde benutzerdefinierte Felder konfiguriert hat:

1. Scrollen Sie zum Abschnitt **Benutzerdefinierte Felder**
2. Füllen Sie behördenspezifische Felder aus:
   - Textfelder
   - Zahlenfelder
   - Datumsfelder
   - Dropdown-Auswahl
   - Checkboxen

**Beispiele für benutzerdefinierte Felder:**
- Abzeichennummer
- Schließfachnummer
- Uniformgröße
- Sicherheitsfreigabestufe
- Notfallkontakt

### Schritt 10: Notizen

**Interne Notizen hinzufügen:**
1. Scrollen Sie zum Abschnitt **Notizen**
2. Geben Sie Notizen in Textbereich ein
3. Notizen sind nur intern (nicht für Mitarbeiter sichtbar)

### Schritt 11: Mitarbeiter speichern

**Speicheroptionen:**
1. **Speichern**: Mitarbeiter erstellen und Formular schließen
2. **Speichern & Neu**: Mitarbeiter erstellen und neues Formular öffnen
3. **Abbrechen**: Änderungen verwerfen

**Validierung:**
- Pflichtfelder müssen ausgefüllt sein
- E-Mail muss gültiges Format haben
- Telefonnummern müssen gültig sein
- Daten müssen logisch sein (Einstellungsdatum vor heute, etc.)

## Mitarbeiter bearbeiten

### Mitarbeiter zum Bearbeiten öffnen

**Methode 1: Aus Liste**
1. Finden Sie Mitarbeiter in Liste
2. Klicken Sie auf **Bearbeiten**-Schaltfläche (Stiftsymbol)

**Methode 2: Aus Detailansicht**
1. Klicken Sie auf Mitarbeiternamen, um Detailansicht zu öffnen
2. Klicken Sie oben auf **Bearbeiten**-Schaltfläche

### Bearbeitungsformular

Gleiches Formular wie Erstellung, aber mit vorausgefüllten vorhandenen Daten.

**Abschnitte bearbeiten:**
- Basisinformationen
- Adresse
- Abteilungen
- Dienstgrad
- Lizenzen
- Schulungen
- Unternehmenszuweisungen
- Benutzerdefinierte Felder
- Notizen

**Änderungen speichern:**
- Klicken Sie auf **Speichern**-Schaltfläche
- Änderungen werden sofort gespeichert
- Verlauf wird verfolgt (siehe unten)

## Mitarbeiterdetailansicht

Klicken Sie auf Mitarbeiternamen in Liste, um vollständiges Profil anzuzeigen.

### Tabs

**Übersicht-Tab:**
- Foto
- Basisinformationen
- Aktueller Dienstgrad und Abteilung
- Kontaktinformationen
- Schnellstatistiken

**Abteilungen-Tab:**
- Liste aller Abteilungszuweisungen
- Verlauf der Abteilungen
- Abteilungen hinzufügen/entfernen

**Schulungen-Tab:**
- Abgeschlossene Schulungen
- Laufende Schulungen
- Geplante Schulungen
- Schulungszertifikate
- Neue Schulung hinzufügen

**Lizenzen-Tab:**
- Aktive Lizenzen
- Abgelaufene Lizenzen
- Ablaufdaten
- Lizenzdokumente
- Neue Lizenz hinzufügen

**Beförderungen-Tab:**
- Beförderungsverlauf
- Dienstgradänderungen
- Daten und Gründe
- Beförderungsdokumente

**Urlaub-Tab:**
- Urlaubsanträge
- Genehmigte Urlaube
- Abgelehnte Urlaube
- Urlaubsguthaben
- Neuen Urlaub beantragen

**Unternehmen-Tab:**
- Unternehmenszuweisungen
- Aktuell und vergangen
- Rollen bei jedem Unternehmen
- Zuweisungsdaten

**Verlauf-Tab:**
- Alle Änderungen am Mitarbeiterdatensatz
- Wer Änderungen vorgenommen hat
- Wann Änderungen vorgenommen wurden
- Was geändert wurde (vorher/nachher)

**Dokumente-Tab:**
- Hochgeladene Dokumente
- Verträge
- Zertifikate
- Andere Dateien
- Neue Dokumente hochladen

## Abteilungsverwaltung

### Abteilungen erstellen

**Aus Admin-Panel:**
1. Öffnen Sie Startmenü → Administration → Mitarbeiteradministration → Abteilungen
2. Klicken Sie auf **Abteilung hinzufügen**
3. Geben Sie **Name** ein
4. Geben Sie **Beschreibung** ein
5. Wählen Sie **Übergeordnete Abteilung** (für Unterabteilungen)
6. Wählen Sie **Abteilungsleiter** (Mitarbeiter)
7. Klicken Sie auf **Speichern**

**Abteilungshierarchie:**
```
Polizeiabteilung
├── Streifendienst
│   ├── Tagschicht
│   └── Nachtschicht
├── Kriminalpolizei
└── Administration
```

### Zu Abteilungen zuweisen

**Aus Mitarbeiterbearbeitung:**
1. Öffnen Sie Mitarbeiter
2. Gehen Sie zum **Abteilungen**-Tab
3. Klicken Sie auf **Abteilung hinzufügen**
4. Wählen Sie Abteilung
5. Wählen Sie Datum
6. Speichern

**Massenzuweisung:**
1. Gehen Sie zur Abteilungsliste
2. Öffnen Sie Abteilung
3. Klicken Sie auf **Mitglieder hinzufügen**
4. Wählen Sie mehrere Mitarbeiter
5. Klicken Sie auf **Ausgewählte hinzufügen**

## Dienstgradverwaltung

### Dienstgrade erstellen

**Aus Admin-Panel:**
1. Öffnen Sie Administration → Mitarbeiteradministration → Dienstgrade
2. Klicken Sie auf **Dienstgrad hinzufügen**
3. Geben Sie **Name** ein (z.B. "Beamter", "Sergeant")
4. Geben Sie **Stufe** ein (1-10, für Hierarchie)
5. Geben Sie **Gehaltsgruppe** ein (optional)
6. Wählen Sie **Farbe** (für visuelle Identifikation)
7. Laden Sie **Symbol** hoch (optional)
8. Klicken Sie auf **Speichern**

**Dienstgradhierarchie:**
- Stufe 1: Beamter
- Stufe 2: Oberbeamter
- Stufe 3: Sergeant
- Stufe 4: Leutnant
- Stufe 5: Hauptmann
- Stufe 6: Kommandant
- Stufe 7: Chef

### Mitarbeiter befördern

**Methode 1: Aus Mitarbeiterprofil**
1. Öffnen Sie Mitarbeiter
2. Klicken Sie auf **Befördern**-Schaltfläche
3. Wählen Sie neuen Dienstgrad
4. Geben Sie Beförderungsdatum ein
5. Geben Sie Grund ein
6. Laden Sie Beförderungsdokument hoch (optional)
7. Klicken Sie auf **Speichern**

**Methode 2: Massenbeförderung**
1. Gehen Sie zur Mitarbeiterliste
2. Wählen Sie mehrere Mitarbeiter (Checkbox)
3. Klicken Sie auf **Aktionen** → **Befördern**
4. Wählen Sie neuen Dienstgrad
5. Geben Sie Datum ein
6. Klicken Sie auf **Auf Ausgewählte anwenden**

**Beförderungsverlauf:**
- Alle Beförderungen werden verfolgt
- Ansicht im **Beförderungen**-Tab des Mitarbeiters
- Zeigt Datum, alter Dienstgrad, neuer Dienstgrad, Grund

## Lizenzverwaltung

### Lizenztypen erstellen

**Aus Admin-Panel:**
1. Öffnen Sie Administration → Mitarbeiteradministration → Lizenzen
2. Klicken Sie auf **Lizenztyp hinzufügen**
3. Geben Sie **Name** ein (z.B. "Führerschein")
4. Geben Sie **Beschreibung** ein
5. Setzen Sie **Gültigkeitsdauer** (Monate/Jahre)
6. Setzen Sie **Erneuerung erforderlich** (ja/nein)
7. Klicken Sie auf **Speichern**

**Gängige Lizenztypen:**
- Führerschein
- Waffenschein-Zertifizierung
- Erste-Hilfe-Zertifizierung
- CPR-Zertifizierung
- Spezialausrüstungslizenz
- Sicherheitsfreigabe

### Lizenzen zuweisen

**Aus Mitarbeiterprofil:**
1. Öffnen Sie Mitarbeiter
2. Gehen Sie zum **Lizenzen**-Tab
3. Klicken Sie auf **Lizenz hinzufügen**
4. Wählen Sie Lizenztyp
5. Geben Sie Ausstellungsdatum ein
6. Geben Sie Ablaufdatum ein
7. Laden Sie Dokument hoch
8. Klicken Sie auf **Speichern**

**Lizenzerneuerung:**
1. Öffnen Sie abgelaufene Lizenz
2. Klicken Sie auf **Erneuern**
3. Geben Sie neues Ausstellungsdatum ein
4. Geben Sie neues Ablaufdatum ein
5. Laden Sie neues Dokument hoch
6. Klicken Sie auf **Speichern**

**Ablaufbenachrichtigungen:**
- System sendet automatisch Benachrichtigungen:
  - 30 Tage vor Ablauf
  - 14 Tage vor Ablauf
  - 7 Tage vor Ablauf
  - Am Ablauftag

## Schulungsverwaltung

### Schulungsprogramme erstellen

**Aus Admin-Panel:**
1. Öffnen Sie Administration → Schulungsverwaltung → Schulungsprogramme
2. Klicken Sie auf **Schulung hinzufügen**
3. Geben Sie **Name** ein
4. Geben Sie **Beschreibung** ein
5. Setzen Sie **Dauer** (Stunden/Tage)
6. Setzen Sie **Erforderlich** (ja/nein)
7. Wählen Sie **Kategorie**
8. Setzen Sie **Bestehende Punktzahl** (falls Test enthalten)
9. Laden Sie **Schulungsmaterialien** hoch
10. Klicken Sie auf **Speichern**

**Schulungskategorien:**
- Waffenausbildung
- Verteidigungstaktiken
- Erste Hilfe/CPR
- Rechtsaktualisierungen
- Gerätetraining
- Führungsentwicklung
- Kommunikationsfähigkeiten

### Schulung zuweisen

**Einzelzuweisung:**
1. Öffnen Sie Mitarbeiter
2. Gehen Sie zum **Schulungen**-Tab
3. Klicken Sie auf **Schulung zuweisen**
4. Wählen Sie Schulungsprogramm
5. Wählen Sie Ausbilder (optional)
6. Geben Sie geplantes Datum ein
7. Klicken Sie auf **Zuweisen**

**Massenzuweisung:**
1. Öffnen Sie Schulungsprogramm
2. Klicken Sie auf **Zu Mitarbeitern zuweisen**
3. Wählen Sie mehrere Mitarbeiter
4. Wählen Sie geplantes Datum
5. Klicken Sie auf **Alle zuweisen**

**Abteilungsweite Zuweisung:**
1. Öffnen Sie Schulungsprogramm
2. Klicken Sie auf **Zu Abteilung zuweisen**
3. Wählen Sie Abteilung
4. Alle Mitarbeiter in Abteilung werden zugewiesen

### Schulungsabschluss erfassen

**Aus Mitarbeiter-Schulungen-Tab:**
1. Finden Sie Schulung in Liste
2. Klicken Sie auf **Als abgeschlossen markieren**
3. Geben Sie Abschlussdatum ein
4. Geben Sie Punktzahl ein (falls zutreffend)
5. Laden Sie Zertifikat hoch
6. Klicken Sie auf **Speichern**

**Schulungsstatusfarben:**
- 🟢 Grün: Abgeschlossen
- 🟡 Gelb: In Bearbeitung
- 🔴 Rot: Überfällig
- ⚪ Grau: Geplant

## Urlaubsverwaltung

### Urlaub beantragen

**Aus Mitarbeiterprofil:**
1. Öffnen Sie Mitarbeiter
2. Gehen Sie zum **Urlaub**-Tab
3. Klicken Sie auf **Urlaub beantragen**
4. Wählen Sie **Startdatum**
5. Wählen Sie **Enddatum**
6. Geben Sie **Grund** ein
7. Geben Sie **Notizen** ein (optional)
8. Klicken Sie auf **Absenden**

**Urlaubsarten:**
- Jahresurlaub
- Krankheitsurlaub
- Persönlicher Tag
- Unbezahlter Urlaub
- Ausgleichszeit

### Urlaub genehmigen

**Aus Urlaubsliste:**
1. Öffnen Sie Administration → Mitarbeiteradministration → Urlaubsanträge
2. Finden Sie ausstehenden Antrag
3. Klicken Sie auf **Überprüfen**
4. Details anzeigen
5. Klicken Sie auf **Genehmigen** oder **Ablehnen**
6. Geben Sie Notizen ein (bei Ablehnung)
7. Klicken Sie auf **Bestätigen**

**Benachrichtigungen:**
- Mitarbeiter erhält Benachrichtigung über Entscheidung
- Vorgesetzter erhält Benachrichtigung über Antrag

### Urlaubsguthaben

**Guthaben anzeigen:**
1. Öffnen Sie Mitarbeiter
2. Gehen Sie zum **Urlaub**-Tab
3. Guthaben oben angezeigt:
   - Verfügbare Gesamttage
   - Verbrauchte Tage
   - Verbleibende Tage
   - Ausstehende Tage

**Guthaben anpassen:**
1. Klicken Sie auf **Guthaben anpassen**
2. Geben Sie Anpassungsbetrag ein (+/-)
3. Geben Sie Grund ein
4. Klicken Sie auf **Speichern**

## Unternehmenszuweisungen

### Zu Unternehmen zuweisen

**Zweck:**
- Verfolgen, welche Mitarbeiter mit welchen Unternehmen arbeiten
- Temporäre Zuweisungen verwalten
- Rollen bei verschiedenen Unternehmen verfolgen

**Aus Mitarbeiterprofil:**
1. Gehen Sie zum **Unternehmen**-Tab
2. Klicken Sie auf **Unternehmenszuweisung hinzufügen**
3. Wählen Sie Unternehmen
4. Geben Sie Startdatum ein
5. Geben Sie Enddatum ein (optional)
6. Wählen Sie Rolle
7. Geben Sie Notizen ein
8. Klicken Sie auf **Speichern**

**Rollenoptionen:**
- Hauptansprechpartner
- Account Manager
- Technischer Support
- Projektmanager
- Berater
- Benutzerdefinierte Rollen...

### Unternehmenszuweisungen anzeigen

**Aus Unternehmensansicht:**
1. Öffnen Sie Unternehmensprofil
2. Gehen Sie zum **Zugewiesene Mitarbeiter**-Tab
3. Zeigen Sie alle zugewiesenen Mitarbeiter an
4. Sehen Sie deren Rollen und Daten

## Benutzerdefinierte Felder

Benutzerdefinierte Felder ermöglichen es Behörden, spezifische Felder zu Mitarbeiterdatensätzen hinzuzufügen.

### Benutzerdefinierte Felder erstellen (Admin)

**Aus Admin-Panel:**
1. Öffnen Sie Administration → Behördeneinstellungen → Benutzerdefinierte Felder
2. Klicken Sie auf **Benutzerdefiniertes Feld hinzufügen**
3. Wählen Sie **Entität**: Mitarbeiter
4. Geben Sie **Feldname** ein
5. Wählen Sie **Feldtyp**:
   - Text
   - Zahl
   - Datum
   - Dropdown
   - Checkbox
   - Mehrfachauswahl
   - Datei-Upload
6. Setzen Sie **Erforderlich** (ja/nein)
7. Setzen Sie **Sichtbar** (ja/nein)
8. Geben Sie **Standardwert** ein (optional)
9. Für Dropdowns: Geben Sie Optionen ein (eine pro Zeile)
10. Klicken Sie auf **Speichern**

### Benutzerdefinierte Felder verwenden

**Im Mitarbeiterformular:**
1. Benutzerdefinierte Felder erscheinen in dediziertem Abschnitt
2. Füllen Sie nach Bedarf aus
3. Erforderliche Felder müssen ausgefüllt werden
4. Werte werden mit Mitarbeiterdatensatz gespeichert

**Beispiele für benutzerdefinierte Felder:**
- Abzeichennummer
- Schließfachnummer
- Funk-ID
- Uniformgröße
- Parkplatz
- Sicherheitsfreigabestufe
- Name des Notfallkontakts
- Telefon des Notfallkontakts

## Suche und Filter

### Basissuche

**Suchfeld:**
1. Tippen Sie in Suchfeld oben
2. Durchsucht:
   - Vorname
   - Nachname
   - Mitarbeiter-ID
   - E-Mail
   - Telefonnummer

**Echtzeitresultate:**
- Ergebnisse aktualisieren sich während Sie tippen
- Hebt übereinstimmenden Text hervor

### Erweiterte Suche

**Erweiterte Suche öffnen:**
1. Klicken Sie auf **Erweiterte Suche**-Schaltfläche
2. Erweitertes Suchformular öffnet sich

**Suchkriterien:**
- Name
- Mitarbeiter-ID
- Abteilung
- Dienstgrad
- Einstellungsdatumsbereich
- Status
- Lizenztyp
- Schulungsstatus
- Unternehmenszuweisung

**Filter kombinieren:**
- Alle ausgewählten Filter werden mit UND kombiniert
- Ergebnisse müssen alle Kriterien erfüllen

**Suche speichern:**
1. Suchkriterien konfigurieren
2. Klicken Sie auf **Suche speichern**
3. Geben Sie Namen ein
4. Klicken Sie auf **Speichern**
5. Zugriff über **Gespeicherte Suchen**-Dropdown

## Berichte und Export

### Mitarbeiterberichte

**Verfügbare Berichte:**

**1. Mitarbeiterlistenbericht**
- Alle Mitarbeiter mit Hauptinformationen
- Konfigurierbare Spalten
- Export nach Excel/PDF

**2. Abteilungsbericht**
- Nach Abteilung gruppierte Mitarbeiter
- Abteilungsstatistiken
- Mitarbeiteranzahl pro Abteilung

**3. Schulungsbericht**
- Schulungsabschlussstatus
- Überfällige Schulungen
- Bevorstehende Schulungen

**4. Lizenzbericht**
- Aktive Lizenzen
- Ablaufende Lizenzen
- Abgelaufene Lizenzen

**5. Urlaubsbericht**
- Urlaubsguthaben
- Verbrauchte Urlaubstage
- Ausstehende Anträge

### Berichte erstellen

**Schritte:**
1. Klicken Sie auf **Berichte**-Schaltfläche
2. Wählen Sie Berichtstyp
3. Optionen konfigurieren:
   - Datumsbereich
   - Abteilungen
   - Felder ein-/ausschließen
4. Wählen Sie Format (PDF/Excel)
5. Klicken Sie auf **Erstellen**
6. Bericht wird automatisch heruntergeladen

### Daten exportieren

**Mitarbeiterliste exportieren:**
1. Filter anwenden (optional)
2. Klicken Sie auf **Exportieren**-Schaltfläche
3. Wählen Sie Format:
   - Excel (.xlsx)
   - CSV (.csv)
   - PDF (.pdf)
4. Klicken Sie auf **Herunterladen**

**Exportoptionen:**
- Alle Mitarbeiter
- Nur gefilterte Mitarbeiter
- Nur ausgewählte Mitarbeiter (Checkbox)

## Massenaktionen

### Mehrere Mitarbeiter auswählen

**Mitarbeiter auswählen:**
1. Aktivieren Sie Box neben jedem Mitarbeiter
2. Oder aktivieren Sie Box im Header, um alle auf Seite auszuwählen
3. Anzahl der Ausgewählten zeigt oben

**Verfügbare Massenaktionen:**

**1. Massenbearbeitung**
- Klicken Sie auf **Aktionen** → **Ausgewählte bearbeiten**
- Gemeinsame Felder für alle ändern:
  - Abteilung
  - Dienstgrad
  - Status
- Klicken Sie auf **Anwenden**

**2. Massenlöschung**
- Klicken Sie auf **Aktionen** → **Ausgewählte löschen**
- Löschung bestätigen
- Alle ausgewählten Mitarbeiter gelöscht

**3. Massenexport**
- Klicken Sie auf **Aktionen** → **Ausgewählte exportieren**
- Wählen Sie Format
- Datei herunterladen

**4. Massenschulungszuweisung**
- Klicken Sie auf **Aktionen** → **Schulung zuweisen**
- Wählen Sie Schulungsprogramm
- Alle ausgewählten Mitarbeiter werden zugewiesen

**5. Massenabteilungszuweisung**
- Klicken Sie auf **Aktionen** → **Abteilung zuweisen**
- Wählen Sie Abteilung
- Wählen Sie Datum
- Alle ausgewählten Mitarbeiter zugewiesen

## Mitarbeiterstatus

### Statustypen

**Aktiv:**
- Derzeit beschäftigt
- Voller Zugriff auf alle Funktionen
- Wird in normalen Listen angezeigt

**Inaktiv:**
- Nicht mehr beschäftigt
- Eingeschränkter Zugriff
- Aus normalen Listen ausgeblendet (mit Filter anzeigen)

**Im Urlaub:**
- Vorübergehend abwesend
- Konto bleibt aktiv
- Spezielle Anzeige in Listen

**Suspendiert:**
- Vorübergehend suspendiert
- Kein Systemzugriff
- Spezielle Anzeige

### Status ändern

**Aus Mitarbeiterprofil:**
1. Klicken Sie auf **Status ändern**
2. Wählen Sie neuen Status
3. Geben Sie Datum des Inkrafttretens ein
4. Geben Sie Grund ein
5. Klicken Sie auf **Speichern**

**Benachrichtigungen:**
- Mitarbeiter wird über Statusänderung benachrichtigt
- Vorgesetzter wird benachrichtigt
- Personalabteilung wird benachrichtigt

## Berechtigungen

Die Mitarbeiterverwaltung respektiert das Berechtigungssystem.

**Erforderliche Berechtigungen:**

| Aktion | Berechtigung |
|--------|-------------|
| Mitarbeiter anzeigen | `READ_EMPLOYEE` |
| Mitarbeiter erstellen | `WRITE_EMPLOYEE` |
| Mitarbeiter bearbeiten | `WRITE_EMPLOYEE` |
| Mitarbeiter löschen | `DELETE_EMPLOYEE` |
| Sensible Daten anzeigen | `READ_EMPLOYEE_SENSITIVE` |
| Abteilungen verwalten | `ADMIN_EMPLOYEE_DEPARTMENTS` |
| Dienstgrade verwalten | `ADMIN_EMPLOYEE_RANKS` |

**Berechtigungsprüfungen:**
- Schaltflächen ausgeblendet, wenn keine Berechtigung
- API-Aufrufe blockiert, wenn keine Berechtigung
- Fehlermeldung bei nicht autorisierter Aktion

## Best Practices

### Dateneingabe

✅ **Tun:**
- Vollständige Informationen eingeben
- Konsistente Formatierung verwenden
- Unterstützende Dokumente hochladen
- Notizen für Kontext hinzufügen
- Informationen aktuell halten

❌ **Nicht tun:**
- Pflichtfelder leer lassen
- Abkürzungen inkonsistent verwenden
- Abteilungszuweisungen vergessen
- Schulungsaufzeichnungen überspringen

### Organisation

✅ **Tun:**
- Abteilungshierarchie verwenden
- Mitarbeiter angemessen taggen
- Beförderungsverlauf führen
- Alle Schulungen verfolgen
- Alles dokumentieren

❌ **Nicht tun:**
- Doppelte Abteilungen erstellen
- Dienstgradzuweisungen überspringen
- Lizenzablaufdaten vergessen
- Schulungsanforderungen ignorieren

### Wartung

**Regelmäßige Aufgaben:**
- Mitarbeiterinformationen monatlich überprüfen
- Abteilungszuweisungen aktualisieren
- Lizenzablaufdaten prüfen
- Schulungsabschluss überprüfen
- Inaktive Mitarbeiter bereinigen

## Fehlerbehebung

### Kann Mitarbeiter nicht finden

**Lösungen:**
1. Schreibweise der Suche prüfen
2. Alle Filter löschen
3. Prüfen, ob Mitarbeiter inaktiv ist
4. Erweiterte Suche verwenden
5. Nach Mitarbeiter-ID suchen

### Kann Mitarbeiter nicht bearbeiten

**Mögliche Ursachen:**
- Fehlende `WRITE_EMPLOYEE`-Berechtigung
- Mitarbeiter ist in gesperrtem Zustand
- Ihre Behörde stimmt nicht überein

**Lösung:**
- Berechtigung vom Admin anfordern
- Mitarbeiterstatus prüfen
- Systemadministrator kontaktieren

### Schulung wird nicht angezeigt

**Mögliche Ursachen:**
- Schulung nicht zugewiesen
- Falscher Filter angewendet
- Status ist archiviert

**Lösung:**
- Schulungszuweisungen prüfen
- Filter löschen
- Schulungsstatus überprüfen

### Lizenz-Upload fehlgeschlagen

**Mögliche Ursachen:**
- Datei zu groß (>5MB)
- Falsches Dateiformat
- Netzwerkfehler

**Lösung:**
- Datei komprimieren
- In PDF/JPG konvertieren
- Erneut versuchen
- Internetverbindung prüfen

## Tipps und Tricks

### Tastaturkürzel

- **Strg+F**: Schnellsuche
- **Strg+N**: Neuer Mitarbeiter
- **Strg+E**: Ausgewählten Mitarbeiter bearbeiten
- **Esc**: Dialoge schließen

### Schnellaktionen

- Doppelklicken Sie auf Mitarbeiterzeile zum Öffnen
- Rechtsklick für Kontextmenü
- Spaltenüberschriften ziehen zum Neuordnen
- Auf Spalte klicken zum Sortieren

### Effizienztipps

1. **Gespeicherte Suchen verwenden** für häufige Filter
2. **Massenaktionen** für mehrere Mitarbeiter
3. **Export** für Backup/Berichterstattung
4. **Benutzerdefinierte Felder** für spezifische Bedürfnisse
5. **Abteilungshierarchie** für Organisation

## Administration & Konfiguration

### Admin-Menüzugriff

**Erforderliche Berechtigung:** `ADMIN_READ_EMPLOYEE`

**Auf Admin-Einstellungen zugreifen:**
1. Desktop → Start → Administration → Mitarbeiter → Einstellungen
2. Oder direkte Navigation: `/admin/employees`

### Route: `/admin/employees`

**Konfiguration des Mitarbeitersystems:**
Dieses Admin-Panel ermöglicht die Konfiguration von:
- Abteilungen und Hierarchie
- Dienstgrade und Positionen
- Lizenztypen
- Mitarbeiternummernformat
- Benutzerdefinierte Felder
- Statustypen

### Abteilungsverwaltung

**Abteilungen konfigurieren:**
1. Navigieren Sie zu `/admin/employees`
2. Gehen Sie zum **Abteilungen**-Tab

**Abteilung erstellen:**
1. Klicken Sie auf **+ Neue Abteilung**
2. Konfigurieren Sie:
   - **Name**: Abteilungsname (z.B. "Feuerwehr", "Rettungsdienst")
   - **Kurzcode**: Abkürzung (z.B. "FW", "RD")
   - **Beschreibung**: Zweck der Abteilung
   - **Farbe**: Visuelle Kennung (Hex oder Voreinstellung)
   - **Symbol**: Abteilungssymbol
   - **Übergeordnete Abteilung**: Hierarchische Struktur (optional)
   - **Aktiv**: Aktivieren/Deaktivieren
   - **Sortierreihenfolge**: Anzeigereihenfolge

**Abteilungshierarchie:**
```
Feuerwehr (Übergeordnet)
├── Einsatz
├── Schulung
└── Administration
```

**Abteilungseinstellungen:**
- Chef/Manager: Abteilungsleiter zuweisen
- Standarddienstgrad: Standard für neue Mitarbeiter
- Benachrichtigungs-E-Mails: Abteilungsweite Warnungen
- Benutzerdefinierte Felder: Abteilungsspezifische Felder

**Abteilung bearbeiten:**
- Einstellungen ändern
- Hierarchie ändern
- Zuweisungen aktualisieren
- Alle Mitarbeiter in Abteilung betroffen

**Abteilung löschen:**
- Nur wenn keine Mitarbeiter zugewiesen
- Oder zuerst Mitarbeiter neu zuweisen
- Archivierungsoption verfügbar

### Dienstgradverwaltung

**Dienstgrade konfigurieren:**
1. Admin → Mitarbeiter → Dienstgrade-Tab

**Dienstgrad erstellen:**
1. Klicken Sie auf **+ Neuer Dienstgrad**
2. Konfigurieren Sie:
   - **Name**: Dienstgradname (z.B. "Feuerwehrmann", "Leutnant")
   - **Kurzname**: Abkürzung (z.B. "FM", "LT")
   - **Stufe**: Hierarchiestufe (1-10)
     - Niedriger = Einstiegslevel
     - Höher = Führungspositionen
   - **Beschreibung**: Dienstgradpflichten
   - **Farbe**: Abzeichenfarbe
   - **Symbol**: Dienstgradabzeichen
   - **Abteilung**: Anwendbare Abteilungen
   - **Aktiv**: Aktivieren/Deaktivieren
   - **Anforderungen**: Voraussetzungen
     - Dienstjahre
     - Erforderliche Schulung
     - Erforderliche Lizenzen

**Dienstgradhierarchie:**
Stufen definieren Beförderungspfade:
```
Stufe 1: Anwärter
Stufe 2: Feuerwehrmann
Stufe 3: Oberfeuerwehrmann
Stufe 4: Leutnant
Stufe 5: Hauptmann
Stufe 6: Bataillonschef
Stufe 7: Stellvertretender Chef
Stufe 8: Feuerwehrchef
```

**Beförderungsregeln:**
- Mindestzeit im Dienstgrad
- Erforderliche Zertifizierungen
- Schulungsabschluss
- Genehmigungsworkflow

### Lizenztyp-Verwaltung

**Lizenztypen konfigurieren:**
1. Admin → Mitarbeiter → Lizenzen-Tab

**Lizenztyp erstellen:**
1. Klicken Sie auf **+ Neuer Lizenztyp**
2. Konfigurieren Sie:
   - **Name**: Lizenzname (z.B. "Führerschein", "Rettungssanitäter-Zertifizierung")
   - **Code**: Kurzkennung (z.B. "FS", "RS")
   - **Beschreibung**: Lizenzdetails
   - **Kategorie**: Lizenzen gruppieren
     - Führerscheine
     - Medizinische Zertifizierungen
     - Berufslizenzen
     - Gerätezertifizierungen
   - **Ablaufverfolgung**: Aktivieren/Deaktivieren
   - **Standarddauer**: Jahre gültig (z.B. 2 Jahre)
   - **Erinnerungstage**: Warnung vor Ablauf
   - **Erforderlich für**: Welche Positionen dies erfordern
   - **Ausstellende Behörde**: Wer Lizenz ausstellt
   - **Upload erforderlich**: Dokumentenupload erforderlich
   - **Aktiv**: Aktivieren/Deaktivieren

**Lizenzkategorien:**
- Verwandte Lizenzen gruppieren
- Filterung und Berichterstattung
- Berechtigungsanforderungen
- Stapeloperationen

**Ablaufverwaltung:**
- Ablaufdaten automatisch berechnen
- E-Mail-Erinnerungen (30/14/7 Tage vorher)
- Dashboard-Warnungen für abgelaufene
- Erneuerungsverfolgung

### Mitarbeiternummernformat

**Auto-Nummerierung konfigurieren:**
1. Admin → Mitarbeiter → Einstellungen-Tab
2. Abschnitt Mitarbeiternummernformat

**Formatoptionen:**
- **Präfix**: Fester Text (z.B. "MA-", "FW-")
- **Jahr**: Jahr einbeziehen (JJ oder JJJJ)
- **Abteilungscode**: Abt.-Code automatisch einfügen
- **Fortlaufende Nummer**: Automatisch erhöhen
  - Auffüllung: 001, 0001, 00001
  - Startnummer: Sequenz beginnen
  - Zurücksetzen: Jährlich, nie
- **Suffix**: Fester Text (optional)

**Formatbeispiele:**
- `MA-2025-001` (Präfix-Jahr-Seq)
- `FW-001` (Abt.-Seq)
- `2025-FW-0001` (Jahr-Abt.-Seq)
- Benutzerdefinierte Muster

**Einstellungen:**
- Automatische Zuweisung bei Erstellung
- Manuelle Überschreibung erlauben
- Eindeutigkeit validieren
- Format ändern (betrifft nur neue)

### Benutzerdefinierte Felder

**Benutzerdefinierte Mitarbeiterfelder:**
1. Admin → Mitarbeiter → Benutzerdefinierte Felder-Tab

**Benutzerdefiniertes Feld erstellen:**
Ähnlich wie benutzerdefinierte Berichtsfelder:
- Feldname und Beschriftung
- Feldtyp (Text, Zahl, Datum, Auswahl, etc.)
- Erforderlich/Optional
- Standardwert
- Hilfetext
- Validierungsregeln
- Anzeigereihenfolge
- Anwendbare Abteilungen

**Feldtypen:**
- Text, Textbereich, Zahl
- Datum, DatumZeit, Zeit
- Auswahl (Dropdown)
- Mehrfachauswahl
- Checkbox, Radio
- Datei-Upload
- Mitarbeiterverweis (Link zu anderem Mitarbeiter)

**Anwendungsfälle:**
- Abzeichennummer
- Wachenzuweisung
- Besondere Fähigkeiten
- Notfallkontakte
- Zusätzliche Zertifizierungen
- Ausrüstungszuweisungen

### Statustypen

**Mitarbeiterstatus konfigurieren:**
1. Admin → Mitarbeiter → Status-Tab

**Standardstatus:**
- Aktiv: Derzeit beschäftigt
- Inaktiv: Vorübergehend abwesend
- Im Ruhestand: Aus dem Dienst ausgeschieden
- Gekündigt: Beschäftigung beendet

**Benutzerdefinierter Status:**
- Krankheitsurlaub
- Militärurlaub
- Probezeit
- Suspendiert
- Ausgeliehen (an andere Abt.)

**Statuseinstellungen:**
- Farbcodierung
- Automatische Übergänge
- Benachrichtigungsregeln
- Berechtigungsänderungen
- Berichtssichtbarkeit

### Benachrichtigungseinstellungen

**Mitarbeiterbenachrichtigungen:**
1. Admin → Mitarbeiter → Benachrichtigungen

**Warnungen konfigurieren:**
- **Lizenzablauf**: Tage vor Warnung
- **Schulung fällig**: Bevorstehende Schulungserinnerungen
- **Jubiläum**: Dienstjubiläen
- **Geburtstag**: Mitarbeitergeburtstage
- **Beförderung**: Beförderungsbenachrichtigungen
- **Statusänderung**: Änderungen des Beschäftigungsstatus

**Benachrichtigungsmethoden:**
- E-Mail
- In-App-Benachrichtigung
- Push-Benachrichtigung (mobil)
- Dashboard-Widget
- SMS (falls aktiviert)

**Empfänger:**
- Mitarbeiter selbst
- Abteilungsleiter
- Personal-/Administrationspersonal
- Benutzerdefinierte Verteilerlisten

### Import-/Export-Konfiguration

**Mitarbeiterdatenimport:**
1. Admin → Mitarbeiter → Import

**Importoptionen:**
- CSV-Vorlage herunterladen
- Feldzuordnung
- Validierungsregeln
- Duplikatbehandlung
- Stapelgröße

**Importfelder:**
- Alle Mitarbeiterfelder
- Abteilungs-/Dienstgradzuweisung
- Lizenzdaten
- Benutzerdefinierte Felder
- Foto-URLs

**Exportvorlagen:**
- Vollständige Mitarbeiterdaten
- Abteilungslisten
- Lizenzberichte
- Schulungsaufzeichnungen
- Benutzerdefinierte Exporte

### Integrationseinstellungen

**Externe Systeme:**
1. Admin → Mitarbeiter → Integrationen

**Lohnabrechungsintegration:**
- Mitarbeiter-ID-Synchronisation
- Abteilungscodes
- Dienstgrad-/Gehaltsgruppenzuordnung
- Statussynchronisation

**Zeit & Anwesenheit:**
- Mitarbeiterlisten-Synchronisation
- Abteilungspläne
- Statusaktualisierungen

**API-Zugriff:**
- REST API-Endpunkte
- Authentifizierungs-Tokens
- Ratenbegrenzung
- Webhook-Konfiguration

### Berichtskonfiguration

**Mitarbeiterberichte:**
1. Admin → Mitarbeiter → Berichte

**Standardberichte:**
- Aktive Mitarbeiterliste
- Abteilungsaufschlüsselung
- Lizenzablaufbericht
- Schulungsstatus
- Jubiläumsliste
- Neueinstellungsbericht

**Benutzerdefinierte Berichte:**
- Berichts-Builder
- Feldauswahl
- Filter und Gruppierung
- Geplante Berichte
- Automatische Verteilung

### Systemeinstellungen

**Globale Mitarbeitereinstellungen:**
1. Administration → Einstellungen → Mitarbeitermodul

**Allgemeine Einstellungen:**
- Foto-Upload: Max. Größe, Formate
- Dokumentenspeicherung: Ort, Aufbewahrung
- Datenaufbewahrung: Inaktive Mitarbeiterdaten
- Datenschutzeinstellungen: Feldsichtbarkeit
- Audit-Protokollierung: Änderungen verfolgen

**Leistungseinstellungen:**
- Cache-Dauer
- Suchindizierung
- Foto-Thumbnails
- Behandlung großer Listen

---

## Nächste Schritte

- [📝 Berichtsverwaltung](/guide/reports)
- [📁 Dokumentenverwaltung](/guide/documents)
- [📚 Schulungsverwaltung](/guide/training)
- [👥 Admin: Benutzerverwaltung](/guide/admin/users)
