# Socket.io-Server

Echtzeit-Kommunikationsserver mit Socket.io für Chat, Benachrichtigungen und Statusaktualisierungen.

## Funktionen

- **Authentifizierung**: JWT-basierte Authentifizierung für sichere Verbindungen
- **Chat-Funktionen**: Direkte Nachrichten und Gruppenchats
- **Benachrichtigungen**: Benutzerspezifische und Broadcast-Benachrichtigungen
- **Statusverwaltung**: Benutzerstatus und Systemstatus in Echtzeit

## Installation

1. Repository klonen
2. Abhängigkeiten installieren:
   ```
   npm install
   ```
3. Konfigurationsdatei erstellen:
   ```
   cp .env.example .env
   ```
4. `.env`-Datei bearbeiten und die erforderlichen Werte anpassen
5. Server starten:
   ```
   npm start
   ```

## Entwicklung

Für die Entwicklung mit automatischem Neuladen bei Änderungen:

```
npm run dev
```

## Namespaces und Events

### Hauptnamespace

- `connection` - Verbindung hergestellt
- `disconnect` - Verbindung getrennt

### Chat-Namespace `/chat`

- `message:direct` - Direktnachricht senden
- `message:room` - Nachricht an einen Raum senden
- `room:join` - Einem Raum beitreten
- `room:leave` - Einen Raum verlassen
- `typing:start` - Schreibvorgang beginnen
- `typing:stop` - Schreibvorgang beenden

### Benachrichtigungs-Namespace `/notification`

- `notify:user` - Benachrichtigung an einen bestimmten Benutzer senden
- `notify:broadcast` - Benachrichtigung an alle Benutzer senden
- `notify:role` - Benachrichtigung an Benutzer mit einer bestimmten Rolle senden

### Status-Namespace `/status`

- `status:update` - Benutzerstatus aktualisieren
- `status:get_active_users` - Liste aktiver Benutzer abrufen
- `status:activity` - Benutzeraktivität melden
- `status:system` - Systemstatus abrufen

## JWT-Authentifizierung

Der Server erwartet ein JWT-Token entweder:
- Im Authorization-Header: `Authorization: Bearer <token>`
- Als Query-Parameter: `?token=<token>`

Das Token muss mit dem in der `.env`-Datei konfigurierten Secret signiert sein.

## Beispiel für die Client-Integration

```javascript
// Socket.io-Client initialisieren
const socket = io('http://localhost:3001', {
  auth: {
    token: 'your-jwt-token'
  }
});

// Verbindung zum Chat-Namespace
const chatSocket = io('http://localhost:3001/chat', {
  auth: {
    token: 'your-jwt-token'
  }
});

// Verbindung zum Benachrichtigungs-Namespace
const notifySocket = io('http://localhost:3001/notification', {
  auth: {
    token: 'your-jwt-token'
  }
});

// Verbindung zum Status-Namespace
const statusSocket = io('http://localhost:3001/status', {
  auth: {
    token: 'your-jwt-token'
  }
});

// Events
socket.on('connection:established', (data) => {
  console.log('Verbindung hergestellt:', data);
});

chatSocket.on('message:received', (message) => {
  console.log('Neue Nachricht:', message);
});
``` 