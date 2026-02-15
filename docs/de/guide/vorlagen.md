# Vorlagensystem

Umfassendes Vorlagenverwaltungssystem zur Erstellung wiederverwendbarer Dokument-, Berichts- und E-Mail-Vorlagen mit dynamischen Variablen und konsistenter Formatierung.

## Übersicht

Funktionen:
- Dokumentenvorlagen
- Berichtsvorlagen
- E-Mail-Vorlagen
- Rechnungsvorlagen
- Dynamische Variablen und Platzhalter
- Rich-Text-Formatierung
- Vorlagenkategorien
- Versionskontrolle
- Vorlagenfreigabe
- Variablenbibliothek
- Vorschau vor Verwendung
- Vorlagenklonen
- Import/Export von Vorlagen
- Rollenbasierter Zugriff

## Anforderungen

**Erforderliche Berechtigung:** `READ_TEMPLATES` (zum Anzeigen), `WRITE_TEMPLATES` (zum Erstellen)

**Admin-Konfiguration:**
- Vorlagenkategorien
- Variablendefinitionen
- Standardvorlagen
- Freigabeberechtigungen

## Vorlagenmanager öffnen

**Desktop:** Doppelklicken Sie auf das Symbol **Vorlagen**
**Menü:** Startmenü → Dokumente → Vorlagen
**Route:** `/template`

## Vorlagentypen

### Dokumentenvorlagen

**Zweck:**
- Standardbriefe
- Formulare
- Verträge
- Vereinbarungen
- Memos
- Zertifikate
- Berichte

**Funktionen:**
- Rich-Text-Editor
- Kopf-/Fußzeilenunterstützung
- Logos und Branding
- Seitennummerierung
- Inhaltsverzeichnis
- Dynamische Felder
- PDF-Generierung

### Berichtsvorlagen

**Zweck:**
- Vorfallberichte
- Inspektionsberichte
- Leistungsberichte
- Finanzberichte
- Benutzerdefinierte Berichte

**Funktionen:**
- Datenintegration
- Diagramme und Grafiken
- Automatisch ausgefüllte Felder
- Berechnungen
- Bedingte Abschnitte
- Mehrseitige Layouts
- Exportoptionen

### E-Mail-Vorlagen

**Zweck:**
- Willkommens-E-Mails
- Benachrichtigungen
- Newsletter
- Ankündigungen
- Erinnerungen
- Automatische Antworten

**Funktionen:**
- HTML-Formatierung
- Bilder und Logos
- Personalisierung
- Betreffzeilen-Variablen
- Signaturblöcke
- Abmeldelinks
- Tracking-Pixel

### Rechnungsvorlagen

**Zweck:**
- Dienstleistungsrechnungen
- Produktrechnungen
- Angebote
- Bestellungen
- Quittungen

**Funktionen:**
- Einzelposten
- Steuerberechnungen
- Zahlungsbedingungen
- Unternehmensbranding
- Mehrere Währungen
- Wiederkehrende Rechnungen
- Zahlungsverfolgung

## Vorlagen erstellen

### Neue Dokumentenvorlage

**Vorlage erstellen:**
1. Klicken Sie auf **+ Neue Vorlage**
2. **Dokumentenvorlage** auswählen
3. Vorlagendetails ausfüllen:

**Grundinformationen:**
- **Name**: Vorlagenname (erforderlich)
  - Beispiel: "Dienstleistungsvertrag"
- **Beschreibung**: Wofür sie ist
- **Kategorie**: Kategorie auswählen
  - Briefe
  - Formulare
  - Verträge
  - Berichte
  - Zertifikate
  - Andere
- **Tags**: Schlüsselwörter zum Suchen
  - Durch Komma getrennt
  - Beispiel: vertrag, dienstleistung, vereinbarung

**Vorlageneinstellungen:**
- **Zugriffsstufe**:
  - Privat: Nur Sie
  - Abteilung: Ihre Abteilung
  - Organisation: Alle
  - Benutzerdefiniert: Bestimmte Benutzer/Rollen
- **Versionskontrolle**: Änderungen verfolgen
- **Genehmigung erforderlich**: Admin-Genehmigung zur Verwendung
- **Aktiv-Status**: Aktivieren/Deaktivieren

