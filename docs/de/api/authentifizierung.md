# Authentifizierungs-API

K-Systems verwendet JWT (JSON Web Token) basierte Authentifizierung für sicheren API-Zugriff.

## Übersicht

**Authentifizierungs-Ablauf:**
1. Client sendet Benutzername, Passwort und authority_id an Login-Endpunkt
2. Server validiert Anmeldedaten
3. Server generiert JWT Token mit Benutzerdaten und Berechtigungen
4. Token wird in HttpOnly Cookie zurückgegeben (oder Authorization Header Antwort)
5. Client fügt Token bei nachfolgenden Anfragen ein
6. Server validiert Token bei jeder Anfrage

**Token-Speicherung:**
- **Primär**: HttpOnly Cookie (`auth_token`) - Sicher, XSS-geschützt
- **Alternativ**: Authorization Header - Für FiveM Integration und API-Clients

**Token-Lebensdauer:**
- Standard: 24 Stunden
- Remember Me: 30 Tage
- Aktualisierung: Automatisch wenn < 1 Stunde verbleibend

## Basis-URL

```
http://localhost:8080 (Entwicklung)
https://your-domain.com/api (Produktion)
```

## Login

### POST /login/?action=login

Benutzer authentifizieren und JWT Token erhalten.

**Endpunkt:**
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

**Parameter:**

| Feld | Typ | Erforderlich | Beschreibung |
|-------|------|----------|-------------|
| username | string | Ja | Benutzername des Benutzers |
| password | string | Ja | Passwort des Benutzers |
| authority_id | integer | Ja | Organisations-/Mandanten-ID |
| remember_me | boolean | Nein | Token-Lebensdauer auf 30 Tage erweitern |

**Erfolgreiche Antwort (200 OK):**
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

**Fehler-Antworten:**

**401 Unauthorized - Ungültige Anmeldedaten:**
```json
{
  "success": false,
  "error": "Invalid username or password"
}
```

**401 Unauthorized - Inaktiver Benutzer:**
```json
{
  "success": false,
  "error": "Account is inactive. Please contact administrator."
}
```

**403 Forbidden - Gesperrter Benutzer:**
```json
{
  "success": false,
  "error": "Account has been banned. Contact support."
}
```

**400 Bad Request - Fehlende Felder:**
```json
{
  "success": false,
  "error": "Missing required fields",
  "missing": ["username", "password"]
}
```

**400 Bad Request - Ungültige Authority:**
```json
{
  "success": false,
  "error": "Invalid authority ID"
}
```

**Beispiel-Anfrage (cURL):**
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

**Beispiel-Anfrage (JavaScript):**
```javascript
const response = await fetch('http://localhost:8080/login/?action=login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  credentials: 'include', // Wichtig für Cookies
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

**Beispiel-Anfrage (Axios):**
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

Aktuelle Sitzung ungültig machen und Authentifizierungs-Token löschen.

**Endpunkt:**
```http
POST /login/?action=logout
Cookie: auth_token=YOUR_TOKEN
```

**Request Body:** Nicht erforderlich

**Erfolgreiche Antwort (200 OK):**
```json
{
  "message": "Logout successful"
}
```

**Set-Cookie Header:**
```
Set-Cookie: auth_token=; HttpOnly; Secure; SameSite=Strict; Path=/; Max-Age=0
```

**Beispiel-Anfrage (cURL):**
```bash
curl -X POST 'http://localhost:8080/login/?action=logout' \
  -b cookies.txt \
  -c cookies.txt
```

**Beispiel-Anfrage (JavaScript):**
```javascript
await fetch('http://localhost:8080/login/?action=logout', {
  method: 'POST',
  credentials: 'include'
});

// Zur Login-Seite umleiten
window.location.href = '/';
```

## Token validieren

### GET /login/?action=validate

Prüfen, ob aktuelles Token gültig ist und Benutzerinformationen abrufen.

**Endpunkt:**
```http
GET /login/?action=validate
Cookie: auth_token=YOUR_TOKEN
```

**Erfolgreiche Antwort (200 OK):**
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

**Fehler-Antwort (401 Unauthorized):**
```json
{
  "valid": false,
  "error": "Invalid or expired token"
}
```

**Beispiel-Anfrage (cURL):**
```bash
curl -X GET 'http://localhost:8080/login/?action=validate' \
  -b cookies.txt
