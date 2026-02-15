<?php
/**
 * Backend Endpoint: employee/index.php
 * Handles CRUD operations for Employees and related data (Licenses, Promotions, etc.).
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
require_once __DIR__ . '/../logging/logging.php';
require_once __DIR__ . '/../calendar/calendar.php'; // For editEmployee -> termination calendar entry

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

global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority) || $authorityId === null) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'employee')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to employee features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    'getEmployee'       => ['module' => 'employee', 'action' => 'read'],
    'getCompanies'      => ['module' => 'employee', 'action' => 'read'],
    'getDepartments'    => ['module' => 'employee', 'action' => 'read'],
    'getRanks'          => ['module' => 'employee', 'action' => 'read'],
    'getLicenses'       => ['module' => 'employee', 'action' => 'read'],
    'createEmployee'    => ['module' => 'employee', 'action' => 'write'],
    'editEmployee'      => ['module' => 'employee', 'action' => 'write'],
    'updateNotes'       => ['module' => 'employee', 'action' => 'write'],
    'addRank'           => ['module' => 'employee', 'action' => 'write'],
    'deleteRank'        => ['module' => 'employee', 'action' => 'delete'],
    'updateRankName'    => ['module' => 'employee', 'action' => 'write'],
    'saveCategorySorting' => ['module' => 'employee', 'action' => 'write'],
    'getJobTypes'       => ['module' => 'employee', 'action' => 'read'],
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
    // Add PUT/DELETE checks if preferred

    switch ($action) {
        // GET Actions
        case 'getEmployee':     if ($request_method === 'GET') getEmployee($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getCompanies':    if ($request_method === 'GET') getCompanies($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getDepartments':  if ($request_method === 'GET') getDepartments($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getRanks':        if ($request_method === 'GET') getRanks($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getLicenses':     if ($request_method === 'GET') getLicenses($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getJobTypes':     if ($request_method === 'GET') getJobTypes($pdo, $authority, $authorityId); else MethodNotAllowed(); break; // Added back
        case 'getStats':        if ($request_method === 'GET') getStats($pdo, $authority, $authorityId); else MethodNotAllowed(); break; // Dashboard widget
        case 'getUpcomingVacations': if ($request_method === 'GET') getUpcomingVacations($pdo, $authority, $authorityId); else MethodNotAllowed(); break; // Dashboard widget

        // POST Actions (or PUT)
        case 'createEmployee': if ($is_post_request) createEmployee($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'editEmployee':   if ($is_post_request) editEmployee($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'updateNotes':    if ($is_post_request) updateNotes($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'addRank':        if ($is_post_request) addRank($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'deleteRank':     if ($is_post_request) deleteRank($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Added back
        case 'updateRankName': if ($is_post_request) updateRankName($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Added back
        case 'saveCategorySorting': if ($is_post_request) saveCategorySorting($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Added back

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

/**
 * Fetches employee data structured by rank, including related info.
 * Optimized to reduce redundant data fetching compared to original version.
 */
