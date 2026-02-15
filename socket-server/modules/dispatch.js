/**
 * Dispatch-Modul für den Socket.io-Server
 * Verarbeitet Updates für Dispatches und verteilt sie an alle relevanten Clients
 */

const logger = require('../utils/logger');
const { createLogger, format, transports } = require('winston');
const { combine, timestamp, printf, colorize } = format;

// Spezifischer Logger für Dispatch-Updates
const dispatchLogger = createLogger({
  level: 'info',
  format: combine(
    timestamp(),
    printf(({ level, message, timestamp }) => {
      return `${timestamp} [${level.toUpperCase()}]: ${message}`;
    })
  ),
  transports: [
    new transports.File({ filename: 'logs/dispatch.log', maxsize: 5242880 }),
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

/**
 * Dispatch-Modul initialisieren
 * @param {Object} io - Socket.io-Serverinstanz
 */
module.exports = function(io) {
  dispatchLogger.info('Initialisiere Dispatch-Modul...');

  // Handler für die Hauptverbindung
  io.on('connection', (socket) => {
    const userId = socket.user?.id || 'unknown';
    const userAuthority = socket.user?.authority || 'unknown';
    
    dispatchLogger.info(`Dispatch-Modul: Neue Verbindung von User ${userId} (${socket.id})`);

    // Handler für Dispatch-Updates
    socket.on('dispatch:update', (data) => {
      // Protokolliere das eingehende Update
      dispatchLogger.info(`Dispatch-Update empfangen von User ${userId} (${socket.id}): ${JSON.stringify(data)}`);
      
      try {
        // Validiere die eingehenden Daten
        if (!data || !data.type) {
          dispatchLogger.warn(`Ungültiges Dispatch-Update-Format von User ${userId}: ${JSON.stringify(data)}`);
          return;
        }
        
        // Erweitere die Daten um den Absender (Socket ID und User ID)
        const updateData = {
          ...data,
          sender: userId,              // User ID für Logging/Debugging
          senderSocketId: socket.id,   // Socket ID für Tab-Unterscheidung
          authority: userAuthority,
          serverTimestamp: new Date().toISOString()
        };
        
        // Broadcast an alle Clients (außer dem Absender)
        dispatchLogger.info(`Broadcast Dispatch-Update an alle Clients (Typ: ${data.type})`);
        socket.broadcast.emit('dispatch:update', updateData);
        
        // Optional: Bestätigung an den Absender senden
        socket.emit('dispatch:update:ack', {
          success: true,
          message: 'Update erfolgreich verarbeitet',
          originalData: data,
          timestamp: new Date().toISOString()
        });

      } catch (error) {
        dispatchLogger.error(`Fehler bei der Verarbeitung des Dispatch-Updates: ${error.message}`);
        dispatchLogger.error(error.stack);
        
        // Fehler an den Client melden
        socket.emit('dispatch:update:error', {
          success: false,
          message: 'Fehler bei der Verarbeitung des Updates',
          error: error.message
        });
      }
    });

    // Trennung behandeln
    socket.on('disconnect', () => {
      dispatchLogger.info(`Dispatch-Modul: Verbindung getrennt von User ${userId} (${socket.id})`);
    });
  });

  dispatchLogger.info('Dispatch-Modul initialisiert');
}; 