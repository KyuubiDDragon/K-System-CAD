<?php
/**
 * Backend Endpoint: admin/logs/index.php
 * Handles operations for System Logs (View, Filter).
 * Uses Cookie-based Authentication and PDO database connection.
 */
declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../bootstrap.php';

// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../../db.php'; // Defines $pdo
} catch (Exception $e) {
    http_response_code(500); echo json_encode(["error" => "Database connection failed."]); exit();
}

// --- Authentication ---
try {
    require_once __DIR__ . '/../../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- User Context & Authority Validation ---
$requestingUserId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

if ($requestingUserId === null || !is_int($requestingUserId) || $authority === null || !is_string($authority)) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'default')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to default features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required ADMIN permissions
$permissions_map = [
    'getLogEntries'  => 'LOG_ACCESS',
    'getLogDetails'  => 'LOG_ACCESS',  // Lazy loading für einzelne Log-Details
    'getLogTypes'    => 'LOG_ACCESS',
    'getLogUsers'    => 'LOG_ACCESS',
    'getLogStats'    => 'LOG_ACCESS',
    'getAuthorities' => 'LOG_ACCESS',  // Available to all users with LOG_ACCESS
    'clearLogs'      => 'SYSTEM_ADMIN',
];

$required_permission = $permissions_map[$action] ?? 'ACTION_NOT_DEFINED';
$has_permission = false;

if ($action === '') { http_response_code(400); echo json_encode(["error" => "No action specified."]); exit(); }
if ($required_permission === 'ACTION_NOT_DEFINED') { http_response_code(404); echo json_encode(['error' => 'Invalid action specified.']); exit(); }

// Load permission helper
require_once __DIR__ . '/../../utils/permission_helper.php';

// Check permission levels 
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} elseif (hasPermission($userPermissions, 'SYSTEM_ADMIN')) {
    $has_permission = true;
} elseif ($required_permission && hasPermission($userPermissions, $required_permission)) {
    $has_permission = true;
}

