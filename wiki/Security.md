# Security Architecture

This document outlines the comprehensive security architecture of K-Systems, including authentication, authorization, data protection, and compliance measures.

## Security Principles

K-Systems is built on industry-standard security principles:

1. **Defense in Depth**: Multiple layers of security controls
2. **Least Privilege**: Users granted minimum necessary permissions
3. **Separation of Duties**: Multi-tenant isolation prevents cross-access
4. **Secure by Default**: Security features enabled by default
5. **Audit Everything**: Comprehensive logging of security events

## Authentication System

### JWT (JSON Web Token) Authentication

K-Systems uses JWT for stateless authentication across all components.

#### Token Structure

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

#### Token Generation

```php
use Firebase\JWT\JWT;

function generateToken($user, $authorityId, $permissions, $roles) {
    $secretKey = $_ENV['JWT_SECRET'];
    $issuedAt = time();
    $expire = $issuedAt + (60 * 60 * 8); // 8 hours

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

#### Token Validation

```php
function validateToken($token) {
    try {
        $secretKey = $_ENV['JWT_SECRET'];
        $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));

        // Verify token hasn't expired
        if ($decoded->exp < time()) {
            throw new Exception('Token expired');
        }

        // Verify authority is still active
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

#### Cookie-Based Token Storage

Tokens are stored in secure HTTP-only cookies:

```php
// Set secure cookie
setcookie(
    'auth_token',
    $jwt,
    [
        'expires' => time() + (60 * 60 * 8),
        'path' => '/',
        'domain' => $_ENV['COOKIE_DOMAIN'],
        'secure' => true,      // HTTPS only
        'httponly' => true,    // No JavaScript access
        'samesite' => 'Strict' // CSRF protection
    ]
);
```

### Login Flow

1. **User submits credentials**
   ```javascript
   const response = await fetch('/backend/login/', {
     method: 'POST',
     headers: { 'Content-Type': 'application/json' },
     body: JSON.stringify({ username, password })
   })
   ```

2. **Backend validates credentials**
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

3. **Generate JWT with user context**
4. **Set secure cookie**
5. **Return user information**

### Password Security

#### Password Hashing

```php
// Hash password (registration/password change)
$hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Verify password (login)
if (password_verify($inputPassword, $storedHash)) {
    // Password correct
}
```

#### Password Requirements

- Minimum 8 characters
- Mix of uppercase and lowercase
- At least one number
- At least one special character
- Not in common password list

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

#### Password Reset

Secure password reset with time-limited tokens:

```php
function generatePasswordResetToken($userId) {
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour

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

### Session Management

- **Token Expiration**: 8 hours by default
- **Sliding Window**: Token refreshed on activity
- **Logout**: Token invalidated and cookie cleared
- **Concurrent Sessions**: Tracked per user

### Two-Factor Authentication (2FA)

Optional 2FA for enhanced security:

```php
function verifyTOTP($user, $code) {
    $secret = $user['totp_secret'];
    $ga = new GoogleAuthenticator();

    return $ga->verifyCode($secret, $code, 2); // 2 = tolerance
}
```

## Authorization System

### Permission-Based Access Control

K-Systems uses a flexible permission system with roles.

#### Permission Hierarchy

```
ALL_PERMISSIONS (superuser)
  +- Module Permissions
  |   +- READ_MODULE
  |   +- WRITE_MODULE (implies READ)
  |   +- DELETE_MODULE (requires WRITE)
  +- Admin Permissions
      +- ADMIN_READ_USERS
      +- ADMIN_WRITE_USERS (implies READ)
```

#### Permission Checking

```php
function hasPermission($userPermissions, $requiredPermission) {
    // Check for ALL_PERMISSIONS
    if (in_array('ALL_PERMISSIONS', $userPermissions)) {
        return true;
    }

    // Check for specific permission
    if (in_array($requiredPermission, $userPermissions)) {
        return true;
    }

    // Check for implied permissions (WRITE implies READ)
    if (strpos($requiredPermission, 'READ_') === 0) {
        $writePermission = str_replace('READ_', 'WRITE_', $requiredPermission);
        if (in_array($writePermission, $userPermissions)) {
            return true;
        }
    }

    return false;
}
```

#### Frontend Permission Guards

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

#### Backend Permission Enforcement

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

### Role-Based Access Control (RBAC)

#### Role Structure

```sql
-- Define roles
INSERT INTO kdd_roles (name, authority_id) VALUES
('Administrator', 1),
('Firefighter', 1),
('Captain', 1),
('Chief', 1);

