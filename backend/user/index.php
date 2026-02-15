<?php

/**
 * Backend Endpoint: user/index.php
 * Handles various actions related to user data, activity, messages, etc.
 * Uses Cookie-based Authentication and PDO database connection.
 */

declare(strict_types=1); // Enable strict types for better code quality

// --- Core Initialisation ---
require_once __DIR__ . '/../bootstrap.php';

// --- Dependencies & DB Connection ---
try {
    // This defines the $pdo variable globally in this script's scope
    require_once __DIR__ . '/../db.php';
} catch (Exception $e) {
    // db.php should handle its own critical connection errors and exit
    // This catch is an additional safety net.
    http_response_code(500);
    echo json_encode(["error" => "Database connection could not be established."]);
    exit();
}
// Ensure logging functions are available if needed by actions
require_once __DIR__ . '/../logging/logging.php';

// --- Authentication & User Context ---

// 6. Authentication via Cookie Check
try {
    // This script validates the 'auth_token' cookie.
    // If valid, it defines $decoded_jwt containing the token payload.
    // If invalid or missing, it outputs a 401 error and exits.
    require_once __DIR__ . '/../auth_check.php';
} catch (\Throwable $e) {
    // Catch potential errors during inclusion or if auth_check throws unexpectedly
    error_log("Critical error during authentication check include: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Authentication system error."]);
    exit();
}
// If execution continues, user is authenticated.

// 7. Extract Context from Decoded Token and Validate
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$userRoles = $decoded_jwt->roles ?? [];
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

// Basic validation of extracted context
if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority)) {
    http_response_code(400); // Bad Request - Token payload structure is wrong
    echo json_encode(["error" => "Invalid token payload structure."]);
    exit();
}

require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Load permission helper
require_once __DIR__ . '/../utils/permission_helper.php';

// --- Authorization & Action Routing ---

// 8. Define Action and Permissions Mapping
// Trim the action value to remove potential leading/trailing whitespace
$action = trim($_GET['action'] ?? '');

// Actions that don't require feature access (available to all authenticated users)
$publicActions = ['getUserOverview', 'updateLastInteract'];

// Feature-Zugriff prüfen (skip for public actions)
if (!in_array($action, $publicActions) && !hasFeatureAccess($pdo, $authorityId, 'default')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to global features."]); exit();
}

// Map actions to the *single* specific permission required.
// Use 'CAN_LOGIN' for basic authenticated access.
// Use 'null' ONLY if absolutely no specific permission is needed beyond being logged in.
// Use specific permission strings (e.g., 'EDIT_SETTINGS') otherwise.
// Ensure these permission strings match exactly what's stored in the JWT payload.
$permissions_map = [
    // Actions requiring only basic login (null = no specific permission needed beyond authentication)
    // MIGRATION NOTE: Changed from 'CAN_LOGIN' to null for bitmask compatibility
    'getUserOverview'     => null,  // Basic user data - only login required
    'updateLastInteract'  => null,  // User activity tracking - only login required
    'getLast5Messages'    => null,
    'getOwnReportCount'   => null,
    'getOwnVacations'     => null,
    'stopVacation'        => null, // Modifying own data often just needs login
    'addVacation'         => null, // Modifying own data often just needs login

    // Actions requiring specific permissions
    'getActiveUsers'      => null,   // Example: Monitor activity
    'getSuspendedUsers'   => null, // Example: View suspensions
    'getUsers'            => null,           // Example: View user list
    'setMarqueeText'      => null,         // Example: Change dashboard text
    'getInvoiceCount'     => null,        // Example: Access billing info

    // Actions potentially requiring no specific permission (public info for logged-in users?) - Review carefully!
    'getMarqueeText'      => null, // Or should this be CAN_LOGIN?
    'getVacationsCount'   => null, // Or should this be VIEW_VACATIONS?
    'getMeetingCount'     => null, // Or should this be VIEW_CALENDAR?
    'getUsersWithEmployee' => null,  // Add this line
    'getOwnCrew'          => null,   // Get user's crew assignment
];



// 9. Perform Authorization Check
if ($action === '') {
    http_response_code(400);
    echo json_encode(["error" => "No action specified."]);
    exit();
}

// Check if action exists in permissions map
// IMPORTANT: Use array_key_exists instead of ?? because null is a valid value
if (!array_key_exists($action, $permissions_map)) {
    http_response_code(404);
    echo json_encode(['error' => 'Invalid action specified.']);
    exit();
}

$required_permission = $permissions_map[$action]; // Can be null (= only login required)
$has_permission = false;

// Check permission levels
if ($required_permission === null) {
    $has_permission = true; // Login is sufficient (already checked by auth_check.php)
} elseif (hasAllPermissions($userPermissions)) {
    $has_permission = true; // Admin override
} elseif ($required_permission && hasPermission($userPermissions, $required_permission)) {
    $has_permission = true; // Has the specific required permission
}

