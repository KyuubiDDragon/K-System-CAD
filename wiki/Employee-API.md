# Employee API

Comprehensive API for employee management including CRUD operations, department assignments, ranks, and status management.

## Base URL

```
/backend/employee/
```

## Authentication

All endpoints require valid JWT token and `READ_EMPLOYEE` permission (write operations require `WRITE_EMPLOYEE`).

## List Employees

**GET** `/employee/`

Retrieve list of employees for current authority with pagination and filtering.

**Endpoint:**
```http
GET /employee/?page=1&limit=25&search=john&department=fire&status=active
Cookie: auth_token=YOUR_TOKEN
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | integer | No | Page number (default: 1) |
| limit | integer | No | Items per page (default: 25, max: 100) |
| search | string | No | Search first name, last name, or ID |
| department | string | No | Filter by department name |
| status | string | No | Filter by status (active/inactive) |
| rank | string | No | Filter by rank |
| sort | string | No | Sort field (lastname, firstname, id, department) |
| order | string | No | Sort order (asc/desc, default: asc) |

**Success Response (200 OK):**
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

**Example Request (cURL):**
```bash
curl 'http://localhost:8080/employee/?search=john&status=active' \
  -H 'Cookie: auth_token=YOUR_TOKEN'
```

**Example Request (JavaScript):**
```javascript
const response = await fetch('/employee/?search=john&status=active', {
  credentials: 'include'
});
const { data, pagination } = await response.json();
```

## Get Single Employee

**GET** `/employee/`?id={id}

Retrieve detailed information for specific employee.

**Endpoint:**
```http
GET /employee/?id=123
Cookie: auth_token=YOUR_TOKEN
```

**Success Response (200 OK):**
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

**Error Response (404 Not Found):**
```json
{
  "success": false,
  "error": "Employee not found"
}
```

## Create Employee

**POST** `/employee/`

Create new employee record.

**Required Permission:** `WRITE_EMPLOYEE`

**Endpoint:**
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

**Required Fields:**
- `firstname` (string, 1-100 chars)
- `lastname` (string, 1-100 chars)

**Optional Fields:**
- All other fields from request body

**Success Response (201 Created):**
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

**Error Response (400 Bad Request):**
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

**Error Response (409 Conflict):**
```json
{
  "success": false,
  "error": "Employee ID already exists"
}
```

## Update Employee

**PUT** `/employee/`?id={id}

Update existing employee record.

**Required Permission:** `WRITE_EMPLOYEE`

**Endpoint:**
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

**Note:** Only include fields you want to update. All fields are optional.

**Success Response (200 OK):**
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

**Error Response (404 Not Found):**
```json
{
  "success": false,
  "error": "Employee not found"
}
```

## Delete Employee

**DELETE** `/employee/`?id={id}

Delete employee record (soft delete - marks as deleted).

**Required Permission:** `DELETE_EMPLOYEE`

**Endpoint:**
```http
DELETE /employee/?id=123
Cookie: auth_token=YOUR_TOKEN
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Employee deleted successfully"
}
```

**Error Response (404 Not Found):**
```json
{
  "success": false,
  "error": "Employee not found"
}
```

**Error Response (409 Conflict):**
```json
{
  "success": false,
  "error": "Cannot delete employee with active reports"
}
```

## Additional Endpoints

## Get Departments

```http
GET /employee/?action=getDepartments
```

Returns list of all departments in authority.

**Response:**
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

## Get Ranks

```http
GET /employee/?action=getRanks
```

Returns list of all ranks in authority.

**Response:**
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

## Upload Avatar

```http
POST /employee/?action=uploadAvatar&id=123
Content-Type: multipart/form-data
```

**Form Data:**
- `avatar`: Image file (JPG, PNG, max 5MB)

**Response:**
```json
{
  "success": true,
  "message": "Avatar uploaded successfully",
  "avatar_url": "/uploads/employees/123.jpg"
}
```

## Data Validation

## Employee ID
- Optional field
- Must be unique within authority
- Alphanumeric with hyphens allowed
- Max 50 characters

## Email
- Must be valid email format
- Unique within authority (optional)
- Max 255 characters

### Phone Numbers
- Optional
- Format: +X-XXX-XXXX or (XXX) XXX-XXXX
- Validated on frontend, stored as-is

### Dates
- Format: YYYY-MM-DD
- hire_date cannot be in future
- birth_date must be at least 18 years ago

### Status
- Valid values: active, inactive, terminated, suspended
- Default: active

## Permissions

| Action | Required Permission | Notes |
|--------|-------------------|-------|
| List Employees | READ_EMPLOYEE | Only shows employees in user's authority |
| View Employee | READ_EMPLOYEE | Cannot view employees from other authorities |
| Create Employee | WRITE_EMPLOYEE | Creates in user's authority |
| Update Employee | WRITE_EMPLOYEE | Cannot update employees from other authorities |
| Delete Employee | DELETE_EMPLOYEE | Soft delete only |

## Best Practices

**Creating Employees:**
1. Validate data on frontend before submitting
2. Use unique employee_id if your organization requires it
3. Include hire_date for accurate record-keeping
4. Add emergency contact information

**Updating Employees:**
1. Only send changed fields
2. Validate status changes (active -> terminated requires confirmation)
3. Log significant changes (rank promotions, department transfers)

**Searching Employees:**
1. Use specific search terms for better performance
2. Apply filters to narrow results
3. Use pagination for large employee lists

**Performance:**
1. Limit results to 25-50 per page
2. Use specific filters instead of searching all fields
3. Cache department/rank lists on frontend

## Example Integration

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

## Error Codes

| Status | Error | Cause | Solution |
|--------|-------|-------|----------|
| 400 | Validation failed | Invalid data | Check field formats |
| 401 | Unauthorized | No/invalid token | Login again |
| 403 | Forbidden | Missing permission | Request WRITE_EMPLOYEE permission |
| 404 | Not found | Invalid ID | Verify employee exists |
| 409 | Conflict | Duplicate employee_id | Use unique employee_id |
| 500 | Server error | Database error | Contact administrator |

## Next Steps

- [[Training-API]] - Employee training and certifications
- [[Vacation-API]] - Employee vacation management
- [[Report-API]] - Link employees to reports
- [[Admin-API]] - User account management

---

> **Tip: Multi-Tenant Isolation** - All employee queries automatically filter by authority_id. You can never access employees from other authorities.

> **Warning: Soft Delete** - Deleted employees are marked as deleted but not removed from database. This preserves historical data and report associations.
