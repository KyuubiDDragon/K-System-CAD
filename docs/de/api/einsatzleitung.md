# Einsatzleitung API

Die Einsatzleitung API bietet Endpunkte zur Verwaltung von Einsatzeinheiten (Mannschaften), Zuweisung von Mitarbeitern und Fahrzeugen zu Einheiten und Verfolgung der Einsatzbereitschaft.

## Base URL

```
/backend/dispatch/
```

## Authentication

Alle Endpunkte erfordern einen gültigen JWT-Token, der als Cookie übergeben wird. Der Token muss gültige `userId`, `authority` und `authority_id` Claims enthalten.

### Erforderliche Berechtigungen

- **READ_DISPATCH**: Einsatzeinheiten und Zuweisungen anzeigen
- **WRITE_DISPATCH**: Einsatzeinheiten und Zuweisungen erstellen und bearbeiten
- **DELETE_DISPATCH**: Einsatzeinheiten löschen

## Endpoints

### Get Dispatches

**GET** `/backend/dispatch/?action=getDispatches`

Ruft alle Einsatzeinheiten für die Behörde ab.

**Erforderliche Berechtigung:** `READ_DISPATCH`

**Response (200):**
```json
{
  "dispatches": [
    {
      "id": 1,
      "name": "Engine 1",
      "type": "engine",
      "status": "available",
      "station": "Station 1",
      "call_sign": "E1",
      "crew_size": 4,
      "created_at": "2025-01-01 08:00:00",
      "updated_at": "2025-01-20 14:30:00",
      "employees": [
        {
          "id": 42,
          "name": "John Doe",
          "rank": "Captain",
          "role": "Officer"
        },
        {
          "id": 43,
          "name": "Jane Smith",
          "rank": "Firefighter",
          "role": "Driver"
        }
      ],
      "vehicles": [
        {
          "id": 10,
          "name": "Engine 1",
          "type": "fire_engine",
          "plate": "FD-E1",
          "status": "operational"
        }
      ]
    },
    {
      "id": 2,
      "name": "Ladder 1",
      "type": "ladder",
      "status": "on_call",
      "station": "Station 1",
      "call_sign": "L1",
      "crew_size": 3,
      "created_at": "2025-01-01 08:00:00",
      "updated_at": "2025-01-20 15:45:00",
      "employees": [],
      "vehicles": []
    }
  ]
}
```

### Get Employees

**GET** `/backend/dispatch/?action=getEmployees`

Ruft alle für Einsatzzuweisungen verfügbaren Mitarbeiter ab.

**Erforderliche Berechtigung:** `READ_DISPATCH`

**Response (200):**
```json
{
  "employees": [
    {
      "id": 42,
      "employee_number": "EMP-001",
      "first_name": "John",
      "last_name": "Doe",
      "rank_id": 5,
      "rank_name": "Captain",
      "department": "Operations",
      "status": "active",
      "certifications": ["Fire Officer I", "EMT"],
      "current_dispatch_id": null,
      "available": true
    },
    {
      "id": 43,
      "employee_number": "EMP-002",
      "first_name": "Jane",
      "last_name": "Smith",
      "rank_id": 3,
      "rank_name": "Firefighter",
      "department": "Operations",
      "status": "active",
      "certifications": ["Firefighter II", "Driver Operator"],
      "current_dispatch_id": 1,
      "available": false
    }
  ]
}
```

### Get Vehicles

**GET** `/backend/dispatch/?action=getVehicles`

Ruft alle für Einsatzzuweisungen verfügbaren Fahrzeuge ab.

**Erforderliche Berechtigung:** `READ_DISPATCH`

**Response (200):**
```json
{
  "vehicles": [
    {
      "id": 10,
      "name": "Engine 1",
      "type": "fire_engine",
      "plate": "FD-E1",
      "vin": "1HGBH41JXMN109186",
      "year": 2020,
      "make": "Pierce",
      "model": "Enforcer",
      "status": "operational",
      "station": "Station 1",
      "current_dispatch_id": 1,
      "available": false,
      "last_service": "2025-01-10",
      "next_service": "2025-04-10"
    }
  ]
}
```

### Add Dispatch Unit

**POST** `/backend/dispatch/?action=addDispatch`

Erstellt eine neue Einsatzeinheit.

**Erforderliche Berechtigung:** `WRITE_DISPATCH`

**Request Body:**
```json
{
  "name": "Rescue 1",
  "type": "rescue",
  "status": "available",
  "station": "Station 2",
  "call_sign": "R1",
  "crew_size": 4,
  "notes": "Heavy rescue unit"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Dispatch unit created successfully",
  "dispatch_id": 3
}
```

### Edit Dispatch Unit

**POST** `/backend/dispatch/?action=editDispatch`

Aktualisiert eine bestehende Einsatzeinheit.

**Erforderliche Berechtigung:** `WRITE_DISPATCH`

**Request Body:**
```json
{
  "id": 3,
  "name": "Rescue 1",
  "type": "rescue",
  "status": "on_call",
  "station": "Station 2",
  "call_sign": "R1",
  "crew_size": 5,
  "notes": "Heavy rescue unit - crew increased"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Dispatch unit updated successfully"
}
```

