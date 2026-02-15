/**
 * Whiteboard-Modul für Socket.io
 * Ermöglicht kollaboratives Zeichnen mit Datenbankintegration
 */

const logger = require('../utils/logger');
const whiteboardDb = require('./whiteboard-db');

// In-Memory-Speicher für aktive Whiteboard-Sitzungen
const activeWhiteboards = new Map();

// Aktuelle Benutzer-Sitzungen für Whiteboards
const activeUsers = new Map();

// Whiteboard-Modul initialisieren
const initWhiteboardModule = async (io) => {
  // Datenbank-Verbindung initialisieren
  const dbConnected = await whiteboardDb.init();
  if (!dbConnected) {
    logger.error('Whiteboard-Modul: Datenbank-Verbindung konnte nicht hergestellt werden.');
    return false;
  }
  
  logger.info('Whiteboard-Modul: Datenbank-Verbindung hergestellt.');

  // Namespace für Whiteboard-Events
  const whiteboardNamespace = io.of('/whiteboard');

  // Bei neuer Socket-Verbindung
  io.on('connection', (socket) => {
    // Whiteboard erstellen
    socket.on('whiteboard:create', async (data) => {
      try {
        const userId = socket.user?.id;
        const authorityId = socket.user?.authority_id;
        
        if (!userId || !authorityId) {
          return socket.emit('whiteboard:error', { message: 'Nicht authentifiziert' });
        }

        const { name, accessType, joinCode, backgroundColor } = data;
        
        // Zufälligen Beitrittscode generieren, wenn nötig
        const finalJoinCode = accessType === 'code' 
          ? (joinCode || Math.random().toString(36).substr(2, 6).toUpperCase()) 
          : null;
        
        // Whiteboard in Datenbank erstellen
        const whiteboard = await whiteboardDb.createWhiteboard({
          name: name || 'Neues Whiteboard',
          owner_id: userId,
          authority_id: authorityId,
          access_type: accessType || 'private',
          join_code: finalJoinCode,
          background_color: backgroundColor || '#FFFFFF'
        });

        // Whiteboard-Raum betreten
        socket.join(`whiteboard:${whiteboard.id}`);

        logger.info(`Whiteboard erstellt: ${whiteboard.id} von Benutzer ${userId}`);

        // Bestätigung an Client senden
        socket.emit('whiteboard:created', {
          whiteboard: {
            id: whiteboard.id,
            name: whiteboard.name,
            accessType: whiteboard.access_type,
            joinCode: whiteboard.join_code,
            createdBy: whiteboard.owner_id,
            participants: 1,
            createdAt: whiteboard.created_at,
            backgroundColor: whiteboard.background_color
          }
        });
      } catch (error) {
        logger.error(`Fehler beim Erstellen des Whiteboards: ${error.message}`);
        socket.emit('whiteboard:error', { message: 'Fehler beim Erstellen des Whiteboards' });
      }
    });

    // Verfügbare öffentliche Whiteboards abrufen
    socket.on('whiteboard:list', async () => {
      try {
        const authorityId = socket.user?.authority_id;
        
        if (!authorityId) {
          return socket.emit('whiteboard:error', { message: 'Nicht authentifiziert' });
        }
        
        // Öffentliche Whiteboards aus der Datenbank abrufen
        const publicWhiteboards = await whiteboardDb.getPublicWhiteboards(authorityId);
        
        // Transformieren für Client-Format
        const clientData = publicWhiteboards.map(wb => ({
          id: wb.id,
          name: wb.name,
          createdBy: wb.owner_id,
          participants: wb.participants || 0,
          createdAt: wb.created_at
        }));

        socket.emit('whiteboard:list', { whiteboards: clientData });
      } catch (error) {
        logger.error(`Fehler beim Abrufen der öffentlichen Whiteboards: ${error.message}`);
        socket.emit('whiteboard:error', { message: 'Fehler beim Abrufen der Whiteboards' });
      }
    });

    // Eigene Whiteboards abrufen
    socket.on('whiteboard:my-boards', async () => {
      try {
        const userId = socket.user?.id;
        const authorityId = socket.user?.authority_id;
        
        if (!userId || !authorityId) {
          return socket.emit('whiteboard:error', { message: 'Nicht authentifiziert' });
        }

        // Eigene Whiteboards aus der Datenbank abrufen
        const myWhiteboards = await whiteboardDb.getUserWhiteboards(userId, authorityId);
        
        // Transformieren für Client-Format
        const clientData = myWhiteboards.map(wb => ({
          id: wb.id,
          name: wb.name,
          accessType: wb.access_type,
          joinCode: wb.is_owner ? wb.join_code : undefined,
          createdBy: wb.owner_id,
          isOwner: !!wb.is_owner,
          participants: wb.participants || 0,
          createdAt: wb.created_at,
          backgroundColor: wb.background_color
        }));

        socket.emit('whiteboard:my-boards', { whiteboards: clientData });
      } catch (error) {
        logger.error(`Fehler beim Abrufen der eigenen Whiteboards: ${error.message}`);
        socket.emit('whiteboard:error', { message: 'Fehler beim Abrufen der Whiteboards' });
      }
    });

    // Mit Code beitreten
    socket.on('whiteboard:join-with-code', async (data) => {
      try {
        const userId = socket.user?.id;
        const authorityId = socket.user?.authority_id;
        
        if (!userId || !authorityId) {
          return socket.emit('whiteboard:error', { message: 'Nicht authentifiziert' });
        }
        
        const { joinCode } = data;
        if (!joinCode) {
          return socket.emit('whiteboard:error', { message: 'Beitrittscode fehlt' });
        }
        
        // Whiteboard mit dem Code finden
        const whiteboard = await whiteboardDb.findWhiteboardByCode(joinCode, authorityId);
        
        if (!whiteboard) {
          return socket.emit('whiteboard:error', { message: 'Kein Whiteboard mit diesem Code gefunden' });
        }
        
        // Zum normalen Beitritts-Flow weiterleiten
        await handleJoinWhiteboard(socket, { whiteboardId: whiteboard.id });
      } catch (error) {
        logger.error(`Fehler beim Beitreten mit Code: ${error.message}`);
        socket.emit('whiteboard:error', { message: 'Fehler beim Beitreten mit Code' });
      }
    });

    // Whiteboard beitreten
    socket.on('whiteboard:join', (data) => handleJoinWhiteboard(socket, data));

    // Whiteboard-Element zeichnen/hinzufügen
    socket.on('whiteboard:draw', async (data) => {
      try {
        const userId = socket.user?.id;
        const authorityId = socket.user?.authority_id;
        
        if (!userId || !authorityId) {
          return socket.emit('whiteboard:error', { message: 'Nicht authentifiziert' });
        }

        const { whiteboardId, element } = data;
        
        // Whiteboard-Session in Memory abrufen
        let whiteboardSession = activeWhiteboards.get(whiteboardId);
        
        // Wenn keine aktive Session, Whiteboard aus Datenbank laden
        if (!whiteboardSession) {
          const whiteboard = await whiteboardDb.getWhiteboard(whiteboardId, authorityId);
          if (!whiteboard) {
            return socket.emit('whiteboard:error', { message: 'Whiteboard nicht gefunden' });
          }
          
          // Neue Session mit den Elementen aus der Datenbank erstellen
          const elements = await whiteboardDb.getWhiteboardElements(whiteboardId, authorityId);
          
          whiteboardSession = {
            id: whiteboard.id,
            authorityId: whiteboard.authority_id,
            elements: elements,
            participants: []
          };
          
          activeWhiteboards.set(whiteboardId, whiteboardSession);
        }
        
        // Berechtigung prüfen
        if (!isUserActive(whiteboardId, userId)) {
          return socket.emit('whiteboard:error', { message: 'Keine Berechtigung für dieses Whiteboard' });
        }
        
        // Element mit Benutzer-ID und Authority-ID versehen
        const drawElement = {
          ...element,
          user_id: userId,
          authority_id: authorityId
        };
        
        // Element in die Datenbank speichern
        await whiteboardDb.saveElement(whiteboardId, drawElement);
        
        // Element zur Session hinzufügen
        if (drawElement.id) {
          whiteboardSession.elements[drawElement.id] = drawElement;
        }

        // Zeichnung an alle anderen Teilnehmer senden
        socket.to(`whiteboard:${whiteboardId}`).emit('whiteboard:draw', {
          element: drawElement
        });
      } catch (error) {
        logger.error(`Fehler beim Zeichnen auf Whiteboard: ${error.message}`);
        socket.emit('whiteboard:error', { message: 'Fehler beim Zeichnen' });
      }
    });

    // Whiteboard-Element löschen
    socket.on('whiteboard:delete-element', async (data) => {
      try {
        const userId = socket.user?.id;
        const authorityId = socket.user?.authority_id;
        
        if (!userId || !authorityId) {
          return socket.emit('whiteboard:error', { message: 'Nicht authentifiziert' });
        }

        const { whiteboardId, elementId } = data;
        
        // Whiteboard-Session in Memory abrufen
        const whiteboardSession = activeWhiteboards.get(whiteboardId);
        if (!whiteboardSession) {
          return socket.emit('whiteboard:error', { message: 'Whiteboard nicht gefunden' });
        }
        
        // Berechtigung prüfen
        if (!isUserActive(whiteboardId, userId)) {
          return socket.emit('whiteboard:error', { message: 'Keine Berechtigung für dieses Whiteboard' });
        }
        
        // Element in der Datenbank als gelöscht markieren
        await whiteboardDb.deleteElement(whiteboardId, elementId, userId, authorityId);
        
        // Element aus der Session entfernen
        if (whiteboardSession.elements[elementId]) {
          delete whiteboardSession.elements[elementId];
        }
        
        // Löschaktion an alle anderen Teilnehmer senden
        socket.to(`whiteboard:${whiteboardId}`).emit('whiteboard:delete-element', {
          elementId
        });
      } catch (error) {
        logger.error(`Fehler beim Löschen eines Whiteboard-Elements: ${error.message}`);
        socket.emit('whiteboard:error', { message: 'Fehler beim Löschen eines Elements' });
      }
    });

    // Whiteboard verlassen
    socket.on('whiteboard:leave', (data) => {
      try {
        const userId = socket.user?.id;
        if (!userId) {
          return;
        }

        const { whiteboardId } = data;
        
        // Aus Whiteboard-Raum austreten
        socket.leave(`whiteboard:${whiteboardId}`);
        
        // Benutzer aus aktiven Teilnehmern entfernen
        removeUserFromSession(whiteboardId, userId);
        
        // Andere Teilnehmer benachrichtigen
        const whiteboardSession = activeWhiteboards.get(whiteboardId);
        if (whiteboardSession) {
          const participantCount = whiteboardSession.participants.length;
          
          socket.to(`whiteboard:${whiteboardId}`).emit('whiteboard:user-left', {
            userId,
            participantCount
          });
          
          // Wenn keine Teilnehmer mehr, Session aus dem Speicher entfernen
          if (participantCount === 0) {
            activeWhiteboards.delete(whiteboardId);
          }
        }
        
        logger.info(`Benutzer ${userId} hat Whiteboard ${whiteboardId} verlassen`);
      } catch (error) {
        logger.error(`Fehler beim Verlassen des Whiteboards: ${error.message}`);
      }
    });

    // Whiteboard löschen
    socket.on('whiteboard:delete', async (data) => {
      try {
        const userId = socket.user?.id;
        const authorityId = socket.user?.authority_id;
        
        if (!userId || !authorityId) {
          return socket.emit('whiteboard:error', { message: 'Nicht authentifiziert' });
        }

        const { whiteboardId } = data;
        
        // Whiteboard in der Datenbank als archiviert markieren
        const result = await whiteboardDb.deleteWhiteboard(whiteboardId, userId, authorityId);
        
        if (!result.success) {
          return socket.emit('whiteboard:error', { message: result.message || 'Fehler beim Löschen des Whiteboards' });
        }
        
        // Alle Teilnehmer benachrichtigen
        io.to(`whiteboard:${whiteboardId}`).emit('whiteboard:deleted', {
          whiteboardId
        });
        
        // Session aus dem Speicher entfernen
        activeWhiteboards.delete(whiteboardId);
        
        // Client bestätigen
        socket.emit('whiteboard:deleted', { success: true });
        
        logger.info(`Whiteboard ${whiteboardId} wurde von Benutzer ${userId} gelöscht`);
      } catch (error) {
        logger.error(`Fehler beim Löschen des Whiteboards: ${error.message}`);
        socket.emit('whiteboard:error', { message: 'Fehler beim Löschen des Whiteboards' });
      }
    });

    // Snapshot speichern
    socket.on('whiteboard:save-snapshot', async (data) => {
      try {
        const userId = socket.user?.id;
        const authorityId = socket.user?.authority_id;
        
        if (!userId || !authorityId) {
          return socket.emit('whiteboard:error', { message: 'Nicht authentifiziert' });
        }

        const { whiteboardId, name, thumbnail } = data;
        
        // Snapshot in der Datenbank speichern
        const result = await whiteboardDb.createSnapshot(
          whiteboardId, 
          userId, 
          authorityId, 
          name || `Snapshot vom ${new Date().toLocaleString()}`,
          thumbnail
        );
        
        if (result.success) {
          socket.emit('whiteboard:snapshot-saved', { success: true });
          logger.info(`Snapshot für Whiteboard ${whiteboardId} wurde von Benutzer ${userId} erstellt`);
        }
      } catch (error) {
        logger.error(`Fehler beim Speichern des Snapshots: ${error.message}`);
        socket.emit('whiteboard:error', { message: 'Fehler beim Speichern des Snapshots' });
      }
    });
    
    // Trennung des Sockets
    socket.on('disconnect', () => {
      const userId = socket.user?.id;
      if (!userId) return;
      
      // Alle aktiven Sessions des Benutzers beenden
      const userSessions = activeUsers.get(userId) || [];
      userSessions.forEach(whiteboardId => {
        removeUserFromSession(whiteboardId, userId);
        
        // Andere Teilnehmer benachrichtigen
        const whiteboardSession = activeWhiteboards.get(whiteboardId);
        if (whiteboardSession) {
          const participantCount = whiteboardSession.participants.length;
          
          socket.to(`whiteboard:${whiteboardId}`).emit('whiteboard:user-left', {
            userId,
            participantCount
          });
          
          // Wenn keine Teilnehmer mehr, Session aus dem Speicher entfernen
          if (participantCount === 0) {
            activeWhiteboards.delete(whiteboardId);
          }
        }
      });
      
      // Benutzersitzungen löschen
      activeUsers.delete(userId);
    });
  });
  
  // Hilfsfunktion zum Beitreten eines Whiteboards
  const handleJoinWhiteboard = async (socket, data) => {
    try {
      const userId = socket.user?.id;
      const authorityId = socket.user?.authority_id;
      const username = socket.user?.username;
      
      if (!userId || !authorityId) {
        return socket.emit('whiteboard:error', { message: 'Nicht authentifiziert' });
      }

      const { whiteboardId } = data;
      
      // Whiteboard aus Datenbank laden
      const whiteboard = await whiteboardDb.getWhiteboard(whiteboardId, authorityId);
      if (!whiteboard) {
        return socket.emit('whiteboard:error', { message: 'Whiteboard nicht gefunden' });
      }

      // Benutzer dem Whiteboard in der Datenbank hinzufügen
      const userResult = await whiteboardDb.addUserToWhiteboard(
        whiteboardId, 
        userId, 
        authorityId, 
        whiteboard.owner_id === userId ? 'owner' : 'editor'
      );

      // Whiteboard-Raum betreten
      socket.join(`whiteboard:${whiteboardId}`);
      
      // Benutzer zur aktiven Session hinzufügen
      addUserToSession(whiteboardId, userId);

      // Elemente aus der Datenbank laden
      let whiteboardContent = {};
      
      // Aktive Session suchen oder erstellen
      let whiteboardSession = activeWhiteboards.get(whiteboardId);
      if (whiteboardSession) {
        whiteboardContent = whiteboardSession.elements;
      } else {
        // Elemente aus der Datenbank laden
        whiteboardContent = await whiteboardDb.getWhiteboardElements(whiteboardId, authorityId);
        
        // Neue Session erstellen
        whiteboardSession = {
          id: whiteboard.id,
          authorityId: whiteboard.authority_id,
          elements: whiteboardContent,
          participants: [userId]
        };
        
        activeWhiteboards.set(whiteboardId, whiteboardSession);
      }
      
      // Andere Teilnehmer benachrichtigen
      if (userResult.isNew) {
        socket.to(`whiteboard:${whiteboardId}`).emit('whiteboard:user-joined', {
          userId,
          username,
          participantCount: whiteboardSession.participants.length
        });
      }

      logger.info(`Benutzer ${userId} ist Whiteboard ${whiteboardId} beigetreten`);

      // Whiteboard-Daten an Client senden
      socket.emit('whiteboard:joined', {
        whiteboard: {
          id: whiteboard.id,
          name: whiteboard.name,
          createdBy: whiteboard.owner_id,
          participants: whiteboardSession.participants.length,
          createdAt: whiteboard.created_at,
          content: whiteboardContent,
          backgroundColor: whiteboard.background_color
        }
      });
    } catch (error) {
      logger.error(`Fehler beim Beitreten zum Whiteboard: ${error.message}`);
      socket.emit('whiteboard:error', { message: 'Fehler beim Beitreten zum Whiteboard' });
    }
  };
  
  // Hilfsfunktion zum Hinzufügen eines Benutzers zu einer aktiven Session
  const addUserToSession = (whiteboardId, userId) => {
    // Zur Session hinzufügen
    let whiteboardSession = activeWhiteboards.get(whiteboardId);
    if (!whiteboardSession) {
      whiteboardSession = {
        id: whiteboardId,
        participants: [],
        elements: {}
      };
      activeWhiteboards.set(whiteboardId, whiteboardSession);
    }
    
    // Nur hinzufügen, wenn nicht bereits vorhanden
    if (!whiteboardSession.participants.includes(userId)) {
      whiteboardSession.participants.push(userId);
    }
    
    // Zur Benutzer-Session-Map hinzufügen
    let userSessions = activeUsers.get(userId);
    if (!userSessions) {
      userSessions = [];
      activeUsers.set(userId, userSessions);
    }
    
    // Nur hinzufügen, wenn nicht bereits vorhanden
    if (!userSessions.includes(whiteboardId)) {
      userSessions.push(whiteboardId);
    }
  };
  
  // Hilfsfunktion zum Entfernen eines Benutzers aus einer aktiven Session
  const removeUserFromSession = (whiteboardId, userId) => {
    // Aus der Session entfernen
    const whiteboardSession = activeWhiteboards.get(whiteboardId);
    if (whiteboardSession) {
      whiteboardSession.participants = whiteboardSession.participants.filter(id => id !== userId);
    }
    
    // Aus der Benutzer-Session-Map entfernen
    const userSessions = activeUsers.get(userId);
    if (userSessions) {
      activeUsers.set(userId, userSessions.filter(id => id !== whiteboardId));
      
      // Wenn keine Sessions mehr, Benutzer ganz entfernen
      if (userSessions.length === 0) {
        activeUsers.delete(userId);
      }
    }
  };
  
  // Hilfsfunktion zum Prüfen, ob ein Benutzer in einer aktiven Session ist
  const isUserActive = (whiteboardId, userId) => {
    const whiteboardSession = activeWhiteboards.get(whiteboardId);
    return whiteboardSession && whiteboardSession.participants.includes(userId);
  };
  
  return true; // Erfolgreiche Initialisierung
};

module.exports = initWhiteboardModule; 