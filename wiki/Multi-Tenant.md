# Multi-Tenant Architecture

K-Systems implements a robust multi-tenant architecture that enables multiple independent organizations (authorities) to share the same application infrastructure while maintaining complete data isolation and security.

## What is Multi-Tenancy?

Multi-tenancy is an architecture where a single instance of the software serves multiple customers (tenants). Each tenant's data is isolated and remains invisible to other tenants.

### Benefits

- **Cost Efficiency**: Shared infrastructure reduces costs
- **Easier Maintenance**: Single codebase to update
- **Scalability**: Add new tenants without infrastructure changes
- **Resource Optimization**: Better utilization of computing resources

### K-Systems Approach

K-Systems uses a **shared database, shared schema** approach with the `authority_id` discriminator column.

## The Authority Concept

### What is an Authority?

In K-Systems, an "authority" (German: "Behorde") represents an independent organization or tenant. Each authority:

- Has its own users, employees, and data
- Cannot access other authorities' data
- Has its own feature configuration
- Has independent branding and settings

### Authority Table

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

**Example Data:**
```sql
INSERT INTO kdd_authorities (name, display_name) VALUES
('fd_city1', 'City 1 Fire Department'),
('fd_city2', 'City 2 Fire Department'),
('police_dept', 'Police Department');
```

### Authority Identification

The `authority_id` is the key to multi-tenancy:

- **Included in**: Every table that stores tenant-specific data
- **Added to JWT**: Token includes user's authority context
- **Used in queries**: All queries filter by authority_id
- **Validated on request**: Backend verifies authority context

## Data Isolation Implementation

### Database Level

Every multi-tenant table includes `authority_id`:

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

### Query Isolation

All queries automatically filter by authority:

```php
// CORRECT: Always include authority_id
$stmt = $pdo->prepare("
    SELECT * FROM kdd_employees
    WHERE authority_id = :authority_id
");
$stmt->bindParam(':authority_id', $authorityId);
$stmt->execute();

// INCORRECT: Missing authority filter
$stmt = $pdo->prepare("SELECT * FROM kdd_employees");
// This would expose all authorities' data!
```

### JWT Token Structure

User tokens include authority context:

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

### Backend Validation

Every backend endpoint validates authority:

```php
// Extract authority from JWT
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

// Validate authority exists and is active
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403);
    echo json_encode(["error" => "Invalid authority context"]);
    exit();
}

// Check feature access for this authority
if (!hasFeatureAccess($pdo, $authorityId, 'employee')) {
    http_response_code(403);
    echo json_encode(["error" => "Feature not available"]);
    exit();
}
```

## Feature Management

### Authority Features System

Not all authorities need all features. The system supports feature-level access control.

#### Feature Definitions

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

#### Feature Access Control

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

#### Feature Checking

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

## User Authentication in Multi-Tenant Context

### Login Flow

1. User enters credentials and selects authority (if applicable)
2. Backend validates credentials
3. System retrieves user's authority_id
4. JWT token generated with authority context
5. All subsequent requests use this authority context

### Single Authority Users

Most users belong to a single authority:

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

### Authority Switching

For users who need access to multiple authorities (rare):

```javascript
async function switchAuthority(newAuthorityId) {
  const response = await fetch('/backend/auth/switch-authority', {
    method: 'POST',
    credentials: 'include',
    body: JSON.stringify({ authority_id: newAuthorityId })
  })

  if (response.ok) {
    // New JWT token issued with new authority context
    location.reload()
  }
}
```

## File Storage Isolation

### Authority-Specific Directories

Files are organized by authority:

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

### File Access Validation

```php
function getDocumentPath($authorityId, $documentId) {
    // Validate document belongs to authority
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

    // Verify file exists and is within authority directory
    $realPath = realpath($filePath);
    if (!$realPath || strpos($realPath, realpath($basePath)) !== 0) {
        throw new Exception('Invalid file path');
    }

    return $realPath;
}
```

## Cross-Authority Security

### Preventing Cross-Authority Access

Common attack vectors and protections:

#### 1. Direct ID Manipulation

**Attack:** User modifies `employee_id` in request to access another authority's employee.

