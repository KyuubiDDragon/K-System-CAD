const logger = require('../utils/logger');
const chatModule = require('./chat');
const notificationModule = require('./notification');
const statusModule = require('./status');
const dispatchModule = require('./dispatch');
// Das ursprüngliche Whiteboard-Modul mit MySQL-Abhängigkeit
// const whiteboardModule = require('./whiteboard');
// Simple Version ohne MySQL-Abhängigkeit
const whiteboardSimpleModule = require('./whiteboard-simple');

/**
 * Registriert alle Funktionsmodule beim Socket.io-Server
 * @param {Object} io - Socket.io-Serverinstanz
 */
function registerModules(io) {
  logger.info('Registriere Socket.io-Module...');
  
  try {
    // Chat-Modul registrieren
    chatModule(io);
    logger.info('Chat-Modul registriert');
    
    // Benachrichtigungsmodul registrieren
    notificationModule(io);
    logger.info('Benachrichtigungsmodul registriert');
    
    // Status-Modul registrieren
    statusModule(io);
    logger.info('Status-Modul registriert');
    
    // Dispatch-Modul registrieren
    dispatchModule(io);
    logger.info('Dispatch-Modul registriert');
    
    // Whiteboard-Modul registrieren - einfache Version ohne MySQL
    whiteboardSimpleModule(io);
    logger.info('Einfaches Whiteboard-Modul registriert');
    
    logger.info('Alle Socket.io-Module erfolgreich registriert');
  } catch (error) {
    logger.error(`Fehler beim Registrieren der Module: ${error.message}`);
    throw error;
  }
}

module.exports = registerModules; 