const winston = require('winston');
const fs = require('fs');
const path = require('path');

// Stellen Sie sicher, dass das logs-Verzeichnis existiert
const logDir = path.join(__dirname, '../logs');
if (!fs.existsSync(logDir)) {
  fs.mkdirSync(logDir, { recursive: true });
}

const levels = {
  error: 0,
  warn: 1,
  info: 2,
  http: 3,
  debug: 4,
};

// Adjust log level based on environment and LOG_VERBOSITY
const level = () => {
  const env = process.env.NODE_ENV || 'development';
  const verbosity = process.env.LOG_VERBOSITY || 'normal';
  
  // In development, use a more conservative logging level by default
  const isDevelopment = env === 'development';
  
  if (verbosity === 'verbose') {
    return 'debug';
  } else if (verbosity === 'quiet') {
    return 'warn';
  } else {
    // Normal verbosity
    return isDevelopment ? 'info' : 'info';
  }
};

const colors = {
  error: 'red',
  warn: 'yellow',
  info: 'green',
  http: 'magenta',
  debug: 'white',
};

winston.addColors(colors);

// Custom format to filter out debug messages that are not important
const filterMessages = winston.format((info) => {
  // Always allow error and warn levels
  if (info.level.includes('error') || info.level.includes('warn')) {
    return info;
  }
  
  // Allow info messages about notifications or connection events
  if (info.level.includes('info') && (
      info.message.includes('notification') || 
      info.message.includes('Notification') ||
      info.message.includes('Verbindung') ||
      info.message.includes('connection') ||
      info.message.includes('authenticated') ||
      info.message.includes('Toast')
    )) {
    return info;
  }
  
  // Filter out debug messages unless they're important
  if (info.level.includes('debug') && (
      info.message.includes('Debug-Ping Daten:') ||
      info.message.includes('Socket-Räume:') ||
      info.message.includes('Handshake für Client')
    )) {
    return false;
  }
  
  return info;
});

const format = winston.format.combine(
  filterMessages(),
  winston.format.timestamp({ format: 'YYYY-MM-DD HH:mm:ss:ms' }),
  winston.format.colorize({ all: true }),
  winston.format.printf(
    (info) => `${info.timestamp} ${info.level}: ${info.message}`,
  ),
);

const transports = [
  new winston.transports.Console(),
  new winston.transports.File({
    filename: path.join(logDir, 'error.log'),
    level: 'error',
  }),
  new winston.transports.File({ filename: path.join(logDir, 'all.log') }),
];

const Logger = winston.createLogger({
  level: process.env.LOG_LEVEL || level(),
  levels,
  format,
  transports,
});

module.exports = Logger; 