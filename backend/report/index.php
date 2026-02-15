<?php

/**
 * Backend Endpoint: report/index.php
 * Handles CRUD operations for Reports and related entities (Templates, Categories, Codes, etc.).
 * Uses Cookie-based Authentication and PDO database connection.
 * Accepts JSON payloads for POST/PUT/DELETE operations.
 */

declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../bootstrap.php';

// Load Dompdf if needed (used in getReportPdf) - Autoloader should handle this
use Dompdf\Dompdf;

// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../db.php'; // Defines $pdo
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed."]);
    exit();
}
require_once __DIR__ . '/../logging/logging.php'; // For logDatabaseChange and helpers

// --- Authentication ---
try {
    require_once __DIR__ . '/../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Authentication system error."]);
    exit();
}

// Load permission helper
require_once __DIR__ . '/../utils/permission_helper.php';

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority) || $authorityId === null || !is_int($authorityId)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid token payload."]);
    exit();
}
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'reports')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to report features."]); exit();
}

// Helper function to get authority ID
function getAuthorityId(PDO $pdo, ?string $authority): ?int {
    if (!$authority) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = ?");
        $stmt->execute([$authority]);
        return (int)$stmt->fetchColumn() ?: null;
    } catch (\PDOException $e) {
        error_log("Error fetching authority ID: " . $e->getMessage());
        return null;
    }
}




// --- Authorization & Action Routing ---
// Action still read from GET/POST/COOKIE via $_REQUEST for simplicity here
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    // Report specific
    'getReports'                  => ['module' => 'report', 'action' => 'read'],
    'getReport'                   => ['module' => 'report', 'action' => 'read'],
    'getOwnReportCount'           => ['module' => 'report', 'action' => 'read'],
    'getReportsToProcessCount'    => ['module' => 'report', 'action' => 'read'],
    'addReport'                   => ['module' => 'report', 'action' => 'write'],
    'editReport'                  => ['module' => 'report', 'action' => 'write'],
    'deleteReport'                => ['module' => 'report', 'action' => 'delete'],
    'pinReport'                   => ['module' => 'report', 'action' => 'write'],
    'getReportsByLinkedPerson'    => ['module' => 'report', 'action' => 'read'],
    'getReportsByLinkedCompanies' => ['module' => 'report', 'action' => 'read'],
    'getReportPdf'                => ['module' => 'report', 'action' => 'read'],

    // Template specific
    'getTemplates'                => ['module' => 'report.template', 'action' => 'read'],
    'addTemplate'                 => ['module' => 'report.template', 'action' => 'write'],
    'editTemplate'                => ['module' => 'report.template', 'action' => 'write'],
    'deleteTemplate'              => ['module' => 'report.template', 'action' => 'delete'],

    // Category specific
    'getCategories'               => ['module' => 'report.category', 'action' => 'read'],
    'addCategory'                 => ['module' => 'report.category', 'action' => 'write'],
    'editCategory'                => ['module' => 'report.category', 'action' => 'write'],
    'deleteCategory'              => ['module' => 'report.category', 'action' => 'delete'],

    // Missing Relation specific
    'addMissingRel'               => ['module' => 'report', 'action' => 'write'],
    'getMissingRels'              => ['module' => 'report', 'action' => 'read'],
    'deleteMissingRel'            => ['module' => 'report', 'action' => 'write'],

    // Code specific
    'getCodes'                    => ['module' => 'report.code', 'action' => 'read'],
    'addCode'                     => ['module' => 'report.code', 'action' => 'write'],
    'editCode'                    => ['module' => 'report.code', 'action' => 'write'],
    'deleteCode'                  => ['module' => 'report.code', 'action' => 'delete'],

    // Additional specific
    'getAdditionals'              => ['module' => 'report.additionals', 'action' => 'read'],
    'addAdditional'               => ['module' => 'report.additionals', 'action' => 'write'],
    'editAdditional'              => ['module' => 'report.additionals', 'action' => 'write'],
    'deleteAdditional'            => ['module' => 'report.additionals', 'action' => 'delete'],

    // Status specific
    'getStatuses'                 => ['module' => 'report.status', 'action' => 'read'],
    'addStatus'                   => ['module' => 'report.status', 'action' => 'write'],
    'editStatus'                  => ['module' => 'report.status', 'action' => 'write'],
    'deleteStatus'                => ['module' => 'report.status', 'action' => 'delete'],

    // Helper - requires both report and employee permissions
    'getEmployees'                => ['module' => 'report', 'action' => 'read'],
];

// Special permission check for getEmployees: Requires BOTH report.read AND employee.read
if ($action === 'getEmployees') {
    $has_read_report = hasModulePermission($userPermissions, 'report', 'read');
    $has_read_employee = hasModulePermission($userPermissions, 'employee', 'read');
    $has_all = hasAllPermissions($userPermissions);

    if (!$has_all && !($has_read_report && $has_read_employee)) {
        http_response_code(403);
        echo json_encode([
            'error' => 'Benötigt report.read UND employee.read Berechtigungen',
            'required_permissions' => ['report.read', 'employee.read']
        ]);
        exit();
    }
}

$required_permission = $permissions_map[$action] ?? null;
$has_permission = false;

