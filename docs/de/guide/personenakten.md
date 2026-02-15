# Personenakten

Personenaktenverwaltung zur Speicherung von Kontaktinformationen, persönlichen Daten und Identifikationsdetails.

## Überblick

Die Personenakten ermöglichen Ihnen die Pflege von Aufzeichnungen über Personen mit Kontaktinformationen, Identifikation und persönlichen Details.

**Funktionen:**
- Umfassende Personeninformationen
- Suche über mehrere Felder
- CRUD-Operationen
- Fahndungsstatus-Kennzeichnung
- Autoritätsspezifische Datenisolation

**Erforderliche Berechtigung:** `READ_PERSON_FILE` (Ansicht), `WRITE_PERSON_FILE` (Erstellen/Bearbeiten), `DELETE_PERSON_FILE` (Löschen)

## Zugriff

**Desktop:** Doppelklick auf **Personenakten**-Symbol
**Menü:** Startmenü → Dateien → Personenakten
**Route:** `/person`

## Personenliste

### Tabellenansicht

**Tabellenspalten:**
- **Name**: Vollständiger Name (anklickbar für Details)
- **Telefonnummer**: Kontakttelefon
- **E-Mail**: E-Mail-Adresse
- **Aktionen**: Anzeigen, Bearbeiten, Löschen Buttons

**Suche:**
- Echtzeit-Suche während der Eingabe
- Durchsucht: Name, Telefon, E-Mail, Vorname, Nachname, Adresse, Personalausweis

**Aktions-Button:**
- **+ Person hinzufügen**: Neue Personenakte erstellen

## Personenakten erstellen

### Neue Person hinzufügen

1. Klicken Sie auf **+ Person hinzufügen** Button
2. Autoritätsspezifischer Dialog öffnet sich
3. Personeninformationen ausfüllen (Felder variieren je nach Authorität)
4. Klicken Sie auf **Speichern**
5. Person wird zur Liste hinzugefügt

**Häufige Felder:**
- **Name**: Vollständiger Name oder Nachname
- **Vorname**: Vorname
- **Nachname**: Familienname
- **Geschlecht**: M/W/Divers
- **Titel**: Herr, Frau, Dr., etc.
- **Geburtsort**: Geburtsort
- **Geburtsdatum**: Geburtsdatum
- **Telefonnummer**: Kontakttelefon
- **Adresse**: Vollständige Adresse
- **Personalausweis**: Personalausweisnummer
- **Bankkonto**: Bankkontodaten
- **E-Mail**: E-Mail-Adresse
- **Eintrag**: Eintragungs-/Registrierungsnotizen
- **Führerscheine**: Führerscheininformationen
- **Gesucht**: Als gesucht kennzeichnen (Checkbox)
- **Text**: Zusätzliche Notizen/Beschreibung

**Hinweis:** Die genauen Felder hängen von der autoritätsspezifischen Konfiguration ab.

## Personenakten anzeigen

### Personendetails anzeigen

1. Klicken Sie auf den **Namen** der Person in der Liste
2. Ansichts-Dialog öffnet sich
3. Zeigt alle Personeninformationen an
4. Dialog schließen, wenn fertig

**Ansichts-Dialog:**
- Zeigt alle ausgefüllten Felder an
- Nur-Lese-Ansicht
- Autoritätsspezifisches Layout

## Personenakten bearbeiten

### Person bearbeiten

1. Person in Liste finden
2. Klicken Sie auf **Bearbeiten** Button (Stift-Symbol)
3. Bearbeitungs-Dialog öffnet sich mit aktuellen Daten
4. Beliebige Felder ändern
5. Klicken Sie auf **Speichern**
6. Änderungen werden sofort übernommen

**Alle Felder bearbeitbar** außer Systemfelder (ID, created_at, etc.).

## Personenakten löschen

### Person löschen

1. Person in Liste finden
2. Klicken Sie auf **Löschen** Button (Papierkorb-Symbol)
3. Löschung bestätigen
4. Person wird soft-gelöscht (is_deleted Flag gesetzt)

