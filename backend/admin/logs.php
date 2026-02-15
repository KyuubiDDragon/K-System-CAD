<?php
// Log Management System Backend

// Debugging - Log errors to file
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_log("Starting logs.php endpoint");

// CORS Headers - Spezifischer für die Anwendung
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173'); // Ändere dies zu deiner Frontend-URL
header('Access-Control-Allow-Credentials: true'); // Wichtig für Cookies
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database connection
require_once '../db.php';  // Korrekter Pfad relativ zu admin/logs.php

// Debug
error_log("Database connection included");

// Check authentication
try {
    require_once '../auth_check.php';
    error_log("Auth check passed");
} catch (\Throwable $e) {
    error_log("Error during authentication: " . $e->getMessage());
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized access: ' . $e->getMessage()]));
}

// Get user context from JWT
$userId = $decoded_jwt->userId ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];

error_log("User: $userId, Authority: $authorityId");

// Permission check helper - use if loaded from auth_check.php
if (!function_exists('hasPermission')) {
    require_once '../utils/permission_helper.php';
}

// Only SYSTEM_ADMIN, ALL_PERMISSIONS, or LOG_ACCESS users can access logs
$hasAccess = hasPermission($userPermissions, 'SYSTEM_ADMIN') || 
             hasPermission($userPermissions, 'ALL_PERMISSIONS') || 
             hasPermission($userPermissions, 'LOG_ACCESS');

error_log("Has Access: " . ($hasAccess ? "yes" : "no"));

if (!$hasAccess) {
    http_response_code(403);
    die(json_encode(['error' => 'Insufficient permissions to access logs']));
}

// Some operations are restricted to SYSTEM_ADMIN only
$isSystemAdmin = hasPermission($userPermissions, 'SYSTEM_ADMIN');

// Handle different actions
$action = isset($_GET['action']) ? $_GET['action'] : '';
error_log("Action: $action");

try {
    switch ($action) {
        case 'getLogs':
            getLogs($pdo, $authorityId, $isSystemAdmin);
            break;
        
        case 'getLogStats':
            getLogStats($pdo, $authorityId, $isSystemAdmin);
            break;
        
        case 'clearLogs':
            // Only SYSTEM_ADMIN can clear logs
            if (!$isSystemAdmin) {
                http_response_code(403);
                die(json_encode(['error' => 'Only system administrators can clear logs']));
            }
            clearLogs($pdo);
            break;
        
        case 'exportLogs':
            exportLogs($pdo, $authorityId, $isSystemAdmin);
            break;
            
        default:
            http_response_code(400);
            die(json_encode(['error' => 'Invalid action specified']));
    }
} catch (PDOException $e) {
    error_log("PDO Error: " . $e->getMessage());
    http_response_code(500);
    die(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    http_response_code(500);
    die(json_encode(['error' => 'Server error: ' . $e->getMessage()]));
}

/**
 * Get paginated and filtered logs
 */
function getLogs($pdo, $authorityId, $isSystemAdmin) {
    error_log("getLogs called");
    
    try {
        // Parse query parameters
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 50;
        $offset = ($page - 1) * $limit;
        
        // Build filter conditions
        $whereConditions = [];
        $params = [];
        
        // Filter by date range
        if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
            $whereConditions[] = "timestamp >= :start_date";
            $params[':start_date'] = $_GET['start_date'] . ' 00:00:00';
        }
        
        if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
            $whereConditions[] = "timestamp <= :end_date";
            $params[':end_date'] = $_GET['end_date'] . ' 23:59:59';
        }
        
        // Filter by action type
        if (isset($_GET['action_type']) && !empty($_GET['action_type'])) {
            $whereConditions[] = "action = :action_type";
            $params[':action_type'] = $_GET['action_type'];
        }
        
        // Filter by table name
        if (isset($_GET['table_name']) && !empty($_GET['table_name'])) {
            $whereConditions[] = "table_name = :table_name";
            $params[':table_name'] = $_GET['table_name'];
        }
        
        // Filter by user id
        if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
            $whereConditions[] = "user_id = :user_id";
            $params[':user_id'] = $_GET['user_id'];
        }
        
        // Filter by search term (searches across multiple columns)
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $searchTerm = '%' . $_GET['search'] . '%';
            $whereConditions[] = "(table_name LIKE :search OR column_name LIKE :search OR old_value LIKE :search OR new_value LIKE :search)";
            $params[':search'] = $searchTerm;
        }
        
        // Authority filter - if not System Admin, restrict to current authority
        if (!$isSystemAdmin) {
            $whereConditions[] = "authority_id = :authority_id";
            $params[':authority_id'] = $authorityId;
        } elseif (isset($_GET['authority_id']) && !empty($_GET['authority_id'])) {
            // System admin can filter by authority
            $whereConditions[] = "authority_id = :authority_id";
            $params[':authority_id'] = $_GET['authority_id'];
        }
        
        // Build the WHERE clause
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM kdd_database_logs $whereClause";
        $countStmt = $pdo->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get the logs with pagination
        $sql = "SELECT * FROM kdd_database_logs $whereClause ORDER BY timestamp DESC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Enrich logs with user information
        $logs = enrichLogsWithUserInfo($pdo, $logs);
        
        // Return response with pagination info
        $response = [
            'logs' => $logs,
            'pagination' => [
                'total' => intval($totalRecords),
                'page' => intval($page),
                'limit' => intval($limit),
                'totalPages' => ceil($totalRecords / $limit),
                'totalItems' => intval($totalRecords) // Backend-Eigenschaft könnte anders benannt sein als im Frontend
            ]
        ];
        
        error_log("getLogs response: " . json_encode(array_keys($response)));
        echo json_encode($response);
    } catch (Exception $e) {
        error_log("getLogs error: " . $e->getMessage());
        http_response_code(500);
        die(json_encode(['error' => 'Error fetching logs: ' . $e->getMessage()]));
    }
}