**Seiteneinrichtung:**
- **Seitengröße**: Letter, Legal, A4, Benutzerdefiniert
- **Ausrichtung**: Hochformat oder Querformat
- **Ränder**: Oben, unten, links, rechts
- **Kopfzeilenhöhe**: Automatisch oder benutzerdefiniert
- **Fußzeilenhöhe**: Automatisch oder benutzerdefiniert

**Inhalt:**

**Kopfzeilenbereich:**
1. Klicken Sie auf **Kopfzeile bearbeiten**
2. Inhalt hinzufügen:
   - Firmenlogo (Variable: {company.logo})
   - Firmenname (Variable: {company.name})
   - Adresse (Variable: {company.address})
   - Benutzerdefinierter Text
   - Datum (Variable: {current.date})
3. Mit Rich-Editor formatieren
4. Links/Mitte/Rechts ausrichten

**Körperbereich:**
1. Klicken Sie auf **Körper bearbeiten**
2. Vorlageninhalt eingeben
3. Rich-Text-Editor verwenden:
   - Fett, kursiv, unterstrichen
   - Schriftgröße und -farbe
   - Ausrichtung
   - Listen
   - Tabellen
   - Bilder
   - Links
4. Variablen einfügen (siehe Variablen-Abschnitt)
5. Nach Bedarf formatieren

**Fußzeilenbereich:**
1. Klicken Sie auf **Fußzeile bearbeiten**
2. Inhalt hinzufügen:
   - Seitenzahlen (Variable: {page.number})
   - Firmendaten
   - Rechtliche Haftungsausschlüsse
   - Kontaktinformationen
3. Text formatieren
4. Speichern

4. Klicken Sie auf **Vorlage speichern**

### Neue E-Mail-Vorlage

**E-Mail-Vorlage erstellen:**
1. Klicken Sie auf **+ Neue Vorlage**
2. **E-Mail-Vorlage** auswählen
3. Konfigurieren:

**E-Mail-Details:**
- **Name**: Vorlagenname
- **Betreffzeile**: E-Mail-Betreff
  - Variablen verwenden: "Willkommen {employee.firstname}!"
- **Von-Name**: Absender-Anzeigename
  - Variable: {company.name}
- **Antwort an**: Antwort-E-Mail-Adresse
  - Variable: {user.email}

**E-Mail-Inhalt:**
- **HTML-Editor**: Rich-Formatierung
- **Nur-Text-Version**: Automatisch generiert oder benutzerdefiniert
- **Preheader-Text**: Vorschautext
- **Variablen**: Platzhalter einfügen

**Designelemente:**
- Firmenlogo
- Kopfbild
- Farbschema
- Button-Styling
- Fußzeileninhalt
- Social-Media-Links
- Abmeldelink (erforderlich für Massen)

**Einstellungen:**
- **Öffnungen verfolgen**: Tracking-Pixel
- **Klicks verfolgen**: Link-Tracking
- **Abmeldung aktivieren**: Abmeldelink hinzufügen
- **Signatur einschließen**: Benutzersignatur

4. E-Mail-Vorschau
5. Test-E-Mail senden
6. Vorlage speichern

### Neue Berichtsvorlage

**Berichtsvorlage erstellen:**
1. Klicken Sie auf **+ Neue Vorlage**
2. **Berichtsvorlage** auswählen
3. Konfigurieren:

**Berichtskonfiguration:**
- **Name**: Berichtsname
- **Typ**: Berichtstyp auswählen
  - Vorfallbericht
  - Inspektionsbericht
  - Leistungsbericht
  - Benutzerdefinierter Bericht
- **Datenquelle**: Woher Daten kommen
  - Datenbanktabellen
  - Formulareinsendungen
  - API-Daten
  - Manuelle Eingabe

**Berichtsstruktur:**

**Titelseite:**
- Titel
- Datum
- Autor
- Firmenlogo
- Berichtszeitraum
- Klassifizierungsstufe

