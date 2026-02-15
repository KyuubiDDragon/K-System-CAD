<?php
/**
 * Backend Endpoint: admin/training/index.php
 * Handles ADMIN CRUD operations for Training Definitions and Categories.
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
require_once __DIR__ . '/../../logging/logging.php'; // For logDatabaseChange

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
if (!hasFeatureAccess($pdo, $authorityId, 'training')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to training features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required ADMIN permissions
// **WICHTIG:** Passe diese Berechtigungsnamen genau an dein System an!
$permissions_map = [
    'getTrainings'     => 'ADMIN_READ_TRAINING',
    'getCategories'    => 'ADMIN_READ_TRAINING',
    'saveTrainings'    => 'ADMIN_WRITE_TRAINING',
    'saveCategories'   => 'ADMIN_WRITE_TRAINING',
    'deleteTrainings'  => 'ADMIN_WRITE_TRAINING', // Assumed from original logic
    'deleteCategories' => 'ADMIN_WRITE_TRAINING', // Assumed from original logic
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
} elseif ($required_permission === 'ADMIN_READ_TRAINING' && hasPermission($userPermissions, 'ADMIN_WRITE_TRAINING')) {
    $has_permission = true; // WRITE implies READ
}

// --- Execute Action or Deny ---
if ($has_permission) {
    $is_post_request = ($request_method === 'POST');

    switch ($action) {
        // GET Actions
        case 'getTrainings':     if ($request_method === 'GET') getTrainings($pdo, $authority); else MethodNotAllowed(); break;
        case 'getCategories':    if ($request_method === 'GET') getCategories($pdo, $authority); else MethodNotAllowed(); break;

        // POST Actions (or PUT/DELETE)
        case 'saveTrainings':    if ($is_post_request) saveTrainings($pdo, $userId, $authority); else MethodNotAllowed(); break; // Handles Insert/Update
        case 'saveCategories':   if ($is_post_request) saveCategories($pdo, $userId, $authority); else MethodNotAllowed(); break; // Handles Insert/Update
        case 'deleteTrainings':  if ($is_post_request) deleteTrainings($pdo, $userId, $authority); else MethodNotAllowed(); break; // Consider DELETE
        case 'deleteCategories': if ($is_post_request) deleteCategories($pdo, $userId, $authority); else MethodNotAllowed(); break; // Consider DELETE

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



// --- Function Implementations (PDO Refactored) ---

function getTrainings(PDO $pdo, string $authority): void {
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
        $sql = "SELECT et.id, et.catId, et.name, etc.name AS cat_name, etc.short AS cat_short, etc.sort_order as cat_sort_order 
                FROM `kdd_employee_training` AS et 
                LEFT JOIN `kdd_employee_training_cat` etc ON etc.authority_id = ? AND et.catId = etc.id
                WHERE et.authority_id = ? AND etc.is_deleted = 0
                ORDER BY etc.sort_order, et.sort_order";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $authorityId]);
        $trainings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($trainings);
    } catch (\PDOException $e) {
        error_log("DB error in getTrainings [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not retrieve trainings."]);
    }
}

function getCategories(PDO $pdo, string $authority): void {
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
        $sql = "SELECT * FROM `kdd_employee_training_cat` WHERE authority_id = ? AND is_deleted = 0 ORDER BY sort_order, name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($categories);
    } catch (\PDOException $e) {
        error_log("DB error in getCategories [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not retrieve training categories."]);
    }
}

// Handles INSERT or UPDATE for Trainings
function saveTrainings(PDO $pdo, int $requestingUserId, string $authority): void {
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
    
    // Use JSON data instead of POST
    $data = getJsonRequestData();
    if (!$data) return;
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $catId = filter_var($data['catId'] ?? null, FILTER_VALIDATE_INT);
    $name = $data['name'] ?? null;
    $sort_order = filter_var($data['sort_order'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);

    if (!$catId || empty(trim($name ?? ''))) {
        http_response_code(400); 
        echo json_encode(['error' => 'Category ID and Training name are required.']); 
        return;
    }

    try {
        $pdo->beginTransaction();
        $logAction = ''; $logId = $id; $rowCount = 0;
        $oldEntry = null;

        if ($id) { // Update
            $logAction = 'UPDATE';
            $oldEntry = getEntryById($pdo, $id, "kdd_employee_training");
            if (!$oldEntry) throw new \Exception("Training with ID {$id} not found.");

            $sql = "UPDATE `kdd_employee_training` SET catId = ?, name = ?, sort_order = ? WHERE authority_id = ? AND id = ? AND is_deleted = 0";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$catId, $name, $sort_order, $authorityId, $id]);
            if (!$success) throw new \Exception("Failed to update training.");
            $rowCount = $stmt->rowCount();

        } else { // Insert
            $logAction = 'INSERT';
            $sql = "INSERT INTO `kdd_employee_training` (authority_id, catId, name, sort_order) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$authorityId, $catId, $name, $sort_order]);
            if (!$success) throw new \Exception("Failed to insert training.");
            $logId = (int) $pdo->lastInsertId();
             if (!$logId) throw new \Exception("Failed to retrieve ID for new training.");
            $rowCount = 1;
        }

        $pdo->commit();

        if ($rowCount > 0) {
            http_response_code($id ? 200 : 201); echo json_encode(["success" => true, "message" => "Training saved successfully.", "id" => $logId]);
            // Log change
            if ($logAction === 'INSERT') {
                $changes = [
                    ['column_name' => 'catId', 'old_value' => null, 'new_value' => $catId],
                    ['column_name' => 'name', 'old_value' => null, 'new_value' => $name],
                    ['column_name' => 'sort_order', 'old_value' => null, 'new_value' => $sort_order]
                ];
                logDatabaseChange($authorityId, $pdo, 'INSERT', "employee_training", $logId, $requestingUserId, $changes);
            } elseif ($logAction === 'UPDATE' && $oldEntry) {
                $changes = [];
                if ($oldEntry['catId'] != $catId) {
                    $changes[] = ['column_name' => 'catId', 'old_value' => $oldEntry['catId'], 'new_value' => $catId];
                }
                if ($oldEntry['name'] !== $name) {
                    $changes[] = ['column_name' => 'name', 'old_value' => $oldEntry['name'], 'new_value' => $name];
                }
                if ($oldEntry['sort_order'] != $sort_order) {
                    $changes[] = ['column_name' => 'sort_order', 'old_value' => $oldEntry['sort_order'], 'new_value' => $sort_order];
                }
                if (!empty($changes)) {
                    logDatabaseChange($authorityId, $pdo, 'UPDATE', "employee_training", $logId, $requestingUserId, $changes);
                }
            }
        } else { http_response_code(200); echo json_encode(["error" => "No changes made to the training."]); }

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
         if ($e instanceof \PDOException && $e->getCode() == '23000') { // Unique name? FK constraint on catId?
            http_response_code(409); echo json_encode(["error" => "Could not save training: Name might already exist or category invalid."]);
         } else {
            error_log("Error in saveTrainings [ADMIN] ($authority): " . $e->getMessage());
            http_response_code(500); echo json_encode(["error" => "Could not save training: " . $e->getMessage()]);
         }
    }
}

/**
 * Handles INSERT or UPDATE for Training Categories
 */
