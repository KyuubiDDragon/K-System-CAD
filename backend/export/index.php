<?php
/**
 * Backend Endpoint: export/index.php
 * Handles export presets management (CRUD operations).
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

// --- Authentication ---
try {
    require_once __DIR__ . '/../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- User Context & Authority Validation ---
$userId = isset($decoded_jwt->userId) ? (int) $decoded_jwt->userId : null;
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null;
$authorityId = isset($decoded_jwt->authority_id) ? (int) $decoded_jwt->authority_id : null;

if ($userId === null || $authority === null || !is_string($authority) || $authorityId === null) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature access check - employee feature required for export
if (!hasFeatureAccess($pdo, $authorityId, 'employee')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to employee/export features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions
$permissions_map = [
    'getPresets'    => ['module' => 'employee', 'action' => 'read'],
    'savePreset'    => ['module' => 'employee', 'action' => 'read'],
    'deletePreset'  => ['module' => 'employee', 'action' => 'read'],
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
    $has_permission = true;
} elseif ($actionType === 'read' && hasModulePermission($userPermissions, $module, 'delete')) {
    $has_permission = true;
} elseif ($actionType === 'write' && hasModulePermission($userPermissions, $module, 'delete')) {
    $has_permission = true;
}

// --- Execute Action or Deny ---
if ($has_permission) {
    switch ($action) {
        case 'getPresets':    if ($request_method === 'GET') getPresets($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'savePreset':    if ($request_method === 'POST') savePreset($pdo, $userId, $authorityId, $userPermissions); else MethodNotAllowed(); break;
        case 'deletePreset':  if ($request_method === 'DELETE' || $request_method === 'POST') deletePreset($pdo, $userId, $authorityId, $userPermissions); else MethodNotAllowed(); break;
        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();


/**
 * Gets input data from request, either from $_POST, $_GET, or from JSON request body
 * @return array Parsed input data
 */
function getInputData(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';

    if (strpos($contentType, 'json') !== false) {
        $inputData = json_decode(file_get_contents('php://input'), true) ?? [];
        return is_array($inputData) ? $inputData : [];
    }

    if ($requestMethod === 'GET') {
        return $_GET ?? [];
    } else {
        return $_POST ?? [];
    }
}

/**
 * Check if user has admin permissions for export presets
 * @param mixed $permissions User permissions (stdClass from JWT or array)
 */
function hasAdminPermissions($permissions): bool {
    // Use the centralized permission helpers
    if (hasAllPermissions($permissions)) {
        return true;
    }
    // Check for write or delete permissions on employee module
    if (hasModulePermission($permissions, 'employee', 'write')) {
        return true;
    }
    if (hasModulePermission($permissions, 'employee', 'delete')) {
        return true;
    }
    return false;
}


// =============================================================================
// PRESET FUNCTIONS
// =============================================================================

/**
 * Get all presets for the current user (user-specific + global)
 */
