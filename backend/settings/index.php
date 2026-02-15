<?php
/**
 * Backend Endpoint: settings/index.php
 * Handles fetching global settings for a specific authority.
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
// require_once __DIR__ . '/../logging/logging.php'; // Not used in this specific file

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

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority)) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'default')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to default features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_GET['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    'getGlobalSettings' => ['module' => 'settings', 'action' => 'read'],
];

$required_permission = $permissions_map[$action] ?? null;
$has_permission = false;

if ($action === '') { http_response_code(400); echo json_encode(["error" => "No action specified."]); exit(); }
if ($required_permission === null) { http_response_code(404); echo json_encode(['error' => 'Invalid action specified.']); exit(); }

// Load permission helper
require_once __DIR__ . '/../utils/permission_helper.php';

$module = $required_permission['module'];
$actionType = $required_permission['action'];

// Check permission levels using module-based permissions
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} elseif (hasModulePermission($userPermissions, $module, $actionType)) {
    $has_permission = true; // Has specific permission
} elseif ($actionType === 'read' && hasModulePermission($userPermissions, $module, 'write')) {
    $has_permission = true; // WRITE implies READ
} elseif ($actionType === 'read' && hasModulePermission($userPermissions, $module, 'delete')) {
    $has_permission = true; // DELETE implies READ
} elseif ($actionType === 'write' && hasModulePermission($userPermissions, $module, 'delete')) {
    $has_permission = true; // DELETE implies WRITE
}

// --- Execute Action or Deny ---
if ($has_permission) {
    if ($request_method === 'GET') { // Only allow GET
        switch ($action) {
            case 'getGlobalSettings':
                getGlobalSettings($pdo, $authority);
                break;
            default:
                http_response_code(500); echo json_encode(['error' => 'Action routing error.']);
                break;
        }
    } else {
        MethodNotAllowed();
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



/**
 * Helper function to get authority ID from the authority name
 * 
 * @param PDO $pdo Database connection
 * @param string|null $authority Authority name
 * @return int|null Authority ID or null if not found
 */
function getAuthorityId(PDO $pdo, ?string $authority): ?int {
    if (!$authority) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = ?");
        $stmt->execute([$authority]);
        $authorityId = $stmt->fetchColumn();
        
        return $authorityId ? (int)$authorityId : null;
    } catch (\PDOException $e) {
        error_log("Error fetching authority_id: " . $e->getMessage());
        return null;
    }
}

// --- Function Implementation (PDO Refactored) ---

function getGlobalSettings(PDO $pdo, string $authority): void {
    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            return;
        }
        
        // Query the global settings table with the authority ID
        $sql = "SELECT key_name, value FROM kdd_global_settings WHERE authority_id = ? ORDER BY key_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);

        // Fetch results directly into a key-value pair array
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        http_response_code(200);
        echo json_encode($settings);
    } catch (\PDOException $e) {
        error_log("DB error in getGlobalSettings ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not retrieve global settings."]);
    }
}
?>