/**
 * Get log statistics
 */
function getLogStats($pdo, $authorityId, $isSystemAdmin) {
    error_log("getLogStats called");
    
    try {
        // Only apply authority filter for non-system admins
        $authorityFilter = !$isSystemAdmin ? "WHERE authority_id = :authority_id" : "";
        $params = [];
        
        if (!$isSystemAdmin) {
            $params[':authority_id'] = $authorityId;
        }
        
        // Most active users
        $usersSql = "
            SELECT user_id, COUNT(*) as action_count
            FROM kdd_database_logs
            " . ($authorityFilter ? $authorityFilter : "") . "
            GROUP BY user_id
            ORDER BY action_count DESC
            LIMIT 10
        ";
        
        $usersStmt = $pdo->prepare($usersSql);
        if (!$isSystemAdmin) {
            $usersStmt->bindValue(':authority_id', $authorityId);
        }
        $usersStmt->execute();
        $topUsers = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Enrich with user info
        $topUsers = array_map(function($user) use ($pdo) {
            $sql = "SELECT username FROM kdd_users WHERE id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':user_id', $user['user_id']);
            $stmt->execute();
            $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'user_id' => $user['user_id'],
                'username' => $userInfo ? $userInfo['username'] : 'Unknown',
                'action_count' => $user['action_count']
            ];
        }, $topUsers);
        
        // Action types distribution
        $actionsSql = "
            SELECT action, COUNT(*) as count
            FROM kdd_database_logs
            " . ($authorityFilter ? $authorityFilter : "") . "
            GROUP BY action
            ORDER BY count DESC
        ";
        
        $actionsStmt = $pdo->prepare($actionsSql);
        if (!$isSystemAdmin) {
            $actionsStmt->bindValue(':authority_id', $authorityId);
        }
        $actionsStmt->execute();
        $actionTypes = $actionsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Most modified tables
        $tablesSql = "
            SELECT table_name, COUNT(*) as count
            FROM kdd_database_logs
            " . ($authorityFilter ? $authorityFilter : "") . "
            GROUP BY table_name
            ORDER BY count DESC
            LIMIT 10
        ";
        
        $tablesStmt = $pdo->prepare($tablesSql);
        if (!$isSystemAdmin) {
            $tablesStmt->bindValue(':authority_id', $authorityId);
        }
        $tablesStmt->execute();
        $topTables = $tablesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Activity over time (daily count for the last 30 days)
        $activitySql = "
            SELECT DATE(timestamp) as date, COUNT(*) as count
            FROM kdd_database_logs
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            " . ($authorityFilter ? "AND authority_id = :authority_id" : "") . "
            GROUP BY DATE(timestamp)
            ORDER BY date
        ";
        
        $activityStmt = $pdo->prepare($activitySql);
        if (!$isSystemAdmin) {
            $activityStmt->bindValue(':authority_id', $authorityId);
        }
        $activityStmt->execute();
        $activityTrend = $activityStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Latest login attempts
        $loginsSql = "
            SELECT *
            FROM kdd_database_logs
            WHERE action = 'LOGIN'
            " . ($authorityFilter ? "AND authority_id = :authority_id" : "") . "
            ORDER BY timestamp DESC
            LIMIT 10
        ";
        
        $loginsStmt = $pdo->prepare($loginsSql);
        if (!$isSystemAdmin) {
            $loginsStmt->bindValue(':authority_id', $authorityId);
        }
        $loginsStmt->execute();
        $recentLogins = $loginsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Enrich login data with user info
        $recentLogins = enrichLogsWithUserInfo($pdo, $recentLogins);
        
        // Return combined stats with default empty values when needed
        $response = [
            'topUsers' => $topUsers ?: [],
            'actionTypes' => $actionTypes ?: [],
            'topTables' => $topTables ?: [],
            'activityTrend' => $activityTrend ?: [],
            'recentLogins' => $recentLogins ?: [],
            'totalLogs' => getTotalLogCount($pdo, $authorityId, $isSystemAdmin)
        ];
        
        error_log("getLogStats response: " . json_encode(array_keys($response)));
        echo json_encode($response);
    } catch (Exception $e) {
        error_log("getLogStats error: " . $e->getMessage());
        http_response_code(500);
        die(json_encode(['error' => 'Error fetching log stats: ' . $e->getMessage()]));
    }
}