-- Assign permissions to role
INSERT INTO kdd_role_permissions (role_id, permission_id) VALUES
(2, 1),  -- Firefighter: READ_REPORT
(2, 2),  -- Firefighter: WRITE_REPORT
(3, 1),  -- Captain: READ_REPORT
(3, 2),  -- Captain: WRITE_REPORT
(3, 5);  -- Captain: APPROVE_REPORT
```

#### Dynamic Permission Loading

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

## Data Protection

### Encryption at Rest

Sensitive data encrypted in database:

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

### Encryption in Transit

- **HTTPS/TLS 1.3**: All communications encrypted
- **WebSocket Security**: WSS for socket connections
- **Database Connections**: SSL/TLS for MySQL connections

```php
// Database connection with SSL
$pdo = new PDO($dsn, $user, $pass, [
    PDO::MYSQL_ATTR_SSL_CA => '/path/to/ca-cert.pem',
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true
]);
```

### SQL Injection Prevention

Always use prepared statements:

```php
// SECURE
$stmt = $pdo->prepare("
    SELECT * FROM kdd_employees
    WHERE id = :id AND authority_id = :aid
");
$stmt->execute([':id' => $id, ':aid' => $authorityId]);

// VULNERABLE
$query = "SELECT * FROM kdd_employees WHERE id = $id";
$result = $pdo->query($query);
```

### XSS Prevention

Output escaping and Content Security Policy:

```php
// Escape output
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// Content Security Policy header
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';");
```

```vue
<!-- Vue automatically escapes -->
<template>
  <div>{{ userInput }}</div>
</template>

<!-- Raw HTML (dangerous - avoid)
<div v-html="userInput"></div>
-->
```

### CSRF Protection

- **SameSite Cookies**: `SameSite=Strict` on auth cookies
- **Token Validation**: CSRF tokens for state-changing operations
- **Origin Checking**: Validate request origin headers

```php
// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit();
    }
}
```

### File Upload Security

Strict validation of uploaded files:

```php
function validateFileUpload($file, $allowedTypes, $maxSize) {
    // Check file was uploaded
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception('Invalid file upload');
    }

    // Check file size
    if ($file['size'] > $maxSize) {
        throw new Exception('File too large');
    }

    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Invalid file type');
    }

    // Generate safe filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeFilename = bin2hex(random_bytes(16)) . '.' . $extension;

    return $safeFilename;
}
```

## Multi-Tenant Security

For detailed information about multi-tenant data isolation, see [[Multi-Tenant]].

### Authority Isolation

Every request validates authority context:

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

### Cross-Authority Protection

Prevent access to other authorities' data:

```php
function getEmployee($pdo, $employeeId, $authorityId) {
    // ALWAYS validate authority
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

### File System Isolation

Authority-specific directories:

```php
function getAuthorityUploadPath($authorityId, $type) {
    $basePath = __DIR__ . '/../../uploads/';
    $authorityPath = $basePath . "authority{$authorityId}/{$type}/";

    // Create directory if doesn't exist
    if (!file_exists($authorityPath)) {
        mkdir($authorityPath, 0755, true);
    }

    return $authorityPath;
}

function validateFilePath($authorityId, $filepath) {
    $authorityPath = getAuthorityUploadPath($authorityId, 'documents');
    $realPath = realpath($filepath);

    // Ensure file is within authority directory
    if (!$realPath || strpos($realPath, realpath($authorityPath)) !== 0) {
        throw new Exception('File access denied');
    }

    return $realPath;
}
```

## Audit Logging

### Comprehensive Audit Trail

All security-relevant events are logged:

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

### Logged Events

- **Authentication**: Login, logout, failed attempts
- **Authorization**: Permission denied events
- **Data Access**: Sensitive data reads
- **Data Modification**: Create, update, delete operations
- **Administrative**: User/role/permission changes
- **Security**: Password changes, token issues

### Database Change Logging

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

## Security Headers

### HTTP Security Headers

```php
// Prevent clickjacking
header("X-Frame-Options: DENY");

// Enable XSS protection
header("X-XSS-Protection: 1; mode=block");

// Prevent MIME sniffing
header("X-Content-Type-Options: nosniff");

// Referrer policy
header("Referrer-Policy: strict-origin-when-cross-origin");

// Content Security Policy
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;");

// HTTPS enforcement
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
```

## API Security

### Rate Limiting

Prevent brute force and DoS attacks:

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

// Usage
checkRateLimit($_SERVER['REMOTE_ADDR'], 100, 60); // 100 requests per minute
```

### Input Validation

Strict input validation on all endpoints:

```php
function validateInput($data, $rules) {
    foreach ($rules as $field => $rule) {
        if (!isset($data[$field]) && $rule['required']) {
            throw new Exception("Field {$field} is required");
        }

        if (isset($data[$field])) {
            $value = $data[$field];

            // Type validation
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

            // Length validation
            if (isset($rule['max_length'])) {
                if (strlen($value) > $rule['max_length']) {
                    throw new Exception("Field {$field} too long");
                }
            }
        }
    }
}
```

## Compliance and Standards

### GDPR Compliance

- **Right to Access**: Users can export their data
- **Right to Erasure**: Account deletion functionality
- **Data Minimization**: Only collect necessary data
- **Consent Management**: Explicit user consent tracking
- **Data Portability**: Export in standard formats

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

### Security Certifications

K-Systems architecture supports compliance with:
- **ISO 27001**: Information security management
- **SOC 2**: Service organization controls
- **HIPAA**: Healthcare data protection (if applicable)
- **PCI DSS**: Payment card data security (if applicable)

## Incident Response

### Security Monitoring

- Real-time monitoring of failed login attempts
- Automated alerts for suspicious activity
- Regular security log review

### Incident Procedures

1. **Detection**: Automated monitoring and alerts
2. **Analysis**: Review logs and determine scope
3. **Containment**: Isolate affected systems
4. **Eradication**: Remove threat and vulnerabilities
5. **Recovery**: Restore normal operations
6. **Lessons Learned**: Post-incident review

## Security Best Practices for Developers

1. **Never trust user input** - Validate and sanitize everything
2. **Use parameterized queries** - Prevent SQL injection
3. **Check permissions on every request** - Don't rely on frontend
4. **Log security events** - Maintain audit trail
5. **Keep dependencies updated** - Patch vulnerabilities
6. **Use HTTPS everywhere** - Encrypt all communications
7. **Implement rate limiting** - Prevent abuse
8. **Hash passwords properly** - Use bcrypt with sufficient cost
9. **Validate authority context** - Enforce multi-tenant isolation
10. **Review code for security** - Peer review and security testing

The security architecture of K-Systems provides comprehensive protection through multiple layers of defense, ensuring data confidentiality, integrity, and availability while maintaining compliance with relevant regulations and standards.
