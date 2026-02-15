# Fahrzeugakten

Grundlegende Fahrzeugverwaltung mit Inline-Detailansicht, die Fahrzeuginformationen, Eigentümer/Fahrer und Beschreibungen anzeigt.

## Übersicht

Funktionen:
- Verwaltung von Fahrzeuginformationen (Marke, Modell, Kennzeichen, Farbe)
- Zuweisung von Eigentümern und Fahrern aus Personenakten
- Erfassung des Zulassungsdatums
- Status-Markierungen für gestohlen/gesucht mit visuellen Chips
- Inline-Detailansicht mit 3 Tabs (Klick auf Kennzeichen zum Öffnen)
- Suchfunktion über Fahrzeugfelder
- Desktop-Fensterunterstützung mit Deep Linking
- Autoritätsspezifische Datenisolierung

**Erforderliche Berechtigung:** `READ_VEHICLE_FILE` (Ansicht), `WRITE_VEHICLE_FILE` (Erstellen/Bearbeiten), `DELETE_VEHICLE_FILE` (Löschen)

## Zugriff

**Desktop:** Doppelklick auf **Vehicle Files** Symbol
**Menü:** Startmenü → Files → Vehicle Files
**Route:** `/vehicleFile`
**Deep Link:** `/vehicleFile?id={id}` (öffnet spezifische Fahrzeug-Detailansicht)

## Oberflächenaufbau

### Fahrzeugliste Tabelle

**Tabellenspalten:**
- **Numberplate** - Kennzeichen (anklickbar für Inline-Detailansicht)
- **Brand** - Fahrzeughersteller
- **Model** - Fahrzeugmodell
- **Color** - Fahrzeugfarbe
- **Actions** - Bearbeiten- und Löschen-Schaltflächen

**Aktionsschaltfläche:**
- **+ New Vehicle** - Neue Fahrzeugakte erstellen

**Suche:**
- Echtzeit-Suche während der Eingabe
- Durchsucht: Kennzeichen, Marke, Modell, Farbe

### Inline-Detailansicht

**Öffnet sich wenn:** Klick auf Kennzeichen in der Tabelle oder Deep Link mit `?id={id}`

**Layout:**
- Ersetzt die Haupttabelle
- Zurück-Schaltfläche oben zur Rückkehr zur Liste
- Karte mit 3 Tabs
- Fahrzeugmarke/Modell wird im Header angezeigt
- Status-Chips werden im Header angezeigt (Gestohlen/Gesucht falls zutreffend)

**Tab 1: Fahrzeuginfo**
- Marke
- Modell
- Kennzeichen
- Farbe
- Gestohlen (Checkbox)
- Gesucht (Checkbox)
- Zugelassen (Datum)

**Tab 2: Eigentümer & Fahrer**
- **Eigentümer-Bereich:**
  - Datentabelle mit als Eigentümer zugewiesenen Personen
  - Spalten: Name, Telefonnummer, E-Mail
  - Zeigt "Keine Eigentümer" Nachricht wenn leer
- **Fahrer-Bereich:**
  - Datentabelle mit als Fahrer zugewiesenen Personen
  - Spalten: Name, Telefonnummer, E-Mail
  - Zeigt "Keine Fahrer" Nachricht wenn leer

**Tab 3: Beschreibung**
- HTML-Inhalt wird gerendert
- Zeigt "Keine Beschreibung" Nachricht wenn leer
- Nur-Lese-Anzeige

**Navigation:**
- Klick auf **Back to List** Schaltfläche zur Rückkehr zur Fahrzeugtabelle

## Fahrzeuge erstellen

### Neues Fahrzeug

1. Klick auf **+ New Vehicle** Schaltfläche
2. Hinzufügen-Dialog wird geladen (autoritätsspezifisch)
3. Fahrzeuginformationen ausfüllen
4. Eigentümer und Fahrer zuweisen (Personen-IDs)
5. Beschreibungstext hinzufügen (unterstützt HTML)
6. Klick auf **Save**
7. Fahrzeug wird zur Liste hinzugefügt

## Fahrzeuge anzeigen

### Fahrzeugdetails anzeigen (Inline)

1. Klick auf **Kennzeichen** des Fahrzeugs in der Tabelle
2. Inline-Detailansicht öffnet sich mit 3 Tabs
3. Tab 1: Fahrzeuginformationen anzeigen
4. Tab 2: Eigentümer- und Fahrertabellen mit Personendaten anzeigen
5. Tab 3: Beschreibungs-HTML-Inhalt anzeigen
6. Klick auf **Back to List** zur Rückkehr zur Tabelle

**Deep Linking:**
- Öffnen von `/vehicleFile?id=123` zeigt automatisch Inline-Details für Fahrzeug 123
- Funktioniert in Desktop-Fenstern

