/**
 * Socket.io Authentifizierungs-Middleware
 * Prüft JWT-Token und setzt Benutzerinformationen auf dem Socket-Objekt
 */

const jwt = require("jsonwebtoken");
const logger = require("../utils/logger");

// Create a dedicated auth logger
const authLogger = require('winston').createLogger({
	level: 'info',
	format: require('winston').format.combine(
		require('winston').format.timestamp(),
		require('winston').format.printf(({ level, message, timestamp }) => {
			return `${timestamp} [${level.toUpperCase()}] AUTH: ${message}`;
		})
	),
	transports: [
		new (require('winston').transports).File({ filename: 'logs/auth.log', maxsize: 5242880 }),
		new (require('winston').transports).Console({
			format: require('winston').format.combine(
				require('winston').format.colorize(),
				require('winston').format.printf(({ level, message, timestamp }) => {
					return `${timestamp} [${level.toUpperCase()}] AUTH: ${message}`;
				})
			)
		})
	]
});

/**
 * Extrahiert den JWT-Token aus verschiedenen Quellen des Socket.io-Handshakes
 * @param {Object} handshake - Socket.io handshake Objekt
 * @returns {string|null} Der extrahierte Token oder null
 */
function extractTokenFromHandshake(handshake) {
	const clientAddress = handshake.address || "unknown";
	
	// Debug information for troubleshooting
	authLogger.debug(`🔍 Extracting token for client: ${clientAddress} (${handshake.headers['user-agent'] || 'Unknown Agent'})`);

	// Aus auth-Objekt extrahieren (Standard-Weg)
	if (handshake.auth && handshake.auth.token) {
		authLogger.debug("✅ Found token in auth.token");
		return handshake.auth.token;
	}

	// Aus Headers extrahieren
	if (handshake.headers && handshake.headers.authorization) {
		const authHeader = handshake.headers.authorization;
		authLogger.debug("✅ Found token in Authorization header");
		// Bearer-Token Format prüfen
		if (authHeader.startsWith("Bearer ")) {
			return authHeader.substring(7);
		}
		return authHeader;
	}

	// Aus Query-Parameter extrahieren
	if (handshake.query && handshake.query.token) {
		authLogger.debug("✅ Found token in query.token");
		return handshake.query.token;
	}

	// Aus Cookies extrahieren
	if (handshake.headers && handshake.headers.cookie) {
		const cookies = handshake.headers.cookie.split(";");
		authLogger.debug(`📝 Cookies available: ${cookies.length}`);

		// Nach bekannten Cookie-Namen suchen
		const possibleCookieNames = ["jwt", "auth_token", "token"];

		for (const cookieName of possibleCookieNames) {
			const cookie = cookies.find((c) =>
				c.trim().startsWith(`${cookieName}=`)
			);
			if (cookie) {
				const value = cookie.trim().substring(cookieName.length + 1);
				if (value) {
					authLogger.debug(
						`✅ Found token in cookie '${cookieName}'`
					);
					return value;
				}
			}
		}
	}

	// Kein Token gefunden
	authLogger.debug("❌ No token found in request");
	return null;
}

/**
 * Verify and decode the JWT token
 * @param {string} token - The JWT token to verify
 * @returns {Object|null} Decoded token payload or null if invalid
 */
function verifyToken(token) {
	if (!token) return null;

	try {
		// Try to decode token with each potential secret
		const secrets = [
			process.env.JWT_SECRET,
			process.env.API_KEY,
			'development_secret_key' // Fallback for development
		].filter(Boolean); // Remove undefined/null values
		
		if (secrets.length === 0) {
			authLogger.warn("⚠️ No JWT secrets configured! Using unsafe fallback.");
			secrets.push('unsafe_fallback_secret');
		}

		// Try each secret
		for (const secret of secrets) {
			try {
				const decoded = jwt.verify(token, secret);
				authLogger.debug(`✅ Successfully decoded token with secret`);
				return decoded;
			} catch (err) {
				// Continue to next secret
				authLogger.debug(`Failed to decode with a secret: ${err.message}`);
			}
		}

		// If no secret worked, try to decode without verification in development
		if (process.env.NODE_ENV === 'development') {
			authLogger.warn("⚠️ Token verification failed. Attempting unsafe decode in development mode");
			const decoded = jwt.decode(token);
			if (decoded) {
				authLogger.warn("⚠️ Using unverified token payload in development mode");
				return decoded;
			}
		}

		authLogger.warn("❌ Token verification failed with all secrets");
		return null;
	} catch (error) {
		authLogger.error(`❌ Error verifying token: ${error.message}`);
		return null;
	}
}

/**
 * Extract user data from token payload
 * @param {Object} decoded - Decoded token payload
 * @param {Object} handshake - Socket handshake object for fallback data
 * @returns {Object} User data object
 */
