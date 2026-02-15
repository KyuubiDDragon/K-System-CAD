# Gebäude- und Wohnungsakten

Einfaches Gebäudeverzeichnis zur Speicherung grundlegender Gebäudeinformationen mit asynchronen komponentenbasierten Detailansichten.

## Überblick

Funktionen:
- Gebäude-/Wohnungsaufzeichnungen mit grundlegenden Informationen
- CRUD-Operationen (Erstellen, Lesen, Aktualisieren, Löschen)
- Suchfunktion (Name, Ort, Straße, Hausnummer)
- Asynchrones Laden von Komponenten für Detailansichten
- Autoritätsspezifische Datenisolation
- Tabellenansicht mit Paginierung

**Erforderliche Berechtigung:** `READ_APARTMENT_FILE` (Anzeigen), `WRITE_APARTMENT_FILE` (Erstellen/Bearbeiten), `DELETE_APARTMENT_FILE` (Löschen)

## Zugriff auf Wohnungsakten

**Desktop:** Doppelklick auf **Wohnungsakten**-Symbol
**Menü:** Startmenü → Dateien → Wohnungsakten
**Route:** `/apartmentFile`

## Oberfläche

### Hauptansicht - Gebäudetabelle

**Oberer Bereich:**
- Schaltfläche **Neue Wohnung** (wenn Sie die WRITE-Berechtigung haben)
- Info-Hinweis mit Erklärung

**Spalten der Gebäudetabelle:**
- **Name** - Gebäudename (anklickbar zur Anzeige)
- **Ort** (Location) - Stadt/Gebiet
- **Straße** (Street) - Straßenname
- **Hausnr.** (House number) - Gebäudenummer
- **Aktionen** (Actions) - Schaltflächen zum Bearbeiten und Löschen

**Tabellenfunktionen:**
- 25 Einträge pro Seite
- Sortierbare Spalten
- Hover-Effekte auf Zeilen
- Ladeanzeige während des Abrufens
- Leerer Zustand mit Symbol
- Client-seitige Suchfilterung

**Suchleiste:**
- Befindet sich in der Symbolleiste (oben rechts)
- Filtert nach: Name, Ort, Straße, Hausnummer
- Echtzeit-Filterung
- Löschbares Feld
- Maximale Breite 400px

### Detailansichten

**Anzeigemodi:**
- **Hinzufügen-Ansicht** - Neues Gebäude erstellen (Vollbild-Komponente)
- **Bearbeiten-Ansicht** - Bestehendes Gebäude ändern (Vollbild-Komponente)
- **Ansicht-Ansicht** - Schreibgeschützte Gebäudedetails (Vollbild-Komponente)

**Navigation:**
- Wenn die Detailansicht geöffnet wird, wird die Tabelle ausgeblendet
- Zurück-Schaltfläche erscheint oben
- Klicken Sie auf Zurück, um zur Tabelle zurückzukehren

**Komponenten-Laden:**
- Asynchrone Komponenten werden bei Bedarf geladen
- Autoritätsspezifische Komponenten aus `/components/ApartmentFile/Authority/`
- Komponenten: `Add.vue`, `Edit.vue`, `View.vue`

## Gebäude erstellen

### Neues Gebäude

1. Klicken Sie auf die Schaltfläche **Neue Wohnung** (erfordert `WRITE_APARTMENT_FILE`-Berechtigung)
2. Hinzufügen-Dialog-Komponente wird geladen
3. Füllen Sie die Gebäudeinformationen im Komponentenformular aus
4. Komponente sendet `apartment-added`-Event bei Erfolg
5. Tabelle wird automatisch aktualisiert
6. Erfolgs-Toast-Benachrichtigung
7. Kehrt zur Tabellenansicht zurück

**Berechtigungen:**
- Schaltfläche wird nur angezeigt, wenn `canEdit` wahr ist
- Prüft `props.canEdit` oder `route.meta.canEdit`

**Komponente:**
- `AuthorityAddApartmentFile`
- Asynchron geladen aus `@/components/ApartmentFile/Authority/Add.vue`
- Vollbildansicht ersetzt Tabelle
- Verwaltet eigene Formularvalidierung und API-Aufrufe

## Gebäude anzeigen

### Gebäudedetails anzeigen

