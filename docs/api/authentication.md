# Authentication API

K-Systems uses JWT (JSON Web Token) based authentication for secure API access.

## Overview

**Authentication Flow:**
1. Client sends username, password, and authority_id to login endpoint
2. Server validates credentials
3. Server generates JWT token with user data and permissions
4. Token returned in HttpOnly cookie (or Authorization header response)
5. Client includes token in subsequent requests
6. Server validates token on each request

**Token Storage:**
- **Primary**: HttpOnly cookie (`auth_token`) - Secure, XSS-protected
- **Alternative**: Authorization header - For FiveM integration and API clients

**Token Lifetime:**
- Default: 24 hours
- Remember Me: 30 days
- Refresh: Automatic when < 1 hour remaining

## Base URL

```
http://localhost:8080 (development)
https://your-domain.com/api (production)
```

## Login

### POST /login/?action=login

Authenticate user and receive JWT token.

**Endpoint:**
```http
POST /login/?action=login
Content-Type: application/json
```

**Request Body:**
```json
{
  "username": "john.doe",
  "password": "SecurePass123!",
  "authority_id": 1,
  "remember_me": false
}
```

**Parameters:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| username | string | Yes | User's login username |
| password | string | Yes | User's password |
| authority_id | integer | Yes | Organization/tenant ID |
| remember_me | boolean | No | Extend token lifetime to 30 days |

**Success Response (200 OK):**
```json
{
  "message": "Login successful",
  "user": {
    "id": 123,
    "username": "john.doe",
    "firstname": "John",
    "lastname": "Doe",
    "email": "john.doe@example.com",
    "authority_id": 1,
    "authority_name": "Fire Department",
    "roles": ["Firefighter", "Report Writer"],
    "permissions": [
      "READ_EMPLOYEE",
      "WRITE_REPORT",
      "READ_REPORT",
      "READ_DOCUMENT"
    ],
    "avatar": "/uploads/avatars/123.jpg",
    "status": "active"
  },
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Set-Cookie Header:**
```
Set-Cookie: auth_token=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...; HttpOnly; Secure; SameSite=Strict; Path=/; Max-Age=86400
```

**Error Responses:**

**401 Unauthorized - Invalid Credentials:**
```json
{
  "success": false,
  "error": "Invalid username or password"
}
```

**401 Unauthorized - Inactive User:**
```json
{
  "success": false,
  "error": "Account is inactive. Please contact administrator."
}
```

**403 Forbidden - Banned User:**
```json
{
  "success": false,
  "error": "Account has been banned. Contact support."
}
```

**400 Bad Request - Missing Fields:**
```json
{
  "success": false,
  "error": "Missing required fields",
  "missing": ["username", "password"]
}
```

**400 Bad Request - Invalid Authority:**
```json
{
  "success": false,
  "error": "Invalid authority ID"
}
```

**Example Request (cURL):**
```bash
curl -X POST 'http://localhost:8080/login/?action=login' \
  -H 'Content-Type: application/json' \
  -d '{
    "username": "admin",
    "password": "admin123",
    "authority_id": 1,
    "remember_me": false
  }' \
  -c cookies.txt
```

**Example Request (JavaScript):**
```javascript
const response = await fetch('http://localhost:8080/login/?action=login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  credentials: 'include', // Important for cookies
  body: JSON.stringify({
    username: 'john.doe',
    password: 'SecurePass123!',
    authority_id: 1,
    remember_me: false
  })
});

const data = await response.json();
console.log(data.user);
```

**Example Request (Axios):**
```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8080',
  withCredentials: true
});

try {
  const { data } = await api.post('/login/?action=login', {
    username: 'john.doe',
    password: 'SecurePass123!',
    authority_id: 1,
    remember_me: false
  });

  console.log('Logged in:', data.user);
} catch (error) {
  console.error('Login failed:', error.response.data);
}
```

## Logout

### POST /login/?action=logout

Invalidate current session and clear authentication token.

**Endpoint:**
```http
POST /login/?action=logout
Cookie: auth_token=YOUR_TOKEN
```

**Request Body:** None required

**Success Response (200 OK):**
```json
{
  "message": "Logout successful"
}
```

**Set-Cookie Header:**
```
Set-Cookie: auth_token=; HttpOnly; Secure; SameSite=Strict; Path=/; Max-Age=0
```

**Example Request (cURL):**
```bash
curl -X POST 'http://localhost:8080/login/?action=logout' \
  -b cookies.txt \
  -c cookies.txt
```

**Example Request (JavaScript):**
```javascript
await fetch('http://localhost:8080/login/?action=logout', {
  method: 'POST',
  credentials: 'include'
});