```

**Beispiel-Anfrage (JavaScript):**
```javascript
const response = await fetch('http://localhost:8080/login/?action=validate', {
  credentials: 'include'
});

const data = await response.json();

if (data.valid) {
  console.log('Token is valid:', data.user);
} else {
  // Zum Login umleiten
  window.location.href = '/';
}
```

## Token aktualisieren

### POST /login/?action=refresh

JWT Token vor Ablauf aktualisieren.

**Endpunkt:**
```http
POST /login/?action=refresh
Cookie: auth_token=YOUR_TOKEN
```

**Request Body:** Nicht erforderlich

**Erfolgreiche Antwort (200 OK):**
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

**Fehler-Antwort (401 Unauthorized):**
```json
{
  "success": false,
  "error": "Cannot refresh expired token. Please login again."
}
```

**Beispiel-Anfrage (JavaScript):**
```javascript
// Token automatisch aktualisieren wenn < 1 Stunde verbleibend
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

// Alle 10 Minuten prüfen
setInterval(checkAndRefreshToken, 10 * 60 * 1000);
```

## Passwort-Zurücksetzen anfordern

### POST /login/?action=reset_request

E-Mail zum Zurücksetzen des Passworts anfordern.

**Endpunkt:**
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

**Erfolgreiche Antwort (200 OK):**
```json
{
  "message": "Password reset email sent. Please check your inbox."
}
```

**Fehler-Antwort (404 Not Found):**
```json
{
  "success": false,
  "error": "No account found with that email address"
}
```

**Hinweis:** Aus Sicherheitsgründen wird immer Erfolg zurückgegeben, auch wenn E-Mail nicht existiert (verhindert E-Mail-Enumeration).

## Passwort zurücksetzen

### POST /login/?action=reset_password

Passwort mit Reset-Token zurücksetzen.

**Endpunkt:**
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

**Erfolgreiche Antwort (200 OK):**
```json
{
  "message": "Password reset successful. You can now login."
}
```

**Fehler-Antworten:**

**400 Bad Request - Passwort stimmt nicht überein:**
```json
{
  "success": false,
  "error": "Passwords do not match"
}
```

**400 Bad Request - Schwaches Passwort:**
```json
{
  "success": false,
  "error": "Password must be at least 8 characters with uppercase, lowercase, and number"
}
```

**401 Unauthorized - Ungültiges Token:**
```json
{
  "success": false,
  "error": "Invalid or expired reset token"
}
```

## JWT Token-Struktur

### Token-Payload

Das JWT Token enthält:

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

**Felder:**
- `user_id`: Datenbank-ID des Benutzers
- `username`: Benutzername des Benutzers
- `authority_id`: Organisations-/Mandanten-ID
- `roles`: Array von Rollennamen
- `permissions`: Array von Berechtigungscodes
- `iat`: Issued at (Unix-Zeitstempel)
- `exp`: Expires at (Unix-Zeitstempel)

### Token-Verifizierung

Backend verifiziert Token automatisch bei jeder Anfrage mittels `auth_check.php`.

**Verifizierungs-Schritte:**
1. Token aus Cookie oder Authorization Header extrahieren
2. JWT-Signatur mit geheimem Schlüssel verifizieren
3. Ablaufzeit prüfen
4. Validieren, dass Benutzer noch existiert und aktiv ist
5. Verifizieren, dass authority_id mit Anfrage-Kontext übereinstimmt
6. Erforderliche Berechtigungen für Endpunkt prüfen

## Tokens in Anfragen verwenden

### Methode 1: Cookies (Empfohlen)

```javascript
fetch('http://localhost:8080/employee/', {
  credentials: 'include' // Sendet automatisch Cookies
});
```

### Methode 2: Authorization Header

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

### Methode 3: Beides (Maximale Kompatibilität)

```javascript
fetch('http://localhost:8080/employee/', {
  credentials: 'include',
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
```

## Sicherheits-Best Practices

### Token-Speicherung

**Tun Sie:**
✅ In HttpOnly Cookies speichern (XSS-Schutz)
✅ Secure-Flag verwenden (nur HTTPS)
✅ SameSite=Strict verwenden (CSRF-Schutz)
✅ Kurze Token-Lebensdauer mit Aktualisierung

**Tun Sie nicht:**
❌ In localStorage speichern (XSS-anfällig)
❌ In sessionStorage speichern (XSS-anfällig)
❌ Token in URL-Parametern einfügen
❌ Tokens in Konsole loggen

### Passwort-Sicherheit

**Anforderungen:**
- Mindestens 8 Zeichen
- Mindestens ein Großbuchstabe
- Mindestens ein Kleinbuchstabe
- Mindestens eine Zahl
- Sonderzeichen empfohlen

**Server-seitig:**
- Passwörter mit bcrypt gehasht
- Salt Rounds: 10
- Nie im Klartext gespeichert
- Passwort-Historie verfolgt (verhindert Wiederverwendung)

### Rate Limiting

**Login-Endpunkt:**
- 5 fehlgeschlagene Versuche pro Benutzername pro 15 Minuten
- Temporäre Kontosperre nach 10 fehlgeschlagenen Versuchen
- IP-basiertes Rate Limiting: 20 Versuche pro IP pro Stunde

**Token-Validierung:**
- Kein Rate Limit (wird bei jeder Anfrage validiert)

**Passwort-Zurücksetzen:**
- 3 Reset-Anfragen pro E-Mail pro Stunde
- Reset-Tokens laufen nach 1 Stunde ab

## Fehlercodes

| Code | Nachricht | Beschreibung |
|------|---------|-------------|
| 400 | Missing required fields | Benutzername, Passwort oder authority_id nicht angegeben |
| 401 | Invalid username or password | Anmeldedaten stimmen nicht überein |
| 401 | Invalid or expired token | Token-Validierung fehlgeschlagen |
| 401 | Account is inactive | Benutzerkonto deaktiviert |
| 403 | Account has been banned | Benutzerkonto gesperrt |
| 429 | Too many requests | Rate Limit überschritten |
| 500 | Internal server error | Server-seitiger Fehler |

## Authentifizierung testen

### Mit cURL

```bash
# 1. Login und Cookies speichern
curl -X POST 'http://localhost:8080/login/?action=login' \
  -H 'Content-Type: application/json' \
  -d '{"username":"admin","password":"admin123","authority_id":1}' \
  -c cookies.txt \
  -v

# 2. Cookies in nachfolgenden Anfragen verwenden
curl 'http://localhost:8080/employee/' \
  -b cookies.txt

# 3. Logout
curl -X POST 'http://localhost:8080/login/?action=logout' \
  -b cookies.txt
```

### Mit Postman

1. **Login:**
   - POST zu `/login/?action=login`
   - Body auf JSON mit Anmeldedaten setzen
   - Im Tests-Tab Token extrahieren:
     ```javascript
     pm.environment.set("auth_token", pm.response.json().token);
     ```

2. **Token verwenden:**
   - In Collection-Einstellungen Authorization Header hinzufügen:
     ```
     Authorization: Bearer {{auth_token}}
     ```

3. **Auto-Refresh:**
   - Pre-request Script hinzufügen um Token zu prüfen und zu aktualisieren

## Frontend-Integration

### Vue 3 Beispiel

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

### React Beispiel

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

## Nächste Schritte

- [Employee API](/api/employee) - Mitarbeiterverwaltungs-Endpunkte
- [Report API](/api/report) - Berichtssystem-Endpunkte
- [Document API](/api/document) - Dokumentenverwaltungs-Endpunkte
- [Admin User API](/api/admin/user) - Benutzerverwaltungs-Endpunkte

---

::: tip Token-Sicherheit
Verwenden Sie in der Produktion immer HttpOnly Cookies für maximale Sicherheit gegen XSS-Angriffe.
:::

::: warning HTTPS erforderlich
In der Produktion immer HTTPS verwenden. Tokens, die über HTTP gesendet werden, können abgefangen werden.
:::

::: info Token-Aktualisierung
Tokens werden automatisch aktualisiert, wenn weniger als 1 Stunde verbleibt. Keine Aktion im Frontend erforderlich.
:::
