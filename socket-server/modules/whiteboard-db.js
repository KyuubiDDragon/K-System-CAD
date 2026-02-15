/**
 * Whiteboard-Datenbankmodul für Socket.io
 * Erweitert die In-Memory-Funktionalität mit Datenbank-Integration
 */

const logger = require('../utils/logger');
const mysql = require('mysql2/promise');
const { DB_CONFIG } = require('../config');

// Datenbank-Verbindung initialisieren
let dbPool;

// Hilfs-Funktionen für die Datenbank-Integration
const initDbConnection = async () => {
  try {
    dbPool = mysql.createPool({
      ...DB_CONFIG,
      waitForConnections: true,
      connectionLimit: 10,
      queueLimit: 0
    });
    
    logger.info('Whiteboard-Datenbank-Verbindung initialisiert');
    
    // Teste die Verbindung
    const conn = await dbPool.getConnection();
    conn.release();
    logger.info('Whiteboard-Datenbank-Verbindung erfolgreich getestet');
    
    return true;
  } catch (error) {
    logger.error(`Fehler bei der Initialisierung der Whiteboard-Datenbank: ${error.message}`);
    return false;
  }
};

// CRUD-Operationen für Whiteboards

// Whiteboard aus der Datenbank abrufen
const getWhiteboard = async (whiteboardId, authorityId) => {
  try {
    const [rows] = await dbPool.execute(
      `SELECT * FROM kdd_whiteboards 
       WHERE id = ? AND authority_id = ?`,
      [whiteboardId, authorityId]
    );
    
    if (rows.length === 0) {
      return null;
    }
    
    return rows[0];
  } catch (error) {
    logger.error(`Fehler beim Abrufen des Whiteboards: ${error.message}`);
    throw error;
  }
};

// Alle Whiteboards eines Benutzers abrufen
const getUserWhiteboards = async (userId, authorityId) => {
  try {
    const [rows] = await dbPool.execute(
      `SELECT w.*, 
              (w.owner_id = ?) as is_owner,
              (SELECT COUNT(*) FROM kdd_whiteboard_users WHERE whiteboard_id = w.id) as participants
       FROM kdd_whiteboards w
       JOIN kdd_whiteboard_users wu ON w.id = wu.whiteboard_id
       WHERE wu.user_id = ? 
       AND w.authority_id = ?
       AND w.is_archived = false
       ORDER BY w.updated_at DESC`,
      [userId, userId, authorityId]
    );
    
    return rows;
  } catch (error) {
    logger.error(`Fehler beim Abrufen der Benutzer-Whiteboards: ${error.message}`);
    throw error;
  }
};

// Öffentliche Whiteboards abrufen
const getPublicWhiteboards = async (authorityId) => {
  try {
    const [rows] = await dbPool.execute(
      `SELECT w.*, 
              (SELECT COUNT(*) FROM kdd_whiteboard_users WHERE whiteboard_id = w.id) as participants
       FROM kdd_whiteboards w
       WHERE w.access_type = 'public' 
       AND w.authority_id = ?
       AND w.is_archived = false
       ORDER BY w.updated_at DESC`,
      [authorityId]
    );
    
    return rows;
  } catch (error) {
    logger.error(`Fehler beim Abrufen der öffentlichen Whiteboards: ${error.message}`);
    throw error;
  }
};

// Whiteboard mit Code finden
const findWhiteboardByCode = async (joinCode, authorityId) => {
  try {
    const [rows] = await dbPool.execute(
      `SELECT * FROM kdd_whiteboards 
       WHERE join_code = ? 
       AND authority_id = ?
       AND access_type = 'code'
       AND is_archived = false`,
      [joinCode, authorityId]
    );
    
    if (rows.length === 0) {
      return null;
    }
    
    return rows[0];
  } catch (error) {
    logger.error(`Fehler beim Suchen des Whiteboards mit Code: ${error.message}`);
    throw error;
  }
};

