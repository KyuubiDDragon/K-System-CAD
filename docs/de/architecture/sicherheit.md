# Sicherheitsarchitektur

Dieses Dokument beschreibt die umfassende Sicherheitsarchitektur von K-Systems, einschließlich Authentifizierung, Autorisierung, Datenschutz und Compliance-Maßnahmen.

## Sicherheitsprinzipien

K-Systems basiert auf branchenüblichen Sicherheitsprinzipien:

1. **Defense in Depth**: Mehrere Ebenen von Sicherheitskontrollen
2. **Least Privilege**: Benutzern werden nur minimal notwendige Berechtigungen erteilt
3. **Separation of Duties**: Multi-Tenant-Isolierung verhindert Cross-Access
4. **Secure by Default**: Sicherheitsfunktionen standardmäßig aktiviert
5. **Audit Everything**: Umfassende Protokollierung von Sicherheitsereignissen

## Authentifizierungssystem

### JWT (JSON Web Token) Authentifizierung

K-Systems verwendet JWT für zustandslose Authentifizierung über alle Komponenten hinweg.

#### Token-Struktur

```json
{
  "userId": 42,
  "username": "john.doe",
  "email": "john.doe@example.com",
  "authority": "authority1",
  "authority_id": 1,
  "permissions": [
    "READ_REPORT",
    "WRITE_REPORT",
    "READ_EMPLOYEE"
  ],
  "roles": [
    "Firefighter",
    "Team Leader"
  ],
  "iat": 1706745600,
  "exp": 1706832000
}
```

#### Token-Generierung

```php
use Firebase\JWT\JWT;

function generateToken($user, $authorityId, $permissions, $roles) {
    $secretKey = $_ENV['JWT_SECRET'];
    $issuedAt = time();
    $expire = $issuedAt + (60 * 60 * 8); // 8 Stunden

    $payload = [
        'userId' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'authority' => $user['authority_name'],
        'authority_id' => $authorityId,
        'permissions' => $permissions,
        'roles' => $roles,
        'iat' => $issuedAt,
        'exp' => $expire
    ];

    return JWT::encode($payload, $secretKey, 'HS256');
}
```

#### Token-Validierung

```php
function validateToken($token) {
    try {
        $secretKey = $_ENV['JWT_SECRET'];
        $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));

        // Überprüfen, ob Token nicht abgelaufen ist
        if ($decoded->exp < time()) {
            throw new Exception('Token expired');
        }

        // Überprüfen, ob Authority noch aktiv ist
        if (!isValidAuthority($pdo, $decoded->authority_id)) {
            throw new Exception('Authority inactive');
        }

        return $decoded;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit();
    }
}
```

#### Cookie-basierte Token-Speicherung

Tokens werden in sicheren HTTP-Only-Cookies gespeichert:

```php
// Sicheres Cookie setzen
setcookie(
    'auth_token',
    $jwt,
    [
        'expires' => time() + (60 * 60 * 8),
        'path' => '/',
        'domain' => $_ENV['COOKIE_DOMAIN'],
        'secure' => true,      // Nur HTTPS
        'httponly' => true,    // Kein JavaScript-Zugriff
        'samesite' => 'Strict' // CSRF-Schutz
    ]
);
```

### Login-Flow

1. **Benutzer übermittelt Anmeldedaten**
   ```javascript
   const response = await fetch('/backend/login/', {
     method: 'POST',
     headers: { 'Content-Type': 'application/json' },
     body: JSON.stringify({ username, password })
   })
   ```

2. **Backend validiert Anmeldedaten**
   ```php
   $stmt = $pdo->prepare("
       SELECT id, username, password, authority_id
       FROM kdd_users
       WHERE username = :username AND active = 1
   ");
   $stmt->execute([':username' => $username]);
   $user = $stmt->fetch();

   if (!$user || !password_verify($password, $user['password'])) {
       throw new Exception('Invalid credentials');
   }
   ```

3. **JWT mit Benutzerkontext generieren**
4. **Sicheres Cookie setzen**
5. **Benutzerinformationen zurückgeben**

### Passwortsicherheit

#### Passwort-Hashing

```php
// Passwort hashen (Registrierung/Passwortänderung)
$hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Passwort verifizieren (Login)
if (password_verify($inputPassword, $storedHash)) {
    // Passwort korrekt
}
```

