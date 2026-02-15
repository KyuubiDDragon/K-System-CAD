# WebSocket API

Echtzeit-Kommunikation via Socket.io für Benachrichtigungen, Chat, Statusaktualisierungen und kollaborative Funktionen.

## Connection

## Socket Server URL

```
ws://localhost:3001 (development)
wss://your-domain.com (production)
```

## Connect with Authentication

```javascript
import { io } from 'socket.io-client';

const socket = io('http://localhost:3001', {
  auth: {
    token: 'YOUR_JWT_TOKEN'
  }
});

socket.on('connect', () => {
  console.log('Connected:', socket.id);
});

socket.on('error', (error) => {
  console.error('Socket error:', error);
});
```

## Namespaces

## Notification Namespace

Namespace für Systembenachrichtigungen und Toasts.

**Connect:**
```javascript
const notificationSocket = io('/notification', {
  auth: { token: 'YOUR_JWT_TOKEN' }
});
```

**Events:**

#### notification:new
Neue Systembenachrichtigung empfangen.

```javascript
notificationSocket.on('notification:new', (data) => {
  // data = {
  //   id: 123,
  //   title: "New Report",
  //   message: "Report #456 was created",
  //   type: "info",
  //   link: "/report/456",
  //   created_at: "2024-03-15T14:00:00Z"
  // }
});
```

#### notification:toast
Toast-Nachricht empfangen (temporäres Popup).

```javascript
notificationSocket.on('notification:toast', (data) => {
  // data = {
  //   message: "Employee updated successfully",
  //   type: "success",
  //   duration: 3000
  // }
});
```

## Chat Namespace

Namespace für Chat und Messaging.

**Connect:**
```javascript
const chatSocket = io('/chat', {
  auth: { token: 'YOUR_JWT_TOKEN' }
});
```

**Events:**

#### chat:message
Neue Chat-Nachricht empfangen.

```javascript
chatSocket.on('chat:message', (message) => {
  // message = {
  //   id: 501,
  //   conversation_id: "conv_abc123",
  //   from_user_id: 123,
  //   from_user_name: "John Smith",
  //   content: "Hello!",
  //   created_at: "2024-03-15T14:00:00Z"
  // }
});
```

#### chat:typing
Benutzer tippt Indikator.

```javascript
chatSocket.on('chat:typing', (data) => {
  // data = {
  //   conversation_id: "conv_abc123",
  //   user_id: 123,
  //   user_name: "John Smith",
  //   is_typing: true
  // }
});
```

**Tipp-Status senden:**
```javascript
chatSocket.emit('chat:typing', {
  conversation_id: "conv_abc123",
  is_typing: true
});
```

## Status Namespace

Namespace für Benutzerstatusaktualisierungen.

**Connect:**
```javascript
const statusSocket = io('/status', {
  auth: { token: 'YOUR_JWT_TOKEN' }
});
```

**Events:**

#### status:update
Benutzerstatus geändert (online, away, offline).

```javascript
statusSocket.on('status:update', (data) => {
  // data = {
  //   user_id: 123,
  //   status: "online",
  //   last_seen: "2024-03-15T14:00:00Z"
  // }
});
```

**Ihren Status setzen:**
```javascript
statusSocket.emit('status:set', {
  status: "away"
});
```

## Whiteboard Namespace

Namespace für kollaboratives Whiteboard.

**Connect:**
```javascript
const whiteboardSocket = io('/whiteboard', {
  auth: { token: 'YOUR_JWT_TOKEN' }
});
```

**Events:**

#### whiteboard:draw
Zeichenaktion auf Whiteboard.

```javascript
whiteboardSocket.on('whiteboard:draw', (data) => {
  // data = {
  //   whiteboard_id: 1,
  //   user_id: 123,
  //   action: "line",
  //   points: [[100, 100], [150, 150]],
  //   color: "#000000",
  //   width: 2
  // }
});
```

**Zeichenaktion senden:**
```javascript
whiteboardSocket.emit('whiteboard:draw', {
  whiteboard_id: 1,
  action: "line",
  points: [[100, 100], [150, 150]],
  color: "#FF0000",
  width: 3
});
```

## HTTP Endpoints

Socket-Server bietet auch HTTP-Endpunkte für Admin-Operationen.

**POST** `/api/notification/toast`

Toast-Benachrichtigung an bestimmte Benutzer senden.

**Headers:**
```
X-API-Key: YOUR_SOCKET_API_KEY
Content-Type: application/json
```