if ($action === '') {
    http_response_code(400);
    echo json_encode(["error" => "No action specified."]);
    exit();
}
if ($required_permission === null) {
    http_response_code(404);
    echo json_encode(['error' => 'Invalid action specified.']);
    exit();
}

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
    // Simplified method check within the switch for clarity
    switch ($action) {
            // GET Actions (No change needed here as they use $_GET implicitly via $_REQUEST or directly)
        case 'getReports':
            if ($request_method === 'GET') getReports($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getReport':
            if ($request_method === 'GET') getReport($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getOwnReportCount':
            if ($request_method === 'GET') getOwnReportCount($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getReportsToProcessCount':
            if ($request_method === 'GET') getReportsToProcessCount($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getTemplates':
            if ($request_method === 'GET') getTemplates($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getCategories':
            if ($request_method === 'GET') getCategories($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getMissingRels':
            if ($request_method === 'GET') getMissingRels($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getEmployees':
            if ($request_method === 'GET') getEmployees($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getCodes':
            if ($request_method === 'GET') getCodes($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getAdditionals':
            if ($request_method === 'GET') getAdditionals($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getStatuses':
            if ($request_method === 'GET') getStatuses($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getReportPdf':
            if ($request_method === 'GET') getReportPdf($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getReportsByLinkedPerson':
            if ($request_method === 'GET') getReportsByLinkedPerson($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getReportsByLinkedCompanies':
            if ($request_method === 'GET') getReportsByLinkedCompanies($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;

        // POST/PUT Actions (mostly modify operations that use JSON body)
        case 'addReport':
            if ($request_method === 'POST') addReport($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'editReport':
            if ($request_method === 'PUT' || $request_method === 'POST') editReport($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'deleteReport':
            if ($request_method === 'DELETE' || $request_method === 'POST') deleteReport($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'pinReport':
            if ($request_method === 'PUT' || $request_method === 'POST') pinReport($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;

        // Template operations
        case 'addTemplate':
            if ($request_method === 'POST') addTemplate($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'editTemplate':
            if ($request_method === 'PUT' || $request_method === 'POST') editTemplate($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'deleteTemplate':
            if ($request_method === 'DELETE' || $request_method === 'POST') deleteTemplate($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;

        // Category operations
        case 'addCategory':
            if ($request_method === 'POST') addCategory($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'editCategory':
            if ($request_method === 'PUT' || $request_method === 'POST') editCategory($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'deleteCategory':
            if ($request_method === 'DELETE' || $request_method === 'POST') deleteCategory($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;

        // Missing Rel operations
        case 'addMissingRel':
            if ($request_method === 'POST') addMissingRel($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'deleteMissingRel':
            if ($request_method === 'DELETE' || $request_method === 'POST') deleteMissingRel($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;

        // Code operations
        case 'addCode':
            if ($request_method === 'POST') addCode($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'editCode':
            if ($request_method === 'PUT' || $request_method === 'POST') editCode($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'deleteCode':
            if ($request_method === 'DELETE' || $request_method === 'POST') deleteCode($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;

        // Additional operations
        case 'addAdditional':
            if ($request_method === 'POST') addAdditional($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'editAdditional':
            if ($request_method === 'PUT' || $request_method === 'POST') editAdditional($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'deleteAdditional':
            if ($request_method === 'DELETE' || $request_method === 'POST') deleteAdditional($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;

        // Status operations
        case 'addStatus':
            if ($request_method === 'POST') addStatus($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'editStatus':
            if ($request_method === 'PUT' || $request_method === 'POST') editStatus($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'deleteStatus':
            if ($request_method === 'DELETE' || $request_method === 'POST') deleteStatus($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;

        // Dashboard Widget Endpoints
        case 'getAnalytics':
            if ($request_method === 'GET') getAnalytics($pdo, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getOpenReports':
            if ($request_method === 'GET') getOpenReports($pdo, $authority, $authorityId);
            else MethodNotAllowed();
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Action not implemented.']);
            break;
    }
} else {
    // Permission denied
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied for this action.']);
}

exit();



// --- Function Definitions (Modified where $_POST was used) ---

// GET functions (getReports, getOwnReportCount, etc.) remain unchanged as they don't use POST data.
// ... (Keep all original GET functions like getReports, getOwnReportCount, getTemplates, getCategories, etc.) ...


function getReports(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        // --- Parameters for the main query ---
        $mainQueryParams = [':authorityId' => $authorityId];

        // First, get the user's roles
        $userRolesQuery = "SELECT role_id FROM kdd_user_roles WHERE user_id = :userId";
        $userRolesStmt = $pdo->prepare($userRolesQuery);
        $userRolesStmt->execute([':userId' => $userId]);
        $userRoleIds = $userRolesStmt->fetchAll(PDO::FETCH_COLUMN);

        // Build the base query with category access check
        $query = "SELECT r.*, u.username as reporter_name, c.title as category_name, 
                 CONCAT('[', e.servicenumber, '] ', e.name) as creator_name,
                 rc.code as report_code, rc.description as report_code_description,
                 rs.name as status_name, r.custom_fields as custom_fields,
                 GROUP_CONCAT(DISTINCT mr.employee_id) AS missing_employees,
                 GROUP_CONCAT(DISTINCT pr.id_person) AS linked_persons,
                 GROUP_CONCAT(DISTINCT cr.id_company) AS linked_companies,
                 GROUP_CONCAT(DISTINCT CONCAT_WS(':', ar.additionals_id, ar.price, ar.units, ar.amount)) AS additionals
                 FROM kdd_reports r
                 LEFT JOIN kdd_users u ON r.creator = u.id
                 LEFT JOIN kdd_employee e ON r.creator = e.id
                 LEFT JOIN kdd_report_category c ON r.category_id = c.id
                 LEFT JOIN kdd_report_code rc ON r.report_code_id = rc.id
                 LEFT JOIN kdd_report_status rs ON r.report_status_id = rs.id
                 LEFT JOIN kdd_report_missing_rel mr ON mr.report_id = r.id
                 LEFT JOIN kdd_report_person_rel pr ON pr.id_report = r.id
                 LEFT JOIN kdd_report_company_rel cr ON cr.id_report = r.id
                 LEFT JOIN kdd_report_additionals_rel ar ON ar.report_id = r.id
                 WHERE r.is_deleted = 0 AND r.authority_id = :authorityId";

        // Add category access check if user has roles
        if (!empty($userRoleIds)) {
            $placeholders = [];
            foreach ($userRoleIds as $index => $roleId) {
                $paramName = ":roleId" . $index;
                $placeholders[] = $paramName;
                $mainQueryParams[$paramName] = $roleId; // Add to main query params
            }
            $placeholdersStr = implode(',', $placeholders);
            $query .= " AND (c.id IS NULL OR c.id IN (
                SELECT DISTINCT report_category_id 
                FROM kdd_report_category_user_roles 
                WHERE role_id IN ($placeholdersStr)
            ))";
        }

        // Add category filter if provided
        if (isset($_GET['category_id']) && $_GET['category_id'] !== '') {
            $query .= " AND r.category_id = :categoryId";
            $mainQueryParams[':categoryId'] = $_GET['category_id']; // Add to main query params
        }

        // Add status filter if provided
        if (isset($_GET['status_id']) && $_GET['status_id'] !== '') {
            $query .= " AND r.report_status_id = :statusId";
            $mainQueryParams[':statusId'] = $_GET['status_id']; // Add to main query params
        }

        // Add search filter if provided
        if (isset($_GET['search']) && $_GET['search'] !== '') {
            $query .= " AND (r.title LIKE :search OR r.description LIKE :search)";
            $mainQueryParams[':search'] = '%' . $_GET['search'] . '%'; // Add to main query params
        }

        // Add sorting and grouping
        $query .= " GROUP BY r.id ORDER BY r.pinned DESC, r.created_at DESC";

        // Add pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        $query .= " LIMIT :limit OFFSET :offset";
        $mainQueryParams[':limit'] = $limit; // Add to main query params
        $mainQueryParams[':offset'] = $offset; // Add to main query params

        // Execute the main query
        $stmt = $pdo->prepare($query);
        
        // Bind values for the main query using mainQueryParams
        foreach ($mainQueryParams as $key => $value) {
             // Explicitly bind INT types for LIMIT/OFFSET for safety
             if ($key === ':limit' || $key === ':offset') {
                  $stmt->bindValue($key, $value, PDO::PARAM_INT);
             } else {
                  $stmt->bindValue($key, $value);
             }
        }
        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // --- Parameters for the count query ---
        // Initialize a NEW parameter array for the count query
        $countParams = [':authorityId' => $authorityId];

        // Get total count for pagination
        $countQuery = "SELECT COUNT(DISTINCT r.id) as total 
                       FROM kdd_reports r
                       LEFT JOIN kdd_report_category c ON r.category_id = c.id
                       WHERE r.is_deleted = 0 AND r.authority_id = :authorityId";

        // Add category access check to count query (use countParams)
        if (!empty($userRoleIds)) {
            $countPlaceholders = [];
            foreach ($userRoleIds as $index => $roleId) {
                // Reuse the same placeholder names as the main query for simplicity
                $paramName = ":roleId" . $index;
                $countPlaceholders[] = $paramName;
                $countParams[$paramName] = $roleId; // Add to count query params
            }
            $countPlaceholdersStr = implode(',', $countPlaceholders);
            $countQuery .= " AND (c.id IS NULL OR c.id IN (
                SELECT DISTINCT report_category_id 
                FROM kdd_report_category_user_roles 
                WHERE role_id IN ($countPlaceholdersStr)
            ))";
        }

        // Add filters to count query (use countParams)
        if (isset($_GET['category_id']) && $_GET['category_id'] !== '') {
            $countQuery .= " AND r.category_id = :categoryId";
            $countParams[':categoryId'] = $_GET['category_id']; // Add to count query params
        }
        if (isset($_GET['status_id']) && $_GET['status_id'] !== '') {
            $countQuery .= " AND r.report_status_id = :statusId";
            $countParams[':statusId'] = $_GET['status_id']; // Add to count query params
        }
        if (isset($_GET['search']) && $_GET['search'] !== '') {
            $countQuery .= " AND (r.title LIKE :search OR r.description LIKE :search)";
            $countParams[':search'] = '%' . $_GET['search'] . '%'; // Add to count query params
        }

        // Execute the count query
        $countStmt = $pdo->prepare($countQuery);

        // Bind values for the count query using countParams
        foreach ($countParams as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Process the results (remains the same)
        foreach ($reports as &$report) {
            // Process missing employees
            $report['missing_employees'] = $report['missing_employees'] ? explode(',', $report['missing_employees']) : [];

            // Process linked persons
            $report['linked_persons'] = $report['linked_persons'] ? explode(',', $report['linked_persons']) : [];

            // Process linked companies
            $report['linked_companies'] = $report['linked_companies'] ? explode(',', $report['linked_companies']) : [];

            // Process additionals
            if ($report['additionals']) {
                $additionals = [];
                foreach (explode(',', $report['additionals']) as $additional) {
                    list($id, $price, $units, $amount) = explode(':', $additional);
                    $additionals[] = [
                        'id' => $id,
                        'price' => (float)$price, // Cast to appropriate types if needed
                        'units' => (int)$units,
                        'amount' => (float)$amount // Cast to appropriate types if needed
                    ];
                }
                $report['additionals'] = $additionals;
            } else {
                $report['additionals'] = [];
            }

            // Standardize boolean fields
            $report['pinned'] = (bool)$report['pinned'];
            $report['is_deleted'] = (bool)$report['is_deleted'];
        }

        // Return the response
        header('Content-Type: application/json'); // Set content type
        echo json_encode([
            'reports' => $reports,
            'pagination' => [
                'total' => (int)$total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ]);

    } catch (PDOException $e) {
        error_log("Database error in getReports: " . $e->getMessage());
        http_response_code(500);
        header('Content-Type: application/json'); // Set content type for error
        echo json_encode(['error' => 'Database error occurred']);
    } catch (Exception $e) { // Catch other potential errors (like list() unpacking)
         error_log("Application error in getReports: " . $e->getMessage());
         http_response_code(500);
         header('Content-Type: application/json');
         echo json_encode(['error' => 'An unexpected error occurred']);
    }
}

function getOwnReportCount(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $query = "SELECT COUNT(*) as count FROM kdd_reports WHERE creator = :userId AND is_deleted = 0 AND authority_id = :authorityId";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':userId', $userId);
        $stmt->bindValue(':authorityId', $authorityId);
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

        http_response_code(200);
        echo json_encode(['count' => $count]);
    } catch (PDOException $e) {
        error_log("Database error in getOwnReportCount: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error. Please try again later.']);
    }
}

function getReportsToProcessCount(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $query = "SELECT COUNT(*) as count FROM kdd_reports WHERE is_deleted = 0 AND authority_id = :authorityId";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':authorityId', $authorityId);
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

        http_response_code(200);
        echo json_encode(['count' => $count]);
    } catch (PDOException $e) {
        error_log("Database error in getReportsToProcessCount: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error. Please try again later.']);
    }
}

function getTemplates(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $query = "SELECT *
                 FROM kdd_report_templates t
                 WHERE t.authority_id = :authorityId";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':authorityId', $authorityId);
        $stmt->execute();
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode(['templates' => $templates]);
    } catch (PDOException $e) {
        error_log("Database error in getTemplates: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error. Please try again later.']);
    }
}

function getCategories(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $query = "SELECT c.*, 
                 JSON_ARRAYAGG(
                     JSON_OBJECT(
                         'id', r.role_id,
                         'name', (SELECT name FROM kdd_roles WHERE id = r.role_id)
                     )
                 ) as roles
                 FROM kdd_report_category c
                 LEFT JOIN kdd_report_category_user_roles r ON c.id = r.report_category_id
                 WHERE c.authority_id = :authorityId
                 GROUP BY c.id
                 ORDER BY c.title ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':authorityId', $authorityId);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process roles for each category
        foreach ($categories as &$category) {
            // Parse the JSON roles string into an array
            $roles = json_decode($category['roles'], true);
            
            // Filter out null values (from LEFT JOIN when no roles exist)
            $category['roles'] = array_filter($roles, function($role) {
                return $role['id'] !== null;
            });
            
            // Reset array keys
            $category['roles'] = array_values($category['roles']);
        }

        http_response_code(200);
        echo json_encode(['categories' => $categories]);
    } catch (PDOException $e) {
        error_log("Database error in getCategories: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error. Please try again later.']);
    }
}

function getMissingRels(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        // Get report ID from query parameters
        $reportId = $_GET['report_id'] ?? null;
        if (!$reportId) {
            http_response_code(400);
            echo json_encode(['error' => 'Report ID is required.']);
            return;
        }

        // Verify report exists and belongs to authority
        $checkStmt = $pdo->prepare("SELECT id FROM kdd_reports WHERE id = :reportId AND authority_id = :authorityId AND is_deleted = 0");
        $checkStmt->bindValue(':reportId', $reportId, PDO::PARAM_INT);
        $checkStmt->bindValue(':authorityId', $authorityId, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Report not found.']);
            return;
        }

        // Get missing relations
        $query = "SELECT mr.*, e.name as employee_name, e.servicenumber
                 FROM kdd_report_missing_rel mr
                 LEFT JOIN kdd_employee e ON mr.employee_id = e.id
                 WHERE mr.report_id = :reportId";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':reportId', $reportId, PDO::PARAM_INT);
        $stmt->execute();
        $missingRels = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode(['missing_rels' => $missingRels]);
    } catch (PDOException $e) {
        error_log("Database error in getMissingRels: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error. Please try again later.']);
    }
}

function getEmployees(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $query = "SELECT id, name, servicenumber 
                 FROM kdd_employee 
                 WHERE is_terminated = 0 AND authority_id = :authorityId
                 ORDER BY name ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':authorityId', $authorityId);
        $stmt->execute();
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode(['employees' => $employees]);
    } catch (PDOException $e) {
        error_log("Database error in getEmployees: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error. Please try again later.']);
    }
}

function getCodes(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $query = "SELECT * 
                 FROM kdd_report_code 
                 WHERE is_deleted = 0 AND authority_id = :authorityId
                 ORDER BY code ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':authorityId', $authorityId);
        $stmt->execute();
        $codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode(['codes' => $codes]);
    } catch (PDOException $e) {
        error_log("Database error in getCodes: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error. Please try again later.']);
    }
}

function getAdditionals(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $query = "SELECT * 
                 FROM kdd_report_additionals 
                 WHERE is_deleted = 0 AND authority_id = :authorityId
                 ORDER BY sort_order ASC, name ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':authorityId', $authorityId);
        $stmt->execute();
        $additionals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode(['additionals' => $additionals]);
    } catch (PDOException $e) {
        error_log("Database error in getAdditionals: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error. Please try again later.']);
    }
}

function getStatuses(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        $query = "SELECT * 
                 FROM kdd_report_status 
                 WHERE is_deleted = 0 AND authority_id = :authorityId
                 ORDER BY sort_order ASC, name ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':authorityId', $authorityId);
        $stmt->execute();
        $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode(['statuses' => $statuses]);
    } catch (PDOException $e) {
        error_log("Database error in getStatuses: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error. Please try again later.']);
    }
}

function getReportPdf(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    // Implementation for PDF generation
    // This is a placeholder. The actual implementation would depend on your PDF generation system
    http_response_code(501);
    echo json_encode(['error' => 'PDF generation not implemented in this version.']);
}

function getReportsByLinkedPerson(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        // Get person ID from query parameters
        $personId = $_GET['person_id'] ?? null;
        if (!$personId) {
            http_response_code(400);
            echo json_encode(['error' => 'Person ID is required.']);
            return;
        }

        $query = "SELECT r.*, c.title as category_name, rc.code as report_code, rs.name as status_name
                 FROM kdd_reports r
                 LEFT JOIN kdd_report_person_rel rpr ON r.id = rpr.id_report
                 LEFT JOIN kdd_report_category c ON r.category_id = c.id
                 LEFT JOIN kdd_report_code rc ON r.report_code_id = rc.id
                 LEFT JOIN kdd_report_status rs ON r.report_status_id = rs.id
                 WHERE rpr.id_person = :personId AND r.is_deleted = 0 AND r.authority_id = :authorityId
                 ORDER BY r.report_date DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':personId', $personId, PDO::PARAM_INT);
        $stmt->bindValue(':authorityId', $authorityId, PDO::PARAM_INT);
        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode(['reports' => $reports]);
    } catch (PDOException $e) {
        error_log("Database error in getReportsByLinkedPerson: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error. Please try again later.']);
    }
}

function getReportsByLinkedCompanies(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        // Get company ID from query parameters
        $companyId = $_GET['company_id'] ?? null;
        if (!$companyId) {
            http_response_code(400);
            echo json_encode(['error' => 'Company ID is required.']);
            return;
        }

        $query = "SELECT r.*, c.title as category_name, rc.code as report_code, rs.name as status_name
                 FROM kdd_reports r
                 LEFT JOIN kdd_report_company_rel rcr ON r.id = rcr.id_report
                 LEFT JOIN kdd_report_category c ON r.category_id = c.id
                 LEFT JOIN kdd_report_code rc ON r.report_code_id = rc.id
                 LEFT JOIN kdd_report_status rs ON r.report_status_id = rs.id
                 WHERE rcr.id_company = :companyId AND r.is_deleted = 0 AND r.authority_id = :authorityId
                 ORDER BY r.report_date DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':companyId', $companyId, PDO::PARAM_INT);
        $stmt->bindValue(':authorityId', $authorityId, PDO::PARAM_INT);
        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode(['reports' => $reports]);
    } catch (PDOException $e) {
        error_log("Database error in getReportsByLinkedCompanies: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error. Please try again later.']);
    }
}

function addReport(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    if ($requestData === null) {
        http_response_code(400);
        echo json_encode(['error' => 'No JSON data provided in request body.']);
        return;
    }

    // --- Get Base Data from JSON ---
    $report_date_str = $requestData['report_date'] ?? null;
    // Fix ISO date format parsing
    $report_date = null;
    if (!empty($report_date_str)) {
        // Handle ISO dates with T format (e.g. "2025-01-05T13:37") and regular dates
        $dateObj = new DateTime($report_date_str);
        $report_date = $dateObj->format('Y-m-d H:i:s'); // Jetzt mit Zeit!
    }
    $report_code_id = filter_var($requestData['report_code_id'] ?? null, FILTER_VALIDATE_INT);
    $location = $requestData['location'] ?? null;
    $title = $requestData['title'] ?? '';
    $text = $requestData['text'] ?? '';
    $description = $requestData['description'] ?? '';
    $creator = filter_var($requestData['creator'] ?? $userId, FILTER_VALIDATE_INT); // Default to requesting user? Or fail if missing?
    $approved = filter_var($requestData['approved'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $category_id = filter_var($requestData['category_id'] ?? null, FILTER_VALIDATE_INT);
    $report_status_id = filter_var($requestData['report_status_id'] ?? null, FILTER_VALIDATE_INT);

    // --- Get Relation Data (expecting arrays in JSON) ---
    $missing_employees = isset($requestData['missing_employees']) && is_array($requestData['missing_employees']) ? array_filter(array_map('intval', $requestData['missing_employees']), fn($id) => $id > 0) : [];
    $linked_persons = isset($requestData['linked_persons']) && is_array($requestData['linked_persons']) ? array_filter(array_map('intval', $requestData['linked_persons']), fn($id) => $id > 0) : [];
    $linked_companies = isset($requestData['linked_companies']) && is_array($requestData['linked_companies']) ? array_filter(array_map('intval', $requestData['linked_companies']), fn($id) => $id > 0) : [];
    // Expect additionals as array of objects directly in JSON: [{"id":1, "price":10.0, "units":1, "amount":1}, ...]
    $additionals = $requestData['additionals'] ?? [];
    if (!is_array($additionals)) {
        $additionals = [];
    } // Ensure it's an array

    // --- Get Specific Fields (pass requestData to helper) ---
    $specificFieldsData = getSpecificFieldsForAuthority($authority, $requestData);

    // --- Validation ---
    if (empty($report_date) || !$report_code_id || empty($location) || !$category_id || !$report_status_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Report date, code, location, category, and status are required.']);
        return;
    }
    if (empty($creator)) { // Ensure creator ID is valid
        http_response_code(400);
        echo json_encode(['error' => 'Creator ID is required.']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // 1. Build dynamic SQL for main insert
        $baseFieldsSQL = "report_date, report_code_id, location, title, text, description, creator, user_id, approved, category_id, report_status_id";
        $basePlaceholders = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
        $baseParams = [$report_date, $report_code_id, $location, $title, $text, $description, $creator, $userId, $approved ? 1 : 0, $category_id, $report_status_id];

        $specificFieldsSQL = "";
        $specificPlaceholders = "";
        $specificParams = [];

        foreach ($specificFieldsData as $field => $value) {
            $specificFieldsSQL .= ", `{$field}`"; // Use backticks
            $specificPlaceholders .= ", ?";
            $specificParams[] = $value; // Add value to separate array
        }

        $sql = "INSERT INTO `kdd_reports` (authority_id, {$baseFieldsSQL}{$specificFieldsSQL}) VALUES (?, {$basePlaceholders}{$specificPlaceholders})";
        $allParams = array_merge([$authorityId], $baseParams, $specificParams);

        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute($allParams)) {
            throw new \Exception("Failed to insert report record.");
        }
        $report_id = $pdo->lastInsertId();
        if (!$report_id) {
            throw new \Exception("Failed to retrieve ID for new report.");
        }

        // Log main report creation
        $reportChanges = [
            ['column_name' => 'report_date', 'old_value' => null, 'new_value' => $report_date],
            ['column_name' => 'report_code_id', 'old_value' => null, 'new_value' => $report_code_id],
            ['column_name' => 'location', 'old_value' => null, 'new_value' => $location],
            ['column_name' => 'title', 'old_value' => null, 'new_value' => $title],
            ['column_name' => 'category_id', 'old_value' => null, 'new_value' => $category_id],
            ['column_name' => 'report_status_id', 'old_value' => null, 'new_value' => $report_status_id]
        ];
        logDatabaseChange($authorityId, $pdo, 'INSERT', "reports", $report_id, $userId, $reportChanges);

        // 2. Insert Relations
        if (!empty($missing_employees)) {
            $stmtRel = $pdo->prepare("INSERT INTO `kdd_report_missing_rel` (authority_id, report_id, employee_id) VALUES (?, ?, ?)");
            foreach ($missing_employees as $employee_id) {
                if ($employee_id > 0) {
                    $stmtRel->execute([$authorityId, $report_id, $employee_id]);
                    $relId = $pdo->lastInsertId();
                    $changes = [
                        ['column_name' => 'report_id', 'old_value' => null, 'new_value' => $report_id],
                        ['column_name' => 'employee_id', 'old_value' => null, 'new_value' => $employee_id]
                    ];
                    logDatabaseChange($authorityId, $pdo, 'INSERT', "report_missing_rel", $relId, $userId, $changes);
                }
            }
        }
        if (!empty($linked_persons)) {
            $stmtRel = $pdo->prepare("INSERT INTO `kdd_report_person_rel` (authority_id, id_report, id_person) VALUES (?, ?, ?)");
            foreach ($linked_persons as $person_id) {
                if ($person_id > 0) {
                    $stmtRel->execute([$authorityId, $report_id, $person_id]);
                    $relId = $pdo->lastInsertId();
                    $changes = [
                        ['column_name' => 'id_report', 'old_value' => null, 'new_value' => $report_id],
                        ['column_name' => 'id_person', 'old_value' => null, 'new_value' => $person_id]
                    ];
                    logDatabaseChange($authorityId, $pdo, 'INSERT', "report_person_rel", $relId, $userId, $changes);
                }
            }
        }
        if (!empty($linked_companies)) {
            $stmtRel = $pdo->prepare("INSERT INTO `kdd_report_company_rel` (authority_id, id_report, id_company) VALUES (?, ?, ?)");
            foreach ($linked_companies as $company_id) {
                if ($company_id > 0) {
                    $stmtRel->execute([$authorityId, $report_id, $company_id]);
                    $relId = $pdo->lastInsertId();
                    $changes = [
                        ['column_name' => 'id_report', 'old_value' => null, 'new_value' => $report_id],
                        ['column_name' => 'id_company', 'old_value' => null, 'new_value' => $company_id]
                    ];
                    logDatabaseChange($authorityId, $pdo, 'INSERT', "report_company_rel", $relId, $userId, $changes);
                }
            }
        }
        if (!empty($additionals)) {
            $stmtAdd = $pdo->prepare("INSERT INTO `kdd_report_additionals_rel` (authority_id, report_id, additionals_id, price, units, amount) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($additionals as $additional) {
                // Validate each additional item structure before insertion
                $addId = filter_var($additional['id'] ?? null, FILTER_VALIDATE_INT);
                if ($addId) { // Only process if ID is valid
                    $addPrice = filter_var($additional['price'] ?? 0.0, FILTER_VALIDATE_FLOAT);
                    $addUnits = filter_var($additional['units'] ?? 0, FILTER_VALIDATE_INT);
                    $addAmount = filter_var($additional['amount'] ?? 0, FILTER_VALIDATE_INT);
                    $stmtAdd->execute([$authorityId, $report_id, $addId, $addPrice, $addUnits, $addAmount]);
                    $addRelId = $pdo->lastInsertId();
                    $changes = [
                        ['column_name' => 'report_id', 'old_value' => null, 'new_value' => $report_id],
                        ['column_name' => 'additionals_id', 'old_value' => null, 'new_value' => $addId],
                        ['column_name' => 'price', 'old_value' => null, 'new_value' => $addPrice],
                        ['column_name' => 'units', 'old_value' => null, 'new_value' => $addUnits],
                        ['column_name' => 'amount', 'old_value' => null, 'new_value' => $addAmount]
                    ];
                    logDatabaseChange($authorityId, $pdo, 'INSERT', "report_additionals_rel", $addRelId, $userId, $changes);
                } else {
                    error_log("Skipping invalid additional item in addReport: " . json_encode($additional));
                }
            }
        }

        $pdo->commit();
        http_response_code(201);
        echo json_encode(["success" => true, "message" => "Report added successfully.", "id" => $report_id]);

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in addReport ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not add report: " . $e->getMessage()]);
    }
}

function editReport(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    error_log("editReport called with data: " . json_encode($requestData));
    
    if ($requestData === null) {
        http_response_code(400);
        echo json_encode(['error' => 'No JSON data provided in request body.']);
        return;
    }

    // --- Get Base Data from JSON ---
    $id = filter_var($requestData['id'] ?? null, FILTER_VALIDATE_INT);
    $report_date_str = $requestData['report_date'] ?? null;
    // Fix ISO date format parsing
    $report_date = null;
    if (!empty($report_date_str)) {
        // Handle ISO dates with T format (e.g. "2025-01-05T13:37") and regular dates
        $dateObj = new DateTime($report_date_str);
        $report_date = $dateObj->format('Y-m-d H:i:s'); // Jetzt mit Zeit!
    }
    $report_code_id = filter_var($requestData['report_code_id'] ?? null, FILTER_VALIDATE_INT);
    $location = $requestData['location'] ?? null;
    $title = $requestData['title'] ?? '';
    $text = $requestData['text'] ?? '';
    $description = $requestData['description'] ?? '';
    // Creator should generally not be updatable
    $approved = filter_var($requestData['approved'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $category_id = filter_var($requestData['category_id'] ?? null, FILTER_VALIDATE_INT);
    $report_status_id = filter_var($requestData['report_status_id'] ?? null, FILTER_VALIDATE_INT);

    // --- Get Relation Data from JSON ---
    $missing_employees = isset($requestData['missing_employees']) && is_array($requestData['missing_employees']) ? array_filter(array_map('intval', $requestData['missing_employees']), fn($id) => $id > 0) : [];
    $linked_persons = isset($requestData['linked_persons']) && is_array($requestData['linked_persons']) ? array_filter(array_map('intval', $requestData['linked_persons']), fn($id) => $id > 0) : [];
    $linked_companies = isset($requestData['linked_companies']) && is_array($requestData['linked_companies']) ? array_filter(array_map('intval', $requestData['linked_companies']), fn($id) => $id > 0) : [];
    $additionals = $requestData['additionals'] ?? [];
    if (!is_array($additionals)) {
        $additionals = [];
    }

    // --- Get Specific Fields (pass requestData to helper) ---
    error_log("Calling getSpecificFieldsForAuthority for authority: $authority");
    $specificFieldsData = getSpecificFieldsForAuthority($authority, $requestData);
    error_log("Specific fields data: " . json_encode($specificFieldsData));

    // --- Validation ---
    if (!$id || empty($report_date) || !$report_code_id || empty($location) || !$category_id || !$report_status_id) {
        error_log("Validation failed for report: id=$id, report_date=$report_date, report_code_id=$report_code_id, location=$location, category_id=$category_id, report_status_id=$report_status_id");
        http_response_code(400);
        echo json_encode(['error' => 'ID, Report date, code, location, category, and status are required.']);
        return;
    }

    try {
        $pdo->beginTransaction();
        error_log("Started transaction for editing report ID: $id");

        // Get old report data for logging
        $oldReport = getEntryById($pdo, $id, "kdd_reports");
        if (!$oldReport) {
            throw new \Exception("Report not found for ID: $id");
        }

        // 1. Build dynamic SQL for main update
        $baseFields = [
            'report_date' => $report_date,
            'report_code_id' => $report_code_id,
            'location' => $location,
            'title' => $title,
            'text' => $text,
            'description' => $description, /*'creator' => $creator,*/
            'approved' => $approved ? 1 : 0,
            'category_id' => $category_id,
            'report_status_id' => $report_status_id
        ];
        $allFieldsData = array_merge($baseFields, $specificFieldsData); // Combine base and specific fields
        error_log("All fields to update: " . json_encode($allFieldsData));

        $setParts = [];
        $params = [];
        foreach ($allFieldsData as $field => $value) {
            // Use named placeholders for PDO safety and clarity
            $setParts[] = "`{$field}` = :{$field}";
            $params[":{$field}"] = $value;
        }
        $setParts[] = "updated_at = NOW()"; // Add timestamp update
        $params[":id"] = $id; // Add ID for WHERE
        $params[":authorityId"] = $authorityId; // Add authority ID for WHERE

        $sql = "UPDATE `kdd_reports` SET " . implode(', ', $setParts) . " WHERE authority_id = :authorityId AND id = :id AND is_deleted = 0";
        error_log("Generated SQL: $sql");
        error_log("SQL parameters: " . json_encode($params));

        $stmt = $pdo->prepare($sql);

        if (!$stmt->execute($params)) {
            error_log("SQL execution failed: " . json_encode($stmt->errorInfo()));
            throw new \Exception("Failed to update report record.");
        }

        $rowsAffected = $stmt->rowCount();
        error_log("SQL update completed. Rows affected: $rowsAffected");

        if ($rowsAffected === 0) {
            error_log("WARNING: Report update executed but no rows affected (ID: $id, Authority: $authorityId). This could mean no changes were made or the report was not found.");
        }

        // Log report changes
        if ($rowsAffected > 0 && $oldReport) {
            $changes = [];
            foreach ($allFieldsData as $field => $newValue) {
                if (isset($oldReport[$field]) && $oldReport[$field] != $newValue) {
                    $changes[] = ['column_name' => $field, 'old_value' => $oldReport[$field], 'new_value' => $newValue];
                }
            }
            if (!empty($changes)) {
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "reports", $id, $requestingUserId, $changes);
            }
        }

        // 2. Remove and Reinsert Relationships (using helper function, passing PDO and userId)
        error_log("Calling removeAndReinsertRelationships for report ID: $id");
        if (!removeAndReinsertRelationships($pdo, $authority, $authorityId, $id, $missing_employees, $linked_persons, $additionals, $linked_companies, $requestingUserId)) {
            error_log("Failed to update report relationships for report ID: $id");
            throw new \Exception("Failed to update report relationships.");
        }
        error_log("Successfully updated relationships for report ID: $id");

        $pdo->commit();
        error_log("Transaction committed successfully for report ID: $id");

        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Report updated successfully."]);

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
            error_log("Transaction rolled back due to error for report ID: $id");
        }
        error_log("Error in editReport (ID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update report: " . $e->getMessage()]);
    }
}

function deleteReport(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    // ID might be in URL param for RESTful DELETE, but here we expect JSON body based on original code
    $id = filter_var($requestData['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing report ID in request body.']);
        return;
    }

    try {
        $sql = "UPDATE `kdd_reports` SET is_deleted = 1 WHERE authority_id = :authorityId AND id = :id AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([':authorityId' => $authorityId, ':id' => $id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Report marked as deleted."]);
            // Log change
            $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "reports", $id, $requestingUserId, $changes);
        } elseif ($success) {
            http_response_code(404);
            echo json_encode(["error" => "Report not found or already deleted."]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to execute report deletion.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteReport (ID: $id, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete report."]);
    }
}

function pinReport(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    $id = filter_var($requestData['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing report ID in request body.']);
        return;
    }

    try {
        // 1. Get current status
        $sqlSelect = "SELECT pinned FROM `kdd_reports` WHERE authority_id = :authorityId AND id = :id AND is_deleted = 0";
        $stmtSelect = $pdo->prepare($sqlSelect);
        $stmtSelect->execute([':authorityId' => $authorityId, ':id' => $id]);
        $currentStatusResult = $stmtSelect->fetchColumn();

        if ($currentStatusResult === false) {
            http_response_code(404);
            echo json_encode(["error" => "Report not found or deleted."]);
            return;
        }
        $currentStatus = (int)$currentStatusResult;
        $newStatus = $currentStatus === 1 ? 0 : 1; // Toggle status

        // 2. Update status
        $sqlUpdate = "UPDATE `kdd_reports` SET pinned = :newStatus, updated_at = NOW() WHERE authority_id = :authorityId AND id = :id";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $success = $stmtUpdate->execute([':newStatus' => $newStatus, ':authorityId' => $authorityId, ':id' => $id]);

        if ($success && $stmtUpdate->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Report pin status toggled.", "new_status" => $newStatus]);
            // Log change
            $changes = [['column_name' => 'pinned', 'old_value' => $currentStatus, 'new_value' => $newStatus]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "reports", $id, $requestingUserId, $changes);
        } elseif ($success) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Pin status not changed (no update needed?)."]); // Changed from error to success
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to toggle pin status.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in pinReport (ID: $id, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not toggle pin status."]);
    }
}

// == TEMPLATE CRUD (Modified) ==
// DUPLICATE FUNCTION - Already defined at line 549
// function getTemplates(PDO $pdo, int $userId, string $authority, int $authorityId): void
// {
//     // Function body was here
// }

function addTemplate(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    if ($requestData === null) {
        http_response_code(400);
        echo json_encode(['error' => 'No JSON data provided in request body.']);
        return;
    }

    $name = $requestData['name'] ?? null;
    $template = $requestData['template'] ?? null;

    if (empty(trim($name ?? '')) || $template === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Template name and content are required.']);
        return;
    }

    try {
        $sql = "INSERT INTO `kdd_report_templates` (authority_id, name, template) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $name, $template]);

        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Template added successfully.", "id" => $newId]);
            $changes = [['column_name' => 'name', 'old_value' => null, 'new_value' => $name]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "report_templates", $newId, $requestingUserId, $changes);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add template.']);
        }
    } catch (\PDOException $e) {
        if ($e->getCode() == '23000') {
            http_response_code(409);
            echo json_encode(["error" => "Could not add template: Name might already exist."]);
        } else {
            error_log("DB error in addTemplate ($authority): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["error" => "Could not add template."]);
        }
    }
}

function editTemplate(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    if ($requestData === null) {
        http_response_code(400);
        echo json_encode(['error' => 'No JSON data provided in request body.']);
        return;
    }

    $id = filter_var($requestData['id'] ?? null, FILTER_VALIDATE_INT);
    $name = $requestData['name'] ?? null;
    $template = $requestData['template'] ?? null;

    if (!$id || empty(trim($name ?? '')) || $template === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Template ID, name, and content are required.']);
        return;
    }

    try {
        // Get old entry for logging
        $oldEntry = getEntryById($pdo, $id, "kdd_report_templates");

        $sql = "UPDATE `kdd_report_templates` SET name = ?, template = ? WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$name, $template, $authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Template updated successfully."]);

            // Log change
            if ($oldEntry) {
                $changes = [];
                if ($oldEntry['name'] != $name) {
                    $changes[] = ['column_name' => 'name', 'old_value' => $oldEntry['name'], 'new_value' => $name];
                }
                if ($oldEntry['template'] != $template) {
                    $changes[] = ['column_name' => 'template', 'old_value' => $oldEntry['template'], 'new_value' => $template];
                }
                if (!empty($changes)) {
                    logDatabaseChange($authorityId, $pdo, 'UPDATE', "report_templates", $id, $requestingUserId, $changes);
                }
            }
        } elseif ($success) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "No changes made to the template."]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to execute template update.']);
        }
    } catch (\PDOException $e) {
        if ($e->getCode() == '23000') {
            http_response_code(409);
            echo json_encode(["error" => "Could not update template: Name might already exist."]);
        } else {
            error_log("DB error in editTemplate (ID: $id, Auth: $authority): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["error" => "Could not update template."]);
        }
    }
}

function deleteTemplate(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    $id = filter_var($requestData['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing template ID in request body.']);
        return;
    }

    try {
        // Change to soft delete for consistency? Assuming is_deleted column exists.
        $sql = "DELETE FROM kdd_report_templates WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$id, $authorityId]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Template marked as deleted."]);
            $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "report_templates", $id, $requestingUserId, $changes);
        } elseif ($success) {
            http_response_code(404);
            echo json_encode(["error" => "Template not found or already deleted."]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to execute template deletion.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteTemplate (ID: $id, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete template."]);
    }
}

// == CATEGORY CRUD (Modified) ==

function addCategory(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    if ($requestData === null) {
        http_response_code(400);
        echo json_encode(['error' => 'No JSON data provided.']);
        return;
    }

    $name = $requestData['name'] ?? null;
    $title = $requestData['title'] ?? ''; // Title seems specific to report category
    $template = $requestData['template'] ?? ''; // Default template text?

    if (empty(trim($name ?? ''))) {
        http_response_code(400);
        echo json_encode(['error' => 'Category name is required.']);
        return;
    }

    try {
        // Get next sort order? Assume 0 or let DB handle default for now.
        $sort_order = 0; // Or implement logic to find max sort order

        $sql = "INSERT INTO `kdd_report_category` (authority_id, name, title, template)
                VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $name, $title, $template]);

        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Category added successfully.", "id" => $newId]);
            $logData = json_encode(['name' => $name, 'title' => $title]);
            $changes = [['column_name' => 'row_data', 'old_value' => null, 'new_value' => $logData]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "report_category", $newId, $requestingUserId, $changes);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add category.']);
        }
    } catch (\PDOException $e) {
        if ($e->getCode() == '23000') {
            http_response_code(409);
            echo json_encode(["error" => "Could not add category: Name might already exist."]);
        } else {
            error_log("DB error in addCategory (Report Context) ($authority): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["error" => "Could not add category."]);
        }
    }
}

function editCategory(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    if ($requestData === null) {
        http_response_code(400);
        echo json_encode(['error' => 'No JSON data provided.']);
        return;
    }

    $id = filter_var($requestData['id'] ?? null, FILTER_VALIDATE_INT);
    $name = $requestData['name'] ?? null;
    $title = $requestData['title'] ?? null;
    $template = $requestData['template'] ?? null;
    $roleIds = $requestData['roleIds'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Category ID is required.']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Get old entry for logging
        $oldEntry = getEntryById($pdo, $id, "kdd_report_category");

        // Build SET clause dynamically
        $setParts = [];
        $params = [];
        if (array_key_exists('name', $requestData) && !empty(trim($requestData['name']))) {
            $setParts[] = "name = :name";
            $params[':name'] = trim($requestData['name']);
        }
        if (array_key_exists('title', $requestData)) {
            $setParts[] = "title = :title";
            $params[':title'] = $requestData['title'];
        }
        if (array_key_exists('template', $requestData)) {
            $setParts[] = "template = :template";
            $params[':template'] = $requestData['template'];
        }

        if (empty($setParts)) {
            http_response_code(400);
            echo json_encode(['error' => 'No valid fields to update provided in JSON body.']);
            return;
        }

        $params[':id'] = $id;
        $params[':authorityId'] = $authorityId;

        $sql = "UPDATE `kdd_report_category` SET " . implode(', ', $setParts) . " WHERE authority_id = :authorityId AND id = :id";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);

        // Log category field changes
        if ($success && $oldEntry) {
            $changes = [];
            foreach (['name', 'title', 'template'] as $field) {
                if (array_key_exists($field, $requestData) && $oldEntry[$field] != $requestData[$field]) {
                    $changes[] = ['column_name' => $field, 'old_value' => $oldEntry[$field], 'new_value' => $requestData[$field]];
                }
            }
            if (!empty($changes)) {
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "report_category", $id, $requestingUserId, $changes);
            }
        }

        // Handle role assignments if roleIds are provided
        if ($success && $roleIds !== null) {
            // Get old role assignments for logging
            $sqlGetOldRoles = "SELECT * FROM `kdd_report_category_user_roles` WHERE report_category_id = ?";
            $stmtGetOldRoles = $pdo->prepare($sqlGetOldRoles);
            $stmtGetOldRoles->execute([$id]);
            $oldRoles = $stmtGetOldRoles->fetchAll(PDO::FETCH_ASSOC);

            // First, remove all existing role assignments for this category
            $deleteSql = "DELETE FROM `kdd_report_category_user_roles` WHERE report_category_id = :categoryId";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute([':categoryId' => $id]);

            // Log deleted role assignments
            foreach ($oldRoles as $oldRole) {
                $changes = [
                    ['column_name' => 'PERMANENT_DELETE', 'old_value' => json_encode($oldRole), 'new_value' => null]
                ];
                logDatabaseChange($authorityId, $pdo, 'DELETE', "report_category_user_roles", $oldRole['id'], $requestingUserId, $changes);
            }

            // Then, insert new role assignments
            if (!empty($roleIds)) {
                $insertSql = "INSERT INTO `kdd_report_category_user_roles` (report_category_id, role_id) VALUES (:categoryId, :roleId)";
                $insertStmt = $pdo->prepare($insertSql);

                foreach ($roleIds as $roleId) {
                    $insertStmt->execute([
                        ':categoryId' => $id,
                        ':roleId' => $roleId
                    ]);

                    // Log new role assignment
                    $roleAssignId = $pdo->lastInsertId();
                    $changes = [
                        ['column_name' => 'report_category_id', 'old_value' => null, 'new_value' => $id],
                        ['column_name' => 'role_id', 'old_value' => null, 'new_value' => $roleId]
                    ];
                    logDatabaseChange($authorityId, $pdo, 'INSERT', "report_category_user_roles", $roleAssignId, $requestingUserId, $changes);
                }
            }
        }

        if ($success) {
            $pdo->commit();
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Category and role assignments updated successfully."
            ]);
        } else {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to execute category update.']);
        }
    } catch (\PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == '23000') {
            http_response_code(409);
            echo json_encode(["error" => "Could not update category: Name might already exist."]);
        } else {
            error_log("DB error in editCategory (Report Context) (ID: $id, Auth: $authority): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["error" => "Could not update category."]);
        }
    }
}

function deleteCategory(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    $id = filter_var($requestData['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing category ID in request body.']);
        return;
    }

    try {
        // Check if category contains non-deleted reports first
        $sqlCheck = "SELECT COUNT(*) FROM `kdd_reports` WHERE authority_id = ? AND category_id = ? AND is_deleted = 0";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$authorityId, $id]);
        $count = $stmtCheck->fetchColumn();

        if ($count > 0) {
            http_response_code(409); // Conflict
            echo json_encode(['error' => 'Cannot delete category that contains active reports.']);
            return;
        }

        // Proceed with deletion or marking as deleted
        $sql = "DELETE FROM `kdd_report_category` WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Category successfully deleted."]);
            // Log deletion if needed
            // global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "kdd_report_category", $id, $requestingUserId, []);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Category not found or already deleted."]);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteCategory (Report) (ID: $id, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete category."]);
    }
}

// == MISSING RELATION CRUD (Modified) ==

function addMissingRel(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    if ($requestData === null) {
        http_response_code(400);
        echo json_encode(['error' => 'No JSON data provided.']);
        return;
    }

    $report_id = filter_var($requestData['report_id'] ?? null, FILTER_VALIDATE_INT);
    $employee_id = filter_var($requestData['employee_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$report_id || !$employee_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Report ID and Employee ID are required.']);
        return;
    }

    try {
        // Check if relationship already exists
        $sqlCheck = "SELECT COUNT(*) FROM `kdd_report_missing_rel` 
                    WHERE report_id = ? AND employee_id = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$report_id, $employee_id]);
        $exists = $stmtCheck->fetchColumn() > 0;

        if ($exists) {
            http_response_code(409); // Conflict
            echo json_encode(['error' => 'This employee is already marked as missing on this report.']);
            return;
        }

        // Add the relationship
        $sql = "INSERT INTO `kdd_report_missing_rel` (report_id, employee_id) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$report_id, $employee_id]);

        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Employee successfully marked as missing.", "id" => $newId]);
            // Log if needed
            // $logData = json_encode(['report_id' => $report_id, 'employee_id' => $employee_id]);
            // $changes = [['column_name' => 'row_data', 'old_value' => null, 'new_value' => $logData]];
            // global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                $changes = []; // Initialisiere $changes als leeres Array
                logDatabaseChange($authorityId, $pdo, 'INSERT', "report_missing_rel", $newId, $requestingUserId, $changes);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add relationship.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in addMissingRel (Report Context) (Report: $report_id, Employee: $employee_id): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not add relationship."]);
    }
}

function deleteMissingRel(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    $id = filter_var($requestData['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing relationship ID.']);
        return;
    }

    try {
        // Verify the relationship exists and belongs to this authority by checking report's authority
        $sqlCheck = "SELECT r.authority_id 
                    FROM `kdd_report_missing_rel` mr
                    JOIN `kdd_reports` r ON mr.report_id = r.id 
                    WHERE mr.id = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$id]);
        $relationAuthId = $stmtCheck->fetchColumn();

        if (!$relationAuthId || $relationAuthId != $authorityId) {
            http_response_code(404);
            echo json_encode(['error' => 'Relationship not found or not accessible.']);
            return;
        }

        // Delete the relationship
        $sql = "DELETE FROM `kdd_report_missing_rel` WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Relationship successfully deleted."]);
            // Log if needed
            // global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "report_missing_rel", $id, $requestingUserId, []);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Relationship not found."]);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteMissingRel (ID: $id, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete relationship."]);
    }
}