// Redirect to login page
window.location.href = '/';
```

## Validate Token

### GET /login/?action=validate

Check if current token is valid and get user information.

**Endpoint:**
```http
GET /login/?action=validate
Cookie: auth_token=YOUR_TOKEN
```

**Success Response (200 OK):**
```json
{
  "valid": true,
  "user": {
    "id": 123,
    "username": "john.doe",
    "authority_id": 1,
    "roles": ["Firefighter"],
    "permissions": ["READ_EMPLOYEE", "WRITE_REPORT"]
  },
  "expires_at": "2024-03-16T15:30:00Z"
}
```

**Error Response (401 Unauthorized):**
```json
{
  "valid": false,
  "error": "Invalid or expired token"
}
```

**Example Request (cURL):**
```bash
curl -X GET 'http://localhost:8080/login/?action=validate' \
  -b cookies.txt
```

**Example Request (JavaScript):**
```javascript
const response = await fetch('http://localhost:8080/login/?action=validate', {
  credentials: 'include'
});

const data = await response.json();

if (data.valid) {
  console.log('Token is valid:', data.user);
} else {
  // Redirect to login
  window.location.href = '/';
}
```

## Refresh Token

### POST /login/?action=refresh

Refresh JWT token before expiration.

**Endpoint:**
```http
POST /login/?action=refresh
Cookie: auth_token=YOUR_TOKEN
```

**Request Body:** None required

**Success Response (200 OK):**
```json
{
  "message": "Token refreshed successfully",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expires_at": "2024-03-17T12:00:00Z"
}
```

**Set-Cookie Header:**
```
Set-Cookie: auth_token=NEW_TOKEN; HttpOnly; Secure; SameSite=Strict; Path=/; Max-Age=86400
```

**Error Response (401 Unauthorized):**
```json
{
  "success": false,
  "error": "Cannot refresh expired token. Please login again."
}
```

**Example Request (JavaScript):**
```javascript
// Automatically refresh token when < 1 hour remaining
async function checkAndRefreshToken() {
  const response = await fetch('http://localhost:8080/login/?action=validate', {
    credentials: 'include'
  });

  const data = await response.json();

  if (data.valid) {
    const expiresAt = new Date(data.expires_at);
    const now = new Date();
    const hoursRemaining = (expiresAt - now) / (1000 * 60 * 60);

    if (hoursRemaining < 1) {
      await fetch('http://localhost:8080/login/?action=refresh', {
        method: 'POST',
        credentials: 'include'
      });
      console.log('Token refreshed');
    }
  }
}

// Check every 10 minutes
setInterval(checkAndRefreshToken, 10 * 60 * 1000);
```

## Password Reset Request

### POST /login/?action=reset_request

Request password reset email.

**Endpoint:**
```http
POST /login/?action=reset_request
Content-Type: application/json
```

**Request Body:**
```json
{
  "email": "john.doe@example.com",
  "authority_id": 1
}
```

**Success Response (200 OK):**
```json
{
  "message": "Password reset email sent. Please check your inbox."
}
```

**Error Response (404 Not Found):**
```json
{
  "success": false,
  "error": "No account found with that email address"
}
```

**Note:** For security, always returns success even if email doesn't exist (prevents email enumeration).

## Password Reset

### POST /login/?action=reset_password

Reset password using reset token.

**Endpoint:**
```http
POST /login/?action=reset_password
Content-Type: application/json
```

**Request Body:**
```json
{
  "token": "RESET_TOKEN_FROM_EMAIL",
  "password": "NewSecurePass123!",
  "password_confirm": "NewSecurePass123!"
}
```

**Success Response (200 OK):**
```json
{
  "message": "Password reset successful. You can now login."
}
```

**Error Responses:**

**400 Bad Request - Password Mismatch:**
```json
{
  "success": false,
  "error": "Passwords do not match"
}
```

**400 Bad Request - Weak Password:**
```json
{
  "success": false,
  "error": "Password must be at least 8 characters with uppercase, lowercase, and number"
}
```

**401 Unauthorized - Invalid Token:**
```json
{
  "success": false,
  "error": "Invalid or expired reset token"
}
```

## JWT Token Structure

### Token Payload

The JWT token contains:

```json
{
  "user_id": 123,
  "username": "john.doe",
  "authority_id": 1,
  "roles": ["Firefighter", "Report Writer"],
  "permissions": [
    "READ_EMPLOYEE",
    "WRITE_REPORT",
    "READ_REPORT"
  ],
  "iat": 1710504000,
  "exp": 1710590400
}
```

**Fields:**
- `user_id`: User's database ID
- `username`: User's login username
- `authority_id`: Organization/tenant ID
- `roles`: Array of role names
- `permissions`: Array of permission codes
- `iat`: Issued at (Unix timestamp)
- `exp`: Expires at (Unix timestamp)

### Token Verification

Backend automatically verifies token on each request using `auth_check.php`.

**Verification Steps:**
1. Extract token from cookie or Authorization header
2. Verify JWT signature using secret key
3. Check expiration time
4. Validate user still exists and is active
5. Verify authority_id matches request context
6. Check required permissions for endpoint

## Using Tokens in Requests

### Method 1: Cookies (Recommended)

```javascript
fetch('http://localhost:8080/employee/', {
  credentials: 'include' // Automatically sends cookies
});
```

### Method 2: Authorization Header

```http
GET /employee/ HTTP/1.1
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

