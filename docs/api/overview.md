# API Overview

K-Systems provides a comprehensive RESTful API for all system functionality.

## Base URLs

**Production:**
```
https://your-domain.com/api
```

**Development:**
```
http://localhost:8080
```

**Socket Server:**
```
ws://localhost:3001 (dev)
wss://your-domain.com (prod)
```

## Authentication

All API requests (except login) require authentication via JWT token.

### Token Location

The JWT token can be provided in two ways:

**1. Cookie (Recommended)**
```http
Cookie: auth_token=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

**2. Authorization Header (FiveM Compatibility)**
```http
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### Getting a Token

```http
POST /login/?action=login
Content-Type: application/json

{
  "username": "john.doe",
  "password": "password123",
  "authority_id": 1,
  "remember_me": false
}
```

**Response:**
```json
{
  "message": "Login successful",
  "user": {
    "id": 123,
    "username": "john.doe",
    "authority_id": 1,
    "roles": ["Admin"],
    "permissions": ["READ_EMPLOYEE", "WRITE_EMPLOYEE"]
  }
}
```

The token is automatically set as an HttpOnly cookie.

## Request Format

### Headers

```http
Content-Type: application/json
Cookie: auth_token=YOUR_JWT_TOKEN
```

Or with Authorization header:

```http
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN
```

### Body

All POST and PUT requests accept JSON:

```json
{
  "field1": "value1",
  "field2": "value2"
}
```

## Response Format

### Success Response

```json
{
  "success": true,
  "data": {
    // Response data
  },
  "message": "Operation successful"
}
```

### Error Response

```json
{
  "success": false,
  "error": "Error message",
  "code": "ERROR_CODE"
}
```

### HTTP Status Codes

- **200 OK**: Successful request
- **201 Created**: Resource created
- **400 Bad Request**: Invalid request data
- **401 Unauthorized**: Missing or invalid token
- **403 Forbidden**: Insufficient permissions
- **404 Not Found**: Resource not found
- **500 Internal Server Error**: Server error

## Core Endpoints

### Employee API

```http
GET    /employee/              # List employees
GET    /employee/{id}          # Get single employee
POST   /employee/              # Create employee
PUT    /employee/{id}          # Update employee
DELETE /employee/{id}          # Delete employee
```

[Full Employee API Documentation →](/api/employee)

### Report API

```http
GET    /report/                # List reports
GET    /report/{id}            # Get single report
POST   /report/                # Create report
PUT    /report/{id}            # Update report
DELETE /report/{id}            # Delete report
POST   /report/share.php       # Create shareable link
```

[Full Report API Documentation →](/api/report)

### Document API

```http
GET    /document/?action=getDocuments      # List documents
GET    /document/?action=getDocument       # Get single document
POST   /document/?action=createDocument    # Create document
PUT    /document/?action=updateDocument    # Update document
DELETE /document/?action=deleteDocument    # Delete document
GET    /document/?action=getAreas          # List document areas
```

[Full Document API Documentation →](/api/document)

### Message API

```http
GET    /message/?action=getMessages        # List messages
GET    /message/?action=getConversation    # Get conversation
POST   /message/?action=sendMessage        # Send message
PUT    /message/?action=markRead           # Mark as read
POST   /message/?action=createGroup        # Create group
DELETE /message/?action=deleteMessage      # Delete message
```

[Full Message API Documentation →](/api/message)

### Calendar API

```http
GET    /calendar/?action=getEvents         # List events
GET    /calendar/?action=getEvent          # Get single event
POST   /calendar/?action=createEvent       # Create event
PUT    /calendar/?action=updateEvent       # Update event
DELETE /calendar/?action=deleteEvent       # Delete event
GET    /calendar/?action=getGroups         # List calendar groups
```

[Full Calendar API Documentation →](/api/calendar)

## Admin Endpoints

### User Management

```http
GET    /admin/user/            # List users
GET    /admin/user/{id}        # Get user
POST   /admin/user/            # Create user
PUT    /admin/user/{id}        # Update user
DELETE /admin/user/{id}        # Delete user
```

[Full User API Documentation →](/api/admin/user)

### Role Management

```http
GET    /admin/roles/                     # List roles
GET    /admin/roles/{id}                 # Get role
POST   /admin/roles/                     # Create role
PUT    /admin/roles/{id}                 # Update role
DELETE /admin/roles/{id}                 # Delete role
GET    /admin/roles/{id}/permissions     # Get role permissions
POST   /admin/roles/{id}/permissions     # Update role permissions
```

[Full Role API Documentation →](/api/admin/role)

