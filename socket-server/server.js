/**
 * Socket.io Server für Echtzeit-Kommunikation
 * Dieser Server fungiert als WebSocket-Schnittstelle zwischen Frontend und Backend
 */

require('dotenv').config();
const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');
const logger = require('./utils/logger');
const authenticate = require('./middleware/auth');
const registerModules = require('./modules');
const { createLogger, format, transports } = require('winston');
const { combine, timestamp, printf, colorize } = format;

// Konfiguration
const PORT = process.env.PORT || 3001;
const NODE_ENV = process.env.NODE_ENV || 'development';

// Express App einrichten
const app = express();
app.use(cors());
app.use(express.json()); // Body-Parser für JSON-Anfragen

// Basic Route für Healthcheck
app.get('/', (req, res) => {
  res.send('Socket.io-Server läuft');
});

// HTTP Server erstellen
const server = http.createServer(app);

// Socket.io Server initialisieren
const io = new Server(server, {
  cors: {
    origin: process.env.CORS_ORIGIN ? [process.env.CORS_ORIGIN] : [
      'http://localhost:5173',
      'http://localhost:8000',
      'http://localhost:8080',
      'http://localhost:3000'
    ],
    methods: ['GET', 'POST'],
    credentials: true
  },
  pingTimeout: 60000, // 60 Sekunden
  // Verbesserte Logging-Optionen
  connectTimeout: 45000,
  // Polling-Transport-Optimierungen
  transports: ['websocket', 'polling'],
  allowUpgrades: true,
  upgrade: true,
  // Weitere Socket.io-Optionen für Stabilität
  maxHttpBufferSize: 5e6 // 5MB
});

// Middleware für Authentifizierung verwenden
io.use(authenticate);

// Verbindungsereignisse registrieren
io.on('connection', (socket) => {
  const userId = socket.user?.id || 'unknown';
  
  // Log when the socket connects and add to user room
  logger.info(`Neue Verbindung: ${userId} (${socket.id})`);
  
  // Socket zur Benutzer-Raum hinzufügen
  if (socket.user?.id) {
    const userRoom = `user:${socket.user.id}`;
    
    socket.join(userRoom);
    logger.info(`User ${userId} added to room ${userRoom}`);
    
    // Send a direct welcome message to confirm user room membership
    socket.emit('user:authenticated', {
      userId: socket.user.id,
      socketId: socket.id,
      room: userRoom,
      timestamp: new Date().toISOString()
    });
  } else {
    logger.warn(`⚠️ Connection without user ID: Socket ${socket.id}`);
  }
  
  // Verbindungsereignis senden
  socket.emit('connection:established', {
    socketId: socket.id,
    message: 'Verbindung hergestellt'
  });
  
  // Special debug echo handler for notification testing
  socket.on('notification:echo', (data) => {
    const socketId = socket.id;
    const userId = socket.user?.id || 'nicht authentifiziert';
    
    logger.info(`📢 Notification echo request from user ${userId} (Socket ${socketId})`);
    
    // Echo the notification back to the sender with a unique ID to prevent duplicates
    const echoData = {
      ...data,
      title: data.title || 'Echo Notification',
      message: data.message || 'This is an echo of your notification request',
      echo: true,
      server_received: new Date().toISOString(),
      notificationId: `echo_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`
    };
    
    // Send only to the individual socket, not to the room (to avoid duplicates)
    socket.emit('notification:toast', echoData);
    logger.info(`📣 Echoed notification to socket ${socketId}`);
  });
  
  // Trennungsereignis
  socket.on('disconnect', (reason) => {
    logger.info(`Verbindung getrennt: ${userId} (${socket.id}) - Grund: ${reason}`);
  });
  
  // Fehlerbehandlung
  socket.on('error', (error) => {
    logger.error(`Socket-Fehler ${userId} (${socket.id}): ${error.message}`);
  });
});

// Funktionsmodule registrieren
registerModules(io);

// Altes Whiteboard-Modul mit MySQL-Abhängigkeit - auskommentiert
/* 
const initWhiteboardModule = require('./modules/whiteboard');
logger.info('Initializing Whiteboard Module...');
initWhiteboardModule(io).then(success => {
  if (success) {
    logger.info('Whiteboard Module initialized successfully');
  } else {
    logger.error('Whiteboard Module initialization failed');
  }
}).catch(error => {
  logger.error(`Error initializing Whiteboard Module: ${error.message}`);
});
*/
// Hinweis: Wir verwenden jetzt stattdessen das einfache Whiteboard-Modul ohne MySQL (über registerModules)

