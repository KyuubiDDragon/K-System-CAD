<?php
/**
 * Backend Endpoint: admin/map/index.php
 * Handles ADMIN CRUD operations for Map Categories.
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

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority) || $authorityId === null) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'map')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to map features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required ADMIN permissions
// **WICHTIG:** Passe diese Berechtigungsnamen genau an dein System an!
$permissions_map = [
    'getCategories'    => 'ADMIN_READ_MAP',
    'saveCategory'     => 'ADMIN_WRITE_MAP',
    'deleteCategory'   => 'ADMIN_WRITE_MAP', // Assume WRITE includes DELETE based on original
    'getAvailableIcons' => 'ADMIN_READ_MAP',

    // 'getAvailableIcons' action missing in original functions
];

$required_permission = $permissions_map[$action] ?? 'ACTION_NOT_DEFINED';
$has_permission = false;

if ($action === '') { http_response_code(400); echo json_encode(["error" => "No action specified."]); exit(); }
if ($required_permission === 'ACTION_NOT_DEFINED') { http_response_code(404); echo json_encode(['error' => 'Invalid action specified.']); exit(); }

// Load permission helper
require_once __DIR__ . '/../../utils/permission_helper.php';

// Check permission levels (ALL > ADMIN_WRITE > ADMIN_READ)
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} elseif ($required_permission && hasPermission($userPermissions, $required_permission)) {
    $has_permission = true; // Has specific admin permission
} elseif ($required_permission === 'ADMIN_READ_MAP' && hasPermission($userPermissions, 'ADMIN_WRITE_MAP')) {
    $has_permission = true; // WRITE implies READ
}

// --- Execute Action or Deny ---
if ($has_permission) {
    $is_post_request = ($request_method === 'POST');

    switch ($action) {
        case 'getCategories':    if ($request_method === 'GET') getCategories($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'saveCategory':     if ($is_post_request) saveCategory($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Handles Insert/Update
        case 'deleteCategory':   if ($is_post_request) deleteCategory($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Consider DELETE
        case 'getAvailableIcons': if ($request_method === 'GET') getAvailableIcons($pdo, $authority, $authorityId); else MethodNotAllowed(); break;

        // case 'getAvailableIcons': // Action missing
        default: http_response_code(500); echo json_encode(['error' => 'Action routing error or action not implemented.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



// --- Function Implementations (PDO Refactored) ---
function getAvailableIcons(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $stmt = $pdo->prepare("SELECT name, path AS filename FROM kdd_map_icons ORDER BY name ASC");
        $stmt->execute();
        $icons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($icons);
    } catch (\PDOException $e) {
        error_log("DB error in getAvailableIcons: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve icons."]);
    }
}

function getCategories(PDO $pdo, string $authority, int $authorityId): void {
    try {
        // Fetch categories and count associated non-deleted markers using a subquery
        $sql = "SELECT mc.*,
                       (SELECT COUNT(*) FROM kdd_map m WHERE m.authority_id = ? AND m.category_id = mc.id AND m.is_deleted = 0) as marker_count
                FROM kdd_map_category mc
                WHERE mc.authority_id = ? AND mc.is_deleted = 0
                ORDER BY mc.name ASC"; // Order by name
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $authorityId]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Cast count to integer
        $categories = array_map(function($cat) {
            $cat['marker_count'] = (int)$cat['marker_count'];
            $cat['is_deleted'] = (bool)$cat['is_deleted']; // Cast boolean
            return $cat;
        }, $categories);

        http_response_code(200);
        echo json_encode($categories);
    } catch (\PDOException $e) {
        error_log("DB error in getCategories [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve map categories."]);
    }
}

function saveCategory(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    // Use JSON data instead of POST
    $data = getJsonRequestData();
    if (!$data) return;
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $name = $data['name'] ?? null;
    $icon = $data['icon'] ?? null; // Icon might be optional or required

    if (empty(trim($name ?? '')) || empty(trim($icon ?? ''))) {
        http_response_code(400); 
        echo json_encode(['error' => 'Category name and icon are required.']); 
        return;
    }

    try {
        $pdo->beginTransaction();
        $logAction = '';
        $logId = $id;
        // $oldEntry = null;

        if ($id) { // Update existing
            $logAction = 'UPDATE';
            // $oldEntry = getEntryById($pdo, $id, "kdd_map_category");
            // if (!$oldEntry) { throw new \Exception("Category with ID {$id} not found."); }

            $sql = "UPDATE kdd_map_category SET name = ?, icon = ? WHERE authority_id = ? AND id = ? AND is_deleted = 0";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$name, $icon, $authorityId, $id]);
            if (!$success) throw new \Exception("Failed to update category.");
            $rowCount = $stmt->rowCount();

        } else { // Insert new
            $logAction = 'INSERT';
            // Consider adding sort_order if needed
            $sql = "INSERT INTO kdd_map_category (authority_id, name, icon) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$authorityId, $name, $icon]);
            if (!$success) throw new \Exception("Failed to insert category.");
            $logId = $pdo->lastInsertId();
            $rowCount = 1; // Indicate success
        }

        $pdo->commit();

        if ($rowCount > 0) {
            http_response_code($id ? 200 : 201);
            echo json_encode(["success" => true, "message" => "Category saved successfully.", "id" => $logId]);
            // Log change
            // $changes = ... ; logDatabaseChange(...);
        } else {
            http_response_code(200); echo json_encode(["error" => "No changes made to the category (or category not found)."]);
        }

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
         if ($e instanceof \PDOException && $e->getCode() == '23000') { // Handle potential unique name constraint?
            http_response_code(409); echo json_encode(["error" => "Could not save category: Name might already exist."]);
         } else {
            error_log("Error in saveCategory [ADMIN] ($authority): " . $e->getMessage());
            http_response_code(500); echo json_encode(["error" => "Could not save category: " . $e->getMessage()]);
         }
    }
}

function deleteCategory(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    // Use JSON data instead of POST
    $data = getJsonRequestData();
    if (!$data) return;
     
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400); 
        echo json_encode(['error' => 'Invalid or missing category ID.']); 
        return; 
    }

    try {
        // Check if category is empty before deleting
        $sqlCheck = "SELECT COUNT(*) FROM kdd_map WHERE authority_id = ? AND category_id = ? AND is_deleted = 0";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$authorityId, $id]);
        $itemCount = (int) $stmtCheck->fetchColumn();

        if ($itemCount > 0) {
            http_response_code(409); // Conflict
            echo json_encode(['error' => 'Category cannot be deleted because it still contains markers.']);
            return;
        }

        // Soft delete the category
        $sqlDelete = "UPDATE kdd_map_category SET is_deleted = 1 WHERE authority_id = ? AND id = ? AND is_deleted = 0";
        $stmtDelete = $pdo->prepare($sqlDelete);
        $success = $stmtDelete->execute([$authorityId, $id]);

        if ($success && $stmtDelete->rowCount() > 0) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Category marked as deleted."]);
            // Log change
            $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "map_category", $id, $requestingUserId, $changes);
        } elseif ($success) { http_response_code(404); echo json_encode(["error" => "Category not found or already deleted."]);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to execute category deletion.']); }
    } catch (\PDOException $e) {
        error_log("DB error in deleteCategory (ID: $id, Auth: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not delete category."]);
    }
}
?>