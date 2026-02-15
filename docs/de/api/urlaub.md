# Urlaub API

Die Urlaub API bietet Endpunkte zur Verwaltung von Urlaubsanträgen von Mitarbeitern, Genehmigungen und Nachverfolgung des Urlaubsguthabens.

## Base URL

```
/backend/vacation/
```

## Authentication

Alle Endpunkte erfordern einen gültigen JWT-Token, der als Cookie übergeben wird. Der Token muss gültige `userId`, `authority` und `authority_id` Claims enthalten.

### Erforderliche Berechtigungen

- **READ_VACATION**: Urlaubsanträge und Guthaben anzeigen
- **WRITE_VACATION**: Urlaubsanträge erstellen und bearbeiten
- **APPROVE_VACATION**: Urlaubsanträge genehmigen oder ablehnen
- **DELETE_VACATION**: Urlaubsanträge löschen

## Endpoints

### Get Vacation Requests

**GET** `/backend/vacation/?action=getVacations`

Ruft alle Urlaubsanträge für die Behörde ab.

**Erforderliche Berechtigung:** `READ_VACATION`

**Query Parameters:**
- `employee_id` (optional): Nach bestimmtem Mitarbeiter filtern
- `status` (optional): Nach Status filtern (pending, approved, rejected, cancelled)
- `year` (optional): Nach Jahr filtern

**Response (200):**
```json
{
  "vacations": [
    {
      "id": 1,
      "employee_id": 42,
      "employee_name": "John Doe",
      "start_date": "2025-07-01",
      "end_date": "2025-07-14",
      "days": 10,
      "type": "annual",
      "status": "approved",
      "reason": "Family vacation",
      "approver_id": 10,
      "approver_name": "Jane Manager",
      "approved_date": "2025-06-15",
      "notes": null,
      "created_at": "2025-06-10 09:30:00",
      "updated_at": "2025-06-15 14:22:00"
    },
    {
      "id": 2,
      "employee_id": 43,
      "employee_name": "Jane Smith",
      "start_date": "2025-08-01",
      "end_date": "2025-08-05",
      "days": 5,
      "type": "sick",
      "status": "pending",
      "reason": "Medical appointment",
      "approver_id": null,
      "approver_name": null,
      "approved_date": null,
      "notes": null,
      "created_at": "2025-07-25 10:15:00",
      "updated_at": "2025-07-25 10:15:00"
    }
  ]
}
```

### Get Employee Vacation Balance

**GET** `/backend/vacation/?action=getBalance&employee_id={id}`

Ruft das Urlaubsguthaben für einen bestimmten Mitarbeiter ab.

**Erforderliche Berechtigung:** `READ_VACATION`

**Query Parameters:**
- `employee_id` (erforderlich): ID des Mitarbeiters
- `year` (optional): Jahr für Guthabenberechnung (Standard ist aktuelles Jahr)

**Response (200):**
```json
{
  "balance": {
    "employee_id": 42,
    "employee_name": "John Doe",
    "year": 2025,
    "total_days": 25,
    "used_days": 10,
    "pending_days": 3,
    "remaining_days": 12,
    "carried_over": 5,
    "breakdown": {
      "annual": {
        "total": 20,
        "used": 10,
        "pending": 3,
        "remaining": 7
      },
      "sick": {
        "total": 10,
        "used": 0,
        "pending": 0,
        "remaining": 10
      }
    }
  }
}
```

### Create Vacation Request

**POST** `/backend/vacation/?action=createVacation`

Erstellt einen neuen Urlaubsantrag.

**Erforderliche Berechtigung:** `WRITE_VACATION`

**Request Body:**
```json
{
  "employee_id": 42,
  "start_date": "2025-09-01",
  "end_date": "2025-09-10",
  "type": "annual",
  "reason": "Personal travel",
  "notes": "Will be reachable by email"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Vacation request created successfully",
  "vacation_id": 3,
  "days": 8
}
```

### Update Vacation Request

**POST** `/backend/vacation/?action=updateVacation`

Aktualisiert einen bestehenden Urlaubsantrag (nur erlaubt, wenn noch nicht genehmigt).

**Erforderliche Berechtigung:** `WRITE_VACATION`

**Request Body:**
```json
{
  "id": 3,
  "start_date": "2025-09-01",
  "end_date": "2025-09-12",
  "type": "annual",
  "reason": "Extended personal travel",
  "notes": "Updated dates"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Vacation request updated successfully",
  "days": 10
}
```

### Approve Vacation Request

**POST** `/backend/vacation/?action=approveVacation`

Genehmigt einen Urlaubsantrag.

**Erforderliche Berechtigung:** `APPROVE_VACATION`

