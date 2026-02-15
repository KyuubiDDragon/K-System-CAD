# Schulungen API

Die Schulungen API bietet Endpunkte zur Verwaltung von Schulungsprogrammen, Schulungszuweisungen für Mitarbeiter und Nachverfolgung von Schulungszertifikaten.

## Base URL

```
/backend/training/
```

## Authentication

Alle Endpunkte erfordern einen gültigen JWT-Token, der als Cookie übergeben wird. Der Token muss gültige `userId`, `authority` und `authority_id` Claims enthalten.

### Erforderliche Berechtigungen

- **READ_TRAINING**: Schulungsdefinitionen und Zuweisungen anzeigen
- **WRITE_TRAINING**: Schulungszuweisungen erstellen und bearbeiten
- **READ_EMPLOYEE**: Mitarbeiterdaten für Schulungszuweisungen anzeigen
- **READ_RANK**: Rangdaten für Schulungsfilterung anzeigen
- **READ_COMPANY**: Unternehmensdaten für Schulungszuweisungen anzeigen

## Endpoints

### Get Trainings

**GET** `/backend/training/?action=getTrainings`

Ruft alle im System verfügbaren Schulungsdefinitionen ab.

**Erforderliche Berechtigung:** `READ_TRAINING`

**Response (200):**
```json
{
  "trainings": [
    {
      "id": 1,
      "name": "Fire Safety Training",
      "description": "Basic fire safety and prevention",
      "duration_hours": 8,
      "validity_months": 12,
      "category": "Safety",
      "mandatory": true,
      "created_at": "2024-01-01 10:00:00"
    },
    {
      "id": 2,
      "name": "First Aid Certification",
      "description": "CPR and basic first aid",
      "duration_hours": 16,
      "validity_months": 24,
      "category": "Medical",
      "mandatory": true,
      "created_at": "2024-01-01 10:00:00"
    }
  ]
}
```

### Get Training Assignments

**GET** `/backend/training/?action=getTrainingAssigns`

Ruft alle Schulungszuweisungen für Mitarbeiter in der Behörde ab.

**Erforderliche Berechtigung:** `READ_TRAINING`

**Response (200):**
```json
{
  "assignments": [
    {
      "id": 1,
      "employee_id": 42,
      "employee_name": "John Doe",
      "training_id": 1,
      "training_name": "Fire Safety Training",
      "assigned_date": "2025-01-10",
      "completion_date": "2025-01-15",
      "expiry_date": "2026-01-15",
      "status": "completed",
      "score": 95,
      "certificate_url": "/uploads/authority1/trainings/cert_1.pdf",
      "notes": "Excellent performance"
    },
    {
      "id": 2,
      "employee_id": 43,
      "employee_name": "Jane Smith",
      "training_id": 2,
      "training_name": "First Aid Certification",
      "assigned_date": "2025-01-05",
      "completion_date": null,
      "expiry_date": null,
      "status": "in_progress",
      "score": null,
      "certificate_url": null,
      "notes": "Scheduled for February"
    }
  ]
}
```

### Save Training Assignment

**POST** `/backend/training/?action=saveTraining`

Erstellt oder aktualisiert eine Schulungszuweisung für einen Mitarbeiter.

**Erforderliche Berechtigung:** `WRITE_TRAINING`

**Request Body (Neue Zuweisung):**
```json
{
  "employee_id": 42,
  "training_id": 1,
  "assigned_date": "2025-01-20",
  "status": "assigned",
  "notes": "Required for promotion"
}
```

**Request Body (Zuweisung aktualisieren):**
```json
{
  "id": 1,
  "employee_id": 42,
  "training_id": 1,
  "assigned_date": "2025-01-10",
  "completion_date": "2025-01-15",
  "expiry_date": "2026-01-15",
  "status": "completed",
  "score": 95,
  "certificate_url": "/uploads/authority1/trainings/cert_1.pdf",
  "notes": "Excellent performance"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Training assignment saved successfully",
  "assignment_id": 1
}
```