### Authority Management

```http
GET    /admin/authority/                 # List authorities
GET    /admin/authority/{id}             # Get authority
POST   /admin/authority/                 # Create authority
PUT    /admin/authority/{id}             # Update authority
DELETE /admin/authority/{id}             # Delete authority
GET    /admin/authority/{id}/features    # Get authority features
POST   /admin/authority/{id}/features    # Update features
```

[Full Authority API Documentation →](/api/admin/authority)

## Socket API

### HTTP Endpoints

```http
POST /api/notification/toast      # Send toast notification
POST /api/notification            # Send notification
POST /api/broadcast               # Broadcast to all users
GET  /api/diagnostic              # System diagnostics
GET  /health                      # Health check
```

**Authentication:** API Key in header
```http
X-API-Key: your_socket_api_key
```

[Full Socket API Documentation →](/api/socket/notifications)

### WebSocket Events

**Connection:**
```javascript
import { io } from 'socket.io-client';

const socket = io('/notification', {
  auth: { token: 'YOUR_JWT_TOKEN' }
});
```

**Events:**
- `notification:new` - New notification
- `notification:toast` - Toast message
- `chat:message` - Chat message
- `status:update` - Status change

[Full Socket Events Documentation →](/api/socket/notifications)

## Permissions

Most endpoints require specific permissions:

| Permission | Description |
|-----------|-------------|
| `READ_EMPLOYEE` | View employees |
| `WRITE_EMPLOYEE` | Create/update employees |
| `DELETE_EMPLOYEE` | Delete employees |
| `READ_REPORT` | View reports |
| `WRITE_REPORT` | Create/update reports |
| `DELETE_REPORT` | Delete reports |
| `ADMIN_USER` | User administration |
| `ADMIN_ROLE` | Role administration |
| `ALL_PERMISSIONS` | Super admin access |

### Checking Permissions

The backend automatically checks permissions using `auth_check.php`:

```php
// Backend automatically validates
if (!hasPermission($pdo, $userId, $authorityId, 'READ_EMPLOYEE')) {
    http_response_code(403);
    exit();
}
```

## Rate Limiting

Currently no rate limiting is implemented. Future versions may include:
- Per-user rate limits
- Per-endpoint limits
- IP-based limits

## Pagination

List endpoints support pagination:

```http
GET /employee/?page=1&limit=25&search=john
```

**Parameters:**
- `page` - Page number (default: 1)
- `limit` - Items per page (default: 25)
- `search` - Search query (optional)

**Response:**
```json
{
  "data": [...],
  "pagination": {
    "current_page": 1,
    "total_pages": 10,
    "total_items": 250,
    "per_page": 25
  }
}
```

## Error Handling

### Common Errors

**401 Unauthorized**
```json
{
  "error": "Invalid or expired token"
}
```

**403 Forbidden**
```json
{
  "error": "Permission denied",
  "required_permission": "WRITE_EMPLOYEE"
}
```

**400 Bad Request**
```json
{
  "error": "Validation failed",
  "details": {
    "name": "Name is required",
    "email": "Invalid email format"
  }
}
```

## Testing the API

### Using cURL

```bash
# Login
curl -X POST http://localhost:8080/login/?action=login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password","authority_id":1}' \
  -c cookies.txt

# List employees (using saved cookies)
curl http://localhost:8080/employee/ \
  -b cookies.txt

# Create employee
curl -X POST http://localhost:8080/employee/ \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{"firstname":"John","lastname":"Doe"}'
```

### Using Postman

1. Import the K-Systems collection
2. Set environment variables:
   - `BASE_URL`: http://localhost:8080
   - `TOKEN`: Your JWT token
3. Use {{BASE_URL}} in requests
4. Token automatically included in requests

### Using JavaScript

```javascript
// Using Axios
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8080',
  withCredentials: true
});

// Login
await api.post('/login/?action=login', {
  username: 'admin',
  password: 'password',
  authority_id: 1
});

// List employees
const { data } = await api.get('/employee/');
console.log(data);
```

## API Versioning

Currently K-Systems uses a single API version. Future versions may include:
- `/api/v1/` - Version 1
- `/api/v2/` - Version 2

## Next Steps

Explore detailed documentation for each API:

- [Employee API](/api/employee)
- [Report API](/api/report)
- [Document API](/api/document)
- [Message API](/api/message)
- [Calendar API](/api/calendar)
- [Admin APIs](/api/admin/user)
- [Socket API](/api/socket/notifications)

---

::: tip API Testing
Use the browser's Network tab to see actual API requests made by the frontend!
:::