**Request Body:**
```json
{
  "id": 3,
  "notes": "Approved - coverage arranged"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Vacation request approved successfully"
}
```

### Reject Vacation Request

**POST** `/backend/vacation/?action=rejectVacation`

Lehnt einen Urlaubsantrag ab.

**Erforderliche Berechtigung:** `APPROVE_VACATION`

**Request Body:**
```json
{
  "id": 3,
  "notes": "Insufficient coverage during this period"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Vacation request rejected"
}
```

### Cancel Vacation Request

**POST** `/backend/vacation/?action=cancelVacation`

Storniert einen Urlaubsantrag (Mitarbeiter oder Manager können stornieren).

**Erforderliche Berechtigung:** `WRITE_VACATION`

**Request Body:**
```json
{
  "id": 3,
  "notes": "Plans changed"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Vacation request cancelled"
}
```

### Delete Vacation Request

**POST** `/backend/vacation/?action=deleteVacation`

Löscht einen Urlaubsantrag dauerhaft.

**Erforderliche Berechtigung:** `DELETE_VACATION`

**Request Body:**
```json
{
  "id": 3
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Vacation request deleted successfully"
}
```

### Get Vacation Calendar

**GET** `/backend/vacation/?action=getVacationCalendar`

Ruft den Urlaubskalender ab, der alle genehmigten Urlaube für die Teamplanung anzeigt.

**Erforderliche Berechtigung:** `READ_VACATION`

**Query Parameters:**
- `start_date` (optional): Beginn des Datumsbereichs
- `end_date` (optional): Ende des Datumsbereichs
- `department` (optional): Nach Abteilung filtern

**Response (200):**
```json
{
  "calendar": [
    {
      "date": "2025-07-01",
      "employees_on_leave": [
        {
          "employee_id": 42,
          "employee_name": "John Doe",
          "department": "Operations",
          "vacation_type": "annual"
        }
      ]
    }
  ]
}
```

### Get Pending Approvals

**GET** `/backend/vacation/?action=getPendingApprovals`

Ruft ausstehende Genehmigungen für Urlaubsanträge ab (für Manager).

**Erforderliche Berechtigung:** `APPROVE_VACATION`

**Response (200):**
```json
{
  "pending": [
    {
      "id": 2,
      "employee_id": 43,
      "employee_name": "Jane Smith",
      "department": "Administration",
      "start_date": "2025-08-01",
      "end_date": "2025-08-05",
      "days": 5,
      "type": "sick",
      "reason": "Medical appointment",
      "requested_date": "2025-07-25 10:15:00"
    }
  ]
}
```

## Vacation Types

Das Feld `type` unterstützt folgende Werte:
- `annual`: Standardjahresurlaub
- `sick`: Krankenstand
- `personal`: Persönlicher Urlaub
- `bereavement`: Trauerurlaub
- `parental`: Eltern-/Mutterschutz-/Vaterschaftsurlaub
- `unpaid`: Unbezahlter Urlaub
- `compensatory`: Zeitausgleich
- `public_holiday`: Feiertag

## Vacation Status Values

Das Feld `status` unterstützt:
- `pending`: Antrag wartet auf Genehmigung
- `approved`: Antrag wurde genehmigt
- `rejected`: Antrag wurde abgelehnt
- `cancelled`: Antrag wurde storniert
- `completed`: Urlaub wurde genommen (vergangene Daten)

## Business Rules

1. **Minimum Notice**: Urlaubsanträge erfordern typischerweise eine Vorankündigung
2. **Overlap Prevention**: System prüft auf überschneidende Anträge
3. **Balance Validation**: Es können nicht mehr Tage beantragt werden als verfügbar
4. **Approval Workflow**: Anträge erfordern Manager-Genehmigung
5. **Cancellation Policy**: Genehmigte Urlaube können mit Vorankündigung storniert werden
6. **Weekend/Holiday Calculation**: Nur Arbeitstage (konfigurierbar)
7. **Carryover Rules**: Nicht genutzter Urlaub kann ins nächste Jahr übertragen werden (richtlinienabhängig)

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad request - Ungültige Daten, unzureichendes Guthaben oder fehlende Parameter |
| 403 | Forbidden - Unzureichende Berechtigungen oder ungültige Behörde |
| 404 | Not found - Urlaubsantrag existiert nicht |
| 405 | Method not allowed - Falsche HTTP-Methode verwendet |
| 409 | Conflict - Überschneidende Urlaubsdaten |
| 500 | Internal server error - Datenbank- oder Systemfehler |

## Multi-Tenant Isolation

