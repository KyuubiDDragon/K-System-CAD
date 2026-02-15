# Bericht-API

Vollständige API-Dokumentation für das K-Systems Berichtsverwaltungssystem einschließlich benutzerdefinierter Felder, Kategorien, Status-Workflow und Dateianhängen.

## Basis-URL

```
/backend/report/
```

## Authentifizierung

Erfordert die Berechtigung `READ_REPORT` zum Anzeigen, `WRITE_REPORT` zum Erstellen/Bearbeiten, `DELETE_REPORT` zum Löschen.

## Berichte auflisten

**GET** `/report/`

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|------|-------------|
| page | integer | Seitennummer (Standard: 1) |
| limit | integer | Einträge pro Seite (Standard: 25) |
| search | string | Suche in Titel, Inhalt, ID |
| status | string | Nach Status filtern |
| category | string | Nach Kategorie filtern |
| date_from | string | Startdatum (YYYY-MM-DD) |
| date_to | string | Enddatum (YYYY-MM-DD) |
| reporter_id | integer | Nach Berichterstatter filtern |

**Antwort (200 OK):**
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

## Einzelnen Bericht abrufen

**GET** `/report/`?id={id}

**Antwort (200 OK):**
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

## Bericht erstellen

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

**Antwort (201 Created):**
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

## Bericht aktualisieren

**PUT** `/report/`?id={id}

**Request Body:** (nur zu aktualisierende Felder einbeziehen)
```json
{
  "title": "Updated Title",
  "status": "completed",
  "custom_fields": {
    "severity": "Medium"
  }
}
```

**Antwort (200 OK):**
```json
{
  "success": true,
  "message": "Report updated successfully"
}
```

## Bericht löschen

**DELETE** `/report/`?id={id}

**Antwort (200 OK):**
```json
{
  "success": true,
  "message": "Report deleted successfully"
}
```

## Bericht teilen

**POST** `/report/`share.php

Teilbaren Link mit Ablaufdatum erstellen.

**Request Body:**
```json
{
  "report_id": 456,
  "expires_days": 7
}
```

**Antwort (200 OK):**
```json
{
  "success": true,
  "share_url": "https://your-domain.com/share/abc123def456",
  "expires_at": "2024-03-22T10:00:00Z"
}
```

## Anhang hochladen

**POST** `/report/`?action=uploadAttachment&id={id}

**Form Data:**
- `file`: Hochzuladende Datei

**Antwort (200 OK):**
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

## Berichts-Kategorien

**GET** `/reportcategory/`

**Antwort:**
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

## Berichts-Status

**GET** `/reportstatus/`

**Antwort:**
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

## Benutzerdefinierte Felder

**GET** `/admin/reportfields/`

Benutzerdefinierte Felddefinitionen für Berichte abrufen.

**Antwort:**
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

::: tip Berichtsnummern
Berichtsnummern werden automatisch im Format RPT-YYYY-NNN generiert. Können nicht manuell gesetzt werden.
:::