#### Passwortanforderungen

- Minimum 8 Zeichen
- Mix aus Groß- und Kleinbuchstaben
- Mindestens eine Zahl
- Mindestens ein Sonderzeichen
- Nicht in häufiger Passwortliste

```php
function validatePassword($password) {
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters";
    }

    if (!preg_match('/[A-Z]/', $password)) {
        return "Password must contain uppercase letter";
    }

    if (!preg_match('/[a-z]/', $password)) {
        return "Password must contain lowercase letter";
    }

    if (!preg_match('/[0-9]/', $password)) {
        return "Password must contain number";
    }

    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return "Password must contain special character";
    }

    return true;
}
```

#### Passwort-Zurücksetzung

Sichere Passwort-Zurücksetzung mit zeitlich begrenzten Tokens:

```php
function generatePasswordResetToken($userId) {
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 Stunde

    $stmt = $pdo->prepare("
        INSERT INTO kdd_password_resets (user_id, token, expires_at)
        VALUES (:uid, :token, :exp)
    ");
    $stmt->execute([
        ':uid' => $userId,
        ':token' => hash('sha256', $token),
        ':exp' => $expiry
    ]);

    return $token;
}
```

### Session-Verwaltung

- **Token-Ablauf**: 8 Stunden standardmäßig
- **Sliding Window**: Token bei Aktivität aktualisiert
- **Logout**: Token invalidiert und Cookie gelöscht
- **Gleichzeitige Sessions**: Pro Benutzer nachverfolgt

### Zwei-Faktor-Authentifizierung (2FA)

Optionale 2FA für erhöhte Sicherheit:

```php
function verifyTOTP($user, $code) {
    $secret = $user['totp_secret'];
    $ga = new GoogleAuthenticator();

    return $ga->verifyCode($secret, $code, 2); // 2 = Toleranz
}
```

## Autorisierungssystem

### Berechtigungsbasierte Zugriffskontrolle

K-Systems verwendet ein flexibles Berechtigungssystem mit Rollen.

#### Berechtigungshierarchie

```
ALL_PERMISSIONS (Superuser)
  ├─ Modul-Berechtigungen
  │   ├─ READ_MODULE
  │   ├─ WRITE_MODULE (impliziert READ)
  │   └─ DELETE_MODULE (erfordert WRITE)
  └─ Admin-Berechtigungen
      ├─ ADMIN_READ_USERS
      └─ ADMIN_WRITE_USERS (impliziert READ)
```

#### Berechtigungsprüfung

```php
function hasPermission($userPermissions, $requiredPermission) {
    // Auf ALL_PERMISSIONS prüfen
    if (in_array('ALL_PERMISSIONS', $userPermissions)) {
        return true;
    }

    // Auf spezifische Berechtigung prüfen
    if (in_array($requiredPermission, $userPermissions)) {
        return true;
    }

    // Auf implizierte Berechtigungen prüfen (WRITE impliziert READ)
    if (strpos($requiredPermission, 'READ_') === 0) {
        $writePermission = str_replace('READ_', 'WRITE_', $requiredPermission);
        if (in_array($writePermission, $userPermissions)) {
            return true;
        }
    }

    return false;
}
```

#### Frontend-Berechtigungs-Guards

```vue
<template>
  <v-btn
    v-if="hasPermission('WRITE_REPORT')"
    @click="editReport"
  >
    Edit Report
  </v-btn>
</template>

<script setup>
import { usePermissionCheck } from '@/composables/usePermissionCheck'

const { hasPermission } = usePermissionCheck()
</script>
```

#### Backend-Berechtigungsdurchsetzung

```php
$permissions_map = [
    'getData' => 'READ_REPORT',
    'saveData' => 'WRITE_REPORT',
    'deleteData' => 'DELETE_REPORT'
];

$required_permission = $permissions_map[$action];

if (!hasPermission($userPermissions, $required_permission)) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied']);
    exit();
}
```

### Rollenbasierte Zugriffskontrolle (RBAC)

#### Rollenstruktur

```sql
-- Rollen definieren
INSERT INTO kdd_roles (name, authority_id) VALUES
('Administrator', 1),
('Firefighter', 1),
('Captain', 1),
('Chief', 1);

-- Berechtigungen zu Rolle zuweisen
INSERT INTO kdd_role_permissions (role_id, permission_id) VALUES
(2, 1),  -- Firefighter: READ_REPORT
(2, 2),  -- Firefighter: WRITE_REPORT
(3, 1),  -- Captain: READ_REPORT
(3, 2),  -- Captain: WRITE_REPORT
(3, 5);  -- Captain: APPROVE_REPORT
```

