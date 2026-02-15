const logger = require('../utils/logger');

/**
 * Status-Modul für Socket.io
 * Implementiert Funktionen zur Verwaltung und Aktualisierung von Benutzer- und Systemstatus
 * 
 * @param {Object} io - Socket.io-Serverinstanz
 */
function statusModule(io) {
  // Status-Namespace erstellen
  const statusNamespace = io.of('/status');
  
  // Aktive Benutzer verwalten
  const activeUsers = new Map();
  
  // Verbindung zum Status-Namespace
  statusNamespace.on('connection', (socket) => {
    const userId = socket.user?.id || 'unknown';
    logger.info(`Benutzer ${userId} hat sich mit dem Status-Namespace verbunden`);
    
    // Benutzer als aktiv markieren
    if (userId !== 'unknown') {
      addActiveUser(userId, {
        id: userId,
        username: socket.user.username || 'Unbekannt',
        status: 'online',
        lastActivity: new Date().toISOString(),
        socketId: socket.id
      });
      
      // Status an alle Benutzer senden (nur ID und Status, nicht alle Details)
      broadcastUserStatus(userId, 'online');
    }
    
    // Status aktualisieren
    socket.on('status:update', (data) => {
      try {
        if (!data.status) {
          return socket.emit('error', { 
            message: 'Ungültiger Status', 
            code: 'INVALID_STATUS' 
          });
        }
        
        if (!isValidStatus(data.status)) {
          return socket.emit('error', { 
            message: 'Ungültiger Status-Wert', 
            code: 'INVALID_STATUS_VALUE' 
          });
        }
        
        // Status aktualisieren
        updateUserStatus(userId, data.status, data.statusMessage);
        
        // Status an alle Benutzer senden
        broadcastUserStatus(userId, data.status, data.statusMessage);
        
        // Bestätigung an den Absender
        socket.emit('status:updated', { 
          status: data.status,
          timestamp: new Date().toISOString()
        });
        
      } catch (error) {
        logger.error(`Fehler beim Aktualisieren des Status: ${error.message}`);
        socket.emit('error', { 
          message: 'Status konnte nicht aktualisiert werden', 
          code: 'STATUS_UPDATE_FAILED' 
        });
      }
    });
    
    // Aktive Benutzer abrufen
    socket.on('status:get_active_users', () => {
      try {
        // Aktive Benutzer (mit eingeschränkten Informationen) zurückgeben
        const users = Array.from(activeUsers.values()).map(user => ({
          id: user.id,
          username: user.username,
          status: user.status,
          statusMessage: user.statusMessage,
          lastActivity: user.lastActivity
        }));
        
        socket.emit('status:active_users', { users });
        
      } catch (error) {
        logger.error(`Fehler beim Abrufen aktiver Benutzer: ${error.message}`);
        socket.emit('error', { 
          message: 'Aktive Benutzer konnten nicht abgerufen werden', 
          code: 'GET_ACTIVE_USERS_FAILED' 
        });
      }
    });
    
    // Aktivität eines Benutzers
    socket.on('status:activity', () => {
      if (userId !== 'unknown') {
        const user = activeUsers.get(userId);
        if (user) {
          user.lastActivity = new Date().toISOString();
          activeUsers.set(userId, user);
        }
      }
    });
    
    // Systemstatus abrufen
    socket.on('status:system', () => {
      try {
        const systemStatus = getSystemStatus();
        socket.emit('status:system_info', systemStatus);
      } catch (error) {
        logger.error(`Fehler beim Abrufen des Systemstatus: ${error.message}`);
        socket.emit('error', { 
          message: 'Systemstatus konnte nicht abgerufen werden', 
          code: 'GET_SYSTEM_STATUS_FAILED' 
        });
      }
    });
    
    // Verbindung getrennt
    socket.on('disconnect', () => {
      logger.info(`Benutzer ${userId} hat die Verbindung zum Status-Namespace getrennt`);
      
      // Prüfen, ob es noch andere aktive Verbindungen des Benutzers gibt
      const userStillActive = Array.from(io.sockets.sockets.values())
        .some(s => s.id !== socket.id && s.user && s.user.id === userId);
      
      if (!userStillActive) {
        // Benutzer als offline markieren
        if (userId !== 'unknown') {
          updateUserStatus(userId, 'offline');
          
          // Nach 60 Sekunden prüfen, ob der Benutzer noch offline ist
          setTimeout(() => {
            const user = activeUsers.get(userId);
            if (user && user.status === 'offline') {
              // Benutzer aus der Liste aktiver Benutzer entfernen
              activeUsers.delete(userId);
              
              // Status an alle Benutzer senden
              broadcastUserStatus(userId, 'offline');
              
              logger.debug(`Benutzer ${userId} wurde aus der Liste aktiver Benutzer entfernt`);
            }
          }, 60000);
          
          // Status an alle Benutzer senden
          broadcastUserStatus(userId, 'offline');
        }
      }
    });
  });
  
  // Interval für Aufräumen inaktiver Benutzer (alle 5 Minuten)
  setInterval(() => {
    cleanupInactiveUsers();
  }, 5 * 60 * 1000);
  
  /**
   * Fügt einen aktiven Benutzer hinzu
   * @param {string} userId - Benutzer-ID
   * @param {Object} userData - Benutzerdaten
   */
  function addActiveUser(userId, userData) {
    activeUsers.set(userId, userData);
    logger.debug(`Benutzer ${userId} zur Liste aktiver Benutzer hinzugefügt`);
  }
  
  /**
   * Aktualisiert den Status eines Benutzers
   * @param {string} userId - Benutzer-ID
   * @param {string} status - Neuer Status
   * @param {string} [statusMessage] - Optionale Statusnachricht
   */
  function updateUserStatus(userId, status, statusMessage = null) {
    const user = activeUsers.get(userId);
    
    if (user) {
      user.status = status;
      user.lastActivity = new Date().toISOString();
      
      if (statusMessage !== null) {
        user.statusMessage = statusMessage;
      }
      
      activeUsers.set(userId, user);
      logger.debug(`Status von Benutzer ${userId} auf '${status}' aktualisiert`);
    }
  }
  
  /**
   * Sendet den Status eines Benutzers an alle verbundenen Clients
   * @param {string} userId - Benutzer-ID
   * @param {string} status - Status
   * @param {string} [statusMessage] - Optionale Statusnachricht
   */
  function broadcastUserStatus(userId, status, statusMessage = undefined) {
    const user = activeUsers.get(userId);
    
    if (user) {
      statusNamespace.emit('status:user_update', {
        userId,
        username: user.username,
        status,
        statusMessage: statusMessage !== undefined ? statusMessage : user.statusMessage,
        timestamp: new Date().toISOString()
      });
      
      logger.debug(`Status-Broadcast für Benutzer ${userId}: ${status}`);
    }
  }
  
  /**
   * Bereinigt inaktive Benutzer
   */
  function cleanupInactiveUsers() {
    const now = new Date();
    const inactivityThreshold = 30 * 60 * 1000; // 30 Minuten
    
    for (const [userId, user] of activeUsers.entries()) {
      const lastActivity = new Date(user.lastActivity);
      
      // Wenn der Benutzer seit mehr als 30 Minuten inaktiv ist und nicht bereits als offline markiert
      if (now - lastActivity > inactivityThreshold && user.status !== 'offline') {
        updateUserStatus(userId, 'away');
        broadcastUserStatus(userId, 'away');
        logger.debug(`Benutzer ${userId} aufgrund von Inaktivität als 'away' markiert`);
      }
    }
  }
  
  /**
   * Überprüft, ob ein Status gültig ist
   * @param {string} status - Zu prüfender Status
   * @returns {boolean} Gültig oder nicht
   */
  function isValidStatus(status) {
    const validStatuses = ['online', 'offline', 'away', 'busy', 'invisible'];
    return validStatuses.includes(status);
  }
  
  /**
   * Ruft den aktuellen Systemstatus ab
   * @returns {Object} Systemstatus
   */
  function getSystemStatus() {
    return {
      uptime: process.uptime(),
      timestamp: new Date().toISOString(),
      activeConnections: io.engine.clientsCount,
      activeUsers: activeUsers.size,
      memory: process.memoryUsage(),
      load: process.cpuUsage()
    };
  }
  
  return statusNamespace;
}

module.exports = statusModule; 