function getPresets(PDO $pdo, int $userId, int $authorityId): void {
    try {
        // Fetch user-specific presets and global presets for the authority
        $stmt = $pdo->prepare("
            SELECT
                id,
                user_id,
                name,
                is_global,
                columns,
                options,
                created_at,
                created_by
            FROM kdd_export_presets
            WHERE authority_id = :authority_id
              AND is_deleted = 0
              AND (user_id = :user_id OR is_global = 1)
            ORDER BY is_global DESC, name ASC
        ");
        $stmt->execute([
            ':authority_id' => $authorityId,
            ':user_id' => $userId
        ]);

        $presets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode JSON fields
        foreach ($presets as &$preset) {
            $preset['columns'] = json_decode($preset['columns'], true) ?? [];
            $preset['options'] = json_decode($preset['options'], true) ?? [];
            $preset['is_global'] = (bool) $preset['is_global'];
            $preset['is_own'] = $preset['user_id'] === $userId;
        }

        echo json_encode($presets);
    } catch (PDOException $e) {
        error_log("Error fetching presets: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch presets.']);
    }
}

/**
 * Save a new preset or update existing one
 */
function savePreset(PDO $pdo, int $userId, int $authorityId, $userPermissions): void {
    try {
        $input = getInputData();

        $name = trim($input['name'] ?? '');
        $columns = $input['columns'] ?? [];
        $options = $input['options'] ?? [];
        $isGlobal = (bool) ($input['is_global'] ?? false);
        $presetId = isset($input['id']) ? (int) $input['id'] : null;

        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Preset name is required.']);
            return;
        }

        if (empty($columns) || !is_array($columns)) {
            http_response_code(400);
            echo json_encode(['error' => 'Columns configuration is required.']);
            return;
        }

        // Only admins can create global presets
        if ($isGlobal && !hasAdminPermissions($userPermissions)) {
            http_response_code(403);
            echo json_encode(['error' => 'Only administrators can create global presets.']);
            return;
        }

        $columnsJson = json_encode($columns);
        $optionsJson = json_encode($options);

        if ($presetId) {
            // Update existing preset - check ownership or admin status
            $stmt = $pdo->prepare("
                SELECT user_id, is_global FROM kdd_export_presets
                WHERE id = :id AND authority_id = :authority_id AND is_deleted = 0
            ");
            $stmt->execute([':id' => $presetId, ':authority_id' => $authorityId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                http_response_code(404);
                echo json_encode(['error' => 'Preset not found.']);
                return;
            }

            // Check if user can edit this preset
            $canEdit = ($existing['user_id'] === $userId) || hasAdminPermissions($userPermissions);
            if (!$canEdit) {
                http_response_code(403);
                echo json_encode(['error' => 'You cannot edit this preset.']);
                return;
            }

            $stmt = $pdo->prepare("
                UPDATE kdd_export_presets
                SET name = :name,
                    columns = :columns,
                    options = :options,
                    is_global = :is_global,
                    updated_at = NOW()
                WHERE id = :id AND authority_id = :authority_id
            ");
            $stmt->execute([
                ':name' => $name,
                ':columns' => $columnsJson,
                ':options' => $optionsJson,
                ':is_global' => $isGlobal ? 1 : 0,
                ':id' => $presetId,
                ':authority_id' => $authorityId
            ]);

            echo json_encode(['success' => true, 'id' => $presetId, 'message' => 'Preset updated.']);
        } else {
            // Create new preset
            $stmt = $pdo->prepare("
                INSERT INTO kdd_export_presets
                (user_id, authority_id, name, is_global, columns, options, created_by)
                VALUES (:user_id, :authority_id, :name, :is_global, :columns, :options, :created_by)
            ");
            $stmt->execute([
                ':user_id' => $isGlobal ? null : $userId,
                ':authority_id' => $authorityId,
                ':name' => $name,
                ':is_global' => $isGlobal ? 1 : 0,
                ':columns' => $columnsJson,
                ':options' => $optionsJson,
                ':created_by' => $userId
            ]);

            $newId = $pdo->lastInsertId();
            echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Preset created.']);
        }
    } catch (PDOException $e) {
        error_log("Error saving preset: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save preset: ' . $e->getMessage()]);
    }
}

/**
 * Delete a preset (soft delete)
 */
function deletePreset(PDO $pdo, int $userId, int $authorityId, $userPermissions): void {
    try {
        $input = getInputData();
        $presetId = isset($input['id']) ? (int) $input['id'] : null;

        if (!$presetId) {
            http_response_code(400);
            echo json_encode(['error' => 'Preset ID is required.']);
            return;
        }

        // Check ownership
        $stmt = $pdo->prepare("
            SELECT user_id, is_global FROM kdd_export_presets
            WHERE id = :id AND authority_id = :authority_id AND is_deleted = 0
        ");
        $stmt->execute([':id' => $presetId, ':authority_id' => $authorityId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            http_response_code(404);
            echo json_encode(['error' => 'Preset not found.']);
            return;
        }

        // Check if user can delete this preset
        $canDelete = ($existing['user_id'] === $userId) || hasAdminPermissions($userPermissions);
        if (!$canDelete) {
            http_response_code(403);
            echo json_encode(['error' => 'You cannot delete this preset.']);
            return;
        }

        // Soft delete
        $stmt = $pdo->prepare("
            UPDATE kdd_export_presets
            SET is_deleted = 1, updated_at = NOW()
            WHERE id = :id AND authority_id = :authority_id
        ");
        $stmt->execute([':id' => $presetId, ':authority_id' => $authorityId]);

        echo json_encode(['success' => true, 'message' => 'Preset deleted.']);
    } catch (PDOException $e) {
        error_log("Error deleting preset: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete preset.']);
    }
}
