# Rollenverwaltung

Umfassende Anleitung zur Verwaltung von Rollen und Berechtigungen in K-Systems.

## Überblick

Die Rollenverwaltung ermöglicht Ihnen:
- Benutzerdefinierte Rollen erstellen
- Berechtigungen Rollen zuweisen
- Rollenhierarchie verwalten
- Rollen Benutzern zuweisen
- Rollenbasierte Zugriffskontrolle konfigurieren
- Rollennutzung prüfen

## Anforderungen

**Erforderliche Berechtigung:** `ADMIN_ROLE`

## Rollenverwaltung öffnen

**Desktop:** Start → Administration → Rollenverwaltung
**Menü:** Administration → Rollen

## Rollen verstehen

### Was sind Rollen?

**Rollen sind:**
- Benannte Sammlungen von Berechtigungen
- Benutzern zugewiesen
- Wiederverwendbar über mehrere Benutzer
- Behördenspezifisch (jede Behörde hat eigene Rollen)

**Beispiel-Rollen:**
- Super Admin
- Abteilungsleiter
- Teamleiter
- Mitarbeiter
- Betrachter

### Rolle vs Berechtigung

**Rolle:**
- Container für Berechtigungen
- Benutzerfreundlicher Name
- Geschäftslogik-Gruppierung
- Beispiel: "HR Manager"

**Berechtigung:**
- Spezifische Systemfähigkeit
- Technischer Identifikator
- Feingranularer Zugriff
- Beispiel: "READ_EMPLOYEE", "WRITE_REPORT"

### Rollenhierarchie

Rollen können hierarchisch sein:
```
Super Admin
├── Admin
│   ├── HR Manager
│   ├── IT Manager
│   └── Finance Manager
└── Manager
    ├── Team Lead
    └── Supervisor
```

Höhere Rollen erben niedrigere Rollenberechtigungen (falls konfiguriert).

## Rollenliste

### Oberfläche

**Rollentabelle:**
- **Rollenname**: Anzeigename
- **Beschreibung**: Rollenzweck
- **Benutzer**: Anzahl Benutzer mit dieser Rolle
- **Berechtigungen**: Anzahl Berechtigungen
- **Typ**: System/Benutzerdefiniert
- **Aktionen**: Bearbeiten, Löschen, Kopieren

### Systemrollen

**Eingebaute Rollen:**
- **Super Admin**: Voller Systemzugriff
- **Admin**: Behördenverwaltung
- **Benutzer**: Standard-Benutzerzugriff
- **Betrachter**: Nur-Lese-Zugriff

**Eigenschaften:**
- Können nicht gelöscht werden
- Können bearbeitet werden (vorsichtig)
- In allen Behörden vorhanden
- Empfohlener Ausgangspunkt

### Benutzerdefinierte Rollen

**Vom Benutzer erstellte Rollen:**
- Behördenspezifisch
- Auf Organisation zugeschnitten
- Können gelöscht werden
- Volle Anpassung

## Rollen erstellen

### Schritt 1: Grundinformationen

1. Klicken Sie auf **+ Neue Rolle**
2. Felder ausfüllen:

**Erforderlich:**
- **Rollenname**: Anzeigename
  - Klar, beschreibend
  - Beispiel: "HR Manager"
- **Beschreibung**: Rollenzweck
  - Wer sollte diese Rolle haben
  - Was sie tun können

**Optional:**
- **Rollenschlüssel**: Interner Identifikator
  - Automatisch vom Namen generiert
  - In Code/API verwendet
  - Beispiel: hr_manager
- **Farbe**: Visuelle Identifikation
  - In Badges verwendet
  - Rollenindikatoren

### Schritt 2: Berechtigungszuweisung

**Berechtigungen zuweisen:**

**Nach Kategorie:**
1. Kategorie zum Erweitern klicken:
   - Mitarbeiterverwaltung
   - Berichte
   - Dokumente
   - Kalender
   - Nachrichten
   - Administration
   - System
2. Berechtigungen zum Einschließen auswählen
3. **Alle auswählen** für Kategorie verwenden

**Nach Suche:**
1. Berechtigungsnamen suchen
2. Aus Ergebnissen auswählen
3. Zur Rolle hinzufügen

**Häufige Muster:**
- **Lesen + Schreiben**: Erstellen/Bearbeiten-Zugriff
- **Lesen + Schreiben + Löschen**: Volle Verwaltung
- **Admin**: Volle Modulkontrolle

### Berechtigungskategorien

**Mitarbeiterverwaltung:**
- `READ_EMPLOYEE` - Mitarbeiter anzeigen
- `WRITE_EMPLOYEE` - Mitarbeiter erstellen/bearbeiten
- `DELETE_EMPLOYEE` - Mitarbeiter löschen
- `READ_EMPLOYEE_SENSITIVE` - Sensible Daten anzeigen
- `ADMIN_EMPLOYEE` - Volle Mitarbeiterverwaltung

**Berichte:**
- `READ_REPORT` - Berichte anzeigen
- `WRITE_REPORT` - Berichte erstellen/bearbeiten
- `DELETE_REPORT` - Berichte löschen
- `SHARE_REPORT` - Berichte extern teilen
- `ADMIN_REPORT` - Berichtskonfiguration

