<?php
/**
 * Backend Endpoint: notes/index.php
 * Handles CRUD operations for Desktop Notes, grouped by authority.
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
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null; // Authority is key here
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
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to global features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions
$permissions_map = [
    'getNotes'    => 'CAN_LOGIN',
    'createNote'  => 'CAN_LOGIN',
    'updateNote'  => 'CAN_LOGIN',
    'deleteNote'  => 'CAN_LOGIN'
];

$required_permission = $permissions_map[$action] ?? 'ACTION_NOT_DEFINED';
$has_permission = false;

if ($action === '') { http_response_code(400); echo json_encode(["error" => "No action specified."]); exit(); }
if ($required_permission === 'ACTION_NOT_DEFINED') { http_response_code(404); echo json_encode(['error' => 'Invalid action specified.']); exit(); }

// Load permission helper
require_once __DIR__ . '/../utils/permission_helper.php';

// Check permission levels
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} elseif ($required_permission && hasPermission($userPermissions, $required_permission)) {
    $has_permission = true; // Has the specific permission
}

// --- Execute Action or Deny ---
if ($has_permission) {
    $is_post_request = ($request_method === 'POST');
    $is_put_request = ($request_method === 'PUT');
    $is_delete_request = ($request_method === 'DELETE');

    switch ($action) {
        // GET Actions
        case 'getNotes':     if ($request_method === 'GET') getNotes($pdo, $userId, $authority); else MethodNotAllowed(); break;
        
        // POST/PUT/DELETE Actions
        case 'createNote':   if ($is_post_request) createNote($pdo, $userId, $authority); else MethodNotAllowed(); break;
        case 'updateNote':   if ($is_post_request || $is_put_request) updateNote($pdo, $userId, $authority); else MethodNotAllowed(); break;
        case 'deleteNote':   if ($is_post_request || $is_delete_request) deleteNote($pdo, $userId, $authority); else MethodNotAllowed(); break;
        
        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



/**
 * Gets the authority ID from the database
 * 
 * @param PDO $pdo The database connection
 * @param string|null $authority The authority name
 * @return int|null The authority ID or null if not found
 */
function getAuthorityId(PDO $pdo, ?string $authority): ?int {
    if (!$authority) return null;
    
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


// --- Function Implementations ---

/**
 * Retrieves all notes for the current user
 */
function getNotes(PDO $pdo, int $userId, string $authority): void {
    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        
        $sql = "SELECT * FROM `kdd_notes` WHERE authority_id = ? AND user_id = ? ORDER BY updated_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $userId]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode JSON data
        foreach ($notes as &$note) {
            if (isset($note['position']) && is_string($note['position'])) {
                $note['position'] = json_decode($note['position'], true);
            }
            if (isset($note['tags']) && is_string($note['tags'])) {
                $note['tags'] = json_decode($note['tags'], true);
            } else {
                $note['tags'] = [];
            }
        }
        
        http_response_code(200);
        echo json_encode($notes);
    } catch (\PDOException $e) {
        error_log("DB error in getNotes: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve notes."]);
    }
}

/**
 * Creates a new note for the current user
 */