**Erforderliche Berechtigung:** `DELETE_PERSON_FILE`

**Hinweis:** Gelöschte Personen werden ausgeblendet, verbleiben aber in der Datenbank.

## Suchen und Filtern

### Personen suchen

**Suchverhalten:**
- Suchleiste am Anfang der Liste
- Echtzeit-Filterung während der Eingabe
- Groß-/Kleinschreibung wird nicht berücksichtigt
- Durchsucht mehrere Felder gleichzeitig:
  - Name
  - Vorname
  - Nachname
  - Telefonnummer
  - E-Mail
  - Adresse
  - Personalausweisnummer

**Such-Tipps:**
- Teilnamen eingeben für breitere Ergebnisse
- Telefonnummernsuche findet exakte Übereinstimmungen
- E-Mail-Suche berücksichtigt Groß-/Kleinschreibung nicht
- Suche löschen, um alle Personen anzuzeigen

## Fahndungsstatus

### Als gesucht markieren

Beim Erstellen oder Bearbeiten einer Person:
1. **Gesucht** Checkbox aktivieren
2. Person speichern
3. Fahndungs-Flag wird gespeichert

**Anwendungsfall:** Personen zur Aufmerksamkeit oder Nachverfolgung kennzeichnen.

## Datenfelder

### Standardfelder

**Persönliche Informationen:**
- `id` - Eindeutige Kennung
- `name` - Vollständiger Name oder Nachname
- `firstname` - Vor-/Rufname
- `lastname` - Nach-/Familienname
- `fullname` - Berechneter vollständiger Name
- `gender` - Geschlecht
- `title` - Namenstitel/Präfix
- `birthplace` - Geburtsort
- `birthday` - Geburtsdatum (JJJJ-MM-TT)

**Kontaktinformationen:**
- `phonenumber` - Telefonnummer
- `address` - Vollständige Adresse
- `mail` - E-Mail-Adresse

**Identifikation:**
- `idcard` - Personalausweisnummer
- `bankaccount` - Bankkontodaten
- `licenses` - Führerscheininformationen

**Zusätzlich:**
- `entry` - Eintragungs-/Registrierungsnotizen
- `text` - Zusätzliche Notizen
- `wanted` - Fahndungsstatus-Flag (Boolean)

**Systemfelder:**
- `authority_id` - Organisations-ID
- `is_deleted` - Soft-Delete-Flag
- `created_at` - Erstellungszeitstempel
- `updated_at` - Letzter Aktualisierungszeitstempel

**Benutzerdefinierte Felder:**
- System bewahrt alle zusätzlichen Felder aus der Datenbank
- Benutzerdefinierte Felder werden beibehalten, werden aber möglicherweise nicht in der Benutzeroberfläche angezeigt

## Desktop-Fenster-Unterstützung

**Fenstereigenschaften:**
- Kann Personenakte im Desktop-Fenster öffnen
- Deep Linking: `/person?id={id}` öffnet spezifische Person
- Fenstertitel zeigt Personenname
- Fenster schließen kehrt zum Desktop zurück

## Mandantenfähige Sicherheit

**Autoritätsisolation:**
- Alle Personenakten gefiltert nach `authority_id`
- Benutzer sehen nur Personen aus ihrer Organisation
- Kein organisationsübergreifender Zugriff
- Automatische Filterung in allen Abfragen

## API-Referenz

### Endpunkte

```http
GET /personfile/?action=getPersons
Returns: Array von Personenakten für aktuelle Authorität

POST /personfile/?action=deletePerson
Body: { id }
Returns: Erfolgsmeldung
```

**Hinweis:** Hinzufügen/Bearbeiten-Operationen werden durch autoritätsspezifische Komponenten gehandhabt.

## TypeScript-Interface

```typescript
interface PersonFile {
  id: number;
  name: string;
  fullname: string;
  firstname: string;
  lastname: string;
  gender: string;
  title: string;
  birthplace: string;
  birthday: string;
  phonenumber: string;
  address: string;
  idcard: string;
  bankaccount: string;
  mail: string;
  entry: string;
  licenses: string;
  wanted: boolean;
  text: string;
  is_deleted: boolean;
  authority_id: number;
  [key: string]: any;  // Unterstützt benutzerdefinierte Felder
}
```