Alle Urlaubsvorgänge sind automatisch auf die Behörde des authentifizierten Benutzers beschränkt. Mitarbeiter können nur ihre eigenen Anträge sehen, es sei denn, sie haben Manager-/Admin-Berechtigungen.

## Examples

### JavaScript Example

```javascript
// Get all vacation requests
async function getVacations() {
  const response = await fetch('/backend/vacation/?action=getVacations', {
    method: 'GET',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json'
    }
  });

  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }

  const data = await response.json();
  return data.vacations;
}

// Get employee vacation balance
async function getVacationBalance(employeeId) {
  const response = await fetch(
    `/backend/vacation/?action=getBalance&employee_id=${employeeId}`,
    {
      method: 'GET',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json'
      }
    }
  );

  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }

  const data = await response.json();
  return data.balance;
}

// Create vacation request
async function createVacationRequest(requestData) {
  const response = await fetch('/backend/vacation/?action=createVacation', {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(requestData)
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.error || 'Failed to create vacation request');
  }

  return data;
}

// Approve vacation request
async function approveVacation(vacationId, notes) {
  const response = await fetch('/backend/vacation/?action=approveVacation', {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      id: vacationId,
      notes: notes
    })
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.error || 'Failed to approve vacation');
  }

  return data;
}
```

### cURL Examples

```bash
# Get all vacations
curl -X GET \
  'http://localhost:8080/backend/vacation/?action=getVacations' \
  -H 'Cookie: token=YOUR_JWT_TOKEN'

# Get employee balance
curl -X GET \
  'http://localhost:8080/backend/vacation/?action=getBalance&employee_id=42' \
  -H 'Cookie: token=YOUR_JWT_TOKEN'

# Create vacation request
curl -X POST \
  'http://localhost:8080/backend/vacation/?action=createVacation' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "employee_id": 42,
    "start_date": "2025-09-01",
    "end_date": "2025-09-10",
    "type": "annual",
    "reason": "Personal travel"
  }'

# Approve vacation request
curl -X POST \
  'http://localhost:8080/backend/vacation/?action=approveVacation' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "id": 3,
    "notes": "Approved"
  }'

# Reject vacation request
curl -X POST \
  'http://localhost:8080/backend/vacation/?action=rejectVacation' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "id": 3,
    "notes": "Insufficient coverage"
  }'
```

## Best Practices

1. **Early Planning**: Reichen Sie Anträge rechtzeitig ein
2. **Team Coordination**: Prüfen Sie den Teamkalender vor der Beantragung
3. **Clear Reasons**: Geben Sie einen angemessenen Grund für den Antragstyp an
4. **Balance Monitoring**: Überprüfen Sie regelmäßig das verbleibende Guthaben
5. **Manager Communication**: Kommunizieren Sie besondere Umstände mit dem Vorgesetzten
6. **Coverage Planning**: Organisieren Sie Arbeitsvertretung vor dem Urlaub
7. **Status Updates**: Halten Sie Anträge auf dem neuesten Stand, wenn sich Pläne ändern
8. **Documentation**: Führen Sie Aufzeichnungen über Krankenstand und besondere Urlaubsarten

## Vacation Balance Calculation

Das Guthaben wird wie folgt berechnet:

1. **Total Days**: Jährlicher Anspruch + übertragene Tage
2. **Used Days**: Summe der genehmigten und abgeschlossenen Urlaube
3. **Pending Days**: Summe der ausstehenden Anträge
4. **Remaining Days**: Total - Used - Pending

## Approval Workflow

Standard-Genehmigungsworkflow:

1. **Employee** erstellt Urlaubsantrag
2. **System** validiert Guthaben und Daten
3. **Manager** erhält Benachrichtigung
4. **Manager** prüft und genehmigt/lehnt ab
5. **Employee** erhält Benachrichtigung über Entscheidung
6. **System** aktualisiert Guthaben bei Genehmigung

## Calendar Integration

Genehmigte Urlaube erscheinen in:
- Persönlichem Mitarbeiterkalender
- Teamkalender
- Abteilungskalender
- Behördenweitem Urlaubskalender

## Notifications

Das Urlaubssystem sendet Benachrichtigungen für:
- Neuer Antrag eingereicht
- Antrag genehmigt
- Antrag abgelehnt
- Antrag storniert
- Erinnerung an bevorstehenden Urlaub
- Warnung bei niedrigem Guthaben

## Database Tables

Die Urlaub API interagiert mit folgenden Tabellen:

- `vacations`: Urlaubsanträge
- `vacation_balances`: Mitarbeiter-Urlaubsguthaben
- `employees`: Mitarbeiterinformationen
- `users`: Benutzerkonten und Genehmiger-Informationen

Alle Tabellen enthalten `authority_id` für Multi-Tenant-Datenisolation.
