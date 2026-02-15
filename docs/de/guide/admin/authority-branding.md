# Authority-Branding

Visuelles Branding und Anpassung für Ihre Behörde konfigurieren, einschließlich Logos, Farben, Login-Seiten-Anpassung und E-Mail-Branding.

## Überblick

Authority-Branding ermöglicht jeder Behörde (Organisation) in der K-Systems Multi-Tenant-Umgebung, ihr Erscheinungsbild und Branding im gesamten System anzupassen.

**Anpassungsoptionen:**
- Logo-Upload und -Verwaltung
- Farbschema-Anpassung
- Login-Seiten-Branding
- E-Mail-Vorlagen-Branding
- Öffentlich zugängliches Branding
- Favicon und App-Icons
- Dokument-Kopf-/Fußzeilen
- Benutzerdefiniertes CSS (erweitert)

## Anforderungen

**Erforderliche Berechtigung:** `ADMIN_AUTHORITY` (Behördenverwaltung)

**Zugriffsebene:** Authority-Administrator oder Super Administrator

## Authority-Branding öffnen

**Desktop:** Start → Administration → Authority-Branding
**Menü:** Administration → Branding
**Route:** `/admin/authority-branding`

## Logo-Upload

### Primäres Logo

**Hauptlogo hochladen:**
1. Zu Tab **Logo** gehen
2. Klicken Sie auf **Primäres Logo hochladen**
3. Bilddatei auswählen:

**Logo-Anforderungen:**
- **Format**: PNG, JPG, SVG (empfohlen)
- **Abmessungen**: Empfohlen: 400x100px, Maximum: 2000x500px
- **Dateigröße**: Max 2MB
- **Hintergrund**: Transparentes PNG oder SVG bevorzugt

4. Anpassen: Zuschneiden falls nötig, Größe ändern, Positionieren
5. Klicken Sie auf **Logo speichern**

**Logo-Verwendung:**
- Desktop-Oberflächen-Header
- Login-Seiten-Header
- E-Mail-Vorlagen
- PDF-Berichte
- Öffentliche Seiten

### Sekundäres Logo

**Alternatives Logo hochladen:**
1. Klicken Sie auf **Sekundäres Logo hochladen**
2. Bild auswählen

**Anwendungsfälle:**
- Invertierte Farben für dunkle Hintergründe
- Vereinfachte Nur-Icon-Version
- Monochrome Version
- Version für kleine Räume

### Favicon

**Favicon hochladen:**
1. Klicken Sie auf **Favicon hochladen**
2. Icon-Datei auswählen:

**Favicon-Anforderungen:**
- **Format**: ICO, PNG
- **Größe**: 32x32px oder 64x64px
- **Quadratisch**: 1:1 Seitenverhältnis

**Favicon erscheint:**
- Browser-Tab
- Lesezeichen
- Browser-Verlauf

### App-Icons

**App-Icons hochladen:**

**Für Mobile/PWA:**
- **App-Icon**: 512x512px PNG
- **App-Icon klein**: 192x192px PNG

**Apple Touch Icon:**
- 180x180px PNG
- iOS-Startbildschirm

## Farbschema

### Primärfarben

**Hauptfarben konfigurieren:**
1. Zu Tab **Farben** gehen
2. Primärfarben festlegen:

