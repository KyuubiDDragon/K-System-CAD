# Mandantenfähige Architektur

K-Systems implementiert eine robuste mandantenfähige Architektur, die es mehreren unabhängigen Organisationen (Authorities) ermöglicht, dieselbe Anwendungsinfrastruktur zu teilen, während gleichzeitig vollständige Datenisolierung und Sicherheit gewährleistet werden.

## Was ist Mandantenfähigkeit?

Mandantenfähigkeit ist eine Architektur, bei der eine einzelne Instanz der Software mehrere Kunden (Mandanten) bedient. Die Daten jedes Mandanten sind isoliert und bleiben für andere Mandanten unsichtbar.

### Vorteile

- **Kosteneffizienz**: Gemeinsame Infrastruktur reduziert Kosten
- **Einfachere Wartung**: Einzelne Codebasis zum Aktualisieren
- **Skalierbarkeit**: Neue Mandanten ohne Infrastrukturänderungen hinzufügen
- **Ressourcenoptimierung**: Bessere Nutzung der Rechenressourcen

### K-Systems-Ansatz

K-Systems verwendet einen **Shared Database, Shared Schema**-Ansatz mit der `authority_id`-Diskriminatorspalte.

## Das Authority-Konzept

### Was ist eine Authority?

In K-Systems repräsentiert eine "Authority" (deutsch: "Behörde") eine unabhängige Organisation oder einen Mandanten. Jede Authority:

- Hat ihre eigenen Benutzer, Mitarbeiter und Daten
- Kann nicht auf Daten anderer Authorities zugreifen
- Hat ihre eigene Feature-Konfiguration
- Hat unabhängiges Branding und Einstellungen

### Authority-Tabelle

```sql
CREATE TABLE kdd_authorities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Beispieldaten:**
```sql
INSERT INTO kdd_authorities (name, display_name) VALUES
('fd_city1', 'City 1 Fire Department'),
('fd_city2', 'City 2 Fire Department'),
('police_dept', 'Police Department');
```

### Authority-Identifikation

Die `authority_id` ist der Schlüssel zur Mandantenfähigkeit:

- **Enthalten in**: Jeder Tabelle, die mandantenspezifische Daten speichert
- **In JWT hinzugefügt**: Token enthält Authority-Kontext des Benutzers
- **In Abfragen verwendet**: Alle Abfragen filtern nach authority_id
- **Bei Anfrage validiert**: Backend überprüft Authority-Kontext

## Implementierung der Datenisolierung

### Datenbankebene

Jede mandantenfähige Tabelle enthält `authority_id`:

```sql
CREATE TABLE kdd_employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(255),
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    INDEX idx_authority (authority_id)
);
```

### Abfrage-Isolierung

Alle Abfragen filtern automatisch nach Authority:

```php
// KORREKT: Immer authority_id einbeziehen
$stmt = $pdo->prepare("
    SELECT * FROM kdd_employees
    WHERE authority_id = :authority_id
");
$stmt->bindParam(':authority_id', $authorityId);
$stmt->execute();

// FALSCH: Fehlender Authority-Filter
$stmt = $pdo->prepare("SELECT * FROM kdd_employees");
// Dies würde Daten aller Authorities offenlegen!
```

### JWT-Token-Struktur

Benutzer-Tokens enthalten Authority-Kontext:

```json
{
  "userId": 42,
  "username": "john.doe",
  "authority": "fd_city1",
  "authority_id": 1,
  "permissions": ["READ_REPORT", "WRITE_REPORT"],
  "roles": ["Firefighter"],
  "iat": 1706745600,
  "exp": 1706832000
}
```

### Backend-Validierung

Jeder Backend-Endpunkt validiert die Authority:

```php
// Authority aus JWT extrahieren
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

// Authority-Existenz und Aktivität validieren
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403);
    echo json_encode(["error" => "Invalid authority context"]);
    exit();
}