// --- Execute Action or Deny ---
if ($has_permission) {
    // Check if user is SYSTEM_ADMIN (both permission AND authority must be SYSTEM_ADMIN)
    $isSystemAdmin = ($authority === 'SYSTEM_ADMIN') && hasPermission($userPermissions, 'SYSTEM_ADMIN');

    // Determine the effective authority ID to use for queries
    $effectiveAuthorityId = $authorityId;

    if ($isSystemAdmin) {
        // SYSTEM_ADMIN can optionally specify an authority_id parameter to filter
        $requestedAuthorityId = null;

        if ($request_method === 'POST') {
            $data = getJsonRequestData();
            $requestedAuthorityId = isset($data['authorityId']) ? filter_var($data['authorityId'], FILTER_VALIDATE_INT) : null;
        } else {
            $requestedAuthorityId = isset($_GET['authorityId']) ? filter_var($_GET['authorityId'], FILTER_VALIDATE_INT) : null;
        }

        // If a valid authority ID was requested, use it; otherwise null means "all authorities"
        if ($requestedAuthorityId !== null && $requestedAuthorityId !== false) {
            $effectiveAuthorityId = $requestedAuthorityId;
        } else {
            // For SYSTEM_ADMIN without filter, null means "all authorities"
            $effectiveAuthorityId = null;
        }
    }

    switch ($action) {
        case 'getLogEntries':      if ($request_method === 'GET' || $request_method === 'POST') getLogEntries($pdo, $effectiveAuthorityId); else MethodNotAllowed(); break;
        case 'getLogDetails':      if ($request_method === 'GET') getLogDetails($pdo, $effectiveAuthorityId); else MethodNotAllowed(); break;
        case 'getLogTypes':        if ($request_method === 'GET') getLogTypes($pdo, $effectiveAuthorityId); else MethodNotAllowed(); break;
        case 'getLogUsers':        if ($request_method === 'GET') getLogUsers($pdo, $effectiveAuthorityId); else MethodNotAllowed(); break;
        case 'getLogStats':        if ($request_method === 'GET') getLogStats($pdo, $effectiveAuthorityId); else MethodNotAllowed(); break;
        case 'getAuthorities':     if ($request_method === 'GET') getAuthorities($pdo, $isSystemAdmin); else MethodNotAllowed(); break;
        case 'clearLogs':          if ($request_method === 'POST') clearLogs($pdo, $requestingUserId, $effectiveAuthorityId); else MethodNotAllowed(); break;
        default: http_response_code(500); echo json_encode(['error' => 'Action routing error or action not found.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();





// --- Function Implementations ---

/**
 * Gets log entries with optional filtering
 */
function getLogEntries(PDO $pdo, ?int $authorityId): void {
    try {
        // Get data from JSON if POST, or from GET params
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = getJsonRequestData();
            if (!$data) return;

            $startDate = $data['startDate'] ?? null;
            $endDate = $data['endDate'] ?? null;
            $userId = isset($data['userId']) ? filter_var($data['userId'], FILTER_VALIDATE_INT) : null;
            $logType = $data['logType'] ?? null;
            $searchTerm = $data['searchTerm'] ?? null;
            $limit = isset($data['limit']) ? filter_var($data['limit'], FILTER_VALIDATE_INT, ['options' => ['default' => 100, 'min_range' => 1, 'max_range' => 1000]]) : 100;
            $offset = isset($data['offset']) ? filter_var($data['offset'], FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 0]]) : 0;
        } else {
            $startDate = $_GET['startDate'] ?? null;
            $endDate = $_GET['endDate'] ?? null;
            $userId = isset($_GET['userId']) ? filter_var($_GET['userId'], FILTER_VALIDATE_INT) : null;
            $logType = $_GET['logType'] ?? null;
            $searchTerm = $_GET['searchTerm'] ?? null;
            $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT, ['options' => ['default' => 100, 'min_range' => 1, 'max_range' => 1000]]) : 100;
            $offset = isset($_GET['offset']) ? filter_var($_GET['offset'], FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 0]]) : 0;
        }

        // Build query with WHERE conditions
        $sql = "SELECT l.*, u.username
                FROM kdd_database_logs l
                LEFT JOIN kdd_users u ON l.user_id = u.id";
        $params = [];
        $whereConditions = [];

        // Add authority filter only if authorityId is not null (SYSTEM_ADMIN can see all)
        if ($authorityId !== null) {
            $whereConditions[] = "l.authority_id = :authority_id";
            $params[':authority_id'] = $authorityId;
        }

        if ($startDate) {
            $whereConditions[] = "l.timestamp >= :start_date";
            $params[':start_date'] = $startDate;
        }

        if ($endDate) {
            $whereConditions[] = "l.timestamp <= :end_date";
            $params[':end_date'] = $endDate . ' 23:59:59';
        }

        if ($userId) {
            $whereConditions[] = "l.user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        if ($logType) {
            $whereConditions[] = "l.log_type = :log_type";
            $params[':log_type'] = $logType;
        }

        if ($searchTerm) {
            $whereConditions[] = "(l.message LIKE :search_term OR l.table_name LIKE :search_term OR l.changed_data LIKE :search_term)";
            $params[':search_term'] = "%$searchTerm%";
        }

        $sql .= !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";
        
        // Add count query for pagination
        $countSql = str_replace("SELECT l.*, u.username", "SELECT COUNT(*) as total", $sql);
        $stmtCount = $pdo->prepare($countSql);
        $stmtCount->execute($params);
        $totalCount = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Add ORDER BY and LIMIT to main query
        $sql .= " ORDER BY l.timestamp DESC LIMIT :offset, :limit";
        $params[':offset'] = $offset;
        $params[':limit'] = $limit;
        
        $stmt = $pdo->prepare($sql);
        
        // PDO requires bindValue for limits
        foreach ($params as $key => $value) {
            $paramType = PDO::PARAM_STR;
            if (is_int($value)) {
                $paramType = PDO::PARAM_INT;
            }
            $stmt->bindValue($key, $value, $paramType);
        }
        
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process logs to format data
        foreach ($logs as &$log) {
            if (!empty($log['changed_data'])) {
                $log['changed_data'] = json_decode($log['changed_data'], true);
            }

            // Dekodiere JSON-Werte für old_value und new_value
            if (!empty($log['old_value'])) {
                $decoded = json_decode($log['old_value'], true);
                if ($decoded !== null && json_last_error() === JSON_ERROR_NONE) {
                    $log['old_value'] = $decoded;
                }
            }

            if (!empty($log['new_value'])) {
                $decoded = json_decode($log['new_value'], true);
                if ($decoded !== null && json_last_error() === JSON_ERROR_NONE) {
                    $log['new_value'] = $decoded;
                }
            }
        }
        
        http_response_code(200);
        echo json_encode([
            'logs' => $logs,
            'total' => $totalCount,
            'limit' => $limit,
            'offset' => $offset
        ]);
        
    } catch (\PDOException $e) {
        error_log("DB error in getLogEntries: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve log entries: " . $e->getMessage()]);
    }
}