// 10. Execute Action or Deny Access
if ($has_permission) {
    $request_method = $_SERVER['REQUEST_METHOD'];
    $is_post_request = ($request_method === 'POST');

    // Route to the appropriate function, passing necessary context
    switch ($action) {
            // Define which HTTP method is expected for each action
        case 'getUserOverview':
            if ($request_method === 'GET') getUserOverview($pdo, $userId, $authority, $userPermissions);
            else MethodNotAllowed();
            break;
        case 'getActiveUsers':
            if ($request_method === 'GET') getActiveUsers($pdo, $authority);
            else MethodNotAllowed();
            break;
        case 'getSuspendedUsers':
            if ($request_method === 'GET') getSuspendedUsers($pdo, $authority);
            else MethodNotAllowed();
            break;
        case 'getLast5Messages':
            if ($request_method === 'GET') getLast5Messages($pdo, $userId, $authority);
            else MethodNotAllowed();
            break;
        case 'getUsers':
            if ($request_method === 'GET') getUsers($pdo, $authority);
            else MethodNotAllowed();
            break;
        case 'getMarqueeText':
            if ($request_method === 'GET') getMarqueeText($pdo, $authority);
            else MethodNotAllowed();
            break;
        case 'getVacationsCount':
            if ($request_method === 'GET') getVacationsCount($pdo, $authority);
            else MethodNotAllowed();
            break;
        case 'getOwnReportCount':
            if ($request_method === 'GET') getOwnReportCount($pdo, $userId, $authority);
            else MethodNotAllowed();
            break;
        case 'getOwnVacations':
            if ($request_method === 'GET') getOwnVacations($pdo, $userId, $authority);
            else MethodNotAllowed();
            break;
        case 'getMeetingCount':
            if ($request_method === 'GET') getMeetingCount($pdo, $authority);
            else MethodNotAllowed();
            break;
        case 'getInvoiceCount':
            if ($request_method === 'GET') getInvoiceCount($pdo, $authority);
            else MethodNotAllowed();
            break;
        case 'getUsersWithEmployee':
            if ($request_method === 'GET') getUsersWithEmployee($pdo, $authority);
            else MethodNotAllowed();
            break;
        case 'getOwnCrew':
            if ($request_method === 'GET') getOwnCrew($pdo, $userId, $authority);
            else MethodNotAllowed();
            break;

            // Actions potentially modifying data (consider POST/PUT/DELETE)
        case 'updateLastInteract':
            if ($request_method === 'GET') updateLastInteract($pdo, $userId);
            else MethodNotAllowed();
            break; // Typically GET or maybe POST
        case 'setMarqueeText':
            if ($is_post_request) setMarqueeText($pdo, $authority);
            else MethodNotAllowed();
            break; // Expects POST data
        case 'stopVacation':
            if ($is_post_request) stopVacation($pdo, $userId, $authority);
            else MethodNotAllowed();
            break; // Expects POST data
        case 'addVacation':
            if ($is_post_request) addVacation($pdo, $userId, $authority);
            else MethodNotAllowed();
            break; // Expects POST data

        default:
            // This case should ideally not be reached due to checks above
            http_response_code(500);
            echo json_encode(['error' => 'Internal Server Error: Action routing failed.']);
            break;
    }
} else {
    // Permission Denied
    http_response_code(403); // Forbidden
    echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit(); // Ensure script terminates cleanly





// --- Action Function Implementations (Refactored for PDO) ---

function getUserOverview(PDO $pdo, int $userId, string $authority, $userPermissions): void
{
    try {
        // Get authority ID and branding data from the authorities table
        $sqlAuthority = "SELECT id, logo_url, primary_color, secondary_color, app_title,
                                default_background, display_name, theme_settings
                         FROM kdd_authorities WHERE name = ?";
        $stmtAuthority = $pdo->prepare($sqlAuthority);
        $stmtAuthority->execute([$authority]);
        $authorityData = $stmtAuthority->fetch(PDO::FETCH_ASSOC);

        if (!$authorityData) {
            http_response_code(400);
            echo json_encode(["error" => "Authority not found in database."]);
            return;
        }

        $authorityId = $authorityData['id'];

        $sqlUser = "SELECT u.id, u.username, u.email, u.mail_header, u.mail_footer,
                       u.mail_header_neutral, u.mail_footer_neutral, u.signature,
                       u.last_login, u.banned, u.linked_employee, e.name,
                       e.servicenumber, u.documentView, u.authority
                FROM kdd_users u
                LEFT JOIN kdd_employee e ON u.linked_employee = e.id AND e.authority_id = ?
                WHERE u.id = ?";
        $stmtUser = $pdo->prepare($sqlUser);
        $stmtUser->execute([$authorityId, $userId]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode(["error" => "User data not found."]);
            return;
        }

        // Get roles from database
        $sqlRoles = "SELECT DISTINCT r.name as role_name
                     FROM kdd_user_roles ur
                     JOIN kdd_roles r ON ur.role_id = r.id
                     WHERE ur.user_id = ?";
        $stmtRoles = $pdo->prepare($sqlRoles);
        $stmtRoles->execute([$userId]);
        $rolesResult = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);

        $roles = [];
        foreach ($rolesResult as $row) {
            if (!empty($row['role_name'])) {
                $roles[] = $row['role_name'];
            }
        }

        // Load active features for this authority
        require_once __DIR__ . '/../utils/authority_helper.php';
        $activeFeatures = getAuthorityActiveFeatures($pdo, $authorityId);

        // Prepare authority branding data from kdd_authorities table (already fetched)
        $authorityBranding = [
            'logo_url' => $authorityData['logo_url'],
            'primary_color' => $authorityData['primary_color'],
            'secondary_color' => $authorityData['secondary_color'],
            'app_title' => $authorityData['app_title'],
            'default_background' => $authorityData['default_background'],
            'display_name' => $authorityData['display_name'],
            'theme_settings' => []
        ];

        // Parse theme_settings JSON if present
        if (!empty($authorityData['theme_settings'])) {
            $authorityBranding['theme_settings'] = json_decode($authorityData['theme_settings'], true) ?? [];
        }

        // Add authority_id to user data
        $user['authority_id'] = $authorityId;
        $user['roles'] = $roles;

        // ✅ Use permissions from JWT token (already in bitmask format)
        // JWT contains: {employee: 7, calendar: 23, system: 255}
        global $decoded_jwt;
        $user['permissions'] = (array)$decoded_jwt->permissions;

        $user['active_features'] = $activeFeatures;
        $user['authority_branding'] = $authorityBranding;

        // Get unread messages count
        $sqlMessages = "SELECT COUNT(*) AS count FROM kdd_messages m
                        LEFT JOIN kdd_message_folders sf ON m.sender_folder_id = sf.id AND sf.authority_id = ?
                        WHERE m.recipient_id = ? AND m.recipient_type = 'user' AND m.deleted_by_recipient = 0";
        $stmtMessages = $pdo->prepare($sqlMessages);
        $stmtMessages->execute([$authorityId, $userId]);
        $messagesResult = $stmtMessages->fetch(PDO::FETCH_ASSOC);
        $user['unreadMessagesCount'] = (int)$messagesResult['count'];

        // Get today's events
        $today = date('Y-m-d');
        $sqlEvents = "SELECT COUNT(*) AS count FROM kdd_calendar c
                      LEFT JOIN kdd_calendar_assigned ca ON c.id = ca.event_id
                      WHERE (ca.user_id = ? OR ca.user_id IS NULL)
                      AND DATE(c.start_date) = ?
                      AND c.is_deleted = 0";
        $stmtEvents = $pdo->prepare($sqlEvents);
        $stmtEvents->execute([$userId, $today]);
        $eventsResult = $stmtEvents->fetch(PDO::FETCH_ASSOC);
        $user['todayEventsCount'] = (int)$eventsResult['count'];

        // Get user's message groups
        $sqlGroups = "SELECT DISTINCT group_id FROM kdd_message_group_members WHERE user_id = ?";
        $stmtGroups = $pdo->prepare($sqlGroups);
        $stmtGroups->execute([$userId]);
        $groups = $stmtGroups->fetchAll(PDO::FETCH_COLUMN, 0);
        $user['message_groups'] = $groups;

        http_response_code(200);
        echo json_encode($user);
    } catch (\PDOException $e) {
        error_log("Database error in getUserOverview: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "An internal database error occurred."]);
    }
}

function getUsers(PDO $pdo, string $authority): void
{
    try {
        // Get authority ID from the authorities table
        $sqlAuthority = "SELECT id FROM kdd_authorities WHERE name = ?";
        $stmtAuthority = $pdo->prepare($sqlAuthority);
        $stmtAuthority->execute([$authority]);
        $authorityId = $stmtAuthority->fetchColumn();
        
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(["error" => "Authority not found in database."]);
            return;
        }

        // Use authority_id in WHERE clause
        $sql = "SELECT u.id, COALESCE(CONCAT('[', e.servicenumber, '] ', e.name), u.username) as username 
                FROM kdd_users u
                LEFT JOIN kdd_employee e ON u.linked_employee = e.id AND e.authority_id = ?
                WHERE u.authority = ?
                ORDER BY username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $authority]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($users);
    } catch (\PDOException $e) {
        error_log("Database error in getUsers for authority $authority: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve users."]);
    }
}