function createNote(PDO $pdo, int $userId, string $authority): void {
    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        
        $data = getJsonRequestData();
        
        // Check required fields
        if (empty($data['content'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Note content is required']);
            return;
        }
        
        // Prepare data
        $content = $data['content'];
        $color = $data['color'] ?? '#fbbf24'; // Default to amber
        $position = json_encode($data['position'] ?? ['x' => 0, 'y' => 0]);
        $tags = isset($data['tags']) && is_array($data['tags']) ? json_encode($data['tags']) : '[]';
        
        $sql = "INSERT INTO `kdd_notes` (authority_id, user_id, content, color, position, tags) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $userId, $content, $color, $position, $tags]);
        
        if ($success) {
            $noteId = $pdo->lastInsertId();

            // Fetch the new note to return it
            $sql = "SELECT * FROM `kdd_notes` WHERE authority_id = ? AND id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$authorityId, $noteId]);
            $note = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Decode JSON data
            if (isset($note['position']) && is_string($note['position'])) {
                $note['position'] = json_decode($note['position'], true);
            }
            if (isset($note['tags']) && is_string($note['tags'])) {
                $note['tags'] = json_decode($note['tags'], true);
            }
            
            http_response_code(201);
            echo json_encode($note);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create note']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in createNote: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not create note."]);
    }
}

/**
 * Updates an existing note
 */
function updateNote(PDO $pdo, int $userId, string $authority): void {
    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        
        $data = getJsonRequestData();
        $noteId = $_REQUEST['id'] ?? null;
        
        if (!$noteId) {
            http_response_code(400);
            echo json_encode(['error' => 'Note ID is required']);
            return;
        }
        
        // Verify the note belongs to the user
        $sql = "SELECT * FROM `kdd_notes` WHERE authority_id = ? AND id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $noteId, $userId]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$note) {
            http_response_code(404);
            echo json_encode(['error' => 'Note not found or access denied']);
            return;
        }
        
        // Decode JSON data for comparison
        if (isset($note['position']) && is_string($note['position'])) {
            $note['position'] = json_decode($note['position'], true);
        }
        if (isset($note['tags']) && is_string($note['tags'])) {
            $note['tags'] = json_decode($note['tags'], true);
        } else {
            $note['tags'] = [];
        }

        // Check which fields to update
        $changes = [];
        if (isset($data['content'])) {
            $changes[] = ['column_name' => 'content', 'new_value' => $data['content']];
        }
        if (isset($data['color'])) {
            $changes[] = ['column_name' => 'color', 'new_value' => $data['color']];
        }
        if (isset($data['position'])) {
            $changes[] = ['column_name' => 'position', 'new_value' => json_encode($data['position'])];
        }
        if (isset($data['tags'])) {
            $changes[] = ['column_name' => 'tags', 'new_value' => json_encode($data['tags'])];
        }

        if (empty($changes)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            return;
        }
        
        // Add updated_at and ID/user parameters
        $updateFields = [];
        $params = [];
        
        foreach ($changes as $change) {
            $columnName = $change['column_name'];
            $newValue = $change['new_value'];
            
            // Handle JSON fields
            if ($columnName === 'position' || $columnName === 'tags') {
                if (is_array($newValue)) {
                    $newValue = json_encode($newValue);
                }
            }
            
            $updateFields[] = "{$columnName} = ?";
            $params[] = $newValue;
        }
        
        $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
        
        // Add ID parameters at the end
        $params[] = $authorityId;
        $params[] = $noteId;
        $params[] = $userId;
        
        $sql = "UPDATE `kdd_notes` SET " . implode(", ", $updateFields) . " WHERE authority_id = ? AND id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);

        if ($success) {
            // Fetch updated note
            $sql = "SELECT * FROM `kdd_notes` WHERE authority_id = ? AND id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$authorityId, $noteId]);
            $updatedNote = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Decode JSON data
            if (isset($updatedNote['position']) && is_string($updatedNote['position'])) {
                $updatedNote['position'] = json_decode($updatedNote['position'], true);
            }
            if (isset($updatedNote['tags']) && is_string($updatedNote['tags'])) {
                $updatedNote['tags'] = json_decode($updatedNote['tags'], true);
            }
            
            http_response_code(200);
            echo json_encode($updatedNote);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update note']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in updateNote: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update note."]);
    }
}

/**
 * Deletes a note
 */
function deleteNote(PDO $pdo, int $userId, string $authority): void {
    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        
        $noteId = $_REQUEST['id'] ?? null;
        
        if (!$noteId) {
            http_response_code(400);
            echo json_encode(['error' => 'Note ID is required']);
            return;
        }
        
        // Verify the note belongs to the user and fetch for logging
        $sql = "SELECT * FROM `kdd_notes` WHERE authority_id = ? AND id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $noteId, $userId]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$note) {
            http_response_code(404);
            echo json_encode(['error' => 'Note not found or access denied']);
            return;
        }

        // Delete the note
        $sql = "DELETE FROM `kdd_notes` WHERE authority_id = ? AND id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $noteId, $userId]);
        
        if ($success) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Note deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete note']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteNote: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete note."]);
    }
}
?> 