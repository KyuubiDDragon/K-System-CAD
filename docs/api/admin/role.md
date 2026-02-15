# Role Management API

The Role Management API provides administrative endpoints for managing roles and their associated permissions.

## Base URL

```
/backend/admin/roles/
```

## Authentication

All endpoints require administrative JWT authentication with `ADMIN_READ_USERS` or `ADMIN_WRITE_USERS` permissions.

## Required Permissions

- **ADMIN_READ_USERS**: View roles and permissions
- **ADMIN_WRITE_USERS**: Create, update, and delete roles

## Endpoints

### Get Roles

**GET** `/backend/admin/roles/?action=getRoles`

Retrieves all roles in the authority.

**Required Permission:** `ADMIN_READ_USERS`

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

Retrieves all available permissions in the system.

**Required Permission:** `ADMIN_READ_USERS`

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

Retrieves all role-permission assignments.

**Required Permission:** `ADMIN_READ_USERS`

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

Retrieves roles organized by permission categories.

**Required Permission:** `ADMIN_READ_USERS`

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

Creates a new role with specified permissions.

**Required Permission:** `ADMIN_WRITE_USERS`

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

Updates an existing role and its permissions.

**Required Permission:** `ADMIN_WRITE_USERS`

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

Deletes a role (only if no users are assigned to it).

**Required Permission:** `ADMIN_WRITE_USERS`

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

Permissions are organized into categories:

- **Reports**: Report management permissions
- **Documents**: Document management permissions
- **Employees**: Employee management permissions
- **Calendar**: Calendar and event permissions
- **Messages**: Messaging permissions
- **Admin**: Administrative permissions
- **Dispatch**: Dispatch and crew permissions
- **Training**: Training management permissions
- **Finance**: Invoice and financial permissions

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
- `ALL_PERMISSIONS`: Full system access
- `CAN_LOGIN`: Basic login access
- `APPROVE_VACATION`: Approve leave requests

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad request - Invalid data or missing fields |
| 403 | Forbidden - Insufficient admin permissions |
| 404 | Not found - Role does not exist |
| 409 | Conflict - Role name already exists or role has assigned users |
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

1. **Principle of Least Privilege**: Grant only necessary permissions
2. **Role Naming**: Use clear, descriptive role names
3. **Documentation**: Document each role's purpose
4. **Regular Audits**: Review role assignments periodically
5. **Permission Groups**: Organize permissions logically
6. **Testing**: Test role permissions before deployment
7. **Backup**: Backup role configurations before major changes

## Permission Hierarchy

Write permissions typically imply read permissions:
- `WRITE_REPORT` includes `READ_REPORT`
- `DELETE_REPORT` requires `WRITE_REPORT`
- `ALL_PERMISSIONS` overrides all permission checks

## Role Assignment Workflow

1. **Create Role**: Define role with name and description
2. **Assign Permissions**: Select appropriate permissions
3. **Test**: Verify permissions work as expected
4. **Assign Users**: Assign role to users
5. **Monitor**: Track role usage and effectiveness
6. **Update**: Modify permissions as needs change

## Security Considerations

- Roles are authority-specific (multi-tenant isolation)
- System logs all role modifications
- Cannot delete roles with assigned users
- Admin permissions are highly restricted
- Permission changes take effect immediately

## Database Tables

- `roles`: Role definitions
- `permissions`: Available permissions
- `role_permissions`: Role-permission assignments
- `user_roles`: User-role assignments

All role operations respect authority boundaries for multi-tenant isolation.
