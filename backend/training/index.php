<?php
/**
 * Backend Endpoint: training/index.php
 * Handles fetching training definitions and assigning trainings to employees.
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

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority)) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../utils/authority_helper.php';
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

// Map actions to required permissions (module.action format)
$permissions_map = [
    'getTrainings'         => ['module' => 'training', 'action' => 'read'],
    'getTrainingAssigns'   => ['module' => 'training', 'action' => 'read'],
    'saveTraining'         => ['module' => 'training', 'action' => 'write'],
    'getEmployees'         => ['module' => 'employee', 'action' => 'read'],
    'getRanks'             => ['module' => 'employee', 'action' => 'read'],
    'getCompanies'         => ['module' => 'company', 'action' => 'read'],
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
    switch ($action) {
        case 'getTrainings':         if ($request_method === 'GET') getTrainings($pdo, $authority); else MethodNotAllowed(); break;
        case 'getTrainingAssigns':   if ($request_method === 'GET') getTrainingAssigns($pdo, $authority); else MethodNotAllowed(); break;
        case 'saveTraining':         if ($request_method === 'POST') saveTraining($pdo, $userId, $authority); else MethodNotAllowed(); break;
        case 'deleteTrainingAssign': if ($request_method === 'DELETE' || $request_method === 'POST') deleteTrainingAssign($pdo, $userId, $authority); else MethodNotAllowed(); break;
        case 'getEmployees':         if ($request_method === 'GET') getEmployees($pdo, $authority); else MethodNotAllowed(); break;
        case 'getRanks':             if ($request_method === 'GET') getRanks($pdo, $authority); else MethodNotAllowed(); break;
        case 'getCompanies':         if ($request_method === 'GET') getCompanies($pdo, $authority); else MethodNotAllowed(); break;

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
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

/**
 * Gets input data from request, either from $_POST, $_GET, or from JSON request body
 * @return array Parsed input data
 */
function getInputData(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';
    
    // If content type contains 'json', parse the request body as JSON
    if (strpos($contentType, 'json') !== false) {
        $inputData = json_decode(file_get_contents('php://input'), true) ?? [];
        return is_array($inputData) ? $inputData : [];
    }
    
    // Otherwise, return POST or GET data depending on request method
    if ($requestMethod === 'GET') {
        return $_GET ?? [];
    } else {
        return $_POST ?? [];
    }
}

// --- Function Implementations (PDO Refactored) ---

/** Fetches all training definitions with category details */
function getTrainings(PDO $pdo, string $authority): void {
    // Get authority ID for proper querying
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    try {
        $sql = "SELECT et.id, et.catId, et.name, etc.name AS cat_name, etc.short AS cat_short, etc.sort_order as cat_sort_order 
                FROM `kdd_employee_training` et
                LEFT JOIN `kdd_employee_training_cat` etc ON et.catId = etc.id
                WHERE et.is_deleted = 0 AND etc.is_deleted = 0 
                ORDER BY etc.sort_order, et.sort_order";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $trainings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($trainings);
    } catch (\PDOException $e) {
        error_log("DB error in getTrainings ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve trainings."]);
    }
}

/** Fetches all employee training assignments with details */
function getTrainingAssigns(PDO $pdo, string $authority): void {
    // Get authority ID for proper querying
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    try {
        $sql = "SELECT etr.id as relation_id, etr.instructor, etr.date,
                       e.name AS employeename, e.id AS employeeid, e.servicenumber,
                       et.name AS training_name, et.id as training_id,
                       etc.short AS cat_short, etc.name AS cat_name, etc.id AS cat_id
                FROM kdd_employee_training_rel etr
                LEFT JOIN `kdd_employee` e ON etr.employee_id = e.id
                LEFT JOIN `kdd_employee_training` et ON etr.training_id = et.id
                LEFT JOIN `kdd_employee_training_cat` etc ON et.catId = etc.id
                WHERE et.is_deleted = 0 AND etc.is_deleted = 0
                ORDER BY e.name, etc.sort_order, et.sort_order, etr.date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($assignments);
    } catch (\PDOException $e) {
        error_log("DB error in getTrainingAssigns ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve training assignments."]);
    }
}

