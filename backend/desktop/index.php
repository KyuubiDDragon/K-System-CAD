<?php
/**
 * Backend Endpoint: desktop/index.php
 * Handles desktop settings operations for the StreamUnity desktop view
 * Uses Cookie-based Authentication and PDO database connection.
 */
declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../bootstrap.php';

// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../db.php'; // Defines $pdo
} catch (Exception $e) {
    http_response_code(500); echo json_encode(["error" => "Database connection failed."]); exit();
}
require_once __DIR__ . '/../logging/logging.php'; // For logDatabaseChange

// --- Authentication ---
try {
    require_once __DIR__ . '/../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

if ($userId === null || !is_int($userId)) {
    http_response_code(401); echo json_encode(["error" => "Unauthorized"]); exit();
}

// Check authority validity
if (empty($authority)) {
    http_response_code(400); echo json_encode(["error" => "Invalid authority specified."]); exit();
}

// --- Request Action Routing ---
$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Try to get action from $_REQUEST first (for GET requests with URL params)
$action = $_REQUEST['action'] ?? '';

// If action is not in $_REQUEST, try to get it from JSON body (for POST/PUT requests)
if (empty($action) && in_array($request_method, ['POST', 'PUT', 'DELETE'])) {
    $json = file_get_contents('php://input');
    $jsonData = json_decode($json, true);
    if ($jsonData && isset($jsonData['action'])) {
        $action = $jsonData['action'];
    }
}

// Basic permission checking
$has_permission = true; // Default permission for user-specific desktop actions

// --- Execute Action or Deny ---
if ($has_permission) {
    $is_post_request = ($request_method === 'POST');
    $is_put_request = ($request_method === 'PUT');
    $is_get_request = ($request_method === 'GET');
    $is_delete_request = ($request_method === 'DELETE');

    switch ($action) {
        // GET Actions
        case 'getSettings':   if ($is_get_request) getDesktopSettings($pdo, $userId, $authority); else MethodNotAllowed(); break;
        case 'getSidebarSettings': if ($is_get_request) getSidebarSettings($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'getLayoutPreference': if ($is_get_request) getLayoutPreference($pdo, $userId, $authorityId); else MethodNotAllowed(); break;

        // POST/PUT Actions
        case 'saveSettings':  if ($is_post_request || $is_put_request) saveDesktopSettings($pdo, $userId, $authority); else MethodNotAllowed(); break;
        case 'saveSidebarSettings': if ($is_post_request || $is_put_request) saveSidebarSettings($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'saveLayoutPreference': if ($is_post_request || $is_put_request) saveLayoutPreference($pdo, $userId, $authorityId); else MethodNotAllowed(); break;

        case 'uploadBackground': if ($is_post_request) uploadBackground($userId); else MethodNotAllowed(); break;

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();





/**
 * Get desktop settings for the current user
 */
function getDesktopSettings(PDO $pdo, int $userId, string $authority): void {
    try {
        // Create table if it doesn't exist
        ensureDesktopSettingsTable($pdo, $authority);
        
        // Get authority ID from authority name
        $authorityId = null;
        if (function_exists('getAuthorityId')) {
            global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        }
        
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            return;
        }
        
        // Retrieve settings
        $sql = "SELECT * FROM `kdd_desktop_settings` WHERE authority_id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $userId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$settings) {
            // Return default settings
            http_response_code(200);
            echo json_encode([
                'user_id' => $userId,
                'background' => '/img/bg.jpg',
                'icon_positions' => '{}',
                'window_layouts' => '{}',
                'theme' => 'dark',
                'widgets' => [
                    'active' => [],
                    'positions' => new stdClass()
                ],
                'created_at' => null,
                'updated_at' => null
            ]);
            return;
        }
        
        // Parse JSON fields if settings exist
        if ($settings) {
            // Parse widget_state and ensure it has the correct structure
            $widgetState = json_decode($settings['widget_state'] ?: '{"active":[],"positions":{}}', true);
            if (!isset($widgetState['active'])) {
                $widgetState['active'] = [];
            }
            if (!isset($widgetState['positions'])) {
                $widgetState['positions'] = new stdClass();
            }
            
            $settings['widgets'] = $widgetState;
            unset($settings['widget_state']); // Remove raw field
        }
        
        // Return settings
        http_response_code(200);
        echo json_encode($settings);
    } catch (\PDOException $e) {
        error_log("DB error in getDesktopSettings: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve desktop settings."]);
    }
}

/**
 * Save desktop settings for the current user
 */
function saveDesktopSettings(PDO $pdo, int $userId, string $authority): void {
    try {
        // Get authority ID from authority name
        $authorityId = null;
        if (function_exists('getAuthorityId')) {
            global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        }

        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            return;
        }

        // Holt die JSON-Daten aus dem Request Body.
        $data = getJsonRequestData();

        // Stellt sicher, dass die Datenbanktabelle existiert.
        ensureDesktopSettingsTable($pdo, $authority);

        // Prüft, ob bereits Einstellungen für diesen Benutzer existieren.
        $sql = "SELECT id FROM `kdd_desktop_settings` WHERE authority_id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $userId]);
        $existingSettings = $stmt->fetch(PDO::FETCH_ASSOC);

        // --- Beginn der korrigierten Datenaufbereitung ---

        // Hintergrundbild: Wert aus Daten oder Standardwert verwenden.
        $background = $data['background'] ?? '/img/bg.jpg';

        // Icon-Positionen:
        // Nimmt den String direkt aus den Daten, falls er existiert und ein String ist,
        // da er bereits vom Frontend JSON-kodiert wurde.
        // Standardwert ist ein leerer JSON-Objekt-String '{}'.
        $iconPositions = (isset($data['icon_positions']) && is_string($data['icon_positions']))
                         ? $data['icon_positions']
                         : '{}';

        // Optional aber empfohlen: Validieren, ob der empfangene String gültiges JSON ist.
        if (json_decode($iconPositions) === null && $iconPositions !== '{}') {
            // Loggt einen Fehler und setzt auf Standard zurück, wenn ungültiges JSON empfangen wurde.
            error_log("Received invalid JSON string for icon_positions from user {$userId} (authority: {$authority}): " . $iconPositions);
            $iconPositions = '{}'; // Standardwert bei ungültigem JSON
        }

        // Fenster-Layouts:
        // Gleiche Logik wie für Icon-Positionen.
        $windowLayouts = (isset($data['window_layouts']) && is_string($data['window_layouts']))
                         ? $data['window_layouts']
                         : '{}';

        // Optionale Validierung für Fenster-Layouts.
        if (json_decode($windowLayouts) === null && $windowLayouts !== '{}') {
            error_log("Received invalid JSON string for window_layouts from user {$userId} (authority: {$authority}): " . $windowLayouts);
            $windowLayouts = '{}'; // Standardwert bei ungültigem JSON
        }

        // Theme: Wert aus Daten oder Standardwert verwenden.
        $theme = $data['theme'] ?? 'dark';

        // Widget State: JSON-String für Widget-Einstellungen
        $widgetState = (isset($data['widget_state']) && is_string($data['widget_state']))
                       ? $data['widget_state']
                       : '{"active":[],"positions":{}}';

        // Optionale Validierung für Widget State
        if (json_decode($widgetState) === null && $widgetState !== '{}') {
            error_log("Received invalid JSON string for widget_state from user {$userId} (authority: {$authority}): " . $widgetState);
            $widgetState = '{"active":[],"positions":{}}'; // Standardwert bei ungültigem JSON
        }

        // --- Ende der korrigierten Datenaufbereitung ---

        // Fährt mit INSERT oder UPDATE fort, je nachdem, ob Einstellungen existieren.
        if ($existingSettings) {
            // Aktualisiert vorhandene Einstellungen.
            $sql = "UPDATE `kdd_desktop_settings` SET background = ?, icon_positions = ?, window_layouts = ?, theme = ?, widget_state = ?, updated_at = NOW() WHERE authority_id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            // Führt die Abfrage mit den *korrigierten* Variablen aus ($iconPositions, $windowLayouts sind jetzt einfache JSON-Strings).
            $success = $stmt->execute([$background, $iconPositions, $windowLayouts, $theme, $widgetState, $authorityId, $userId]);

            if ($success) {
                // Gibt eine Erfolgsantwort zurück.
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Desktop settings updated']);
            } else {
                // Gibt eine Fehlerantwort zurück.
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update desktop settings']);
            }
        } else {
            // Fügt neue Einstellungen ein.
            $sql = "INSERT INTO `kdd_desktop_settings` (authority_id, user_id, background, icon_positions, window_layouts, theme, widget_state, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
             // Führt die Abfrage mit den *korrigierten* Variablen aus.
            $success = $stmt->execute([$authorityId, $userId, $background, $iconPositions, $windowLayouts, $theme, $widgetState]);

            if ($success) {
                // Gibt eine Erfolgsantwort zurück (201 Created für neue Ressourcen).
                http_response_code(201);
                echo json_encode(['success' => true, 'message' => 'Desktop settings created']);
            } else {
                 // Gibt eine Fehlerantwort zurück.
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create desktop settings']);
            }
        }
    } catch (\PDOException $e) {
        // Loggt Datenbankfehler.
        error_log("DB error in saveDesktopSettings for user {$userId} (authority: {$authority}): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error occurred while saving desktop settings."]);
    } catch (\Exception $e) {
         // Fängt andere potenzielle Fehler ab (z.B. von getJsonRequestData).
         error_log("General error in saveDesktopSettings for user {$userId} (authority: {$authority}): " . $e->getMessage());
         http_response_code(500);
         echo json_encode(["error" => "An unexpected error occurred while saving desktop settings."]);
    }
}

