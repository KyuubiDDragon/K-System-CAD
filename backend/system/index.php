<?php
/**
 * Backend Endpoint: system/index.php
 * Handles system health monitoring, logs, and user activity tracking for dashboard widgets.
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
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority) || $authorityId === null) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}

require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions
// System actions typically require admin permissions
$permissions_map = [
    'getHealth'       => ['module' => 'settings', 'action' => 'admin'],
    'getRecentLogs'   => ['module' => 'settings', 'action' => 'admin'],
    'getUserActivity' => ['module' => 'settings', 'action' => 'admin'],
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
    $has_permission = true;
}

// --- Execute Action or Deny ---
if ($has_permission) {
    switch ($action) {
        case 'getHealth':       if ($request_method === 'GET') getHealth($pdo, $authorityId); else MethodNotAllowed(); break;
        case 'getRecentLogs':   if ($request_method === 'GET') getRecentLogs($pdo, $authorityId); else MethodNotAllowed(); break;
        case 'getUserActivity': if ($request_method === 'GET') getUserActivity($pdo, $authorityId); else MethodNotAllowed(); break;
        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



/**
 * Get system health metrics for dashboard widget
 * Returns: status, cpu, memory, disk, uptime
 */
function getHealth(PDO $pdo, int $authorityId): void {
    try {
        // Get basic server stats (these are approximations - adjust based on your server capabilities)
        $status = 'healthy'; // Default status

        // CPU Load (Linux only)
        $cpuLoad = 0;
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $cpuLoad = round($load[0] * 100 / 4, 1); // Assuming 4 cores, adjust as needed
        }

        // Memory usage
        $memoryUsage = 0;
        if (function_exists('memory_get_usage')) {
            $memoryUsed = memory_get_usage(true);
            $memoryLimit = ini_get('memory_limit');
            if ($memoryLimit !== '-1') {
                $memoryLimitBytes = convertToBytes($memoryLimit);
                $memoryUsage = round(($memoryUsed / $memoryLimitBytes) * 100, 1);
            }
        }

        // Disk usage (of current partition)
        $diskUsage = 0;
        if (function_exists('disk_free_space') && function_exists('disk_total_space')) {
            $free = @disk_free_space(__DIR__);
            $total = @disk_total_space(__DIR__);
            if ($free !== false && $total !== false && $total > 0) {
                $diskUsage = round((($total - $free) / $total) * 100, 1);
            }
        }

        // System uptime (Linux only)
        $uptime = null;
        if (file_exists('/proc/uptime')) {
            $uptimeData = @file_get_contents('/proc/uptime');
            if ($uptimeData) {
                $uptimeSeconds = (int)explode(' ', $uptimeData)[0];
                $days = floor($uptimeSeconds / 86400);
                $hours = floor(($uptimeSeconds % 86400) / 3600);
                $uptime = "{$days}d {$hours}h";
            }
        }

        // Determine overall status
        if ($cpuLoad > 80 || $memoryUsage > 90 || $diskUsage > 85) {
            $status = 'warning';
        }
        if ($cpuLoad > 95 || $memoryUsage > 98 || $diskUsage > 95) {
            $status = 'critical';
        }

        http_response_code(200);
        echo json_encode([
            'status' => $status,
            'cpu' => $cpuLoad,
            'memory' => $memoryUsage,
            'disk' => $diskUsage,
            'uptime' => $uptime ?? 'N/A'
        ]);
    } catch (\Exception $e) {
        error_log("Error in getHealth: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve system health."]);
    }
}

/**
 * Get recent logs for dashboard widget
 * Returns: List of recent system logs (limited to 10)
 */
function getRecentLogs(PDO $pdo, int $authorityId): void {
    try {
        // Check if logs table exists
        $sql = "SELECT
                    l.id,
                    l.level,
                    l.message,
                    l.created_at as timestamp,
                    u.username as user
                FROM kdd_logs l
                LEFT JOIN kdd_users u ON l.user_id = u.id
                WHERE l.authority_id = ?
                ORDER BY l.created_at DESC
                LIMIT 10";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($logs);
    } catch (\PDOException $e) {
        // If table doesn't exist, return empty array
        if ($e->getCode() == '42S02') { // Table doesn't exist
            error_log("Logs table doesn't exist yet: " . $e->getMessage());
            http_response_code(200);
            echo json_encode([]);
        } else {
            error_log("Database error in getRecentLogs: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["error" => "Could not retrieve logs."]);
        }
    }
}

/**
 * Get user activity statistics for dashboard widget
 * Returns: stats (logins, active users, new users) and recent activities
 */
function getUserActivity(PDO $pdo, int $authorityId): void {
    try {
        // Stats: Total logins today
        $today = date('Y-m-d 00:00:00');
        $sqlLoginsToday = "SELECT COUNT(*) FROM kdd_users
                           WHERE authority_id = ? AND last_login >= ?";
        $stmtLoginsToday = $pdo->prepare($sqlLoginsToday);
        $stmtLoginsToday->execute([$authorityId, $today]);
        $loginsToday = (int)$stmtLoginsToday->fetchColumn();

        // Stats: Currently active users (logged in within last 5 minutes)
        $fiveMinutesAgo = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        $sqlActive = "SELECT COUNT(*) FROM kdd_users
                      WHERE authority_id = ? AND last_interact >= ?";
        $stmtActive = $pdo->prepare($sqlActive);
        $stmtActive->execute([$authorityId, $fiveMinutesAgo]);
        $activeUsers = (int)$stmtActive->fetchColumn();

        // Stats: New users this month
        $firstDayOfMonth = date('Y-m-01 00:00:00');
        $sqlNewUsers = "SELECT COUNT(*) FROM kdd_users
                        WHERE authority_id = ? AND created_at >= ?";
        $stmtNewUsers = $pdo->prepare($sqlNewUsers);
        $stmtNewUsers->execute([$authorityId, $firstDayOfMonth]);
        $newUsers = (int)$stmtNewUsers->fetchColumn();

        // Recent activities: Last 10 user logins
        $sqlActivities = "SELECT
                            u.id,
                            u.username,
                            u.last_login as timestamp,
                            'login' as type
                          FROM kdd_users u
                          WHERE u.authority_id = ?
                          AND u.last_login IS NOT NULL
                          ORDER BY u.last_login DESC
                          LIMIT 10";
        $stmtActivities = $pdo->prepare($sqlActivities);
        $stmtActivities->execute([$authorityId]);
        $activities = $stmtActivities->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'stats' => [
                'logins' => $loginsToday,
                'active' => $activeUsers,
                'newUsers' => $newUsers
            ],
            'activities' => $activities
        ]);
    } catch (\PDOException $e) {
        error_log("Database error in getUserActivity: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve user activity."]);
    }
}

// Helper function to convert PHP memory_limit string to bytes
function convertToBytes(string $value): int {
    $value = trim($value);
    $last = strtolower($value[strlen($value)-1]);
    $value = (int)$value;

    switch($last) {
        case 'g': $value *= 1024;
        case 'm': $value *= 1024;
        case 'k': $value *= 1024;
    }

    return $value;
}

function MethodNotAllowed(): void {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed for this action.']);
}
