/**
 * Einfaches Whiteboard-Modul für Socket.io ohne Datenbankanbindung
 * Dient nur zur Echtzeit-Kommunikation zwischen Benutzern
 */

const logger = require('../utils/logger');

/**
 * Initialisiert das Whiteboard-Modul
 * @param {Object} io - Socket.io-Serverinstanz
 */
function initWhiteboardModule(io) {
  // In-Memory-Daten (nur für die Lebensdauer des Servers)
  const whiteboardRooms = new Map(); // Mapping von Whiteboard-IDs zu Benutzer-IDs
  
  // Optional: In-Memory-Cache für Whiteboard-Elemente (nur für Echtzeit, nicht persistent)
  const whiteboardElements = new Map(); // Mapping von Whiteboard-IDs zu Elementen
  
  logger.info('Simples Whiteboard-Modul wird initialisiert (ohne Datenbank)');
  
  // Debug-Funktion: Alle Socket-Räume auflisten
  const logRooms = () => {
    logger.debug('Alle aktiven Whiteboard-Räume:');
    for (const [whiteboardId, users] of whiteboardRooms.entries()) {
      logger.debug(`- Whiteboard ${whiteboardId}: ${users.size} Benutzer (${Array.from(users).join(', ')})`);
    }
    
    // Socket.io-Räume überprüfen
    const rooms = io.sockets.adapter.rooms;
    logger.debug('Socket.io Räume:');
    for (const [roomName, sockets] of rooms.entries()) {
      if (roomName.startsWith('whiteboard:')) {
        logger.debug(`- ${roomName}: ${sockets.size} Sockets (${Array.from(sockets).join(', ')})`);
      }
    }
  };
  
  io.on('connection', (socket) => {
    const userId = socket.user?.id;
    
    if (!userId) {
      logger.warn('Whiteboard: Verbindung ohne Benutzer-ID abgelehnt');
      return;
    }
    
    logger.info(`Whiteboard: Benutzer ${userId} verbunden mit Socket ${socket.id}`);
    
    // Event: Beitreten zu einem Whiteboard
    socket.on('whiteboard:join', async (data) => {
      try {
        const { whiteboardId } = data;
        
        if (!whiteboardId) {
          logger.warn(`Whiteboard: Ungültige Whiteboard-ID von Benutzer ${userId}`);
          socket.emit('whiteboard:error', { 
            message: 'Keine gültige Whiteboard-ID angegeben' 
          });
          return;
        }
        
        logger.info(`Whiteboard: Benutzer ${userId} (Socket ${socket.id}) tritt Whiteboard ${whiteboardId} bei`);
        
        const roomName = `whiteboard:${whiteboardId}`;
        
        // Bestehende Räume verlassen
        const currentRooms = Array.from(socket.rooms)
          .filter(room => room.startsWith('whiteboard:') && room !== roomName);
        
        for (const room of currentRooms) {
          socket.leave(room);
          logger.info(`Whiteboard: Benutzer ${userId} hat ${room} verlassen`);
          
          // Teilnehmerzahl aktualisieren
          io.to(room).emit('whiteboard:user-left', { 
            userId,
            timestamp: new Date().toISOString()
          });
        }
        
        // Neuem Raum beitreten
        socket.join(roomName);
        logger.info(`Whiteboard: Benutzer ${userId} ist ${roomName} beigetreten`);
        
        // Debug: Prüfe Raumzugehörigkeit
        const isInRoom = socket.rooms.has(roomName);
        logger.debug(`Socket ${socket.id} ist ${isInRoom ? '' : 'NICHT '}im Raum ${roomName}`);
        
        // Raum-Tracking aktualisieren
        if (!whiteboardRooms.has(whiteboardId)) {
          whiteboardRooms.set(whiteboardId, new Set());
        }
        whiteboardRooms.get(whiteboardId).add(userId);
        
        // Zähle Benutzer in diesem Raum
        const participantsCount = whiteboardRooms.get(whiteboardId).size;
        
        // Debug: Raumliste ausgeben
        logRooms();
        
        // Beitrittsbestätigung an Client senden
        // WICHTIG: Das Format muss mit dem vom Frontend erwarteten Format übereinstimmen
        socket.emit('whiteboard:joined', {
          success: true,
          // Wir müssen ein vollständiges whiteboard-Objekt zurückgeben,
          // nicht nur die ID, da das Frontend darauf wartet
          whiteboard: {
            id: whiteboardId,
            name: `Whiteboard ${whiteboardId}`, // Standardname, da wir keine Datenbank haben
            participants: participantsCount,
            content: whiteboardElements.get(whiteboardId) || {}, // Cache der Elemente oder leeres Objekt
            history: []  // Leere Historie, da wir keine Datenbank haben
          },
          timestamp: new Date().toISOString()
        });
        
        // Anderen Teilnehmern mitteilen
        socket.to(roomName).emit('whiteboard:user-joined', {
          userId,
          timestamp: new Date().toISOString()
        });
        
        // Aktualisiere Teilnehmerzahl für alle im Raum
        io.to(roomName).emit('whiteboard:participants-updated', {
          whiteboardId,
          participants: participantsCount,
          timestamp: new Date().toISOString()
        });
        
        logger.info(`Whiteboard: Benutzer ${userId} ist erfolgreich ${roomName} beigetreten (${participantsCount} Teilnehmer total)`);
      } catch (error) {
        logger.error(`Whiteboard: Fehler beim Beitritt: ${error.message}`);
        socket.emit('whiteboard:error', { message: 'Fehler beim Beitritt zum Whiteboard' });
      }
    });
    
    // Event: Zeichnen-Event - hier ist der Event-Name wichtig!
    socket.on('whiteboard:draw', (data) => {
      try {
        const { whiteboardId, element } = data;
        
        if (!whiteboardId || !element) {
          logger.warn(`Whiteboard: Ungültige Zeichendaten von Benutzer ${userId}`);
          return;
        }
        
        const roomName = `whiteboard:${whiteboardId}`;
        
        // Debug: Raumzugehörigkeit prüfen
        const isInRoom = socket.rooms.has(roomName);
        logger.debug(`💬 DRAW: Socket ${socket.id} ist ${isInRoom ? '' : 'NICHT '}im Raum ${roomName}`);
        
        logger.debug(`Whiteboard: Benutzer ${userId} hat gezeichnet in ${roomName}, Element-ID: ${element.id}`);
        
        // Optional: Element im In-Memory-Cache speichern
        if (!whiteboardElements.has(whiteboardId)) {
          whiteboardElements.set(whiteboardId, {});
        }
        whiteboardElements.get(whiteboardId)[element.id] = element;
        
        // Debug: Anzahl der Sockets im Raum
        const socketsInRoom = io.sockets.adapter.rooms.get(roomName);
        const socketCount = socketsInRoom ? socketsInRoom.size : 0;
        logger.debug(`💬 Raum ${roomName} hat ${socketCount} verbundene Sockets`);
        
        // Event an alle Clients im Raum weiterleiten (außer an den Sender)
        // ⚠️ WICHTIG: Event-Name muss exakt mit dem im Frontend erwarteten übereinstimmen
        logger.debug(`🔄 Sende Element-ID ${element.id} an Raum ${roomName}`);
        socket.to(roomName).emit('whiteboard:element-drawn', {
          element,
          userId,
          timestamp: new Date().toISOString()
        });
        
        // Alternative Methode, um sicherzustellen, dass das Event an alle geht
        // Mit Ausnahme des Senders direkt über io.to senden
        if (socketsInRoom && socketsInRoom.size > 1) {
          logger.debug(`🔄 Alternative Methode: Sende an alle ${socketsInRoom.size} Clients im Raum ${roomName}`);
          for (const clientId of socketsInRoom) {
            if (clientId !== socket.id) {
              io.to(clientId).emit('whiteboard:element-drawn', {
                element,
                userId,
                timestamp: new Date().toISOString()
              });
            }
          }
        } else {
          logger.debug(`⚠️ Keine anderen Clients im Raum ${roomName} gefunden`);
        }
        
        logger.debug(`Whiteboard: Zeichenelement von Benutzer ${userId} an Raum ${roomName} gesendet`);
      } catch (error) {
        logger.error(`Whiteboard: Fehler beim Verarbeiten des Zeichnens: ${error.message}`);
      }
    });
    
    // Event: Element löschen
    socket.on('whiteboard:delete-element', (data) => {
      try {
        const { whiteboardId, elementId } = data;
        
        if (!whiteboardId || !elementId) {
          logger.warn(`Whiteboard: Ungültige Löschdaten von Benutzer ${userId}`);
          return;
        }
        
        const roomName = `whiteboard:${whiteboardId}`;
        
        // Debug: Raumzugehörigkeit prüfen
        const isInRoom = socket.rooms.has(roomName);
        logger.debug(`🗑️ DELETE: Socket ${socket.id} ist ${isInRoom ? '' : 'NICHT '}im Raum ${roomName}`);
        
        logger.debug(`Whiteboard: Benutzer ${userId} löscht Element ${elementId} in ${roomName}`);
        
        // Optional: Element aus dem In-Memory-Cache entfernen
        if (whiteboardElements.has(whiteboardId) && whiteboardElements.get(whiteboardId)[elementId]) {
          delete whiteboardElements.get(whiteboardId)[elementId];
        }
        
        // Debug: Anzahl der Sockets im Raum
        const socketsInRoom = io.sockets.adapter.rooms.get(roomName);
        const socketCount = socketsInRoom ? socketsInRoom.size : 0;
        logger.debug(`🗑️ Raum ${roomName} hat ${socketCount} verbundene Sockets`);
        
        // Event an alle Clients im Raum weiterleiten (außer an den Sender)
        // ⚠️ WICHTIG: Event-Name muss exakt mit dem im Frontend erwarteten übereinstimmen
        logger.debug(`🔄 Sende Löschbefehl für Element-ID ${elementId} an Raum ${roomName}`);
        socket.to(roomName).emit('whiteboard:element-deleted', {
          elementId,
          whiteboardId,
          userId,
          timestamp: new Date().toISOString()
        });
        
        // Alternative Methode, um sicherzustellen, dass das Event an alle geht
        if (socketsInRoom && socketsInRoom.size > 1) {
          logger.debug(`🔄 Alternative Methode: Sende Löschbefehl an alle ${socketsInRoom.size - 1} anderen Clients im Raum ${roomName}`);
          for (const clientId of socketsInRoom) {
            if (clientId !== socket.id) {
              io.to(clientId).emit('whiteboard:element-deleted', {
                elementId,
                whiteboardId,
                userId,
                timestamp: new Date().toISOString()
              });
            }
          }
        }
        
        logger.debug(`Whiteboard: Löschelement von Benutzer ${userId} an Raum ${roomName} gesendet`);
      } catch (error) {
        logger.error(`Whiteboard: Fehler beim Verarbeiten des Löschens: ${error.message}`);
      }
    });
    
    // Event: Ein Whiteboard verlassen
    socket.on('whiteboard:leave', (data) => {
      try {
        const { whiteboardId } = data;
        
        if (!whiteboardId) return;
        
        const roomName = `whiteboard:${whiteboardId}`;
        
        socket.leave(roomName);
        logger.info(`Whiteboard: Benutzer ${userId} hat ${roomName} verlassen`);
        
        // Raum-Tracking aktualisieren
        if (whiteboardRooms.has(whiteboardId)) {
          whiteboardRooms.get(whiteboardId).delete(userId);
          
          // Aktualisierte Teilnehmerzahl
          const participantsCount = whiteboardRooms.get(whiteboardId).size;
          
          // Teilnehmerzahl für alle aktualisieren
          io.to(roomName).emit('whiteboard:participants-updated', {
            whiteboardId,
            participants: participantsCount,
            timestamp: new Date().toISOString()
          });
          
          // Raum löschen, wenn leer
          if (participantsCount === 0) {
            whiteboardRooms.delete(whiteboardId);
            
            // Optional: Gespeicherte Elemente löschen, wenn der Raum leer ist
            whiteboardElements.delete(whiteboardId);
            
            logger.info(`Whiteboard: Raum ${roomName} wurde gelöscht (keine Teilnehmer mehr)`);
          }
        }
        
        // Anderen Teilnehmern mitteilen
        socket.to(roomName).emit('whiteboard:user-left', {
          userId,
          timestamp: new Date().toISOString()
        });
      } catch (error) {
        logger.error(`Whiteboard: Fehler beim Verlassen: ${error.message}`);
      }
    });
    
    // Aufräumen bei Verbindungstrennung
    socket.on('disconnect', () => {
      try {
        // Alle Whiteboard-Räume verlassen
        for (const [whiteboardId, users] of whiteboardRooms.entries()) {
          if (users.has(userId)) {
            users.delete(userId);
            
            const roomName = `whiteboard:${whiteboardId}`;
            const participantsCount = users.size;
            
            // Anderen Teilnehmern mitteilen
            socket.to(roomName).emit('whiteboard:user-left', {
              userId,
              timestamp: new Date().toISOString()
            });
            
            // Teilnehmerzahl für alle aktualisieren
            io.to(roomName).emit('whiteboard:participants-updated', {
              whiteboardId,
              participants: participantsCount,
              timestamp: new Date().toISOString()
            });
            
            logger.info(`Whiteboard: Benutzer ${userId} hat bei Disconnect ${roomName} verlassen (${participantsCount} Teilnehmer verbleiben)`);
            
            // Raum löschen, wenn leer
            if (participantsCount === 0) {
              whiteboardRooms.delete(whiteboardId);
              
              // Optional: Gespeicherte Elemente löschen, wenn der Raum leer ist
              whiteboardElements.delete(whiteboardId);
              
              logger.info(`Whiteboard: Raum ${roomName} wurde bei Disconnect gelöscht`);
            }
          }
        }
      } catch (error) {
        logger.error(`Whiteboard: Fehler beim Disconnect-Cleanup: ${error.message}`);
      }
    });
  });
  
  logger.info('Simples Whiteboard-Modul erfolgreich initialisiert');
  return true;
}

module.exports = initWhiteboardModule; 