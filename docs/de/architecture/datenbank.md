# Datenbankarchitektur

Dieses Dokument bietet einen umfassenden Überblick über das K-Systems-Datenbankschema, Tabellenbeziehungen und Datenmodell.

## Datenbankübersicht

### Plattform

- **DBMS**: MariaDB 10.11+ oder MySQL 8.0+
- **Zeichensatz**: UTF-8 (utf8mb4_unicode_ci)
- **Storage-Engine**: InnoDB
- **Datenbankname**: `ksystems`

### Statistiken

- **Gesamtanzahl Tabellen**: 109+ Tabellen
- **Tabellen-Präfix**: `kdd_` (alle Tabellen)
- **Multi-Tenant**: Alle Datentabellen enthalten `authority_id`
- **Indizes**: Primary Keys, Foreign Keys und Performance-Indizes

## Schema-Organisation

### Kernsystem-Tabellen

#### Authority-Verwaltung

**kdd_authorities**
- Primäre Authority-/Mandanten-Definitionen
- Spalten: `id`, `name`, `display_name`, `description`, `active`

**kdd_authority_features**
- Verfügbare Systemfeatures
- Beispiele: dispatch, employee, reports, invoice

**kdd_authority_features_rel**
- Mapping, welche Features für jede Authority aktiviert sind
- Links: `authority_id` → `feature_id`

**kdd_authority_fields**
- Benutzerdefinierte Feldkonfiguration pro Authority
- Ermöglicht Authority-spezifische Datenanpassung

### Benutzer- und Zugriffskontrolle

**kdd_users**
```sql
CREATE TABLE kdd_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,  -- bcrypt gehasht
    email VARCHAR(255),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    authority_id INT NOT NULL,
    active TINYINT(1) DEFAULT 1,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id)
);
```

**kdd_roles**
```sql
CREATE TABLE kdd_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_name_authority (name, authority_id),
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id)
);
```