function getEmployee(PDO $pdo, string $authority, int $authorityId): void {
    $output = [];
    $employeesById = [];
    $employeeIds = [];

    try {
        // 1. Fetch Ranks (to structure the output)
        $sqlRanks = "SELECT r.id, r.name, r.description, r.rankImage, r.sort_order,
                           r.department, r.jobrole_id, j.name AS jobrole_name
                     FROM kdd_employee_rank r
                     LEFT JOIN `kdd_jobroles` j ON j.id = r.jobrole_id AND j.authority_id = ?
                     WHERE r.authority_id = ? AND r.is_deleted = 0
                     ORDER BY r.sort_order ASC";
        $stmtRanks = $pdo->prepare($sqlRanks);
        $stmtRanks->execute([$authorityId, $authorityId]);
        $ranks = $stmtRanks->fetchAll(PDO::FETCH_ASSOC);

        if (empty($ranks)) {
            http_response_code(200); echo json_encode([]); return; 
        }

        // Initialize output structure based on ranks
        foreach ($ranks as $rank) {
            $output[$rank['id']] = $rank;
            $output[$rank['id']]['employees'] = [];
        }

        // 2. Fetch Employees
        $sqlEmployees = "SELECT e.id, e.name, e.phonenumber, e.is_terminated, e.servicenumber,
                                e.bankaccount, e.personalid, e.entrydate, e.notes, e.leavedate,
                                e.mail, e.sidejob, e.rank_id, e.birthdate, r.name as rank_name,
                                (SELECT MAX(p.generated_date) FROM `kdd_employee_promotion` AS p 
                                WHERE p.employee = e.id AND p.authority_id = ?) AS last_promotion
                         FROM kdd_employee e
                         LEFT JOIN `kdd_employee_rank` r ON e.rank_id = r.id AND r.authority_id = ?
                         WHERE e.authority_id = ?";
        $stmtEmployees = $pdo->prepare($sqlEmployees);
        $stmtEmployees->execute([$authorityId, $authorityId, $authorityId]);
        $employeeRows = $stmtEmployees->fetchAll(PDO::FETCH_ASSOC);

        // Populate employeesById and collect IDs
        foreach ($employeeRows as $emp) {
            $empId = $emp['id'];
            $rankId = $emp['rank_id'];
            $employeesById[$empId] = $emp; // Store full employee data once
            $employeeIds[] = $empId;
            // Initialize related data arrays
            $employeesById[$empId]['licenses'] = [];
            $employeesById[$empId]['promotions'] = [];
            $employeesById[$empId]['departments'] = [];
            $employeesById[$empId]['companies'] = [];
            $employeesById[$empId]['vacations'] = [];
        }

        // Proceed only if employees were found
        if (!empty($employeeIds)) {
            $placeholders = rtrim(str_repeat('?,', count($employeeIds)), ',');

            // 3. Fetch Licenses for these employees
            $sqlLicenses = "SELECT r.employee_id, l.id, l.type, l.name 
                            FROM `kdd_employee_license_rel` r 
                            JOIN `kdd_employee_license` l ON l.id = r.license_id AND l.authority_id = ?
                            WHERE r.employee_id IN ({$placeholders})";
            $licenseParams = array_merge([$authorityId], $employeeIds);
            $stmtLicenses = $pdo->prepare($sqlLicenses);
            $stmtLicenses->execute($licenseParams);
            while ($row = $stmtLicenses->fetch(PDO::FETCH_ASSOC)) {
                if (isset($employeesById[$row['employee_id']])) {
                     $employeesById[$row['employee_id']]['licenses'][$row['id']] = $row; // Use ID as key to deduplicate
                }
            }

             // 4. Fetch Promotions for these employees
             $sqlPromotions = "SELECT p.employee, p.id, p.old_rank_name, p.new_rank_name, p.generated_date as promotion_date 
                               FROM `kdd_employee_promotion` p 
                               WHERE p.authority_id = ? AND p.employee IN ({$placeholders}) 
                               ORDER BY p.generated_date DESC";
             $promotionParams = array_merge([$authorityId], $employeeIds);
             $stmtPromotions = $pdo->prepare($sqlPromotions);
             $stmtPromotions->execute($promotionParams);
             while ($row = $stmtPromotions->fetch(PDO::FETCH_ASSOC)) {
                 if (isset($employeesById[$row['employee']])) {
                     $employeesById[$row['employee']]['promotions'][$row['id']] = $row;
                 }
             }

             // 5. Fetch Departments for these employees
             $sqlDepartments = "SELECT r.employee_id, d.id, d.name 
                                FROM `kdd_employee_department_rel` r 
                                JOIN `kdd_employee_department` d ON d.id = r.department_id AND d.authority_id = ?
                                WHERE r.employee_id IN ({$placeholders})";
             $departmentParams = array_merge([$authorityId], $employeeIds);
             $stmtDepartments = $pdo->prepare($sqlDepartments);
             $stmtDepartments->execute($departmentParams);
             while ($row = $stmtDepartments->fetch(PDO::FETCH_ASSOC)) {
                 if (isset($employeesById[$row['employee_id']])) {
                     $employeesById[$row['employee_id']]['departments'][$row['id']] = $row;
                 }
             }

             // 6. Fetch Companies for these employees
             $sqlCompanies = "SELECT r.employee_id, c.id, c.name 
                              FROM `kdd_employee_company_rel` r 
                              JOIN `kdd_employee_company` c ON c.id = r.company_id AND c.authority_id = ?
                              WHERE r.employee_id IN ({$placeholders})";
             $companyParams = array_merge([$authorityId], $employeeIds);
             $stmtCompanies = $pdo->prepare($sqlCompanies);
             $stmtCompanies->execute($companyParams);
             while ($row = $stmtCompanies->fetch(PDO::FETCH_ASSOC)) {
                 if (isset($employeesById[$row['employee_id']])) {
                     $employeesById[$row['employee_id']]['companies'][$row['id']] = $row;
                 }
             }

             // 7. Fetch non-deleted Vacations for these employees
             $sqlVacations = "SELECT v.employee, v.id, v.reason, v.start, v.end, v.reported, v.other 
                              FROM `kdd_employee_vacation` v 
                              WHERE v.authority_id = ? AND v.employee IN ({$placeholders}) AND v.is_deleted = 0";
             $vacationParams = array_merge([$authorityId], $employeeIds);
             $stmtVacations = $pdo->prepare($sqlVacations);
             $stmtVacations->execute($vacationParams);
             while ($row = $stmtVacations->fetch(PDO::FETCH_ASSOC)) {
                  if (isset($employeesById[$row['employee']])) {
                     $employeesById[$row['employee']]['vacations'][$row['id']] = $row;
                 }
             }

        } // End if !empty($employeeIds)

        // 8. Assign employees (with all their data) to the correct rank in the output structure
        foreach ($employeesById as $employee) {
            $rankId = $employee['rank_id'];
            if (isset($output[$rankId])) {
                // Convert nested associative arrays (used for deduplication) to indexed arrays
                $employee['licenses'] = array_values($employee['licenses']);
                $employee['promotions'] = array_values($employee['promotions']);
                $employee['departments'] = array_values($employee['departments']);
                $employee['companies'] = array_values($employee['companies']);
                $employee['vacations'] = array_values($employee['vacations']);
                $output[$rankId]['employees'][] = $employee;
            } else {
                 error_log("Employee {$employee['id']} has rank {$rankId} which was not found in ranks query.");
            }
        }

        http_response_code(200);
        echo json_encode(array_values($output)); // Return final structure

    } catch (\PDOException $e) {
        error_log("DB error in getEmployee ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve employee data."]);
    }
}

