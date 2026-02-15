# Kalender-API

API für die Verwaltung von Kalenderereignissen, wiederkehrenden Ereignissen, Kalendergruppen und RSVPs.

## Basis-URL

```
/backend/calendar/
```

## Authentifizierung

Erfordert die Berechtigung `READ_CALENDAR` zum Anzeigen, `WRITE_CALENDAR` zum Erstellen/Bearbeiten.

## Ereignisse auflisten

**GET** `/calendar/?action=getEvents`

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|------|-------------|
| start_date | string | Startdatum (YYYY-MM-DD) |
| end_date | string | Enddatum (YYYY-MM-DD) |
| group_id | integer | Nach Kalendergruppe filtern |
| user_id | integer | Nach zugewiesenem Benutzer filtern |

**Antwort (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 301,
      "title": "Team Meeting",
      "description": "Monthly team sync",
      "start_date": "2024-03-20T10:00:00Z",
      "end_date": "2024-03-20T11:00:00Z",
      "location": "Conference Room A",
      "group_id": 1,
      "group_name": "Operations",
      "group_color": "#2196F3",
      "is_recurring": false,
      "assigned_users": [
        {"id": 123, "name": "John Smith"},
        {"id": 124, "name": "Jane Doe"}
      ],
      "created_by": "Admin",
      "created_at": "2024-03-15T10:00:00Z"
    }
  ]
}
```

## Einzelnes Ereignis abrufen

**GET** `/calendar/?action=getEvent&id={id}`

**Antwort (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 301,
    "title": "Team Meeting",
    "description": "Monthly team sync meeting to discuss progress",
    "start_date": "2024-03-20T10:00:00Z",
    "end_date": "2024-03-20T11:00:00Z",
    "location": "Conference Room A",
    "group_id": 1,
    "is_recurring": false,
    "recurrence_pattern": null,
    "assigned_users": [
      {
        "id": 123,
        "name": "John Smith",
        "rsvp_status": "accepted"
      }
    ],
    "reminders": [
      {"type": "email", "minutes_before": 30}
    ]
  }
}
```

## Ereignis erstellen

**POST** `/calendar/?action=createEvent`

**Request Body:**
```json
{
  "title": "Team Meeting",
  "description": "Monthly sync",
  "start_date": "2024-03-20T10:00:00Z",
  "end_date": "2024-03-20T11:00:00Z",
  "location": "Conference Room A",
  "group_id": 1,
  "assigned_user_ids": [123, 124, 125],
  "is_recurring": false,
  "send_notifications": true
}
```

**Antwort (201 Created):**
```json
{
  "success": true,
  "message": "Event created successfully",
  "data": {"id": 302}
}
```

## Ereignis aktualisieren

**PUT** `/calendar/?action=updateEvent`

**Request Body:**
```json
{
  "id": 301,
  "title": "Updated Title",
  "start_date": "2024-03-20T11:00:00Z"
}
```

**Antwort (200 OK):**
```json
{
  "success": true,
  "message": "Event updated successfully"
}
```

## Ereignis löschen

**DELETE** `/calendar/?action=deleteEvent&id={id}`

**Antwort (200 OK):**
```json
{
  "success": true,
  "message": "Event deleted successfully"
}
```

## RSVP

**POST** `/calendar/?action=rsvp`

**Request Body:**
```json
{
  "event_id": 301,
  "status": "accepted"
}
```

**Statuswerte:** accepted, declined, tentative

**Antwort (200 OK):**
```json
{
  "success": true,
  "message": "RSVP updated successfully"
}
```

## Kalendergruppen

**GET** `/calendar/?action=getGroups`

**Antwort (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Operations",
      "color": "#2196F3",
      "description": "Operational events",
      "event_count": 45
    }
  ]
}
```

---

::: tip Wiederkehrende Ereignisse
Wiederkehrende Ereignisse generieren einzelne Instanzen. Das Aktualisieren einer Instanz erstellt eine Ausnahme, das Aktualisieren der Serie aktualisiert alle zukünftigen Instanzen.
:::