/**
 * Gets a single log entry with full details (LAZY LOADING)
 * Optimiert: Lädt nur 1 Log-Eintrag statt alle
 */
function getLogDetails(PDO $pdo, ?int $authorityId): void {
    try {
        $logId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

        if (!$logId) {
            http_response_code(400);
            echo json_encode(["error" => "Log ID is required"]);
            return;
        }

        // Query für einzelnen Log mit User-Details
        $sql = "SELECT l.*, u.username
                FROM kdd_database_logs l
                LEFT JOIN kdd_users u ON l.user_id = u.id
                WHERE l.id = ?";

        $params = [$logId];

        // Authority-Filter für normale User
        if ($authorityId !== null) {
            $sql .= " AND l.authority_id = ?";
            $params[] = $authorityId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $log = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$log) {
            http_response_code(404);
            echo json_encode(["error" => "Log entry not found"]);
            return;
        }

        // Dekodiere JSON-Werte wenn vorhanden
        if (!empty($log['old_value'])) {
            $decoded = json_decode($log['old_value'], true);
            if ($decoded !== null) {
                $log['old_value'] = $decoded;
            }
        }

        if (!empty($log['new_value'])) {
            $decoded = json_decode($log['new_value'], true);
            if ($decoded !== null) {
                $log['new_value'] = $decoded;
            }
        }

        http_response_code(200);
        echo json_encode($log);

    } catch (\PDOException $e) {
        error_log("DB error in getLogDetails: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve log details: " . $e->getMessage()]);
    }
}

/**
 * Gets all log types for filtering
 */
function getLogTypes(PDO $pdo, ?int $authorityId): void {
    try {
        if ($authorityId !== null) {
            $sql = "SELECT DISTINCT action FROM kdd_database_logs WHERE authority_id = ? ORDER BY action";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$authorityId]);
        } else {
            $sql = "SELECT DISTINCT action FROM kdd_database_logs ORDER BY action";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
        }
        $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        http_response_code(200);
        echo json_encode($types);
        
    } catch (\PDOException $e) {
        error_log("DB error in getLogTypes: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve log types."]);
    }
}

/**
 * Gets users who have log entries
 */
function getLogUsers(PDO $pdo, ?int $authorityId): void {
    try {
        if ($authorityId !== null) {
            $sql = "SELECT DISTINCT u.id, u.username
                    FROM kdd_database_logs l
                    JOIN kdd_users u ON l.user_id = u.id
                    WHERE l.authority_id = ?
                    ORDER BY u.username";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$authorityId]);
        } else {
            $sql = "SELECT DISTINCT u.id, u.username
                    FROM kdd_database_logs l
                    JOIN kdd_users u ON l.user_id = u.id
                    ORDER BY u.username";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
        }
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($users);
        
    } catch (\PDOException $e) {
        error_log("DB error in getLogUsers: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve log users."]);
    }
}

