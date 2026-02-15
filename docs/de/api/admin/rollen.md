# Rollenverwaltung API

Die Rollenverwaltung API bietet administrative Endpunkte zur Verwaltung von Rollen und ihren zugehörigen Berechtigungen.

## Base URL

```
/backend/admin/roles/
```

## Authentication

Alle Endpunkte erfordern administrative JWT-Authentifizierung mit `ADMIN_READ_USERS` oder `ADMIN_WRITE_USERS` Berechtigungen.

## Erforderliche Berechtigungen

- **ADMIN_READ_USERS**: Rollen und Berechtigungen anzeigen
- **ADMIN_WRITE_USERS**: Rollen erstellen, aktualisieren und löschen

## Endpoints

### Get Roles

**GET** `/backend/admin/roles/?action=getRoles`

Ruft alle Rollen in der Behörde ab.

**Erforderliche Berechtigung:** `ADMIN_READ_USERS`

**Response:**
```json
{
  "roles": [
    {
      "id": 1,
      "name": "Administrator",
      "description": "Full system access",
      "user_count": 3,
      "created_at": "2024-01-01 10:00:00"
    },
    {
      "id": 2,
      "name": "Firefighter",
      "description": "Standard firefighter access",
      "user_count": 45,
      "created_at": "2024-01-01 10:00:00"
    }
  ]
}
```

### Get Permissions

**GET** `/backend/admin/roles/?action=getPermissions`

Ruft alle im System verfügbaren Berechtigungen ab.

**Erforderliche Berechtigung:** `ADMIN_READ_USERS`

**Response:**
```json
{
  "permissions": [
    {
      "id": 1,
      "name": "READ_REPORT",
      "category": "Reports",
      "description": "View reports"
    },
    {
      "id": 2,
      "name": "WRITE_REPORT",
      "category": "Reports",
      "description": "Create and edit reports"
    },
    {
      "id": 3,
      "name": "DELETE_REPORT",
      "category": "Reports",
      "description": "Delete reports"
    }
  ]
}
```

### Get Role Permissions

**GET** `/backend/admin/roles/?action=getRolePermissions`

Ruft alle Rollen-Berechtigungs-Zuweisungen ab.

**Erforderliche Berechtigung:** `ADMIN_READ_USERS`

**Response:**
```json
{
  "role_permissions": [
    {
      "role_id": 1,
      "role_name": "Administrator",
      "permissions": [
        {
          "id": 1,
          "name": "READ_REPORT"
        },
        {
          "id": 2,
          "name": "WRITE_REPORT"
        },
        {
          "id": 3,
          "name": "DELETE_REPORT"
        }
      ]
    }
  ]
}
```

### Get Category Roles

**GET** `/backend/admin/roles/?action=getCategoryRoles`

Ruft Rollen organisiert nach Berechtigungskategorien ab.

**Erforderliche Berechtigung:** `ADMIN_READ_USERS`

**Response:**
```json
{
  "categories": [
    {
      "category": "Reports",
      "roles": [
        {
          "role_id": 1,
          "role_name": "Administrator",
          "permissions": ["READ_REPORT", "WRITE_REPORT", "DELETE_REPORT"]
        },
        {
          "role_id": 2,
          "role_name": "Firefighter",
          "permissions": ["READ_REPORT", "WRITE_REPORT"]
        }
      ]
    }
  ]
}
```

### Create Role

**POST** `/backend/admin/roles/?action=createRole`

Erstellt eine neue Rolle mit angegebenen Berechtigungen.

**Erforderliche Berechtigung:** `ADMIN_WRITE_USERS`

**Request:**
```json
{
  "name": "Captain",
  "description": "Fire captain with elevated permissions",
  "permissions": [1, 2, 5, 6, 10, 15]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Role created successfully",
  "role_id": 5
}
```

### Update Role

**POST** `/backend/admin/roles/?action=updateRole`

Aktualisiert eine bestehende Rolle und ihre Berechtigungen.

**Erforderliche Berechtigung:** `ADMIN_WRITE_USERS`

**Request:**
```json
{
  "id": 5,
  "name": "Captain",
  "description": "Fire captain with command authority",
  "permissions": [1, 2, 3, 5, 6, 10, 15, 20]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Role updated successfully"
}
```

### Delete Role

**POST** `/backend/admin/roles/?action=deleteRole`

Löscht eine Rolle (nur wenn keine Benutzer ihr zugewiesen sind).

**Erforderliche Berechtigung:** `ADMIN_WRITE_USERS`

**Request:**
```json
{
  "id": 5
}
```

**Response:**
```json
{
  "success": true,
  "message": "Role deleted successfully"
}
```

## Permission Categories

Berechtigungen sind in Kategorien organisiert:

- **Reports**: Berichtverwaltungsberechtigungen
- **Documents**: Dokumentenverwaltungsberechtigungen
- **Employees**: Mitarbeiterverwaltungsberechtigungen
- **Calendar**: Kalender- und Ereignisberechtigungen
- **Messages**: Nachrichtenberechtigungen
- **Admin**: Administrative Berechtigungen
- **Dispatch**: Einsatz- und Mannschaftsberechtigungen
- **Training**: Schulungsverwaltungsberechtigungen
- **Finance**: Rechnungs- und Finanzberechtigungen

## Common Permission Names

### Read Permissions
- `READ_REPORT`
- `READ_DOCUMENT`
- `READ_EMPLOYEE`
- `READ_CALENDAR`
- `READ_MESSAGE`
- `READ_DISPATCH`
- `READ_TRAINING`
- `READ_INVOICE`