/**
 * Clear logs (SYSTEM_ADMIN only)
 * This is a potentially dangerous operation!
 */
function clearLogs($pdo) {
    // Require confirmation parameter
    if (!isset($_POST['confirm']) || $_POST['confirm'] !== 'yes') {
        http_response_code(400);
        die(json_encode(['error' => 'Confirmation required to clear logs']));
    }
    
    // Option to clear logs older than a specific date
    if (isset($_POST['older_than']) && !empty($_POST['older_than'])) {
        $sql = "DELETE FROM kdd_database_logs WHERE timestamp < :date";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':date', $_POST['older_than']);
        $stmt->execute();
        
        $affectedRows = $stmt->rowCount();
        echo json_encode(['success' => true, 'message' => "Cleared $affectedRows logs older than " . $_POST['older_than']]);
    } else {
        // Dangerous! This clears ALL logs
        $stmt = $pdo->prepare("TRUNCATE TABLE kdd_database_logs");
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'All logs have been cleared']);
    }
    
    // Log this clearance operation itself
    logDatabaseChange('kdd_database_logs', 0, $_SESSION['user_id'], 'bulk_delete', 'Multiple records', 'Deleted', $pdo);
}

/**
 * Export logs in CSV format
 */
function exportLogs($pdo, $authorityId, $isSystemAdmin) {
    // Similar filtering as getLogs, but without pagination
    $whereConditions = [];
    $params = [];
    
    // Apply filters (same as getLogs)
    if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
        $whereConditions[] = "timestamp >= :start_date";
        $params[':start_date'] = $_GET['start_date'] . ' 00:00:00';
    }
    
    if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
        $whereConditions[] = "timestamp <= :end_date";
        $params[':end_date'] = $_GET['end_date'] . ' 23:59:59';
    }
    
    if (isset($_GET['action_type']) && !empty($_GET['action_type'])) {
        $whereConditions[] = "action = :action_type";
        $params[':action_type'] = $_GET['action_type'];
    }
    
    if (isset($_GET['table_name']) && !empty($_GET['table_name'])) {
        $whereConditions[] = "table_name = :table_name";
        $params[':table_name'] = $_GET['table_name'];
    }
    
    if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
        $whereConditions[] = "user_id = :user_id";
        $params[':user_id'] = $_GET['user_id'];
    }
    
    if (!$isSystemAdmin) {
        $whereConditions[] = "authority_id = :authority_id";
        $params[':authority_id'] = $authorityId;
    } elseif (isset($_GET['authority_id']) && !empty($_GET['authority_id'])) {
        $whereConditions[] = "authority_id = :authority_id";
        $params[':authority_id'] = $_GET['authority_id'];
    }
    
    // Build the WHERE clause
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get the logs
    $sql = "SELECT * FROM kdd_database_logs $whereClause ORDER BY timestamp DESC";
    $stmt = $pdo->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="logs_export_' . date('Y-m-d') . '.csv"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Output CSV header row
    fputcsv($output, array_keys($logs[0]));
    
    // Output each log row
    foreach ($logs as $log) {
        fputcsv($output, $log);
    }
    
    // Close output stream
    fclose($output);
    exit;
}

/**
 * Helper function to get the total count of logs
 */
function getTotalLogCount($pdo, $authorityId, $isSystemAdmin) {
    $sql = "SELECT COUNT(*) as count FROM kdd_database_logs";
    
    if (!$isSystemAdmin) {
        $sql .= " WHERE authority_id = :authority_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':authority_id', $authorityId);
    } else {
        $stmt = $pdo->prepare($sql);
    }
    
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

/**
 * Helper function to add user information to log entries
 */
function enrichLogsWithUserInfo($pdo, $logs) {
    if (empty($logs)) {
        return [];
    }
    
    // Collect all user IDs
    $userIds = array_unique(array_column($logs, 'user_id'));
    
    // Fetch user info in a single query
    $placeholders = rtrim(str_repeat('?,', count($userIds)), ',');
    $sql = "SELECT id, username FROM kdd_users WHERE id IN ($placeholders)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($userIds);
    
    $users = [];
    while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $users[$user['id']] = $user['username'];
    }
    
    // Add user info to logs
    foreach ($logs as &$log) {
        $log['username'] = isset($users[$log['user_id']]) ? $users[$log['user_id']] : 'Unknown';
    }
    
    return $logs;
}

/**
 * Helper function to log database changes
 */
function logDatabaseChange($table, $recordId, $userId, $column, $oldValue, $newValue, $pdo) {
    $sql = "INSERT INTO kdd_database_logs (action, table_name, record_id, user_id, column_name, old_value, new_value, timestamp) 
            VALUES (:action, :table_name, :record_id, :user_id, :column_name, :old_value, :new_value, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':action', 'UPDATE');
    $stmt->bindValue(':table_name', $table);
    $stmt->bindValue(':record_id', $recordId);
    $stmt->bindValue(':user_id', $userId);
    $stmt->bindValue(':column_name', $column);
    $stmt->bindValue(':old_value', $oldValue);
    $stmt->bindValue(':new_value', $newValue);
    
    $stmt->execute();
} 