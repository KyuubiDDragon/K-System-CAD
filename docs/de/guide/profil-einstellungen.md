# Profil-Einstellungen

Benutzerprofilverwaltung zur Personalisierung Ihrer K-Systems-Erfahrung, Verwaltung von Berichtsvorlagen und Sicherheitseinstellungen.

## Überblick

Die Profil-Einstellungen ermöglichen Ihnen:
- Ihre Kontoinformationen anzuzeigen
- Ihr Passwort zu ändern
- Dokument-Ansichtspräferenzen zu konfigurieren
- Berichtsvorlagenbilder zu verwalten
- Ihre Unterschrift für Berichte festzulegen

**Erforderliche Berechtigung:** `READ_ACCOUNT` (alle Benutzer haben diese standardmäßig)

## Zugriff auf Profil-Einstellungen

**Route:** `/profile`

**Zugriff:**
- Klicken Sie auf Ihren Benutzernamen in der oberen Navigationsleiste
- Oder navigieren Sie direkt zu `/profile`

## Profilinformationen

### Kontoübersicht

**Ihre Informationen anzeigen:**
- **Benutzername**: Ihr System-Benutzername (schreibgeschützt)
- **Letzte Anmeldung**: Wann Sie sich zuletzt im System angemeldet haben
- **Rollen**: Alle Ihrem Konto zugewiesenen Rollen

Diese Informationen sind nur zur Anzeige und können hier nicht bearbeitet werden. Wenden Sie sich an Ihren Administrator, um Benutzernamen oder Rollen zu aktualisieren.

**Aktualisieren-Schaltfläche**: Klicken Sie auf das Aktualisierungssymbol, um Ihre Kontoinformationen vom Server neu zu laden.

## Persönliche Einstellungen

### Dokument-Ansichtspräferenz

Wählen Sie, wie Dokumente im gesamten System angezeigt werden:

**Ansichtsoptionen:**
1. **Kachel**: Dokumente als visuelle Karten/Kacheln anzeigen
2. **Tabelle**: Dokumente in einem traditionellen Tabellenformat anzeigen

**So ändern Sie:**
1. Navigieren Sie zum Tab **Persönliche Einstellungen** (Konto)
2. Wählen Sie Ihre bevorzugte Ansicht aus dem Dropdown-Menü
3. Klicken Sie auf **Speichern**
4. Ihre Auswahl wird sofort gespeichert und gilt für alle Dokumentenansichten

## Vorlagen & Unterschriften

### Organisations-Branding-Vorlagen

Konfigurieren Sie Kopf- und Fußzeilenbilder für die Berichte Ihrer Organisation.

**Kopfzeilenbild:**
- Geben Sie die URL zum Kopfzeilenbild Ihrer Organisation ein
- Dies erscheint oben in generierten Berichten
- Vorschau des Bildes durch Klicken auf das Augensymbol

**Fußzeilenbild:**
- Geben Sie die URL zum Fußzeilenbild Ihrer Organisation ein
- Dies erscheint unten in generierten Berichten
- Vorschau des Bildes durch Klicken auf das Augensymbol

**Organisations-Branding speichern:**
Klicken Sie auf die **Speichern**-Schaltfläche, um Ihre Kopf- und Fußzeilenbilder auf alle Berichte anzuwenden.

### Neutrale Vorlagen

Konfigurieren Sie Kopf- und Fußzeilenbilder für neutrale Berichte (Berichte ohne Organisations-Branding).

**Anwendungsfall**: Einige Berichte müssen möglicherweise ohne spezifisches Organisations-Branding erstellt werden. Diese neutralen Vorlagen werden in diesen Fällen verwendet.

**Konfiguration**:
- Neutrale Kopfzeilen-Bild-URL
- Neutrale Fußzeilen-Bild-URL
- Vorschau jedes Bildes vor dem Speichern
- Separat vom Organisations-Branding speichern

### Unterschrift