// Feature-Zugriff für diese Authority prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'employee')) {
    http_response_code(403);
    echo json_encode(["error" => "Feature not available"]);
    exit();
}
```

## Feature-Verwaltung

### Authority-Features-System

Nicht alle Authorities benötigen alle Features. Das System unterstützt Feature-Level-Zugriffskontrolle.

#### Feature-Definitionen

```sql
CREATE TABLE kdd_authority_features (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO kdd_authority_features (name, code) VALUES
('Dispatch System', 'dispatch'),
('Employee Management', 'employee'),
('Report System', 'reports'),
('Invoice System', 'invoice'),
('Training Management', 'training');
```

#### Feature-Zugriffskontrolle

```sql
CREATE TABLE kdd_authority_feature_access (
    id INT PRIMARY KEY AUTO_INCREMENT,
    authority_id INT NOT NULL,
    feature_id INT NOT NULL,
    enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    FOREIGN KEY (feature_id) REFERENCES kdd_authority_features(id),
    UNIQUE KEY uk_authority_feature (authority_id, feature_id)
);
```

#### Feature-Prüfung

```php
function hasFeatureAccess($pdo, $authorityId, $featureCode) {
    $stmt = $pdo->prepare("
        SELECT afa.enabled
        FROM kdd_authority_feature_access afa
        JOIN kdd_authority_features af ON afa.feature_id = af.id
        WHERE afa.authority_id = :aid
        AND af.code = :code
    ");
    $stmt->execute([
        ':aid' => $authorityId,
        ':code' => $featureCode
    ]);

    $result = $stmt->fetch();
    return $result && $result['enabled'] == 1;
}
```

## Benutzerauthentifizierung im Multi-Tenant-Kontext

### Login-Flow

1. Benutzer gibt Anmeldedaten ein und wählt Authority aus (falls zutreffend)
2. Backend validiert Anmeldedaten
3. System ruft authority_id des Benutzers ab
4. JWT-Token mit Authority-Kontext generiert
5. Alle nachfolgenden Anfragen verwenden diesen Authority-Kontext

### Single-Authority-Benutzer

Die meisten Benutzer gehören zu einer einzigen Authority:

```sql
CREATE TABLE kdd_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    authority_id INT NOT NULL,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    UNIQUE KEY uk_username_authority (username, authority_id)
);
```

### Authority-Wechsel

Für Benutzer, die Zugriff auf mehrere Authorities benötigen (selten):

```javascript
async function switchAuthority(newAuthorityId) {
  const response = await fetch('/backend/auth/switch-authority', {
    method: 'POST',
    credentials: 'include',
    body: JSON.stringify({ authority_id: newAuthorityId })
  })

  if (response.ok) {
    // Neues JWT-Token mit neuem Authority-Kontext ausgestellt
    location.reload()
  }
}
```

## Dateispeicher-Isolierung

### Authority-spezifische Verzeichnisse

Dateien sind nach Authority organisiert:

```
uploads/
├── authority1/
│   ├── documents/
│   │   ├── report_123.pdf
│   │   └── policy_456.pdf
│   ├── invoices/
│   ├── trainings/
│   └── avatars/
├── authority2/
│   ├── documents/
│   └── invoices/
└── authority3/
    └── documents/
```

### Dateizugriff-Validierung

```php
function getDocumentPath($authorityId, $documentId) {
    // Dokument gehört zur Authority validieren
    $stmt = $pdo->prepare("
        SELECT filename
        FROM kdd_documents
        WHERE id = :id AND authority_id = :aid
    ");
    $stmt->execute([':id' => $documentId, ':aid' => $authorityId]);
    $doc = $stmt->fetch();

    if (!$doc) {
        throw new Exception('Document not found or access denied');
    }

    $basePath = __DIR__ . "/../../uploads/authority{$authorityId}/documents/";
    $filePath = $basePath . $doc['filename'];

    // Datei existiert und ist innerhalb des Authority-Verzeichnisses
    $realPath = realpath($filePath);
    if (!$realPath || strpos($realPath, realpath($basePath)) !== 0) {
        throw new Exception('Invalid file path');
    }

    return $realPath;
}
```

## Cross-Authority-Sicherheit

### Verhinderung von Cross-Authority-Zugriff

Häufige Angriffsvektoren und Schutzmaßnahmen:

#### 1. Direkte ID-Manipulation

**Angriff:** Benutzer ändert `employee_id` in Anfrage, um auf Mitarbeiter einer anderen Authority zuzugreifen.

**Schutz:**
```php
// SCHLECHT: Prüft nur, ob Mitarbeiter existiert
$stmt = $pdo->prepare("SELECT * FROM kdd_employees WHERE id = :id");

// GUT: Validiert, dass Mitarbeiter zur Authority des Benutzers gehört
$stmt = $pdo->prepare("
    SELECT * FROM kdd_employees
    WHERE id = :id AND authority_id = :aid
");
$stmt->execute([':id' => $employeeId, ':aid' => $authorityId]);
```

#### 2. SQL-Injection mit Authority-Bypass

**Angriff:** SQL injizieren, um Authority-Filter zu umgehen.

**Schutz:** Immer Prepared Statements verwenden:
```php
// ANFÄLLIG
$sql = "SELECT * FROM employees WHERE authority_id = $authorityId";

// SICHER
$stmt = $pdo->prepare("SELECT * FROM employees WHERE authority_id = :aid");
$stmt->bindParam(':aid', $authorityId, PDO::PARAM_INT);
```

#### 3. File-Path-Traversal

**Angriff:** `../` in Dateipfaden verwenden, um auf Dateien anderer Authorities zuzugreifen.

**Schutz:**
```php
function validateFilePath($authorityId, $filename) {
    $basePath = __DIR__ . "/uploads/authority{$authorityId}/";
    $fullPath = $basePath . $filename;
    $realPath = realpath($fullPath);

    // Sicherstellen, dass Datei innerhalb des Authority-Verzeichnisses ist
    if (!$realPath || strpos($realPath, realpath($basePath)) !== 0) {
        throw new Exception('Access denied');
    }

    return $realPath;
}
```

#### 4. JWT-Token-Manipulation

**Angriff:** JWT-Token ändern, um authority_id zu ändern.

**Schutz:**
- JWT-Signatur-Validierung
- Serverseitige Authority-Überprüfung
- Kurze Token-Ablaufzeiten

```php
try {
    $decoded = JWT::decode($token, new Key($secret, 'HS256'));

    // Authority existiert und Benutzer gehört dazu überprüfen
    $stmt = $pdo->prepare("
        SELECT 1 FROM kdd_users
        WHERE id = :uid AND authority_id = :aid
    ");
    $stmt->execute([
        ':uid' => $decoded->userId,
        ':aid' => $decoded->authority_id
    ]);

    if (!$stmt->fetch()) {
        throw new Exception('Invalid authority context');
    }
} catch (Exception $e) {
    // Ungültiges oder manipuliertes Token
    http_response_code(401);
    exit();
}
```

## Super-Admin-Authority

### Systemadministrator-Zugriff

Super-Admins benötigen Zugriff auf alle Authorities für Wartungszwecke:

```sql
-- Spezielle system_admin-Authority
INSERT INTO kdd_authorities (name, display_name) VALUES
('system_admin', 'System Administrator');

-- Super-Admin-Benutzer
CREATE TABLE kdd_super_admins (
    user_id INT PRIMARY KEY,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES kdd_users(id)
);
```

### Super-Admin-Berechtigungen

```php
function isSuperAdmin($userId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT 1 FROM kdd_super_admins WHERE user_id = :uid
    ");
    $stmt->execute([':uid' => $userId]);
    return (bool)$stmt->fetch();
}

// In Abfragen
if (isSuperAdmin($userId, $pdo)) {
    // Kein Authority-Filter - kann alle Daten sehen
    $stmt = $pdo->prepare("SELECT * FROM kdd_authorities");
} else {
    // Regulärer Benutzer - nach Authority filtern
    $stmt = $pdo->prepare("
        SELECT * FROM kdd_data WHERE authority_id = :aid
    ");
    $stmt->bindParam(':aid', $authorityId);
}
```

### Super-Admin-Benutzeroberfläche

Frontend zeigt Authority-Auswahl:

```vue
<template>
  <v-select
    v-if="isSuperAdmin"
    v-model="selectedAuthority"
    :items="allAuthorities"
    label="Select Authority"
    @update:model-value="switchAuthority"
  />
</template>
```

## Authority-Branding

### Individuelles Branding pro Authority

Jede Authority kann individuelles Branding haben:

```sql
CREATE TABLE kdd_authority_settings (
    authority_id INT PRIMARY KEY,
    logo_url VARCHAR(255),
    primary_color VARCHAR(7),
    secondary_color VARCHAR(7),
    background_image VARCHAR(255),
    custom_css TEXT,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id)
);
```

### Dynamisches Theme-Laden

```javascript
// Frontend lädt Authority-spezifisches Branding
async function loadAuthorityBranding() {
  const response = await fetch('/backend/settings/branding')
  const branding = await response.json()

  // Benutzerdefinierte Farben anwenden
  document.documentElement.style.setProperty('--primary-color', branding.primary_color)
  document.documentElement.style.setProperty('--secondary-color', branding.secondary_color)

  // Benutzerdefiniertes Logo laden
  if (branding.logo_url) {
    document.querySelector('.logo').src = branding.logo_url
  }

  // Benutzerdefinierten Hintergrund anwenden
  if (branding.background_image) {
    document.body.style.backgroundImage = `url(${branding.background_image})`
  }
}
```

## Performance-Optimierung

### Indizierungsstrategie

Kritische Indizes für Multi-Tenant-Performance:

```sql
-- Authority-ID-Indizes auf allen mandantenfähigen Tabellen
CREATE INDEX idx_authority ON kdd_employees(authority_id);
CREATE INDEX idx_authority ON kdd_reports(authority_id);
CREATE INDEX idx_authority ON kdd_documents(authority_id);

-- Zusammengesetzte Indizes für häufige Abfragen
CREATE INDEX idx_authority_date ON kdd_reports(authority_id, created_at);
CREATE INDEX idx_authority_status ON kdd_employees(authority_id, status);
```

### Abfrage-Optimierung

```sql
-- Effizient: Verwendet Authority-Index
EXPLAIN SELECT * FROM kdd_employees
WHERE authority_id = 1 AND status = 'active';

-- Ergebnis: Verwendet idx_authority_status zusammengesetzten Index

-- Weniger effizient: Fehlender Authority-Filter
SELECT * FROM kdd_employees WHERE status = 'active';
-- Ergebnis: Full Table Scan über alle Authorities
```

### Connection Pooling

Datenbankverbindungen über Anfragen hinweg wiederverwenden:

```php
// Singleton-Pattern für PDO-Verbindung
class Database {
    private static $pdo = null;

    public static function getConnection() {
        if (self::$pdo === null) {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        }
        return self::$pdo;
    }
}
```

## Best Practices für Entwickler

### 1. Immer Authority-Kontext einbeziehen

```php
// ✅ KORREKT
function getEmployees($pdo, $authorityId) {
    return $pdo->prepare("
        SELECT * FROM kdd_employees WHERE authority_id = :aid
    ")->execute([':aid' => $authorityId]);
}

// ❌ FALSCH
function getEmployees($pdo) {
    return $pdo->query("SELECT * FROM kdd_employees");
}
```

### 2. Authority bei Beziehungen validieren

```php
// Beim Zugriff auf verwandte Daten Authority auf beiden Tabellen validieren
function getEmployeeReports($pdo, $employeeId, $authorityId) {
    return $pdo->prepare("
        SELECT r.*
        FROM kdd_reports r
        JOIN kdd_employees e ON r.employee_id = e.id
        WHERE e.id = :eid
        AND e.authority_id = :aid
        AND r.authority_id = :aid
    ")->execute([':eid' => $employeeId, ':aid' => $authorityId]);
}
```

### 3. Authority-Hilfsfunktionen verwenden

```php
// Zentralisierte Authority-Validierung
require_once 'utils/authority_helper.php';

if (!isValidAuthority($pdo, $authorityId)) {
    throw new Exception('Invalid authority');
}

if (!hasFeatureAccess($pdo, $authorityId, 'reports')) {
    throw new Exception('Feature not available');
}
```

### 4. Cross-Authority-Isolierung testen

```php
// Unit-Test zur Überprüfung der Isolierung
function testCrossAuthorityIsolation() {
    $user1 = loginAs('user1', 'authority1');
    $user2 = loginAs('user2', 'authority2');

    // Mitarbeiter in authority1 erstellen
    $employeeId = createEmployee($user1, ['name' => 'Test']);

    // Versuch, von authority2 zuzugreifen - sollte fehlschlagen
    $result = getEmployee($user2, $employeeId);
    assert($result === null, 'Cross-authority access prevented');
}
```

### 5. Authority-Anforderungen dokumentieren

```php
/**
 * Mitarbeiter nach ID abrufen
 *
 * @param PDO $pdo Datenbankverbindung
 * @param int $employeeId Mitarbeiter-ID zum Abrufen
 * @param int $authorityId Authority-Kontext (ERFORDERLICH)
 * @return array|null Mitarbeiterdaten oder null wenn nicht gefunden/Zugriff verweigert
 */
function getEmployee($pdo, $employeeId, $authorityId) {
    // ...
}
```

## Fehlerbehebung

### Häufige Probleme

#### Problem: Benutzer sehen Daten anderer Authorities

**Ursache:** Fehlender authority_id-Filter in Abfrage

**Lösung:**
```sql
-- Authority-Filter zu allen Abfragen hinzufügen
WHERE authority_id = :authority_id
```

#### Problem: Feature erscheint, aber wirft Fehler

**Ursache:** Feature nicht für Authority aktiviert

**Lösung:**
```sql
-- Feature für Authority aktivieren
INSERT INTO kdd_authority_feature_access
(authority_id, feature_id, enabled)
VALUES (1, 5, 1);
```

#### Problem: Datei-Upload schlägt fehl

**Ursache:** Authority-Verzeichnis existiert nicht

**Lösung:**
```php
// Authority-Verzeichnisstruktur erstellen
$uploadPath = __DIR__ . "/uploads/authority{$authorityId}/";
if (!file_exists($uploadPath)) {
    mkdir($uploadPath, 0755, true);
}
```

## Migrationsüberlegungen

### Neue Authority hinzufügen

```sql
-- 1. Authority erstellen
INSERT INTO kdd_authorities (name, display_name) VALUES
('new_authority', 'New Authority Name');

-- 2. Features aktivieren
INSERT INTO kdd_authority_feature_access (authority_id, feature_id, enabled)
SELECT LAST_INSERT_ID(), id, 1 FROM kdd_authority_features;

-- 3. Admin-Benutzer erstellen
INSERT INTO kdd_users (username, password, authority_id)
VALUES ('admin', '$2y$10$...', LAST_INSERT_ID());

-- 4. Admin-Rolle zuweisen
INSERT INTO kdd_user_roles (user_id, role_id)
VALUES (LAST_INSERT_ID(), 1);
```

### Datenmigration zwischen Authorities

```sql
-- Vorsicht bei Datenmigration - authority_id aktualisieren
UPDATE kdd_employees
SET authority_id = 2
WHERE id IN (...) AND authority_id = 1;

-- Alle zugehörigen Datensätze aktualisieren
UPDATE kdd_reports
SET authority_id = 2
WHERE employee_id IN (...);
```

## Compliance und Auditing

### Audit-Logging

Alle Authority-spezifischen Aktionen werden protokolliert:

```sql
CREATE TABLE kdd_audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    authority_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(100),
    table_name VARCHAR(100),
    record_id INT,
    old_values JSON,
    new_values JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    FOREIGN KEY (user_id) REFERENCES kdd_users(id)
);
```

### GDPR-Compliance

Authority-Level-Datenexport und -löschung:

```php
function exportAuthorityData($authorityId) {
    // Alle Daten für eine Authority exportieren
    $tables = ['kdd_employees', 'kdd_reports', 'kdd_documents'];
    $data = [];

    foreach ($tables as $table) {
        $stmt = $pdo->prepare("
            SELECT * FROM $table WHERE authority_id = :aid
        ");
        $stmt->execute([':aid' => $authorityId]);
        $data[$table] = $stmt->fetchAll();
    }

    return json_encode($data);
}
```

Die mandantenfähige Architektur in K-Systems bietet sichere, skalierbare und wartbare Trennung von Kundendaten bei gleichzeitiger Ermöglichung effizienter Ressourcenfreigabe und zentralisierter Verwaltung.