function updateLastInteract(PDO $pdo, int $userId): void
{
    try {
        // Get old timestamp for logging
        global $decoded_jwt;
        $authorityId = $decoded_jwt->authority_id ?? null;

        $getOldSql = "SELECT last_interact FROM kdd_users WHERE id = ?";
        $getOldStmt = $pdo->prepare($getOldSql);
        $getOldStmt->execute([$userId]);
        $oldTimestamp = $getOldStmt->fetchColumn();

        $sql = "UPDATE kdd_users SET last_interact = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$userId]);

        if ($success && $stmt->rowCount() > 0) {
            // Log last_interact update
            if ($authorityId) {
                $changes = [
                    ['column_name' => 'last_interact', 'old_value' => $oldTimestamp, 'new_value' => date('Y-m-d H:i:s')]
                ];
                logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_users', $userId, $userId, $changes);
            }

            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Interaction time updated."]);
        } else {
            http_response_code(200);
            echo json_encode(["success" => false, "message" => "Interaction time not updated (user may not exist or value unchanged)."]);
        }
    } catch (\PDOException $e) {
        error_log("Database error in updateLastInteract for user $userId: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to update interaction time."]);
    }
}

function getActiveUsers(PDO $pdo, string $authority): void
{
    try {
        // Get authority ID from the authorities table
        $sqlAuthority = "SELECT id FROM kdd_authorities WHERE name = ?";
        $stmtAuthority = $pdo->prepare($sqlAuthority);
        $stmtAuthority->execute([$authority]);
        $authorityId = $stmtAuthority->fetchColumn();
        
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(["error" => "Authority not found in database."]);
            return;
        }

        $sql = "SELECT u.id, CONCAT('[', e.servicenumber, '] ', e.name) AS name 
                FROM kdd_users u
                LEFT JOIN kdd_employee e ON u.linked_employee = e.id AND e.authority_id = ?
                WHERE u.last_interact >= (NOW() - INTERVAL 10 MINUTE) 
                AND u.authority = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $authority]);
        $activeUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($activeUsers);
    } catch (\PDOException $e) {
        error_log("Database error in getActiveUsers for authority $authority: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve active users."]);
    }
}