function getCompanies(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $stmt = $pdo->prepare("SELECT id, name, sort_order, is_deleted 
                          FROM kdd_employee_company 
                          WHERE authority_id = ? AND is_deleted = 0
                          ORDER BY sort_order ASC");
        $stmt->execute([$authorityId]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($result);
    } catch (PDOException $e) {
        error_log("DB Error in getCompanies: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error while fetching companies."]);
    }
}

function getJobTypes(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $stmt = $pdo->prepare("SELECT id, name FROM kdd_jobroles WHERE authority_id = ?");
        $stmt->execute([$authorityId]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($result);
    } catch (PDOException $e) {
        error_log("DB Error in getJobTypes: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error while fetching job types."]);
    }
}

function getDepartments(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $stmt = $pdo->prepare("SELECT id, name, sort_order, is_deleted 
                          FROM kdd_employee_department 
                          WHERE authority_id = ? AND is_deleted = 0
                          ORDER BY sort_order ASC");
        $stmt->execute([$authorityId]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($result);
    } catch (PDOException $e) {
        error_log("DB Error in getDepartments: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error while fetching departments."]);
    }
}

function getRanks(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $stmt = $pdo->prepare("SELECT r.id, r.name, r.description, r.icon, r.rankImage, 
                             r.sort_order, r.department, r.jobrole_id, r.is_deleted,
                             j.name AS jobrole_name
                      FROM kdd_employee_rank r
                      LEFT JOIN kdd_jobroles j ON j.id = r.jobrole_id AND j.authority_id = ?
                      WHERE r.authority_id = ? AND r.is_deleted = 0
                      ORDER BY r.sort_order ASC");
        $stmt->execute([$authorityId, $authorityId]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($result);
    } catch (PDOException $e) {
        error_log("DB Error in getRanks: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error while fetching ranks."]);
    }
}

function getLicenses(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $stmt = $pdo->prepare("SELECT id, name, type, sort_order 
                          FROM kdd_employee_license 
                          WHERE authority_id = ?
                          ORDER BY sort_order ASC");
        $stmt->execute([$authorityId]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($result);
    } catch (PDOException $e) {
        error_log("DB Error in getLicenses: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error while fetching licenses."]);
    }
}