/**
 * Ensure the desktop settings table exists
 */
function ensureDesktopSettingsTable(PDO $pdo, string $authority): void {
    $tableName = "kdd_desktop_settings";
    
    // Check if table exists
    $tableExists = false;
    try {
        $pdo->query("SELECT 1 FROM $tableName LIMIT 1");
        $tableExists = true;
    } catch (\PDOException $e) {
        // Table doesn't exist
    }
    
    if (!$tableExists) {
        // Create table
        $sql = "CREATE TABLE $tableName (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            authority_id INT(11) NOT NULL,
            user_id INT(11) UNSIGNED NOT NULL,
            background VARCHAR(255) NOT NULL DEFAULT '/img/bg.jpg',
            icon_positions LONGTEXT NOT NULL,
            window_layouts LONGTEXT NOT NULL,
            theme VARCHAR(50) NOT NULL DEFAULT 'dark',
            widget_state LONGTEXT NOT NULL DEFAULT '{\"active\":[],\"positions\":{}}',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY authority_user (authority_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
    }
}

/**
 * Verarbeitet den Upload eines Hintergrundbildes
 *
 * @param int $userId ID des Benutzers
 * @return void
 */
function uploadBackground(int $userId): void {
    try {
        // Prüfen, ob ein Bild hochgeladen wurde
        if (!isset($_FILES['background']) || $_FILES['background']['error'] !== UPLOAD_ERR_OK) {
            SendError('No file uploaded or upload error', 400);
            return;
        }

        // Überprüfen des Dateityps
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileType = $_FILES['background']['type'];
        if (!in_array($fileType, $allowedTypes)) {
            SendError('Invalid file type. Only JPEG and PNG are allowed.', 400);
            return;
        }

        // Dateierweiterung basierend auf Dateityp bestimmen
        $extension = match($fileType) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            default => 'jpg',
        };
        $timestamp = time();
        // Fester Dateiname basierend auf der Benutzer-ID
        $filename = $timestamp . '_user_' . $userId . '_' . $timestamp . '.' . $extension;

        // Basis-Upload-Verzeichnis aus ENV oder Standardpfad
        $uploadBaseDir = $_ENV['UPLOAD_BASE_DIR'] ?? __DIR__ . '/../uploads';
        $uploadDir = rtrim($uploadBaseDir, '/') . '/desktop/background/';
        
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Prüfen, ob bereits Bilder für diesen Benutzer existieren und diese löschen
        $existingFiles = glob($uploadDir . 'background_user_' . $userId . '.*');
        foreach ($existingFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
                error_log("Deleted old background image: " . $file);
            }
        }

        // Bild verarbeiten und speichern
        $tempFile = $_FILES['background']['tmp_name'];
        list($width, $height) = getimagesize($tempFile);

        // Maximale Dimensionen für die Komprimierung
        $maxWidth = 1920;
        $maxHeight = 1080;

        // Neues Bild erstellen, wenn nötig skaliert
        if ($width > $maxWidth || $height > $maxHeight) {
            // Skalierungsverhältnis berechnen
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = $width * $ratio;
            $newHeight = $height * $ratio;

            // Neues Bild erstellen und skalieren
            switch ($fileType) {
                case 'image/jpeg':
                case 'image/jpg':
                    $source = imagecreatefromjpeg($tempFile);
                    $destination = imagecreatetruecolor((int)$newWidth, (int)$newHeight);
                    imagecopyresampled($destination, $source, 0, 0, 0, 0, (int)$newWidth, (int)$newHeight, $width, $height);
                    imagejpeg($destination, $uploadDir . $filename, 85); // 85% Qualität
                    imagedestroy($source);
                    imagedestroy($destination);
                    break;
                
                case 'image/png':
                    // Suppress libpng warnings about color profiles
                    $source = @imagecreatefrompng($tempFile);
                    $destination = imagecreatetruecolor((int)$newWidth, (int)$newHeight);
                    // Transparenz erhalten
                    imagealphablending($destination, false);
                    imagesavealpha($destination, true);
                    imagecopyresampled($destination, $source, 0, 0, 0, 0, (int)$newWidth, (int)$newHeight, $width, $height);
                    imagepng($destination, $uploadDir . $filename, 9); // 0-9, wobei 9 die höchste Kompression ist
                    imagedestroy($source);
                    imagedestroy($destination);
                    break;
            }
        } else {
            // Wenn das Bild bereits klein genug ist, einfach verschieben
            move_uploaded_file($tempFile, $uploadDir . $filename);
        }

        // Basis-URL aus ENV oder Standardpfad
        $baseUrl = $_ENV['PUBLIC_UPLOAD_URL'] ?? '/backend/uploads';
        $relativePath = rtrim($baseUrl, '/') . '/desktop/background/' . $filename;

        // Erfolg zurückmelden
        SendSuccess([
            'success' => true,
            'path' => $relativePath,
            'message' => 'Background image uploaded successfully.'
        ]);
    } catch (Exception $e) {
        error_log("Error uploading background image: " . $e->getMessage());
        SendError('Failed to upload background image: ' . $e->getMessage(), 500);
    }
}