**Protection:**
```php
// BAD: Only checks if employee exists
$stmt = $pdo->prepare("SELECT * FROM kdd_employees WHERE id = :id");

// GOOD: Validates employee belongs to user's authority
$stmt = $pdo->prepare("
    SELECT * FROM kdd_employees
    WHERE id = :id AND authority_id = :aid
");
$stmt->execute([':id' => $employeeId, ':aid' => $authorityId]);
```

#### 2. SQL Injection with Authority Bypass

**Attack:** Inject SQL to bypass authority filter.

**Protection:** Always use prepared statements:
```php
// VULNERABLE
$sql = "SELECT * FROM employees WHERE authority_id = $authorityId";

// SECURE
$stmt = $pdo->prepare("SELECT * FROM employees WHERE authority_id = :aid");
$stmt->bindParam(':aid', $authorityId, PDO::PARAM_INT);
```

#### 3. File Path Traversal

**Attack:** Use `../` in file paths to access other authorities' files.

**Protection:**
```php
function validateFilePath($authorityId, $filename) {
    $basePath = __DIR__ . "/uploads/authority{$authorityId}/";
    $fullPath = $basePath . $filename;
    $realPath = realpath($fullPath);

    // Ensure file is within authority directory
    if (!$realPath || strpos($realPath, realpath($basePath)) !== 0) {
        throw new Exception('Access denied');
    }

    return $realPath;
}
```

#### 4. JWT Token Manipulation

**Attack:** Modify JWT token to change authority_id.

**Protection:**
- JWT signature validation
- Server-side authority verification
- Short token expiration times

```php
try {
    $decoded = JWT::decode($token, new Key($secret, 'HS256'));

    // Verify authority exists and user belongs to it
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
    // Invalid or manipulated token
    http_response_code(401);
    exit();
}
```

## Super Admin Authority

### System Administrator Access

Super admins need access to all authorities for maintenance:

```sql
-- Special system_admin authority
INSERT INTO kdd_authorities (name, display_name) VALUES
('system_admin', 'System Administrator');

-- Super admin users
CREATE TABLE kdd_super_admins (
    user_id INT PRIMARY KEY,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES kdd_users(id)
);
```

### Super Admin Permissions

```php
function isSuperAdmin($userId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT 1 FROM kdd_super_admins WHERE user_id = :uid
    ");
    $stmt->execute([':uid' => $userId]);
    return (bool)$stmt->fetch();
}

// In queries
if (isSuperAdmin($userId, $pdo)) {
    // No authority filter - can see all data
    $stmt = $pdo->prepare("SELECT * FROM kdd_authorities");
} else {
    // Regular user - filter by authority
    $stmt = $pdo->prepare("
        SELECT * FROM kdd_data WHERE authority_id = :aid
    ");
    $stmt->bindParam(':aid', $authorityId);
}
```

### Super Admin UI

Frontend shows authority selector:

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

## Authority Branding

### Custom Branding Per Authority

Each authority can have custom branding:

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

### Dynamic Theme Loading

```javascript
// Frontend loads authority-specific branding
async function loadAuthorityBranding() {
  const response = await fetch('/backend/settings/branding')
  const branding = await response.json()

  // Apply custom colors
  document.documentElement.style.setProperty('--primary-color', branding.primary_color)
  document.documentElement.style.setProperty('--secondary-color', branding.secondary_color)

  // Load custom logo
  if (branding.logo_url) {
    document.querySelector('.logo').src = branding.logo_url
  }

  // Apply custom background
  if (branding.background_image) {
    document.body.style.backgroundImage = `url(${branding.background_image})`
  }
}
```

## Performance Optimization

### Indexing Strategy

Critical indexes for multi-tenant performance:

```sql
-- Authority ID indexes on all multi-tenant tables
CREATE INDEX idx_authority ON kdd_employees(authority_id);
CREATE INDEX idx_authority ON kdd_reports(authority_id);
CREATE INDEX idx_authority ON kdd_documents(authority_id);

-- Composite indexes for common queries
CREATE INDEX idx_authority_date ON kdd_reports(authority_id, created_at);
CREATE INDEX idx_authority_status ON kdd_employees(authority_id, status);
```

### Query Optimization

