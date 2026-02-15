# Mitarbeiter-API

Umfassende API für Mitarbeiterverwaltung einschließlich CRUD-Operationen, Abteilungszuordnungen, Rängen und Status-Verwaltung.

## Basis-URL

```
/backend/employee/
```

## Authentifizierung

Alle Endpunkte erfordern ein gültiges JWT Token und die Berechtigung `READ_EMPLOYEE` (Schreiboperationen erfordern `WRITE_EMPLOYEE`).

## Mitarbeiter auflisten

**GET** `/employee/`

Liste der Mitarbeiter für aktuelle Authority mit Pagination und Filterung abrufen.

**Endpunkt:**
```http
GET /employee/?page=1&limit=25&search=john&department=fire&status=active
Cookie: auth_token=YOUR_TOKEN
```

**Query-Parameter:**

| Parameter | Typ | Erforderlich | Beschreibung |
|-----------|------|----------|-------------|
| page | integer | Nein | Seitennummer (Standard: 1) |
| limit | integer | Nein | Einträge pro Seite (Standard: 25, max: 100) |
| search | string | Nein | Suche nach Vorname, Nachname oder ID |
| department | string | Nein | Nach Abteilungsnamen filtern |
| status | string | Nein | Nach Status filtern (active/inactive) |
| rank | string | Nein | Nach Rang filtern |
| sort | string | Nein | Sortierfeld (lastname, firstname, id, department) |
| order | string | Nein | Sortierreihenfolge (asc/desc, Standard: asc) |

**Erfolgreiche Antwort (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "firstname": "John",
      "lastname": "Smith",
      "employee_id": "EMP-2024-001",
      "department": "Fire Rescue",
      "rank": "Firefighter",
      "status": "active",
      "email": "john.smith@example.com",
      "phone": "+1-555-0123",
      "hire_date": "2024-01-15",
      "avatar": "/uploads/employees/123.jpg",
      "authority_id": 1
    },
    {
      "id": 124,
      "firstname": "Jane",
      "lastname": "Doe",
      "employee_id": "EMP-2024-002",
      "department": "Medical",
      "rank": "Paramedic",
      "status": "active",
      "email": "jane.doe@example.com",
      "phone": "+1-555-0124",
      "hire_date": "2024-02-01",
      "avatar": null,
      "authority_id": 1
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_items": 125,
    "per_page": 25,
    "has_next": true,
    "has_prev": false
  }
}
```

**Beispiel-Anfrage (cURL):**
```bash
curl 'http://localhost:8080/employee/?search=john&status=active' \
  -H 'Cookie: auth_token=YOUR_TOKEN'
```

**Beispiel-Anfrage (JavaScript):**
```javascript
const response = await fetch('/employee/?search=john&status=active', {
  credentials: 'include'
});
const { data, pagination } = await response.json();
```

## Einzelnen Mitarbeiter abrufen

**GET** `/employee/`?id={id}

Detaillierte Informationen für bestimmten Mitarbeiter abrufen.

**Endpunkt:**
```http
GET /employee/?id=123
Cookie: auth_token=YOUR_TOKEN
```

**Erfolgreiche Antwort (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "firstname": "John",
    "lastname": "Smith",
    "employee_id": "EMP-2024-001",
    "department": "Fire Rescue",
    "rank": "Firefighter",
    "status": "active",
    "email": "john.smith@example.com",
    "phone": "+1-555-0123",
    "mobile": "+1-555-9876",
    "address": "123 Main St",
    "city": "Springfield",
    "state": "IL",
    "zip": "62701",
    "hire_date": "2024-01-15",
    "birth_date": "1990-05-20",
    "emergency_contact": "Mary Smith",
    "emergency_phone": "+1-555-5555",
    "avatar": "/uploads/employees/123.jpg",
    "notes": "Certified in advanced rescue",
    "authority_id": 1,
    "created_at": "2024-01-15T10:00:00Z",
    "updated_at": "2024-03-15T14:30:00Z",
    "certifications": [
      {
        "id": 1,
        "name": "EMT-Basic",
        "issue_date": "2023-01-10",
        "expiry_date": "2025-01-10"
      }
    ],
    "training_records": [
      {
        "id": 1,
        "title": "Fire Safety Training",
        "completed_date": "2024-02-15",
        "instructor": "Chief Johnson"
      }
    ]
  }
}
```

**Fehler-Antwort (404 Not Found):**
```json
{
  "success": false,
  "error": "Employee not found"
}
```

## Mitarbeiter erstellen

**POST** `/employee/`

Neuen Mitarbeiterdatensatz erstellen.

**Erforderliche Berechtigung:** `WRITE_EMPLOYEE`

**Endpunkt:**
```http
POST /employee/
Content-Type: application/json
Cookie: auth_token=YOUR_TOKEN
```

