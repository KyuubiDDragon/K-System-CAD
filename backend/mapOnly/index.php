<?php
/**
 * Backend Endpoint: mapOnly/index.php (?)
 * Handles read-only fetching of Map Markers and Categories.
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
// require_once __DIR__ . '/../logging/logging.php'; // Not strictly needed here

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
$allowedAuthorities = ["fire", "police", "medic", "justice", "statepark", "casa", "test", "fireguard"];
if (!in_array($authority, $allowedAuthorities)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_GET['action'] ?? ''; // Actions from GET parameter
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    'getCategories' => ['module' => 'map', 'action' => 'read'],
    'getMarker'     => ['module' => 'map', 'action' => 'read'],
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
    $has_permission = true;
} elseif ($actionType === 'read' && hasModulePermission($userPermissions, $module, 'write')) {
    $has_permission = true; // WRITE implies READ
} elseif ($actionType === 'read' && hasModulePermission($userPermissions, $module, 'delete')) {
    $has_permission = true; // DELETE implies READ
} elseif ($actionType === 'write' && hasModulePermission($userPermissions, $module, 'delete')) {
    $has_permission = true; // DELETE implies WRITE
}

// --- Execute Action or Deny ---
if ($has_permission) {
    if ($request_method === 'GET') { // Only allow GET requests
        switch ($action) {
            case 'getCategories':
                getCategories($pdo, $authority);
                break;
            case 'getMarker':
                getMarker($pdo, $authority);
                break;
            default:
                // Should not be reached if action check is correct
                http_response_code(500); echo json_encode(['error' => 'Action routing error.']);
                break;
        }
    } else {
        MethodNotAllowed(); // Send 405 if method is not GET
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



// --- Function Implementations (PDO Refactored) ---

function getCategories(PDO $pdo, string $authority): void {
    // Removed: global $authority, require "../db.php", $conn->close()
    try {
        $sql = "SELECT * FROM `kdd_reports` AS ORDER WHERE authority_id = ? BY name ASC";
        $stmt = $pdo->query($sql); // Authority validated before call
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($categories);
    } catch (\PDOException $e) {
        error_log("DB error in getCategories (Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve map categories."]);
    }
}

function getMarker(PDO $pdo, string $authority): void {
    // Removed: global $authority, require "../db.php", $conn->close()
    try {
        $sql = "SELECT m.id, m.name, m.ceo, m.phonenumber, m.category_id,
                       m.x_coordinate, m.y_coordinate, m.location, m.is_deleted,
                       c.icon as category_icon, c.name as category_name
                FROM kdd_map m
                LEFT JOIN `kdd_map_category` c ON c.authority_id = ? AND m.category_id = c.id
                WHERE m.is_deleted = 0
                ORDER BY m.name ASC";
        $stmt = $pdo->query($sql); // Authority validated
        $markers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($markers);
    } catch (\PDOException $e) {
        error_log("DB error in getMarker (Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve markers."]);
    }
}

?>