Fügen Sie Ihre persönliche Unterschrift hinzu, die in von Ihnen erstellten oder genehmigten Berichten erscheint.

**So konfigurieren Sie:**
1. Gehen Sie zum Tab **Vorlagen** (Vorlagen)
2. Scrollen Sie zum Abschnitt **Unterschrift**
3. Geben Sie die URL zu Ihrem Unterschriftsbild ein
4. Klicken Sie auf Vorschau (Augensymbol), um zu überprüfen, ob sie korrekt aussieht
5. Klicken Sie auf **Speichern**

**Bildanforderungen:**
- Muss eine öffentlich zugängliche URL sein
- Empfohlen: PNG mit transparentem Hintergrund
- Empfohlene Größe: 300x100 Pixel
- Halten Sie die Dateigröße klein für schnelleres Laden

## Sicherheit

### Passwort ändern

Aktualisieren Sie Ihr Kontopasswort aus Sicherheitsgründen.

**Passwortanforderungen:**
- Mindestens 8 Zeichen
- Mindestens ein Großbuchstabe empfohlen
- Mindestens eine Zahl empfohlen
- Mindestens ein Sonderzeichen empfohlen

**Schritte zum Ändern des Passworts:**
1. Navigieren Sie zum Tab **Sicherheit** (Sicherheit)
2. Geben Sie Ihr **Aktuelles Passwort** ein (zur Verifizierung)
3. Geben Sie Ihr **Neues Passwort** ein
4. **Neues Passwort wiederholen** (muss genau übereinstimmen)
5. Klicken Sie auf **Passwort ändern**
6. Sie sehen eine Bestätigungsmeldung, wenn erfolgreich

**Nach dem Ändern:**
- Alle Formularfelder werden automatisch geleert
- Sie bleiben mit Ihrem neuen Passwort angemeldet
- Ihre nächste Anmeldung erfordert das neue Passwort

**Sicherheitstipps:**

::: tip Starke Passwörter
Verwenden Sie mindestens 8 Zeichen mit einer Mischung aus Groß-, Kleinbuchstaben, Zahlen und Sonderzeichen (@$!%*?&).
:::

::: warning Regelmäßige Änderungen
Ändern Sie Ihr Passwort regelmäßig aus Sicherheitsgründen, insbesondere wenn Sie unbefugten Zugriff vermuten.
:::

::: tip Nie teilen
Geben Sie Ihr Passwort niemals an jemanden weiter, auch nicht an Administratoren. K-Systems-Mitarbeiter werden Sie niemals nach Ihrem Passwort fragen.
:::

## Bildvorschau

Wenn Sie Vorlagenbilder oder Unterschriften konfigurieren, können Sie diese vor dem Speichern in der Vorschau anzeigen:

**Vorschaufunktion:**
- Klicken Sie auf das Augensymbol (👁️) neben einem beliebigen Bild-URL-Feld
- Ein Dialog öffnet sich und zeigt das vollständige Bild
- Wenn das Bild nicht geladen werden kann, sehen Sie eine Fehlermeldung
- Schließen Sie die Vorschau durch Klicken auf die X-Schaltfläche

**Fehlerbehebung Bildvorschau:**
- Überprüfen Sie, ob die URL korrekt und öffentlich zugänglich ist
- Prüfen Sie, ob das Bildformat unterstützt wird (JPG, PNG, GIF)
- Stellen Sie sicher, dass die URL HTTPS (nicht HTTP) verwendet
- Versuchen Sie, die URL in einem neuen Browser-Tab zu öffnen, um zu überprüfen, ob sie funktioniert

## Tipps & Best Practices

**Dokumentenansicht:**
- Wählen Sie **Kachel** für visuelles Durchsuchen mit Miniaturansichten
- Wählen Sie **Tabelle** für detaillierte Informationen und schnelleres Scannen vieler Elemente