**Request Body:**
```json
{
  "firstname": "John",
  "lastname": "Smith",
  "employee_id": "EMP-2024-001",
  "department": "Fire Rescue",
  "rank": "Firefighter",
  "status": "active",
  "email": "john.smith@example.com",
  "phone": "+1-555-0123",
  "mobile": "+1-555-9876",
  "address": "123 Main St",
  "city": "Springfield",
  "state": "IL",
  "zip": "62701",
  "hire_date": "2024-01-15",
  "birth_date": "1990-05-20",
  "emergency_contact": "Mary Smith",
  "emergency_phone": "+1-555-5555",
  "notes": "Certified in advanced rescue"
}
```

**Erforderliche Felder:**
- `firstname` (string, 1-100 Zeichen)
- `lastname` (string, 1-100 Zeichen)

**Optionale Felder:**
- Alle anderen Felder aus Request Body

**Erfolgreiche Antwort (201 Created):**
```json
{
  "success": true,
  "message": "Employee created successfully",
  "data": {
    "id": 125,
    "firstname": "John",
    "lastname": "Smith",
    "employee_id": "EMP-2024-001",
    "authority_id": 1,
    "created_at": "2024-03-15T15:00:00Z"
  }
}
```

**Fehler-Antwort (400 Bad Request):**
```json
{
  "success": false,
  "error": "Validation failed",
  "details": {
    "firstname": "First name is required",
    "email": "Invalid email format"
  }
}
```

**Fehler-Antwort (409 Conflict):**
```json
{
  "success": false,
  "error": "Employee ID already exists"
}
```

## Mitarbeiter aktualisieren

**PUT** `/employee/`?id={id}

Bestehenden Mitarbeiterdatensatz aktualisieren.

**Erforderliche Berechtigung:** `WRITE_EMPLOYEE`

**Endpunkt:**
```http
PUT /employee/?id=123
Content-Type: application/json
Cookie: auth_token=YOUR_TOKEN
```

**Request Body:**
```json
{
  "firstname": "John",
  "lastname": "Smith",
  "department": "Fire Rescue",
  "rank": "Lieutenant",
  "phone": "+1-555-0123",
  "status": "active"
}
```

**Hinweis:** Nur Felder einbeziehen, die Sie aktualisieren möchten. Alle Felder sind optional.

**Erfolgreiche Antwort (200 OK):**
```json
{
  "success": true,
  "message": "Employee updated successfully",
  "data": {
    "id": 123,
    "updated_at": "2024-03-15T15:30:00Z"
  }
}
```

**Fehler-Antwort (404 Not Found):**
```json
{
  "success": false,
  "error": "Employee not found"
}
```

## Mitarbeiter löschen

**DELETE** `/employee/`?id={id}

Mitarbeiterdatensatz löschen (Soft Delete - markiert als gelöscht).

**Erforderliche Berechtigung:** `DELETE_EMPLOYEE`

**Endpunkt:**
```http
DELETE /employee/?id=123
Cookie: auth_token=YOUR_TOKEN
```

**Erfolgreiche Antwort (200 OK):**
```json
{
  "success": true,
  "message": "Employee deleted successfully"
}
```

**Fehler-Antwort (404 Not Found):**
```json
{
  "success": false,
  "error": "Employee not found"
}
```

**Fehler-Antwort (409 Conflict):**
```json
{
  "success": false,
  "error": "Cannot delete employee with active reports"
}
```

## Zusätzliche Endpunkte

## Abteilungen abrufen

```http
GET /employee/?action=getDepartments
```

Gibt Liste aller Abteilungen in der Authority zurück.

**Antwort:**
```json
{
  "success": true,
  "data": [
    "Fire Rescue",
    "Medical",
    "Administration",
    "Training"
  ]
}
```

## Ränge abrufen

```http
GET /employee/?action=getRanks
```

Gibt Liste aller Ränge in der Authority zurück.

**Antwort:**
```json
{
  "success": true,
  "data": [
    "Chief",
    "Deputy Chief",
    "Captain",
    "Lieutenant",
    "Firefighter",
    "Paramedic",
    "EMT"
  ]
}
```

## Avatar hochladen

```http
POST /employee/?action=uploadAvatar&id=123
Content-Type: multipart/form-data
```

**Form Data:**
- `avatar`: Bilddatei (JPG, PNG, max 5MB)

**Antwort:**
```json
{
  "success": true,
  "message": "Avatar uploaded successfully",
  "avatar_url": "/uploads/employees/123.jpg"
}
```

## Daten-Validierung

## Mitarbeiter-ID
- Optionales Feld
- Muss innerhalb der Authority eindeutig sein
- Alphanumerisch mit Bindestrichen erlaubt
- Max 50 Zeichen

## E-Mail
- Muss gültiges E-Mail-Format sein
- Eindeutig innerhalb der Authority (optional)
- Max 255 Zeichen