// == CODE CRUD (Modified) ==
function addCode(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    if ($requestData === null) {
        http_response_code(400);
        echo json_encode(['error' => 'No JSON data provided.']);
        return;
    }

    $code = trim($requestData['code'] ?? '');
    $description = trim($requestData['description'] ?? '');

    if (empty($code)) {
        http_response_code(400);
        echo json_encode(['error' => 'Code is required.']);
        return;
    }

    try {
        $sql = "INSERT INTO `kdd_report_code` (authority_id, code, description) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $code, $description]);

        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Code added successfully.", "id" => $newId]);
            $logData = json_encode(['code' => $code, 'description' => $description]);
            $changes = [['column_name' => 'row_data', 'old_value' => null, 'new_value' => $logData]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "report_code", $newId, $requestingUserId, $changes);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add code.']);
        }
    } catch (\PDOException $e) {
        if ($e->getCode() == '23000') {
            http_response_code(409);
            echo json_encode(["error" => "Could not add code: Code might already exist."]);
        } else {
            error_log("DB error in addCode (Report Context) ($authority): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["error" => "Could not add code."]);
        }
    }
}

function editCode(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    if ($requestData === null) {
        http_response_code(400);
        echo json_encode(['error' => 'No JSON data provided.']);
        return;
    }

    $id = filter_var($requestData['id'] ?? null, FILTER_VALIDATE_INT);
    $code = $requestData['code'] ?? null;
    $description = $requestData['description'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Code ID is required.']);
        return;
    }

    try {
        // Get old entry for logging
        $oldEntry = getEntryById($pdo, $id, "kdd_report_code");

        // Build SET clause dynamically
        $setParts = [];
        $params = [];
        // Check if the key exists in the request data before adding to update
        if (array_key_exists('code', $requestData) && !empty(trim($requestData['code']))) {
            $setParts[] = "code = :code";
            $params[':code'] = trim($requestData['code']);
        }
        if (array_key_exists('description', $requestData)) {
            $setParts[] = "description = :description";
            $params[':description'] = $requestData['description'];
        }

        if (empty($setParts)) {
            http_response_code(400);
            echo json_encode(['error' => 'No valid fields to update provided in JSON body.']);
            return;
        }

        $params[':id'] = $id; // For WHERE clause
        $params[':authorityId'] = $authorityId;

        $sql = "UPDATE `kdd_report_code` SET " . implode(', ', $setParts) . " WHERE authority_id = :authorityId AND id = :id";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Code updated successfully."]);

            // Log changes
            if ($oldEntry) {
                $changes = [];
                foreach (['code', 'description'] as $field) {
                    if (array_key_exists($field, $requestData) && $oldEntry[$field] != $requestData[$field]) {
                        $changes[] = ['column_name' => $field, 'old_value' => $oldEntry[$field], 'new_value' => $requestData[$field]];
                    }
                }
                if (!empty($changes)) {
                    logDatabaseChange($authorityId, $pdo, 'UPDATE', "report_code", $id, $requestingUserId, $changes);
                }
            }
        } elseif ($success) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "No changes made to the code."]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to execute code update.']);
        }
    } catch (\PDOException $e) {
        if ($e->getCode() == '23000') {
            http_response_code(409);
            echo json_encode(["error" => "Could not update code: Code might already exist."]);
        } else {
            error_log("DB error in editCode (Report Context) (ID: $id, Auth: $authority): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["error" => "Could not update code."]);
        }
    }
}