```javascript
fetch('http://localhost:8080/employee/', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
```

### Method 3: Both (Maximum Compatibility)

```javascript
fetch('http://localhost:8080/employee/', {
  credentials: 'include',
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
```

## Security Best Practices

### Token Storage

**Do:**
✅ Store in HttpOnly cookies (XSS protection)
✅ Use Secure flag (HTTPS only)
✅ Use SameSite=Strict (CSRF protection)
✅ Short token lifetime with refresh

**Don't:**
❌ Store in localStorage (XSS vulnerable)
❌ Store in sessionStorage (XSS vulnerable)
❌ Include token in URL parameters
❌ Log tokens to console

### Password Security

**Requirements:**
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number
- Special characters recommended

**Server-Side:**
- Passwords hashed with bcrypt
- Salt rounds: 10
- Never stored in plain text
- Password history tracked (prevents reuse)

### Rate Limiting

**Login Endpoint:**
- 5 failed attempts per username per 15 minutes
- Temporary account lock after 10 failed attempts
- IP-based rate limiting: 20 attempts per IP per hour

**Token Validation:**
- No rate limit (validated on every request)

**Password Reset:**
- 3 reset requests per email per hour
- Reset tokens expire after 1 hour

## Error Codes

| Code | Message | Description |
|------|---------|-------------|
| 400 | Missing required fields | Username, password, or authority_id not provided |
| 401 | Invalid username or password | Credentials don't match |
| 401 | Invalid or expired token | Token validation failed |
| 401 | Account is inactive | User account disabled |
| 403 | Account has been banned | User account banned |
| 429 | Too many requests | Rate limit exceeded |
| 500 | Internal server error | Server-side error |

## Testing Authentication

### Using cURL

```bash
# 1. Login and save cookies
curl -X POST 'http://localhost:8080/login/?action=login' \
  -H 'Content-Type: application/json' \
  -d '{"username":"admin","password":"admin123","authority_id":1}' \
  -c cookies.txt \
  -v

# 2. Use cookies in subsequent requests
curl 'http://localhost:8080/employee/' \
  -b cookies.txt

# 3. Logout
curl -X POST 'http://localhost:8080/login/?action=logout' \
  -b cookies.txt
```

### Using Postman

1. **Login:**
   - POST to `/login/?action=login`
   - Set body to JSON with credentials
   - In Tests tab, extract token:
     ```javascript
     pm.environment.set("auth_token", pm.response.json().token);
     ```

2. **Use Token:**
   - In collection settings, add Authorization header:
     ```
     Authorization: Bearer {{auth_token}}
     ```

3. **Auto-Refresh:**
   - Add pre-request script to check and refresh token

## Frontend Integration

### Vue 3 Example

```typescript
// auth.service.ts
import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true
});

export const authService = {
  async login(username: string, password: string, authorityId: number) {
    const { data } = await api.post('/login/?action=login', {
      username,
      password,
      authority_id: authorityId
    });
    return data;
  },

  async logout() {
    await api.post('/login/?action=logout');
  },

  async validate() {
    try {
      const { data } = await api.get('/login/?action=validate');
      return data.valid;
    } catch {
      return false;
    }
  },

  async refresh() {
    await api.post('/login/?action=refresh');
  }
};
```

### React Example

```typescript
// useAuth.ts
import { useState, useEffect } from 'react';

export function useAuth() {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    validateToken();
  }, []);

  async function validateToken() {
    try {
      const response = await fetch('/login/?action=validate', {
        credentials: 'include'
      });
      const data = await response.json();

      if (data.valid) {
        setUser(data.user);
      }
    } finally {
      setLoading(false);
    }
  }

  async function login(username, password, authorityId) {
    const response = await fetch('/login/?action=login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ username, password, authority_id: authorityId })
    });

    const data = await response.json();
    setUser(data.user);
    return data;
  }

  async function logout() {
    await fetch('/login/?action=logout', {
      method: 'POST',
      credentials: 'include'
    });
    setUser(null);
  }

  return { user, loading, login, logout };
}
```

## Next Steps

- [Employee API](/api/employee) - Employee management endpoints
- [Report API](/api/report) - Report system endpoints
- [Document API](/api/document) - Document management endpoints
- [Admin User API](/api/admin/user) - User administration endpoints

---

::: tip Token Security
Always use HttpOnly cookies in production for maximum security against XSS attacks.
:::

::: warning HTTPS Required
In production, always use HTTPS. Tokens sent over HTTP can be intercepted.
:::

::: info Token Refresh
Tokens automatically refresh when less than 1 hour remains. No action needed on frontend.
:::