**Primärfarbe:**
- Haupt-Markenfarbe
- Verwendet für: Primäre Buttons, Links, Aktive Zustände, Header
- Farbwähler klicken
- Hex-Code eingeben (z.B. #1976D2)
- Oder RGB/HSL verwenden
- Vorschau in Echtzeit

**Sekundärfarbe:**
- Unterstützende Farbe
- Verwendet für: Sekundäre Buttons, Hover-Zustände, Hintergründe

**Akzentfarbe:**
- Hervorhebungsfarbe
- Verwendet für: Warnungen, Benachrichtigungen, Call-to-Action

### Systemfarben

**Statusfarben:**
- **Erfolg**: Grüntöne (Standard: #4CAF50)
- **Warnung**: Orange/Gelbtöne (Standard: #FF9800)
- **Fehler**: Rottöne (Standard: #F44336)
- **Info**: Blautöne (Standard: #2196F3)

**Textfarben:**
- **Primärtext**: Haupttextfarbe (Standard: #212121)
- **Sekundärtext**: Gedämpfter Text (Standard: #757575)
- **Deaktivierter Text**: Inaktiver Text (Standard: #BDBDBD)

### Hintergrundfarben

**Hintergründe konfigurieren:**
- **Haupthintergrund**: Primärer Hintergrund (Standard: #FFFFFF)
- **Alt-Hintergrund**: Sekundärer Hintergrund (Standard: #FAFAFA)
- **Desktop-Hintergrund**: Desktop-Modus

### Dunkelmodus

**Dunkles Theme-Farben:**
Falls Dunkelmodus aktiviert:
- **Dunkel primär**: Dunkler Hintergrund (Standard: #121212)
- **Dunkle Oberfläche**: Element-Hintergrund (Standard: #1E1E1E)
- **Dunkler Text**: Text auf dunkel (Standard: #FFFFFF)

**Auto-Generieren:**
- Klicken Sie auf **Dunkles Theme auto-generieren**
- System erstellt passende dunkle Farben
- Vorschau, bei Bedarf anpassen

### Farbvoreinstellungen

**Farbvoreinstellung verwenden:**
1. Klicken Sie auf **Farbvoreinstellungen**
2. Aus Vorlagen wählen:
   - Professionelles Blau
   - Modernes Grün
   - Kräftiges Rot
   - Corporate Grau
   - Lebendiges Orange
3. Voreinstellung anwenden
4. Bei Bedarf anpassen

## Login-Seiten-Anpassung

### Login-Seiten-Einstellungen

**Login anpassen:**
1. Zu Tab **Login-Seite** gehen
2. Elemente konfigurieren:

**Layout:**
- **Stil**: Geteilt, Zentriert, Voll, Minimal
- **Logo-Position**: Oben Mitte, Oben Links, Linke Seite
- **Logo-Größe**: Klein (150px), Mittel (200px), Groß (300px)

**Hintergrund:**
- **Hintergrundtyp**: Einfarbig, Verlauf, Bild, Video
- **Hintergrundfarbe**: Falls einfarbig
- **Verlaufsfarben**: Start und Ende
- **Hintergrundbild**: Bild hochladen, Max 5MB, Empfohlen: 1920x1080px
- **Hintergrunddeckkraft**: 0-100%
- **Weichzeichnungseffekt**: 0-20px

**Formular-Styling:**
- **Formular-Hintergrund**: Farbe
- **Formular-Deckkraft**: 0-100%
- **Formular-Randradius**: Ecken
- **Input-Stil**: Umrandet/Gefüllt

### Login-Seiten-Inhalt

**Textinhalt:**
- **Willkommenstitel**: Begrüßungstext
- **Willkommensnachricht**: Untertitel
- **Fußzeilentext**: Text unten

**Links:**
- "Passwort vergessen" Link anzeigen
- "Support kontaktieren" Link anzeigen
- "Nutzungsbedingungen" Link anzeigen
- "Datenschutzrichtlinie" Link anzeigen

### Login-Seite Vorschau

**Vorschaumodus:**
1. Klicken Sie auf **Login-Vorschau**
2. Genaues Erscheinungsbild sehen
3. Testen auf: Desktop, Tablet, Mobil
4. Anpassungen vornehmen
5. Speichern wenn zufrieden

## E-Mail-Branding

### E-Mail-Vorlagen

**E-Mails anpassen:**
1. Zu Tab **E-Mail-Branding** gehen
2. E-Mail-Erscheinungsbild konfigurieren:

**E-Mail-Header:**
- **Logo**: Logo in E-Mails einschließen
- **Header-Hintergrund**: Farbe
- **Header-Text**: Optionaler Slogan

**E-Mail-Farben:**
- **Primärfarbe**: Buttons und Links
- **Hintergrundfarbe**: E-Mail-Hintergrund
- **Inhalts-Hintergrund**: Karten-/Panel-Farbe
- **Textfarbe**: Body-Text

**E-Mail-Fußzeile:**
- **Firmenname**: Organisationsname
- **Adresse**: Physische Adresse
- **Telefon**: Kontakttelefon
- **E-Mail**: Kontakt-E-Mail
- **Social-Links**: Facebook, Twitter, LinkedIn
- **Rechtstext**: Copyright, Abbestellen-Text

### E-Mail-Layout

**E-Mail-Struktur:**
- **Layout-Stil**: Einzelne Spalte (empfohlen), Zwei Spalten
- **Max Breite**: 600px (E-Mail-Standard)
- **Polsterung**: Interner Abstand
- **Randradius**: Abgerundete Ecken

**Button-Stil:**
- Button-Farbe (Primärfarbe)
- Button-Textfarbe
- Button-Randradius
- Button-Größe

### Test-E-Mail-Branding

**Test-E-Mail senden:**
1. Klicken Sie auf **Test-E-Mail senden**
2. E-Mail-Typ auswählen
3. Empfänger eingeben
4. Klicken Sie auf **Senden**
5. Erscheinungsbild überprüfen
6. Auf verschiedenen Clients testen

## Öffentliches Branding

### Öffentliche Seiten

**Öffentlich zugängliche anpassen:**

**Öffentliche Seiten beinhalten:**
- Passwortzurücksetzungsseite
- E-Mail-Verifizierungsseite
- Öffentliche Formulare
- Geteilte Dokumentenansicht
- Fehlerseiten (404, 500)

### Dokument-Branding

**Dokumente anpassen:**

**PDF-Berichte:**
- Header-Stil: Logo-Position, Firmeninfo
- Fußzeilenstil: Seitenzahlen, Firmeninfo
- Wasserzeichen (optional): Text oder Bild

**Dokumentvorlagen:**
- Briefkopf-Vorlage
- Berichtsvorlage
- Zertifikatsvorlage
- Rechnungsvorlage

## Erweiterte Anpassung

### Benutzerdefiniertes CSS

**Benutzerdefinierte Stile hinzufügen:**
1. Zu Tab **Erweitert** gehen
2. Klicken Sie auf **Benutzerdefiniertes CSS**
3. CSS-Code eingeben:

```css
/* Beispiel: Header anpassen */
.app-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Benutzerdefinierter Button-Stil */
.btn-primary {
  border-radius: 20px;
  text-transform: uppercase;
}
```

4. Änderungen Vorschau
5. Speichern

### Schriftart-Anpassung

**Benutzerdefinierte Schriftarten:**
1. Zu **Typografie** gehen
2. Schriftarten auswählen:

**Schriftart-Optionen:**
- **Systemschriftarten**: Vorinstalliert (Arial, Helvetica, Times New Roman)
- **Google Fonts**: Webfonts (Roboto, Open Sans, Lato, Montserrat)
- **Benutzerdefinierte Schriftarten**: Hochladen (WOFF2-Dateien)

**Schriftart-Einstellungen:**
- **Überschriftenschriftart**: Für h1-h6
- **Body-Schriftart**: Für Absätze
- **Monospace-Schriftart**: Für Code

## Vorschau & Anwenden

### Branding Vorschau

**Alle Änderungen Vorschau:**
1. Klicken Sie auf **Vorschaumodus**
2. System mit neuem Branding durchsuchen
3. Alle Bereiche überprüfen
4. Anpassungen vornehmen
5. Vorschau beenden

### Branding anwenden

**Änderungen veröffentlichen:**
1. Alle Anpassungen überprüfen
2. Klicken Sie auf **Branding anwenden**
3. Änderungen bestätigen
4. Verarbeitung: Assets generieren, Cache aktualisieren
5. Branding live

**Rollback:**
- Änderungen als Version gespeichert
- Bei Bedarf zurücksetzen
- Vorherige Versionen behalten

## Branding-Verwaltung

### Markenrichtlinien

**Richtlinien generieren:**
1. Klicken Sie auf **Markenrichtlinien generieren**
2. System erstellt PDF: Logo-Verwendung, Farbpalette, Typografie
3. Herunterladen
4. Mit Team teilen

### Branding exportieren

**Assets exportieren:**
1. Klicken Sie auf **Branding exportieren**
2. Zu exportierendes auswählen
3. Format wählen
4. ZIP-Datei herunterladen

### Branding importieren

**Aus Datei importieren:**
1. Klicken Sie auf **Branding importieren**
2. Branding-Datei auswählen (.kbrand)
3. Import-Vorschau
4. Zu importierendes wählen
5. Anwenden

## Best Practices

**Tun Sie:**
✅ Hochwertige Logo-Bilder verwenden
✅ Konsistente Farben beibehalten
✅ Auf mehreren Geräten testen
✅ Login-Seite professionell halten
✅ Websichere Schriftarten verwenden
✅ E-Mail in mehreren Clients testen

**Nicht tun:**
❌ Niedrigauflösende Logos verwenden
❌ Zu viele Farben verwenden
❌ Login-Seite überkomplizieren
❌ Mobiles Erscheinungsbild ignorieren
❌ Urheberrechtlich geschützte Schriftarten ohne Lizenz verwenden
❌ Änderungen vor Vorschau vergessen

## Fehlerbehebung

### Logo erscheint nicht
- Dateiformat überprüfen (PNG, JPG, SVG)
- Dateigröße unter Limit verifizieren
- Browser-Cache leeren
- Logo-URL überprüfen

### Farben werden nicht angewendet
- "Branding anwenden" klicken
- Browser-Cache leeren
- Aktualisierung erzwingen (Strg+F5)
- Benutzerdefinierte CSS-Konflikte überprüfen

### Login-Seite sieht falsch aus
- Auf verschiedenen Browsern Vorschau
- Hintergrundbildgröße überprüfen
- Mobil-Responsive verifizieren
- Ohne benutzerdefiniertes CSS testen

---

## Nächste Schritte
- [⚙️ Systemeinstellungen](/guide/admin/system-settings)
- [🏢 Behördenverwaltung](/guide/admin/authorities)
- [👥 Benutzerverwaltung](/guide/admin/users)