1. Klicken Sie auf den Gebäudenamen in der Tabelle
2. Ansicht-Dialog-Komponente wird geladen
3. Schreibgeschützte Anzeige der Gebäudeinformationen
4. Schließen-Schaltfläche kehrt zur Tabelle zurück

**Komponente:**
- `AuthorityViewApartmentFile`
- Asynchron geladen aus `@/components/ApartmentFile/Authority/View.vue`
- Erhält `apartment-to-view`-Prop (Kopie des Wohnungsobjekts)
- Sendet `close`-Event, um zur Tabelle zurückzukehren

**Navigation:**
- Namensspalte ist als Link gestaltet (Zeiger-Cursor)
- Klicken Sie irgendwo auf den Namen, um die Ansicht zu öffnen

## Gebäude bearbeiten

### Gebäude bearbeiten

1. Klicken Sie auf das **Bearbeiten**-Symbol (Stift) in der Gebäudezeile
2. Bearbeiten-Dialog-Komponente wird geladen
3. Ändern Sie die Gebäudeinformationen im Komponentenformular
4. Komponente sendet `apartment-updated`-Event bei Erfolg
5. Tabelle wird automatisch aktualisiert
6. Erfolgs-Toast-Benachrichtigung
7. Kehrt zur Tabellenansicht zurück

**Bearbeitungsberechtigungen:**
- Schaltfläche wird nur angezeigt, wenn `canEdit` wahr ist
- Prüft `props.canEdit` oder `route.meta.canEdit`

**Komponente:**
- `AuthorityEditApartmentFile`
- Asynchron geladen aus `@/components/ApartmentFile/Authority/Edit.vue`
- Erhält `apartment-to-edit`-Prop (Kopie des Wohnungsobjekts)
- Vollbildansicht ersetzt Tabelle
- Verwaltet eigene Formularvalidierung und API-Aufrufe

## Gebäude löschen

### Gebäude löschen

1. Klicken Sie auf das **Löschen**-Symbol (Papierkorb) in der Gebäudezeile
2. Bestätigungsdialog erscheint:
   - Fehlersymbol und Titel
   - "Sind Sie sicher?"-Meldung mit Gebäudenamen
   - Warnung vor permanenter Löschung
3. Klicken Sie auf **Löschen**, um zu bestätigen
4. Oder **Abbrechen**, um abzubrechen
5. Gebäude wird aus der Datenbank entfernt
6. Tabelle wird automatisch aktualisiert
7. Erfolgs-Toast-Benachrichtigung

**Löschberechtigungen:**
- Schaltfläche wird nur angezeigt, wenn `canDelete` wahr ist
- Prüft `props.canDelete` oder `route.meta.canDelete`

**Bestätigungsdialog:**
- Maximale Breite: 500px
- Dunkles Theme
- Persistent (muss Option wählen)
- Ladezustand auf Löschen-Schaltfläche während des API-Aufrufs

**Warnung:** Das Löschen ist permanent und kann nicht rückgängig gemacht werden.

## Suchfunktion

**Suchverhalten:**
- Befindet sich in der Symbolleiste
- Filtert Gebäude in Echtzeit
- Groß-/Kleinschreibung wird nicht berücksichtigt
- Durchsucht mehrere Felder:
  - Gebäudename
  - Ort (Stadt/Gebiet)
  - Straßenname
  - Hausnummer

**Implementierung:**
- Client-seitige Filterung über berechnete Eigenschaft
- Keine API-Aufrufe bei der Suche
- Löschen-Schaltfläche zum Zurücksetzen der Suche
- Suche bleibt bestehen, bis sie gelöscht wird

**Verwendung:**
1. Geben Sie Text in das Suchfeld ein
2. Tabelle filtert automatisch
3. Zeigt nur übereinstimmende Gebäude an
4. Löschen, um alle Gebäude zu sehen

## Datenmodell

**Apartment-Schnittstelle:**
```typescript
interface Apartment {
  id: number;
  name: string;
  location: string;
  street: string;
  housenumber: string;
  authority_id: number;
  // Zusätzliche Felder werden von Detailkomponenten verwaltet
}
```

**Hinweis:** Das vollständige Wohnungsdatenmodell wird in Detailkomponenten definiert. Die Hauptansicht zeigt nur Name, Ort, Straße und Hausnummer an.

## API-Integration

**Basispfad:** `/apartmentfile/`

