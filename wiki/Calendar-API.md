# Calendar API

API for managing calendar events, recurring events, calendar groups, and RSVPs.

## Base URL

```
/backend/calendar/
```

## Authentication

Requires `READ_CALENDAR` permission for viewing, `WRITE_CALENDAR` for creating/editing.

## List Events

**GET** `/calendar/?action=getEvents`

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| start_date | string | Start date (YYYY-MM-DD) |
| end_date | string | End date (YYYY-MM-DD) |
| group_id | integer | Filter by calendar group |
| user_id | integer | Filter by assigned user |

**Response (200 OK):**
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

## Get Single Event

**GET** `/calendar/?action=getEvent&id={id}`

**Response (200 OK):**
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

## Create Event

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

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Event created successfully",
  "data": {"id": 302}
}
```

## Update Event

**PUT** `/calendar/?action=updateEvent`

**Request Body:**
```json
{
  "id": 301,
  "title": "Updated Title",
  "start_date": "2024-03-20T11:00:00Z"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Event updated successfully"
}
```

## Delete Event

**DELETE** `/calendar/?action=deleteEvent&id={id}`

**Response (200 OK):**
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

**Status values:** accepted, declined, tentative

**Response (200 OK):**
```json
{
  "success": true,
  "message": "RSVP updated successfully"
}
```

## Calendar Groups

**GET** `/calendar/?action=getGroups`

**Response (200 OK):**
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

> **Tip: Recurring Events** - Recurring events generate individual instances. Updating one instance creates exception, updating series updates all future instances.
