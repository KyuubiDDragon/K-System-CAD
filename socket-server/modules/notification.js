const logger = require('../utils/logger');

/**
 * Benachrichtigungsmodul für Socket.io
 * Implementiert Echtzeit-Benachrichtigungen für Benutzer
 * 
 * @param {Object} io - Socket.io-Serverinstanz
 */
function notificationModule(io) {
  // Benachrichtigungs-Namespace erstellen
  const notifyNamespace = io.of('/notification');
  
  // Verbindung zum Benachrichtigungs-Namespace
  notifyNamespace.on('connection', (socket) => {
    const userId = socket.user?.id || 'unknown';
    logger.info(`Benutzer ${userId} hat sich mit dem Benachrichtigungs-Namespace verbunden`);
    
    // Benutzer zu seinen privaten Räumen hinzufügen
    if (socket.user?.id) {
      socket.join(`user:${socket.user.id}`);
    }
    
    // Benutzer zu Rollen-basierten Räumen hinzufügen
    if (socket.user?.roles) {
      const roles = Array.isArray(socket.user.roles) 
        ? socket.user.roles 
        : [socket.user.roles];
      
      roles.forEach(role => {
        socket.join(`role:${role}`);
        logger.debug(`Benutzer ${userId} zum Rollen-Raum ${role} hinzugefügt`);
      });
    }
    
    // Benachrichtigung an einen bestimmten Benutzer senden
    socket.on('notify:user', (data) => {
      try {
        if (!data.userId || !data.message) {
          return socket.emit('error', { 
            message: 'Ungültige Benachrichtigungsdaten', 
            code: 'INVALID_NOTIFICATION_DATA' 
          });
        }
        
        // Prüfen, ob der Absender berechtigt ist
        if (!hasPermission(socket.user, 'send_notifications')) {
          logger.warn(`Benutzer ${userId} hat versucht, eine Benachrichtigung ohne Berechtigung zu senden`);
          return socket.emit('error', {
            message: 'Keine Berechtigung zum Senden von Benachrichtigungen',
            code: 'PERMISSION_DENIED'
          });
        }
        
        const notification = formatNotification(data, socket.user.id);
        
        logger.debug(`Benutzerbenachrichtigung: ${socket.user.id} -> ${data.userId}`);
        
        // An den Empfänger senden
        io.to(`user:${data.userId}`).emit('notification:new', notification);
        
        // Toast-Benachrichtigung für den Empfänger senden
        io.to(`user:${data.userId}`).emit('notification:toast', {
          type: 'notification',
          title: notification.title,
          message: notification.message,
          priority: notification.priority,
          notificationType: notification.type,
          senderId: notification.senderId,
          timestamp: notification.timestamp,
          notificationId: notification.id,
          data: notification.data,
          link: notification.link
        });
        
        // Bestätigung an den Absender
        socket.emit('notification:sent', { 
          id: notification.id,
          recipientId: data.userId,
          status: 'sent'
        });
        
      } catch (error) {
        logger.error(`Fehler beim Senden der Benutzerbenachrichtigung: ${error.message}`);
        socket.emit('error', { 
          message: 'Benachrichtigung konnte nicht gesendet werden', 
          code: 'NOTIFICATION_SEND_FAILED' 
        });
      }
    });
    
    // Broadcast-Benachrichtigung an alle Benutzer senden
    socket.on('notify:broadcast', (data) => {
      try {
        if (!data.message) {
          return socket.emit('error', { 
            message: 'Ungültige Broadcast-Daten', 
            code: 'INVALID_BROADCAST_DATA' 
          });
        }
        
        // Prüfen, ob der Absender berechtigt ist (z.B. Admin)
        if (!hasPermission(socket.user, 'broadcast_notifications')) {
          logger.warn(`Benutzer ${userId} hat versucht, einen Broadcast ohne Berechtigung zu senden`);
          return socket.emit('error', {
            message: 'Keine Berechtigung zum Senden von Broadcasts',
            code: 'BROADCAST_PERMISSION_DENIED'
          });
        }
        
        const notification = formatNotification(data, socket.user.id, true);
        
        logger.info(`Broadcast-Benachrichtigung von ${socket.user.id}: ${data.message}`);
        
        // An alle Benutzer senden
        notifyNamespace.emit('notification:broadcast', notification);
        
        // Toast-Benachrichtigung für alle Benutzer senden
        notifyNamespace.emit('notification:toast', {
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
        
        // Bestätigung an den Absender
        socket.emit('notification:broadcast_sent', { 
          id: notification.id,
          status: 'sent'
        });
        
      } catch (error) {
        logger.error(`Fehler beim Senden der Broadcast-Benachrichtigung: ${error.message}`);
        socket.emit('error', { 
          message: 'Broadcast konnte nicht gesendet werden', 
          code: 'BROADCAST_FAILED' 
        });
      }
    });
    
    // Rollen-basierte Benachrichtigung senden
    socket.on('notify:role', (data) => {
      try {
        if (!data.role || !data.message) {
          return socket.emit('error', { 
            message: 'Ungültige Rollendaten', 
            code: 'INVALID_ROLE_DATA' 
          });
        }
        
        // Prüfen, ob der Absender berechtigt ist
        if (!hasPermission(socket.user, 'notify_role')) {
          logger.warn(`Benutzer ${userId} hat versucht, eine Rollenbenachrichtigung ohne Berechtigung zu senden`);
          return socket.emit('error', {
            message: 'Keine Berechtigung zum Senden von Rollenbenachrichtigungen',
            code: 'ROLE_NOTIFICATION_PERMISSION_DENIED'
          });
        }
        
        const notification = formatNotification(data, socket.user.id);
        
        logger.info(`Rollenbenachrichtigung an ${data.role} von ${socket.user.id}: ${data.message}`);
        
        // An alle Benutzer mit der angegebenen Rolle senden
        io.to(`role:${data.role}`).emit('notification:role', notification);
        
        // Toast-Benachrichtigung für alle Benutzer mit der angegebenen Rolle senden
        io.to(`role:${data.role}`).emit('notification:toast', {
          type: 'role_notification',
          title: notification.title,
          message: notification.message,
          priority: notification.priority,
          notificationType: notification.type,
          senderId: notification.senderId,
          timestamp: notification.timestamp,
          notificationId: notification.id,
          role: data.role,
          data: notification.data,
          link: notification.link
        });
        
        // Bestätigung an den Absender
        socket.emit('notification:role_sent', { 
          id: notification.id,
          role: data.role,
          status: 'sent'
        });
        
      } catch (error) {
        logger.error(`Fehler beim Senden der Rollenbenachrichtigung: ${error.message}`);
        socket.emit('error', { 
          message: 'Rollenbenachrichtigung konnte nicht gesendet werden', 
          code: 'ROLE_NOTIFICATION_FAILED' 
        });
      }
    });
    
    // API für das Senden von Toast-Benachrichtigungen
    socket.on('toast', (data) => {
      try {
        if (!data.userId || !data.message) {
          return socket.emit('error', { 
            message: 'Ungültige Toast-Daten', 
            code: 'INVALID_TOAST_DATA' 
          });
        }
        
        // Prüfen, ob der Absender berechtigt ist
        if (!hasPermission(socket.user, 'send_notifications')) {
          logger.warn(`Benutzer ${userId} hat versucht, einen Toast ohne Berechtigung zu senden`);
          return socket.emit('error', {
            message: 'Keine Berechtigung zum Senden von Toast-Benachrichtigungen',
            code: 'TOAST_PERMISSION_DENIED'
          });
        }
        
        const toastData = {
          type: 'custom_toast',
          title: data.title || 'Benachrichtigung',
          message: data.message,
          priority: data.priority || 'normal',
          senderId: socket.user.id,
          senderName: socket.user.username || 'Unbekannt',
          timestamp: new Date().toISOString(),
          id: `toast_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
          data: data.data || {}
        };
        
        // An den Empfänger senden
        io.to(`user:${data.userId}`).emit('notification:toast', toastData);
        
        // Bestätigung an den Absender
        socket.emit('toast:sent', {
          id: toastData.id,
          recipientId: data.userId,
          status: 'sent'
        });
        
      } catch (error) {
        logger.error(`Fehler beim Senden des Toasts: ${error.message}`);
        socket.emit('error', { 
          message: 'Toast-Benachrichtigung konnte nicht gesendet werden', 
          code: 'TOAST_SEND_FAILED' 
        });
      }
    });
    
    // Verbindung getrennt
    socket.on('disconnect', () => {
      logger.info(`Benutzer ${userId} hat die Verbindung zum Benachrichtigungs-Namespace getrennt`);
    });
  });
  
  return notifyNamespace;
}

/**
 * Formatiert eine Benachrichtigung
 * @param {Object} data - Benachrichtigungsdaten
 * @param {string} senderId - ID des Absenders
 * @param {boolean} [isBroadcast=false] - Ob es sich um einen Broadcast handelt
 * @returns {Object} Formatierte Benachrichtigung
 */
function formatNotification(data, senderId, isBroadcast = false) {
  return {
    id: `notify_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
    senderId,
    message: data.message,
    title: data.title || 'Benachrichtigung',
    type: data.type || 'info',
    isBroadcast,
    timestamp: new Date().toISOString(),
    data: data.data || {},
    link: data.link || null,
    expiresAt: data.expiresAt || null,
    priority: data.priority || 'normal'
  };
}

/**
 * Prüft, ob ein Benutzer eine bestimmte Berechtigung hat
 * @param {Object} user - Benutzerobjekt
 * @param {string} permission - Benötigte Berechtigung
 * @returns {boolean} Hat Berechtigung oder nicht
 */
function hasPermission(user, permission) {
  // Hier sollte eine komplexere Berechtigungsprüfung implementiert werden
  // Für Demonstrationszwecke verwenden wir eine einfache rollenbasierte Prüfung
  
  // Admins haben alle Berechtigungen
  if (user.roles && (
    Array.isArray(user.roles) && user.roles.includes('admin') ||
    user.roles === 'admin'
  )) {
    return true;
  }
  
  // Spezifische Berechtigungen
  const permissionMap = {
    'send_notifications': ['admin', 'manager', 'support'],
    'broadcast_notifications': ['admin', 'manager'],
    'notify_role': ['admin', 'manager']
  };
  
  const requiredRoles = permissionMap[permission] || [];
  
  if (user.roles) {
    if (Array.isArray(user.roles)) {
      return user.roles.some(role => requiredRoles.includes(role));
    } else {
      return requiredRoles.includes(user.roles);
    }
  }
  
  return false;
}

module.exports = notificationModule; 