<?php
/**
 * Backend Endpoint: login/index.php
 * Handles user authentication and session management.
 * Uses Cookie-based Authentication and PDO database connection.
 */
declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../bootstrap.php';

// --- Dependencies & DB Connection ---
require_once __DIR__ . "/../jwt.php";
require_once __DIR__ . '/../logging/logging.php';

// db.php jetzt hier einbinden, um $pdo zu erhalten
try {
    require_once __DIR__ . '/../db.php'; // Definiert die globale Variable $pdo
} catch (Exception $e) {
    // db.php fängt den Fehler schon ab und beendet das Skript mit `die()`,
    // aber zur Sicherheit hier nochmals (obwohl dieser Code evtl. nicht erreicht wird)
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed during include."]);
    exit();
}
// $pdo ist nun verfügbar

// === Restliche Logik ===
error_log("=== STARTING REQUEST HANDLING ===");
$method = $_SERVER['REQUEST_METHOD'];
error_log("=== REQUEST METHOD: $method ===");

switch ($method) {
    case 'POST':
        // Daten aus POST holen (oder JSON, falls du das Frontend änderst)
        $action = $_REQUEST['action'] ?? null;
        error_log("=== POST ACTION: $action ===");

        switch ($action) {
            case 'login':
                // Übergebe das $pdo Objekt an die Funktion
                error_log("=== CALLING loginUser() ===");
                loginUser($pdo);
                error_log("=== loginUser() COMPLETED ===");
                break;
            case 'logout':
                logoutUser($pdo); // Pass PDO connection
                break;
            case 'refresh':
                refreshToken($pdo);
                break;
            case 'getAuthorities':
                getAuthorities($pdo);
                break;
            case 'validateSite':
                validateSite($pdo);
                break;
            default:
                http_response_code(400);
                echo json_encode(["error" => "Invalid action."]);
        }
        break;
    case 'GET':
        $action = $_REQUEST['action'] ?? null;

        switch ($action) {
            case 'getAuthorities':
                getAuthorities($pdo);
                break;
            default:
                http_response_code(400);
                echo json_encode(["error" => "Invalid action."]);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed."]);
}

// --- Funktionen ---

// Angepasste loginUser Funktion mit PDO
function loginUser(PDO $pdo)
{
    error_log("=== INSIDE loginUser() - START ===");
    // Akzeptiert das PDO-Objekt
    $jsonInput = file_get_contents('php://input');
    $data = json_decode($jsonInput, true);
    error_log("=== loginUser() - Data received ===");
    $username = $data['username'] ?? null;
    $password = $data['password'] ?? null;
    $authorityId = $data['authority_id'] ?? null;
    $rememberMe = (isset($data['remember_me']) && $data['remember_me'] == 'true') ? true : false;

    error_log("=== loginUser() - Username: $username, AuthorityID: $authorityId ===");

    if (!$username || !$password || !$authorityId) {
        http_response_code(400);
        echo json_encode(["error" => "Missing credentials."]);
        return;
    }

    try { // Datenbankoperationen in try-catch wegen PDO::ERRMODE_EXCEPTION
        error_log("=== loginUser() - Starting DB operations ===");

        // Authority_helper einbinden für Feature-Checks
        error_log("=== loginUser() - BEFORE authority_helper ===");
        require_once __DIR__ . '/../utils/authority_helper.php';
        error_log("=== loginUser() - AFTER authority_helper ===");

        // Prüfen ob die Authority überhaupt gültig/aktiv ist
        if (!isValidAuthority($pdo, $authorityId)) {
            http_response_code(403);
            echo json_encode(["error" => "This authority is not active or does not exist."]);
            return;
        }

        // Hole zuerst die authority_id aus der Authorities-Tabelle
        $sqlAuthority = "SELECT name FROM kdd_authorities WHERE id = ?";
        $stmtAuthority = $pdo->prepare($sqlAuthority);
        $stmtAuthority->execute([$authorityId]);
        $authority = $stmtAuthority->fetchColumn();

        // Benutzerdaten holen mit PDO - inklusive Authority-Branding-Daten
        $sqlUser = "SELECT u.*, e.name, a.logo_url, a.primary_color, a.secondary_color, a.app_title, a.default_background, a.display_name as authority_display_name, a.theme_settings
                    FROM kdd_users u
                    LEFT JOIN kdd_employee e ON e.authority_id = u.authority_id AND u.linked_employee = e.id
                    LEFT JOIN kdd_authorities a ON a.id = u.authority_id
                    WHERE u.username = ? AND u.authority_id = ?";
        $stmtUser = $pdo->prepare($sqlUser);
        // Parameter direkt an execute übergeben (sicherer gegen SQL Injection)
        $stmtUser->execute([$username, $authorityId]);
        // fetch() holen, da wir nur einen User erwarten (FETCH_ASSOC ist Standard durch db.php)
        $user = $stmtUser->fetch();

        if (!$user) {
            http_response_code(401); // User nicht gefunden
            echo json_encode(["error" => "Invalid username or password."]);
            return;
        }

        if (!empty($user['banned'])) { // Prüfen ob banned gesetzt und true ist
            http_response_code(403); // 403 Forbidden für gesperrte User
            echo json_encode(["error" => "Your account is banned."]);
            return;
        }

        // Passwort prüfen
        error_log("=== loginUser() - Verifying password ===");
        if (password_verify($password, $user['password'])) {
            error_log("=== loginUser() - Password verified, user authenticated ===");
            // Erfolgreiches Anmelden
            $userId = $user['id'];

            // Load permissions using PermissionManager (returns bitmask format)
            error_log("=== loginUser() - BEFORE PermissionManager ===");
            require_once __DIR__ . '/../helpers/PermissionManager.php';
            error_log("=== loginUser() - AFTER PermissionManager ===");
            error_log("=== loginUser() - Creating PermissionManager ===");
            $permissionManager = new PermissionManager($pdo, (int)$authorityId);
            error_log("=== loginUser() - Loading permissions ===");
            $permissionsBitmask = $permissionManager->loadUserPermissions((int)$userId);
            error_log("=== loginUser() - Permissions loaded ===");

            // Get roles only (we already have permissions as bitmask)
            $sqlRoles = "SELECT DISTINCT r.name as role_name
                         FROM kdd_user_roles ur
                         JOIN kdd_roles r ON r.authority_id = ur.authority_id AND ur.role_id = r.id
                         WHERE ur.user_id = ? AND ur.authority_id = ?";
            $stmtRoles = $pdo->prepare($sqlRoles);
            $stmtRoles->execute([$userId, $authorityId]);
            $rolesResult = $stmtRoles->fetchAll();

            $roles = [];
            foreach ($rolesResult as $row) {
                $roles[] = $row['role_name'];
            }

            // Load active features for this authority
            require_once __DIR__ . '/../utils/authority_helper.php';
            $activeFeatures = getAuthorityActiveFeatures($pdo, $authorityId);

            // Aktualisiere User-Objekt mit Berechtigungen (now using bitmask format)
            $user['roles'] = $roles;
            $user['permissions'] = $permissionsBitmask; // Bitmask format: {"employee": 7, "calendar": 23, ...}
            $user['active_features'] = $activeFeatures; // Active feature codes: ["employee", "calendar", "document", ...]
            $user['authority'] = $authority;
            $user['authority_id'] = $authorityId;

            // ✅ FIXED: Suspension now handled via user.banned field
            // - Banned status is included in JWT token (jwt.php line 83)
            // - Auth check validates banned status on every request (auth_check.php line 212)
            // - Super admins (ALL_PERMISSIONS) are exempt from ban checks

            // --- Cookie & JWT (Logik wie im vorherigen Schritt) ---
            $jwtExpirationSeconds = $_ENV['JWT_EXPIRATION_TIME'] ?? 3600;
            $cookieLifetimeSeconds = $rememberMe ? (60 * 60 * 24 * 30) : $jwtExpirationSeconds;
            $expiryTimestamp = time() + $cookieLifetimeSeconds;

            // Debug logging for environment variables
            error_log("Cookie settings - Domain: " . ($_ENV['COOKIE_DOMAIN'] ?? "not set"));
            error_log("Cookie settings - Secure: " . ($_ENV['COOKIE_SECURE'] ?? "not set"));
            error_log("Cookie settings - SameSite: " . ($_ENV['COOKIE_SAMESITE'] ?? "not set"));

            // Create JWT token
            try {
                $jwt = create_jwt($user, $pdo, $rememberMe);

                // Extract jti from the JWT for session tracking
                require_once __DIR__ . '/../jwt.php';
                $jti = get_jti_from_jwt($jwt);

                // Save session to database (multi-device tracking)
                require_once __DIR__ . '/../utils/session_manager.php';
                try {
                    saveSession($pdo, $userId, $authorityId, $jwt, $jti, $expiryTimestamp);
                    error_log("[LOGIN] Session saved to database for user $userId");
                } catch (\PDOException $e) {
                    // Table doesn't exist yet - this is OK during migration period
                    error_log("[LOGIN] Session not saved (table may not exist yet): " . $e->getMessage());
                }
            } catch (\Exception $e) {
                error_log("Failed to create JWT: " . $e->getMessage());
                throw $e;
            }

            error_log("Setting auth_token cookie for user ID: {$userId}");
            error_log("Cookie parameters: " . json_encode([
                'expires' => $expiryTimestamp,
                'path' => '/',
                'domain' => $_ENV['COOKIE_DOMAIN'] ?? "",
                'secure' => filter_var($_ENV['COOKIE_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'httponly' => true,
                'samesite' => $_ENV['COOKIE_SAMESITE'] ?? 'Lax'
            ]));

            // For FiveM compatibility, use None for SameSite (requires Secure)
            $isSecure = filter_var($_ENV['COOKIE_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $sameSite = $_ENV['COOKIE_SAMESITE'] ?? 'Lax';
            
            // If in FiveM context (can be detected via user agent or custom header), use None
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if (strpos($userAgent, 'FiveM') !== false || strpos($userAgent, 'CEF') !== false) {
                $sameSite = 'None';
                $isSecure = true; // None requires Secure
                error_log("Detected FiveM/CEF browser, using SameSite=None with Secure=true");
            }
            
            $cookieResult = setcookie(
                'auth_token',
                $jwt,
                [
                    'expires' => $expiryTimestamp,
                    'path' => '/',
                    'domain' => $_ENV['COOKIE_DOMAIN'] ?? "",
                    'secure' => $isSecure,
                    'httponly' => false,
                    'samesite' => $sameSite
                ]
            );
            error_log("setcookie result for auth_token: " . ($cookieResult ? "success" : "failed"));

            // Cookie für die aktuelle authority
            error_log("Setting current_authority cookie");
            $authorityCookieResult = setcookie(
                'current_authority',
                $authority,
                [
                    'expires' => $expiryTimestamp,
                    'path' => '/',
                    'domain' => $_ENV['COOKIE_DOMAIN'] ?? "",
                    'secure' => $isSecure,
                    'httponly' => false,
                    'samesite' => $sameSite
                ]
            );
            error_log("setcookie result for current_authority: " . ($authorityCookieResult ? "success" : "failed"));

            // Save JWT token to database for session management
            // IMPORTANT: This requires kdd_jwt_tokens table to exist!
            require_once __DIR__ . '/../utils/jwt_token_manager.php';
            try {
                saveJwtToken($pdo, $userId, $authorityId, $jwt, $expiryTimestamp);
            } catch (\PDOException $e) {
                // Table doesn't exist yet - this is OK during migration period
                error_log("[LOGIN] JWT token not saved to database (table may not exist yet): " . $e->getMessage());
            }

            // ✅ CREATE REFRESH TOKEN (7 days validity)
            // IMPORTANT: This requires kdd_refresh_tokens table to exist!
            error_log("[LOGIN] CHECKPOINT 1: About to enter refresh token try-catch block");

            try {
                error_log("[LOGIN] CHECKPOINT 2: Inside try block, about to require refresh_token_helper.php");

                $requirePath = __DIR__ . '/../utils/refresh_token_helper.php';
                error_log("[LOGIN] Require path: {$requirePath}");
                error_log("[LOGIN] File exists: " . (file_exists($requirePath) ? 'YES' : 'NO'));
                error_log("[LOGIN] Is readable: " . (is_readable($requirePath) ? 'YES' : 'NO'));
                error_log("[LOGIN] __DIR__ value: " . __DIR__);

                if (!file_exists($requirePath)) {
                    error_log("[LOGIN] ERROR: File does not exist!");
                    throw new \Exception("Refresh token helper file not found at: {$requirePath}");
                }

                if (!is_readable($requirePath)) {
                    error_log("[LOGIN] ERROR: File is not readable!");
                    throw new \Exception("Refresh token helper file not readable at: {$requirePath}");
                }

                error_log("[LOGIN] About to execute require_once...");

                // Load refresh token helper with renamed functions (avoid conflicts with jwt_token_manager.php)
                require_once __DIR__ . '/../utils/refresh_token_helper.php';
                error_log("[LOGIN] CHECKPOINT 3: refresh_token_helper.php loaded successfully!");

                // Check if table exists before attempting to create token
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'kdd_refresh_tokens'")->fetch();

                if ($tableCheck) {
                    error_log("[LOGIN] Creating refresh token for user {$userId}, authority {$authorityId}");

                    $refreshToken = createRefreshToken($pdo, $userId, $authorityId, 7);

                    error_log("[LOGIN] Refresh token created successfully: " . substr($refreshToken, 0, 8) . "...");

                    // Set refresh token as HttpOnly cookie (secure, not accessible via JavaScript)
                    $cookieSet = setcookie(
                        'refresh_token',
                        $refreshToken,
                        [
                            'expires' => time() + (7 * 24 * 60 * 60), // 7 days
                            'path' => '/',
                            'domain' => $_ENV['COOKIE_DOMAIN'] ?? "",
                            'secure' => $isSecure,
                            'httponly' => true, // IMPORTANT: Prevents XSS attacks
                            'samesite' => $sameSite
                        ]
                    );

                    error_log("[LOGIN] Refresh token cookie set: " . ($cookieSet ? "success" : "failed"));
                } else {
                    error_log("[LOGIN] Refresh tokens table not found - skipping refresh token creation. Run migration: backend/migrations/add_refresh_tokens_table.sql");
                }
            } catch (\Throwable $e) {
                // Refresh token creation failed - log but don't fail login
                error_log("[LOGIN] EXCEPTION creating refresh token: " . get_class($e) . " - " . $e->getMessage());
                error_log("[LOGIN] Exception trace: " . $e->getTraceAsString());
                error_log("[LOGIN] Exception file: " . $e->getFile() . " line: " . $e->getLine());
            }

            // Letzten Login aktualisieren (mit PDO)
            setLastLogin($pdo, $userId, $authorityId); // Übergebe PDO und authorityId

            // Logeintrag (angenommen logDatabaseChange akzeptiert $pdo als 2. Parameter statt $conn)
            $changes = [['column_name' => 'login', 'old_value' => null, 'new_value' => "User logged in successfully."]];
            // Prüfe, ob logDatabaseChange PDO oder mysqli erwartet! Ggf. anpassen.
            logDatabaseChange($authorityId, $pdo, 'LOGIN', 'kdd_users', $userId, $userId, $changes);

            // Parse theme_settings JSON
            $themeSettings = [];
            if (!empty($user['theme_settings'])) {
                $themeSettings = json_decode($user['theme_settings'], true) ?? [];
            }

            // Authority-Branding-Daten in User-Response einbauen
            $user['authority_branding'] = [
                'logo_url' => $user['logo_url'],
                'primary_color' => $user['primary_color'] ?: '#3B82F6',
                'secondary_color' => $user['secondary_color'] ?: '#6B7280',
                'app_title' => $user['app_title'] ?: $authority,
                'default_background' => $user['default_background'],
                'display_name' => $user['authority_display_name'] ?: $authority,
                'theme_settings' => $themeSettings
            ];

            // Cleanup - Authority-Branding-Felder aus User-Root-Level entfernen
            unset($user['logo_url'], $user['primary_color'], $user['secondary_color'], $user['app_title'], $user['default_background'], $user['authority_display_name'], $user['theme_settings']);
            
            // Passwort-Hash aus der Antwort entfernen
            unset($user['password']);

            // Erfolgsantwort - Always include JWT token for better compatibility
            $response = [
                "message" => "Login successful.",
                "user" => $user,
                "token" => $jwt  // Always include token for FiveM and other embedded browsers
            ];
            
            // Log if we're in FiveM/CEF context
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if (strpos($userAgent, 'FiveM') !== false || strpos($userAgent, 'CEF') !== false) {
                error_log("FiveM/CEF browser detected, token included in response body");
            }
            
            http_response_code(200);
            echo json_encode($response);
        } else {
            // Falsches Passwort
            $userIdToLog = $user['id']; // $user ist hier definiert
            $changes = [['column_name' => 'login', 'old_value' => null, 'new_value' => "Invalid password"]];
            // Prüfe, ob logDatabaseChange PDO oder mysqli erwartet! Ggf. anpassen.
            logDatabaseChange($authorityId, $pdo, 'LOGIN', 'kdd_users', $userIdToLog, $userIdToLog, $changes);

            http_response_code(401);
            echo json_encode(["error" => "Invalid username or password."]);
        }
    } catch (\PDOException $e) {
        // Fange PDO-Fehler ab
        error_log("Database error in loginUser: " . $e->getMessage());
        http_response_code(500); // Internal Server Error
        echo json_encode(["error" => "An internal database error occurred."]);
    }
}

// Logout Funktion (angepasst für PDO)
function logoutUser(PDO $pdo)
{

    // 1. Delete JWT token from database (if exists)
    // Try to get JWT from Authorization header first (FiveM compatibility)
    $jwt = null;
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $jwt = $matches[1];
        }
    }

    // If no JWT in header, fall back to cookie
    if (!$jwt && isset($_COOKIE['auth_token'])) {
        $jwt = $_COOKIE['auth_token'];
    }

    // Revoke session from database if we have it
    if ($jwt) {
        // Revoke session (new session management system)
        require_once __DIR__ . '/../utils/session_manager.php';
        try {
            $tokenHash = hash('sha256', $jwt);
            error_log("[LOGOUT] Token hash: " . substr($tokenHash, 0, 16) . "...");

            $sql = "UPDATE kdd_sessions SET is_active = 0 WHERE token_hash = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tokenHash]);

            $rowCount = $stmt->rowCount();
            if ($rowCount > 0) {
                error_log("[LOGOUT] Session revoked from database (rows affected: $rowCount)");
            } else {
                error_log("[LOGOUT] WARNING: No session found with token_hash, no rows updated");
                // Log alle aktiven Sessions für Debug
                $debugStmt = $pdo->query("SELECT id, LEFT(token_hash, 16) as hash_preview, is_active FROM kdd_sessions WHERE is_active = 1 LIMIT 5");
                $activeSessions = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("[LOGOUT] Active sessions in DB: " . json_encode($activeSessions));
            }
        } catch (\PDOException $e) {
            // Table doesn't exist yet - this is OK during migration period
            error_log("[LOGOUT] Session revocation skipped (table may not exist yet): " . $e->getMessage());
        }
        // Also delete from old JWT token table if it exists
        require_once __DIR__ . '/../utils/jwt_token_manager.php';
        try {
            deleteJwtToken($pdo, $jwt);
            error_log("[LOGOUT] JWT token deleted from old table");
        } catch (\PDOException $e) {
            error_log("[LOGOUT] JWT token deletion skipped (old table may not exist): " . $e->getMessage());
        }
    } else {
        error_log("[LOGOUT] WARNING: No JWT token found in request (neither header nor cookie)");
    }

    // ✅ Revoke refresh token if exists
    if (isset($_COOKIE['refresh_token'])) {
        try {
            require_once __DIR__ . '/../utils/refresh_token_helper.php';
            $refreshToken = $_COOKIE['refresh_token'];
            $result = revokeRefreshToken($pdo, $refreshToken);
            if ($result) {
                error_log("[LOGOUT] Refresh token revoked successfully: " . substr($refreshToken, 0, 8) . "...");
            } else {
                error_log("[LOGOUT] WARNING: Refresh token revocation returned false");
            }
        } catch (\Throwable $e) {
            error_log("[LOGOUT] Refresh token revocation failed: " . $e->getMessage());
            error_log("[LOGOUT] Stack trace: " . $e->getTraceAsString());
        }
    } else {
        error_log("[LOGOUT] No refresh_token cookie found to revoke");
    }

    // Detect FiveM/CEF browser from User-Agent
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $isFiveM = stripos($userAgent, 'CitizenFX') !== false || stripos($userAgent, 'FiveM') !== false;

    // Use same parameters as during login to ensure cookie deletion works
    $sameSite = $isFiveM ? 'None' : ($_ENV['COOKIE_SAMESITE'] ?? 'Lax');
    $isSecure = $isFiveM ? true : filter_var($_ENV['COOKIE_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN);

    // Delete auth_token cookie (must match login parameters exactly!)
    setcookie(
        'auth_token',
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => $_ENV['COOKIE_DOMAIN'] ?? "",
            'secure' => $isSecure,
            'httponly' => false, // Must match login (false, not true!)
            'samesite' => $sameSite
        ]
    );

    // Delete current_authority cookie
    setcookie(
        'current_authority',
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => $_ENV['COOKIE_DOMAIN'] ?? "",
            'secure' => $isSecure,
            'httponly' => false,
            'samesite' => $sameSite
        ]
    );

    // ✅ Delete refresh_token cookie
    setcookie(
        'refresh_token',
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => $_ENV['COOKIE_DOMAIN'] ?? "",
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => $sameSite
        ]
    );

    http_response_code(200);
    echo json_encode(["message" => "Logout successful."]);
}

/**
 * Refresh JWT Token using Refresh Token
 * Uses long-lived refresh token to issue new short-lived access token
 * Implements token rotation for security
 */
function refreshToken(PDO $pdo)
{
    header('Content-Type: application/json');

    try {
        // ✅ Get REFRESH TOKEN from HttpOnly cookie (not access token!)
        if (!isset($_COOKIE['refresh_token'])) {
            http_response_code(401);
            echo json_encode([
                "success" => false,
                "error" => "No refresh token found. Please login."
            ]);
            return;
        }

        $refreshToken = $_COOKIE['refresh_token'];

        // Check if refresh tokens table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'kdd_refresh_tokens'")->fetch();
        if (!$tableCheck) {
            http_response_code(503);
            echo json_encode([
                "success" => false,
                "error" => "Refresh token system not initialized. Please run migration."
            ]);
            error_log("[REFRESH] Refresh tokens table not found. Run migration: backend/migrations/add_refresh_tokens_table.sql");
            return;
        }

        // ✅ Validate refresh token from database
        require_once __DIR__ . '/../utils/refresh_token_helper.php';
        $tokenData = validateRefreshToken($pdo, $refreshToken);

        if (!$tokenData) {
            http_response_code(401);
            echo json_encode([
                "success" => false,
                "error" => "Invalid or expired refresh token. Please login again."
            ]);
            return;
        }

        // Extract user information from validated token
        $userId = $tokenData['user_id'];
        $authorityId = $tokenData['authority_id'];

        // Load fresh user data from database
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.banned, a.name as authority, a.id as authority_id
            FROM kdd_users u
            JOIN kdd_authorities a ON u.authority_id = a.id
            WHERE u.id = ? AND u.authority_id = ?
        ");
        $stmt->execute([$userId, $authorityId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "error" => "User not found"
            ]);
            return;
        }

        // Check if user is banned
        if (!empty($user['banned'])) {
            http_response_code(403);
            echo json_encode([
                "success" => false,
                "error" => "Account is suspended"
            ]);
            return;
        }

        // Load fresh permissions and roles
        require_once __DIR__ . '/../helpers/PermissionManager.php';
        $permissionManager = new PermissionManager($pdo, (int)$authorityId);
        $permissionsBitmask = $permissionManager->loadUserPermissions((int)$userId);

        // Load roles
        $stmt = $pdo->prepare("
            SELECT r.id, r.name, r.description
            FROM kdd_roles r
            JOIN kdd_user_roles ur ON r.id = ur.role_id
            WHERE ur.user_id = ? AND ur.authority_id = ?
        ");
        $stmt->execute([$userId, $authorityId]);
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Load active features
        require_once __DIR__ . '/../utils/authority_helper.php';
        $activeFeatures = getAuthorityActiveFeatures($pdo, (int)$authorityId);

        // Prepare user object for JWT creation
        $user['roles'] = $roles;
        $user['permissions'] = $permissionsBitmask;
        $user['active_features'] = $activeFeatures;

        // Create new JWT token with fresh data
        $rememberMe = false; // Refresh always uses short expiration (15 min)
        $newToken = create_jwt($user, $pdo, $rememberMe);

        // Calculate expiration
        $jwtExpirationSeconds = 900; // 15 minutes
        $expiryTimestamp = time() + $jwtExpirationSeconds;

        // Detect FiveM/CEF browser
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $isFiveM = stripos($userAgent, 'CitizenFX') !== false || stripos($userAgent, 'FiveM') !== false;

        $sameSite = $isFiveM ? 'None' : ($_ENV['COOKIE_SAMESITE'] ?? 'Lax');
        $isSecure = $isFiveM ? true : filter_var($_ENV['COOKIE_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Set new auth_token cookie
        error_log("[REFRESH] Setting auth_token cookie - Token length: " . strlen($newToken) . " bytes");
        error_log("[REFRESH] Token preview: " . substr($newToken, 0, 50) . "...");
        error_log("[REFRESH] Cookie params: expires=" . date('Y-m-d H:i:s', $expiryTimestamp) . ", domain=" . ($_ENV['COOKIE_DOMAIN'] ?? "") . ", secure={$isSecure}, samesite={$sameSite}");

        $authCookieResult = setcookie(
            'auth_token',
            $newToken,
            [
                'expires' => $expiryTimestamp,
                'path' => '/',
                'domain' => $_ENV['COOKIE_DOMAIN'] ?? "",
                'secure' => $isSecure,
                'httponly' => false,
                'samesite' => $sameSite
            ]
        );

        error_log("[REFRESH] auth_token setcookie() result: " . ($authCookieResult ? "SUCCESS" : "FAILED"));

        // ✅ TOKEN ROTATION: Create new refresh token, revoke old one (security best practice)
        $newRefreshToken = rotateRefreshToken($pdo, $refreshToken, $userId, $authorityId, 7);

        // Set new refresh token cookie
        error_log("[REFRESH] Setting refresh_token cookie - Token length: " . strlen($newRefreshToken) . " bytes");

        $refreshCookieResult = setcookie(
            'refresh_token',
            $newRefreshToken,
            [
                'expires' => time() + (7 * 24 * 60 * 60), // 7 days
                'path' => '/',
                'domain' => $_ENV['COOKIE_DOMAIN'] ?? "",
                'secure' => $isSecure,
                'httponly' => true, // HttpOnly for security
                'samesite' => $sameSite
            ]
        );

        error_log("[REFRESH] refresh_token setcookie() result: " . ($refreshCookieResult ? "SUCCESS" : "FAILED"));

        error_log("[REFRESH] Access token + refresh token refreshed for user ID: {$userId}");

        // Get old JWT token if it exists (to revoke old session)
        $oldJwt = null;
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $oldJwt = $matches[1];
            }
        }

        // Fallback: Try to get from cookie
        if (!$oldJwt && isset($_COOKIE['auth_token'])) {
            $oldJwt = $_COOKIE['auth_token'];
        }

        // ✅ CRITICAL: Revoke old session and create new session in kdd_sessions
        require_once __DIR__ . '/../utils/session_manager.php';

        // ✅ CRITICAL: Update existing session OR create new one (only if IP/device changed)
        // Note: Old session revocation is handled inside updateOrCreateSession when needed
        try {
            $jti = get_jti_from_jwt($newToken);
            if ($jti) {
                // Get old token hash for potential revocation
                $oldTokenHash = null;
                if ($oldJwt) {
                    $oldTokenHash = hash('sha256', $oldJwt);
                }

                $saveResult = updateOrCreateSession($pdo, $userId, $authorityId, $newToken, $jti, $expiryTimestamp, $oldTokenHash);
                if ($saveResult) {
                    error_log("[REFRESH] Session updated/created in kdd_sessions with jti: $jti");
                } else {
                    error_log("[REFRESH] ERROR: Failed to update/create session");
                }
            } else {
                error_log("[REFRESH] ERROR: Could not extract jti from new token");
            }
        } catch (\PDOException $e) {
            // Table doesn't exist yet - this is OK during migration period
            error_log("[REFRESH] Session not saved to kdd_sessions (table may not exist yet): " . $e->getMessage());
        }

        // Also handle old JWT token table (for backward compatibility)
        require_once __DIR__ . '/../utils/jwt_token_manager.php';

        // Revoke old JWT token in old table
        if ($oldJwt) {
            try {
                revokeJwtToken($pdo, $oldJwt);
                error_log("[REFRESH] Old JWT token revoked in old table");
            } catch (\PDOException $e) {
                error_log("[REFRESH] Could not revoke old JWT token in old table: " . $e->getMessage());
            }
        }

        // Save new JWT token to old table (for backward compatibility)
        try {
            saveJwtToken($pdo, $userId, $authorityId, $newToken, $expiryTimestamp);
            error_log("[REFRESH] New JWT token saved to old table");
        } catch (\PDOException $e) {
            error_log("[REFRESH] JWT token not saved to old table: " . $e->getMessage());
        }

        // Return success response
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Token refreshed successfully",
            "token" => $newToken,
            "expires_at" => date('c', $expiryTimestamp),
            "user" => [
                "id" => $user['id'],
                "username" => $user['username'],
                "authority" => $user['authority'],
                "authority_id" => $user['authority_id'],
                "permissions" => $permissionsBitmask,
                "roles" => $roles,
                "active_features" => $activeFeatures
            ]
        ]);

    } catch (\Exception $e) {
        error_log("[REFRESH] Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "error" => "Failed to refresh token"
        ]);
    }
}

