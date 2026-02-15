# Message API

API for the messaging system including private messages, group chats, and real-time message delivery.

## Base URL

```
/backend/message/
```

## Authentication

Requires `READ_MESSAGE` permission for viewing, `WRITE_MESSAGE` for sending.

## List Messages

**GET** `/message/?action=getMessages`

Get message inbox or folder.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| folder | string | inbox, sent, archive, trash (default: inbox) |
| page | integer | Page number |
| limit | integer | Items per page |

**Response (200 OK):**
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

## Get Conversation

**GET** `/message/?action=getConversation&conversation_id={id}`

Get all messages in a conversation.

**Response (200 OK):**
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

## Send Message

**POST** `/message/?action=sendMessage`

Send new message or reply to conversation.

**Request Body:**
```json
{
  "to_user_ids": [124, 125],
  "subject": "Meeting Tomorrow",
  "content": "Let's meet at 10 AM tomorrow.",
  "conversation_id": null
}
```

**Fields:**
- `to_user_ids`: Array of recipient user IDs (for new conversation)
- `conversation_id`: Existing conversation ID (for reply)
- `subject`: Subject line (optional for replies)
- `content`: Message content (required)

**Response (201 Created):**
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

**Real-time Notification:** Recipients receive socket.io event:
```javascript
socket.on('message:new', (message) => {
  // Handle new message notification
});
```

## Mark as Read

**PUT** `/message/?action=markRead`

Mark message(s) as read.

**Request Body:**
```json
{
  "message_ids": [501, 502, 503]
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Messages marked as read"
}
```

## Delete Message

**DELETE** `/message/?action=deleteMessage&id={id}`

Move message to trash or permanently delete.

**Query Parameters:**
- `permanent=true`: Permanently delete (otherwise moves to trash)

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Message deleted successfully"
}
```

## Create Group

**POST** `/message/?action=createGroup`

Create group conversation.

**Request Body:**
```json
{
  "name": "Project Team",
  "member_ids": [123, 124, 125, 126]
}
```

**Response (201 Created):**
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

## Upload Attachment

**POST** `/message/?action=uploadAttachment`

Upload file attachment for message.

**Form Data:**
- `file`: File to upload (max 25MB)

**Response (200 OK):**
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

## Get Unread Count

**GET** `/message/?action=getUnreadCount`

**Response (200 OK):**
```json
{
  "success": true,
  "unread_count": 5
}
```

---

> **Tip: Real-time Messages** - Messages are delivered in real-time via WebSocket. Use [[WebSocket-API]] for instant message notifications.

> **Info: Message Search** - Use global search API to search message content and participants.