/** Saves training assignments for multiple employees and trainings */
function saveTraining(PDO $pdo, int $requestingUserId, string $authority): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        MethodNotAllowed(); 
        return; 
    }

    // Get authority ID for proper querying
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    $input = getJsonRequestData();
    
    // Validate required fields - Check both singular and plural field variants
    $employeeIds = $input['employee_id'] ?? $input['employeeIds'] ?? null;
    $trainingIds = $input['training_id'] ?? $input['trainingIds'] ?? null;
    $instructor = $input['instructor'] ?? null;
    $date = $input['date'] ?? null;
    
    if (empty($employeeIds) || empty($trainingIds) || empty($instructor) || empty($date)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: employee_id/employeeIds, training_id/trainingIds, instructor, date']);
        return;
    }

    // Convert to arrays if not already
    $employeeIds = is_array($employeeIds) ? $employeeIds : [$employeeIds]; 
    $trainingIds = is_array($trainingIds) ? $trainingIds : [$trainingIds];
    $instructor = trim($instructor);
    $date = trim($date);

    // Validation - Convert to integers and filter out invalid values
    $employeeIds = array_map('intval', array_filter($employeeIds, function ($id) { return $id > 0; }));
    $trainingIds = array_map('intval', array_filter($trainingIds, function ($id) { return $id > 0; }));

    if (empty($employeeIds) || empty($trainingIds)) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid employee or training IDs provided']);
        return;
    }

    // Date validation
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD']);
        return;
    }

    try {
        $pdo->beginTransaction();
        
        // Verify all employees exist
        $employeeCheckSql = "SELECT COUNT(*) FROM `kdd_employee` WHERE id IN (" . implode(',', array_fill(0, count($employeeIds), '?')) . ")";
        $employeeCheckStmt = $pdo->prepare($employeeCheckSql);
        $employeeCheckStmt->execute($employeeIds);
        $foundEmployees = $employeeCheckStmt->fetchColumn();
        
        if ($foundEmployees != count($employeeIds)) {
            http_response_code(400);
            echo json_encode(['error' => 'One or more employees not found']);
            $pdo->rollBack();
            return;
        }
        
        // Verify all trainings exist
        $trainingCheckSql = "SELECT COUNT(*) FROM `kdd_employee_training` WHERE id IN (" . implode(',', array_fill(0, count($trainingIds), '?')) . ")";
        $trainingCheckStmt = $pdo->prepare($trainingCheckSql);
        $trainingCheckStmt->execute($trainingIds);
        $foundTrainings = $trainingCheckStmt->fetchColumn();
        
        if ($foundTrainings != count($trainingIds)) {
            http_response_code(400);
            echo json_encode(['error' => 'One or more trainings not found']);
            $pdo->rollBack();
            return;
        }

        // Insert statement
        $insertSql = "INSERT INTO `kdd_employee_training_rel` (employee_id, training_id, instructor, date) VALUES (?, ?, ?, ?)";
        $insertStmt = $pdo->prepare($insertSql);
        
        $insertCount = 0;
        $errors = [];

        // Create relationship for each employee and training combination
        foreach ($employeeIds as $employeeId) {
            foreach ($trainingIds as $trainingId) {
                try {
                    $success = $insertStmt->execute([$employeeId, $trainingId, $instructor, $date]);
                    if ($success) {
                        $insertCount++;
                        $changes = [
                            ['column_name' => 'employee_id', 'old_value' => null, 'new_value' => $employeeId],
                            ['column_name' => 'training_id', 'old_value' => null, 'new_value' => $trainingId],
                            ['column_name' => 'instructor', 'old_value' => null, 'new_value' => $instructor],
                            ['column_name' => 'date', 'old_value' => null, 'new_value' => $date]
                        ];
                        
                        // Log the change
                        global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "kdd_employee_training_rel", $pdo->lastInsertId(), $requestingUserId, $changes);
                    }
                } catch (\PDOException $e) {
                    // Check for duplicate entry
                    if ($e->getCode() == '23000') {
                        $errors[] = "Training already assigned for employee ID $employeeId and training ID $trainingId";
                    } else {
                        $errors[] = "Error for employee ID $employeeId, training ID $trainingId: " . $e->getMessage();
                    }
                }
            }
        }

        if ($insertCount > 0) {
            $pdo->commit();
            $response = ['success' => true, 'message' => "$insertCount training assignments created"];
            if (!empty($errors)) {
                $response['warnings'] = $errors;
            }
            http_response_code(201); // Created
            echo json_encode($response);
        } else {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'No assignments created', 'details' => $errors]);
        }
    } catch (\PDOException $e) {
        $pdo->rollBack();
        error_log("DB error in saveTraining: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error occurred while saving training assignments"]);
    }
}