function deleteCode(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    $id = filter_var($requestData['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing code ID in request body.']);
        return;
    }

    try {
        // Check if code is used in reports
        $sqlCheck = "SELECT COUNT(*) FROM `kdd_reports` WHERE authority_id = ? AND report_code_id = ? AND is_deleted = 0";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$authorityId, $id]);
        $count = $stmtCheck->fetchColumn();

        if ($count > 0) {
            http_response_code(409); // Conflict
            echo json_encode(['error' => 'Cannot delete code that is used in active reports.']);
            return;
        }

        // Proceed with marking as deleted
        $sql = "UPDATE `kdd_report_code` SET is_deleted = 1 WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Code successfully deleted."]);
            // Log deletion if needed
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Code not found or already deleted."]);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteCode (Report) (ID: $id, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete code."]);
    }
}

// == ADDITIONAL CRUD (Modified) ==

function addAdditional(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    if ($requestData === null) {
        http_response_code(400);
        echo json_encode(['error' => 'No JSON data provided.']);
        return;
    }

    $name = trim($requestData['name'] ?? '');
    $description = trim($requestData['description'] ?? '');
    $price = filter_var($requestData['price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $units = filter_var($requestData['units'] ?? 1, FILTER_VALIDATE_INT);
    $sort_order = filter_var($requestData['sort_order'] ?? 0, FILTER_VALIDATE_INT);

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Name is required.']);
        return;
    }

    if ($price === false) $price = 0;
    if ($units === false) $units = 1;
    if ($sort_order === false) $sort_order = 0;

    try {
        $sql = "INSERT INTO `kdd_report_additionals` (authority_id, name, description, price, units, sort_order)
               VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $name, $description, $price, $units, $sort_order]);

        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Additional item added successfully.", "id" => $newId]);
            $logData = json_encode([
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'units' => $units,
                'sort_order' => $sort_order
            ]);
            $changes = [['column_name' => 'row_data', 'old_value' => null, 'new_value' => $logData]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "report_additionals", $newId, $requestingUserId, $changes);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add additional item.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in addAdditional (Report Context) ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not add additional item."]);
    }
}