// --- Andere Funktionen ---

// generateRandomPassword (unverändert)
function generateRandomPassword($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $characters_length = strlen($characters);
    $random_password = '';
    for ($i = 0; $i < $length; $i++) {
        $random_password .= $characters[rand(0, $characters_length - 1)];
    }
    return $random_password;
}

// setLastLogin mit PDO angepasst
function setLastLogin(PDO $pdo, $user_id, $authorityId)
{
    try {
        $current_time = date('Y-m-d H:i:s');
        $sql = "UPDATE kdd_users SET last_login = ? WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        // Parameter als Array an execute übergeben
        $stmt->execute([$current_time, $user_id, $authorityId]);
    } catch (\PDOException $e) {
        // Fehler loggen, aber Skript nicht unbedingt beenden
        error_log("Failed to update last login for user $user_id: " . $e->getMessage());
    }
}

function getAuthorityId(PDO $pdo, string $authority): ?int
{
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

// Funktion zum Abrufen aller verfügbaren Authorities
function getAuthorities(PDO $pdo)
{
    try {
        $stmt = $pdo->prepare("
            SELECT id, display_name, name, logo_url, primary_color, secondary_color, app_title, default_background
            FROM kdd_authorities 
            WHERE active = 1 
            ORDER BY display_name
        ");
        $stmt->execute();
        $authorities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process authorities and add frontend-compatible fields
        foreach ($authorities as &$authority) {
            $authority['text'] = $authority['display_name'];
            $authority['value'] = (int)$authority['id'];
            $authority['category'] = 'GTA RP Server';
            // app_title fallback to display_name
            $authority['app_title'] = $authority['app_title'] ?: $authority['display_name'];
        }

        http_response_code(200);
        echo json_encode(["authorities" => $authorities]);
    } catch (\PDOException $e) {
        error_log("Database error in getAuthorities: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "An error occurred while fetching authorities."]);
    }
}

/**
 * Validates a site parameter and returns authority branding data
 */
function validateSite(PDO $pdo)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $siteName = $data['site'] ?? null;
    
    if (!$siteName) {
        http_response_code(400);
        echo json_encode(["error" => "Site parameter missing"]);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, name, display_name, logo_url, primary_color, secondary_color, app_title, default_background
            FROM kdd_authorities 
            WHERE name = ? AND active = 1
        ");
        $stmt->execute([$siteName]);
        $authority = $stmt->fetch();
        
        if ($authority) {
            echo json_encode([
                "valid" => true,
                "authority" => [
                    "id" => (int)$authority['id'],
                    "name" => $authority['name'],
                    "display_name" => $authority['display_name'],
                    "text" => $authority['display_name'], // Für Dropdown-Kompatibilität
                    "value" => (int)$authority['id'], // Für Dropdown-Kompatibilität  
                    "logo_url" => $authority['logo_url'],
                    "primary_color" => $authority['primary_color'],
                    "secondary_color" => $authority['secondary_color'],
                    "app_title" => $authority['app_title'] ?: $authority['display_name'],
                    "default_background" => $authority['default_background'],
                    "category" => 'GTA RP Server'
                ]
            ]);
        } else {
            echo json_encode([
                "valid" => false,
                "error" => "Site not found or inactive"
            ]);
        }
    } catch (PDOException $e) {
        error_log("Error validating site: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}
