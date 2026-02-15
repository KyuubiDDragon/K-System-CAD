<?php
// Auth Check - Cookie Based V2

// Dieses Skript prüft die AUTHENTIFIZIERUNG:
// 1. Ist ein gültiges Auth-Cookie vorhanden?
// 2. Ist das JWT im Cookie gültig (Signatur, Ablauf, Issuer, Audience)?
//
// Die AUTORISIERUNG (Berechtigungsprüfung) muss vom aufrufenden Skript
// mit den Daten aus $decoded_jwt durchgeführt werden.

// Stelle sicher, dass jwt.php (mit decode_jwt) korrekt eingebunden wird.
// __DIR__ bezieht sich auf das Verzeichnis dieser Datei (auth_check.php).
// Passe den Pfad ggf. an, wenn jwt.php woanders liegt.
require_once __DIR__ . '/jwt.php'; // Annahme: jwt.php liegt eine Ebene höher

// Helper function to get environment variables from either source
if (!function_exists('getEnvVar')) {
    function getEnvVar(string $key, $default = null) {
        // Try $_ENV first (for .env loaded variables)
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        // Fallback to getenv() (for Docker/System environment)
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
}

// Lade Umgebungsvariablen, falls nicht schon global geschehen (Sicherheitsnetz)
// Dies ist nur nötig, wenn nicht garantiert ist, dass .env schon geladen wurde.
if (!getEnvVar('JWT_SECRET_KEY')) { // Prüfe eine essentielle Variable
    try {
        // Try parent directory first
        $dotenvPath = dirname(__DIR__);
        $dotenvFile = $dotenvPath . '/.env';
        
        if (file_exists($dotenvFile)) {
            $dotenv = Dotenv\Dotenv::createImmutable($dotenvPath);
            $dotenv->load();
        } else {
            // Try current directory if parent directory failed
            $dotenvPath = __DIR__;
            $dotenvFile = $dotenvPath . '/.env';
            
            if (file_exists($dotenvFile)) {
                $dotenv = Dotenv\Dotenv::createImmutable($dotenvPath);
                $dotenv->load();
            } else {
                throw new \Exception(".env file not found in either parent or current directory");
            }
        }
    } catch (\Throwable $e) {
        error_log("Failed to load .env in auth_check.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Server configuration error during auth check."]);
        exit();
    }
}

// 1. Try to get JWT from Authorization header first (for FiveM compatibility)
$jwt = null;
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $jwt = $matches[1];
        error_log("Auth token found in Authorization header");
    }
}

// 2. If no JWT in header, fall back to cookie
if (!$jwt && isset($_COOKIE['auth_token'])) {
    $jwt = $_COOKIE['auth_token'];
    error_log("Auth token found in cookie");
}

// 3. If still no JWT, authentication fails
if (!$jwt) {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Authentication required. No token provided."]);
    exit(); // Ausführung stoppen
}

try {
    // 3. JWT dekodieren und validieren (Signatur, Ablauf, Issuer, Audience)
    // Die Funktion decode_jwt (aus jwt.php) muss die Logik enthalten,
    // um die Werte (Secret Key, Issuer, Audience) aus $_ENV zu verwenden.
    $decoded_jwt = decode_jwt($jwt); // Nimmt den JWT-String entgegen

    // PRÜFUNG: Optional sicherstellen, dass decode_jwt ein Objekt oder Array zurückgibt
    if (!is_object($decoded_jwt) && !is_array($decoded_jwt)) {
         throw new \UnexpectedValueException('Decoded JWT is not in expected format.');
    }

    // 3.5. Validate session in database (check if not logged out/revoked)
    // IMPORTANT: This requires kdd_sessions table to exist!
    require_once __DIR__ . '/utils/session_manager.php';
    try {
        if (!validateSession($pdo, $jwt)) {
            error_log("Authentication failed: Session not found, logged out, or expired");
            http_response_code(401);
            echo json_encode(["error" => "Authentication failed: Session invalid or revoked."]);
            exit();
        }
        // Session is valid - last_activity timestamp was updated automatically
    } catch (\PDOException $e) {
        // Table doesn't exist yet - skip database validation during migration period
        error_log("[AUTH] Session validation skipped (table may not exist yet): " . $e->getMessage());
        // Continue with authentication (rely on JWT signature validation only)

        // Fallback: Try old JWT token validation if session table doesn't exist
        try {
            require_once __DIR__ . '/utils/jwt_token_manager.php';
            if (!validateJwtToken($pdo, $jwt)) {
                error_log("Authentication failed: Token not found or revoked in old table");
                http_response_code(401);
                echo json_encode(["error" => "Authentication failed: Session invalid or revoked."]);
                exit();
            }
        } catch (\PDOException $e2) {
            error_log("[AUTH] Fallback JWT validation also skipped: " . $e2->getMessage());
        }
    }

    // 4. AUTHENTIFIZIERUNG ERFOLGREICH!
    // Das Skript, das diese Datei inkludiert, kann nun weiterarbeiten
    // und hat Zugriff auf die dekodierten JWT-Daten in der Variable $decoded_jwt.
    // z.B. $userId = $decoded_jwt->userId; (wenn userId im Payload ist)
    // z.B. $permissions = $decoded_jwt->permissions; (wenn permissions im Payload sind)

    /* Beispiel für die Berechtigungsprüfung im aufrufenden Skript:
    ----------------------------------------------------------------
    require_once 'path/to/auth_check.php'; // Prüft Authentifizierung

    // Jetzt Autorisierung: Hat der User die nötige Berechtigung für DIESEN Endpunkt?
    $required_permission = "VIEW_REPORTS"; // Beispiel
    $user_permissions = $decoded_jwt->permissions ?? []; // Annahme: permissions ist ein Array im JWT-Payload

    if (!in_array($required_permission, $user_permissions)) {
        http_response_code(403); // Forbidden - Eingeloggt, aber keine Berechtigung
        echo json_encode(["error" => "Permission denied for this action."]);
        exit();
    }

    // User ist authentifiziert UND autorisiert -> weiter mit der Endpunkt-Logik...
    ----------------------------------------------------------------
    */

} catch (\Firebase\JWT\ExpiredException $e) {
    http_response_code(401);
    error_log("Expired token received: " . $e->getMessage());
    // Lösche das abgelaufene Cookie mit getEnvVar
    setcookie('auth_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => getEnvVar('COOKIE_DOMAIN', ''),
        'secure' => filter_var(getEnvVar('COOKIE_SECURE', 'false'), FILTER_VALIDATE_BOOLEAN),
        'httponly' => true,
        'samesite' => getEnvVar('COOKIE_SAMESITE', 'Lax')
    ]);
    echo json_encode(["error" => "Token has expired. Please log in again."]);
    exit();

} catch (\Exception $e) {
    http_response_code(401);
    error_log("Invalid token received: " . $e->getMessage());
    $error_detail = (getEnvVar('APP_ENV', 'production') === 'development') ? " Details: " . $e->getMessage() : "";
    // Lösche das ungültige Cookie mit getEnvVar
    setcookie('auth_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => getEnvVar('COOKIE_DOMAIN', ''),
        'secure' => filter_var(getEnvVar('COOKIE_SECURE', 'false'), FILTER_VALIDATE_BOOLEAN),
        'httponly' => true,
        'samesite' => getEnvVar('COOKIE_SAMESITE', 'Lax')
    ]);
    echo json_encode(['error' => "Invalid token." . $error_detail]);
    exit();
}