function editAdditional(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    if ($requestData === null) {
        http_response_code(400);
        echo json_encode(['error' => 'No JSON data provided.']);
        return;
    }

    $id = filter_var($requestData['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Additional item ID is required.']);
        return;
    }

    // Check which fields are present in the request body for update
    $updateFields = [];
    if (array_key_exists('name', $requestData)) {
        if (empty(trim($requestData['name']))) {
            http_response_code(400);
            echo json_encode(['error' => 'Name cannot be empty if provided.']);
            return;
        }
        $updateFields['name'] = trim($requestData['name']);
    }
    if (array_key_exists('description', $requestData)) {
        $updateFields['description'] = $requestData['description'];
    } // Allow empty
    if (array_key_exists('price', $requestData)) {
        $price = filter_var($requestData['price'], FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
        if ($price === null || $price < 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid or negative price provided.']);
            return;
        }
        $updateFields['price'] = $price;
    }
    if (array_key_exists('units', $requestData)) {
        $units = filter_var($requestData['units'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($units === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Units must be a positive integer if provided.']);
            return;
        }
        $updateFields['units'] = $units;
    }
    if (array_key_exists('sort_order', $requestData)) {
        $sort_order = filter_var($requestData['sort_order'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($sort_order === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Sort order must be a non-negative integer if provided.']);
            return;
        }
        $updateFields['sort_order'] = $sort_order;
    }


    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid update fields provided in JSON body.']);
        return;
    }

    try {
        // Get old entry for logging
        $oldEntry = getEntryById($pdo, $id, "kdd_report_additionals");

        // Build SET clause dynamically
        $setParts = [];
        $params = [];
        foreach ($updateFields as $field => $value) {
            $setParts[] = "`{$field}` = :{$field}";
            $params[":{$field}"] = $value;
        }

        $params[':id'] = $id; // For WHERE clause
        $params[':authority_id'] = $authorityId; // Use named parameter for authority_id

        $sql = "UPDATE `kdd_report_additionals` SET " . implode(', ', $setParts) . " WHERE authority_id = :authority_id AND id = :id AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Additional item updated successfully."]);

            // Log changes
            if ($oldEntry) {
                $changes = [];
                foreach ($updateFields as $field => $newValue) {
                    if ($oldEntry[$field] != $newValue) {
                        $changes[] = ['column_name' => $field, 'old_value' => $oldEntry[$field], 'new_value' => $newValue];
                    }
                }
                if (!empty($changes)) {
                    logDatabaseChange($authorityId, $pdo, 'UPDATE', "report_additionals", $id, $requestingUserId, $changes);
                }
            }
        } elseif ($success) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "No changes made to the additional item."]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to execute additional item update.']);
        }
    } catch (\PDOException $e) {
        if ($e->getCode() == '23000') {
            http_response_code(409);
            echo json_encode(["error" => "Could not update item: Name might already exist."]);
        } else {
            error_log("DB error in editAdditional (ID: $id, Auth: $authority): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["error" => "Could not update additional item."]);
        }
    }
}

