const logger = require('../utils/logger');

/**
 * Chat-Modul für Socket.io
 * Implementiert Chat-Funktionalitäten wie direkte Nachrichten,
 * Gruppenchats und Schreibindikatoren
 * 
 * @param {Object} io - Socket.io-Serverinstanz
 */
function chatModule(io) {
  // Chat-Namespace erstellen
  const chatNamespace = io.of('/chat');
  
  // Verbindung zum Chat-Namespace
  chatNamespace.on('connection', (socket) => {
    const userId = socket.user?.id || 'unknown';
    logger.info(`Benutzer ${userId} hat sich mit dem Chat-Namespace verbunden`);
    
    // Benutzer zu seinen privaten Räumen hinzufügen
    if (socket.user?.id) {
      socket.join(`user:${socket.user.id}`);
    }
    
    // Direktnachricht senden
    socket.on('message:direct', async (data) => {
      try {
        if (!data.recipientId || !data.message) {
          return socket.emit('error', { 
            message: 'Ungültige Nachrichtendaten', 
            code: 'INVALID_DATA' 
          });
        }
        
        const message = {
          id: generateMessageId(),
          senderId: socket.user.id,
          senderName: socket.user.username || 'Unbekannt',
          recipientId: data.recipientId,
          content: data.message,
          timestamp: new Date().toISOString(),
          read: false
        };
        
        logger.debug(`Direktnachricht: ${socket.user.id} -> ${data.recipientId}`);
        
        // An den Empfänger senden
        io.to(`user:${data.recipientId}`).emit('message:received', message);
        
        // Toast-Benachrichtigung für neue Nachricht an den Empfänger senden
        io.to(`user:${data.recipientId}`).emit('notification:toast', {
          type: 'message',
          title: 'Neue Nachricht',
          message: `Von: ${socket.user.username || 'Unbekannt'}`,
          content: data.message.length > 50 ? data.message.substring(0, 50) + '...' : data.message,
          senderId: socket.user.id,
          senderName: socket.user.username || 'Unbekannt',
          timestamp: new Date().toISOString(),
          messageId: message.id
        });
        
        // Bestätigung an den Absender
        socket.emit('message:sent', { 
          id: message.id,
          status: 'sent',
          timestamp: message.timestamp
        });
        
      } catch (error) {
        logger.error(`Fehler beim Senden der Direktnachricht: ${error.message}`);
        socket.emit('error', { 
          message: 'Nachricht konnte nicht gesendet werden', 
          code: 'SEND_FAILED' 
        });
      }
    });
    
    // Gruppenraum beitreten
    socket.on('room:join', (roomId) => {
      if (!roomId) return;
      
      socket.join(`room:${roomId}`);
      logger.info(`Benutzer ${userId} ist Raum ${roomId} beigetreten`);
      
      // Anderen Raummitgliedern mitteilen
      socket.to(`room:${roomId}`).emit('room:user_joined', {
        roomId,
        userId: socket.user.id,
        username: socket.user.username || 'Unbekannt'
      });
    });
    
    // Gruppenraum verlassen
    socket.on('room:leave', (roomId) => {
      if (!roomId) return;
      
      socket.leave(`room:${roomId}`);
      logger.info(`Benutzer ${userId} hat Raum ${roomId} verlassen`);
      
      // Anderen Raummitgliedern mitteilen
      socket.to(`room:${roomId}`).emit('room:user_left', {
        roomId,
        userId: socket.user.id,
        username: socket.user.username || 'Unbekannt'
      });
    });
    
    // Nachricht an Gruppenraum
    socket.on('message:room', async (data) => {
      try {
        if (!data.roomId || !data.message) {
          return socket.emit('error', { 
            message: 'Ungültige Raumzuordnung oder Nachricht', 
            code: 'INVALID_ROOM_DATA' 
          });
        }
        
        const roomMessage = {
          id: generateMessageId(),
          senderId: socket.user.id,
          senderName: socket.user.username || 'Unbekannt',
          roomId: data.roomId,
          content: data.message,
          timestamp: new Date().toISOString()
        };
        
        logger.debug(`Raumnachricht: ${socket.user.id} -> Raum ${data.roomId}`);
        
        // An alle im Raum senden (außer dem Absender)
        socket.to(`room:${data.roomId}`).emit('room:message', roomMessage);
        
        // Toast-Benachrichtigung für neue Raumnnachricht an alle Mitglieder senden (außer dem Absender)
        socket.to(`room:${data.roomId}`).emit('notification:toast', {
          type: 'room_message',
          title: `Neue Nachricht in Raum ${data.roomId}`,
          message: `Von: ${socket.user.username || 'Unbekannt'}`,
          content: data.message.length > 50 ? data.message.substring(0, 50) + '...' : data.message,
          senderId: socket.user.id,
          senderName: socket.user.username || 'Unbekannt',
          roomId: data.roomId,
          timestamp: new Date().toISOString(),
          messageId: roomMessage.id
        });
        
        // Bestätigung an den Absender
        socket.emit('message:sent', { 
          id: roomMessage.id,
          roomId: data.roomId,
          status: 'sent',
          timestamp: roomMessage.timestamp
        });
        
      } catch (error) {
        logger.error(`Fehler beim Senden der Raumnachricht: ${error.message}`);
        socket.emit('error', { 
          message: 'Raumnachricht konnte nicht gesendet werden', 
          code: 'ROOM_SEND_FAILED' 
        });
      }
    });
    
    // Schreibindikator
    socket.on('typing:start', (data) => {
      if (data.roomId) {
        // Schreibindikator für Raum
        socket.to(`room:${data.roomId}`).emit('user:typing', {
          userId: socket.user.id,
          username: socket.user.username || 'Unbekannt',
          roomId: data.roomId
        });
      } else if (data.recipientId) {
        // Schreibindikator für Direktnachricht
        io.to(`user:${data.recipientId}`).emit('user:typing', {
          userId: socket.user.id,
          username: socket.user.username || 'Unbekannt'
        });
      }
    });
    
    // Schreibindikator beenden
    socket.on('typing:stop', (data) => {
      if (data.roomId) {
        // Schreibindikator für Raum beenden
        socket.to(`room:${data.roomId}`).emit('user:stopped_typing', {
          userId: socket.user.id,
          roomId: data.roomId
        });
      } else if (data.recipientId) {
        // Schreibindikator für Direktnachricht beenden
        io.to(`user:${data.recipientId}`).emit('user:stopped_typing', {
          userId: socket.user.id
        });
      }
    });
    
    // Verbindung getrennt
    socket.on('disconnect', () => {
      logger.info(`Benutzer ${userId} hat die Verbindung zum Chat-Namespace getrennt`);
    });
  });
  
  return chatNamespace;
}

/**
 * Generiert eine eindeutige Nachrichten-ID
 * @returns {string} Eindeutige ID
 */
function generateMessageId() {
  return `msg_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
}

module.exports = chatModule; 