### Get Employees

**GET** `/backend/training/?action=getEmployees`

Ruft alle für Schulungszuweisungen verfügbaren Mitarbeiter ab.

**Erforderliche Berechtigung:** `READ_EMPLOYEE`

**Response (200):**
```json
{
  "employees": [
    {
      "id": 42,
      "first_name": "John",
      "last_name": "Doe",
      "employee_number": "EMP-001",
      "department": "Operations",
      "rank_id": 5,
      "rank_name": "Firefighter",
      "email": "john.doe@example.com",
      "status": "active"
    }
  ]
}
```

### Get Ranks

**GET** `/backend/training/?action=getRanks`

Ruft alle Mitarbeiterränge zur Filterung von Schulungsanforderungen ab.

**Erforderliche Berechtigung:** `READ_RANK`

**Response (200):**
```json
{
  "ranks": [
    {
      "id": 1,
      "name": "Firefighter",
      "level": 1,
      "description": "Entry level firefighter"
    },
    {
      "id": 2,
      "name": "Lieutenant",
      "level": 2,
      "description": "Team leader"
    }
  ]
}
```

### Get Companies

**GET** `/backend/training/?action=getCompanies`

Ruft Unternehmen für externe Schulungsanbieterinformationen ab.

**Erforderliche Berechtigung:** `READ_COMPANY`

**Response (200):**
```json
{
  "companies": [
    {
      "id": 1,
      "name": "Professional Training Institute",
      "type": "training_provider",
      "address": "123 Education St",
      "city": "Boston",
      "phone": "+1-555-0100",
      "email": "info@pti.com"
    }
  ]
}
```

## Training Status Values

Das Feld `status` für Schulungszuweisungen unterstützt:
- `assigned`: Schulung wurde dem Mitarbeiter zugewiesen
- `in_progress`: Mitarbeiter absolviert aktuell die Schulung
- `completed`: Schulung wurde erfolgreich abgeschlossen
- `failed`: Schulung wurde nicht erfolgreich abgeschlossen
- `expired`: Schulungszertifikat ist abgelaufen
- `cancelled`: Schulungszuweisung wurde storniert

## Training Categories

Gängige Schulungskategorien umfassen:
- Safety
- Medical
- Technical
- Leadership
- Compliance
- Operations
- Management

## Validity and Expiration

Schulungszuweisungen verfolgen Ablaufdaten:
- **validity_months**: Wie lange die Schulungszertifizierung gültig ist
- **expiry_date**: Berechnet als `completion_date + validity_months`
- Abgelaufene Schulungen erfordern eine Rezertifizierung

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad request - Fehlende erforderliche Parameter oder ungültige Daten |
| 403 | Forbidden - Unzureichende Berechtigungen oder ungültige Behörde |
| 404 | Not found - Schulung oder Zuweisung existiert nicht |
| 405 | Method not allowed - Falsche HTTP-Methode verwendet |
| 500 | Internal server error - Datenbank- oder Systemfehler |

## Multi-Tenant Isolation

Alle Schulungsvorgänge sind automatisch auf die Behörde des authentifizierten Benutzers beschränkt. Schulungsdefinitionen können systemweit sein, aber Zuweisungen sind behördenspezifisch.

## Examples

### JavaScript Example

```javascript
// Get all trainings
async function getTrainings() {
  const response = await fetch('/backend/training/?action=getTrainings', {
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
  return data.trainings;
}

// Get training assignments
async function getTrainingAssignments() {
  const response = await fetch('/backend/training/?action=getTrainingAssigns', {
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
  return data.assignments;
}

// Assign training to employee
async function assignTraining(employeeId, trainingId, assignedDate, notes) {
  const response = await fetch('/backend/training/?action=saveTraining', {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      employee_id: employeeId,
      training_id: trainingId,
      assigned_date: assignedDate,
      status: 'assigned',
      notes: notes
    })
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.error || 'Failed to assign training');
  }

  return data;
}

// Complete training
async function completeTraining(assignmentId, completionDate, score, certificateUrl) {
  const response = await fetch('/backend/training/?action=saveTraining', {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      id: assignmentId,
      completion_date: completionDate,
      status: 'completed',
      score: score,
      certificate_url: certificateUrl
    })
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.error || 'Failed to complete training');
  }

  return data;
}
```