function deleteAdditional(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    $id = filter_var($requestData['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Additional ID is required.']);
        return;
    }

    try {
        // Check if the additional is in use in any reports
        $stmtCheck = $pdo->prepare("SELECT 1 FROM `kdd_report_additionals_rel` rel 
                                    JOIN `kdd_reports` rep ON rel.report_id = rep.id 
                                    WHERE rel.additionals_id = ? 
                                    AND rep.authority_id = ? 
                                    AND rep.is_deleted = 0 
                                    LIMIT 1");
        $stmtCheck->execute([$id, $authorityId]);
        $isInUse = (bool)$stmtCheck->fetchColumn();

        if ($isInUse) {
            http_response_code(409); // Conflict
            echo json_encode(['error' => 'This additional item is currently in use by one or more reports and cannot be deleted.']);
            return;
        }

        // Delete (mark as deleted) the additional
        $sql = "UPDATE `kdd_report_additionals` SET is_deleted = 1 WHERE authority_id = ? AND id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Additional deleted successfully.']);
            
            $changes = [
                ['column_name' => 'is_deleted', 'old_value' => '0', 'new_value' => '1']
            ];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', 'report_additionals', $id, $requestingUserId, $changes);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Additional not found or already deleted.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteAdditional (ID: $id, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Could not delete additional.']);
    }
}

// == STATUS CRUD (Modified) ==

function addStatus(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    if ($requestData === null) {
        http_response_code(400);
        echo json_encode(['error' => 'No JSON data provided.']);
        return;
    }

    $name = $requestData['name'] ?? null;
    $sort_order_req = $requestData['sort_order'] ?? null; // Explicitly check if provided

    if (empty(trim($name ?? ''))) {
        http_response_code(400);
        echo json_encode(['error' => 'Status name is required.']);
        return;
    }

    try {
        $sort_order = 0; // Default sort order
        // If sort_order is provided in JSON, validate and use it
        if ($sort_order_req !== null) {
            $sort_order_val = filter_var($sort_order_req, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($sort_order_val === false) {
                http_response_code(400);
                echo json_encode(['error' => 'Sort order must be a non-negative integer.']);
                return;
            }
            $sort_order = $sort_order_val;
        } else {
            // Get next sort order if not provided
            $stmtMax = $pdo->query("SELECT MAX(sort_order) FROM `kdd_report_status` WHERE authority_id = ?");
            $maxSort = $stmtMax->fetchColumn();
            $sort_order = ($maxSort === null || $maxSort === false) ? 0 : (int)$maxSort + 1;
        }


        $sql = "INSERT INTO `kdd_report_status` (authority_id, name, sort_order) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $name, $sort_order]);

        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Status added successfully.", "id" => $newId]);
            $changes = [['column_name' => 'name', 'old_value' => null, 'new_value' => $name]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "report_status", $newId, $requestingUserId, $changes);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add status.']);
        }
    } catch (\PDOException $e) {
        if ($e->getCode() == '23000') {
            http_response_code(409);
            echo json_encode(["error" => "Could not add status: Name might already exist."]);
        } else {
            error_log("DB error in addStatus ($authority): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["error" => "Could not add status."]);
        }
    }
}

function editStatus(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    if ($requestData === null) {
        http_response_code(400);
        echo json_encode(['error' => 'No JSON data provided.']);
        return;
    }

    $id = filter_var($requestData['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Status ID is required.']);
        return;
    }

    $updateFields = [];
    if (array_key_exists('name', $requestData)) {
        if (empty(trim($requestData['name']))) {
            http_response_code(400);
            echo json_encode(['error' => 'Name cannot be empty if provided.']);
            return;
        }
        $updateFields['name'] = trim($requestData['name']);
    }
    if (array_key_exists('sort_order', $requestData)) {
        $sort_order = filter_var($requestData['sort_order'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($sort_order === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Sort order must be a non-negative integer if provided.']);
            return;
        }
        $updateFields['sort_order'] = $sort_order;
    }

    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid update fields provided (name or sort_order).']);
        return;
    }

    try {
        // Get old entry for logging
        $oldEntry = getEntryById($pdo, $id, "kdd_report_status");

        // Build SET part dynamically
        $setParts = [];
        $params = [];
        foreach ($updateFields as $field => $value) {
            $setParts[] = "`{$field}` = :{$field}";
            $params[":{$field}"] = $value;
        }

        $params[':id'] = $id; // For WHERE clause
        $params[':authority_id'] = $authorityId; // Named parameter for authority_id


        $sql = "UPDATE `kdd_report_status` SET " . implode(', ', $setParts) . " WHERE authority_id = :authority_id AND id = :id";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Status updated successfully."]);

            // Log changes
            if ($oldEntry) {
                $changes = [];
                foreach ($updateFields as $field => $newValue) {
                    if ($oldEntry[$field] != $newValue) {
                        $changes[] = ['column_name' => $field, 'old_value' => $oldEntry[$field], 'new_value' => $newValue];
                    }
                }
                if (!empty($changes)) {
                    logDatabaseChange($authorityId, $pdo, 'UPDATE', "report_status", $id, $requestingUserId, $changes);
                }
            }
        } elseif ($success) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "No changes made to the status."]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to execute status update.']);
        }
    } catch (\PDOException $e) {
        if ($e->getCode() == '23000') {
            http_response_code(409);
            echo json_encode(["error" => "Could not update status: Name might already exist."]);
        } else {
            error_log("DB error in editStatus (ID: $id, Auth: $authority): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["error" => "Could not update status."]);
        }
    }
}