### Delete Dispatch Unit

**POST** `/backend/dispatch/?action=deleteDispatch`

Löscht eine Einsatzeinheit (entfernt zuerst alle Zuweisungen).

**Erforderliche Berechtigung:** `DELETE_DISPATCH`

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
  "message": "Dispatch unit deleted successfully"
}
```

### Assign Employee

**POST** `/backend/dispatch/?action=assignEmployee`

Weist einen Mitarbeiter einer Einsatzeinheit zu.

**Erforderliche Berechtigung:** `WRITE_DISPATCH`

**Request Body:**
```json
{
  "dispatch_id": 1,
  "employee_id": 42,
  "role": "Officer",
  "shift_start": "2025-01-20 08:00:00",
  "shift_end": "2025-01-20 20:00:00"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Employee assigned to dispatch unit successfully",
  "assignment_id": 15
}
```

### Assign Vehicle

**POST** `/backend/dispatch/?action=assignVehicle`

Weist ein Fahrzeug einer Einsatzeinheit zu.

**Erforderliche Berechtigung:** `WRITE_DISPATCH`

**Request Body:**
```json
{
  "dispatch_id": 1,
  "vehicle_id": 10
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Vehicle assigned to dispatch unit successfully",
  "assignment_id": 8
}
```

### Save Dispatch Configuration

**POST** `/backend/dispatch/?action=saveDispatch`

Speichert die vollständige Einsatzkonfiguration einschließlich Einheitendetails, Mitarbeitern und Fahrzeugen in einem Aufruf.

**Erforderliche Berechtigung:** `WRITE_DISPATCH`

**Request Body:**
```json
{
  "id": 1,
  "name": "Engine 1",
  "type": "engine",
  "status": "available",
  "station": "Station 1",
  "call_sign": "E1",
  "crew_size": 4,
  "employees": [
    {
      "employee_id": 42,
      "role": "Officer"
    },
    {
      "employee_id": 43,
      "role": "Driver"
    }
  ],
  "vehicles": [
    {
      "vehicle_id": 10
    }
  ]
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Dispatch configuration saved successfully"
}
```

## Dispatch Unit Types

Das Feld `type` unterstützt:
- `engine`: Löschfahrzeug/Pumpe
- `ladder`: Drehleiterfahrzeug
- `rescue`: Rettungsfahrzeug
- `ambulance`: Krankentransport
- `command`: Kommandofahrzeug
- `hazmat`: Gefahrguteinheit
- `water_tender`: Wassertransporter
- `brush`: Waldbrand-Einheit
- `utility`: Unterstützungs-/Versorgungsfahrzeug

## Status Values

Das Feld `status` unterstützt:
- `available`: Einheit einsatzbereit
- `on_call`: Einheit aktuell im Einsatz
- `out_of_service`: Einheit nicht einsatzfähig
- `training`: Einheit in Ausbildung
- `staging`: Einheit am Einsatzort in Bereitstellung
- `returning`: Einheit kehrt zur Wache zurück
- `maintenance`: Einheit in Wartung

## Employee Roles

Gängige Mannschaftsrollen umfassen:
- `Officer`: Einheitsführer/Kommandant
- `Driver`: Fahrzeugführer/Maschinist
- `Firefighter`: Feuerwehrpersonal
- `Medic`: Medizinisches Personal
- `Engineer`: Gerätewart
- `Chief`: Führungsoffizier

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad request - Fehlende erforderliche Parameter oder ungültige Daten |
| 403 | Forbidden - Unzureichende Berechtigungen oder ungültige Behörde |
| 404 | Not found - Einsatzeinheit, Mitarbeiter oder Fahrzeug existiert nicht |
| 405 | Method not allowed - Falsche HTTP-Methode verwendet |
| 409 | Conflict - Mitarbeiter oder Fahrzeug bereits zugewiesen |
| 500 | Internal server error - Datenbank- oder Systemfehler |

## Business Rules

1. **Crew Size**: System verfolgt und validiert Mannschaftsgrößenbeschränkungen
2. **Availability**: Mitarbeiter/Fahrzeuge können nur einer Einheit gleichzeitig zugewiesen werden
3. **Certifications**: System prüft erforderliche Qualifikationen für Rollen
4. **Status Management**: Einheitenstatus ändert sich basierend auf Zuweisungen und Aktivität
5. **Shift Tracking**: Mitarbeiterzuweisungen umfassen Schichtzeiten
6. **Station Assignment**: Einheiten werden bestimmten Wachen zugewiesen
7. **Call Signs**: Eindeutige Funkrufzeichen für jede Einheit

## Multi-Tenant Isolation

Alle Einsatzvorgänge sind automatisch auf die Behörde des authentifizierten Benutzers beschränkt. Einheiten, Mitarbeiter und Fahrzeuge sind behördenspezifisch.

## Examples

### JavaScript Example

```javascript
// Get all dispatch units
async function getDispatches() {
  const response = await fetch('/backend/dispatch/?action=getDispatches', {
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
  return data.dispatches;
}

// Create new dispatch unit
async function createDispatchUnit(unitData) {
  const response = await fetch('/backend/dispatch/?action=addDispatch', {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(unitData)
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.error || 'Failed to create dispatch unit');
  }

  return data;
}

// Assign employee to dispatch unit
async function assignEmployeeToUnit(dispatchId, employeeId, role) {
  const response = await fetch('/backend/dispatch/?action=assignEmployee', {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      dispatch_id: dispatchId,
      employee_id: employeeId,
      role: role,
      shift_start: new Date().toISOString(),
      shift_end: new Date(Date.now() + 12 * 60 * 60 * 1000).toISOString()
    })
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.error || 'Failed to assign employee');
  }

  return data;
}