// Neues Whiteboard erstellen
const createWhiteboard = async (whiteboard) => {
  try {
    const { name, owner_id, authority_id, access_type, join_code, background_color } = whiteboard;
    
    const [result] = await dbPool.execute(
      `INSERT INTO kdd_whiteboards 
       (name, owner_id, authority_id, access_type, join_code, background_color) 
       VALUES (?, ?, ?, ?, ?, ?)`,
      [name, owner_id, authority_id, access_type, join_code, background_color]
    );
    
    // Ersteller als ersten Benutzer hinzufügen
    if (result.insertId) {
      await dbPool.execute(
        `INSERT INTO kdd_whiteboard_users 
         (whiteboard_id, user_id, authority_id, role, joined_at) 
         VALUES (?, ?, ?, 'owner', NOW())`,
        [result.insertId, owner_id, authority_id]
      );
    }
    
    return {
      id: result.insertId,
      ...whiteboard
    };
  } catch (error) {
    logger.error(`Fehler beim Erstellen des Whiteboards: ${error.message}`);
    throw error;
  }
};

// Whiteboard löschen
const deleteWhiteboard = async (whiteboardId, userId, authorityId) => {
  try {
    // Prüfen, ob Benutzer Besitzer ist
    const [ownerCheck] = await dbPool.execute(
      `SELECT COUNT(*) as is_owner 
       FROM kdd_whiteboards 
       WHERE id = ? AND owner_id = ? AND authority_id = ?`,
      [whiteboardId, userId, authorityId]
    );
    
    if (ownerCheck[0].is_owner === 0) {
      return { success: false, message: 'Keine Berechtigung zum Löschen' };
    }
    
    // Als archiviert markieren statt zu löschen
    await dbPool.execute(
      `UPDATE kdd_whiteboards SET is_archived = true WHERE id = ?`,
      [whiteboardId]
    );
    
    return { success: true };
  } catch (error) {
    logger.error(`Fehler beim Löschen des Whiteboards: ${error.message}`);
    throw error;
  }
};

// Benutzer einem Whiteboard hinzufügen
const addUserToWhiteboard = async (whiteboardId, userId, authorityId, role = 'viewer') => {
  try {
    // Prüfen, ob Benutzer bereits existiert
    const [existingUser] = await dbPool.execute(
      `SELECT * FROM kdd_whiteboard_users 
       WHERE whiteboard_id = ? AND user_id = ? AND authority_id = ?`,
      [whiteboardId, userId, authorityId]
    );
    
    if (existingUser.length > 0) {
      // Benutzer ist bereits im Whiteboard, nur Aktiv-Zeitstempel aktualisieren
      await dbPool.execute(
        `UPDATE kdd_whiteboard_users SET last_active_at = NOW() 
         WHERE whiteboard_id = ? AND user_id = ? AND authority_id = ?`,
        [whiteboardId, userId, authorityId]
      );
      
      return { success: true, isNew: false, role: existingUser[0].role };
    } else {
      // Neuen Benutzer hinzufügen
      await dbPool.execute(
        `INSERT INTO kdd_whiteboard_users 
         (whiteboard_id, user_id, authority_id, role, joined_at, last_active_at) 
         VALUES (?, ?, ?, ?, NOW(), NOW())`,
        [whiteboardId, userId, authorityId, role]
      );
      
      return { success: true, isNew: true, role };
    }
  } catch (error) {
    logger.error(`Fehler beim Hinzufügen des Benutzers zum Whiteboard: ${error.message}`);
    throw error;
  }
};

// Teilnehmer eines Whiteboards abrufen
const getWhiteboardUsers = async (whiteboardId, authorityId) => {
  try {
    const [rows] = await dbPool.execute(
      `SELECT wu.*, u.username
       FROM kdd_whiteboard_users wu
       JOIN kdd_users u ON wu.user_id = u.id
       WHERE wu.whiteboard_id = ? AND wu.authority_id = ?`,
      [whiteboardId, authorityId]
    );
    
    return rows;
  } catch (error) {
    logger.error(`Fehler beim Abrufen der Whiteboard-Benutzer: ${error.message}`);
    throw error;
  }
};

// Whiteboard-Elemente verwalten
const saveElement = async (whiteboardId, elementData) => {
  try {
    const { id, user_id, authority_id, type, ...data } = elementData;
    
    // Element speichern
    const [result] = await dbPool.execute(
      `INSERT INTO kdd_whiteboard_elements 
       (whiteboard_id, element_id, user_id, authority_id, type, data) 
       VALUES (?, ?, ?, ?, ?, ?)`,
      [whiteboardId, id, user_id, authority_id, type, JSON.stringify(data)]
    );
    
    // Whiteboard-Aktualisierung markieren
    await dbPool.execute(
      `UPDATE kdd_whiteboards SET updated_at = NOW() WHERE id = ?`,
      [whiteboardId]
    );
    
    return { success: true, id: result.insertId };
  } catch (error) {
    logger.error(`Fehler beim Speichern des Whiteboard-Elements: ${error.message}`);
    throw error;
  }
};

