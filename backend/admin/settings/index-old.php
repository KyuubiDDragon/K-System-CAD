<?php
/**
 * Backend Endpoint: admin/settings/index.php
 * Handles updating global settings for a specific authority.
 * Uses Cookie-based Authentication and PDO database connection.
 */
declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../../bootstrap.php';





// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../../db.php'; // Defines $pdo
} catch (Exception $e) {
    http_response_code(500); echo json_encode(["error" => "Database connection failed."]); exit();
}
require_once __DIR__ . '/../../logging/logging.php'; // For logDatabaseChange and helpers

// --- Authentication ---
try {
    require_once __DIR__ . '/../../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority)) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'default')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to default features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? ''; // Expect action via GET or POST
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required ADMIN permissions
// **WICHTIG:** Passe diese Berechtigungsnamen genau an dein System an!
$permissions_map = [
    'updateSettings' => 'ADMIN_WRITE_SETTINGS',
    'getGlobalSettings' => 'ADMIN_READ_SETTINGS', // Neue Aktion hinzufügen
];

$required_permission = $permissions_map[$action] ?? 'ACTION_NOT_DEFINED';
$has_permission = false;

if ($action === '') { http_response_code(400); echo json_encode(["error" => "No action specified."]); exit(); }
if ($required_permission === 'ACTION_NOT_DEFINED') { http_response_code(404); echo json_encode(['error' => 'Invalid action specified.']); exit(); }

// Load permission helper
require_once __DIR__ . '/../../utils/permission_helper.php';




// Check permission levels (ALL > ADMIN_WRITE) - No distinct READ needed here
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} elseif ($required_permission && hasPermission($userPermissions, $required_permission)) {
    $has_permission = true; // Has specific admin permission
}

