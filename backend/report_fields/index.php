<?php
/**
 * API endpoint for report fields
 * Handles CRUD operations for report custom fields
 */

declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../bootstrap.php';

// --- Authentication ---
try {
    require_once __DIR__ . '/../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../db.php'; // Defines $pdo
} catch (Exception $e) {
    http_response_code(500); echo json_encode(["error" => "Database connection failed."]); exit();
}

require_once __DIR__ . '/../logging/logging.php';
require_once __DIR__ . '/../utils/permission_helper.php';

// --- Role Check ---
$userPermissions = $decoded_jwt->permissions ?? [];
if (!hasPermission($userPermissions, 'ADMIN') && !hasAllPermissions($userPermissions)) {
    http_response_code(403); echo json_encode(["error" => "Forbidden. Admin role or ALL_PERMISSIONS required."]); exit();
}

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority)) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}

// Get request action
$action = $_GET['action'] ?? '';

// Get report fields by authority
function getReportFieldsByAuthority($pdo, $authorityId) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id, 
                authority_id, 
                field_name, 
                field_type, 
                field_label, 
                field_required, 
                field_options, 
                created_at, 
                updated_at 
            FROM kdd_report_fields 
            WHERE authority_id = ? OR authority_id IS NULL
            ORDER BY field_label
        ");
        $stmt->execute([$authorityId]);
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Database error getting report fields: " . $e->getMessage());
        return [];
    }
}

// Get all report fields
function getAllReportFields($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id, 
                authority_id, 
                field_name, 
                field_type, 
                field_label, 
                field_required, 
                field_options, 
                created_at, 
                updated_at 
            FROM kdd_report_fields 
            ORDER BY field_label
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Database error getting all report fields: " . $e->getMessage());
        return [];
    }
}

// Get report field by ID
function getReportFieldById($pdo, $id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id, 
                authority_id, 
                field_name, 
                field_type, 
                field_label, 
                field_required, 
                field_options, 
                created_at, 
                updated_at 
            FROM kdd_report_fields 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (\PDOException $e) {
        error_log("Database error getting report field by ID: " . $e->getMessage());
        return null;
    }
}