function createEmployee(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();

    // Extract and validate required fields
    $name = filter_var($data['name'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $servicenumber = filter_var($data['servicenumber'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $entrydate = !empty($data['entrydate']) ? $data['entrydate'] : null;
    $rankId = filter_var($data['rankId'] ?? 0, FILTER_VALIDATE_INT);
    
    // Validate required fields
    if (empty($name) || empty($servicenumber) || empty($entrydate) || $rankId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid required fields. Name, service number, entry date, and rank are required.']);
        return;
    }
    
    // Optional fields
    $phonenumber = filter_var($data['phonenumber'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $sidejob = filter_var($data['sidejob'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $personalid = filter_var($data['personalid'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $mail = filter_var($data['mail'] ?? '', FILTER_SANITIZE_EMAIL);
    $bankaccount = filter_var($data['bankaccount'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $birthdate = !empty($data['birthdate']) ? $data['birthdate'] : null;
    $notes = filter_var($data['notes'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    
    // Additional relationships
    $licenseIds = $data['license'] ?? [];
    $departmentIds = $data['department'] ?? [];
    $companyIds = $data['company'] ?? [];
    
    try {
        $pdo->beginTransaction();
        
        // 1. Insert employee record
        $sql = "INSERT INTO kdd_employee 
                (name, servicenumber, entrydate, rank_id, phonenumber, sidejob, 
                 personalid, mail, bankaccount, birthdate, notes, authority_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $name, $servicenumber, $entrydate, $rankId, $phonenumber, $sidejob,
            $personalid, $mail, $bankaccount, $birthdate, $notes, $authorityId
        ]);
        
        $employeeId = $pdo->lastInsertId();
        
        // 2. Link licenses if any
        if (!empty($licenseIds)) {
            linkRelations($pdo, $authority, 'license', (int)$employeeId, $licenseIds, $authorityId);
        }
        
        // 3. Link departments if any
        if (!empty($departmentIds)) {
            linkRelations($pdo, $authority, 'department', (int)$employeeId, $departmentIds, $authorityId);
        }
        
        // 4. Link companies if any
        if (!empty($companyIds)) {
            linkRelations($pdo, $authority, 'company', (int)$employeeId, $companyIds, $authorityId);
        }
        
        $pdo->commit();
        
        // Log the creation
        $changes = [
            ['column_name' => 'name', 'old_value' => null, 'new_value' => $name],
            ['column_name' => 'servicenumber', 'old_value' => null, 'new_value' => $servicenumber],
            ['column_name' => 'rank_id', 'old_value' => null, 'new_value' => $rankId]
        ];
        global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "employee", $employeeId, $requestingUserId, $changes);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Employee created successfully.',
            'id' => $employeeId
        ]);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("DB Error in createEmployee: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error while creating employee."]);
    }
}

function editEmployee(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    // Assume data comes from POST
    $data = getInputData();
    $employee_id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$employee_id) {
        http_response_code(400); echo json_encode(['error' => 'Invalid or missing employee ID.']); return;
    }

    // Validate other required fields if necessary
    $name = $data['name'] ?? null;
    $servicenumber = $data['servicenumber'] ?? null;
    $rankId = filter_var($data['rankId'] ?? null, FILTER_VALIDATE_INT);
    if (empty($name) || empty($servicenumber) || !$rankId) {
        http_response_code(400); echo json_encode(['error' => 'Name, service number, and rank are required.']); return;
    }

    // Extract other fields (similar to createEmployee)
    $personalid = $data['personalid'] ?? '';
    $phonenumber = $data['phonenumber'] ?? '';
    $birthdate = !empty($data['birthdate']) ? date('Y-m-d', strtotime($data['birthdate'])) : null;
    $mail = $data['mail'] ?? '';
    $entrydate = !empty($data['entrydate']) ? date('Y-m-d', strtotime($data['entrydate'])) : null;
    $sidejob = $data['sidejob'] ?? '';
    $bankaccount = $data['bankaccount'] ?? '';
    $isTerminated = filter_var($data['is_terminated'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $isTerminated = ($isTerminated === null) ? 0 : (int)$isTerminated;
    $leavedate = ($isTerminated && !empty($data['leavedate'])) ? date('Y-m-d', strtotime($data['leavedate'])) : null;
    $notes = $data['notes'] ?? '';

    // Relation IDs (expect arrays, default to empty array if not set)
    $company_ids = isset($data['company']) && is_array($data['company']) ? array_map('intval', $data['company']) : [];
    $department_ids = isset($data['department']) && is_array($data['department']) ? array_map('intval', $data['department']) : [];
    $license_ids = isset($data['license']) && is_array($data['license']) ? array_map('intval', $data['license']) : [];

    try {
        $pdo->beginTransaction();

        // 1. Fetch old employee data (for logging and rank check)
        $oldEntry = getEntryById($pdo, $employee_id, "kdd_employee");
        if (!$oldEntry) { throw new \Exception("Employee with ID $employee_id not found."); }
        $oldRankId = (int)$oldEntry['rank_id'];

        // 2. Check if rank changed and record promotion if necessary
        if ($rankId !== $oldRankId) {
            // Call helper function to insert promotion record
            if (!updateRank($pdo, $requestingUserId, $authority, $employee_id, $oldRankId, $rankId, $authorityId)) {
                throw new \Exception("Failed to record rank promotion.");
            }
        }

        // 3. Update main employee record
        $sqlUpdate = "UPDATE `kdd_employee` SET name = ?, personalid = ?, phonenumber = ?, mail = ?, servicenumber = ?,
                          entrydate = ?, sidejob = ?, rank_id = ?, bankaccount = ?,
                          leavedate = ?, is_terminated = ?, birthdate = ?, notes = ?, authority_id = ?
                      WHERE id = ?";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $success = $stmtUpdate->execute([
            $name, $personalid, $phonenumber, $mail, $servicenumber, $entrydate, $sidejob,
            $rankId, $bankaccount, $leavedate, $isTerminated, $birthdate, $notes, $authorityId,
            $employee_id
        ]);
        if (!$success) { throw new \Exception("Failed to update employee main data."); }

        // 4. Update Relations (Remove old, Add new)
        if (!removeLinks($pdo, $authority, $employee_id, "kdd_employee_company_rel", $authorityId)) throw new \Exception("Failed to remove old company links.");
        if (!removeLinks($pdo, $authority, $employee_id, "kdd_employee_department_rel", $authorityId)) throw new \Exception("Failed to remove old department links.");
        if (!removeLinks($pdo, $authority, $employee_id, "kdd_employee_license_rel", $authorityId)) throw new \Exception("Failed to remove old license links.");

        // Add new links using helper function
        if (!linkRelations($pdo, $authority, 'company', $employee_id, $company_ids, $authorityId)) throw new \Exception("Failed to link new companies.");
        if (!linkRelations($pdo, $authority, 'department', $employee_id, $department_ids, $authorityId)) throw new \Exception("Failed to link new departments.");
        if (!linkRelations($pdo, $authority, 'license', $employee_id, $license_ids, $authorityId)) throw new \Exception("Failed to link new licenses.");

        // 5. Update Calendar for Termination Status Change
        // !! IMPORTANT: Assumes functions in calendar.php are adapted to accept PDO !!
        $calendarTableName = "kdd_employee";
        if ($isTerminated && $leavedate) {
            $eventTitle = 'Kündigung ' . $name;
            $eventContent = '';
            $eventContentFull = "DNr: " . ($servicenumber ?: 'N/A') . "\nPNr: " . ($personalid ?: 'N/A') . "\nTel: " . ($phonenumber ?: 'N/A');
            if (calendarEntryExist($pdo, $authority, $calendarTableName, $employee_id)) {
                editEntryToCalendar($pdo, $requestingUserId, $authority, $eventTitle, $eventContent, $leavedate, '12:00:00', $eventContentFull, $calendarTableName, $employee_id);
            } else {
                addEntryToCalendar($pdo, $requestingUserId, $authority, $eventTitle, $eventContent, $leavedate, '12:00:00', $eventContentFull, $calendarTableName, $employee_id);
            }
        } elseif (!$isTerminated) { // If NOT terminated, ensure no termination entry exists
            if (calendarEntryExist($pdo, $authority, $calendarTableName, $employee_id)) {
                deleteEntryFromCalendar($pdo, $requestingUserId, $authority, $calendarTableName, $employee_id);
            }
        }

        $pdo->commit();

        // Log changes
        $updatedEmployee = getEntryById($pdo, $employee_id, "kdd_employee");
        $changes = [];
        
        // Track basic changes
        if ($oldEntry && $updatedEmployee) {
            $changes = [
                ['column_name' => 'name', 'old_value' => $oldEntry['name'], 'new_value' => $updatedEmployee['name']],
                ['column_name' => 'servicenumber', 'old_value' => $oldEntry['servicenumber'], 'new_value' => $updatedEmployee['servicenumber']],
                ['column_name' => 'rank_id', 'old_value' => $oldEntry['rank_id'], 'new_value' => $updatedEmployee['rank_id']]
            ];
        }
        
        // Log the changes
        global $decoded_jwt;
        $authorityId = $decoded_jwt->authority_id ?? 0;
        logDatabaseChange($authorityId, $pdo, 'UPDATE', "employee", $employee_id, $requestingUserId, $changes);

        http_response_code(200); 
        echo json_encode(["success" => true, "message" => "Employee updated successfully."]);

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("Error in editEmployee (ID: $employee_id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update employee: " . $e->getMessage()]);
    }
}

// Helper: removeLinks (PDO)
function removeLinks(PDO $pdo, string $authority, int $employeeId, string $tableName, int $authorityId, int $userId = 0): bool
{
    try {
        // Get old links for logging
        $getOldSql = "SELECT * FROM `$tableName` WHERE employee_id = ?";
        $getOldStmt = $pdo->prepare($getOldSql);
        $getOldStmt->execute([$employeeId]);
        $oldLinks = $getOldStmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "DELETE FROM `$tableName` WHERE employee_id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$employeeId]);

        // Log hard delete of each link
        foreach ($oldLinks as $oldLink) {
            $changes = [
                ['column_name' => 'PERMANENT_DELETE', 'old_value' => json_encode($oldLink), 'new_value' => null]
            ];
            logDatabaseChange($authorityId, $pdo, 'DELETE', $tableName, $oldLink['id'], $userId, $changes);
        }

        return $success;
    } catch (PDOException $e) {
        error_log("DB Error in removeLinks: " . $e->getMessage());
        return false;
    }
}

// Helper: linkRelations (PDO) - Replaces linkCompany, linkDepartment, linkLicense
function linkRelations(PDO $pdo, string $authority, string $relationType, int $employeeId, array $relIds, int $authorityId, int $userId = 0): bool
{
    if (empty($relIds)) {
        return true; // Nothing to link, so technically successful
    }

    // Map relation type to table name
    $tableMap = [
        'company' => 'kdd_employee_company_rel',
        'department' => 'kdd_employee_department_rel',
        'license' => 'kdd_employee_license_rel'
    ];

    $idColumnMap = [
        'company' => 'company_id',
        'department' => 'department_id',
        'license' => 'license_id'
    ];

    if (!isset($tableMap[$relationType]) || !isset($idColumnMap[$relationType])) {
        error_log("Invalid relation type: $relationType");
        return false;
    }

    $tableName = $tableMap[$relationType];
    $idColumn = $idColumnMap[$relationType];

    try {
        // First, remove any existing links for this employee and relation type
        if (!removeLinks($pdo, $authority, $employeeId, $tableName, $authorityId, $userId)) {
            throw new PDOException("Failed to remove existing links");
        }

        // Now add the new links
        $stmt = $pdo->prepare("INSERT INTO `$tableName` (employee_id, $idColumn) VALUES (?, ?)");

        foreach ($relIds as $relId) {
            if (!$stmt->execute([$employeeId, $relId])) {
                throw new PDOException("Failed to insert relation: $relationType ID $relId");
            }

            // Log link insertion
            $linkId = $pdo->lastInsertId();
            $changes = [
                ['column_name' => 'employee_id', 'old_value' => null, 'new_value' => $employeeId],
                ['column_name' => $idColumn, 'old_value' => null, 'new_value' => $relId]
            ];
            logDatabaseChange($authorityId, $pdo, 'INSERT', $tableName, $linkId, $userId, $changes);
        }

        return true;
    } catch (PDOException $e) {
        error_log("DB Error in linkRelations: " . $e->getMessage());
        return false;
    }
}

// Helper: updateRank (PDO) - Inserts promotion record
function updateRank(PDO $pdo, int $requestingUserId, string $authority, int $employee_id, int $old_rank_id, int $new_rank_id, int $authorityId): bool {
    try {
        // First update employee's rank
        $stmt = $pdo->prepare("UPDATE kdd_employee SET rank_id = ? WHERE id = ? AND authority_id = ?");
        $stmt->execute([$new_rank_id, $employee_id, $authorityId]);
        
        // Get employee name
        $stmt = $pdo->prepare("SELECT name FROM kdd_employee WHERE id = ? AND authority_id = ?");
        $stmt->execute([$employee_id, $authorityId]);
        $employeeName = $stmt->fetchColumn();
        
        // Get old and new rank names
        $stmt = $pdo->prepare("SELECT name FROM kdd_employee_rank WHERE id = ? AND authority_id = ?");
        $stmt->execute([$old_rank_id, $authorityId]);
        $oldRankName = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT name FROM kdd_employee_rank WHERE id = ? AND authority_id = ?");
        $stmt->execute([$new_rank_id, $authorityId]);
        $newRankName = $stmt->fetchColumn();
        
        // Insert into promotion history
        $stmt = $pdo->prepare("INSERT INTO kdd_employee_promotion 
                              (old_rank, old_rank_name, new_rank, new_rank_name, employee, employee_name, generated_date, authority_id) 
                              VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
        $stmt->execute([$old_rank_id, $oldRankName, $new_rank_id, $newRankName, $employee_id, $employeeName, $authorityId]);
        
        return true;
    } catch (PDOException $e) {
        error_log("DB Error in updateRank: " . $e->getMessage());
        return false;
    }
}

// Protected: updateNotes (PDO)
function updateNotes(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getInputData();
    $employee_id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $notes = $data['notes'] ?? ''; // Allow empty notes

    if ($employee_id === false || $employee_id <= 0) {
        http_response_code(400); echo json_encode(['error' => 'Invalid or missing employee ID.']); return;
    }

    try {
        $oldEntry = getEntryById($pdo, $employee_id, "kdd_employee"); // For logging

        $sql = "UPDATE `kdd_employee` SET notes = ? WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$notes, $employee_id, $authorityId]);

        if ($success && $stmt->rowCount() > 0) {
             http_response_code(200); echo json_encode(["success" => true, "message" => "Employee notes updated successfully."]);
             // Log change
             $oldNotes = $oldEntry && isset($oldEntry['notes']) ? $oldEntry['notes'] : '';
             $changes = [['column_name' => 'notes', 'old_value' => $oldNotes, 'new_value' => $notes]];
             global $decoded_jwt;
             $authorityId = $decoded_jwt->authority_id ?? 0;
             logDatabaseChange($authorityId, $pdo, 'UPDATE', "employee", $employee_id, $requestingUserId, $changes);
        } elseif ($success) { http_response_code(200); echo json_encode(["error" => "No changes made to notes."]);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to execute notes update.']); }
    } catch (\PDOException $e) {
        error_log("DB error in updateNotes (EmpID: $employee_id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update employee notes."]);
    }
}

// --- Function Implementation for addRank (PDO) ---

function addRank(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    $data = getInputData();
    $name = filter_var($data['name'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $description = filter_var($data['description'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $icon = filter_var($data['icon'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $rankImage = filter_var($data['rankImage'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $department = filter_var($data['department'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    $jobroleId = filter_var($data['jobrole_id'] ?? null, FILTER_VALIDATE_INT) ?: null;

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Rank name is required.']);
        return;
    }

    try {
        // Get the maximum sort_order value and increment by 10
        $stmt = $pdo->prepare("SELECT MAX(sort_order) as max_order FROM kdd_employee_rank WHERE authority_id = ?");
        $stmt->execute([$authorityId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $sort_order = (int)($result['max_order'] ?? 0) + 10;

        $stmt = $pdo->prepare("INSERT INTO kdd_employee_rank 
                              (name, description, icon, rankImage, sort_order, department, jobrole_id, authority_id) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $icon, $rankImage, $sort_order, $department, $jobroleId, $authorityId]);
        
        $newId = $pdo->lastInsertId();
        
        // Log the insertion
        $changes = [
            ['column_name' => 'name', 'old_value' => null, 'new_value' => $name],
            ['column_name' => 'description', 'old_value' => null, 'new_value' => $description]
        ];
        global $decoded_jwt;
        $authorityId = $decoded_jwt->authority_id ?? 0;
        logDatabaseChange($authorityId, $pdo, 'INSERT', "employee_rank", $newId, $userId, $changes);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Rank added successfully.',
            'id' => $newId
        ]);
    } catch (PDOException $e) {
        error_log("DB Error in addRank: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error while adding rank."]);
    }
}

// --- Function Implementation for deleteRank (PDO) ---
function deleteRank(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $data = getInputData();
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid rank ID']);
            return;
        }

        // Check if there are employees with this rank
        $checkQuery = "SELECT COUNT(*) as employee_count FROM `kdd_employee` WHERE authority_id = ? AND rank_id = ?";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute([$authorityId, $id]);
        $employeeCount = $checkStmt->fetchColumn();

        if ($employeeCount > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Rang kann nicht gelöscht werden, da er noch von Mitarbeitern verwendet wird.']);
            return;
        }

        // Soft delete the rank by setting is_deleted to 1
        $query = "UPDATE `kdd_employee_rank` SET is_deleted = 1 WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($query);
        $success = $stmt->execute([$authorityId, $id]);
        
        if (!$success) {
            throw new \Exception("Failed to delete rank");
        }
        
        // Log the change
        $changes = [
            ['column_name' => 'deleted', 'old_value' => 0, 'new_value' => 1]
        ];
        global $decoded_jwt;
        $authorityId = $decoded_jwt->authority_id ?? 0;
        logDatabaseChange($authorityId, $pdo, 'DELETE', "kdd_employee_rank", $id, $userId, $changes);

        // Return success response
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Rang erfolgreich gelöscht']);
        
    } catch (\PDOException $e) {
        error_log("Database error in deleteRank ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete rank: " . $e->getMessage()]);
    } catch (\Exception $e) {
        error_log("Error in deleteRank ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete rank: " . $e->getMessage()]);
    }
}

// --- Function Implementation for updateRankName (PDO) ---
function updateRankName(PDO $pdo, int $userId, string $authority, int $authorityId): void 
{
    $data = getInputData();
    $rankId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $newName = $data['name'] ?? '';
    
    if (!$rankId || empty($newName)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid rank ID or name']);
        return;
    }
    
    try {
        // Get the old rank data for logging changes
        $oldRank = getEntryById($pdo, $rankId, "kdd_employee_rank");
        if (!$oldRank) {
            http_response_code(404);
            echo json_encode(['error' => 'Rank not found']);
            return;
        }
        
        // Update the rank name
        $sql = "UPDATE `kdd_employee_rank` SET name = ? WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$newName, $authorityId, $rankId]);
        
        if (!$success) {
            throw new \Exception("Failed to update rank name");
        }
        
        // Get the updated rank data for logging changes
        $updatedRank = getEntryById($pdo, $rankId, "kdd_employee_rank");
        $changes = getEntryChanges($oldRank, $updatedRank);
        
        if (!empty($changes)) {
            global $decoded_jwt;
            $authorityId = $decoded_jwt->authority_id ?? 0;
            logDatabaseChange($authorityId, $pdo, 'UPDATE', "kdd_employee_rank", $rankId, $userId, $changes);
        }
        
        // Return success response
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Rank name updated successfully"]);
        
    } catch (\PDOException $e) {
        error_log("Database error in updateRankName ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update rank name: " . $e->getMessage()]);
    } catch (\Exception $e) {
        error_log("Error in updateRankName ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update rank name: " . $e->getMessage()]);
    }
}

// --- Function Implementation for saveCategorySorting (PDO) ---
function saveCategorySorting(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    $data = getInputData();
    
    if (!isset($data['ranks']) || !is_array($data['ranks'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid rank data']);
        return;
    }
    
    try {
        $pdo->beginTransaction();

        foreach ($data['ranks'] as $rank) {
            $id = filter_var($rank['id'] ?? null, FILTER_VALIDATE_INT);
            $sortOrder = filter_var($rank['sort_order'] ?? null, FILTER_VALIDATE_INT);

            if (!$id || $sortOrder === null) {
                continue; // Skip invalid entries
            }

            // Get the old rank data for logging changes
            $oldRank = getEntryById($pdo, $id, "kdd_employee_rank");
            if (!$oldRank) {
                continue; // Skip if rank not found
            }

            $sql = "UPDATE `kdd_employee_rank` SET sort_order = ? WHERE authority_id = ? AND id = ?";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$sortOrder, $authorityId, $id]);

            if (!$success) {
                throw new \Exception("Failed to update sort order for rank ID: $id");
            }

            // Log the sort order change
            if ($oldRank['sort_order'] != $sortOrder) {
                $changes = [
                    ['column_name' => 'sort_order', 'old_value' => $oldRank['sort_order'], 'new_value' => $sortOrder]
                ];
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "kdd_employee_rank", $id, $userId, $changes);
            }
        }

        $pdo->commit();
        
        // Return success response
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Rank sorting updated successfully"]);
        
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Database error in saveCategorySorting ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not save rank sorting: " . $e->getMessage()]);
    } catch (\Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in saveCategorySorting ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not save rank sorting: " . $e->getMessage()]);
    }
}

/**
 * Get employee statistics for dashboard widget
 * Returns: total employees, active, on vacation
 */
function getStats(PDO $pdo, string $authority, int $authorityId): void {
    try {
        // Total employees
        $sqlTotal = "SELECT COUNT(*) FROM kdd_employee WHERE authority_id = ?";
        $stmtTotal = $pdo->prepare($sqlTotal);
        $stmtTotal->execute([$authorityId]);
        $total = (int)$stmtTotal->fetchColumn();

        // Active employees (not terminated)
        $sqlActive = "SELECT COUNT(*) FROM kdd_employee
                      WHERE authority_id = ? AND is_terminated = 0";
        $stmtActive = $pdo->prepare($sqlActive);
        $stmtActive->execute([$authorityId]);
        $active = (int)$stmtActive->fetchColumn();

        // Employees on vacation (from vacations table)
        $today = date('Y-m-d');
        $sqlVacation = "SELECT COUNT(DISTINCT v.employee_id)
                        FROM kdd_vacations v
                        INNER JOIN kdd_employee e ON v.employee_id = e.id
                        WHERE e.authority_id = ?
                        AND e.is_terminated = 0
                        AND DATE(v.start) <= ?
                        AND DATE(v.end) >= ?";
        $stmtVacation = $pdo->prepare($sqlVacation);
        $stmtVacation->execute([$authorityId, $today, $today]);
        $onVacation = (int)$stmtVacation->fetchColumn();

        http_response_code(200);
        echo json_encode([
            'total' => $total,
            'active' => $active,
            'onVacation' => $onVacation
        ]);
    } catch (\PDOException $e) {
        error_log("Database error in getStats (authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve employee statistics."]);
    }
}

/**
 * Get upcoming vacations for dashboard widget
 * Returns: List of employee vacations in the next 30 days
 */
function getUpcomingVacations(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $today = date('Y-m-d');
        $futureDate = date('Y-m-d', strtotime('+30 days'));

        $sql = "SELECT
                    v.id,
                    e.id as employee_id,
                    e.name as employee_name,
                    v.start,
                    v.end,
                    v.reason as type
                FROM kdd_vacations v
                INNER JOIN kdd_employee e ON v.employee_id = e.id
                WHERE e.authority_id = ?
                AND e.is_terminated = 0
                AND DATE(v.start) <= ?
                AND DATE(v.end) >= ?
                ORDER BY v.start ASC, e.name ASC
                LIMIT 50";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $futureDate, $today]);
        $vacations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($vacations);
    } catch (\PDOException $e) {
        error_log("Database error in getUpcomingVacations (authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve upcoming vacations."]);
    }
}

// --- Removed Vacation Functions ---
// addVacation, stopVacation, deleteVacation, getOwnVacations

?>