## Best Practices

**Empfohlen:**
✅ Alle verfügbaren Felder für vollständige Aufzeichnungen ausfüllen
✅ Konsistente Namensformatierung verwenden
✅ E-Mail-Adressen überprüfen
✅ Telefonnummern aktuell halten
✅ Suche nutzen, um Personen schnell zu finden
✅ Fahndungsstatus bei Bedarf aktualisieren
✅ Adressfeld für vollständige Adresse verwenden

**Nicht empfohlen:**
❌ Doppelte Einträge erstellen
❌ Namensfelder leer lassen
❌ Inkonsistente Datumsformate verwenden
❌ Personen unnötig löschen (sie werden archiviert, nicht entfernt)
❌ Sensible Personalausweisinformationen teilen

## Fehlerbehebung

### Person kann nicht erstellt werden

**Lösungen:**
1. Überprüfen Sie, dass Sie die `WRITE_PERSON_FILE` Berechtigung haben
2. Alle erforderlichen Felder ausfüllen (mindestens Name)
3. Browser-Konsole auf Fehler prüfen
4. Seite aktualisieren und erneut versuchen

### Person wird nicht in Liste angezeigt

**Lösungen:**
1. Suchfilter löschen
2. Prüfen, ob Person nicht gelöscht wurde
3. Seite aktualisieren (F5)
4. Überprüfen, ob dieselbe Authorität ausgewählt ist

### Suche findet Person nicht

**Lösungen:**
1. Andere Suchbegriffe versuchen
2. Stattdessen nach Telefon oder E-Mail suchen
3. Teilnamen verwenden
4. Suche löschen und neu eingeben
5. Überprüfen, ob Person in Datenbank existiert

### Bearbeitung wird nicht gespeichert

**Lösungen:**
1. `WRITE_PERSON_FILE` Berechtigung prüfen
2. Überprüfen, ob alle erforderlichen Felder ausgefüllt sind
3. Internetverbindung prüfen
4. Nach Fehlermeldungen suchen
5. Versuchen, zu aktualisieren und erneut zu bearbeiten

## Einschränkungen

Die folgenden in der Dokumentation erwähnten Funktionen **existieren nicht** in der aktuellen Codebasis:

❌ **Nicht verfügbar:**
- Inline-Bearbeitung in Tabelle
- Erweiterte Filterung (nach Fahndungsstatus, Datumsbereich, etc.)
- Massenoperationen (mehrere auswählen, Massenlöschung/Bearbeitung)
- Import aus CSV/Excel
- Export nach Excel/PDF
- Foto-/Avatar-Upload
- Dokumentenanhänge
- Aktivitätszeitstrahl/Verlaufsprotokoll
- Notizen-Tab
- Medizinische Informationen Bereich
- Notfallkontakte
- Mehrere Telefon-/E-Mail-Adressen
- Adressvalidierung
- Duplikaterkennung
- Personen zusammenführen Funktionalität
- Feldebenen-Berechtigungen
- Prüfprotokoll
- DSGVO-Funktionen (Datenexportanfrage, Recht auf Löschung)
- Aktenummer-System (automatisch generiert)
- Status-Workflow (Aktiv/Inaktiv/Archiviert/Verstorben)
- Automatische Altersberechnung
- Demografische Berichte
- Benutzerdefinierte Feldkonfiguration in Benutzeroberfläche

---

## Nächste Schritte

- [Fahrzeugakten](/guide/vehicle-files) - Fahrzeugaufzeichnungen verwalten
- [Wohnungsakten](/guide/apartment-files) - Wohnungsaufzeichnungen verwalten
- [Unternehmen](/guide/companies) - Unternehmenskontakte
- [Mitarbeiterverwaltung](/guide/employee-management) - Mitarbeiteraufzeichnungen