function getSuspendedUsers(PDO $pdo, string $authority): void
{
    try {
        // Get authority ID from the authorities table
        $sqlAuthority = "SELECT id FROM kdd_authorities WHERE name = ?";
        $stmtAuthority = $pdo->prepare($sqlAuthority);
        $stmtAuthority->execute([$authority]);
        $authorityId = $stmtAuthority->fetchColumn();
        
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(["error" => "Authority not found in database."]);
            return;
        }

        $sql = "SELECT u.id, CONCAT('[', e.servicenumber, '] ', e.name) AS name 
                FROM kdd_users u
                LEFT JOIN kdd_employee e ON u.linked_employee = e.id AND e.authority_id = ?
                JOIN kdd_user_roles ur ON u.id = ur.user_id
                JOIN kdd_roles r ON ur.role_id = r.id AND r.authority_id = ?
                JOIN kdd_role_permissions rp ON r.id = rp.role_id
                JOIN kdd_permissions p ON rp.permission_id = p.id
                WHERE p.name = 'IS_SUSPENDED_GROUP' 
                AND u.authority_id = ?
                GROUP BY u.id"; // Group by user ID
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $authorityId, $authorityId]);
        $suspendedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($suspendedUsers);
    } catch (\PDOException $e) {
        error_log("Database error in getSuspendedUsers for authority $authority: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve suspended users."]);
    }
}