**Aktionen:**

**Wohnungen abrufen:**
```
GET /apartmentfile?action=getApartments
Response: { data: Apartment[] }
```

**Wohnung löschen:**
```
POST /apartmentfile?action=deleteApartment
Payload: { id: number }
```

**Hinzufügen/Bearbeiten-Aktionen:**
- Werden von den jeweiligen Detailkomponenten verwaltet
- Nicht im Hauptansichtscode verfügbar

**Fehlerbehandlung:**
- Toast-Benachrichtigungen für Fehler
- Konsolen-Logging für Debugging
- Ladezustände während der Operationen
- Tabelle wird nach erfolgreichen Operationen aktualisiert

## Komponentenarchitektur

**Hauptansicht (ApartmentView.vue):**
- Tabellenanzeige
- Suchfunktion
- Dialog-Zustandsverwaltung
- Ereignisbehandlung
- API-Aufrufe für Liste und Löschen

**Detailkomponenten (Asynchron):**
- Hinzufügen-Komponente: Formular zum Erstellen
- Bearbeiten-Komponente: Formular zum Aktualisieren
- Ansicht-Komponente: Schreibgeschützte Anzeige

**Komponentenkommunikation:**
- Props: Übergeben von Wohnungsdaten an Komponenten
- Events: Komponenten senden Events bei Erfolg/Schließen
- v-model: Dialog-Sichtbarkeitsbindung

**Asynchrones Laden:**
```typescript
const AuthorityAddApartmentFile = defineAsyncComponent(
  () => import('@/components/ApartmentFile/Authority/Add.vue')
);
```

**Vorteile:**
- Reduzierte anfängliche Bundle-Größe
- Komponenten werden nur bei Bedarf geladen
- Bessere Performance für die Haupttabellenansicht

## Desktop-Fenstermodus

**Props-Unterstützung:**
```typescript
interface Props {
  meta?: Record<string, any>;
  canEdit?: boolean;
  canDelete?: boolean;
  canCreate?: boolean;
  allPermissions?: boolean;
  desktopWindow?: boolean;
}
```

**Berechtigungsauflösung:**
1. Prüfe `props.allPermissions` (gewährt alle)
2. Prüfe individuelle Prop-Berechtigungen
3. Fallback auf `route.meta`-Berechtigungen
4. Schaltflächen werden basierend auf Berechtigungen angezeigt/ausgeblendet

## Multi-Mandanten-Sicherheit

**Autoritätsisolation:**
- Alle Gebäude sind auf `authority_id` beschränkt
- Benutzer sehen nur Gebäude ihrer Organisation
- Backend filtert automatisch nach Autorität
- Zugriff über Organisationen hinweg wird verhindert

**Berechtigungsprüfungen:**
- `READ_APARTMENT_FILE` - Gebäudeliste und Details anzeigen
- `WRITE_APARTMENT_FILE` - Gebäude erstellen und bearbeiten
- `DELETE_APARTMENT_FILE` - Gebäude löschen

## Styling & Theme

**Dunkles Theme:**
- Dunkle Karten mit Elevation
- Primärfarben-Akzente
- Tonale Varianten für Hinweise
- Komfortable Dichte

**Tabellen-Styling:**
- Hover-Effekte auf Zeilen
- Anklickbare Namenslinks (Primärfarbe)
- Aktions-Icon-Schaltflächen (klein, Text-Variante)
- Leere und Ladezustände zentriert

**Responsive:**
- Fluid-Container mit Padding
- Tabelle passt sich der Bildschirmgröße an
- Suchfeld maximale Breite 400px
- Dialoge maximale Breite 500px (Löschen)

**Detailansicht-Layout:**
- Vollbild, wenn Detailansicht geöffnet wird
- Tabelle wird vollständig ausgeblendet
- Zurück-Schaltfläche oben
- Sauberer Übergang zwischen Ansichten

## Einschränkungen

**Was NICHT verfügbar ist:**

### Gebäudeinformationen
- ❌ Gebäudefotos/Bilder
- ❌ Baujahr
- ❌ Anzahl der Stockwerke
- ❌ Anzahl der Einheiten
- ❌ Quadratmeterzahl
- ❌ Gebäudetyp-Klassifizierung
- ❌ Eigentümer/Hausverwaltung
- ❌ Kontaktinformationen

