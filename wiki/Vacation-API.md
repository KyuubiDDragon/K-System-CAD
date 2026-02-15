# Vacation API

The Vacation API provides endpoints for managing employee vacation requests, approvals, and vacation balance tracking.

## Base URL

```
/backend/vacation/
```

## Authentication

All endpoints require a valid JWT token passed as a cookie. The token must contain valid `userId`, `authority`, and `authority_id` claims.

### Required Permissions

- **READ_VACATION**: View vacation requests and balances
- **WRITE_VACATION**: Create and edit vacation requests
- **APPROVE_VACATION**: Approve or reject vacation requests
- **DELETE_VACATION**: Delete vacation requests

## Endpoints

### Get Vacation Requests

**GET** `/backend/vacation/?action=getVacations`

Retrieves all vacation requests for the authority.

**Required Permission:** `READ_VACATION`

**Query Parameters:**
- `employee_id` (optional): Filter by specific employee
- `status` (optional): Filter by status (pending, approved, rejected, cancelled)
- `year` (optional): Filter by year

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

Retrieves the vacation balance for a specific employee.

**Required Permission:** `READ_VACATION`

**Query Parameters:**
- `employee_id` (required): ID of the employee
- `year` (optional): Year for balance calculation (defaults to current year)

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

Creates a new vacation request.

**Required Permission:** `WRITE_VACATION`

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

Updates an existing vacation request (only allowed if not yet approved).

**Required Permission:** `WRITE_VACATION`

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

Approves a vacation request.

**Required Permission:** `APPROVE_VACATION`

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

Rejects a vacation request.

**Required Permission:** `APPROVE_VACATION`

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

Cancels a vacation request (employee or manager can cancel).

**Required Permission:** `WRITE_VACATION`

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

Permanently deletes a vacation request.

**Required Permission:** `DELETE_VACATION`

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

Retrieves vacation calendar showing all approved vacations for team planning.

**Required Permission:** `READ_VACATION`

**Query Parameters:**
- `start_date` (optional): Start of date range
- `end_date` (optional): End of date range
- `department` (optional): Filter by department

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

Retrieves vacation requests pending approval (for managers).

**Required Permission:** `APPROVE_VACATION`

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

The `type` field supports the following values:
- `annual`: Standard annual leave/vacation
- `sick`: Sick leave
- `personal`: Personal leave
- `bereavement`: Bereavement leave
- `parental`: Parental/maternity/paternity leave
- `unpaid`: Unpaid leave
- `compensatory`: Compensatory time off
- `public_holiday`: Public holiday

## Vacation Status Values

The `status` field supports:
- `pending`: Request awaiting approval
- `approved`: Request has been approved
- `rejected`: Request was rejected
- `cancelled`: Request was cancelled
- `completed`: Vacation has been taken (past dates)

## Business Rules

1. **Minimum Notice**: Vacation requests typically require advance notice
2. **Overlap Prevention**: System checks for overlapping requests
3. **Balance Validation**: Cannot request more days than available
4. **Approval Workflow**: Requests require manager approval
5. **Cancellation Policy**: Approved vacations can be cancelled with notice
6. **Weekend/Holiday Calculation**: Working days only (configurable)
7. **Carryover Rules**: Unused vacation may carry over to next year (policy-dependent)

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad request - Invalid dates, insufficient balance, or missing parameters |
| 403 | Forbidden - Insufficient permissions or invalid authority |
| 404 | Not found - Vacation request does not exist |
| 405 | Method not allowed - Wrong HTTP method used |
| 409 | Conflict - Overlapping vacation dates |
| 500 | Internal server error - Database or system error |

## Multi-Tenant Isolation

All vacation operations are automatically scoped to the authenticated user's authority. Employees can only see their own requests unless they have manager/admin permissions.

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

1. **Early Planning**: Submit requests well in advance
2. **Team Coordination**: Check team calendar before requesting
3. **Clear Reasons**: Provide appropriate reason for request type
4. **Balance Monitoring**: Regularly check remaining balance
5. **Manager Communication**: Communicate special circumstances with manager
6. **Coverage Planning**: Arrange work coverage before vacation
7. **Status Updates**: Keep requests up to date if plans change
8. **Documentation**: Maintain records for sick leave and special leave types

## Vacation Balance Calculation

Balance is calculated as:

1. **Total Days**: Annual entitlement + carried over days
2. **Used Days**: Sum of approved and completed vacations
3. **Pending Days**: Sum of pending requests
4. **Remaining Days**: Total - Used - Pending

## Approval Workflow

Standard approval workflow:

1. **Employee** creates vacation request
2. **System** validates balance and dates
3. **Manager** receives notification
4. **Manager** reviews and approves/rejects
5. **Employee** receives notification of decision
6. **System** updates balance if approved

## Calendar Integration

Approved vacations appear in:
- Employee personal calendar
- Team calendar
- Department calendar
- Authority-wide vacation calendar

## Notifications

Vacation system sends notifications for:
- New request submitted
- Request approved
- Request rejected
- Request cancelled
- Upcoming vacation reminder
- Low balance warning

## Database Tables

The Vacation API interacts with the following tables:

- `vacations`: Vacation requests
- `vacation_balances`: Employee vacation balances
- `employees`: Employee information
- `users`: User accounts and approver information

All tables include `authority_id` for multi-tenant data isolation.
