# WebSocket API

Real-time communication via Socket.io for notifications, chat, status updates, and collaborative features.

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

Namespace for system notifications and toasts.

**Connect:**
```javascript
const notificationSocket = io('/notification', {
  auth: { token: 'YOUR_JWT_TOKEN' }
});
```

**Events:**

#### notification:new
Receive new system notification.

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
Receive toast message (temporary popup).

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

Namespace for chat and messaging.

**Connect:**
```javascript
const chatSocket = io('/chat', {
  auth: { token: 'YOUR_JWT_TOKEN' }
});
```

**Events:**

#### chat:message
Receive new chat message.

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
User is typing indicator.

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

**Emit typing status:**
```javascript
chatSocket.emit('chat:typing', {
  conversation_id: "conv_abc123",
  is_typing: true
});
```

## Status Namespace

Namespace for user status updates.

**Connect:**
```javascript
const statusSocket = io('/status', {
  auth: { token: 'YOUR_JWT_TOKEN' }
});
```

**Events:**

#### status:update
User status changed (online, away, offline).

```javascript
statusSocket.on('status:update', (data) => {
  // data = {
  //   user_id: 123,
  //   status: "online",
  //   last_seen: "2024-03-15T14:00:00Z"
  // }
});
```

**Set your status:**
```javascript
statusSocket.emit('status:set', {
  status: "away"
});
```

## Whiteboard Namespace

Namespace for collaborative whiteboard.

**Connect:**
```javascript
const whiteboardSocket = io('/whiteboard', {
  auth: { token: 'YOUR_JWT_TOKEN' }
});
```

**Events:**

#### whiteboard:draw
Drawing action on whiteboard.

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

**Emit draw action:**
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

Socket server also provides HTTP endpoints for admin operations.

**POST** `/api/notification/toast`

Send toast notification to specific user(s).

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

Send persistent notification.

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

Broadcast message to all connected users.

**Request Body:**
```json
{
  "message": "System will restart in 10 minutes",
  "type": "warning"
}
```

**GET** `/health`

Health check endpoint.

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
1. Connect once on app initialization
2. Reuse socket instance across app
3. Disconnect on logout
4. Handle reconnection gracefully

**Event Handlers:**
1. Add listeners once
2. Remove listeners on component unmount
3. Don't add listeners in loops
4. Use namespaces to organize events

**Performance:**
1. Throttle emit events (typing indicator)
2. Batch notifications when possible
3. Disconnect unused namespaces
4. Monitor connection count

**Security:**
1. Always authenticate with JWT
2. Validate events server-side
3. Never trust client data
4. Use HTTPS/WSS in production

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

**Per Connection:**
- 100 events per minute
- 1000 events per hour
- Exceeding limits disconnects socket

**Global:**
- 10,000 concurrent connections
- Auto-scaling based on load

---

> **Tip: Connection Persistence** - Socket connections are automatically maintained. If disconnected, auto-reconnect occurs with exponential backoff.

> **Warning: Production Use** - Always use WSS (WebSocket Secure) in production. Never send tokens over unencrypted connections.