#### Dynamisches Berechtigungsladen

```php
function getUserPermissions($userId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.name
        FROM kdd_permissions p
        JOIN kdd_role_permissions rp ON p.id = rp.permission_id
        JOIN kdd_user_roles ur ON rp.role_id = ur.role_id
        WHERE ur.user_id = :uid
    ");
    $stmt->execute([':uid' => $userId]);

    return array_column($stmt->fetchAll(), 'name');
}
```

## Datenschutz

### Verschlüsselung im Ruhezustand

Sensible Daten in Datenbank verschlüsselt:

```php
function encryptSensitiveData($data) {
    $key = $_ENV['ENCRYPTION_KEY'];
    $cipher = "aes-256-gcm";
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);

    $ciphertext = openssl_encrypt(
        $data,
        $cipher,
        $key,
        $options = 0,
        $iv,
        $tag
    );

    return base64_encode($iv . $tag . $ciphertext);
}

function decryptSensitiveData($encrypted) {
    $key = $_ENV['ENCRYPTION_KEY'];
    $cipher = "aes-256-gcm";
    $ivlen = openssl_cipher_iv_length($cipher);

    $data = base64_decode($encrypted);
    $iv = substr($data, 0, $ivlen);
    $tag = substr($data, $ivlen, 16);
    $ciphertext = substr($data, $ivlen + 16);

    return openssl_decrypt(
        $ciphertext,
        $cipher,
        $key,
        $options = 0,
        $iv,
        $tag
    );
}
```

### Verschlüsselung bei der Übertragung

- **HTTPS/TLS 1.3**: Alle Kommunikation verschlüsselt
- **WebSocket-Sicherheit**: WSS für Socket-Verbindungen
- **Datenbankverbindungen**: SSL/TLS für MySQL-Verbindungen

```php
// Datenbankverbindung mit SSL
$pdo = new PDO($dsn, $user, $pass, [
    PDO::MYSQL_ATTR_SSL_CA => '/path/to/ca-cert.pem',
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true
]);
```

### SQL-Injection-Prävention

Immer Prepared Statements verwenden:

```php
// ✅ SICHER
$stmt = $pdo->prepare("
    SELECT * FROM kdd_employees
    WHERE id = :id AND authority_id = :aid
");
$stmt->execute([':id' => $id, ':aid' => $authorityId]);

// ❌ ANFÄLLIG
$query = "SELECT * FROM kdd_employees WHERE id = $id";
$result = $pdo->query($query);
```

### XSS-Prävention

Output-Escaping und Content Security Policy:

```php
// Ausgabe escapen
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// Content-Security-Policy-Header
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';");
```

```vue
<!-- Vue escaped automatisch -->
<template>
  <div>{{ userInput }}</div>
</template>

<!-- Rohes HTML (gefährlich - vermeiden)
<div v-html="userInput"></div>
-->
```

### CSRF-Schutz

- **SameSite-Cookies**: `SameSite=Strict` auf Auth-Cookies
- **Token-Validierung**: CSRF-Tokens für zustandsändernde Operationen
- **Origin-Prüfung**: Request-Origin-Header validieren

```php
// CSRF-Token generieren
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF-Token validieren
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit();
    }
}
```

### Datei-Upload-Sicherheit

Strikte Validierung hochgeladener Dateien:

```php
function validateFileUpload($file, $allowedTypes, $maxSize) {
    // Prüfen, ob Datei hochgeladen wurde
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception('Invalid file upload');
    }

    // Dateigröße prüfen
    if ($file['size'] > $maxSize) {
        throw new Exception('File too large');
    }

    // MIME-Typ validieren
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Invalid file type');
    }

    // Sicheren Dateinamen generieren
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeFilename = bin2hex(random_bytes(16)) . '.' . $extension;

    return $safeFilename;
}
```

## Multi-Tenant-Sicherheit

### Authority-Isolierung

Jede Anfrage validiert Authority-Kontext:

```php
function validateAuthorityAccess($pdo, $authorityId, $userId) {
    $stmt = $pdo->prepare("
        SELECT 1 FROM kdd_users
        WHERE id = :uid AND authority_id = :aid AND active = 1
    ");
    $stmt->execute([':uid' => $userId, ':aid' => $authorityId]);

    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Authority access denied']);
        exit();
    }
}
```

### Cross-Authority-Schutz

Zugriff auf Daten anderer Authorities verhindern:

```php
function getEmployee($pdo, $employeeId, $authorityId) {
    // IMMER Authority validieren
    $stmt = $pdo->prepare("
        SELECT * FROM kdd_employees
        WHERE id = :id AND authority_id = :aid
    ");
    $stmt->execute([':id' => $employeeId, ':aid' => $authorityId]);

    $employee = $stmt->fetch();
    if (!$employee) {
        throw new Exception('Employee not found or access denied');
    }

    return $employee;
}
```

### Dateisystem-Isolierung

Authority-spezifische Verzeichnisse:

```php
function getAuthorityUploadPath($authorityId, $type) {
    $basePath = __DIR__ . '/../../uploads/';
    $authorityPath = $basePath . "authority{$authorityId}/{$type}/";

    // Verzeichnis erstellen, falls nicht vorhanden
    if (!file_exists($authorityPath)) {
        mkdir($authorityPath, 0755, true);
    }

    return $authorityPath;
}

function validateFilePath($authorityId, $filepath) {
    $authorityPath = getAuthorityUploadPath($authorityId, 'documents');
    $realPath = realpath($filepath);

    // Sicherstellen, dass Datei innerhalb des Authority-Verzeichnisses ist
    if (!$realPath || strpos($realPath, realpath($authorityPath)) !== 0) {
        throw new Exception('File access denied');
    }

    return $realPath;
}
```

## Audit-Logging

### Umfassender Audit-Trail

Alle sicherheitsrelevanten Ereignisse werden protokolliert:

```php
function logSecurityEvent($pdo, $userId, $authorityId, $eventType, $details) {
    $stmt = $pdo->prepare("
        INSERT INTO kdd_security_logs
        (user_id, authority_id, event_type, details, ip_address, user_agent, created_at)
        VALUES (:uid, :aid, :type, :details, :ip, :ua, NOW())
    ");

    $stmt->execute([
        ':uid' => $userId,
        ':aid' => $authorityId,
        ':type' => $eventType,
        ':details' => json_encode($details),
        ':ip' => $_SERVER['REMOTE_ADDR'],
        ':ua' => $_SERVER['HTTP_USER_AGENT']
    ]);
}
```

### Protokollierte Ereignisse

- **Authentifizierung**: Login, Logout, fehlgeschlagene Versuche
- **Autorisierung**: Berechtigungsverweigerte Ereignisse
- **Datenzugriff**: Lesevorgänge sensibler Daten
- **Datenänderung**: Create-, Update-, Delete-Operationen
- **Administrativ**: Benutzer-/Rollen-/Berechtigungsänderungen
- **Sicherheit**: Passwortänderungen, Token-Probleme

### Datenbankänderungs-Logging

```php
function logDatabaseChange($pdo, $userId, $table, $action, $recordId, $oldData, $newData) {
    $stmt = $pdo->prepare("
        INSERT INTO kdd_database_logs
        (user_id, table_name, action, record_id, old_values, new_values, created_at)
        VALUES (:uid, :table, :action, :rid, :old, :new, NOW())
    ");

    $stmt->execute([
        ':uid' => $userId,
        ':table' => $table,
        ':action' => $action,
        ':rid' => $recordId,
        ':old' => json_encode($oldData),
        ':new' => json_encode($newData)
    ]);
}
```

## Sicherheits-Header

### HTTP-Sicherheits-Header

```php
// Clickjacking verhindern
header("X-Frame-Options: DENY");

// XSS-Schutz aktivieren
header("X-XSS-Protection: 1; mode=block");

// MIME-Sniffing verhindern
header("X-Content-Type-Options: nosniff");

// Referrer-Policy
header("Referrer-Policy: strict-origin-when-cross-origin");

// Content-Security-Policy
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;");

// HTTPS-Durchsetzung
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
```

## API-Sicherheit

### Rate Limiting

Brute-Force- und DoS-Angriffe verhindern:

```php
function checkRateLimit($identifier, $maxRequests, $window) {
    $redis = new Redis();
    $redis->connect('localhost', 6379);

    $key = "rate_limit:{$identifier}";
    $current = $redis->incr($key);

    if ($current === 1) {
        $redis->expire($key, $window);
    }

    if ($current > $maxRequests) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded']);
        exit();
    }
}

// Verwendung
checkRateLimit($_SERVER['REMOTE_ADDR'], 100, 60); // 100 Anfragen pro Minute
```

### Eingabevalidierung

Strikte Eingabevalidierung auf allen Endpunkten:

```php
function validateInput($data, $rules) {
    foreach ($rules as $field => $rule) {
        if (!isset($data[$field]) && $rule['required']) {
            throw new Exception("Field {$field} is required");
        }

        if (isset($data[$field])) {
            $value = $data[$field];

            // Typvalidierung
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'int':
                        if (!filter_var($value, FILTER_VALIDATE_INT)) {
                            throw new Exception("Field {$field} must be integer");
                        }
                        break;
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            throw new Exception("Field {$field} must be valid email");
                        }
                        break;
                }
            }

            // Längenvalidierung
            if (isset($rule['max_length'])) {
                if (strlen($value) > $rule['max_length']) {
                    throw new Exception("Field {$field} too long");
                }
            }
        }
    }
}
```

## Compliance und Standards

### GDPR-Compliance

- **Recht auf Zugang**: Benutzer können ihre Daten exportieren
- **Recht auf Löschung**: Kontolöschungsfunktionalität
- **Datenminimierung**: Nur notwendige Daten sammeln
- **Einwilligungsverwaltung**: Explizites Benutzer-Consent-Tracking
- **Datenportabilität**: Export in Standardformaten

```php
function exportUserData($userId, $pdo) {
    $tables = [
        'kdd_users', 'kdd_employees', 'kdd_reports',
        'kdd_messages', 'kdd_documents'
    ];

    $data = [];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);
        $data[$table] = $stmt->fetchAll();
    }

    return json_encode($data, JSON_PRETTY_PRINT);
}
```

### Sicherheitszertifizierungen

Die K-Systems-Architektur unterstützt Compliance mit:
- **ISO 27001**: Informationssicherheitsmanagement
- **SOC 2**: Service-Organisation-Kontrollen
- **HIPAA**: Gesundheitsdatenschutz (falls zutreffend)
- **PCI DSS**: Zahlungskartendatensicherheit (falls zutreffend)

## Incident Response

### Sicherheitsüberwachung

- Echtzeit-Überwachung fehlgeschlagener Login-Versuche
- Automatisierte Warnungen bei verdächtiger Aktivität
- Regelmäßige Sicherheitsprotokoll-Überprüfung

### Incident-Verfahren

1. **Erkennung**: Automatisierte Überwachung und Warnungen
2. **Analyse**: Protokolle überprüfen und Umfang bestimmen
3. **Eindämmung**: Betroffene Systeme isolieren
4. **Beseitigung**: Bedrohung und Schwachstellen entfernen
5. **Wiederherstellung**: Normalen Betrieb wiederherstellen
6. **Lessons Learned**: Post-Incident-Review

## Sicherheits-Best-Practices für Entwickler

1. **Niemals Benutzereingaben vertrauen** - Alles validieren und bereinigen
2. **Parametrisierte Abfragen verwenden** - SQL-Injection verhindern
3. **Berechtigungen bei jeder Anfrage prüfen** - Nicht auf Frontend verlassen
4. **Sicherheitsereignisse protokollieren** - Audit-Trail pflegen
5. **Abhängigkeiten aktuell halten** - Schwachstellen patchen
6. **HTTPS überall verwenden** - Alle Kommunikation verschlüsseln
7. **Rate Limiting implementieren** - Missbrauch verhindern
8. **Passwörter richtig hashen** - bcrypt mit ausreichendem Cost verwenden
9. **Authority-Kontext validieren** - Multi-Tenant-Isolierung durchsetzen
10. **Code auf Sicherheit überprüfen** - Peer-Review und Sicherheitstests

Die Sicherheitsarchitektur von K-Systems bietet umfassenden Schutz durch mehrere Verteidigungsebenen und gewährleistet Vertraulichkeit, Integrität und Verfügbarkeit der Daten bei gleichzeitiger Einhaltung relevanter Vorschriften und Standards.