/**
 * ============================================
 * SIDEBAR LAYOUT SETTINGS
 * ============================================
 */

/**
 * Get sidebar settings for the current user
 */
function getSidebarSettings(PDO $pdo, int $userId, ?int $authorityId): void {
    try {
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            return;
        }

        // Ensure table exists
        ensureUserSettingsTable($pdo);

        // Retrieve sidebar settings
        $sql = "SELECT setting_value FROM kdd_user_settings
                WHERE user_id = ? AND authority_id = ? AND setting_key = 'sidebar_settings'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $authorityId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['setting_value']) {
            // Return stored settings
            http_response_code(200);
            echo $result['setting_value']; // Already JSON
        } else {
            // Return default settings
            http_response_code(200);
            echo json_encode([
                'collapsed' => false,
                'width' => 280,
                'pinnedItems' => [],
                'recentlyVisited' => []
            ]);
        }
    } catch (\PDOException $e) {
        error_log("DB error in getSidebarSettings: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Could not retrieve sidebar settings.']);
    }
}

/**
 * Save sidebar settings for the current user
 */
function saveSidebarSettings(PDO $pdo, int $userId, ?int $authorityId): void {
    try {
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            return;
        }

        // Get JSON data from request
        $data = getJsonRequestData();

        if (!isset($data['settings'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing settings data']);
            return;
        }

        // Ensure table exists
        ensureUserSettingsTable($pdo);

        // Convert settings to JSON
        $settingsJson = json_encode($data['settings']);

        // Insert or update
        $sql = "INSERT INTO kdd_user_settings (user_id, authority_id, setting_key, setting_value, updated_at)
                VALUES (?, ?, 'sidebar_settings', ?, NOW())
                ON DUPLICATE KEY UPDATE
                    setting_value = VALUES(setting_value),
                    updated_at = NOW()";

        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$userId, $authorityId, $settingsJson]);

        if ($success) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Sidebar settings saved']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save sidebar settings']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in saveSidebarSettings: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred while saving sidebar settings.']);
    }
}