// Wenn das Skript bis hierhin kommt, ist der Benutzer erfolgreich authentifiziert.
// Die Variable $decoded_jwt enthält die Payload-Daten aus dem Token.

// Check if we have authority in the token, try to get authority_id
if (isset($decoded_jwt->authority) && !isset($decoded_jwt->authority_id) && isset($pdo)) {
    $authorityId = getAuthorityId($pdo, $decoded_jwt->authority);
    if ($authorityId) {
        // Add authority_id to decoded JWT object dynamically
        $decoded_jwt->authority_id = $authorityId;
    }
}

// --- Permissions are in JWT (Bitmask Format) ---
// JWT tokens contain permissions in bitmask format: {employee: 7, calendar: 23, system: 255}
// No conversion or loading needed - permissions are already in the token
if (!isset($decoded_jwt->permissions) || empty($decoded_jwt->permissions)) {
    error_log("[AUTH] ERROR: No permissions in JWT token (user {$decoded_jwt->userId})");
    $decoded_jwt->permissions = [];
}
// --- End Permissions Check ---

// Check if user is banned (unless they have ALL_PERMISSIONS/super admin)
// ALL_PERMISSIONS users (system module) are never blocked by ban status
$hasAllPermissions = false;
if (isset($decoded_jwt->permissions) && is_object($decoded_jwt->permissions)) {
    $hasAllPermissions = property_exists($decoded_jwt->permissions, 'system');
}

if (isset($decoded_jwt->banned) && $decoded_jwt->banned === true && !$hasAllPermissions) {
    http_response_code(403); // 403 Forbidden for banned users
    error_log("Banned user attempted to access system: User ID " . ($decoded_jwt->userId ?? 'unknown'));

    // Delete the auth cookie to force re-login
    setcookie('auth_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => getEnvVar('COOKIE_DOMAIN', ''),
        'secure' => filter_var(getEnvVar('COOKIE_SECURE', 'false'), FILTER_VALIDATE_BOOLEAN),
        'httponly' => true,
        'samesite' => getEnvVar('COOKIE_SAMESITE', 'Lax')
    ]);

    echo json_encode(["error" => "Your account is suspended. Please contact an administrator."]);
    exit();
}

/**
 * Ermittelt die authority_id anhand des authority-Namens
 * 
 * @param PDO $pdo Die Datenbankverbindung
 * @param string $authority Der Name der Authority
 * @return int|null Die ID der Authority oder null, wenn nicht gefunden
 */
if (!function_exists('getAuthorityId')) {
    function getAuthorityId(PDO $pdo, string $authority): ?int {
        try {
            $sqlAuthority = "SELECT id FROM kdd_authorities WHERE name = ?";
            $stmtAuthority = $pdo->prepare($sqlAuthority);
            $stmtAuthority->execute([$authority]);
            $authorityId = $stmtAuthority->fetchColumn();
            
            return $authorityId ? (int)$authorityId : null;
        } catch (\PDOException $e) {
            error_log("Error fetching authority_id: " . $e->getMessage());
            return null;
        }
    }
}

?>