// API-Endpoints für externe Systeme (z.B. PHP-Backend)
// ------------------------------------------------------------

// API-Schlüssel validieren
const validateApiKey = (req, res, next) => {
  const apiKey = req.headers['x-api-key'];
  const configuredApiKey = process.env.API_KEY;
  
  if (!apiKey || apiKey !== configuredApiKey) {
    logger.warn(`Ungültiger API-Schlüssel: ${apiKey}`);
    return res.status(401).json({ 
      success: false, 
      error: 'Ungültiger API-Schlüssel' 
    });
  }
  
  next();
};

// Create a dedicated notification logger
const notificationLogger = createLogger({
  level: 'info',
  format: combine(
    timestamp(),
    printf(({ level, message, timestamp }) => {
      return `${timestamp} [${level.toUpperCase()}]: ${message}`;
    })
  ),
  transports: [
    new transports.File({ filename: 'logs/notifications.log', maxsize: 5242880 }),
    new transports.Console({
      format: combine(
        colorize(),
        printf(({ level, message, timestamp }) => {
          return `${timestamp} [${level.toUpperCase()}]: ${message}`;
        })
      )
    })
  ]
});

// COMPATIBILITY: Add alias for the old /api/toast endpoint
app.post('/api/toast', validateApiKey, async (req, res) => {
  notificationLogger.info(`🔄 Received request to legacy endpoint /api/toast - redirecting to /api/notification/toast`);
  
  try {
    const { userId, message, title, sender_name, recipient_id, sender_id } = req.body;
    const targetUserId = userId || recipient_id;
    
    // Log notification details
    notificationLogger.info(`🔔 TOAST (legacy): USER ${targetUserId}: "${title}" from ${sender_name || 'System'}`);
    
    // Room for the user
    const userRoom = `user:${targetUserId}`;
    
    // Find all sockets in the specified room
    const socketsInRoom = await io.in(userRoom).fetchSockets();
    
    if (socketsInRoom.length === 0) {
      // Log warning instead of throwing an error - notifications should still "succeed" even if user is offline
      notificationLogger.warn(`⚠️ No sockets found in room ${userRoom} - toast will be missed`);
    } else {
      notificationLogger.info(`✅ Found ${socketsInRoom.length} socket(s) in room ${userRoom}: ${socketsInRoom.map(socket => socket.id).join(', ')}`);
    }

    // Toast data to send
    const toastData = {
      title: title || "New Notification",
      message: message || "",
      sender_name: sender_name || "System",
      sender_id: sender_id || null,
      recipient_id: targetUserId,
      timestamp: new Date().toISOString(),
      notificationId: `toast_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`
    };

    // Send the toast to all sockets in the room
    io.to(userRoom).emit("notification:toast", toastData);
    notificationLogger.info(`📤 Emitted toast to room ${userRoom} [ID: ${toastData.notificationId}]`);

    res.status(200).json({
      success: true,
      message: `Toast notification sent to ${socketsInRoom.length} socket(s)`,
      notification: toastData
    });
  } catch (error) {
    logger.error("Error sending toast notification via legacy endpoint:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// New route for toast notifications
app.post('/api/notification/toast', validateApiKey, async (req, res) => {
  notificationLogger.info(`🔄 Received request to new toast endpoint /api/notification/toast`);
  
  try {
    const { userId, message, title, sender_name, recipient_id, sender_id } = req.body;
    const targetUserId = userId || recipient_id;
    
    // Log notification details
    notificationLogger.info(`🔔 TOAST: USER ${targetUserId}: "${title}" from ${sender_name || 'System'}`);
    
    // Room for the user
    const userRoom = `user:${targetUserId}`;
    
    // Find all sockets in the specified room
    const socketsInRoom = await io.in(userRoom).fetchSockets();
    
    if (socketsInRoom.length === 0) {
      // Log warning instead of throwing an error - notifications should still "succeed" even if user is offline
      notificationLogger.warn(`⚠️ No sockets found in room ${userRoom} - toast will be missed`);
    } else {
      notificationLogger.info(`✅ Found ${socketsInRoom.length} socket(s) in room ${userRoom}: ${socketsInRoom.map(socket => socket.id).join(', ')}`);
    }

    // Toast data to send
    const toastData = {
      title: title || "New Notification",
      message: message || "",
      sender_name: sender_name || "System",
      sender_id: sender_id || null,
      recipient_id: targetUserId,
      timestamp: new Date().toISOString(),
      notificationId: `toast_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`
    };

    // Send the toast to all sockets in the room
    io.to(userRoom).emit("notification:toast", toastData);
    notificationLogger.info(`📤 Emitted toast to room ${userRoom} [ID: ${toastData.notificationId}]`);

    res.status(200).json({
      success: true,
      message: `Toast notification sent to ${socketsInRoom.length} socket(s)`,
      notification: toastData
    });
  } catch (error) {
    logger.error("Error sending toast notification:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// COMPATIBILITY: Add alias for the old notification endpoint
app.post('/api/notification', validateApiKey, async (req, res) => {
  notificationLogger.info(`🔄 Received request to the correct endpoint /api/notification`);
  
  try {
    const { userId, message, title, sender_name, recipient_id, sender_id } = req.body;
    const targetUserId = userId || recipient_id;
    
    // Log notification details clearly with dedicated logger
    notificationLogger.info(`📨 NOTIFICATION: USER ${targetUserId}: "${title}" from ${sender_name || 'System'}`);
    
    // Room for the user
    const userRoom = `user:${targetUserId}`;
    
    // Find all sockets in the specified room
    const socketsInRoom = await io.in(userRoom).fetchSockets();
    
    if (socketsInRoom.length === 0) {
      // Log warning instead of throwing an error - notifications should still "succeed" even if user is offline
      notificationLogger.warn(`⚠️ No sockets found in room ${userRoom} - notification will be missed`);
    } else {
      notificationLogger.info(`✅ Found ${socketsInRoom.length} socket(s) in room ${userRoom}: ${socketsInRoom.map(socket => socket.id).join(', ')}`);
    }

    // Notification data to send
    const notificationData = {
      title: title || "New Notification",
      message: message || "",
      sender_name: sender_name || "System",
      sender_id: sender_id || null,
      recipient_id: targetUserId,
      timestamp: new Date().toISOString(),
      notificationId: `notif_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`
    };

    // Send the notification to all sockets in the room
    io.to(userRoom).emit("notification:new", notificationData);
    notificationLogger.info(`📤 Emitted notification to room ${userRoom} [ID: ${notificationData.notificationId}]`);

    res.status(200).json({
      success: true,
      message: `Notification sent to ${socketsInRoom.length} socket(s)`,
      notification: notificationData
    });
  } catch (error) {
    logger.error("Error sending notification:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// Broadcast-Benachrichtigung an alle Benutzer senden
app.post('/api/broadcast', validateApiKey, (req, res) => {
  try {
    const { title, message, type, data, link, expiresAt } = req.body;
    
    if (!message) {
      return res.status(400).json({ 
        success: false, 
        error: 'message ist erforderlich' 
      });
    }
    
    const notification = {
      id: `api_broadcast_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
      senderId: 'system',
      message,
      title: title || 'Benachrichtigung',
      type: type || 'info',
      isBroadcast: true,
      timestamp: new Date().toISOString(),
      data: data || {},
      link: link || null,
      expiresAt: expiresAt || null,
      priority: req.body.priority || 'normal'
    };
    
    // Broadcast an alle Benutzer
    io.emit('notification:broadcast', notification);
    
    // Auch einen Toast an alle zeigen
    io.emit('notification:toast', {
      type: 'broadcast',
      title: notification.title,
      message: notification.message,
      priority: notification.priority,
      notificationType: notification.type,
      senderId: notification.senderId,
      timestamp: notification.timestamp,
      notificationId: notification.id,
      isBroadcast: true,
      data: notification.data,
      link: notification.link
    });
    
    logger.info(`API Broadcast gesendet: ${message}`);
    
    return res.status(200).json({ 
      success: true, 
      message: 'Broadcast erfolgreich gesendet',
      notificationId: notification.id
    });
  } catch (error) {
    logger.error(`Fehler beim Senden des API-Broadcasts: ${error.message}`);
    return res.status(500).json({ 
      success: false, 
      error: 'Interner Serverfehler' 
    });
  }
});

// Generic notification endpoint for backend to trigger socket events
app.post('/api/notify', validateApiKey, async (req, res) => {
  try {
    const { namespace, event, room, data, userId } = req.body;

    if (!namespace || !event) {
      return res.status(400).json({
        success: false,
        error: 'namespace and event are required'
      });
    }

    notificationLogger.info(`🔔 NOTIFY: namespace=${namespace}, event=${event}, room=${room || 'none'}, userId=${userId || 'none'}`);

    // Route to the appropriate namespace
    if (namespace === 'mail') {
      if (room) {
        // Emit to specific room
        mailNamespace.to(room).emit(event, data);
        notificationLogger.info(`📤 Emitted '${event}' to mail namespace room '${room}'`);
      } else if (userId) {
        // Emit to user's room
        const userRoom = `user:${userId}`;
        mailNamespace.to(userRoom).emit(event, data);
        notificationLogger.info(`📤 Emitted '${event}' to mail namespace user room '${userRoom}'`);
      } else {
        // Broadcast to entire namespace
        mailNamespace.emit(event, data);
        notificationLogger.info(`📤 Broadcasted '${event}' to entire mail namespace`);
      }
    } else if (namespace === 'notification') {
      if (room) {
        notificationNamespace.to(room).emit(event, data);
      } else if (userId) {
        notificationNamespace.to(`user:${userId}`).emit(event, data);
      } else {
        notificationNamespace.emit(event, data);
      }
    } else if (namespace === 'chat') {
      if (room) {
        chatNamespace.to(room).emit(event, data);
      } else if (userId) {
        chatNamespace.to(`user:${userId}`).emit(event, data);
      } else {
        chatNamespace.emit(event, data);
      }
    } else {
      // Default to main namespace
      if (room) {
        io.to(room).emit(event, data);
      } else if (userId) {
        io.to(`user:${userId}`).emit(event, data);
      } else {
        io.emit(event, data);
      }
    }

    res.status(200).json({
      success: true,
      message: 'Notification sent successfully'
    });
  } catch (error) {
    notificationLogger.error(`Error in /api/notify: ${error.message}`);
    res.status(500).json({
      success: false,
      error: 'Failed to send notification'
    });
  }
});

// Diagnostic endpoint to check connected users and send a test notification
app.get('/api/diagnostic', validateApiKey, (req, res) => {
  try {
    // Check connected clients
    const connectedSockets = Array.from(io.sockets.sockets.entries());
    const connectedUsers = [];
    
    connectedSockets.forEach(([socketId, socket]) => {
      connectedUsers.push({
        socketId,
        userId: socket.user?.id || 'unknown',
        username: socket.user?.username || 'unknown',
        rooms: Array.from(socket.rooms || []),
        connected: socket.connected
      });
    });
    
    // Check user rooms
    const allRooms = Array.from(io.sockets.adapter.rooms.entries())
      .filter(([room]) => room.startsWith('user:'))
      .map(([room, users]) => ({
        room,
        userCount: users.size,
        sockets: Array.from(users)
      }));
    
    // Optional: Send a test notification to a specific user
    const userId = req.query.userId;
    let testResult = null;
    
    if (userId) {
      const userRoom = `user:${userId}`;
      const roomExists = io.sockets.adapter.rooms.has(userRoom);
      const roomSize = io.sockets.adapter.rooms.get(userRoom)?.size || 0;
      
      // Send test toast
      const testToast = {
        type: 'test',
        title: 'Test Notification',
        message: `This is a test notification sent at ${new Date().toISOString()}`,
        priority: 'normal',
        notificationType: 'info',
        timestamp: new Date().toISOString(),
        id: `test_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
      };
      
      io.to(userRoom).emit('notification:toast', testToast);
      logger.info(`Diagnostic: Test notification sent to user ${userId}`);
      
      // Direct delivery to matching sockets
      let sentToSockets = 0;
      io.sockets.sockets.forEach(socket => {
        if (socket.user && socket.user.id == userId) {
          socket.emit('notification:toast', testToast);
          logger.debug(`Diagnostic: Direct test notification sent to socket ${socket.id}`);
          sentToSockets++;
        }
      });
      
      testResult = {
        sent: true,
        userId,
        userRoom,
        roomExists,
        roomSize,
        sentToSockets,
        message: testToast.message
      };
    }
    
    // Respond with diagnostic information
    res.status(200).json({
      success: true,
      diagnostic: {
        serverTime: new Date().toISOString(),
        environment: NODE_ENV,
        connectedUsers: connectedUsers.length,
        rooms: allRooms.length,
        users: connectedUsers,
        userRooms: allRooms,
        testResult
      }
    });
  } catch (error) {
    logger.error(`Fehler bei der Diagnose: ${error.message}`);
    res.status(500).json({
      success: false,
      error: 'Interner Serverfehler'
    });
  }
});

// Set up socket namespaces
const mainNamespace = io;
const chatNamespace = io.of('/chat');
const notificationNamespace = io.of('/notification');
const statusNamespace = io.of('/status');
const mailNamespace = io.of('/mail');

// Add diagnostic endpoint for health checks
app.get('/health', (req, res) => {
  res.status(200).json({
    status: 'ok',
    uptime: process.uptime(),
    timestamp: new Date().toISOString(),
    connections: {
      main: Object.keys(io.sockets.sockets).length,
      chat: Object.keys(chatNamespace.sockets).length,
      notification: Object.keys(notificationNamespace.sockets).length,
      status: Object.keys(statusNamespace.sockets).length,
      mail: Object.keys(mailNamespace.sockets).length
    }
  });
});

// Apply authentication middleware to all namespaces
[chatNamespace, notificationNamespace, statusNamespace, mailNamespace].forEach(namespace => {
  namespace.use(authenticate);

  // Log when each namespace is ready
  logger.info(`Namespace ${namespace.name || 'main'} initialized with authentication`);

  // Debug connection events for each namespace
  namespace.on('connection', (socket) => {
    const userId = socket.user?.id || 'unknown';
    logger.info(`Namespace ${namespace.name} connection: User ${userId} (${socket.id})`);

    // Add connection feedback event
    socket.emit('namespace:connected', {
      namespace: namespace.name,
      socketId: socket.id,
      userId: userId
    });
  });
});

// ============================================================================
// Mail Namespace - Real-time mail notifications
// ============================================================================
mailNamespace.on('connection', (socket) => {
  const userId = socket.user?.id;
  const authorityId = socket.user?.authority_id;

  if (!userId) {
    logger.warn('Mail namespace connection without user ID');
    return;
  }

  logger.info(`Mail client connected: User ${userId} (${socket.id})`);

  // Join personal room for individual mail notifications
  socket.join(`user:${userId}`);

  // Join authority room for system/broadcast messages
  if (authorityId) {
    socket.join(`authority:${authorityId}`);
  }

  // Send connection confirmation
  socket.emit('mail:connected', {
    userId,
    authorityId,
    timestamp: new Date().toISOString()
  });

  // Handle mark_as_read event - sync across user's tabs
  socket.on('mark_as_read', (data) => {
    logger.info(`Mail marked as read: ${data.mail_id} by user ${userId}`);
    // Broadcast to other sessions of the same user
    socket.to(`user:${userId}`).emit('mail_read', {
      mail_id: data.mail_id,
      is_read: true,
      timestamp: new Date().toISOString()
    });
  });

  // Handle mail_starred event - sync across user's tabs
  socket.on('mail_starred', (data) => {
    logger.info(`Mail starred: ${data.mail_id} by user ${userId}, starred=${data.is_starred}`);
    // Broadcast to other sessions of the same user
    socket.to(`user:${userId}`).emit('mail_starred', {
      mail_id: data.mail_id,
      is_starred: data.is_starred,
      timestamp: new Date().toISOString()
    });
  });

  // Handle disconnect
  socket.on('disconnect', () => {
    logger.info(`Mail client disconnected: User ${userId} (${socket.id})`);
  });
});

// Server starten
server.listen(PORT, () => {
  logger.info(`Socket.io-Server läuft auf Port ${PORT}`);
});

// Fehlerbehandlung für unerwartete Ausnahmen
process.on('uncaughtException', (error) => {
  logger.error(`Uncaught Exception: ${error.message}`);
  logger.error(error.stack);
});

process.on('unhandledRejection', (reason, promise) => {
  logger.error('Unhandled Rejection at:', promise);
  logger.error('Reason:', reason);
});

// Anmutige Beendigung
process.on('SIGTERM', () => {
  logger.info('SIGTERM received, shutting down gracefully');
  io.close();
  server.close(() => {
    logger.info('Server closed');
    process.exit(0);
  });
});

// Beenden bei SIGINT (Ctrl+C)
process.on('SIGINT', () => {
  logger.info('Server wird heruntergefahren...');
  io.close();
  server.close(() => {
    logger.info('Server wurde heruntergefahren');
    process.exit(0);
  });
});

module.exports = { io, server }; 