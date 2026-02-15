# Report API

Complete API documentation for the K-Systems report management system including custom fields, categories, status workflow, and file attachments.

## Base URL

```
/backend/report/
```

## Authentication

Requires `READ_REPORT` permission for viewing, `WRITE_REPORT` for creating/editing, `DELETE_REPORT` for deletion.

## List Reports

**GET** `/report/`

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| page | integer | Page number (default: 1) |
| limit | integer | Items per page (default: 25) |
| search | string | Search title, content, ID |
| status | string | Filter by status |
| category | string | Filter by category |
| date_from | string | Start date (YYYY-MM-DD) |
| date_to | string | End date (YYYY-MM-DD) |
| reporter_id | integer | Filter by reporter |

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 456,
      "title": "Fire Incident Report",
      "report_number": "RPT-2024-001",
      "category": "Fire",
      "status": "completed",
      "reporter_id": 123,
      "reporter_name": "John Smith",
      "created_at": "2024-03-15T10:00:00Z",
      "updated_at": "2024-03-15T14:00:00Z",
      "custom_fields": {
        "incident_type": "Structure Fire",
        "location": "123 Main St",
        "severity": "High"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 10,
    "total_items": 250
  }
}
```

## Get Single Report

**GET** `/report/`?id={id}

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 456,
    "title": "Fire Incident Report",
    "report_number": "RPT-2024-001",
    "category": "Fire",
    "status": "completed",
    "content": "Detailed report content...",
    "reporter_id": 123,
    "reporter_name": "John Smith",
    "created_at": "2024-03-15T10:00:00Z",
    "updated_at": "2024-03-15T14:00:00Z",
    "custom_fields": {
      "incident_type": "Structure Fire",
      "location": "123 Main St",
      "severity": "High",
      "units_responded": 3
    },
    "attachments": [
      {
        "id": 1,
        "filename": "scene_photo.jpg",
        "size": 1024000,
        "url": "/uploads/reports/456/scene_photo.jpg"
      }
    ],
    "persons": [
      {
        "id": 789,
        "name": "Jane Doe",
        "role": "Witness"
      }
    ],
    "companies": [
      {
        "id": 10,
        "name": "ABC Insurance"
      }
    ]
  }
}
```

## Create Report

**POST** `/report/`

**Request Body:**
```json
{
  "title": "Fire Incident Report",
  "category": "Fire",
  "status": "draft",
  "content": "Report content...",
  "custom_fields": {
    "incident_type": "Structure Fire",
    "location": "123 Main St",
    "severity": "High"
  },
  "persons": [789],
  "companies": [10]
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Report created successfully",
  "data": {
    "id": 457,
    "report_number": "RPT-2024-002"
  }
}
```

## Update Report

**PUT** `/report/`?id={id}

**Request Body:** (only include fields to update)
```json
{
  "title": "Updated Title",
  "status": "completed",
  "custom_fields": {
    "severity": "Medium"
  }
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Report updated successfully"
}
```

## Delete Report

**DELETE** `/report/`?id={id}

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Report deleted successfully"
}
```

## Share Report

**POST** `/report/`share.php

Create shareable link with expiration.

**Request Body:**
```json
{
  "report_id": 456,
  "expires_days": 7
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "share_url": "https://your-domain.com/share/abc123def456",
  "expires_at": "2024-03-22T10:00:00Z"
}
```

## Upload Attachment

**POST** `/report/`?action=uploadAttachment&id={id}

**Form Data:**
- `file`: File to upload

**Response (200 OK):**
```json
{
  "success": true,
  "attachment": {
    "id": 2,
    "filename": "document.pdf",
    "size": 2048000,
    "url": "/uploads/reports/456/document.pdf"
  }
}
```

## Report Categories

**GET** `/reportcategory/`

**Response:**
```json
{
  "success": true,
  "data": [
    {"id": 1, "name": "Fire", "color": "#FF5722"},
    {"id": 2, "name": "Medical", "color": "#2196F3"},
    {"id": 3, "name": "Training", "color": "#4CAF50"}
  ]
}
```

## Report Status

**GET** `/reportstatus/`

**Response:**
```json
{
  "success": true,
  "data": [
    {"id": 1, "name": "Draft", "color": "#9E9E9E"},
    {"id": 2, "name": "In Progress", "color": "#FF9800"},
    {"id": 3, "name": "Completed", "color": "#4CAF50"}
  ]
}
```

## Custom Fields

**GET** `/admin/reportfields/`

Get custom field definitions for reports.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "incident_type",
      "label": "Incident Type",
      "type": "select",
      "options": ["Structure Fire", "Vehicle Fire", "Wildfire"],
      "required": true
    },
    {
      "id": 2,
      "name": "location",
      "label": "Location",
      "type": "text",
      "required": true
    }
  ]
}
```

---

> **Tip: Report Numbers** - Report numbers are auto-generated as RPT-YYYY-NNN format. Cannot be manually set.