function saveCategories(PDO $pdo, int $requestingUserId, string $authority): void {
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
    
    // Use JSON data instead of POST
    $data = getJsonRequestData();
    if (!$data) return;
   
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $name = $data['name'] ?? null;
    $short = $data['short'] ?? ''; // Abbreviation
    $sort_order_input = $data['sort_order'] ?? null;

    if (empty(trim($name ?? ''))) {
        http_response_code(400); 
        echo json_encode(['error' => 'Category name is required.']); 
        return;
    }

    try {
        $pdo->beginTransaction();
        $logAction = ''; $logId = $id; $rowCount = 0;
        $oldEntry = null; // Initialize $oldEntry

        if ($id) { // Update
            $logAction = 'UPDATE';
            // --- KORREKTUR: $oldEntry wird jetzt geholt ---
            $oldEntry = getEntryById($pdo, $id, "kdd_employee_training_cat"); // Fetch old entry
            if (!$oldEntry) { // Check if entry was found
                 $pdo->rollBack(); // Rollback before throwing
                 throw new \Exception("Category with ID {$id} not found for update.");
            }
            // --- Ende Korrektur ---

            // Keep existing sort_order if not provided in update - uses $oldEntry now
            $sort_order = ($sort_order_input !== null && $sort_order_input !== '')
                            ? filter_var($sort_order_input, FILTER_VALIDATE_INT, ['options' => ['default' => 0]])
                            : (int)$oldEntry['sort_order']; // Use old value as default

            $sql = "UPDATE `kdd_employee_training_cat` SET name = ?, short = ?, sort_order = ? WHERE authority_id = ? AND id = ? AND is_deleted = 0";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$name, $short, $sort_order, $authorityId, $id]);
            if (!$success) throw new \Exception("Failed to update category.");
            $rowCount = $stmt->rowCount();

        } else { // Insert
            $logAction = 'INSERT';
             // Get next sort order if not provided
             if ($sort_order_input === null || $sort_order_input === '') {
                $stmtMax = $pdo->query("SELECT MAX(sort_order) FROM `kdd_employee_training_cat` WHERE authority_id = ?");
                $maxSort = $stmtMax->fetchColumn();
                $sort_order = ($maxSort === null || $maxSort === false) ? 0 : (int)$maxSort + 1;
             } else {
                  $sort_order = filter_var($sort_order_input, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
             }

            $sql = "INSERT INTO `kdd_employee_training_cat` (authority_id, name, short, sort_order) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$authorityId, $name, $short, $sort_order]);
            if (!$success) throw new \Exception("Failed to insert category.");
            $logId = (int) $pdo->lastInsertId();
            if (!$logId) throw new \Exception("Failed to retrieve ID for new category.");
            $rowCount = 1;
        }

        $pdo->commit();

        if ($rowCount > 0 || !$id) { // Report success if rows were affected OR if it was a successful insert
            http_response_code($id ? 200 : 201); echo json_encode(["success" => true, "message" => "Category saved successfully.", "id" => $logId]);
            // Log change
            $newData = ['name'=>$name, 'short'=>$short, 'sort_order'=>$sort_order]; // Data that was saved
            $changes = getEntryChanges($oldEntry, $newData); // getEntryChanges needs the correct old data now
             if (!empty($changes)) {
                 global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, $logAction, "employee_training_cat", $logId, $requestingUserId, $changes);
            }
        } else {
            http_response_code(200); echo json_encode(["error" => "No changes made to the category."]);
        }

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
         if ($e instanceof \PDOException && $e->getCode() == '23000') { // Unique name?
            http_response_code(409); echo json_encode(["error" => "Could not save category: Name might already exist."]);
         } else {
            error_log("Error in saveCategories [ADMIN] ($authority): " . $e->getMessage());
            http_response_code(500); echo json_encode(["error" => "Could not save category: " . $e->getMessage()]);
         }
    }
} // End saveCategories function