**Abschnitte:**
1. Klicken Sie auf **Abschnitt hinzufügen**
2. Abschnittstypen:
   - **Textabschnitt**: Narrativer Inhalt
   - **Datentabelle**: Strukturierte Daten
   - **Diagramm/Graph**: Visuelle Daten
   - **Bildergalerie**: Fotos
   - **Zusammenfassungsstatistiken**: Schlüsselkennzahlen
   - **Anhang**: Unterstützende Dokumente
3. Jeden Abschnitt konfigurieren
4. Variablen hinzufügen
5. Bedingte Anzeigeregeln festlegen

**Datenfelder:**
- Felder für Dateneingabe definieren
- Feldtypen:
  - Text
  - Zahl
  - Datum
  - Dropdown
  - Checkbox
  - Datei-Upload
  - Signatur
- Erforderlich/Optional
- Validierungsregeln

**Berechnungen:**
- Formeln hinzufügen
- SUM, AVG, COUNT usw.
- Andere Felder referenzieren
- Bedingte Berechnungen

4. Ausgabeformat festlegen:
   - PDF
   - Word
   - Excel
   - HTML
5. Vorlage speichern

## Vorlagenvariablen

### Variablentypen

**Benutzervariablen:**
- `{user.firstname}` - Vorname des aktuellen Benutzers
- `{user.lastname}` - Nachname des aktuellen Benutzers
- `{user.fullname}` - Vollständiger Name
- `{user.email}` - E-Mail-Adresse
- `{user.phone}` - Telefonnummer
- `{user.title}` - Berufsbezeichnung
- `{user.department}` - Abteilung

**Unternehmensvariablen:**
- `{company.name}` - Firmenname
- `{company.address}` - Vollständige Adresse
- `{company.phone}` - Telefonnummer
- `{company.email}` - E-Mail-Adresse
- `{company.website}` - Website-URL
- `{company.logo}` - Firmenlogo-Bild

**Mitarbeitervariablen:**
- `{employee.id}` - Mitarbeiter-ID
- `{employee.firstname}` - Vorname
- `{employee.lastname}` - Nachname
- `{employee.email}` - E-Mail
- `{employee.phone}` - Telefon
- `{employee.hiredate}` - Einstellungsdatum
- `{employee.department}` - Abteilung
- `{employee.manager}` - Vorgesetztenname

**Datumsvariablen:**
- `{current.date}` - Heutiges Datum
- `{current.time}` - Aktuelle Uhrzeit
- `{current.datetime}` - Datum und Uhrzeit
- `{current.year}` - Aktuelles Jahr
- `{current.month}` - Aktueller Monat
- `{current.day}` - Aktueller Tag

**Dokumentenvariablen:**
- `{document.title}` - Dokumententitel
- `{document.number}` - Dokumentennummer
- `{document.version}` - Versionsnummer
- `{document.date}` - Dokumentendatum
- `{page.number}` - Aktuelle Seitenzahl
- `{page.total}` - Seitenzahl gesamt

**Benutzerdefinierte Variablen:**
- In Variablenbibliothek erstellen
- Name und Beschreibung definieren
- Datentyp festlegen
- Standardwert angeben
- Über Vorlagen hinweg verwenden

### Variablen einfügen

**Variable zu Vorlage hinzufügen:**
1. Cursor positionieren, wo Variable hinkommt
2. Klicken Sie auf **Variable einfügen**
3. Variable aus Liste auswählen
4. Oder eingeben: `{variable.name}`
5. Variable erscheint als Platzhalter
6. Wird bei Verwendung durch tatsächlichen Wert ersetzt

**Variablenformatoptionen:**
- **Datumsformat**: TT.MM.JJJJ, MM/TT/JJJJ usw.
- **Zahlenformat**: Dezimalstellen, Währung
- **Textformat**: Großbuchstaben, Kleinbuchstaben, Titelschreibweise
- **Standardwert**: Falls Variable leer

**Bedingte Variablen:**
```
{if employee.status == "active"}
Willkommen zurück!
{else}
Ihr Konto ist inaktiv.
{endif}
```

**Schleifenvariablen:**
```
{foreach items as item}
- {item.name}: ${item.price}
{endforeach}
```

### Variablenbibliothek

**Variablen verwalten:**
1. Klicken Sie auf **Variablenbibliothek**
2. Alle verfügbaren Variablen anzeigen
3. Klicken Sie auf **Variable hinzufügen**
4. Definieren:
   - Name
   - Beschreibung
   - Datentyp
   - Standardwert
   - Validierungsregeln