// Update unit status
async function updateUnitStatus(dispatchId, status) {
  const response = await fetch('/backend/dispatch/?action=editDispatch', {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      id: dispatchId,
      status: status
    })
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.error || 'Failed to update status');
  }

  return data;
}
```

### cURL Examples

```bash
# Get all dispatch units
curl -X GET \
  'http://localhost:8080/backend/dispatch/?action=getDispatches' \
  -H 'Cookie: token=YOUR_JWT_TOKEN'

# Create new dispatch unit
curl -X POST \
  'http://localhost:8080/backend/dispatch/?action=addDispatch' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Engine 2",
    "type": "engine",
    "status": "available",
    "station": "Station 1",
    "call_sign": "E2",
    "crew_size": 4
  }'

# Assign employee to unit
curl -X POST \
  'http://localhost:8080/backend/dispatch/?action=assignEmployee' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "dispatch_id": 1,
    "employee_id": 42,
    "role": "Officer",
    "shift_start": "2025-01-20 08:00:00",
    "shift_end": "2025-01-20 20:00:00"
  }'

# Assign vehicle to unit
curl -X POST \
  'http://localhost:8080/backend/dispatch/?action=assignVehicle' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "dispatch_id": 1,
    "vehicle_id": 10
  }'

# Delete dispatch unit
curl -X POST \
  'http://localhost:8080/backend/dispatch/?action=deleteDispatch' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{"id": 3}'
```

## Best Practices

1. **Pre-Planning**: Konfigurieren Sie Standard-Einheitenzusammensetzungen im Voraus
2. **Certification Validation**: Überprüfen Sie Mannschaftsqualifikationen vor der Zuweisung
3. **Status Updates**: Halten Sie den Einheitenstatus aktuell für genaue Verfügbarkeit
4. **Shift Management**: Verfolgen Sie Schichtzeiten für Rechenschaftspflicht
5. **Call Sign Standards**: Verwenden Sie konsistente Funkrufzeichen-Namenskonventionen
6. **Station Organization**: Gruppieren Sie Einheiten nach Wache für bessere Verwaltung
7. **Vehicle Maintenance**: Überwachen Sie Fahrzeugstatus und Wartungspläne
8. **Crew Balance**: Stellen Sie eine angemessene Mischung von Rängen und Fähigkeiten pro Einheit sicher

## Operational Workflow

1. **Unit Creation**: Erstellen Sie Einsatzeinheit mit Basisinformationen
2. **Staffing**: Weisen Sie qualifizierte Mitarbeiter zu Mannschaftspositionen zu
3. **Equipment Assignment**: Weisen Sie Fahrzeuge und Ausrüstung zu
4. **Status Ready**: Markieren Sie Einheit als einsatzbereit
5. **Call Response**: Aktualisieren Sie Status auf "on_call" bei Alarmierung
6. **Scene Operations**: Aktualisieren Sie Status während des Einsatzes
7. **Return**: Markieren Sie Einheit als zurückkehrend/wieder im Dienst
8. **Shift Change**: Weisen Sie Mannschaft für nächste Schicht neu zu

## Integration Points

Einsatzleitungssystem integriert mit:
- **Employee Management**: Mannschaftsqualifikation und Verfügbarkeit
- **Vehicle Management**: Fahrzeugstatus und Wartung
- **Calendar**: Schichtplanung
- **Map/GPS**: Einheitenstandortverfolgung
- **Training**: Zertifizierungsanforderungen
- **Reporting**: Betriebsstatistiken

## Real-Time Updates

Für Echtzeit-Einsatzaktualisierungen verwenden Sie die WebSocket API:

```javascript
socket.on('dispatch:status_change', (data) => {
  console.log(`Unit ${data.unit_id} status changed to ${data.status}`);
});

socket.on('dispatch:assignment_change', (data) => {
  console.log(`Assignment updated for unit ${data.unit_id}`);
});
```

## Database Tables

Die Einsatzleitung API interagiert mit folgenden Tabellen:

- `dispatches`: Einsatzeinheiten
- `dispatch_employees`: Mitarbeiterzuweisungen zu Einheiten
- `dispatch_vehicles`: Fahrzeugzuweisungen zu Einheiten
- `employees`: Mitarbeiterinformationen
- `vehicles`: Fahrzeuginformationen

Alle Tabellen enthalten `authority_id` für Multi-Tenant-Datenisolation.