function deleteTrainings(PDO $pdo, int $requestingUserId, string $authority): void {
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
    
    // Use JSON data instead of POST
    $data = getJsonRequestData();
    if (!$data) return;
     
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400); 
        echo json_encode(['error' => 'Invalid or missing training ID.']); 
        return;
    }

    try {
         // Optional: Check if training is assigned before deleting?
         // $stmtCheck = $pdo->prepare("SELECT 1 FROM `kdd_employee_training_re` WHERE authority_id = ?l WHERE training_id = ? LIMIT 1");
         // $stmtCheck->execute([$id]); if ($stmtCheck->fetchColumn()) { ... return error ... }

        // Soft delete
        $sql = "UPDATE `kdd_employee_training` SET is_deleted = 1 WHERE authority_id = ? AND id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) {
           http_response_code(200); echo json_encode(["success" => true, "message" => "Training marked as deleted."]);
           // Log change
           $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
           global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "employee_training", $id, $requestingUserId, $changes);
        } elseif ($success) { http_response_code(404); echo json_encode(["error" => "Training not found or already deleted."]);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to execute training deletion.']); }
    } catch (\PDOException $e) {
        error_log("DB error in deleteTrainings [ADMIN] (ID: $id, Auth: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not delete training."]);
    }
}

function deleteCategories(PDO $pdo, int $requestingUserId, string $authority): void {
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
        // Check if category contains non-deleted trainings first
        $sqlCheck = "SELECT COUNT(*) FROM `kdd_employee_training` WHERE authority_id = ? AND catId = ? AND is_deleted = 0";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$authorityId, $id]);
        $itemCount = (int) $stmtCheck->fetchColumn();

        if ($itemCount > 0) {
            http_response_code(409); // Conflict
            echo json_encode(['error' => 'Category cannot be deleted because it still contains trainings.']);
            return;
        }

        // Soft delete the category
        $sqlDelete = "UPDATE `kdd_employee_training_cat` SET is_deleted = 1 WHERE authority_id = ? AND id = ? AND is_deleted = 0";
        $stmtDelete = $pdo->prepare($sqlDelete);
        $success = $stmtDelete->execute([$authorityId, $id]);

        if ($success && $stmtDelete->rowCount() > 0) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Category marked as deleted."]);
            // Log change
            $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "employee_training_cat", $id, $requestingUserId, $changes);
        } elseif ($success) { http_response_code(404); echo json_encode(["error" => "Category not found or already deleted."]);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to execute category deletion.']); }
    } catch (\PDOException $e) {
        error_log("DB error in deleteCategories [ADMIN] (ID: $id, Auth: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not delete category."]);
    }
}

?>