// Add new report field
function addReportField($pdo, $data, $userId, $sessionAuthorityId) {
    try {
        $fieldName = generateSlug($data['field_label']);

        $stmt = $pdo->prepare("
            INSERT INTO kdd_report_fields (
                authority_id,
                field_name,
                field_type,
                field_label,
                field_required,
                field_options
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        $authorityId = $data['authority_id'] !== '' ? $data['authority_id'] : null;
        $stmt->execute([
            $authorityId,
            $fieldName,
            $data['field_type'],
            $data['field_label'],
            $data['field_required'] ? 1 : 0,
            $data['field_options']
        ]);

        $field_id = $pdo->lastInsertId();

        // Log field creation
        $changes = [
            ['column_name' => 'field_name', 'old_value' => null, 'new_value' => $fieldName],
            ['column_name' => 'field_type', 'old_value' => null, 'new_value' => $data['field_type']],
            ['column_name' => 'field_label', 'old_value' => null, 'new_value' => $data['field_label']],
            ['column_name' => 'field_required', 'old_value' => null, 'new_value' => $data['field_required'] ? 1 : 0]
        ];
        logDatabaseChange($sessionAuthorityId, $pdo, 'INSERT', 'kdd_report_fields', $field_id, $userId, $changes);

        return [
            'id' => $field_id,
            'success' => true
        ];
    } catch (\PDOException $e) {
        error_log("Database error adding report field: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Database error adding report field'
        ];
    }
}

// Update existing report field
function updateReportField($pdo, $id, $data, $userId, $sessionAuthorityId) {
    try {
        // Get old data for logging
        $oldField = getEntryById($pdo, $id, 'kdd_report_fields', null); // null because this table may have null authority_id

        $stmt = $pdo->prepare("
            UPDATE kdd_report_fields
            SET
                authority_id = ?,
                field_type = ?,
                field_label = ?,
                field_required = ?,
                field_options = ?
            WHERE id = ?
        ");

        $authorityId = $data['authority_id'] !== '' ? $data['authority_id'] : null;
        $fieldRequired = $data['field_required'] ? 1 : 0;

        $stmt->execute([
            $authorityId,
            $data['field_type'],
            $data['field_label'],
            $fieldRequired,
            $data['field_options'],
            $id
        ]);

        // Log field update
        if ($oldField) {
            $changes = [];
            if (($oldField['authority_id'] ?? null) != $authorityId) {
                $changes[] = ['column_name' => 'authority_id', 'old_value' => $oldField['authority_id'] ?? null, 'new_value' => $authorityId];
            }
            if ($oldField['field_type'] != $data['field_type']) {
                $changes[] = ['column_name' => 'field_type', 'old_value' => $oldField['field_type'], 'new_value' => $data['field_type']];
            }
            if ($oldField['field_label'] != $data['field_label']) {
                $changes[] = ['column_name' => 'field_label', 'old_value' => $oldField['field_label'], 'new_value' => $data['field_label']];
            }
            if ($oldField['field_required'] != $fieldRequired) {
                $changes[] = ['column_name' => 'field_required', 'old_value' => $oldField['field_required'], 'new_value' => $fieldRequired];
            }
            if (!empty($changes)) {
                logDatabaseChange($sessionAuthorityId, $pdo, 'UPDATE', 'kdd_report_fields', $id, $userId, $changes);
            }
        }

        return [
            'success' => true
        ];
    } catch (\PDOException $e) {
        error_log("Database error updating report field: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Database error updating report field'
        ];
    }
}

// Delete report field
function deleteReportField($pdo, $id, $userId, $sessionAuthorityId) {
    try {
        // Get old data before deletion for logging
        $oldField = getEntryById($pdo, $id, 'kdd_report_fields', null); // null because this table may have null authority_id

        $stmt = $pdo->prepare("DELETE FROM kdd_report_fields WHERE id = ?");
        $stmt->execute([$id]);

        // Log hard delete
        if ($oldField) {
            $changes = [
                ['column_name' => 'PERMANENT_DELETE', 'old_value' => json_encode($oldField), 'new_value' => null]
            ];
            logDatabaseChange($sessionAuthorityId, $pdo, 'DELETE', 'kdd_report_fields', $id, $userId, $changes);
        }

        return [
            'success' => true
        ];
    } catch (\PDOException $e) {
        error_log("Database error deleting report field: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Database error deleting report field'
        ];
    }
}

// Helper function to generate a slug for field_name
function generateSlug($name) {
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9_]/', '_', $slug);
    $slug = preg_replace('/_+/', '_', $slug);
    $slug = trim($slug, '_');
    
    return $slug;
}

// Process the request based on the action
switch ($action) {
    case 'getAll':
        echo json_encode(getAllReportFields($pdo));
        break;
        
    case 'getByAuthority':
        $authorityId = $_GET['authority_id'] ?? null;
        
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing authority_id parameter']);
            break;
        }
        
        echo json_encode(getReportFieldsByAuthority($pdo, $authorityId));
        break;
        
    case 'getById':
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id parameter']);
            break;
        }
        
        $field = getReportFieldById($pdo, $id);
        
        if (!$field) {
            http_response_code(404);
            echo json_encode(['error' => 'Report field not found']);
            break;
        }
        
        echo json_encode($field);
        break;
        
    case 'add':
        // Get POST data
        $inputJSON = file_get_contents('php://input');
        $data = json_decode($inputJSON, true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            break;
        }
        
        // Validate data
        if (!isset($data['field_label']) || !isset($data['field_type'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }
        
        // Add new field
        $result = addReportField($pdo, $data, $userId, $authorityId);

        if (!$result['success']) {
            http_response_code(500);
        }

        echo json_encode($result);
        break;
        
    case 'update':
        // Get POST data
        $inputJSON = file_get_contents('php://input');
        $data = json_decode($inputJSON, true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            break;
        }
        
        // Get ID
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id parameter']);
            break;
        }
        
        // Validate data
        if (!isset($data['field_label']) || !isset($data['field_type'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }
        
        // Update field
        $result = updateReportField($pdo, $id, $data, $userId, $authorityId);

        if (!$result['success']) {
            http_response_code(500);
        }

        echo json_encode($result);
        break;
        
    case 'delete':
        // Get ID
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id parameter']);
            break;
        }
        
        // Delete field
        $result = deleteReportField($pdo, $id, $userId, $authorityId);

        if (!$result['success']) {
            http_response_code(500);
        }

        echo json_encode($result);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
} 