/**
 * Get layout preference for the current user
 */
function getLayoutPreference(PDO $pdo, int $userId, ?int $authorityId): void {
    try {
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            return;
        }

        // Ensure table exists
        ensureUserSettingsTable($pdo);

        // Retrieve layout preference
        $sql = "SELECT setting_value FROM kdd_user_settings
                WHERE user_id = ? AND authority_id = ? AND setting_key = 'layout_preference'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $authorityId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['setting_value']) {
            // Return stored preference
            http_response_code(200);
            echo $result['setting_value']; // Already JSON
        } else {
            // Return null preference - let frontend show selection dialog
            http_response_code(200);
            echo json_encode([
                'preference' => null,
                'alwaysAsk' => false
            ]);
        }
    } catch (\PDOException $e) {
        error_log("DB error in getLayoutPreference: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Could not retrieve layout preference.']);
    }
}

/**
 * Save layout preference for the current user
 */
function saveLayoutPreference(PDO $pdo, int $userId, ?int $authorityId): void {
    try {
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            return;
        }

        // Get JSON data from request
        $data = getJsonRequestData();

        // Ensure table exists
        ensureUserSettingsTable($pdo);

        // Build preference object
        $preference = [
            'preference' => $data['preference'] ?? null,
            'alwaysAsk' => $data['alwaysAsk'] ?? false
        ];

        $preferenceJson = json_encode($preference);

        // Insert or update
        $sql = "INSERT INTO kdd_user_settings (user_id, authority_id, setting_key, setting_value, updated_at)
                VALUES (?, ?, 'layout_preference', ?, NOW())
                ON DUPLICATE KEY UPDATE
                    setting_value = VALUES(setting_value),
                    updated_at = NOW()";

        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$userId, $authorityId, $preferenceJson]);

        if ($success) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Layout preference saved']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save layout preference']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in saveLayoutPreference: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred while saving layout preference.']);
    }
}

/**
 * Ensure the user_settings table exists
 */
function ensureUserSettingsTable(PDO $pdo): void {
    $tableName = "kdd_user_settings";

    // Check if table exists
    $tableExists = false;
    try {
        $pdo->query("SELECT 1 FROM $tableName LIMIT 1");
        $tableExists = true;
    } catch (\PDOException $e) {
        // Table doesn't exist
    }

    if (!$tableExists) {
        // Create table
        $sql = "CREATE TABLE $tableName (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT(11) UNSIGNED NOT NULL,
            authority_id INT(11) NOT NULL,
            setting_key VARCHAR(100) NOT NULL,
            setting_value LONGTEXT NOT NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_authority_key (user_id, authority_id, setting_key),
            KEY idx_user_id (user_id),
            KEY idx_authority_id (authority_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }
}

// Hilfsfunktionen für API-Antworten
function SendSuccess(array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function SendError(string $message, int $statusCode = 400): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit;
}

function NotFound(): void {
    SendError('Not found', 404);
} 