/**
 * Clears logs based on criteria (requires SYSTEM_ADMIN)
 */
function clearLogs(PDO $pdo, int $requestingUserId, ?int $authorityId): void {
    try {
        $data = getJsonRequestData();
        if (!$data) return;

        $beforeDate = $data['beforeDate'] ?? null;
        $logType = $data['logType'] ?? null;

        if (!$beforeDate && !$logType) {
            http_response_code(400);
            echo json_encode(["error" => "At least one filter criteria (beforeDate or logType) is required."]);
            return;
        }

        $sql = "DELETE FROM kdd_database_logs WHERE 1=1";
        $params = [];

        if ($authorityId !== null) {
            $sql .= " AND authority_id = ?";
            $params[] = $authorityId;
        }
        
        if ($beforeDate) {
            $sql .= " AND timestamp < ?";
            $params[] = $beforeDate;
        }
        
        if ($logType) {
            $sql .= " AND log_type = ?";
            $params[] = $logType;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $affectedRows = $stmt->rowCount();
        
        // Log this admin action
        $meta = [
            'beforeDate' => $beforeDate,
            'logType' => $logType,
            'affected_rows' => $affectedRows
        ];
        
        $metaJson = json_encode($meta);
        $logSql = "INSERT INTO kdd_database_logs (authority_id, user_id, log_type, message, changed_data) 
                  VALUES (?, ?, 'ADMIN_ACTION', 'Cleared log entries', ?)";
        $logStmt = $pdo->prepare($logSql);
        $logStmt->execute([$authorityId, $requestingUserId, $metaJson]);
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Successfully cleared $affectedRows log entries.",
            "affected_rows" => $affectedRows
        ]);
        
    } catch (\PDOException $e) {
        error_log("DB error in clearLogs: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not clear logs: " . $e->getMessage()]);
    }
}

/**
 * Gets statistical information about logs directly via SQL
 */