**kdd_permissions**
```sql
CREATE TABLE kdd_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(50),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**kdd_user_roles**
- Verknüpft Benutzer mit ihren zugewiesenen Rollen
- Many-to-Many: Benutzer können mehrere Rollen haben

**kdd_role_permissions**
- Verknüpft Rollen mit ihren Berechtigungen
- Many-to-Many: Rollen können mehrere Berechtigungen haben

### Mitarbeiterverwaltung

**kdd_employee**
```sql
CREATE TABLE kdd_employee (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_number VARCHAR(50),
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    date_of_birth DATE,
    hire_date DATE,
    department_id INT,
    rank_id INT,
    status ENUM('active', 'inactive', 'suspended', 'terminated'),
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    FOREIGN KEY (department_id) REFERENCES kdd_employee_departments(id),
    FOREIGN KEY (rank_id) REFERENCES kdd_employee_ranks(id)
);
```

**kdd_employee_departments**
- Abteilungsstruktur (Einsatz, Verwaltung, etc.)

**kdd_employee_ranks**
- Positions-/Rangdefinitionen (Feuerwehrmann, Hauptmann, Chef, etc.)

**kdd_employee_company**
- Firmenzugehörigkeiten für Mitarbeiter

**kdd_employee_company_rel**
- Mitarbeiter-Firma-Beziehungs-Mapping

### Berichtssystem

**kdd_report**
```sql
CREATE TABLE kdd_report (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_number VARCHAR(50),
    title VARCHAR(255) NOT NULL,
    description TEXT,
    report_date DATETIME NOT NULL,
    location VARCHAR(255),
    employee_id INT,
    category_id INT,
    status ENUM('draft', 'submitted', 'approved', 'archived'),
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    FOREIGN KEY (employee_id) REFERENCES kdd_employee(id),
    FOREIGN KEY (category_id) REFERENCES kdd_report_categories(id)
);
```

**kdd_report_categories**
- Berichtskategorisierung (Feuer, Medizinisch, Rettung, etc.)

**kdd_report_custom_fields**
- Dynamische benutzerdefinierte Felder für Berichte
- Ermöglicht Authority-spezifische Berichtsdaten

**kdd_report_custom_field_values**
- Werte für benutzerdefinierte Felder pro Bericht

**kdd_report_share**
- Berichtsfreigabe-Funktionalität
- Authority-übergreifende Berichtsfreigabe (wenn erlaubt)

### Dokumentenverwaltung

**kdd_doc_documents**
```sql
CREATE TABLE kdd_doc_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500),
    file_size INT,
    mime_type VARCHAR(100),
    area_id INT,
    category_id INT,
    uploaded_by INT,
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    FOREIGN KEY (area_id) REFERENCES kdd_doc_areas(id),
    FOREIGN KEY (uploaded_by) REFERENCES kdd_users(id)
);
```

**kdd_doc_areas**
- Dokumentenorganisationsbereiche

**kdd_doc_categories**
- Dokumentenkategorien innerhalb von Bereichen

**kdd_doc_area_permissions**
- Feinkörnige Zugriffskontrolle für Dokumentenbereiche

### Kalender und Termine

**kdd_calendar**
```sql
CREATE TABLE kdd_calendar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    all_day TINYINT(1) DEFAULT 0,
    location VARCHAR(255),
    event_type ENUM('meeting', 'training', 'shift', 'holiday', 'other'),
    created_by INT,
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    FOREIGN KEY (created_by) REFERENCES kdd_users(id)
);
```

**kdd_calendar_assigned**
- Benutzerzuweisungen zu Kalenderereignissen
- RSVP-Status-Tracking

**kdd_calendar_groups**
- Kalenderereignis-Gruppen

**kdd_calendar_group_members**
- Gruppenmitgliedschaft für Kalenderereignisse

### Nachrichtensystem

**kdd_messages**
```sql
CREATE TABLE kdd_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    recipient_id INT,
    group_id INT,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    read_status TINYINT(1) DEFAULT 0,
    read_at DATETIME,
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    FOREIGN KEY (sender_id) REFERENCES kdd_users(id),
    FOREIGN KEY (recipient_id) REFERENCES kdd_users(id),
    FOREIGN KEY (group_id) REFERENCES kdd_message_groups(id)
);
```

**kdd_message_groups**
- Gruppennachrichten-Funktionalität

**kdd_message_group_members**
- Gruppenmitgliedschaft für Messaging

**kdd_messages_status**
- Nachrichtenzustell- und Lesestatus-Tracking

### Einsatz und Operationen

**kdd_dispatch**
```sql
CREATE TABLE kdd_dispatch (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type ENUM('engine', 'ladder', 'rescue', 'ambulance', 'command', 'hazmat', 'utility'),
    status ENUM('available', 'on_call', 'out_of_service', 'training', 'maintenance'),
    station VARCHAR(100),
    call_sign VARCHAR(50),
    crew_size INT,
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id)
);
```

**kdd_dispatch_employees**
- Mitarbeiterzuweisungen zu Einsatzeinheiten

**kdd_dispatch_vehicles**
- Fahrzeugzuweisungen zu Einsatzeinheiten

**kdd_crew**
- Alternative Crew-Verwaltungsstruktur

**kdd_crew_employee_rel**
- Crew-Mitarbeiter-Beziehungen

### Firma und Geschäft

**kdd_companies**
```sql
CREATE TABLE kdd_companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),
    phone VARCHAR(50),
    email VARCHAR(255),
    tax_id VARCHAR(50),
    type_id INT,
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    FOREIGN KEY (type_id) REFERENCES kdd_company_types(id)
);
```

**kdd_company_types**
- Firmentyp-Kategorisierung

**kdd_company_history**
- Firmeninteraktions-Verlauf-Tracking

**kdd_company_extinguishers**
- Brandschutzausrüstungs-Tracking für Firmen

### Rechnungsverwaltung

**kdd_invoices**
```sql
CREATE TABLE kdd_invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(50) NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    company_id INT,
    person_id INT,
    total_amount DECIMAL(10, 2),
    paid_amount DECIMAL(10, 2) DEFAULT 0,
    status ENUM('unpaid', 'partial', 'paid', 'overdue', 'cancelled'),
    notes TEXT,
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    FOREIGN KEY (company_id) REFERENCES kdd_companies(id)
);
```

**kdd_invoice_entries**
```sql
CREATE TABLE kdd_invoice_entries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES kdd_invoices(id) ON DELETE CASCADE
);
```

### Schulungsverwaltung

**kdd_training**
- Schulungsprogramm-Definitionen

**kdd_training_assigns**
- Schulungszuweisungen an Mitarbeiter
- Abschlussstatus und Zertifizierungs-Tracking

**kdd_training_certifications**
- Mitarbeiterzertifizierungen

**kdd_training_requirements**
- Erforderliche Schulungen nach Rang/Position

### Todo- und Aufgabenverwaltung

**kdd_todo_lists**
```sql
CREATE TABLE kdd_todo_lists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    owner_id INT NOT NULL,
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    FOREIGN KEY (owner_id) REFERENCES kdd_users(id)
);
```

**kdd_todos**
- Einzelne Aufgaben innerhalb von Listen

**kdd_todo_checkboxes**
- Teilaufgaben/Checkboxen innerhalb von Todos

**kdd_todo_permissions**
- Benutzerberechtigungen für Todo-Listen

### Dateiverwaltungssysteme

**kdd_person_files**
- Zivilisten-/Personendatensätze
- Persönliche Informationsdatenbank

**kdd_vehicle_files**
- Fahrzeugregistrierungsdatenbank
- Fahrzeugverlaufs-Tracking

**kdd_apartment**
- Wohnobjekt-Datensätze
- Wohnungs-/Gebäudeverwaltung

**kdd_apartment_rel**
- Person-Wohnungs-Beziehungen (Eigentümer, Mieter, Vermieter)

### Bewerbungsverwaltung

**kdd_applicant**
```sql
CREATE TABLE kdd_applicant (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    birthdate DATE NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone_number VARCHAR(30),
    status ENUM('pending', 'approved', 'rejected'),
    type INT NOT NULL,  -- 1=firefighter, 2=administration
    interview_date DATE,
    interview_time TIME,
    info TEXT,
    authority_id INT NOT NULL,
    added DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id)
);
```

**kdd_applicant_questions**
- Bewerbungsformular-Fragen

**kdd_applicant_answers**
- Bewerber-Antworten auf Fragen

### Kommunikation und Ankündigungen

**kdd_blackboard**
- Ankündigungs-/Bulletin-Board-System

**kdd_blackboard_rel**
- Blackboard-Post-Beziehungen

**kdd_whiteboard**
- Kollaborative Whiteboard-Daten

**kdd_whiteboard_rel**
- Whiteboard-Freigabe und -Berechtigungen

### Systemadministration

**kdd_templates**
- Dokument- und Berichtsvorlagen

**kdd_template_categories**
- Vorlagenorganisation

**kdd_settings**
- Systemweite Einstellungen

**kdd_desktop_settings**
- Benutzer-Desktop-Einstellungen

**kdd_weather**
- Wetter-Widget-Konfiguration

### Audit und Logging

**kdd_access_logs**
```sql
CREATE TABLE kdd_access_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    table VARCHAR(255) NOT NULL,
    userid INT NOT NULL,
    entry_id INT,
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    authority_id INT NOT NULL,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    FOREIGN KEY (userid) REFERENCES kdd_users(id)
);
```

**kdd_database_logs**
- Datenbankänderungs-Audit-Trail
- Tracking von Create-, Update-, Delete-Operationen

**kdd_system_logs**
- Allgemeines Systemereignis-Logging

### Karten- und Standortdienste

**kdd_map_markers**
- Geografische Markierungen und Standorte

**kdd_map_layers**
- Kartenebenen-Definitionen

**kdd_map_settings**
- Kartenkonfiguration pro Authority

## Datenbankbeziehungen

### Wichtige Beziehungsmuster

#### One-to-Many-Beziehungen

```
kdd_authorities (1) ─── (N) kdd_users
kdd_authorities (1) ─── (N) kdd_employees
kdd_authorities (1) ─── (N) kdd_reports
kdd_employee (1) ─── (N) kdd_reports
kdd_invoices (1) ─── (N) kdd_invoice_entries
kdd_todo_lists (1) ─── (N) kdd_todos
kdd_todos (1) ─── (N) kdd_todo_checkboxes
```

#### Many-to-Many-Beziehungen

```
users ←→ kdd_user_roles ←→ roles
roles ←→ kdd_role_permissions ←→ permissions
employees ←→ kdd_employee_company_rel ←→ companies
dispatch ←→ kdd_dispatch_employees ←→ employees
calendar ←→ kdd_calendar_assigned ←→ users
```

## Indizierungsstrategie

### Primary Keys

Alle Tabellen haben auto-inkrementierende Integer-Primary-Keys:
```sql
id INT PRIMARY KEY AUTO_INCREMENT
```

### Multi-Tenant-Indizes

Jede mandantenfähige Tabelle hat einen Authority-Index:
```sql
INDEX idx_authority (authority_id)
```

### Zusammengesetzte Indizes

Häufige Abfragemuster verwenden zusammengesetzte Indizes:
```sql
-- Mitarbeiterabfragen nach Authority und Status
INDEX idx_authority_status (authority_id, status)

