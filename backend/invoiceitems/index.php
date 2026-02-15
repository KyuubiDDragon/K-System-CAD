<?php
/**
 * Backend Endpoint: invoiceitems/index.php
 * Handles CRUD operations for predefined Invoice Items.
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
$authority = $decoded_jwt->authority ?? null; // Authority from user's token
$authorityId = $decoded_jwt->authority_id ?? null; // Extract authority ID from token

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority) || $authorityId === null) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'invoice')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to invoice features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    'getInvoiceItems'   => ['module' => 'invoice.items', 'action' => 'read'],
    'addInvoiceItem'    => ['module' => 'invoice.items', 'action' => 'write'],
    'editInvoiceItem'   => ['module' => 'invoice.items', 'action' => 'write'],
    'deleteInvoiceItem' => ['module' => 'invoice.items', 'action' => 'delete'],
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
    $is_post_request = ($request_method === 'POST');

    switch ($action) {
        case 'getInvoiceItems':   if ($request_method === 'GET') getInvoiceItems($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'addInvoiceItem':    if ($is_post_request) addInvoiceItem($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'editInvoiceItem':   if ($is_post_request) editInvoiceItem($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Consider PUT
        case 'deleteInvoiceItem': if ($is_post_request) deleteInvoiceItem($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Consider DELETE

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



// Helper function to get authority ID
function getAuthorityId(PDO $pdo, ?string $authority): ?int {
    if (!$authority) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = ?");
        $stmt->execute([$authority]);
        $authorityId = $stmt->fetchColumn();
        return $authorityId ? (int)$authorityId : null;
    } catch (\PDOException $e) {
        error_log("Error fetching authority ID: " . $e->getMessage());
        return null;
    }
}

// --- Function Implementations (PDO Refactored) ---

function getInvoiceItems(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $sql = "SELECT * FROM kdd_invoice_items WHERE authority_id = ? AND is_deleted = 0 ORDER BY item_name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($items);
    } catch (\PDOException $e) {
        error_log("DB error in getInvoiceItems ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve invoice items."]);
    }
}

function addInvoiceItem(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    // Try to get data from both POST and JSON input
    $data = $_POST; // First try standard POST data
    
    // If we don't have key fields in POST data, try JSON
    if (empty($data['item_name'])) {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'json') !== false) {
            try {
                $jsonData = json_decode(file_get_contents('php://input'), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                    $data = $jsonData;
                }
            } catch (\Exception $e) {
                error_log("JSON parsing error in addInvoiceItem: " . $e->getMessage());
            }
        }
    }
    
    $item_name = $data['item_name'] ?? null;
    $description = $data['description'] ?? '';
    $price = filter_var($data['price'] ?? 0.00, FILTER_VALIDATE_FLOAT);

    if (empty($item_name)) { http_response_code(400); echo json_encode(['error' => 'Item name is required.']); return; }
    if ($price === false) { http_response_code(400); echo json_encode(['error' => 'Invalid price format.']); return; }

    try {
        $sql = "INSERT INTO kdd_invoice_items (item_name, description, price, created_at, authority_id) VALUES (?, ?, ?, NOW(), ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$item_name, $description, $price, $authorityId]);

        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201); echo json_encode(["success" => true, "message" => "Invoice item added successfully.", "id" => $newId]);
            $logData = json_encode(['item_name' => $item_name, 'description' => $description, 'price' => $price]);
            $changes = [['column_name' => 'row_data', 'old_value' => null, 'new_value' => $logData]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "invoice_items", $newId, $requestingUserId, $changes);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to add invoice item.']); }
    } catch (\PDOException $e) {
         if ($e->getCode() == '23000') {
            http_response_code(409); echo json_encode(["error" => "Could not add invoice item: An item with this name might already exist."]);
         } else {
            error_log("DB error in addInvoiceItem ($authority): " . $e->getMessage());
            http_response_code(500); echo json_encode(["error" => "Could not add invoice item."]);
         }
    }
}

function editInvoiceItem(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    // Try to get data from both POST and JSON input
    $data = $_POST; // First try standard POST data
    
    // If we don't have key fields in POST data, try JSON
    if (empty($data['id']) || empty($data['item_name'])) {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'json') !== false) {
            try {
                $jsonData = json_decode(file_get_contents('php://input'), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                    $data = $jsonData;
                }
            } catch (\Exception $e) {
                error_log("JSON parsing error in editInvoiceItem: " . $e->getMessage());
            }
        }
    }
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $item_name = $data['item_name'] ?? null;
    $description = $data['description'] ?? null;
    $price = filter_var($data['price'] ?? null, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);

    if (!$id || empty($item_name)) { http_response_code(400); echo json_encode(['error' => 'ID and item name are required.']); return; }

    try {
        // First check if the item exists and belongs to this authority
        $checkSql = "SELECT id FROM kdd_invoice_items WHERE id = ? AND authority_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$id, $authorityId]);
        
        if ($checkStmt->rowCount() === 0) {
            http_response_code(404); echo json_encode(['error' => 'Invoice item not found or not accessible.']); return;
        }
        
        // Get old values for logging
        $oldSql = "SELECT * FROM kdd_invoice_items WHERE id = ? AND authority_id = ?";
        $oldStmt = $pdo->prepare($oldSql);
        $oldStmt->execute([$id, $authorityId]);
        $oldItem = $oldStmt->fetch(PDO::FETCH_ASSOC);
        
        // Update the invoice item
        $sql = "UPDATE kdd_invoice_items SET item_name = ?, description = ?, price = ?, updated_at = NOW() WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$item_name, $description, $price, $id, $authorityId]);
        
        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Invoice item updated successfully."]);
            
            // Log the changes
            $changes = [];
            if ($oldItem['item_name'] !== $item_name) {
                $changes[] = ['column_name' => 'item_name', 'old_value' => $oldItem['item_name'], 'new_value' => $item_name];
            }
            if ($oldItem['description'] !== $description) {
                $changes[] = ['column_name' => 'description', 'old_value' => $oldItem['description'], 'new_value' => $description];
            }
            if ($oldItem['price'] != $price) { // Using != for numeric comparison
                $changes[] = ['column_name' => 'price', 'old_value' => $oldItem['price'], 'new_value' => $price];
            }
            
            if (!empty($changes)) {
                global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "invoice_items", $id, $requestingUserId, $changes);
            }
        } else {
            http_response_code(404); echo json_encode(['error' => 'Invoice item not found or no changes were made.']);
        }
    } catch (\PDOException $e) {
        if ($e->getCode() == '23000') {
            http_response_code(409); echo json_encode(["error" => "Could not update invoice item: An item with this name might already exist."]);
        } else {
            error_log("DB error in editInvoiceItem ($authority): " . $e->getMessage());
            http_response_code(500); echo json_encode(["error" => "Could not update invoice item."]);
        }
    }
}

function deleteInvoiceItem(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Valid ID is required.']); return; }
    
    try {
        // Check if related invoice entries exist
        $checkSql = "SELECT COUNT(*) FROM kdd_invoice_entries WHERE item_id = ? AND authority_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$id, $authorityId]);
        $relatedCount = $checkStmt->fetchColumn();
        
        if ($relatedCount > 0) {
            http_response_code(409); // Conflict
            echo json_encode([
                'error' => 'Cannot delete this invoice item because it is used in invoices.',
                'related_count' => $relatedCount
            ]);
            return;
        }
        
        // First check if the item exists and belongs to this authority
        $checkItemSql = "SELECT id FROM kdd_invoice_items WHERE id = ? AND authority_id = ?";
        $checkItemStmt = $pdo->prepare($checkItemSql);
        $checkItemStmt->execute([$id, $authorityId]);
        
        if ($checkItemStmt->rowCount() === 0) {
            http_response_code(404); echo json_encode(['error' => 'Invoice item not found or not accessible.']); return;
        }
        
        // Perform soft delete
        $sql = "DELETE FROM kdd_invoice_items WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$id, $authorityId]);
        
        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Invoice item deleted successfully."]);
            
            // Log the deletion
            $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "invoice_items", $id, $requestingUserId, $changes);
        } else {
            http_response_code(404); echo json_encode(['error' => 'Invoice item not found or already deleted.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteInvoiceItem ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not delete invoice item."]);
    }
}



?>