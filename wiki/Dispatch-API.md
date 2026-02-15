# Dispatch API

The Dispatch API provides endpoints for managing dispatch units (crews), assigning employees and vehicles to units, and tracking operational readiness.

## Base URL

```
/backend/dispatch/
```

## Authentication

All endpoints require a valid JWT token passed as a cookie. The token must contain valid `userId`, `authority`, and `authority_id` claims.

### Required Permissions

- **READ_DISPATCH**: View dispatch units and assignments
- **WRITE_DISPATCH**: Create and edit dispatch units and assignments
- **DELETE_DISPATCH**: Delete dispatch units

## Endpoints

### Get Dispatches

**GET** `/backend/dispatch/?action=getDispatches`

Retrieves all dispatch units for the authority.

**Required Permission:** `READ_DISPATCH`

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

Retrieves all employees available for dispatch assignment.

**Required Permission:** `READ_DISPATCH`

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

Retrieves all vehicles available for dispatch assignment.

**Required Permission:** `READ_DISPATCH`

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

Creates a new dispatch unit.

**Required Permission:** `WRITE_DISPATCH`

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

Updates an existing dispatch unit.

**Required Permission:** `WRITE_DISPATCH`

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

Deletes a dispatch unit (removes all assignments first).

**Required Permission:** `DELETE_DISPATCH`

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

Assigns an employee to a dispatch unit.

**Required Permission:** `WRITE_DISPATCH`

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

Assigns a vehicle to a dispatch unit.

**Required Permission:** `WRITE_DISPATCH`

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

Saves complete dispatch configuration including unit details, employees, and vehicles in one call.

**Required Permission:** `WRITE_DISPATCH`

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

The `type` field supports:
- `engine`: Fire engine/pumper
- `ladder`: Ladder truck/aerial
- `rescue`: Rescue unit
- `ambulance`: Medical transport
- `command`: Command vehicle
- `hazmat`: Hazardous materials unit
- `water_tender`: Water supply tanker
- `brush`: Wildland firefighting unit
- `utility`: Support/utility vehicle

## Status Values

The `status` field supports:
- `available`: Unit ready for dispatch
- `on_call`: Unit currently on a call
- `out_of_service`: Unit not operational
- `training`: Unit engaged in training
- `staging`: Unit staged at scene
- `returning`: Unit returning to station
- `maintenance`: Unit undergoing maintenance

## Employee Roles

Common crew roles include:
- `Officer`: Unit officer/commander
- `Driver`: Apparatus driver/operator
- `Firefighter`: Firefighting personnel
- `Medic`: Medical personnel
- `Engineer`: Equipment specialist
- `Chief`: Chief officer

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad request - Missing required parameters or invalid data |
| 403 | Forbidden - Insufficient permissions or invalid authority |
| 404 | Not found - Dispatch unit, employee, or vehicle does not exist |
| 405 | Method not allowed - Wrong HTTP method used |
| 409 | Conflict - Employee or vehicle already assigned |
| 500 | Internal server error - Database or system error |

## Business Rules

1. **Crew Size**: System tracks and validates crew size limits
2. **Availability**: Employees/vehicles can only be assigned to one unit at a time
3. **Certifications**: System checks required certifications for roles
4. **Status Management**: Unit status changes based on assignments and activity
5. **Shift Tracking**: Employee assignments include shift times
6. **Station Assignment**: Units are assigned to specific stations
7. **Call Signs**: Unique radio call signs for each unit

## Multi-Tenant Isolation

All dispatch operations are automatically scoped to the authenticated user's authority. Units, employees, and vehicles are authority-specific.

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

1. **Pre-Planning**: Configure standard unit compositions in advance
2. **Certification Validation**: Verify crew certifications before assignment
3. **Status Updates**: Keep unit status current for accurate availability
4. **Shift Management**: Track shift times for accountability
5. **Call Sign Standards**: Use consistent call sign naming conventions
6. **Station Organization**: Group units by station for better management
7. **Vehicle Maintenance**: Monitor vehicle status and service schedules
8. **Crew Balance**: Ensure appropriate mix of ranks and skills per unit

## Operational Workflow

1. **Unit Creation**: Create dispatch unit with basic information
2. **Staffing**: Assign qualified employees to crew positions
3. **Equipment Assignment**: Assign vehicles and equipment
4. **Status Ready**: Mark unit as available for dispatch
5. **Call Response**: Update status to "on_call" when dispatched
6. **Scene Operations**: Update status during incident
7. **Return**: Mark unit returning/back in service
8. **Shift Change**: Reassign crew for next shift

## Integration Points

Dispatch system integrates with:
- **[[Employee-API]]**: Crew qualification and availability
- **Vehicle Management**: Apparatus status and maintenance
- **[[Calendar-API]]**: Shift scheduling
- **Map/GPS**: Unit location tracking
- **[[Training-API]]**: Certification requirements
- **[[Report-API]]**: Operational statistics

## Real-Time Updates

For real-time dispatch updates, use the [[WebSocket-API]]:

```javascript
socket.on('dispatch:status_change', (data) => {
  console.log(`Unit ${data.unit_id} status changed to ${data.status}`);
});

socket.on('dispatch:assignment_change', (data) => {
  console.log(`Assignment updated for unit ${data.unit_id}`);
});
```

## Database Tables

The Dispatch API interacts with the following tables:

- `dispatches`: Dispatch units
- `dispatch_employees`: Employee assignments to units
- `dispatch_vehicles`: Vehicle assignments to units
- `employees`: Employee information
- `vehicles`: Vehicle information

All tables include `authority_id` for multi-tenant data isolation.