5. Speichern

**Variablengruppen:**
- Benutzerdaten
- Unternehmensdaten
- Mitarbeiterdaten
- Finanzdaten
- Benutzerdefinierte Daten

**Variablen testen:**
- Testwerte eingeben
- Vorschau mit Beispieldaten
- Formatierung überprüfen
- Bedingungen testen

## Vorlagen verwenden

### Dokument aus Vorlage erstellen

**Vorlage verwenden:**
1. Zu Dokumenten-Bereich gehen
2. Klicken Sie auf **Neu aus Vorlage**
3. Vorlage auswählen
4. Vorlagenvorschau
5. Klicken Sie auf **Vorlage verwenden**

**Variablen ausfüllen:**
1. Vorlage öffnet sich mit Variablenfeldern
2. Erforderliche Felder ausfüllen
3. Optionale Felder können übersprungen werden
4. Variablen werden automatisch ausgefüllt, falls Daten vorhanden
5. Inhalt überprüfen und bearbeiten

**Inhalt anpassen:**
- Beliebigen Text bearbeiten
- Abschnitte hinzufügen/entfernen
- Bilder einfügen
- Formatierung ändern
- Vorlagenspezifische Änderungen vornehmen

**Dokument speichern:**
1. Klicken Sie auf **Speichern**
2. Dokument benennen
3. Speicherort auswählen
4. Berechtigungen festlegen
5. Dokument mit ausgefülltem Inhalt gespeichert

### Bericht aus Vorlage generieren

**Bericht erstellen:**
1. Zu Berichte-Bereich gehen
2. Klicken Sie auf **Neuer Bericht**
3. Vorlage auswählen
4. Datenquelle/Bereich wählen
5. Parameter konfigurieren:
   - Datumsbereich
   - Filter
   - Sortierung
   - Gruppierung
6. Klicken Sie auf **Generieren**

**Berichtsausgabe:**
- Im Browser vorschauen
- Vor Finalisierung bearbeiten
- Notizen/Kommentare hinzufügen
- Nach PDF/Excel exportieren
- Mit anderen teilen
- Automatische Generierung planen

### E-Mail aus Vorlage senden

**E-Mail-Vorlage verwenden:**
1. Zu Nachrichten gehen
2. Klicken Sie auf **Neue E-Mail**
3. **Aus Vorlage** auswählen
4. Vorlage wählen
5. Empfänger auswählen
6. Variablen ausfüllen (automatisch ausgefüllt, falls möglich)
7. E-Mail-Vorschau
8. Senden oder planen

**Massen-E-Mail:**
- Mehrere Empfänger auswählen
- Variablen pro Empfänger personalisiert
- Öffnungsraten verfolgen
- Klickraten verfolgen
- Abmeldungen verwalten

## Vorlagenkategorien

### Standardkategorien

**Briefe:**
- Anschreiben
- Empfehlungsschreiben
- Korrespondenz
- Formelle Kommunikation

**Verträge:**
- Dienstleistungsvereinbarungen
- Arbeitsverträge
- Lieferantenverträge
- Geheimhaltungsvereinbarungen

**Formulare:**
- Antragsformulare
- Anfrageformulare
- Genehmigungsformulare
- Checklisten

**Berichte:**
- Vorfallberichte
- Fortschrittsberichte
- Finanzberichte
- Benutzerdefinierte Berichte

**Zertifikate:**
- Schulungszertifikate
- Leistungszertifikate
- Abschlusszertifikate

### Benutzerdefinierte Kategorien

**Kategorie erstellen:**
1. Admin → Vorlageneinstellungen
2. Klicken Sie auf **Kategorie hinzufügen**
3. Eingeben:
   - Name
   - Beschreibung
   - Symbol
   - Farbe
   - Übergeordnete Kategorie (optional)
4. Speichern

**Kategorieverwaltung:**
- Unterkategorien erstellen
- Kategorieberechtigungen festlegen
- Standardvorlagen zuweisen
- Hierarchisch organisieren

## Vorlagenfreigabe

### Vorlage teilen