```sql
-- Efficient: Uses authority index
EXPLAIN SELECT * FROM kdd_employees
WHERE authority_id = 1 AND status = 'active';

-- Result: Uses idx_authority_status composite index

-- Less efficient: Missing authority filter
SELECT * FROM kdd_employees WHERE status = 'active';
-- Result: Full table scan across all authorities
```

### Connection Pooling

Reuse database connections across requests:

```php
// Singleton pattern for PDO connection
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

## Best Practices for Developers

### 1. Always Include Authority Context

```php
// CORRECT
function getEmployees($pdo, $authorityId) {
    return $pdo->prepare("
        SELECT * FROM kdd_employees WHERE authority_id = :aid
    ")->execute([':aid' => $authorityId]);
}

// WRONG
function getEmployees($pdo) {
    return $pdo->query("SELECT * FROM kdd_employees");
}
```

### 2. Validate Authority on Relationships

```php
// When accessing related data, validate authority on both tables
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

### 3. Use Authority Helper Functions

```php
// Centralized authority validation
require_once 'utils/authority_helper.php';

if (!isValidAuthority($pdo, $authorityId)) {
    throw new Exception('Invalid authority');
}

if (!hasFeatureAccess($pdo, $authorityId, 'reports')) {
    throw new Exception('Feature not available');
}
```

### 4. Test Cross-Authority Isolation

```php
// Unit test to verify isolation
function testCrossAuthorityIsolation() {
    $user1 = loginAs('user1', 'authority1');
    $user2 = loginAs('user2', 'authority2');

    // Create employee in authority1
    $employeeId = createEmployee($user1, ['name' => 'Test']);

    // Try to access from authority2 - should fail
    $result = getEmployee($user2, $employeeId);
    assert($result === null, 'Cross-authority access prevented');
}
```

### 5. Document Authority Requirements

```php
/**
 * Get employee by ID
 *
 * @param PDO $pdo Database connection
 * @param int $employeeId Employee ID to retrieve
 * @param int $authorityId Authority context (REQUIRED)
 * @return array|null Employee data or null if not found/access denied
 */
function getEmployee($pdo, $employeeId, $authorityId) {
    // ...
}
```

## Troubleshooting

### Common Issues

#### Problem: Users see data from other authorities

**Cause:** Missing authority_id filter in query

**Solution:**
```sql
-- Add authority filter to all queries
WHERE authority_id = :authority_id
```

#### Problem: Feature appears but throws errors

**Cause:** Feature not enabled for authority

**Solution:**
```sql
-- Enable feature for authority
INSERT INTO kdd_authority_feature_access
(authority_id, feature_id, enabled)
VALUES (1, 5, 1);
```

#### Problem: File upload fails

**Cause:** Authority directory doesn't exist

**Solution:**
```php
// Create authority directory structure
$uploadPath = __DIR__ . "/uploads/authority{$authorityId}/";
if (!file_exists($uploadPath)) {
    mkdir($uploadPath, 0755, true);
}
```

## Migration Considerations

### Adding New Authority

```sql
-- 1. Create authority
INSERT INTO kdd_authorities (name, display_name) VALUES
('new_authority', 'New Authority Name');

-- 2. Enable features
INSERT INTO kdd_authority_feature_access (authority_id, feature_id, enabled)
SELECT LAST_INSERT_ID(), id, 1 FROM kdd_authority_features;

-- 3. Create admin user
INSERT INTO kdd_users (username, password, authority_id)
VALUES ('admin', '$2y$10$...', LAST_INSERT_ID());

-- 4. Assign admin role
INSERT INTO kdd_user_roles (user_id, role_id)
VALUES (LAST_INSERT_ID(), 1);
```

### Data Migration Between Authorities

```sql
-- Careful when migrating data - update authority_id
UPDATE kdd_employees
SET authority_id = 2
WHERE id IN (...) AND authority_id = 1;

-- Update all related records
UPDATE kdd_reports
SET authority_id = 2
WHERE employee_id IN (...);
```

## Compliance and Auditing

### Audit Logging

All authority-specific actions are logged:

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

### GDPR Compliance

Authority-level data export and deletion:

```php
function exportAuthorityData($authorityId) {
    // Export all data for an authority
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

The multi-tenant architecture in K-Systems provides secure, scalable, and maintainable separation of customer data while enabling efficient resource sharing and centralized management.