// Element als gelöscht markieren
const deleteElement = async (whiteboardId, elementId, userId, authorityId) => {
  try {
    // Als gelöscht markieren statt zu löschen (für Versionierung)
    await dbPool.execute(
      `UPDATE kdd_whiteboard_elements 
       SET deleted_at = NOW() 
       WHERE whiteboard_id = ? AND element_id = ? AND authority_id = ?`,
      [whiteboardId, elementId, authorityId]
    );
    
    // Whiteboard-Aktualisierung markieren
    await dbPool.execute(
      `UPDATE kdd_whiteboards SET updated_at = NOW() WHERE id = ?`,
      [whiteboardId]
    );
    
    return { success: true };
  } catch (error) {
    logger.error(`Fehler beim Löschen des Whiteboard-Elements: ${error.message}`);
    throw error;
  }
};

// Alle aktiven Elemente eines Whiteboards abrufen
const getWhiteboardElements = async (whiteboardId, authorityId) => {
  try {
    const [rows] = await dbPool.execute(
      `SELECT * FROM kdd_whiteboard_elements 
       WHERE whiteboard_id = ? 
       AND authority_id = ? 
       AND deleted_at IS NULL
       ORDER BY created_at ASC`,
      [whiteboardId, authorityId]
    );
    
    // Umformen, sodass die Daten wieder als JSON verfügbar sind
    const elements = {};
    rows.forEach(row => {
      const data = JSON.parse(row.data);
      elements[row.element_id] = {
        id: row.element_id,
        type: row.type,
        userId: row.user_id,
        timestamp: row.created_at,
        ...data
      };
    });
    
    return elements;
  } catch (error) {
    logger.error(`Fehler beim Abrufen der Whiteboard-Elemente: ${error.message}`);
    throw error;
  }
};

// Snapshot erstellen
const createSnapshot = async (whiteboardId, userId, authorityId, name, thumbnail = null) => {
  try {
    await dbPool.execute(
      `INSERT INTO kdd_whiteboard_snapshots 
       (whiteboard_id, user_id, authority_id, name, thumbnail) 
       VALUES (?, ?, ?, ?, ?)`,
      [whiteboardId, userId, authorityId, name, thumbnail]
    );
    
    return { success: true };
  } catch (error) {
    logger.error(`Fehler beim Erstellen des Snapshots: ${error.message}`);
    throw error;
  }
};

// Snapshots eines Whiteboards abrufen
const getWhiteboardSnapshots = async (whiteboardId, authorityId) => {
  try {
    const [rows] = await dbPool.execute(
      `SELECT id, user_id, name, created_at 
       FROM kdd_whiteboard_snapshots 
       WHERE whiteboard_id = ? AND authority_id = ?
       ORDER BY created_at DESC`,
      [whiteboardId, authorityId]
    );
    
    return rows;
  } catch (error) {
    logger.error(`Fehler beim Abrufen der Whiteboard-Snapshots: ${error.message}`);
    throw error;
  }
};

// Snapshot wiederherstellen
const restoreSnapshot = async (snapshotId, userId, authorityId) => {
  try {
    // Verwende die gespeicherte Prozedur zur Wiederherstellung
    await dbPool.execute(
      `CALL restore_whiteboard_snapshot(?, ?, ?)`,
      [snapshotId, userId, authorityId]
    );
    
    return { success: true };
  } catch (error) {
    logger.error(`Fehler bei der Wiederherstellung des Snapshots: ${error.message}`);
    throw error;
  }
};

// Modul initialisieren und exportieren
module.exports = {
  init: initDbConnection,
  getWhiteboard,
  getUserWhiteboards,
  getPublicWhiteboards,
  findWhiteboardByCode,
  createWhiteboard,
  deleteWhiteboard,
  addUserToWhiteboard,
  getWhiteboardUsers,
  saveElement,
  deleteElement,
  getWhiteboardElements,
  createSnapshot,
  getWhiteboardSnapshots,
  restoreSnapshot
}; 