### Telefonnummern
- Optional
- Format: +X-XXX-XXXX oder (XXX) XXX-XXXX
- Im Frontend validiert, wie eingegeben gespeichert

### Daten
- Format: YYYY-MM-DD
- hire_date darf nicht in der Zukunft liegen
- birth_date muss mindestens 18 Jahre zurückliegen

### Status
- Gültige Werte: active, inactive, terminated, suspended
- Standard: active

## Berechtigungen

| Aktion | Erforderliche Berechtigung | Hinweise |
|--------|-------------------|-------|
| Mitarbeiter auflisten | READ_EMPLOYEE | Zeigt nur Mitarbeiter der Benutzer-Authority |
| Mitarbeiter anzeigen | READ_EMPLOYEE | Kann keine Mitarbeiter anderer Authorities anzeigen |
| Mitarbeiter erstellen | WRITE_EMPLOYEE | Erstellt in Benutzer-Authority |
| Mitarbeiter aktualisieren | WRITE_EMPLOYEE | Kann keine Mitarbeiter anderer Authorities aktualisieren |
| Mitarbeiter löschen | DELETE_EMPLOYEE | Nur Soft Delete |

## Best Practices

**Mitarbeiter erstellen:**
1. Daten im Frontend vor dem Absenden validieren
2. Eindeutige employee_id verwenden, falls von Ihrer Organisation gefordert
3. hire_date für genaue Aufzeichnungen einfügen
4. Notfallkontakt-Informationen hinzufügen

**Mitarbeiter aktualisieren:**
1. Nur geänderte Felder senden
2. Statusänderungen validieren (active → terminated erfordert Bestätigung)
3. Signifikante Änderungen protokollieren (Beförderungen, Abteilungswechsel)

**Mitarbeiter suchen:**
1. Spezifische Suchbegriffe für bessere Leistung verwenden
2. Filter anwenden, um Ergebnisse einzugrenzen
3. Pagination für große Mitarbeiterlisten verwenden

**Leistung:**
1. Ergebnisse auf 25-50 pro Seite begrenzen
2. Spezifische Filter statt Suche in allen Feldern verwenden
3. Abteilungs-/Rang-Listen im Frontend cachen

## Beispiel-Integration

### Vue 3 Composable

```typescript
// useEmployees.ts
import { ref } from 'vue';
import api from '@/api';

export function useEmployees() {
  const employees = ref([]);
  const loading = ref(false);
  const error = ref(null);

  async function fetchEmployees(params = {}) {
    loading.value = true;
    error.value = null;

    try {
      const { data } = await api.get('/employee/', { params });
      employees.value = data.data;
      return data;
    } catch (err) {
      error.value = err.message;
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function createEmployee(employeeData) {
    const { data } = await api.post('/employee/', employeeData);
    employees.value.push(data.data);
    return data;
  }

  async function updateEmployee(id, employeeData) {
    const { data } = await api.put(`/employee/?id=${id}`, employeeData);
    const index = employees.value.findIndex(e => e.id === id);
    if (index !== -1) {
      employees.value[index] = { ...employees.value[index], ...employeeData };
    }
    return data;
  }

  async function deleteEmployee(id) {
    await api.delete(`/employee/?id=${id}`);
    employees.value = employees.value.filter(e => e.id !== id);
  }

  return {
    employees,
    loading,
    error,
    fetchEmployees,
    createEmployee,
    updateEmployee,
    deleteEmployee
  };
}
```

## Fehlercodes

| Status | Fehler | Ursache | Lösung |
|--------|-------|-------|----------|
| 400 | Validation failed | Ungültige Daten | Feldformate prüfen |
| 401 | Unauthorized | Kein/ungültiges Token | Erneut anmelden |
| 403 | Forbidden | Fehlende Berechtigung | WRITE_EMPLOYEE Berechtigung anfordern |
| 404 | Not found | Ungültige ID | Mitarbeiter-Existenz verifizieren |
| 409 | Conflict | Doppelte employee_id | Eindeutige employee_id verwenden |
| 500 | Server error | Datenbankfehler | Administrator kontaktieren |

## Nächste Schritte

- [Training API](/api/training) - Mitarbeiterschulungen und Zertifizierungen
- [Vacation API](/api/vacation) - Mitarbeiterurlaubsverwaltung
- [Report API](/api/report) - Mitarbeiter mit Berichten verknüpfen
- [Admin User API](/api/admin/user) - Benutzerkontenverwaltung

---

::: tip Mandanten-Isolation
Alle Mitarbeiter-Abfragen filtern automatisch nach authority_id. Sie können niemals auf Mitarbeiter anderer Authorities zugreifen.
:::

::: warning Soft Delete
Gelöschte Mitarbeiter werden als gelöscht markiert, aber nicht aus der Datenbank entfernt. Dies bewahrt historische Daten und Berichtsverknüpfungen.
:::