// --- Execute Action or Deny ---
if ($has_permission) {
    if ($action === 'updateSettings' && $request_method === 'POST') {
        updateSettings($pdo, $userId, $authority);
    }  elseif ($action === 'getGlobalSettings' && $request_method === 'GET') {
        getGlobalSettings($pdo, $authority);
    }else {
        // Action doesn't match or method not allowed
        MethodNotAllowed();
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



// --- Function Implementation (PDO Refactored) ---

/**
 * Updates multiple global settings based on JSON request data.
 */
function updateSettings(PDO $pdo, int $requestingUserId, string $authority): void {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    // Get data from JSON
    $data = getJsonRequestData();
    if (!$data) return;
    
    // Expect data within a 'settings' key in the JSON payload
    $settingsData = $data['settings'] ?? null;
    if (!is_array($settingsData)) {
        http_response_code(400); 
        echo json_encode(['error' => 'Invalid or missing settings data in request.']); 
        return;
    }

    // Define which settings are allowed to be updated (all settings that can be edited)
    $allowedSettings = [
        // Grundeinstellungen
        'siteName', 'siteLogo', 'employeeNavigation', 'companyName',
        // Theme-Einstellungen
        'darkMode', 'primaryColor', 'secondaryColor', 'accentColor', 'backgroundColor', 
        'surfaceColor', 'tertiaryColor', 'infoColor', 'successColor', 'warningColor', 'errorColor',
        // Text-auf-Farben
        'onPrimaryColor', 'onSecondaryColor', 'onAccentColor', 'onBackgroundColor', 'onSurfaceColor', 
        'onSuccessColor', 'onInfoColor', 'onWarningColor', 'onErrorColor',
        // Weitere Theme-Einstellungen
        'borderRadius', 'enableGradients',
        // Systemeinstellungen
        'defaultLanguage', 'dateFormat', 'defaultStartPage', 'sessionTimeout', 
        'enableNotifications', 'enableUserTracking',
        // Branding & Export
        'pdfHeaderLogo', 'pdfFooterText', 'exportDateFormat', 'defaultExportFormat',
        // Kontakt & Support
        'supportEmail', 'supportPhone', 'helpPageContent',
        // Andere, fireguard-spezifische Einstellungen
        'extinguisherNr', 'marquee_dashboard'
    ];
    
    // Filter settings to only include allowed ones
    $settingsToUpdate = array_intersect_key($settingsData, array_flip($allowedSettings));

    if (empty($settingsToUpdate)) {
        http_response_code(400); echo json_encode(['error' => 'No valid settings provided for update.']); return;
    }

    try {
        $pdo->beginTransaction();
        $success = true;
        $changes = [];

        // Schleife durch alle Einstellungen
        foreach ($settingsToUpdate as $key => $value) {
            // Boolesche Werte in Strings umwandeln für die Datenbank
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            
            // Numerische Werte in Strings umwandeln
            if (is_numeric($value)) {
                $value = (string)$value;
            }
            
            // Prüfen, ob die Einstellung bereits existiert
            $checkStmt = $pdo->prepare("SELECT id FROM `kdd_global_settings` WHERE authority_id = ? AND key_name = ?");
            $checkStmt->execute([$authorityId, $key]);
            $existingId = $checkStmt->fetchColumn();

            if ($existingId) {
                // Einstellung aktualisieren
                $stmt = $pdo->prepare("UPDATE `kdd_global_settings` SET value = ? WHERE authority_id = ? AND key_name = ?");
                $result = $stmt->execute([$value, $authorityId, $key]);
            } else {
                // Neue Einstellung einfügen
                $stmt = $pdo->prepare("INSERT INTO `kdd_global_settings` (authority_id, key_name, value, created_by) VALUES (?, ?, ?, ?)");
                $result = $stmt->execute([$authorityId, $key, $value, $requestingUserId]);
            }

            if (!$result) {
                $success = false;
                error_log("Failed to update setting '{$key}' for authority '{$authority}'.");
                break; // Stop on first error
            }
            
            // Collect changes for logging
            $changes[] = ['column_name' => $key, 'old_value' => '[previous]', 'new_value' => $value];
        }

        if ($success) {
            $pdo->commit();
            http_response_code(200); echo json_encode(["success" => true, "message" => "Settings updated successfully."]);
            // Log all changes made in this transaction
            if (!empty($changes) && function_exists('logDatabaseChange')) {
                $logSummary = [['column_name' => 'batch_update', 'old_value' => null, 'new_value' => json_encode($settingsToUpdate)]];
                global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "global_settings", 0, $requestingUserId, $logSummary); 
            }
        } else {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(['error' => 'Failed to update one or more settings. Transaction rolled back.']);
        }

    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("DB error in updateSettings [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update settings: " . $e->getMessage()]);
    }
}

/**
 * Funktion zum Abrufen aller Einstellungen
 */
function getGlobalSettings(PDO $pdo, string $authority): void {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    try {
        // Holen Sie alle globalen Einstellungen
        $stmt = $pdo->prepare("SELECT key_name, value FROM `kdd_global_settings` WHERE authority_id = ?");
        $stmt->execute([$authorityId]);
        $settingsRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Konvertieren Sie die Zeilen in ein assoziatives Array
        $settings = [];
        foreach ($settingsRows as $row) {
            $key = $row['key_name'];
            $value = $row['value'];
            
            // Konvertieren von booleschen Werten
            if ($value === 'true') $value = true;
            if ($value === 'false') $value = false;
            
            // Konvertieren von numerischen Werten
            if (is_numeric($value) && strpos($value, '.') === false) {
                $value = (int)$value;
            } else if (is_numeric($value)) {
                $value = (float)$value;
            }
            
            $settings[$key] = $value;
        }
        
        // Antwort zurückgeben
        http_response_code(200);
        echo json_encode([
            "success" => true, 
            "settings" => $settings
        ]);
    } catch (\PDOException $e) {
        error_log("DB error in getGlobalSettings [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not fetch settings."]);
    }
}

?>