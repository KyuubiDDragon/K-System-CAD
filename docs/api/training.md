# Training API

The Training API provides endpoints for managing training programs, training assignments to employees, and tracking training certifications.

## Base URL

```
/backend/training/
```

## Authentication

All endpoints require a valid JWT token passed as a cookie. The token must contain valid `userId`, `authority`, and `authority_id` claims.

### Required Permissions

- **READ_TRAINING**: View training definitions and assignments
- **WRITE_TRAINING**: Create and edit training assignments
- **READ_EMPLOYEE**: View employee data for training assignments
- **READ_RANK**: View rank data for training filtering
- **READ_COMPANY**: View company data for training assignments

## Endpoints

### Get Trainings

**GET** `/backend/training/?action=getTrainings`

Retrieves all training definitions available in the system.

**Required Permission:** `READ_TRAINING`

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

Retrieves all training assignments for employees in the authority.

**Required Permission:** `READ_TRAINING`

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

Creates or updates a training assignment for an employee.

**Required Permission:** `WRITE_TRAINING`

**Request Body (New Assignment):**
```json
{
  "employee_id": 42,
  "training_id": 1,
  "assigned_date": "2025-01-20",
  "status": "assigned",
  "notes": "Required for promotion"
}
```

**Request Body (Update Assignment):**
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

Retrieves all employees available for training assignment.

**Required Permission:** `READ_EMPLOYEE`

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

Retrieves all employee ranks for filtering training requirements.

**Required Permission:** `READ_RANK`

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

Retrieves companies for external training provider information.

**Required Permission:** `READ_COMPANY`

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

The `status` field for training assignments supports:
- `assigned`: Training has been assigned to employee
- `in_progress`: Employee is currently taking the training
- `completed`: Training has been successfully completed
- `failed`: Training was not completed successfully
- `expired`: Training certification has expired
- `cancelled`: Training assignment was cancelled

## Training Categories

Common training categories include:
- Safety
- Medical
- Technical
- Leadership
- Compliance
- Operations
- Management

## Validity and Expiration

Training assignments track expiration dates:
- **validity_months**: How long the training certification is valid
- **expiry_date**: Calculated as `completion_date + validity_months`
- Expired trainings require re-certification

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad request - Missing required parameters or invalid data |
| 403 | Forbidden - Insufficient permissions or invalid authority |
| 404 | Not found - Training or assignment does not exist |
| 405 | Method not allowed - Wrong HTTP method used |
| 500 | Internal server error - Database or system error |

## Multi-Tenant Isolation

All training operations are automatically scoped to the authenticated user's authority. Training definitions may be system-wide, but assignments are authority-specific.

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

1. **Assignment Planning**: Plan training schedules to avoid operational disruption
2. **Mandatory Trainings**: Prioritize mandatory trainings and track compliance
3. **Expiration Tracking**: Monitor expiring certifications and schedule renewals
4. **Documentation**: Store certificates and completion records
5. **Score Tracking**: Record training scores for performance evaluation
6. **Notes**: Document special circumstances or requirements
7. **Status Updates**: Keep assignment status current
8. **Bulk Operations**: Use batch processing for organization-wide training initiatives

## Training Workflow

1. **Assignment**: Admin assigns training to employee
2. **Notification**: Employee is notified of training requirement
3. **Scheduling**: Training date and time are scheduled
4. **Completion**: Employee completes training
5. **Certification**: Certificate is uploaded and recorded
6. **Expiration**: System tracks expiration and notifies before expiry
7. **Renewal**: Expired trainings are reassigned for renewal

## Certification Management

Certificates should be stored in the authority-specific directory:

**Upload Path:** `uploads/{authority}/trainings/`

Certificate files typically include:
- PDF certificates
- Completion documents
- Test results
- Attendance records

## Reporting

Training data is commonly used for:
- Compliance reporting
- Competency tracking
- Budget planning
- Performance reviews
- Audit documentation
- Accreditation requirements

## Database Tables

The Training API interacts with the following tables:

- `trainings`: Training definitions
- `training_assigns`: Employee training assignments
- `employees`: Employee information
- `ranks`: Employee rank/position information
- `companies`: External training providers

All tables include `authority_id` for multi-tenant data isolation.