-- Berichtsabfragen nach Authority und Datum
INDEX idx_authority_date (authority_id, report_date)

-- Dokumentabfragen nach Authority und Bereich
INDEX idx_authority_area (authority_id, area_id)
```

### Foreign-Key-Indizes

Alle Foreign-Key-Spalten sind indiziert:
```sql
INDEX idx_employee (employee_id)
INDEX idx_user (user_id)
INDEX idx_company (company_id)
```

### Unique-Constraints

Verhindern doppelte Einträge:
```sql
-- Benutzername eindeutig innerhalb Authority
UNIQUE KEY uk_username_authority (username, authority_id)

-- E-Mail eindeutig innerhalb Authority
UNIQUE KEY uk_email_authority (email, authority_id)

-- Rollenname eindeutig innerhalb Authority
UNIQUE KEY uk_role_authority (name, authority_id)
```

## Datentypen und Standards

### String-Felder

- **VARCHAR(50)**: Codes, kurze Bezeichner
- **VARCHAR(100)**: Namen, Titel
- **VARCHAR(255)**: Längerer Text, E-Mails, Pfade
- **TEXT**: Beschreibungen, Notizen, Inhalt
- **MEDIUMTEXT**: Großer Textinhalt

### Numerische Felder

- **INT**: IDs, Zählungen, Mengen
- **DECIMAL(10,2)**: Währungsbeträge
- **TINYINT(1)**: Boolean-Flags (0/1)

### Datum und Zeit

- **DATE**: Nur Datum (YYYY-MM-DD)
- **TIME**: Nur Zeit (HH:MM:SS)
- **DATETIME**: Datum und Zeit
- **TIMESTAMP**: Auto-aktualisierende Zeitstempel

### Enumerationen

```sql
-- Status-Enumerationen
status ENUM('active', 'inactive', 'suspended', 'terminated')