function getLast5Messages(PDO $pdo, int $userId, string $authority): void
{
    try {
        // Get authority ID from the authorities table
        $sqlAuthority = "SELECT id FROM kdd_authorities WHERE name = ?";
        $stmtAuthority = $pdo->prepare($sqlAuthority);
        $stmtAuthority->execute([$authority]);
        $authorityId = $stmtAuthority->fetchColumn();
        
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(["error" => "Authority not found in database."]);
            return;
        }

        $sql = "SELECT m.*,
                       CASE WHEN m.sender_type = 'user' THEN CONCAT('[', e_sender.servicenumber, '] ', e_sender.name)
                            WHEN m.sender_type = 'group' THEN g_sender.name ELSE NULL END AS sender_name,
                       CASE WHEN m.recipient_type = 'user' THEN CONCAT('[', e_recipient.servicenumber, '] ', e_recipient.name)
                            WHEN m.recipient_type = 'group' THEN g.name ELSE NULL END AS recipient_name,
                       sf.name AS sender_folder_name, rf.name AS recipient_folder_name
                FROM kdd_messages m
                LEFT JOIN kdd_users s ON m.sender_id = s.id AND m.sender_type = 'user'
                LEFT JOIN kdd_employee e_sender ON s.linked_employee = e_sender.id AND e_sender.authority_id = ?
                LEFT JOIN kdd_message_groups g_sender ON m.sender_id = g_sender.id AND m.sender_type = 'group' AND g_sender.authority_id = ?
                LEFT JOIN kdd_users r ON m.recipient_id = r.id AND m.recipient_type = 'user'
                LEFT JOIN kdd_employee e_recipient ON r.linked_employee = e_recipient.id AND e_recipient.authority_id = ?
                LEFT JOIN kdd_message_groups g ON m.recipient_id = g.id AND m.recipient_type = 'group' AND g.authority_id = ?
                LEFT JOIN kdd_message_folders sf ON m.sender_folder_id = sf.id AND sf.authority_id = ?
                LEFT JOIN kdd_message_folders rf ON m.recipient_folder_id = rf.id AND rf.authority_id = ?
                WHERE ((m.recipient_id = ? AND m.recipient_type = 'user' AND m.deleted_by_recipient = 0)
                   OR (m.recipient_id IN (SELECT group_id FROM kdd_message_group_members WHERE user_id = ?)
                       AND m.recipient_type = 'group' AND m.deleted_by_recipient = 0))
                   AND m.authority_id = ?
                ORDER BY m.created_at DESC
                LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $authorityId, 
            $authorityId,
            $authorityId,
            $authorityId,
            $authorityId,
            $authorityId,
            $userId,
            $userId,
            $authorityId
        ]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($messages);
    } catch (\PDOException $e) {
        error_log("Database error in getLast5Messages for user $userId, authority $authority: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve messages."]);
    }
}

function getMarqueeText(PDO $pdo, string $authority): void
{
    try {
        // Get authority ID from the authorities table
        $sqlAuthority = "SELECT id FROM kdd_authorities WHERE name = ?";
        $stmtAuthority = $pdo->prepare($sqlAuthority);
        $stmtAuthority->execute([$authority]);
        $authorityId = $stmtAuthority->fetchColumn();
        
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(["error" => "Authority not found in database."]);
            return;
        }

        $sql = "SELECT `value` FROM kdd_global_settings 
                WHERE `key_name` = 'marquee_dashboard' AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        // fetchColumn() retrieves a single value from the next row
        $value = $stmt->fetchColumn();

        if ($value !== false) {
            http_response_code(200);
            echo json_encode(["marquee_dashboard" => $value]);
        } else {
            http_response_code(404); // Not found is more appropriate than error
            echo json_encode(["error" => "marquee_dashboard setting not found."]);
        }
    } catch (\PDOException $e) {
        error_log("Database error in getMarqueeText for authority $authority: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve marquee text."]);
    }
}

function setMarqueeText(PDO $pdo, string $authority): void
{
    $data = getJsonRequestData();
    $marquee_dashboard = $data['marquee_dashboard'] ?? $_POST['marquee_dashboard'] ?? ''; // Try JSON first, then POST

    try {
        // Get authority ID from the authorities table
        $sqlAuthority = "SELECT id FROM kdd_authorities WHERE name = ?";
        $stmtAuthority = $pdo->prepare($sqlAuthority);
        $stmtAuthority->execute([$authority]);
        $authorityId = $stmtAuthority->fetchColumn();
        
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(["error" => "Authority not found in database."]);
            return;
        }

        // First check if setting exists
        $checkSql = "SELECT COUNT(*) FROM kdd_global_settings 
                     WHERE `key_name` = 'marquee_dashboard' AND authority_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$authorityId]);
        $exists = (int)$checkStmt->fetchColumn() > 0;

        if ($exists) {
            // Get old value for logging
            $getOldSql = "SELECT id, `value` FROM kdd_global_settings WHERE `key_name` = 'marquee_dashboard' AND authority_id = ?";
            $getOldStmt = $pdo->prepare($getOldSql);
            $getOldStmt->execute([$authorityId]);
            $oldRecord = $getOldStmt->fetch(PDO::FETCH_ASSOC);

            // Update existing record
            $sql = "UPDATE kdd_global_settings SET `value` = ?
                    WHERE `key_name` = 'marquee_dashboard' AND authority_id = ?";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$marquee_dashboard, $authorityId]);

            // Log update
            if ($success && $stmt->rowCount() > 0 && $oldRecord) {
                $changes = [
                    ['column_name' => 'value', 'old_value' => $oldRecord['value'], 'new_value' => $marquee_dashboard]
                ];
                logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_global_settings', $oldRecord['id'], $userId, $changes);
            }
        } else {
            // Insert new record with authority_id
            $sql = "INSERT INTO kdd_global_settings (`key_name`, `value`, authority_id)
                    VALUES ('marquee_dashboard', ?, ?)";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$marquee_dashboard, $authorityId]);

            // Log insert
            if ($success) {
                $newId = $pdo->lastInsertId();
                $changes = [
                    ['column_name' => 'key_name', 'old_value' => null, 'new_value' => 'marquee_dashboard'],
                    ['column_name' => 'value', 'old_value' => null, 'new_value' => $marquee_dashboard]
                ];
                logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_global_settings', $newId, $userId, $changes);
            }
        }

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Marquee text updated successfully."]);
        } elseif ($success) {
            http_response_code(200); // OK, but no change made
            echo json_encode(["success" => false, "message" => "Marquee text not updated (value may be the same)."]);
        } else {
            // execute() returned false without throwing exception? Less common with ERRMODE_EXCEPTION
            http_response_code(500);
            echo json_encode(["error" => "Failed to execute marquee update."]);
        }
    } catch (\PDOException $e) {
        error_log("Database error in setMarqueeText for authority $authority: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update marquee text."]);
    }
}