## Fahrzeuge bearbeiten

### Fahrzeug bearbeiten

1. Klick auf **Edit** Symbol (Stift) in der Fahrzeugzeile
2. Bearbeiten-Dialog wird geladen
3. Fahrzeuginformationen ändern
4. Eigentümer/Fahrer-Zuweisungen aktualisieren
5. Beschreibungstext bearbeiten
6. Klick auf **Save**
7. Änderungen werden übernommen

## Fahrzeuge löschen

### Fahrzeug löschen

1. Klick auf **Delete** Symbol (Papierkorb) in der Fahrzeugzeile
2. Löschvorgang im Dialog bestätigen
3. Fahrzeug wird aus der Datenbank entfernt

**Warnung:** Das Löschen ist permanent und kann nicht rückgängig gemacht werden.

## Suchfunktionalität

**Suchverhalten:**
- Befindet sich in der Symbolleiste
- Filtert Fahrzeuge in Echtzeit
- Durchsucht:
  - Kennzeichen
  - Marke
  - Modell
  - Farbe

## Datenmodell

**VehicleFile Interface:**
```typescript
interface VehicleFile {
  id: number;
  owners?: number[];        // Mehrere Eigentümer erlaubt
  drivers?: number[];       // Mehrere Fahrer erlaubt
  brand: string;
  model: string;
  numberplate: string;
  color: string;
  stolen: boolean;
  wanted: boolean;
  registered?: string;      // Datum (optional)
  text: string;             // HTML-Beschreibung
  is_deleted: boolean;
}
```

## API-Referenz

```http
GET /vehiclefile/?action=getVehicles
Returns: VehicleFile[]

GET /vehiclefile/?action=getPersons
Returns: PersonFile[]

POST /vehiclefile/?action=deleteVehicle
Body: { id }
Returns: Success message
```

## Desktop-Fensterunterstützung

**Deep Linking:**
- `route.query.id` - Aus URL-Query-Parameter
- `props.id` - Aus Desktop-Fenster-Props
- `props.meta.id` oder `props.meta.vehicleId` - Aus Fenster-Metadaten

## Best Practices

**Tun:**
✅ Alle erforderlichen Felder ausfüllen (Marke, Modell, Kennzeichen, Farbe)
✅ Eigentümer und Fahrer aus Personenakten zuweisen
✅ Gestohlen/Gesucht-Status korrekt markieren
✅ Zurück-Schaltfläche zur Rückkehr zur Liste verwenden

**Nicht tun:**
❌ Doppelte Einträge erstellen
❌ Kennzeichen leer lassen
❌ Unnötig löschen

## Fehlerbehebung

### Inline-Detailansicht wird nicht angezeigt

**Lösungen:**
1. Prüfen Sie, ob die Fahrzeug-ID existiert
2. Überprüfen Sie, ob die Personen-API funktioniert
3. Seite aktualisieren und erneut versuchen

### Eigentümer/Fahrer werden nicht angezeigt

**Lösungen:**
1. Überprüfen Sie, ob die Personen-API Daten zurückgibt: `/vehiclefile/?action=getPersons`
2. Prüfen Sie, ob vehicle.owners und vehicle.drivers Arrays gültige IDs enthalten
3. Seite aktualisieren, um Personendaten neu zu laden

### Deep Link funktioniert nicht

**Lösungen:**
1. Überprüfen Sie, ob die Fahrzeug-ID in der Datenbank existiert
2. Prüfen Sie, ob das Fahrzeug zu Ihrer Autorität gehört
3. Versuchen Sie stattdessen den Zugriff über Tabellenklick

## Einschränkungen

Die folgenden Funktionen **existieren nicht**:

❌ **Nicht verfügbar:**
- Filterung nach Gestohlen/Gesucht-Status
- Gestohlen/Gesucht-Spalten in der Haupttabelle
- Massenoperationen
- Export nach Excel/CSV/PDF
- Dokumentanhänge
- Serviceverlauf
- Wartungsprotokolle
- Versicherungsverfolgung
- Unfallberichte
- GPS/Standortverfolgung
- Kilometerstandverfolgung
- Fotoverwaltung
- FIN-Verfolgung
- Fahrzeugspezifikationen
- Kostenverfolgung
- Inspektionsverwaltung
- Bearbeitungsmodus in Inline-Detailansicht (nur Ansicht)

---

## Nächste Schritte

- [Person Files](/guide/person-files) - Personenkontakte für Eigentümer/Fahrer
- [Apartment Files](/guide/apartment-files) - Verwaltung von Wohnungsakten
- [Companies](/guide/companies) - Unternehmensverwaltung
