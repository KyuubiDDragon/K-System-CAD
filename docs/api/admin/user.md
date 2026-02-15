# User Management API

The User Management API provides administrative endpoints for managing user accounts, permissions, and access control.

## Base URL

```
/backend/admin/user/
```

## Authentication

All endpoints require administrative JWT authentication with `ADMIN_READ_USERS` or `ADMIN_WRITE_USERS` permissions.

## Required Permissions

- **ADMIN_READ_USERS**: View user accounts and information
- **ADMIN_WRITE_USERS**: Create, update, ban, unban users and reset passwords

## Endpoints

### Get Users

**GET** `/backend/admin/user/?action=getUsers`

Retrieves all users in the authority.

**Required Permission:** `ADMIN_READ_USERS`

**Response:**
```json
{
  "users": [
    {
      "id": 42,
      "username": "john.doe",
      "email": "john.doe@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "employee_id": 100,
      "roles": ["Firefighter", "Admin"],
      "status": "active",
      "last_login": "2025-01-20 14:30:00",
      "created_at": "2024-06-15 10:00:00"
    }
  ]
}
```

### Get User Overview

**GET** `/backend/admin/user/?action=getUserOverview`

Retrieves detailed overview of all users including activity metrics.

**Required Permission:** `ADMIN_READ_USERS`

**Response:**
```json
{
  "users": [
    {
      "id": 42,
      "username": "john.doe",
      "email": "john.doe@example.com",
      "full_name": "John Doe",
      "employee_id": 100,
      "roles": ["Firefighter", "Admin"],
      "permissions": ["READ_REPORT", "WRITE_REPORT", "ADMIN_READ_USERS"],
      "status": "active",
      "last_login": "2025-01-20 14:30:00",
      "login_count": 245,
      "reports_created": 38,
      "messages_sent": 156,
      "created_at": "2024-06-15 10:00:00"
    }
  ]
}
```

### Get Groups

**GET** `/backend/admin/user/?action=getGroups`

Retrieves all user groups/roles for assignment.

**Required Permission:** `ADMIN_READ_USERS`

**Response:**
```json
{
  "groups": [
    {
      "id": 1,
      "name": "Administrators",
      "description": "Full system access",
      "user_count": 3
    },
    {
      "id": 2,
      "name": "Firefighters",
      "description": "Standard firefighter access",
      "user_count": 45
    }
  ]
}
```

### Add New User

**POST** `/backend/admin/user/?action=addNewUser`

Creates a new user account.

**Required Permission:** `ADMIN_WRITE_USERS`

**Request:**
```json
{
  "username": "jane.smith",
  "email": "jane.smith@example.com",
  "password": "SecurePassword123!",
  "first_name": "Jane",
  "last_name": "Smith",
  "employee_id": 101,
  "roles": [2, 5]
}
```

**Response:**
```json
{
  "success": true,
  "message": "User created successfully",
  "user_id": 43
}
```

### Update User

**POST** `/backend/admin/user/?action=updateUser`

Updates an existing user account.

**Required Permission:** `ADMIN_WRITE_USERS`

**Request:**
```json
{
  "id": 43,
  "username": "jane.smith",
  "email": "jane.smith@example.com",
  "first_name": "Jane",
  "last_name": "Smith",
  "employee_id": 101,
  "roles": [2, 5, 8]
}
```

**Response:**
```json
{
  "success": true,
  "message": "User updated successfully"
}
```

### Ban User

**POST** `/backend/admin/user/?action=bannUser`

Suspends a user account, preventing login.

**Required Permission:** `ADMIN_WRITE_USERS`

**Request:**
```json
{
  "user_id": 43,
  "reason": "Violation of company policy",
  "duration": 30
}
```

**Response:**
```json
{
  "success": true,
  "message": "User banned successfully"
}
```

### Unban User

**POST** `/backend/admin/user/?action=unbannUser`

Restores a suspended user account.

**Required Permission:** `ADMIN_WRITE_USERS`

**Request:**
```json
{
  "user_id": 43
}
```

**Response:**
```json
{
  "success": true,
  "message": "User unbanned successfully"
}
```

### Reset Password

**POST** `/backend/admin/user/?action=resetPassword`

Resets a user's password.

**Required Permission:** `ADMIN_WRITE_USERS`

**Request:**
```json
{
  "user_id": 43,
  "new_password": "NewSecurePassword456!",
  "send_email": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "Password reset successfully"
}
```

### Get Users with Todo Permissions

**POST** `/backend/admin/user/?action=getUsersWithTodoPermissions`

Retrieves users who have todo-related permissions.

**Required Permission:** `ADMIN_READ_USERS`

**Request:**
```json
{
  "list_id": 5
}
```

**Response:**
```json
{
  "users": [
    {
      "id": 42,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "can_read": true,
      "can_write": true,
      "can_delete": false
    }
  ]
}
```

## User Status Values

- `active`: Account is active and can login
- `suspended`: Account is temporarily suspended
- `inactive`: Account is deactivated
- `pending`: Account awaiting activation

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad request - Invalid data or missing fields |
| 403 | Forbidden - Insufficient admin permissions |
| 404 | Not found - User does not exist |
| 409 | Conflict - Username or email already exists |
| 500 | Internal server error |

## Examples

### JavaScript

```javascript
// Get all users
async function getUsers() {
  const response = await fetch('/backend/admin/user/?action=getUsers', {
    credentials: 'include'
  });
  return await response.json();
}

// Create new user
async function createUser(userData) {
  const response = await fetch('/backend/admin/user/?action=addNewUser', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(userData)
  });
  return await response.json();
}

// Ban user
async function banUser(userId, reason) {
  const response = await fetch('/backend/admin/user/?action=bannUser', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ user_id: userId, reason })
  });
  return await response.json();
}
```

### cURL

```bash
# Get all users
curl -X GET 'http://localhost:8080/backend/admin/user/?action=getUsers' \
  -H 'Cookie: token=YOUR_JWT_TOKEN'

# Create user
curl -X POST 'http://localhost:8080/backend/admin/user/?action=addNewUser' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "username": "new.user",
    "email": "new.user@example.com",
    "password": "SecurePass123!",
    "first_name": "New",
    "last_name": "User",
    "roles": [2]
  }'

# Ban user
curl -X POST 'http://localhost:8080/backend/admin/user/?action=bannUser' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "user_id": 43,
    "reason": "Policy violation"
  }'
```

## Best Practices

1. Use strong password policies
2. Assign minimal necessary permissions
3. Document ban reasons
4. Regular user access audits
5. Link users to employee records
6. Monitor inactive accounts

## Security Notes

- Passwords are hashed using bcrypt
- Password resets generate audit logs
- User modifications are logged
- Admin actions require elevated permissions
- Authority isolation prevents cross-tenant access

## Database Tables

- `users`: User accounts
- `user_roles`: Role assignments
- `roles`: Role definitions
- `employees`: Employee linkage