function deleteStatus(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void
{
    $requestData = getJsonRequestData();
    $id = filter_var($requestData['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing status ID in request body.']);
        return;
    }

    try {
        // Check if status is used by non-deleted reports
        $sqlCheck = "SELECT COUNT(*) FROM `kdd_reports` WHERE authority_id = ? AND report_status_id = ? AND is_deleted = 0";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$authorityId, $id]);
        $itemCount = (int) $stmtCheck->fetchColumn();

        if ($itemCount > 0) {
            http_response_code(409); // Conflict
            echo json_encode(['error' => 'Status cannot be deleted because it is still assigned to ' . $itemCount . ' report(s).']);
            return;
        }

        // Soft delete the status
        $sqlDelete = "DELETE FROM `kdd_report_status` WHERE id = ? AND authority_id = ?";
        $stmtDelete = $pdo->prepare($sqlDelete);
        $success = $stmtDelete->execute([$id, $authorityId]);

        if ($success && $stmtDelete->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Status marked as deleted."]);
            $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "kdd_report_status", $id, $requestingUserId, $changes);
        } elseif ($success) {
            http_response_code(404);
            echo json_encode(["error" => "Status not found or already deleted."]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to execute status deletion.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteStatus (ID: $id, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete status."]);
    }
}


// == HELPER FUNCTIONS (Modified/Reviewed) ==

/**
 * Gets authority-specific fields from the request data array.
 *
 * @param string $authority The authority context.
 * @param ?array $requestData The decoded JSON request data array.
 * @return array Associative array of specific fields and their values.
 */
function getSpecificFieldsForAuthority(string $authority, ?array $requestData): array
{
    $specificFields = [];
    if (!$requestData) return $specificFields;

    switch ($authority) {
        case 'fireguard':
            // Keine spezifischen Felder
            break;
        case 'police':
            $specificFields['crime_scene'] = $requestData['crime_scene'] ?? null;
            $specificFields['evidences'] = $requestData['evidences'] ?? null;
            $specificFields['evidence_location'] = $requestData['evidence_location'] ?? null;
            $specificFields['wanted_person'] = filter_var($requestData['wanted_person'] ?? 0, FILTER_VALIDATE_INT);
            $specificFields['crime'] = $requestData['crime'] ?? null;
            $specificFields['evidence_number'] = $requestData['evidence_number'] ?? null;
            $specificFields['weapon'] = $requestData['weapon'] ?? null;
            $specificFields['jail_time'] = filter_var($requestData['jail_time'] ?? 0, FILTER_VALIDATE_INT);
            $specificFields['fine'] = filter_var($requestData['fine'] ?? 0, FILTER_VALIDATE_FLOAT);
            $specificFields['court_date'] = $requestData['court_date'] ?? null;
            break;
        case 'medic':
            $specificFields['diagnosis'] = $requestData['diagnosis'] ?? null;
            $specificFields['symptoms'] = $requestData['symptoms'] ?? null;
            $specificFields['treatment'] = $requestData['treatment'] ?? null;
            $specificFields['medication'] = $requestData['medication'] ?? null;
            $specificFields['patient_name'] = $requestData['patient_name'] ?? null;
            $specificFields['patient_id'] = filter_var($requestData['patient_id'] ?? 0, FILTER_VALIDATE_INT);
            $specificFields['vital_signs'] = $requestData['vital_signs'] ?? null;
            $specificFields['allergies'] = $requestData['allergies'] ?? null;
            $specificFields['follow_up_date'] = $requestData['follow_up_date'] ?? null;
            break;
        case 'justice':
            $specificFields['case_number'] = $requestData['case_number'] ?? null;
            $specificFields['court_type'] = $requestData['court_type'] ?? null;
            $specificFields['judge_name'] = $requestData['judge_name'] ?? null;
            $specificFields['plaintiff'] = $requestData['plaintiff'] ?? null;
            $specificFields['defendant'] = $requestData['defendant'] ?? null;
            $specificFields['verdict'] = $requestData['verdict'] ?? null;
            $specificFields['sentence'] = $requestData['sentence'] ?? null;
            $specificFields['fine_amount'] = filter_var($requestData['fine_amount'] ?? 0, FILTER_VALIDATE_FLOAT);
            $specificFields['court_date'] = $requestData['court_date'] ?? null;
            $specificFields['hearing_type'] = $requestData['hearing_type'] ?? null;
            break;
        case 'statepark':
            $specificFields['area'] = $requestData['area'] ?? null;
            $specificFields['weather_conditions'] = $requestData['weather_conditions'] ?? null;
            $specificFields['wildlife_observed'] = $requestData['wildlife_observed'] ?? null;
            $specificFields['damage_assessment'] = $requestData['damage_assessment'] ?? null;
            $specificFields['ranger_patrol'] = $requestData['ranger_patrol'] ?? null;
            $specificFields['visitor_count'] = filter_var($requestData['visitor_count'] ?? 0, FILTER_VALIDATE_INT);
            break;
        case 'casa':
            $specificFields['aircraft_type'] = $requestData['aircraft_type'] ?? null;
            $specificFields['aircraft_registration'] = $requestData['aircraft_registration'] ?? null;
            $specificFields['pilot_name'] = $requestData['pilot_name'] ?? null;
            $specificFields['pilot_license'] = $requestData['pilot_license'] ?? null;
            $specificFields['flight_path'] = $requestData['flight_path'] ?? null;
            $specificFields['weather_briefing'] = $requestData['weather_briefing'] ?? null;
            $specificFields['runway_used'] = $requestData['runway_used'] ?? null;
            $specificFields['flight_type'] = $requestData['flight_type'] ?? null;
            break;
        default:
            // Keine spezifischen Felder für andere Authorities
            break;
    }

    return $specificFields;
}

/**
 * Removes and reinserts relationships for a report within a transaction.
 * IMPORTANT: This function should be called within an existing PDO transaction.
 *
 * @param PDO $pdo PDO database connection object.
 * @param string $authority Authority context.
 * @param int $report_id The ID of the report being edited.
 * @param array $missing_employees Array of employee IDs.
 * @param array $linked_persons Array of person IDs.
 * @param array $additionals Array of additional items (structured array).
 * @param array $linked_companies Array of company IDs.
 * @param int $requestingUserId The ID of the user making the changes.
 * @return bool True on success, false on failure.
 */
function removeAndReinsertRelationships(PDO $pdo, string $authority, int $authorityId, int $report_id, array $missing_employees, array $linked_persons, array $additionals, array $linked_companies, int $requestingUserId): bool
{
    try {
        // Get old relationships for logging before deletion
        $stmtOldMissing = $pdo->prepare("SELECT * FROM `kdd_report_missing_rel` WHERE report_id = ? AND authority_id = ?");
        $stmtOldMissing->execute([$report_id, $authorityId]);
        $oldMissingRels = $stmtOldMissing->fetchAll(PDO::FETCH_ASSOC);

        $stmtOldPersons = $pdo->prepare("SELECT * FROM `kdd_report_person_rel` WHERE id_report = ? AND authority_id = ?");
        $stmtOldPersons->execute([$report_id, $authorityId]);
        $oldPersonRels = $stmtOldPersons->fetchAll(PDO::FETCH_ASSOC);

        $stmtOldCompanies = $pdo->prepare("SELECT * FROM `kdd_report_company_rel` WHERE id_report = ? AND authority_id = ?");
        $stmtOldCompanies->execute([$report_id, $authorityId]);
        $oldCompanyRels = $stmtOldCompanies->fetchAll(PDO::FETCH_ASSOC);

        $stmtOldAdditionals = $pdo->prepare("SELECT * FROM `kdd_report_additionals_rel` WHERE report_id = ? AND authority_id = ?");
        $stmtOldAdditionals->execute([$report_id, $authorityId]);
        $oldAdditionalRels = $stmtOldAdditionals->fetchAll(PDO::FETCH_ASSOC);

        // Start by deleting existing relationships
        $deleteQueries = [
            "DELETE FROM `kdd_report_missing_rel` WHERE report_id = ? AND authority_id = ?",
            "DELETE FROM `kdd_report_person_rel` WHERE id_report = ? AND authority_id = ?",
            "DELETE FROM `kdd_report_company_rel` WHERE id_report = ? AND authority_id = ?",
            "DELETE FROM `kdd_report_additionals_rel` WHERE report_id = ? AND authority_id = ?"
        ];

        foreach ($deleteQueries as $query) {
            $stmt = $pdo->prepare($query);
            $stmt->execute([$report_id, $authorityId]);
        }

        // Log deleted relationships
        foreach ($oldMissingRels as $oldRel) {
            $changes = [['column_name' => 'PERMANENT_DELETE', 'old_value' => json_encode($oldRel), 'new_value' => null]];
            logDatabaseChange($authorityId, $pdo, 'DELETE', "report_missing_rel", $oldRel['id'], $requestingUserId, $changes);
        }
        foreach ($oldPersonRels as $oldRel) {
            $changes = [['column_name' => 'PERMANENT_DELETE', 'old_value' => json_encode($oldRel), 'new_value' => null]];
            logDatabaseChange($authorityId, $pdo, 'DELETE', "report_person_rel", $oldRel['id'], $requestingUserId, $changes);
        }
        foreach ($oldCompanyRels as $oldRel) {
            $changes = [['column_name' => 'PERMANENT_DELETE', 'old_value' => json_encode($oldRel), 'new_value' => null]];
            logDatabaseChange($authorityId, $pdo, 'DELETE', "report_company_rel", $oldRel['id'], $requestingUserId, $changes);
        }
        foreach ($oldAdditionalRels as $oldRel) {
            $changes = [['column_name' => 'PERMANENT_DELETE', 'old_value' => json_encode($oldRel), 'new_value' => null]];
            logDatabaseChange($authorityId, $pdo, 'DELETE', "report_additionals_rel", $oldRel['id'], $requestingUserId, $changes);
        }

        // Re-insert new relationships (using prepared statements outside loops)
        if (!empty($missing_employees)) {
            $stmt = $pdo->prepare("INSERT INTO `kdd_report_missing_rel` (report_id, employee_id, authority_id) VALUES (?, ?, ?)");
            foreach ($missing_employees as $id) {
                if ($id > 0) {
                    $stmt->execute([$report_id, $id, $authorityId]);
                    $relId = $pdo->lastInsertId();
                    $changes = [
                        ['column_name' => 'report_id', 'old_value' => null, 'new_value' => $report_id],
                        ['column_name' => 'employee_id', 'old_value' => null, 'new_value' => $id]
                    ];
                    logDatabaseChange($authorityId, $pdo, 'INSERT', "report_missing_rel", $relId, $requestingUserId, $changes);
                }
            }
        }
        if (!empty($linked_persons)) {
            $stmt = $pdo->prepare("INSERT INTO `kdd_report_person_rel` (id_report, id_person, authority_id) VALUES (?, ?, ?)");
            foreach ($linked_persons as $id) {
                if ($id > 0) {
                    $stmt->execute([$report_id, $id, $authorityId]);
                    $relId = $pdo->lastInsertId();
                    $changes = [
                        ['column_name' => 'id_report', 'old_value' => null, 'new_value' => $report_id],
                        ['column_name' => 'id_person', 'old_value' => null, 'new_value' => $id]
                    ];
                    logDatabaseChange($authorityId, $pdo, 'INSERT', "report_person_rel", $relId, $requestingUserId, $changes);
                }
            }
        }
        if (!empty($linked_companies)) {
            $stmt = $pdo->prepare("INSERT INTO `kdd_report_company_rel` (id_report, id_company, authority_id) VALUES (?, ?, ?)");
            foreach ($linked_companies as $id) {
                if ($id > 0) {
                    $stmt->execute([$report_id, $id, $authorityId]);
                    $relId = $pdo->lastInsertId();
                    $changes = [
                        ['column_name' => 'id_report', 'old_value' => null, 'new_value' => $report_id],
                        ['column_name' => 'id_company', 'old_value' => null, 'new_value' => $id]
                    ];
                    logDatabaseChange($authorityId, $pdo, 'INSERT', "report_company_rel", $relId, $requestingUserId, $changes);
                }
            }
        }
        if (!empty($additionals)) {
            $stmt = $pdo->prepare("INSERT INTO `kdd_report_additionals_rel` (report_id, additionals_id, price, units, amount, authority_id) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($additionals as $add) {
                $addId = filter_var($add['id'] ?? 0, FILTER_VALIDATE_INT);
                if ($addId > 0) {
                    $addPrice = filter_var($add['price'] ?? 0, FILTER_VALIDATE_FLOAT);
                    $addUnits = filter_var($add['units'] ?? 0, FILTER_VALIDATE_INT);
                    $addAmount = filter_var($add['amount'] ?? 0, FILTER_VALIDATE_INT);
                    $stmt->execute([$report_id, $addId, $addPrice, $addUnits, $addAmount, $authorityId]);
                    $relId = $pdo->lastInsertId();
                    $changes = [
                        ['column_name' => 'report_id', 'old_value' => null, 'new_value' => $report_id],
                        ['column_name' => 'additionals_id', 'old_value' => null, 'new_value' => $addId],
                        ['column_name' => 'price', 'old_value' => null, 'new_value' => $addPrice],
                        ['column_name' => 'units', 'old_value' => null, 'new_value' => $addUnits],
                        ['column_name' => 'amount', 'old_value' => null, 'new_value' => $addAmount]
                    ];
                    logDatabaseChange($authorityId, $pdo, 'INSERT', "report_additionals_rel", $relId, $requestingUserId, $changes);
                } else {
                    error_log("Skipping invalid additional item in removeAndReinsertRelationships: " . json_encode($add));
                }
            }
        }

        return true;
    } catch (\PDOException $e) {
        error_log("Error in removeAndReinsertRelationships: " . $e->getMessage());
        return false;
    }
}

function getReport(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        // Hole die Bericht-ID aus den Query-Parametern
        $reportId = isset($_GET['id']) ? (int)$_GET['id'] : null;
        
        if (!$reportId) {
            http_response_code(400);
            echo json_encode(['error' => 'Report ID is required.']);
            return;
        }

        // Erstelle die Grundabfrage
        $query = "SELECT r.*, 
                 c.title as category_name, 
                 rc.code as report_code,
                 rs.name as status_name,
                 u.username as creator_name
                 FROM kdd_reports r
                 LEFT JOIN kdd_report_category c ON r.category_id = c.id
                 LEFT JOIN kdd_report_code rc ON r.report_code_id = rc.id
                 LEFT JOIN kdd_report_status rs ON r.report_status_id = rs.id
                 LEFT JOIN kdd_users u ON r.creator = u.id
                 WHERE r.id = :reportId";

        // Prüfe, ob der Bericht entweder zur Behörde des Benutzers gehört oder mit ihm geteilt wurde
        $accessQuery = "AND (
            r.authority_id = :authorityId 
            OR 
            EXISTS (
                SELECT 1 FROM kdd_report_sharing rs 
                WHERE rs.report_id = r.id 
                AND rs.target_authority_id = :targetAuthorityId
            )
        )";
        
        $stmt = $pdo->prepare($query . ' ' . $accessQuery);
        $stmt->bindValue(':reportId', $reportId, PDO::PARAM_INT);
        $stmt->bindValue(':authorityId', $authorityId, PDO::PARAM_INT);
        $stmt->bindValue(':targetAuthorityId', $authorityId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Report not found or access denied.']);
            return;
        }
        
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Hole die verknüpften Personen
        $stmt = $pdo->prepare("
            SELECT id_person as person_id 
            FROM kdd_report_person_rel 
            WHERE id_report = :reportId
        ");
        $stmt->bindValue(':reportId', $reportId, PDO::PARAM_INT);
        $stmt->execute();
        $linkedPersons = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $report['linked_persons'] = $linkedPersons;
        
        // Hole die verknüpften Unternehmen
        $stmt = $pdo->prepare("
            SELECT id_company as company_id 
            FROM kdd_report_company_rel 
            WHERE id_report = :reportId
        ");
        $stmt->bindValue(':reportId', $reportId, PDO::PARAM_INT);
        $stmt->execute();
        $linkedCompanies = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $report['linked_companies'] = $linkedCompanies;
        
        // Hole die fehlenden Mitarbeiter
        $stmt = $pdo->prepare("
            SELECT employee_id 
            FROM kdd_report_missing_rel 
            WHERE report_id = :reportId
        ");
        $stmt->bindValue(':reportId', $reportId, PDO::PARAM_INT);
        $stmt->execute();
        $missingEmployees = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $report['missing_employees'] = $missingEmployees;
        
        // Hole die Zusatzinformationen
        $stmt = $pdo->prepare("
            SELECT rel.id, rel.additionals_id, rel.price, rel.units, rel.amount, a.name, a.description
            FROM kdd_report_additionals_rel rel
            JOIN kdd_report_additionals a ON rel.additionals_id = a.id
            WHERE rel.report_id = :reportId
        ");
        $stmt->bindValue(':reportId', $reportId, PDO::PARAM_INT);
        $stmt->execute();
        $additionals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $report['additionals'] = $additionals;
        
        http_response_code(200);
        echo json_encode(['data' => $report]);
    } catch (PDOException $e) {
        error_log("Database error in getReport: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error. Please try again later.']);
    }
}


/**
 * Get report analytics for dashboard widget
 * Returns: total reports, this month, open, closed, by category
 */
function getAnalytics(PDO $pdo, string $authority, int $authorityId): void {
    try {
        // Total reports
        $sqlTotal = "SELECT COUNT(*) FROM kdd_reports WHERE authority_id = ? AND is_deleted = 0";
        $stmtTotal = $pdo->prepare($sqlTotal);
        $stmtTotal->execute([$authorityId]);
        $total = (int)$stmtTotal->fetchColumn();

        // Reports this month
        $firstDayOfMonth = date('Y-m-01 00:00:00');
        $sqlThisMonth = "SELECT COUNT(*) FROM kdd_reports
                         WHERE authority_id = ? AND is_deleted = 0
                         AND created_at >= ?";
        $stmtThisMonth = $pdo->prepare($sqlThisMonth);
        $stmtThisMonth->execute([$authorityId, $firstDayOfMonth]);
        $thisMonth = (int)$stmtThisMonth->fetchColumn();

        // Open reports (approved = 0)
        $sqlOpen = "SELECT COUNT(*) FROM kdd_reports
                    WHERE authority_id = ? AND is_deleted = 0 AND approved = 0";
        $stmtOpen = $pdo->prepare($sqlOpen);
        $stmtOpen->execute([$authorityId]);
        $open = (int)$stmtOpen->fetchColumn();

        // Closed reports (approved = 1)
        $sqlClosed = "SELECT COUNT(*) FROM kdd_reports
                      WHERE authority_id = ? AND is_deleted = 0 AND approved = 1";
        $stmtClosed = $pdo->prepare($sqlClosed);
        $stmtClosed->execute([$authorityId]);
        $closed = (int)$stmtClosed->fetchColumn();

        // Reports by category
        $sqlByCategory = "SELECT c.name as category, COUNT(r.id) as count
                          FROM kdd_reports r
                          LEFT JOIN kdd_categories c ON r.category_id = c.id
                          WHERE r.authority_id = ? AND r.is_deleted = 0
                          GROUP BY c.id, c.name
                          ORDER BY count DESC
                          LIMIT 10";
        $stmtByCategory = $pdo->prepare($sqlByCategory);
        $stmtByCategory->execute([$authorityId]);
        $byCategory = $stmtByCategory->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'total' => $total,
            'thisMonth' => $thisMonth,
            'open' => $open,
            'closed' => $closed,
            'byCategory' => $byCategory
        ]);
    } catch (\PDOException $e) {
        error_log("Database error in getAnalytics (authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve report analytics."]);
    }
}

/**
 * Get open reports for dashboard widget
 * Returns: List of open reports (limited to 5)
 */
function getOpenReports(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $sql = "SELECT
                    r.id,
                    r.title,
                    r.report_date,
                    r.created_at,
                    c.name as category,
                    s.name as status
                FROM kdd_reports r
                LEFT JOIN kdd_categories c ON r.category_id = c.id
                LEFT JOIN kdd_report_status s ON r.report_status_id = s.id
                WHERE r.authority_id = ?
                AND r.is_deleted = 0
                AND r.approved = 0
                ORDER BY r.created_at DESC
                LIMIT 5";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($reports);
    } catch (\PDOException $e) {
        error_log("Database error in getOpenReports (authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve open reports."]);
    }
}