function getVacationsCount(PDO $pdo, string $authority): void
{
    try {
        // Get authority ID from the authorities table
        $sqlAuthority = "SELECT id FROM kdd_authorities WHERE name = ?";
        $stmtAuthority = $pdo->prepare($sqlAuthority);
        $stmtAuthority->execute([$authority]);
        $authorityId = $stmtAuthority->fetchColumn();
        
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(["error" => "Authority not found in database."]);
            return;
        }

        $currentDate = date('Y-m-d');
        $sql = "SELECT COUNT(DISTINCT employee) FROM kdd_employee_vacation
                WHERE start <= ? AND end >= ? 
                AND is_deleted = 0 AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currentDate, $currentDate, $authorityId]);
        $vacationCount = (int) $stmt->fetchColumn();
        http_response_code(200);
        echo json_encode(["vacation_count" => $vacationCount]);
    } catch (\PDOException $e) {
        error_log("Database error in getVacationsCount for authority $authority: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve vacation count."]);
    }
}

function getMeetingCount(PDO $pdo, string $authority): void
{
    try {
        // Get authority ID from the authorities table
        $sqlAuthority = "SELECT id FROM kdd_authorities WHERE name = ?";
        $stmtAuthority = $pdo->prepare($sqlAuthority);
        $stmtAuthority->execute([$authority]);
        $authorityId = $stmtAuthority->fetchColumn();
        
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(["error" => "Authority not found in database."]);
            return;
        }

        // Fixed typo in table name
        $sql = "SELECT COUNT(id) FROM kdd_calendar
                WHERE is_deleted = 0 
                AND DATE(start_date) = CURDATE() 
                AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $meetingCount = (int) $stmt->fetchColumn();
        http_response_code(200);
        echo json_encode(["meeting_count" => $meetingCount]);
    } catch (\PDOException $e) {
        error_log("Database error in getMeetingCount for authority $authority: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve meeting count."]);
    }
}

function getInvoiceCount(PDO $pdo, string $authority): void
{
    try {
        // Get authority ID from the authorities table
        $sqlAuthority = "SELECT id FROM kdd_authorities WHERE name = ?";
        $stmtAuthority = $pdo->prepare($sqlAuthority);
        $stmtAuthority->execute([$authority]);
        $authorityId = $stmtAuthority->fetchColumn();
        
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(["error" => "Authority not found in database."]);
            return;
        }

        $sql = "SELECT COUNT(id) FROM kdd_invoices 
                WHERE is_paid = 0 
                AND is_deleted = 0 
                AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $invoiceCount = (int) $stmt->fetchColumn();
        http_response_code(200);
        echo json_encode(["invoice_count" => $invoiceCount]);
    } catch (\PDOException $e) {
        error_log("Database error in getInvoiceCount for authority $authority: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve invoice count."]);
    }
}

function getOwnReportCount(PDO $pdo, int $userId, string $authority): void
{
    try {
        // Get authority ID from the authorities table
        $sqlAuthority = "SELECT id FROM kdd_authorities WHERE name = ?";
        $stmtAuthority = $pdo->prepare($sqlAuthority);
        $stmtAuthority->execute([$authority]);
        $authorityId = $stmtAuthority->fetchColumn();
        
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(["error" => "Authority not found in database."]);
            return;
        }

        // Using a subquery to get linked_employee ID
        $sql = "SELECT COUNT(DISTINCT r.id) FROM kdd_reports r
                LEFT JOIN kdd_report_missing_rel mr ON mr.report_id = r.id
                WHERE r.is_deleted = 0
                  AND mr.employee_id = (SELECT linked_employee FROM kdd_users WHERE id = ?)
                  AND r.authority_id = ?"; // Subquery uses userId

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $authorityId]);
        $reportCount = (int) $stmt->fetchColumn();
        http_response_code(200);
        echo json_encode(["report_count" => $reportCount]);
    } catch (\PDOException $e) {
        error_log("Database error in getOwnReportCount for user $userId, authority $authority: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve report count."]);
    }
}