function extractUserData(decoded, handshake) {
	if (!decoded) return null;

	// Extract user ID based on known formats
	let userId = null;
	let username = null;
	let roles = [];
	let permissions = [];

	// Check data field (used by our JWT format)
	if (decoded.data) {
		userId = decoded.data.userId || decoded.data.user_id;
		username = decoded.data.username;
		roles = decoded.data.roles || [];
		permissions = decoded.data.permissions || [];
	}

	// Check other common JWT fields
	if (!userId) {
		userId = decoded.sub || decoded.user_id || decoded.userId;
	}
	
	if (!username) {
		username = decoded.name || decoded.username || `user-${userId}`;
	}

	// Fallback to query parameters if available
	if (!userId && handshake.query && handshake.query.userId) {
		userId = parseInt(handshake.query.userId);
		authLogger.warn(`⚠️ Falling back to query userId: ${userId}`);
	}

	// Ensure user ID is an integer if possible
	if (userId && !isNaN(parseInt(userId))) {
		userId = parseInt(userId);
	}

	if (!userId) {
		authLogger.error("❌ Could not extract user ID from token or query");
		return null;
	}

	authLogger.info(`👤 Authenticated user: ${userId} (${username || 'unknown'})`);
	
	return {
		id: userId,
		username: username || `user-${userId}`,
		roles: roles,
		permissions: permissions,
		// Additional metadata can be added here
		authenticated: true,
		tokenExp: decoded.exp
	};
}

/**
 * Middleware zur Authentifizierung von Socket.io-Verbindungen
 * @param {Object} socket - Socket.io-Socket-Objekt
 * @param {Function} next - Callback-Funktion
 */
function authenticate(socket, next) {
	try {
		const socketId = socket.id;
		const socketNamespace = socket.nsp ? socket.nsp.name : "main";
		const clientIp = socket.handshake.address;
		
		authLogger.info(
			`🔄 Authentication attempt for namespace "${socketNamespace}" from IP: ${clientIp}, Socket ID: ${socketId}`
		);

		// Extract token
		const token = extractTokenFromHandshake(socket.handshake);

		// DEVELOPMENT MODE handling
		const isDevelopment = process.env.NODE_ENV === "development";
		const disableTokenValidation = process.env.DISABLE_TOKEN_VALIDATION === "true";

		if (!token) {
			authLogger.warn(`❌ Connection attempt without token to namespace "${socketNamespace}" from ${clientIp}`);

			// In development, create a test user
			if (isDevelopment) {
				authLogger.warn(`⚠️ Allowing connection without token in development mode`);
				
				// Try to get user ID from query
				let userId = null;
				if (socket.handshake.query && socket.handshake.query.userId) {
					userId = parseInt(socket.handshake.query.userId, 10);
				}
				
				// Use default ID if needed
				if (!userId || isNaN(userId)) {
					userId = 1; // Default test user ID
				}
				
				socket.user = {
					id: userId,
					username: `dev-user-${userId}`,
					roles: ["user"],
					permissions: ["BASIC_PERMISSIONS"],
					authenticated: false
				};
				
				authLogger.info(`🧪 DEV MODE: Assigned test user ID ${userId} to socket ${socketId}`);
				
				// Make sure user is in the right room
				const userRoom = `user:${userId}`;
				socket.join(userRoom);
				
				return next();
			}

			return next(new Error("Authentication failed: No token provided"));
		}

		// Token found, try to verify it
		const decoded = verifyToken(token);
		
		if (!decoded) {
			authLogger.warn(`❌ Invalid token from ${clientIp}`);
			
			// In development, allow connection even with invalid token
			if (isDevelopment && disableTokenValidation) {
				authLogger.warn(`⚠️ Allowing connection with invalid token in development mode`);
				
				let userId = socket.handshake.query?.userId || 1;
				socket.user = {
					id: userId,
					username: `invalid-token-user-${userId}`,
					roles: ["user"],
					authenticated: false
				};
				
				// Join user room
				const userRoom = `user:${userId}`;
				socket.join(userRoom);
				
				return next();
			}
			
			return next(new Error("Authentication failed: Invalid token"));
		}

		// Extract user data from token
		const userData = extractUserData(decoded, socket.handshake);
		
		if (!userData) {
			authLogger.warn(`❌ Could not extract user data from token`);
			return next(new Error("Authentication failed: Invalid user data"));
		}

		// Check if token is expired
		if (userData.tokenExp && userData.tokenExp * 1000 < Date.now()) {
			authLogger.warn(`❌ Token expired for user ${userData.id}`);
			
			if (isDevelopment) {
				authLogger.warn(`⚠️ Allowing connection with expired token in development mode`);
			} else {
				return next(new Error("Authentication failed: Token expired"));
			}
		}

		// Set user data on socket
		socket.user = userData;
		
		// Join user-specific room
		const userRoom = `user:${userData.id}`;
		socket.join(userRoom);
		
		authLogger.info(`✅ User ${userData.id} authenticated and joined room ${userRoom}`);
		
		// Log all rooms the socket is in
		const rooms = Array.from(socket.rooms || []);
		authLogger.debug(`Socket ${socketId} is in rooms: ${rooms.join(', ')}`);

		return next();
	} catch (error) {
		authLogger.error(`❌ Authentication error: ${error.message}`);
		return next(new Error(`Authentication error: ${error.message}`));
	}
}

module.exports = authenticate;