### cURL Examples

```bash
# Get all trainings
curl -X GET \
  'http://localhost:8080/backend/training/?action=getTrainings' \
  -H 'Cookie: token=YOUR_JWT_TOKEN'

# Get training assignments
curl -X GET \
  'http://localhost:8080/backend/training/?action=getTrainingAssigns' \
  -H 'Cookie: token=YOUR_JWT_TOKEN'

# Assign training to employee
curl -X POST \
  'http://localhost:8080/backend/training/?action=saveTraining' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "employee_id": 42,
    "training_id": 1,
    "assigned_date": "2025-01-20",
    "status": "assigned",
    "notes": "Required for promotion"
  }'

# Mark training as completed
curl -X POST \
  'http://localhost:8080/backend/training/?action=saveTraining' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "id": 1,
    "completion_date": "2025-01-25",
    "expiry_date": "2026-01-25",
    "status": "completed",
    "score": 95,
    "certificate_url": "/uploads/authority1/trainings/cert_1.pdf"
  }'
```

## Best Practices

1. **Assignment Planning**: Planen Sie Schulungstermine, um operative Störungen zu vermeiden
2. **Mandatory Trainings**: Priorisieren Sie Pflichtschulungen und verfolgen Sie die Compliance
3. **Expiration Tracking**: Überwachen Sie ablaufende Zertifizierungen und planen Sie Erneuerungen
4. **Documentation**: Speichern Sie Zertifikate und Abschlussnachweise
5. **Score Tracking**: Erfassen Sie Schulungsergebnisse für Leistungsbewertungen
6. **Notes**: Dokumentieren Sie besondere Umstände oder Anforderungen
7. **Status Updates**: Halten Sie den Zuweisungsstatus aktuell
8. **Bulk Operations**: Verwenden Sie Stapelverarbeitung für organisationsweite Schulungsinitiativen

## Training Workflow

1. **Assignment**: Administrator weist Mitarbeiter eine Schulung zu
2. **Notification**: Mitarbeiter wird über Schulungsanforderung benachrichtigt
3. **Scheduling**: Schulungsdatum und -uhrzeit werden geplant
4. **Completion**: Mitarbeiter schließt Schulung ab
5. **Certification**: Zertifikat wird hochgeladen und erfasst
6. **Expiration**: System verfolgt Ablauf und benachrichtigt vor Ablauf
7. **Renewal**: Abgelaufene Schulungen werden zur Erneuerung neu zugewiesen

## Certification Management

Zertifikate sollten im behördenspezifischen Verzeichnis gespeichert werden:

**Upload Path:** `uploads/{authority}/trainings/`

Zertifikatsdateien umfassen typischerweise:
- PDF-Zertifikate
- Abschlussdokumente
- Testergebnisse
- Anwesenheitsnachweise

## Reporting

Schulungsdaten werden häufig verwendet für:
- Compliance-Berichte
- Kompetenzerfassung
- Budgetplanung
- Leistungsbeurteilungen
- Audit-Dokumentation
- Akkreditierungsanforderungen

## Database Tables

Die Schulungen API interagiert mit folgenden Tabellen:

- `trainings`: Schulungsdefinitionen
- `training_assigns`: Mitarbeiter-Schulungszuweisungen
- `employees`: Mitarbeiterinformationen
- `ranks`: Mitarbeiterrang-/Positionsinformationen
- `companies`: Externe Schulungsanbieter

Alle Tabellen enthalten `authority_id` für Multi-Tenant-Datenisolation.