function getOwnVacations(PDO $pdo, int $userId, string $authority): void
{
    try {
        // Get authority ID from the authorities table
        $sqlAuthority = "SELECT id FROM kdd_authorities WHERE name = ?";
        $stmtAuthority = $pdo->prepare($sqlAuthority);
        $stmtAuthority->execute([$authority]);
        $authorityId = $stmtAuthority->fetchColumn();
        
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(["error" => "Authority not found in database."]);
            return;
        }

        $sql = "SELECT v.id, v.reason, v.start, v.end, v.employee, v.reported, v.other 
                FROM kdd_employee_vacation v 
                JOIN kdd_users u ON v.employee = u.linked_employee
                WHERE u.id = ?
                  AND DATE(v.end) >= CURDATE() -- Check if vacation ends today or later
                  AND v.is_deleted = 0
                  AND v.authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $authorityId]);
        $vacations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($vacations);
    } catch (\PDOException $e) {
        error_log("Database error in getOwnVacations for user $userId, authority $authority: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve vacations."]);
    }
}

function stopVacation(PDO $pdo, int $userId, string $authority): void
{
    $data = getJsonRequestData();
    $newdate = $data['newdate'] ?? $_POST['newdate'] ?? null; // Try JSON first, then POST
    $id = $data['id'] ?? $_POST['id'] ?? null; // Try JSON first, then POST
    
    if ($newdate === null || !strtotime($newdate)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing new date provided.']);
        return;
    }
    // Format date consistently
    $formattedNewDate = date('Y-m-d', strtotime($newdate));

    try {
        // Get authority ID from the authorities table
        $sqlAuthority = "SELECT id FROM kdd_authorities WHERE name = ?";
        $stmtAuthority = $pdo->prepare($sqlAuthority);
        $stmtAuthority->execute([$authority]);
        $authorityId = $stmtAuthority->fetchColumn();
        
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(["error" => "Authority not found in database."]);
            return;
        }

        // Update the 'end' date for active/future vacations of the linked employee
        $sql = "UPDATE kdd_employee_vacation v
                 SET end = ?
                 WHERE id = ?
                   AND DATE(v.end) >= CURDATE()
                   AND v.is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$formattedNewDate, $id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Vacation end date updated successfully.']);
            // Optional: Log this change
            // logDatabaseChange(...);
        } elseif ($success) {
            http_response_code(200); // Or 404?
            echo json_encode(['success' => false, 'message' => 'No active vacation found for user to update.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to execute vacation update.']);
        }
    } catch (\PDOException $e) {
        error_log("Database error in stopVacation for user $userId, authority $authority: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Could not stop vacation.']);
    }
}

function addVacation(PDO $pdo, int $userId, string $authority): void
{
    // Get data from JSON or POST
    $data = getJsonRequestData();
    
    // Use JSON data if available, fall back to POST otherwise
    $reason = $data['type'] ?? $_POST['type'] ?? null;
    $start = $data['start'] ?? $_POST['start'] ?? null;
    $end = $data['end'] ?? $_POST['end'] ?? null;
    $reported = isset($data['reported']) ? (int)$data['reported'] : (isset($_POST['reported']) ? (int)$_POST['reported'] : 0);
    $other = $data['other'] ?? $_POST['other'] ?? ''; // Optional field

    // Basic validation
    if (!$reason || !$start || !$end || !strtotime($start) || !strtotime($end)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid vacation data (reason, start, end).']);
        return;
    }
    $formattedStart = date('Y-m-d', strtotime($start));
    $formattedEnd = date('Y-m-d', strtotime($end));

    try {
        // Get authority ID from the authorities table
        $sqlAuthority = "SELECT id FROM kdd_authorities WHERE name = ?";
        $stmtAuthority = $pdo->prepare($sqlAuthority);
        $stmtAuthority->execute([$authority]);
        $authorityId = $stmtAuthority->fetchColumn();
        
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(["error" => "Authority not found in database."]);
            return;
        }

        // Get linked employee ID first
        $sqlEmployee = "SELECT linked_employee FROM kdd_users WHERE id = ?";
        $stmtEmployee = $pdo->prepare($sqlEmployee);
        $stmtEmployee->execute([$userId]);
        $employeeId = $stmtEmployee->fetchColumn();

        if (!$employeeId) {
            http_response_code(404);
            echo json_encode(['error' => 'Associated employee record not found for this user.']);
            return;
        }

        // Insert the new vacation record - INCLUDING authority_id
        $sqlInsert = "INSERT INTO kdd_employee_vacation (reason, start, end, employee, reported, other, authority_id)
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $success = $stmtInsert->execute([
            $reason,
            $formattedStart,
            $formattedEnd,
            $data['id'],
            $reported,
            $other,
            $authorityId // Added authority_id to the parameters
        ]);

        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201); // Created
            echo json_encode(["success" => true, "message" => "Vacation added successfully.", "new_id" => $newId]);

            // Log the change
            $rowJson = json_encode([
                'reason' => $reason, 
                'start' => $formattedStart, 
                'end' => $formattedEnd, 
                'employee' => $data['id'],
                'reported' => $reported,
                'other' => $other,
                'authority_id' => $authorityId // Include authority_id in log data
            ]);
            $changes = [['column_name' => 'row_data', 'old_value' => null, 'new_value' => $rowJson]];
            
            // Log the change with the correct table name
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "kdd_employee_vacation", $newId, $userId, $changes);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to insert vacation record.']);
        }
    } catch (\PDOException $e) {
        error_log("Database error in addVacation for user $userId, authority $authority: " . $e->getMessage());
        // Check for specific errors like duplicate entry if needed (e.g., $e->getCode() == 23000)
        http_response_code(500);
        echo json_encode(['error' => 'Could not add vacation due to a database error.']);
    }
}