-- Typ-Enumerationen
type ENUM('engine', 'ladder', 'rescue', 'ambulance')

-- Zahlungsstatus
status ENUM('unpaid', 'partial', 'paid', 'overdue', 'cancelled')
```

## Abfragemuster

### Standard-Select mit Authority

```sql
SELECT * FROM kdd_employees
WHERE authority_id = ?
AND status = 'active'
ORDER BY last_name, first_name;
```

### Join mit Authority-Validierung

```sql
SELECT r.*, e.first_name, e.last_name
FROM kdd_reports r
JOIN kdd_employees e ON r.employee_id = e.id
WHERE r.authority_id = ?
AND e.authority_id = ?
AND r.report_date >= ?
ORDER BY r.report_date DESC;
```

### Aggregat-Abfragen

```sql
SELECT COUNT(*) as total_reports,
       COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved
FROM kdd_reports
WHERE authority_id = ?
AND report_date >= DATE_SUB(NOW(), INTERVAL 30 DAY);
```

## Performance-Optimierung

### Tipps zur Abfrage-Optimierung

1. **Immer zuerst nach authority_id filtern**
   - Reduziert Result-Set frühzeitig
   - Nutzt Authority-Indizes

2. **EXPLAIN zur Abfrageanalyse verwenden**
   ```sql
   EXPLAIN SELECT * FROM kdd_reports WHERE authority_id = 1;
   ```

3. **Result-Sets begrenzen**
   ```sql
   SELECT * FROM kdd_reports
   WHERE authority_id = ?
   ORDER BY created_at DESC
   LIMIT 100;
   ```

4. **EXISTS zur Existenzprüfung verwenden**
   ```sql
   SELECT EXISTS(
     SELECT 1 FROM kdd_users
     WHERE username = ? AND authority_id = ?
   );
   ```

### Connection Pooling

Datenbankverbindungen wiederverwenden:
```php
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);
```

### Prepared Statements

Immer Prepared Statements verwenden:
```php
$stmt = $pdo->prepare("
    SELECT * FROM kdd_employees
    WHERE authority_id = :aid AND id = :id
");
$stmt->execute([':aid' => $authorityId, ':id' => $employeeId]);
```

## Backup und Wiederherstellung

### Backup-Strategie

1. **Vollständige tägliche Backups**
   ```bash
   mysqldump --single-transaction ksystems > backup_$(date +%Y%m%d).sql
   ```

2. **Authority-spezifische Backups**
   ```bash
   mysqldump ksystems \
     --where="authority_id=1" \
     kdd_employees kdd_reports > authority1_backup.sql
   ```

3. **Inkrementelle Binärlogs**
   ```bash
   mysqlbinlog mysql-bin.000001 > incremental.sql
   ```

### Wiederherstellungsverfahren

```bash
# Vollständige Wiederherstellung
mysql ksystems < backup_20250120.sql

# Point-in-Time-Wiederherstellung
mysqlbinlog --stop-datetime="2025-01-20 14:30:00" \
  mysql-bin.000001 | mysql ksystems
```

## Migrationsverwaltung

### Schema-Migrationen

Migrationen gespeichert in `/backend/migrations/`:

```sql
-- migrations/0001_add_employee_status.sql
ALTER TABLE kdd_employees
ADD COLUMN status ENUM('active', 'inactive', 'suspended') DEFAULT 'active';
```

### Versionskontrolle

Schema-Änderungen nachverfolgen:
```sql
CREATE TABLE kdd_schema_versions (
    version VARCHAR(50) PRIMARY KEY,
    description TEXT,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Wartungsaufgaben

### Regelmäßige Wartung

1. **Tabellen analysieren**
   ```sql
   ANALYZE TABLE kdd_employees, kdd_reports, kdd_documents;
   ```

2. **Tabellen optimieren**
   ```sql
   OPTIMIZE TABLE kdd_messages;
   ```

3. **Tabellengesundheit prüfen**
   ```sql
   CHECK TABLE kdd_employees;
   ```

### Bereinigungsaufgaben

```sql
-- Alte Access-Logs entfernen
DELETE FROM kdd_access_logs
WHERE date < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Alte Berichte archivieren
INSERT INTO kdd_reports_archive
SELECT * FROM kdd_reports
WHERE report_date < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

## Datenbanksicherheit

### Benutzerrechte

```sql
-- Anwendungsbenutzer (eingeschränkte Rechte)
GRANT SELECT, INSERT, UPDATE, DELETE
ON ksystems.*
TO 'ksystems_app'@'localhost'
IDENTIFIED BY 'secure_password';

-- Read-Only-Benutzer für Reporting
GRANT SELECT ON ksystems.*
TO 'ksystems_reports'@'localhost';

-- Admin-Benutzer (volle Rechte)
GRANT ALL PRIVILEGES ON ksystems.*
TO 'ksystems_admin'@'localhost';
```

### Verbindungssicherheit

- SSL/TLS für Datenbankverbindungen verwenden
- Zugriff nach IP-Adresse beschränken
- Starke Passwörter verwenden
- Zugangsdaten regelmäßig wechseln

Die K-Systems-Datenbankarchitektur bietet eine robuste, skalierbare Grundlage für mandantenfähiges Enterprise-Management mit umfassenden Audit-Trails, flexiblen Datenstrukturen und optimierter Performance.