### Brandschutz
- ❌ Sprinkleranlagen-Tracking
- ❌ Brandmeldeanlage-Details
- ❌ Steigleitungssystem
- ❌ Feuerlöscher-Standorte
- ❌ Notausgangs-Mapping
- ❌ Feuerwehranschluss-Standort
- ❌ Knox-Box-Informationen

### Zugangsinformationen
- ❌ Schlüsselstandort/Knox-Box
- ❌ Torcodes
- ❌ Gebäudezugriffsmethoden
- ❌ Aufzugszugangsdetails
- ❌ Kontakte außerhalb der Geschäftszeiten

### Versorgungseinrichtungen
- ❌ Gasabsperrung-Standort
- ❌ Stromverteiler-Standort
- ❌ Wasserabsperrung-Standort
- ❌ HVAC-System-Details

### Grundrisse
- ❌ Grundriss-Upload
- ❌ Grundriss-Viewer
- ❌ Anmerkungen auf Grundrissen
- ❌ Notausgänge markieren
- ❌ Versorgungsstandorte markieren
- ❌ Gefahren markieren

### Einheitenverwaltung
- ❌ Einzelne Einheitenaufzeichnungen
- ❌ Einheitennummern
- ❌ Bewohnerinformationen
- ❌ Tracking besonderer Bedürfnisse
- ❌ Haustierinformationen
- ❌ Notfallkontakte pro Einheit

### Inspektionen
- ❌ Inspektionsplanung
- ❌ Inspektionsverlauf
- ❌ Inspektions-Checklisten
- ❌ Inspektionsfotos
- ❌ Verstoß-Tracking
- ❌ Follow-up-Management

### Vorplanung
- ❌ Notfall-Reaktionsinformationen
- ❌ Gefahrstoff-Dokumentation
- ❌ Bauart-Details
- ❌ Dachzugangs-Informationen
- ❌ Keller-/Untergeschossbereiche
- ❌ Reaktionsstrategien

### Erweiterte Funktionen
- ❌ Kartenansicht mit Standorten
- ❌ Gebäudefotos/Galerien
- ❌ Dokument-Anhänge
- ❌ Notizen oder Kommentare
- ❌ Tags oder Kategorien
- ❌ Status-Workflow
- ❌ Letzte Inspektion-Tracking
- ❌ Berichterstattung oder Analysen
- ❌ Export-Funktion
- ❌ Import-Funktion
- ❌ Massenoperationen
- ❌ Erweiterte Filterung

### UI-Einschränkungen
- ❌ Haupttabelle zeigt begrenzte Felder (nur Name, Ort, Straße, Hausnummer)
- ❌ Keine Schnellansicht/Vorschau
- ❌ Keine Inline-Bearbeitung
- ❌ Kein sortierbares Drag-Drop
- ❌ Keine Favoriten oder Lesezeichen
- ❌ Keine Liste der letzten Gebäude
- ❌ Tabelle kann nicht angepasst werden (feste Spalten)

**Aktuelle Realität:**
Dies ist ein **einfaches Gebäudeverzeichnis** mit grundlegenden CRUD-Operationen. Vollständige Gebäudedetails und Funktionalität werden durch die autoritätsspezifischen Detailkomponenten bestimmt. Es ist kein umfassendes Gebäudeverwaltungs-, Inspektions- oder Vorplanungssystem.

## Anwendungsfälle

**Gebäudeverzeichnis:**
- Gebäudeliste pflegen
- Grundlegende Adressinformationen speichern
- Schnelle Suche nach Name oder Ort
- Einfache Referenzdatenbank

**Grundlegende Aufzeichnungen:**
- Gebäudenamen verfolgen
- Adressen aufzeichnen
- Mit Autorität verknüpfen
- Einfache Organisation

## Best Practices

**Gebäude erstellen:**
1. Verwenden Sie klare, beschreibende Gebäudenamen
2. Geben Sie vollständige Adressinformationen ein
3. Seien Sie konsistent mit Namenskonventionen
4. Überprüfen Sie Informationen in der Detailkomponente vor dem Speichern

**Suchen:**
1. Verwenden Sie Teilnamen für breitere Ergebnisse
2. Suchen Sie nach Straße für Gebietssuchen
3. Versuchen Sie Hausnummer für bestimmtes Gebäude
4. Löschen Sie die Suche, um die vollständige Liste zu sehen