**Vorlagenbilder:**
- Verwenden Sie hochwertige Bilder für professionelles Aussehen
- Halten Sie Dateigrößen angemessen (unter 500KB) für schnelleres Laden
- Verwenden Sie konsistente Abmessungen über alle Berichte hinweg für ein einheitliches Aussehen
- Testen Sie, wie Bilder aussehen, wenn sie gedruckt oder als PDF gespeichert werden

**Unterschriften:**
- Verwenden Sie ein PNG mit transparentem Hintergrund für beste Ergebnisse
- Stellen Sie sicher, dass Ihre Unterschrift bei kleinen Größen lesbar ist
- Erwägen Sie die Verwendung eines digitalen Signaturdienstes für rechtliche Dokumente
- Testen Sie Ihre Unterschrift auf einem Beispielbericht, bevor Sie sie weitgehend verwenden

**Passwortsicherheit:**
- Verwenden Sie ein eindeutiges Passwort, das nicht auf anderen Websites verwendet wird
- Erwägen Sie die Verwendung eines Passwort-Managers
- Ändern Sie das Passwort, wenn Sie eine Kompromittierung vermuten
- Schreiben Sie Ihr Passwort nicht auf

## Tastaturkürzel

- **Strg+S**: Aktuelle Änderungen speichern (wenn in einem Formular)
- **Esc**: Bildvorschau-Dialog schließen

## Fehlerbehebung

### Änderungen werden nicht gespeichert

**Problem**: Speichern geklickt, aber Änderungen bleiben nicht erhalten

**Lösungen:**
1. Überprüfen Sie Ihre Internetverbindung
2. Suchen Sie nach Fehlermeldungen in Rot
3. Stellen Sie sicher, dass alle erforderlichen Felder ausgefüllt sind
4. Versuchen Sie, die Seite zu aktualisieren und Änderungen erneut vorzunehmen
5. Löschen Sie den Browser-Cache und versuchen Sie es erneut

### Passwortänderung schlägt fehl

**Problem**: Passwort kann nicht geändert werden

**Lösungen:**
1. Überprüfen Sie, ob Sie das aktuelle Passwort korrekt eingegeben haben
2. Prüfen Sie, ob das neue Passwort die Anforderungen erfüllt (8+ Zeichen)
3. Stellen Sie sicher, dass neues Passwort und Wiederholung exakt übereinstimmen
4. Versuchen Sie, das Formular zu löschen und neu zu beginnen
5. Überprüfen Sie, ob die Feststelltaste nicht versehentlich aktiviert ist

### Bilder werden nicht in der Vorschau angezeigt

**Problem**: Vorschau zeigt Fehler oder lädt nicht

**Lösungen:**
1. Überprüfen Sie, ob die Bild-URL korrekt ist
2. Prüfen Sie, ob das Bild öffentlich zugänglich ist (URL in neuem Tab öffnen)
3. Stellen Sie sicher, dass die URL mit https:// beginnt
4. Versuchen Sie eine andere Bild-URL
5. Kontaktieren Sie Ihren Systemadministrator, wenn Bilder intern gehostet werden

### Profilinformationen werden nicht aktualisiert

**Problem**: Kontoinformationen (Benutzername, Rollen) müssen aktualisiert werden

**Lösung**: Kontaktieren Sie Ihren Systemadministrator. Diese Felder werden zentral verwaltet und können aus Sicherheitsgründen nicht selbst bearbeitet werden.

## Verwandte Dokumentation

- [Erste Schritte](/de/guide/erste-schritte) - Lernen Sie die Grundlagen von K-Systems
- [Berichte](/de/guide/berichte) - Wie Sie Berichte mit Ihren Vorlagen erstellen und verwalten
- [Dokumente](/de/guide/dokumente) - Arbeiten mit Dokumenten und Ihren Ansichtspräferenzen
- [Admin: Benutzerverwaltung](/de/guide/admin/benutzerverwaltung) - Für Administratoren zur Verwaltung von Benutzerkonten

---

**Zuletzt aktualisiert:** 2025-10-01
**Version:** 2.0.0 (Korrigiert, um der tatsächlichen Implementierung zu entsprechen)