**Mit anderen teilen:**
1. Vorlage öffnen
2. Klicken Sie auf **Teilen**
3. Freigabeoptionen auswählen:
   - **Bestimmte Benutzer**: Benutzer wählen
   - **Abteilung**: Gesamte Abteilung
   - **Rolle**: Benutzer mit bestimmter Rolle
   - **Organisation**: Alle
4. Berechtigungen festlegen:
   - Nur Ansicht
   - Vorlage verwenden
   - Vorlage bearbeiten
   - Freigabe verwalten
5. Nachricht hinzufügen (optional)
6. Klicken Sie auf **Teilen**

**Freigabelink:**
- Teilbaren Link generieren
- Ablauf festlegen
- Anmeldung erforderlich oder öffentlich
- Kopieren und verteilen

### Vorlagenberechtigungen

**Berechtigungsstufen:**

**Nur Ansicht:**
- Kann Vorlage sehen
- Kann nicht verwenden oder bearbeiten
- Nur Vorschau

**Vorlage verwenden:**
- Kann Dokumente aus Vorlage erstellen
- Kann Vorlage nicht ändern
- Standard-Benutzerzugriff

**Vorlage bearbeiten:**
- Kann Vorlageninhalt ändern
- Kann Variablen aktualisieren
- Kann nicht löschen oder teilen

**Vorlage verwalten:**
- Vollständige Kontrolle
- Bearbeiten, teilen, löschen
- Versionen verwalten
- Normalerweise Admin oder Eigentümer

**Berechtigungen festlegen:**
1. Vorlage öffnen
2. Klicken Sie auf **Berechtigungen**
3. Benutzer/Rollen hinzufügen
4. Berechtigungsstufe zuweisen
5. Speichern

## Versionskontrolle

### Vorlagenversionen

**Versionsverfolgung:**
- Jedes Speichern erstellt neue Version
- Versionsverlauf beibehalten
- Versionen vergleichen
- Vorherige Versionen wiederherstellen
- Sehen, wer Änderungen vorgenommen hat

**Versionen anzeigen:**
1. Vorlage öffnen
2. Klicken Sie auf **Versionsverlauf**
3. Alle Versionen sehen:
   - Versionsnummer
   - Änderungsdatum
   - Geändert von
   - Änderungsbeschreibung
4. Auf Version klicken zur Vorschau

**Versionen vergleichen:**
1. Zwei Versionen auswählen
2. Klicken Sie auf **Vergleichen**
3. Nebeneinander-Vergleich sehen
4. Unterschiede hervorgehoben
5. Änderungen überprüfen

**Version wiederherstellen:**
1. Alte Version öffnen
2. Klicken Sie auf **Diese Version wiederherstellen**
3. Bestätigen
4. Wird zur aktuellen Version
5. Alte aktuelle als neue Version gespeichert

### Versionsnotizen

**Versionsnotizen hinzufügen:**
1. Vorlage speichern
2. Aufforderung für Versionsnotizen
3. Änderungen beschreiben:
   - Was geändert wurde
   - Warum geändert
   - Wer angefragt hat
4. Notizen speichern

**Notizen anzeigen:**
- Versionsverlauf zeigt Notizen
- Hilfreich zur Verfolgung der Entwicklung
- Prüfpfad
- Team-Kommunikation

## Vorlagen-Import/Export

### Vorlagen exportieren

**Einzelne Vorlage exportieren:**
1. Vorlage öffnen
2. Klicken Sie auf **Exportieren**
3. Format wählen:
   - .ktemplate (K-Systems-Format)
   - .docx (Word)
   - .html (HTML)
   - .pdf (PDF)
4. Datei herunterladen

**Mehrere exportieren:**
1. Vorlagen auswählen (Checkbox)
2. Klicken Sie auf **Ausgewählte exportieren**
3. Wird als ZIP heruntergeladen
4. Einschließlich aller Assets

**Exportoptionen:**
- Variablen einschließen
- Einstellungen einschließen
- Versionsverlauf einschließen
- Mit Schriftarten/Bildern verpacken

### Vorlagen importieren