**Organisation:**
1. Halten Sie Aufzeichnungen aktuell
2. Löschen Sie doppelte Einträge
3. Verwenden Sie konsistente Adressformatierung
4. Überprüfen Sie die Datengenauigkeit regelmäßig

## Fehlerbehebung

### Kann kein Gebäude erstellen

**Problem:** Schaltfläche "Neue Wohnung" funktioniert nicht oder fehlt

**Lösungen:**
1. Überprüfen Sie, ob Sie die `WRITE_APARTMENT_FILE`-Berechtigung haben
2. Verifizieren Sie, dass die Funktion für Ihre Autorität aktiviert ist
3. Seite aktualisieren
4. Browser-Konsole auf Fehler prüfen

### Detailkomponente wird nicht geladen

**Problem:** Klicken auf Neu/Bearbeiten/Ansicht zeigt kein Formular

**Lösungen:**
1. Browser-Konsole auf Komponenten-Ladefehler prüfen
2. Verifizieren Sie, dass Autoritätskomponente am korrekten Pfad existiert
3. Netzwerk-Tab auf fehlgeschlagene Imports prüfen
4. Seite aktualisieren und erneut versuchen

### Gebäude wird nicht gespeichert

**Problem:** Formular wird übermittelt, aber nicht gespeichert

**Lösungen:**
1. Detailkomponente auf Validierungsfehler prüfen
2. Internetverbindung überprüfen
3. Browser-Konsole auf API-Fehler prüfen
4. Überprüfen Sie, ob Backend läuft
5. Verifizieren Sie, dass Sie die `WRITE_APARTMENT_FILE`-Berechtigung haben

### Kann Gebäude nicht bearbeiten

**Problem:** Bearbeiten-Schaltfläche funktioniert nicht

**Lösungen:**
1. Überprüfen Sie, ob Sie die `WRITE_APARTMENT_FILE`-Berechtigung haben
2. Verifizieren Sie, dass Gebäude zu Ihrer Autorität gehört
3. Seite aktualisieren und erneut versuchen
4. Browser-Konsole auf Fehler prüfen

### Kann Gebäude nicht löschen

**Problem:** Löschen schlägt fehl oder Schaltfläche fehlt

**Lösungen:**
1. Überprüfen Sie, ob Sie die `DELETE_APARTMENT_FILE`-Berechtigung haben
2. Gebäude hat möglicherweise abhängige Datensätze
3. Verifizieren Sie, dass Gebäude zu Ihrer Autorität gehört
4. Fehlermeldung in Toast prüfen
5. Erwägen Sie Bearbeiten statt Löschen

### Suche funktioniert nicht

**Problem:** Suche filtert Liste nicht

**Lösungen:**
1. Rechtschreibung des Suchbegriffs prüfen
2. Versuchen Sie Teilübereinstimmung statt exakter Übereinstimmung
3. Verifizieren Sie, dass Gebäude mit diesem Begriff existieren
4. Suche löschen und erneut versuchen
5. Seite aktualisieren

### Gebäude werden nicht geladen

**Problem:** Leere Tabelle oder Lade-Spinner hört nicht auf

**Lösungen:**
1. Internetverbindung überprüfen
2. Verifizieren Sie, dass Backend läuft
3. Browser-Konsole auf API-Fehler prüfen
4. Netzwerk-Tab auf fehlgeschlagene Anfragen prüfen
5. Verifizieren Sie, dass authority_id korrekt ist

### Zurück-Schaltfläche funktioniert nicht

**Problem:** Kann nicht von Detailansicht zur Tabelle zurückkehren

**Lösungen:**
1. Klicken Sie auf Zurück-Schaltfläche am oberen Bildschirmrand
2. Seite aktualisieren, um Zustand zurückzusetzen
3. Browser-Konsole auf Fehler prüfen

## Verwandte Dokumentation

- [🏢 Unternehmen](/de/guide/unternehmen) - Unternehmensverwaltung
- [👤 Personenakten](/de/guide/personen-akten) - Personenkontakte
- [📄 Dokumente](/de/guide/dokumente) - Dokumentenverwaltung

---

**Zuletzt aktualisiert:** 2025-10-02
**Version:** 2.0.0 (Korrigiert, um der tatsächlichen Implementierung zu entsprechen)
