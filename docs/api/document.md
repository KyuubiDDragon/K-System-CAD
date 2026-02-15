# Document API

API for managing documents, document areas, categories, and file uploads in K-Systems.

## Base URL

```
/backend/document/
```

## Authentication

Requires `READ_DOCUMENT` permission for viewing, `WRITE_DOCUMENT` for creating/editing.

## List Documents

**GET** `/document/?action=getDocuments`

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| area_id | integer | Filter by document area |
| category | string | Filter by category |
| search | string | Search title/description |
| page | integer | Page number |
| limit | integer | Items per page |

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 101,
      "title": "Safety Policy Manual",
      "description": "Company safety policies",
      "area_id": 1,
      "area_name": "Policies",
      "category": "Safety",
      "file_name": "safety_policy.pdf",
      "file_size": 2048000,
      "file_url": "/uploads/documents/101/safety_policy.pdf",
      "created_by": "John Smith",
      "created_at": "2024-03-01T10:00:00Z",
      "updated_at": "2024-03-15T14:00:00Z"
    }
  ]
}
```

## Get Single Document

**GET** `/document/?action=getDocument&id={id}

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 101,
    "title": "Safety Policy Manual",
    "description": "Comprehensive safety policies...",
    "area_id": 1,
    "area_name": "Policies",
    "category": "Safety",
    "file_name": "safety_policy.pdf",
    "file_size": 2048000,
    "file_url": "/uploads/documents/101/safety_policy.pdf",
    "mime_type": "application/pdf",
    "version": 2,
    "created_by": "John Smith",
    "created_by_id": 123,
    "created_at": "2024-03-01T10:00:00Z",
    "updated_at": "2024-03-15T14:00:00Z",
    "download_count": 45
  }
}
```

## Create Document

**POST** `/document/?action=createDocument`

**Content-Type:** `multipart/form-data`

**Form Fields:**
- `title` (required): Document title
- `description`: Document description
- `area_id` (required): Document area ID
- `category`: Category name
- `file` (required): File to upload

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Document created successfully",
  "data": {
    "id": 102,
    "file_url": "/uploads/documents/102/document.pdf"
  }
}
```

## Update Document

**PUT** `/document/?action=updateDocument`

**Request Body:**
```json
{
  "id": 101,
  "title": "Updated Title",
  "description": "Updated description",
  "category": "Safety"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Document updated successfully"
}
```

## Delete Document

**DELETE** `/document/?action=deleteDocument&id={id}

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Document deleted successfully"
}
```

## Download Document

**GET** `/document/?action=download&id={id}

Downloads the document file.

**Response:** File stream with appropriate headers

## Document Areas

**GET** `/document/?action=getAreas`

Get all document areas with permission filtering.

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Policies",
      "description": "Company policies and procedures",
      "icon": "mdi-file-document",
      "color": "#2196F3",
      "required_permission": "READ_POLICY",
      "document_count": 25
    },
    {
      "id": 2,
      "name": "Forms",
      "description": "Fillable forms",
      "icon": "mdi-form-select",
      "color": "#4CAF50",
      "required_permission": null,
      "document_count": 15
    }
  ]
}
```

**POST** `/admin/documentarea/`

Create new document area (admin only).

**Request Body:**
```json
{
  "name": "Training Materials",
  "description": "Training documents and guides",
  "icon": "mdi-school",
  "color": "#FF9800",
  "required_permission": "READ_TRAINING"
}
```

## Categories

**GET** `/document/?action=getCategories`

**Response:**
```json
{
  "success": true,
  "data": ["Safety", "HR", "Operations", "Training"]
}
```

---

::: tip File Size Limit
Maximum file size: 50MB. Larger files should be split or hosted externally.
:::

::: warning Permissions
Document areas can require specific permissions. Users without required permission cannot see those areas.
:::