### Write Permissions
- `WRITE_REPORT`
- `WRITE_DOCUMENT`
- `WRITE_EMPLOYEE`
- `WRITE_CALENDAR`
- `WRITE_MESSAGE`
- `WRITE_DISPATCH`
- `WRITE_TRAINING`
- `WRITE_INVOICE`

### Delete Permissions
- `DELETE_REPORT`
- `DELETE_DOCUMENT`
- `DELETE_EMPLOYEE`
- `DELETE_CALENDAR`
- `DELETE_MESSAGE`
- `DELETE_DISPATCH`
- `DELETE_TRAINING`
- `DELETE_INVOICE`

### Admin Permissions
- `ADMIN_READ_USERS`
- `ADMIN_WRITE_USERS`
- `ADMIN_SYSTEM_SETTINGS`
- `SUPER_ADMIN`

### Special Permissions
- `ALL_PERMISSIONS`: Vollständiger Systemzugriff
- `CAN_LOGIN`: Grundlegender Anmeldezugriff
- `APPROVE_VACATION`: Urlaubsanträge genehmigen

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad request - Ungültige Daten oder fehlende Felder |
| 403 | Forbidden - Unzureichende Admin-Berechtigungen |
| 404 | Not found - Rolle existiert nicht |
| 409 | Conflict - Rollenname existiert bereits oder Rolle hat zugewiesene Benutzer |
| 500 | Internal server error |

## Examples

### JavaScript

```javascript
// Get all roles
async function getRoles() {
  const response = await fetch('/backend/admin/roles/?action=getRoles', {
    credentials: 'include'
  });
  return await response.json();
}

// Get available permissions
async function getPermissions() {
  const response = await fetch('/backend/admin/roles/?action=getPermissions', {
    credentials: 'include'
  });
  return await response.json();
}

// Create new role
async function createRole(roleData) {
  const response = await fetch('/backend/admin/roles/?action=createRole', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(roleData)
  });
  return await response.json();
}

// Update role permissions
async function updateRole(roleId, name, description, permissions) {
  const response = await fetch('/backend/admin/roles/?action=updateRole', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      id: roleId,
      name,
      description,
      permissions
    })
  });
  return await response.json();
}

// Delete role
async function deleteRole(roleId) {
  const response = await fetch('/backend/admin/roles/?action=deleteRole', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: roleId })
  });
  return await response.json();
}
```

### cURL

```bash
# Get all roles
curl -X GET 'http://localhost:8080/backend/admin/roles/?action=getRoles' \
  -H 'Cookie: token=YOUR_JWT_TOKEN'

# Get all permissions
curl -X GET 'http://localhost:8080/backend/admin/roles/?action=getPermissions' \
  -H 'Cookie: token=YOUR_JWT_TOKEN'

# Create new role
curl -X POST 'http://localhost:8080/backend/admin/roles/?action=createRole' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Captain",
    "description": "Fire captain role",
    "permissions": [1, 2, 5, 6, 10]
  }'

# Update role
curl -X POST 'http://localhost:8080/backend/admin/roles/?action=updateRole' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "id": 5,
    "name": "Captain",
    "description": "Updated description",
    "permissions": [1, 2, 3, 5, 6, 10, 15]
  }'

# Delete role
curl -X POST 'http://localhost:8080/backend/admin/roles/?action=deleteRole' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{"id": 5}'
```

## Best Practices

1. **Principle of Least Privilege**: Gewähren Sie nur notwendige Berechtigungen
2. **Role Naming**: Verwenden Sie klare, beschreibende Rollennamen
3. **Documentation**: Dokumentieren Sie den Zweck jeder Rolle
4. **Regular Audits**: Überprüfen Sie Rollenzuweisungen regelmäßig
5. **Permission Groups**: Organisieren Sie Berechtigungen logisch
6. **Testing**: Testen Sie Rollenberechtigungen vor der Bereitstellung
7. **Backup**: Sichern Sie Rollenkonfigurationen vor größeren Änderungen

## Permission Hierarchy

Schreibberechtigungen implizieren typischerweise Leseberechtigungen:
- `WRITE_REPORT` beinhaltet `READ_REPORT`
- `DELETE_REPORT` erfordert `WRITE_REPORT`
- `ALL_PERMISSIONS` überschreibt alle Berechtigungsprüfungen

## Role Assignment Workflow

1. **Create Role**: Definieren Sie Rolle mit Name und Beschreibung
2. **Assign Permissions**: Wählen Sie geeignete Berechtigungen aus
3. **Test**: Überprüfen Sie, ob Berechtigungen wie erwartet funktionieren
4. **Assign Users**: Weisen Sie Benutzern die Rolle zu
5. **Monitor**: Verfolgen Sie Rollennutzung und Effektivität
6. **Update**: Ändern Sie Berechtigungen bei sich ändernden Anforderungen

## Security Considerations

- Rollen sind behördenspezifisch (Multi-Tenant-Isolation)
- System protokolliert alle Rollenänderungen
- Rollen mit zugewiesenen Benutzern können nicht gelöscht werden
- Admin-Berechtigungen sind stark eingeschränkt
- Berechtigungsänderungen werden sofort wirksam

## Database Tables

- `roles`: Rollendefinitionen
- `permissions`: Verfügbare Berechtigungen
- `role_permissions`: Rollen-Berechtigungs-Zuweisungen
- `user_roles`: Benutzer-Rollen-Zuweisungen

Alle Rollenvorgänge respektieren Behördengrenzen für Multi-Tenant-Isolation.