function getUsersWithEmployee(PDO $pdo, string $authority): void
{
    try {
        // Get authority ID from the authorities table
        $sqlAuthority = "SELECT id FROM kdd_authorities WHERE name = ?";
        $stmtAuthority = $pdo->prepare($sqlAuthority);
        $stmtAuthority->execute([$authority]);
        $authorityId = $stmtAuthority->fetchColumn();

        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(["error" => "Authority not found in database."]);
            return;
        }

        $sql = "SELECT u.id, COALESCE(CONCAT('[', e.servicenumber, '] ', e.name), u.username) as username
            FROM kdd_users u
            INNER JOIN kdd_employee e ON u.linked_employee = e.id
            WHERE u.authority = ?
            AND e.authority_id = ?
            AND u.linked_employee IS NOT NULL
            AND u.linked_employee != 0
            ORDER BY e.name";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authority, $authorityId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($users);
    } catch (\PDOException $e) {
        error_log("Database error in getUsersWithEmployee for authority $authority: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve users with employee data."]);
    }
}

function getOwnCrew(PDO $pdo, int $userId, string $authority): void
{
    try {
        // Get authority ID from the authorities table
        $sqlAuthority = "SELECT id FROM kdd_authorities WHERE name = ?";
        $stmtAuthority = $pdo->prepare($sqlAuthority);
        $stmtAuthority->execute([$authority]);
        $authorityId = $stmtAuthority->fetchColumn();

        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(["error" => "Authority not found in database."]);
            return;
        }

        // Get user's linked employee
        $sqlUser = "SELECT linked_employee FROM kdd_users WHERE id = ? AND authority_id = ?";
        $stmtUser = $pdo->prepare($sqlUser);
        $stmtUser->execute([$userId, $authorityId]);
        $linkedEmployee = $stmtUser->fetchColumn();

        // If user has no linked employee, return null
        if (!$linkedEmployee) {
            http_response_code(200);
            echo json_encode(["crew" => null]);
            return;
        }

        // Get employee's dispatch/crew assignment
        $sqlEmployee = "SELECT dispatch_id FROM kdd_employee WHERE id = ? AND authority_id = ?";
        $stmtEmployee = $pdo->prepare($sqlEmployee);
        $stmtEmployee->execute([$linkedEmployee, $authorityId]);
        $dispatchId = $stmtEmployee->fetchColumn();

        // If employee has no crew assignment, return null
        if (!$dispatchId) {
            http_response_code(200);
            echo json_encode(["crew" => null]);
            return;
        }

        // Get crew/dispatch details
        $sqlCrew = "SELECT id, name, status, active FROM kdd_dispatch WHERE id = ? AND authority_id = ?";
        $stmtCrew = $pdo->prepare($sqlCrew);
        $stmtCrew->execute([$dispatchId, $authorityId]);
        $crew = $stmtCrew->fetch(PDO::FETCH_ASSOC);

        if (!$crew) {
            http_response_code(200);
            echo json_encode(["crew" => null]);
            return;
        }

        // Get all crew members (employees with the same dispatch_id) including rank names
        $sqlMembers = "SELECT
                        e.id,
                        e.name,
                        e.servicenumber,
                        e.rank_id,
                        r.name as role
                       FROM kdd_employee e
                       LEFT JOIN kdd_employee_rank r ON e.rank_id = r.id
                       WHERE e.dispatch_id = ? AND e.authority_id = ? AND e.is_terminated = 0
                       ORDER BY e.rank_id DESC, e.name ASC";
        $stmtMembers = $pdo->prepare($sqlMembers);
        $stmtMembers->execute([$dispatchId, $authorityId]);
        $members = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

        // Add members to crew object
        $crew['members'] = $members;
        $crew['memberCount'] = count($members);

        http_response_code(200);
        echo json_encode(["crew" => $crew]);
    } catch (\PDOException $e) {
        error_log("Database error in getOwnCrew for user $userId, authority $authority: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve crew information."]);
    }
}