/** Fetch employees for training assignment */
function getEmployees(PDO $pdo, string $authority): void {
    // Get authority ID for proper querying
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    try {
        $sql = "SELECT e.id, e.name, e.servicenumber, r.name as rank_name 
                FROM kdd_employee e
                LEFT JOIN kdd_employee_rank r ON e.rank_id = r.id
                WHERE e.is_terminated = 0 
                ORDER BY e.name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($employees);
    } catch (\PDOException $e) {
        error_log("DB error in getEmployees: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Could not retrieve employees']);
    }
}

/** Fetch ranks for reference data */
function getRanks(PDO $pdo, string $authority): void {
    // Get authority ID for proper querying
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    try {
        $sql = "SELECT id, name, description, sort_order 
                FROM kdd_employee_rank
                WHERE is_deleted = 0
                ORDER BY sort_order ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $ranks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($ranks);
    } catch (\PDOException $e) {
        error_log("DB error in getRanks: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Could not retrieve ranks']);
    }
}

/** Fetch company data for reference */
function getCompanies(PDO $pdo, string $authority): void {
    // Get authority ID for proper querying
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    try {
        $sql = "SELECT id, name, sort_order
                FROM kdd_employee_company
                WHERE is_deleted = 0
                ORDER BY sort_order ASC, name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($companies);
    } catch (\PDOException $e) {
        error_log("DB error in getCompanies: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Could not retrieve companies']);
    }
}

/** Delete a training assignment */
function deleteTrainingAssign(PDO $pdo, int $requestingUserId, string $authority): void {
    // Get authority ID for proper querying
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    $input = getJsonRequestData();

    $employeeId = $input['employeeId'] ?? $input['employee_id'] ?? null;
    $trainingId = $input['trainingId'] ?? $input['training_id'] ?? null;

    if (empty($employeeId) || empty($trainingId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: employeeId, trainingId']);
        return;
    }

    $employeeId = intval($employeeId);
    $trainingId = intval($trainingId);

    if ($employeeId <= 0 || $trainingId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid employee or training ID']);
        return;
    }

    try {
        // First check if the assignment exists
        $checkSql = "SELECT id FROM `kdd_employee_training_rel` WHERE employee_id = ? AND training_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$employeeId, $trainingId]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            http_response_code(404);
            echo json_encode(['error' => 'Training assignment not found']);
            return;
        }

        // Delete the assignment
        $deleteSql = "DELETE FROM `kdd_employee_training_rel` WHERE employee_id = ? AND training_id = ?";
        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->execute([$employeeId, $trainingId]);

        // Log the deletion
        $changes = json_encode([
            'employee_id' => $employeeId,
            'training_id' => $trainingId
        ]);
        logDatabaseChange($authorityId, $pdo, 'DELETE', "kdd_employee_training_rel", $existing['id'], $requestingUserId, $changes);

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Training assignment deleted']);
    } catch (\PDOException $e) {
        error_log("DB error in deleteTrainingAssign: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred while deleting training assignment']);
    }
}

?>