function getLogStats(PDO $pdo, ?int $authorityId): void {
    try {
        // Get filter parameters
        $startDate = $_GET['startDate'] ?? null;
        $endDate = $_GET['endDate'] ?? null;
        $searchTerm = $_GET['searchTerm'] ?? null;

        // Base condition - only add authority filter if not null (SYSTEM_ADMIN sees all)
        $whereConditions = [];
        $params = [];

        if ($authorityId !== null) {
            $whereConditions[] = "l.authority_id = :authority_id";
            $params[':authority_id'] = $authorityId;
        }

        // Apply filters if provided
        if ($startDate) {
            $whereConditions[] = "l.timestamp >= :start_date";
            $params[':start_date'] = $startDate;
        }

        if ($endDate) {
            $whereConditions[] = "l.timestamp <= :end_date";
            $params[':end_date'] = $endDate . ' 23:59:59';
        }

        if ($searchTerm) {
            $whereConditions[] = "(l.message LIKE :search_term OR l.table_name LIKE :search_term)";
            $params[':search_term'] = "%$searchTerm%";
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
        
        // 1. Total log count
        $sqlCount = "SELECT COUNT(*) as total FROM kdd_database_logs l $whereClause";
        $stmtCount = $pdo->prepare($sqlCount);
        $stmtCount->execute($params);
        $totalLogs = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
        
        // 2. Top users by log count
        $sqlUsers = "SELECT l.user_id, u.username, COUNT(*) as action_count 
                    FROM kdd_database_logs l
                    LEFT JOIN kdd_users u ON l.user_id = u.id
                    $whereClause
                    GROUP BY l.user_id, u.username
                    ORDER BY action_count DESC
                    LIMIT 20";
        $stmtUsers = $pdo->prepare($sqlUsers);
        $stmtUsers->execute($params);
        $topUsers = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
        
        // 3. Action types by count
        $sqlActions = "SELECT l.action, COUNT(*) as count 
                      FROM kdd_database_logs l
                      $whereClause
                      GROUP BY l.action
                      ORDER BY count DESC";
        $stmtActions = $pdo->prepare($sqlActions);
        $stmtActions->execute($params);
        $actionTypes = $stmtActions->fetchAll(PDO::FETCH_ASSOC);
        
        // 4. Top tables by count
        $sqlTables = "SELECT l.table_name, COUNT(*) as count 
                     FROM kdd_database_logs l
                     $whereClause
                     GROUP BY l.table_name
                     ORDER BY count DESC
                     LIMIT 30";
        $stmtTables = $pdo->prepare($sqlTables);
        $stmtTables->execute($params);
        $topTables = $stmtTables->fetchAll(PDO::FETCH_ASSOC);
        
        // 5. Recent activity over time (last 30 days)
        $activityConditions = $whereConditions; // Copy existing conditions
        $activityConditions[] = "l.timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $activityWhere = !empty($activityConditions) ? "WHERE " . implode(" AND ", $activityConditions) : "WHERE l.timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

        $sqlActivity = "SELECT DATE(l.timestamp) as date, COUNT(*) as count
                       FROM kdd_database_logs l
                       $activityWhere
                       GROUP BY DATE(l.timestamp)
                       ORDER BY date DESC";
        $stmtActivity = $pdo->prepare($sqlActivity);
        $stmtActivity->execute($params);
        $recentActivity = $stmtActivity->fetchAll(PDO::FETCH_ASSOC);
        
        // Combine all statistics
        $stats = [
            'totalLogs' => $totalLogs,
            'topUsers' => $topUsers,
            'actionTypes' => $actionTypes,
            'topTables' => $topTables,
            'recentActivity' => $recentActivity
        ];
        
        // Also get distinct action types and table names for filters
        if ($authorityId !== null) {
            $sqlActionTypes = "SELECT DISTINCT action FROM kdd_database_logs WHERE authority_id = ? ORDER BY action";
            $stmtActionTypes = $pdo->prepare($sqlActionTypes);
            $stmtActionTypes->execute([$authorityId]);

            $sqlTableNames = "SELECT DISTINCT table_name FROM kdd_database_logs WHERE authority_id = ? AND table_name IS NOT NULL ORDER BY table_name";
            $stmtTableNames = $pdo->prepare($sqlTableNames);
            $stmtTableNames->execute([$authorityId]);
        } else {
            $sqlActionTypes = "SELECT DISTINCT action FROM kdd_database_logs ORDER BY action";
            $stmtActionTypes = $pdo->prepare($sqlActionTypes);
            $stmtActionTypes->execute();

            $sqlTableNames = "SELECT DISTINCT table_name FROM kdd_database_logs WHERE table_name IS NOT NULL ORDER BY table_name";
            $stmtTableNames = $pdo->prepare($sqlTableNames);
            $stmtTableNames->execute();
        }

        $actionTypeOptions = $stmtActionTypes->fetchAll(PDO::FETCH_COLUMN);
        $tableNameOptions = $stmtTableNames->fetchAll(PDO::FETCH_COLUMN);
        
        // Return the data
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'actionTypes' => $actionTypeOptions,
            'tableNames' => $tableNameOptions
        ]);
        
    } catch (\PDOException $e) {
        error_log("DB error in getLogStats: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve log statistics: " . $e->getMessage()]);
    }
}

/**
 * Gets all authorities (only for SYSTEM_ADMIN users)
 */
function getAuthorities(PDO $pdo, bool $isSystemAdmin): void {
    try {
        // Only SYSTEM_ADMIN can get the full list of authorities
        if (!$isSystemAdmin) {
            http_response_code(403);
            echo json_encode(["error" => "Only SYSTEM_ADMIN can access authorities list."]);
            return;
        }

        $sql = "SELECT id, name FROM kdd_authorities WHERE active = 1 ORDER BY name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $authorities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($authorities);

    } catch (\PDOException $e) {
        error_log("DB error in getAuthorities: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve authorities."]);
    }
}
?> 