**Vorlage importieren:**
1. Klicken Sie auf **Vorlage importieren**
2. Datei auswählen (.ktemplate)
3. Vorlagenvorschau
4. Konflikte auflösen:
   - Vorhandener Vorlagenname
   - Variablenkonflikte
   - Kategorieunstimmigkeiten
5. Importaktion wählen:
   - Als neu importieren
   - Vorhandene ersetzen
   - Als Version importieren
6. Klicken Sie auf **Importieren**

**Massen-Import:**
- ZIP mit Vorlagen hochladen
- Alle auf einmal importieren
- Vor Finalisierung überprüfen
- Kategorien und Variablen zuordnen

## Vorlagenklonen

### Vorlage klonen

**Vorlage duplizieren:**
1. Vorlage öffnen
2. Klicken Sie auf **Klonen**
3. Neue Kopie erstellt
4. Ändern:
   - Name
   - Kategorie
   - Inhalt
5. Als neue Vorlage speichern

**Anwendungsfälle:**
- Variationen erstellen
- Änderungen sicher testen
- Für Abteilungen anpassen
- Jahresbasierte Versionen

**Klonen mit Änderungen:**
- Klonen und sofort bearbeiten
- Massen-Klonen für mehrere Versionen
- In andere Kategorie klonen
- Mit anderen Berechtigungen klonen

## Best Practices

**Tun Sie:**
✅ Klare, beschreibende Namen verwenden
✅ Vorlagenzweck dokumentieren
✅ Variablen für dynamischen Inhalt verwenden
✅ Vorlagen gründlich testen
✅ Versionskontrolle für wichtige Änderungen
✅ Mit Kategorien organisieren
✅ Nützliche Vorlagen teilen
✅ Hilfreiche Beschreibungen hinzufügen
✅ Geeignete Berechtigungen festlegen
✅ Regelmäßige Vorlagenwartung

**Nicht tun:**
❌ Änderbare Informationen fest kodieren
❌ Doppelte Vorlagen erstellen
❌ Zu viele Variablen verwenden (verwirrend)
❌ Versionsnotizen überspringen
❌ Ungenutzte Vorlagen aktiv lassen
❌ Vorlagenaktualisierungen ignorieren
❌ Vorlagen ohne Testen teilen
❌ Vorlagen zu kompliziert machen
❌ Vorlagen zu sichern vergessen
❌ Inkompatible Variablentypen mischen

## Fehlerbehebung

### Variable wird nicht ausgefüllt
- Variablenschreibweise prüfen
- Überprüfen, ob Daten vorhanden sind
- Variablenbereich prüfen
- Mit Beispieldaten testen
- Variablendefinition überprüfen

### Vorlage nicht verfügbar
- Berechtigungen prüfen
- Aktiv-Status überprüfen
- Kategorie-Zugriff prüfen
- Vorlageneigentümer kontaktieren
- Abteilungseinschränkungen prüfen

### Import fehlgeschlagen
- Dateiformat überprüfen
- Dateibeschädigung prüfen
- Variablenkonflikte auflösen
- Vorlagengröße prüfen
- Fehlerprotokoll überprüfen

### Formatierungsprobleme
- Browser-Kompatibilität prüfen
- CSS-Unterstützung überprüfen
- Im Vorschaumodus testen
- Vorlagencode überprüfen
- Browser-Cache leeren

### Variablen werden als Text angezeigt
- Variablensyntax prüfen: {variable.name}
- Sicherstellen, dass Variablen definiert sind
- Überprüfen, ob Vorlagentyp Variablen unterstützt
- Nach maskierten Zeichen suchen
- Vorlage neu speichern

## Tastaturkürzel

- **Strg+N**: Neue Vorlage
- **Strg+S**: Vorlage speichern
- **Strg+E**: Vorlage bearbeiten
- **Strg+P**: Vorlagenvorschau
- **Strg+K**: Variable einfügen
- **Strg+F**: In Vorlage suchen
- **Strg+H**: Suchen und ersetzen
- **Strg+Z**: Rückgängig
- **Strg+Y**: Wiederherstellen

---

## Nächste Schritte
- [📄 Dokumente](/guide/documents)
- [📊 Berichte](/guide/reports)
- [💬 Nachrichten](/guide/messages)
- [⚙️ Admin: Vorlageneinstellungen](/guide/admin/templates)
