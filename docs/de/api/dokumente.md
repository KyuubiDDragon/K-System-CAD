# Dokument-API

API für die Verwaltung von Dokumenten, Dokumentbereichen, Kategorien und Datei-Uploads in K-Systems.

## Basis-URL

```
/backend/document/
```

## Authentifizierung

Erfordert die Berechtigung `READ_DOCUMENT` zum Anzeigen, `WRITE_DOCUMENT` zum Erstellen/Bearbeiten.

## Dokumente auflisten

**GET** `/document/?action=getDocuments`

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|------|-------------|
| area_id | integer | Nach Dokumentbereich filtern |
| category | string | Nach Kategorie filtern |
| search | string | Suche in Titel/Beschreibung |
| page | integer | Seitennummer |
| limit | integer | Einträge pro Seite |

**Antwort (200 OK):**
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

## Einzelnes Dokument abrufen

**GET** `/document/?action=getDocument&id={id}

**Antwort (200 OK):**
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

## Dokument erstellen

**POST** `/document/?action=createDocument`

**Content-Type:** `multipart/form-data`

**Formularfelder:**
- `title` (erforderlich): Dokumenttitel
- `description`: Dokumentbeschreibung
- `area_id` (erforderlich): Dokumentbereichs-ID
- `category`: Kategoriename
- `file` (erforderlich): Hochzuladende Datei

**Antwort (201 Created):**
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

## Dokument aktualisieren

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

**Antwort (200 OK):**
```json
{
  "success": true,
  "message": "Document updated successfully"
}
```

## Dokument löschen

**DELETE** `/document/?action=deleteDocument&id={id}

**Antwort (200 OK):**
```json
{
  "success": true,
  "message": "Document deleted successfully"
}
```

## Dokument herunterladen

**GET** `/document/?action=download&id={id}

Lädt die Dokumentdatei herunter.

**Antwort:** Datei-Stream mit entsprechenden Headern

## Dokument-Bereiche

**GET** `/document/?action=getAreas`

Alle Dokumentbereiche mit Berechtigungsfilterung abrufen.

**Antwort (200 OK):**
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

Neuen Dokumentbereich erstellen (nur Admin).

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

## Kategorien

**GET** `/document/?action=getCategories`

**Antwort:**
```json
{
  "success": true,
  "data": ["Safety", "HR", "Operations", "Training"]
}
```

---

::: tip Dateigrößen-Limit
Maximale Dateigröße: 50MB. Größere Dateien sollten aufgeteilt oder extern gehostet werden.
:::

::: warning Berechtigungen
Dokumentbereiche können spezifische Berechtigungen erfordern. Benutzer ohne erforderliche Berechtigung können diese Bereiche nicht sehen.
:::