**Dokumente:**
- `READ_DOCUMENT_*` - Dokumente anzeigen (pro Bereich)
- `WRITE_DOCUMENT_*` - Dokumente erstellen/bearbeiten
- `DELETE_DOCUMENT_*` - Dokumente löschen
- `ADMIN_DOCUMENT` - Dokumentbereiche verwalten

**Administration:**
- `ADMIN_USER` - Benutzerverwaltung
- `ADMIN_ROLE` - Rollenverwaltung
- `ADMIN_AUTHORITY` - Behördeneinstellungen
- `ADMIN_SETTINGS` - Systemeinstellungen

**Spezial:**
- `ALL_PERMISSIONS` - Super Admin (vorsichtig verwenden)
- `VIEW_LOGS` - Protokolle zugreifen
- `VIEW_ANALYTICS` - Statistiken anzeigen

### Schritt 3: Rolleneinstellungen

**Erweiterte Einstellungen:**

**Vererbung:**
- **Übergeordnete Rolle**: Berechtigungen erben von
- Kind erbt alle übergeordneten Berechtigungen
- Plus eigene Berechtigungen
- Hierarchische Struktur

**Einschränkungen:**
- **Max Benutzer**: Anzahl Benutzer begrenzen
- **Ablaufdatum**: Rolle läuft ab
- **IP-Einschränkungen**: Nur spezifische IP-Bereiche
- **Zeiteinschränkungen**: Nur spezifische Stunden

**Funktionen:**
- **Self-Service**: Benutzer können Rolle anfordern
- **Genehmigung erforderlich**: Admin-Genehmigung erforderlich
- **Auto-Zuweisen**: Neue Benutzer erhalten diese Rolle
- **Versteckt**: Nicht in Benutzerlisten anzeigen

### Schritt 4: Rolle speichern

**Speicheroptionen:**
- **Speichern**: Rolle erstellen, schließen
- **Speichern & Zuweisen**: Erstellen und Benutzern zuweisen
- **Speichern & Kopieren**: Erstellen und in Zwischenablage kopieren
- **Abbrechen**: Verwerfen

## Rollen bearbeiten

### Rolle ändern

1. Klicken Sie auf **Bearbeiten** bei Rolle
2. Felder ändern:
   - Name/Beschreibung
   - Berechtigungen (hinzufügen/entfernen)
   - Einstellungen
3. Klicken Sie auf **Speichern**

**Auswirkungen:**
- Alle Benutzer mit Rolle sofort aktualisiert
- Keine Neuzuweisung erforderlich
- Audit-Protokoll erstellt

### Berechtigungen hinzufügen/entfernen

**Berechtigung hinzufügen:**
1. Rolle bearbeiten
2. Zu Tab **Berechtigungen** gehen
3. Klicken Sie auf **Berechtigung hinzufügen**
4. Berechtigung(en) auswählen
5. Speichern

**Berechtigung entfernen:**
1. Rolle bearbeiten
2. Berechtigung in Liste finden
3. Klicken Sie auf ✖ zum Entfernen
4. Speichern
5. Auswirkung bestätigen

**Massenoperationen:**
- Mehrere Berechtigungen auswählen
- In einer Aktion hinzufügen/entfernen
- Aus anderer Rolle importieren

## Best Practices

### Rollendesign

**Tun Sie:**
✅ Rollen nach Jobfunktion erstellen
✅ Beschreibende Namen verwenden
✅ Rollenzweck dokumentieren
✅ Verwandte Berechtigungen gruppieren
✅ Rollenhierarchie verwenden
✅ Regelmäßige Rollenaudits

**Nicht tun:**
❌ Zu viele Rollen erstellen
❌ Funktionalität duplizieren
❌ Hierarchie überkomplizieren
❌ Allen Admin geben
❌ Vergessen zu dokumentieren
❌ Jobfunktionen mischen

### Berechtigungszuweisung

**Tun Sie:**
✅ Prinzip der geringsten Berechtigung folgen
✅ Nur benötigte Berechtigungen gewähren
✅ Lesen vor Schreiben verwenden
✅ Mit echten Benutzern testen
✅ Regelmäßig überprüfen

**Nicht tun:**
❌ ALL_PERMISSIONS weit verbreiten
❌ Testen überspringen
❌ Feedback ignorieren
❌ Übermäßig berechtigen
❌ Vergessen, Zugriff zu entfernen

## Fehlerbehebung

### Benutzer fehlt Berechtigung

**Checkliste:**
1. Benutzer hat Rolle verifizieren
2. Rolle hat Berechtigung überprüfen
3. Rolle ist aktiv überprüfen
4. Behördenübereinstimmung verifizieren
5. Feature aktiviert überprüfen
6. Berechtigungscache leeren

**Berechtigungscache:**
- Berechtigungen 1 Stunde gecacht
- Aktualisierung erzwingen: Neu anmelden
- Oder Admin: Benutzer-Cache leeren

### Rolle kann nicht gelöscht werden

**Häufige Ursachen:**
- Benutzer der Rolle zugewiesen
- Systemrolle (kann nicht gelöscht werden)
- In Konfiguration referenziert

**Lösung:**
1. Alle Benutzerzuweisungen entfernen
2. Auf Abhängigkeiten prüfen
3. Löschung erneut versuchen

---

## Nächste Schritte
- [👥 Benutzerverwaltung](/guide/admin/users)
- [🏢 Behördenverwaltung](/guide/admin/authority)
- [🔐 Berechtigungsreferenz](/api/permissions)
