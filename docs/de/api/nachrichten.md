# Nachrichten-API

API für das Nachrichtensystem einschließlich privater Nachrichten, Gruppenchats und Echtzeit-Nachrichtenübermittlung.

## Basis-URL

```
/backend/message/
```

## Authentifizierung

Erfordert die Berechtigung `READ_MESSAGE` zum Anzeigen, `WRITE_MESSAGE` zum Senden.

## Nachrichten auflisten

**GET** `/message/?action=getMessages`

Posteingang oder Ordner mit Nachrichten abrufen.

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|------|-------------|
| folder | string | inbox, sent, archive, trash (Standard: inbox) |
| page | integer | Seitennummer |
| limit | integer | Einträge pro Seite |

**Antwort (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 501,
      "conversation_id": "conv_abc123",
      "from_user_id": 123,
      "from_user_name": "John Smith",
      "from_user_avatar": "/uploads/avatars/123.jpg",
      "subject": "Meeting Tomorrow",
      "preview": "Let's meet at 10 AM...",
      "is_read": false,
      "is_group": false,
      "participant_count": 2,
      "created_at": "2024-03-15T14:00:00Z"
    }
  ]
}
```

## Konversation abrufen

**GET** `/message/?action=getConversation&conversation_id={id}

Alle Nachrichten einer Konversation abrufen.

**Antwort (200 OK):**
```json
{
  "success": true,
  "data": {
    "conversation_id": "conv_abc123",
    "participants": [
      {"id": 123, "name": "John Smith", "avatar": "/uploads/avatars/123.jpg"},
      {"id": 124, "name": "Jane Doe", "avatar": "/uploads/avatars/124.jpg"}
    ],
    "messages": [
      {
        "id": 501,
        "from_user_id": 123,
        "from_user_name": "John Smith",
        "content": "Let's meet at 10 AM tomorrow.",
        "created_at": "2024-03-15T14:00:00Z",
        "is_read": true,
        "attachments": []
      },
      {
        "id": 502,
        "from_user_id": 124,
        "from_user_name": "Jane Doe",
        "content": "Sounds good, see you then!",
        "created_at": "2024-03-15T14:05:00Z",
        "is_read": true,
        "attachments": []
      }
    ]
  }
}
```

## Nachricht senden

**POST** `/message/?action=sendMessage`

Neue Nachricht senden oder auf Konversation antworten.

**Request Body:**
```json
{
  "to_user_ids": [124, 125],
  "subject": "Meeting Tomorrow",
  "content": "Let's meet at 10 AM tomorrow.",
  "conversation_id": null
}
```

**Felder:**
- `to_user_ids`: Array von Empfänger-Benutzer-IDs (für neue Konversation)
- `conversation_id`: Bestehende Konversations-ID (für Antwort)
- `subject`: Betreffzeile (optional für Antworten)
- `content`: Nachrichteninhalt (erforderlich)

**Antwort (201 Created):**
```json
{
  "success": true,
  "message": "Message sent successfully",
  "data": {
    "id": 503,
    "conversation_id": "conv_abc123"
  }
}
```

**Echtzeit-Benachrichtigung:** Empfänger erhalten socket.io Event:
```javascript
socket.on('message:new', (message) => {
  // Neue Nachrichtenbenachrichtigung verarbeiten
});
```

## Als gelesen markieren

**PUT** `/message/?action=markRead`

Nachricht(en) als gelesen markieren.

**Request Body:**
```json
{
  "message_ids": [501, 502, 503]
}
```

**Antwort (200 OK):**
```json
{
  "success": true,
  "message": "Messages marked as read"
}
```

## Nachricht löschen

**DELETE** `/message/?action=deleteMessage&id={id}

Nachricht in Papierkorb verschieben oder dauerhaft löschen.

**Query-Parameter:**
- `permanent=true`: Dauerhaft löschen (andernfalls in Papierkorb verschieben)

**Antwort (200 OK):**
```json
{
  "success": true,
  "message": "Message deleted successfully"
}
```

## Gruppe erstellen

**POST** `/message/?action=createGroup`

Gruppenkonversation erstellen.

**Request Body:**
```json
{
  "name": "Project Team",
  "member_ids": [123, 124, 125, 126]
}
```

**Antwort (201 Created):**
```json
{
  "success": true,
  "data": {
    "conversation_id": "conv_group_xyz789",
    "name": "Project Team",
    "member_count": 4
  }
}
```

## Anhang hochladen

**POST** `/message/?action=uploadAttachment`

Dateianhang für Nachricht hochladen.

**Form Data:**
- `file`: Hochzuladende Datei (max 25MB)

**Antwort (200 OK):**
```json
{
  "success": true,
  "data": {
    "attachment_id": "att_abc123",
    "filename": "document.pdf",
    "size": 1024000,
    "url": "/uploads/messages/attachments/att_abc123.pdf"
  }
}
```

## Anzahl ungelesener Nachrichten abrufen

**GET** `/message/?action=getUnreadCount`

**Antwort (200 OK):**
```json
{
  "success": true,
  "unread_count": 5
}
```

---

::: tip Echtzeit-Nachrichten
Nachrichten werden in Echtzeit über WebSocket übermittelt. Verwenden Sie die Socket API für sofortige Nachrichtenbenachrichtigungen.
:::

::: info Nachrichten-Suche
Verwenden Sie die globale Such-API, um Nachrichteninhalte und Teilnehmer zu durchsuchen.
:::