**Request Body:**
```json
{
  "user_ids": [123, 124],
  "message": "System maintenance in 5 minutes",
  "type": "warning",
  "duration": 5000
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "sent_to": 2
}
```

**POST** `/api/notification`

Permanente Benachrichtigung senden.

**Request Body:**
```json
{
  "user_ids": [123],
  "title": "Report Approved",
  "message": "Your report #456 has been approved",
  "type": "success",
  "link": "/report/456"
}
```

**POST** `/api/broadcast`

Nachricht an alle verbundenen Benutzer senden.

**Request Body:**
```json
{
  "message": "System will restart in 10 minutes",
  "type": "warning"
}
```

**GET** `/health`

Health-Check-Endpunkt.

**Response (200 OK):**
```json
{
  "status": "healthy",
  "uptime": 86400,
  "connections": 45,
  "memory_usage": 125829120
}
```

## Error Handling

## Connection Errors

```javascript
socket.on('connect_error', (error) => {
  console.error('Connection failed:', error.message);
  // Retry connection or redirect to login
});
```

## Authentication Errors

```javascript
socket.on('error', (error) => {
  if (error.message === 'Authentication failed') {
    // Redirect to login
  }
});
```

## Reconnection

```javascript
socket.on('reconnect', (attemptNumber) => {
  console.log('Reconnected after', attemptNumber, 'attempts');
});

socket.on('reconnect_failed', () => {
  console.error('Reconnection failed');
  // Show offline message to user
});
```

## Best Practices

**Connection Management:**
1. Verbinden Sie einmal bei App-Initialisierung
2. Verwenden Sie Socket-Instanz in der gesamten App wieder
3. Trennen Sie bei Abmeldung
4. Behandeln Sie Wiederverbindung elegant

**Event Handlers:**
1. Fügen Sie Listener einmal hinzu
2. Entfernen Sie Listener beim Component-Unmount
3. Fügen Sie keine Listener in Schleifen hinzu
4. Verwenden Sie Namespaces zur Organisation von Events

**Performance:**
1. Drosseln Sie Emit-Events (Tipp-Indikator)
2. Bündeln Sie Benachrichtigungen wenn möglich
3. Trennen Sie ungenutzte Namespaces
4. Überwachen Sie Verbindungsanzahl

**Security:**
1. Authentifizieren Sie immer mit JWT
2. Validieren Sie Events serverseitig
3. Vertrauen Sie niemals Client-Daten
4. Verwenden Sie HTTPS/WSS in Produktion

## Example: Vue 3 Integration

```typescript
// socket.service.ts
import { io, Socket } from 'socket.io-client';

class SocketService {
  private socket: Socket | null = null;
  private notificationSocket: Socket | null = null;
  private chatSocket: Socket | null = null;

  connect(token: string) {
    this.socket = io(import.meta.env.VITE_SOCKET_URL, {
      auth: { token }
    });

    this.notificationSocket = io(`${import.meta.env.VITE_SOCKET_URL}/notification`, {
      auth: { token }
    });

    this.chatSocket = io(`${import.meta.env.VITE_SOCKET_URL}/chat`, {
      auth: { token }
    });

    this.setupListeners();
  }

  private setupListeners() {
    this.notificationSocket?.on('notification:new', (data) => {
      // Handle notification
      this.handleNotification(data);
    });

    this.notificationSocket?.on('notification:toast', (data) => {
      // Show toast
      this.showToast(data);
    });

    this.chatSocket?.on('chat:message', (message) => {
      // Handle new message
      this.handleNewMessage(message);
    });
  }

  disconnect() {
    this.socket?.disconnect();
    this.notificationSocket?.disconnect();
    this.chatSocket?.disconnect();
  }

  sendMessage(conversationId: string, content: string) {
    this.chatSocket?.emit('chat:send', {
      conversation_id: conversationId,
      content
    });
  }
}

export const socketService = new SocketService();
```

## Rate Limiting

**Pro Verbindung:**
- 100 Events pro Minute
- 1000 Events pro Stunde
- Überschreiten der Limits trennt Socket

**Global:**
- 10.000 gleichzeitige Verbindungen
- Auto-Skalierung basierend auf Last

---

::: tip Connection Persistence
Socket-Verbindungen werden automatisch aufrechterhalten. Bei Trennung erfolgt automatische Wiederverbindung mit exponentiellem Backoff.
:::

::: warning Production Use
Verwenden Sie immer WSS (WebSocket Secure) in Produktion. Senden Sie niemals Tokens über unverschlüsselte Verbindungen.
:::
