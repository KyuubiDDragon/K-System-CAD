<?php
/**
 * Backend Endpoint: calendar/index.php
 * Handles CRUD operations for Calendar Events.
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
// Assumes logging functions are available and PDO compatible
require_once __DIR__ . '/../logging/logging.php';

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

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'calendar')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to calendar features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    'getEvents'               => ['module' => 'calendar', 'action' => 'read'],
    'getEventsToday'          => ['module' => 'calendar', 'action' => 'read'],
    'getEventsTodayCount'     => ['module' => 'calendar', 'action' => 'read'],
    'getEvents7Days'          => ['module' => 'calendar', 'action' => 'read'],
    'getEvents7DaysTodayInc'  => ['module' => 'calendar', 'action' => 'read'],
    'getUpcomingAssignedEvents' => ['module' => 'calendar', 'action' => 'read'],
    'createEvent'             => ['module' => 'calendar', 'action' => 'write'],
    'updateEvent'             => ['module' => 'calendar', 'action' => 'write'],
    'deleteEvent'             => ['module' => 'calendar', 'action' => 'delete'],
    // New group management permissions
    'getGroups'               => ['module' => 'calendar', 'action' => 'read'],
    'createGroup'             => ['module' => 'calendar', 'action' => 'write'],
    'updateGroup'             => ['module' => 'calendar', 'action' => 'write'],
    'deleteGroup'             => ['module' => 'calendar', 'action' => 'delete'],
    'getGroupMembers'         => ['module' => 'calendar', 'action' => 'read'],
    'addGroupMember'          => ['module' => 'calendar', 'action' => 'write'],
    'removeGroupMember'       => ['module' => 'calendar', 'action' => 'write'],
    'updateGroupMember'       => ['module' => 'calendar', 'action' => 'write']
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
    $is_put_request = ($request_method === 'PUT');
    $is_delete_request = ($request_method === 'DELETE');

    switch ($action) {
        // GET Actions
        case 'getEvents':               if ($request_method === 'GET') getEvents($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getEventsToday':          if ($request_method === 'GET') getEventsToday($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getEventsTodayCount':     if ($request_method === 'GET') getEventsTodayCount($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getEvents7Days':          if ($request_method === 'GET') getEvents7Days($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getEvents7DaysTodayInc':  if ($request_method === 'GET') getEvents7DaysTodayInc($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getUpcomingAssignedEvents': if ($request_method === 'GET') getUpcomingAssignedEvents($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getGroups':               if ($request_method === 'GET') getGroups($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;

        // POST/PUT/DELETE Actions
        case 'createEvent':             if ($is_post_request) createEvent($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'updateEvent':             if ($is_post_request || $is_put_request) updateEvent($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Allow POST or PUT
        case 'deleteEvent':             if ($is_post_request || $is_delete_request) deleteEvent($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Allow POST or DELETE
        case 'createGroup':             if ($is_post_request) createGroup($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'updateGroup':             if ($is_post_request) updateGroup($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'deleteGroup':             if ($is_post_request) deleteGroup($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getGroupMembers':         if ($request_method === 'GET') getGroupMembers($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'addGroupMember':          if ($is_post_request) addGroupMember($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'removeGroupMember':       if ($is_post_request) removeGroupMember($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'updateGroupMember':       if ($is_post_request) updateGroupMember($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;

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

/**
 * Fetches calendar events and their assigned users.
 * Combines two queries for efficiency.
 */
function fetchEventsWithAssignments(PDO $pdo, string $authority, int $authorityId, int $userId, string $whereClause = "c.is_deleted = 0", array $params = []): array {
    $events = [];

    try {
        // Add authority_id to where clause
        if ($whereClause) {
            $whereClause .= " AND c.authority_id = ?";
        } else {
            $whereClause = "c.authority_id = ?";
        }
        $params[] = $authorityId;

        // 1. Fetch Events with authority_id
        $sqlEvents = "SELECT c.id, c.title, c.content, c.start_date as start, c.end_date as end, 
                     c.color, c.contentFull, c.recurring, c.group_id, c.is_private,
                     g.name as group_name, g.color as group_color
                     FROM kdd_calendar c
                     LEFT JOIN kdd_calendar_groups g ON c.group_id = g.id AND g.is_deleted = 0
                     WHERE {$whereClause}
                     ORDER BY c.start_date";
                     

        $stmtEvents = $pdo->prepare($sqlEvents);
        $stmtEvents->execute($params);
        $eventRows = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

        if (empty($eventRows)) {
            return []; // No events found
        }

        // Prepare events array indexed by ID for easy lookup
        $eventIds = [];
        foreach ($eventRows as $row) {
            $row['assignedUsers'] = []; // Initialize empty assignments array
            $events[$row['id']] = $row;
            $eventIds[] = $row['id'];
        }

        // 2. Fetch Assignments for these specific events
        // Create placeholders for the IN clause (?, ?, ?)
        $placeholders = rtrim(str_repeat('?,', count($eventIds)), ',');
        
        // Berücksichtige authority_id auch in calendar_assigned
        $sqlAssignments = "SELECT ca.event_id, ca.user_id, u.username 
                        FROM kdd_calendar_assigned ca 
                        JOIN kdd_users u ON ca.user_id = u.id
                        JOIN kdd_calendar c ON ca.event_id = c.id
                        WHERE ca.event_id IN ({$placeholders})
                        AND c.authority_id = ?";
        
        $assignmentParams = $eventIds;
        $assignmentParams[] = $authorityId;
        $stmtAssignments = $pdo->prepare($sqlAssignments);
        $stmtAssignments->execute($assignmentParams);
        $assignmentRows = $stmtAssignments->fetchAll(PDO::FETCH_ASSOC);

        // 3. Map assignments back to events
        foreach ($assignmentRows as $assignment) {
            if (isset($events[$assignment['event_id']])) {
                $events[$assignment['event_id']]['assignedUsers'][] = [
                    'user_id' => (int)$assignment['user_id'],
                    'username' => $assignment['username']
                ];
            }
        }

        // 4. Filter out private events that the user doesn't have access to
        $filteredEvents = [];
        foreach ($events as $event) {
            // If event is private, check if user has access
            if ($event['is_private']) {
                $hasAccess = false;
                
                // Check if user is assigned to the event
                foreach ($event['assignedUsers'] as $user) {
                    if ($user['user_id'] === $userId) {
                        $hasAccess = true;
                        break;
                    }
                }
                
                // Check if user is in the event's group
                if (!$hasAccess && $event['group_id']) {
                    $sql = "SELECT 1 FROM kdd_calendar_group_members 
                           WHERE group_id = ? AND user_id = ? AND authority_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$event['group_id'], $userId, $authorityId]);
                    if ($stmt->fetch()) {
                        $hasAccess = true;
                    }
                }
                
                // Skip private events that user doesn't have access to
                if (!$hasAccess) {
                    continue;
                }
            }
            
            $filteredEvents[] = $event;
        }

        // Return as a numerically indexed array
        return array_values($filteredEvents);
    } catch (\PDOException $e) {
        error_log("DB error in fetchEventsWithAssignments: " . $e->getMessage());
        return [];
    }
}

function getEvents(PDO $pdo, string $authority, int $authorityId): void {
    global $userId;
    try {
        $events = fetchEventsWithAssignments($pdo, $authority, $authorityId, $userId);
        http_response_code(200);
        echo json_encode($events);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getEvents7Days(PDO $pdo, string $authority, int $authorityId): void {
    global $userId;
    try {
        // Get events for next 7 days
        $whereClause = "c.is_deleted = 0 AND c.start_date BETWEEN DATE(NOW()) + INTERVAL 1 DAY AND DATE(NOW()) + INTERVAL 7 DAY";
        $events = fetchEventsWithAssignments($pdo, $authority, $authorityId, $userId, $whereClause);
        http_response_code(200);
        echo json_encode($events);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getEvents7DaysTodayInc(PDO $pdo, string $authority, int $authorityId): void {
    global $userId;
    try {
        // Get events for today and next 7 days
        $whereClause = "c.is_deleted = 0 AND c.start_date BETWEEN DATE(NOW()) AND DATE(NOW()) + INTERVAL 7 DAY";
        $events = fetchEventsWithAssignments($pdo, $authority, $authorityId, $userId, $whereClause);
        http_response_code(200);
        echo json_encode($events);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getEventsToday(PDO $pdo, string $authority, int $authorityId): void {
    global $userId;
    try {
        // Get events for today
        $whereClause = "c.is_deleted = 0 AND DATE(c.start_date) = DATE(NOW())";
        $events = fetchEventsWithAssignments($pdo, $authority, $authorityId, $userId, $whereClause);
        http_response_code(200);
        echo json_encode($events);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getEventsTodayCount(PDO $pdo, string $authority, int $authorityId): void {
    try {
        // Directly count events for today
        $sql = "SELECT COUNT(*) as count FROM kdd_calendar c
                WHERE c.authority_id = ? AND c.is_deleted = 0 AND DATE(c.start_date) = DATE(NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $count = $result ? (int)$result['count'] : 0;
        
        http_response_code(200);
        echo json_encode(["count" => $count]);
    } catch (\PDOException $e) {
        error_log("DB error in getEventsTodayCount ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve today's event count."]);
    }
}

function createEvent(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    // Get data from request body (JSON)
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Basic validation
    if (!$data || empty($data['title']) || empty($data['start']) || empty($data['end'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required fields (title, start, end)."]);
        return;
    }
    
    // Process and sanitize data
    $title = $data['title'];
    $content = $data['content'] ?? '';
    $contentFull = $data['contentFull'] ?? '';
    $color = $data['color'] ?? '#3788d8'; // Default color
    $start = $data['start'];
    $end = $data['end'];
    $recurring = $data['recurring'] ?? null;
    $linkedToTable = $data['linked_to_table'] ?? null;
    $linkedToId = filter_var($data['linked_to_id'] ?? null, FILTER_VALIDATE_INT);
    $assignedUserIds = $data['assigned_to'] ?? [];
    $isPrivate = $data['is_private'] ?? false;
    $groupId = $data['group_id'] ?? null;
    
    // Validate dates
    if (!strtotime($start) || !strtotime($end)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid date format for start or end."]);
        return;
    }
    
    // Convert to proper MySQL datetime format
    $startFormatted = date('Y-m-d H:i:s', strtotime($start));
    $endFormatted = date('Y-m-d H:i:s', strtotime($end));
    
    try {
        $pdo->beginTransaction();
        
        // 1. Insert the event
        $sql = "INSERT INTO kdd_calendar 
                (authority_id, title, content, contentFull, start_date, end_date, color, recurring, linked_to_table, linked_to_id, is_private, group_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $authorityId, 
            $title, 
            $content, 
            $contentFull, 
            $startFormatted, 
            $endFormatted, 
            $color, 
            $recurring, 
            $linkedToTable, 
            $linkedToId,
            $isPrivate,
            $groupId
        ]);
        
        $eventId = $pdo->lastInsertId();

        // Log event creation
        $changes = [
            ['column_name' => 'title', 'old_value' => null, 'new_value' => $title],
            ['column_name' => 'start_date', 'old_value' => null, 'new_value' => $startFormatted],
            ['column_name' => 'end_date', 'old_value' => null, 'new_value' => $endFormatted],
            ['column_name' => 'recurring', 'old_value' => null, 'new_value' => $recurring],
            ['column_name' => 'is_private', 'old_value' => null, 'new_value' => $isPrivate]
        ];
        logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_calendar', $eventId, $requestingUserId, $changes);

        // 2. Handle assigned users if any
        if (!empty($assignedUserIds)) {
            // Validate that all assigned users are either the current user or in the same groups
            $validUserIds = [$requestingUserId]; // Always allow self-assignment
            
            // If a group is specified, get all members of that group
            if ($groupId) {
                $sql = "SELECT user_id FROM kdd_calendar_group_members WHERE group_id = ? AND authority_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$groupId, $authorityId]);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $validUserIds[] = $row['user_id'];
                }
            }
            
            // Filter out invalid user IDs
            $assignedUserIds = array_intersect($assignedUserIds, $validUserIds);
            
            if (!empty($assignedUserIds)) {
                $sqlAssign = "INSERT INTO kdd_calendar_assigned (event_id, user_id, authority_id) VALUES (?, ?, ?)";
                $stmtAssign = $pdo->prepare($sqlAssign);
                
                foreach ($assignedUserIds as $userId) {
                    $userId = filter_var($userId, FILTER_VALIDATE_INT);
                    if ($userId) {
                        $stmtAssign->execute([$eventId, $userId, $authorityId]);
                        $assignId = $pdo->lastInsertId();

                        // Log assignment
                        $assignChanges = [
                            ['column_name' => 'event_id', 'old_value' => null, 'new_value' => $eventId],
                            ['column_name' => 'user_id', 'old_value' => null, 'new_value' => $userId]
                        ];
                        logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_calendar_assigned', $assignId, $requestingUserId, $assignChanges);
                    }
                }
            }
        }
        
        $pdo->commit();
        
        http_response_code(201); // Created
        echo json_encode([
            "success" => true,
            "message" => "Event created successfully.",
            "id" => $eventId
        ]);
        
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in createEvent ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not create event."]);
    }
}

function updateEvent(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    // Get data from request body (JSON)
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Basic validation
    if (!$data || empty($data['id'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing event ID."]);
        return;
    }
    
    $eventId = filter_var($data['id'], FILTER_VALIDATE_INT);
    if (!$eventId) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid event ID."]);
        return;
    }
    
    try {
        // First check if event exists and belongs to this authority
        $sqlCheck = "SELECT id FROM kdd_calendar WHERE id = ? AND authority_id = ? AND is_deleted = 0";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$eventId, $authorityId]);
        $existingEvent = $stmtCheck->fetch();
        
        if (!$existingEvent) {
            http_response_code(404);
            echo json_encode(["error" => "Event not found or access denied."]);
            return;
        }
        
        // Begin the update transaction
        $pdo->beginTransaction();
        
        // 1. Prepare base SQL for update with only fields that are present
        $updateFields = [];
        $params = [];
        
        if (isset($data['title'])) {
            $updateFields[] = "title = ?";
            $params[] = $data['title'];
        }
        
        if (isset($data['content'])) {
            $updateFields[] = "content = ?";
            $params[] = $data['content'];
        }
        
        if (isset($data['contentFull'])) {
            $updateFields[] = "contentFull = ?";
            $params[] = $data['contentFull'];
        }
        
        if (isset($data['color'])) {
            $updateFields[] = "color = ?";
            $params[] = $data['color'];
        }
        
        if (isset($data['start'])) {
            if (!strtotime($data['start'])) {
                throw new \InvalidArgumentException("Invalid start date format.");
            }
            $updateFields[] = "start_date = ?";
            $params[] = date('Y-m-d H:i:s', strtotime($data['start']));
        }
        
        if (isset($data['end'])) {
            if (!strtotime($data['end'])) {
                throw new \InvalidArgumentException("Invalid end date format.");
            }
            $updateFields[] = "end_date = ?";
            $params[] = date('Y-m-d H:i:s', strtotime($data['end']));
        }
        
        if (isset($data['recurring'])) {
            $updateFields[] = "recurring = ?";
            $params[] = $data['recurring'];
        }
        
        if (isset($data['linked_to_table'])) {
            $updateFields[] = "linked_to_table = ?";
            $params[] = $data['linked_to_table'];
        }
        
        if (isset($data['linked_to_id'])) {
            $updateFields[] = "linked_to_id = ?";
            $params[] = filter_var($data['linked_to_id'], FILTER_VALIDATE_INT);
        }

        if (isset($data['is_private'])) {
            $updateFields[] = "is_private = ?";
            $params[] = $data['is_private'];
        }

        if (isset($data['group_id'])) {
            $updateFields[] = "group_id = ?";
            $params[] = $data['group_id'];
        }
        
        // Only proceed if there are fields to update
        if (empty($updateFields)) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(["error" => "No valid fields to update."]);
            return;
        }

        // Get old state for logging
        $oldEntry = getEntryById($pdo, $eventId, 'kdd_calendar', $authorityId);

        // 2. Execute the update
        $sql = "UPDATE kdd_calendar SET " . implode(", ", $updateFields) . " WHERE id = ? AND authority_id = ?";
        $params[] = $eventId;
        $params[] = $authorityId;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Log the update
        if ($oldEntry) {
            $changes = [];
            foreach ($data as $key => $value) {
                if (isset($oldEntry[$key]) && $oldEntry[$key] != $value && $key !== 'assigned_to') {
                    $changes[] = ['column_name' => $key, 'old_value' => $oldEntry[$key], 'new_value' => $value];
                }
            }
            if (!empty($changes)) {
                logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_calendar', $eventId, $requestingUserId, $changes);
            }
        }
        
        // 3. Handle assigned users if present in the request
        if (isset($data['assigned_to'])) {
            // Get old assignments for logging
            $sqlOldAssign = "SELECT id, user_id FROM kdd_calendar_assigned WHERE event_id = ?";
            $stmtOldAssign = $pdo->prepare($sqlOldAssign);
            $stmtOldAssign->execute([$eventId]);
            $oldAssignments = $stmtOldAssign->fetchAll(PDO::FETCH_ASSOC);

            // First, remove all existing assignments
            $sqlDelete = "DELETE FROM kdd_calendar_assigned WHERE event_id = ?";
            $stmtDelete = $pdo->prepare($sqlDelete);
            $stmtDelete->execute([$eventId]);

            // Log deletions
            foreach ($oldAssignments as $oldAssign) {
                $changes = [
                    ['column_name' => 'user_id', 'old_value' => $oldAssign['user_id'], 'new_value' => null]
                ];
                logDatabaseChange($authorityId, $pdo, 'DELETE', 'kdd_calendar_assigned', $oldAssign['id'], $requestingUserId, $changes);
            }
            
            // Then add the new assignments
            if (!empty($data['assigned_to'])) {
                // Validate that all assigned users are either the current user or in the same groups
                $validUserIds = [$requestingUserId]; // Always allow self-assignment
                
                // If a group is specified, get all members of that group
                if (isset($data['group_id'])) {
                    $sql = "SELECT user_id FROM kdd_calendar_group_members WHERE group_id = ? AND authority_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$data['group_id'], $authorityId]);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $validUserIds[] = $row['user_id'];
                    }
                }
                
                // Filter out invalid user IDs
                $assignedUserIds = array_intersect($data['assigned_to'], $validUserIds);
                
                if (!empty($assignedUserIds)) {
                    $sqlInsert = "INSERT INTO kdd_calendar_assigned (event_id, user_id, authority_id) VALUES (?, ?, ?)";
                    $stmtInsert = $pdo->prepare($sqlInsert);
                    
                    foreach ($assignedUserIds as $userId) {
                        $userId = filter_var($userId, FILTER_VALIDATE_INT);
                        if ($userId) {
                            $stmtInsert->execute([$eventId, $userId, $authorityId]);
                            $assignId = $pdo->lastInsertId();

                            // Log new assignment
                            $assignChanges = [
                                ['column_name' => 'event_id', 'old_value' => null, 'new_value' => $eventId],
                                ['column_name' => 'user_id', 'old_value' => null, 'new_value' => $userId]
                            ];
                            logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_calendar_assigned', $assignId, $requestingUserId, $assignChanges);
                        }
                    }
                }
            }
        }
        
        $pdo->commit();
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Event updated successfully."
        ]);
        
    } catch (\InvalidArgumentException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(400);
        echo json_encode(["error" => $e->getMessage()]);
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in updateEvent ($authority, ID: $eventId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update event."]);
    }
}

function deleteEvent(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    // Support both JSON body (for POST) and URL param (for DELETE)
    $eventId = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['id'])) {
        $eventId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    } else {
        $data = json_decode(file_get_contents('php://input'), true);
        $eventId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    }
    
    if (!$eventId) {
        http_response_code(400);
        echo json_encode(["error" => "Missing or invalid event ID."]);
        return;
    }
    
    try {
        // Check if event exists and belongs to this authority
        $sqlCheck = "SELECT id FROM kdd_calendar WHERE id = ? AND authority_id = ? AND is_deleted = 0";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$eventId, $authorityId]);
        
        if (!$stmtCheck->fetch()) {
            http_response_code(404);
            echo json_encode(["error" => "Event not found or already deleted."]);
            return;
        }
        
        $pdo->beginTransaction();

        // Get old state for logging
        $oldEntry = getEntryById($pdo, $eventId, 'kdd_calendar', $authorityId);

        // Get assignments before deletion for logging
        $sqlOldAssign = "SELECT id, user_id FROM kdd_calendar_assigned WHERE event_id = ?";
        $stmtOldAssign = $pdo->prepare($sqlOldAssign);
        $stmtOldAssign->execute([$eventId]);
        $oldAssignments = $stmtOldAssign->fetchAll(PDO::FETCH_ASSOC);

        // Soft delete by setting is_deleted flag
        $sqlUpdate = "UPDATE kdd_calendar SET is_deleted = 1 WHERE id = ? AND authority_id = ?";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([$eventId, $authorityId]);

        // Log soft delete
        if ($oldEntry) {
            $changes = [
                ['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]
            ];
            logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_calendar', $eventId, $requestingUserId, $changes);
        }

        // Remove assignments (consider whether you want to keep these for history)
        $sqlDeleteAssignments = "DELETE FROM kdd_calendar_assigned WHERE event_id = ?";
        $stmtDeleteAssignments = $pdo->prepare($sqlDeleteAssignments);
        $stmtDeleteAssignments->execute([$eventId]);

        // Log assignment deletions
        foreach ($oldAssignments as $oldAssign) {
            $changes = [
                ['column_name' => 'event_id', 'old_value' => $eventId, 'new_value' => null]
            ];
            logDatabaseChange($authorityId, $pdo, 'DELETE', 'kdd_calendar_assigned', $oldAssign['id'], $requestingUserId, $changes);
        }
        
        // Log the deletion
        // Example: logDatabaseChange($pdo, 'DELETE', 'calendar', $eventId, $requestingUserId, [], []);
        
        $pdo->commit();
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Event deleted successfully."
        ]);
        
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in deleteEvent ($authority, ID: $eventId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete event."]);
    }
}

function getUpcomingAssignedEvents(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    try {
        error_log("Starting getUpcomingAssignedEvents for user $requestingUserId");
        
        // DEBUG: Prüfe, ob überhaupt Events für diesen Benutzer existieren (ohne Datumsbeschränkung)
        $sqlDebug1 = "SELECT COUNT(*) as count 
                     FROM kdd_calendar c
                     JOIN kdd_calendar_assigned ca ON c.id = ca.event_id AND ca.user_id = ?
                     WHERE c.authority_id = ? AND c.is_deleted = 0";
        $stmtDebug1 = $pdo->prepare($sqlDebug1);
        $stmtDebug1->execute([$requestingUserId, $authorityId]);
        $countResult1 = $stmtDebug1->fetch(PDO::FETCH_ASSOC);
        error_log("Debug 1: Total assigned events for user (any date): " . $countResult1['count']);
        
        // DEBUG: Prüfe, ob Gruppen-Events für diesen Benutzer existieren
        $sqlDebugGroup = "SELECT COUNT(*) as count 
                     FROM kdd_calendar c
                     JOIN kdd_calendar_group_members gm ON c.group_id = gm.group_id AND gm.user_id = ? AND gm.is_deleted = 0
                     WHERE c.authority_id = ? AND c.is_deleted = 0";
        $stmtDebugGroup = $pdo->prepare($sqlDebugGroup);
        $stmtDebugGroup->execute([$requestingUserId, $authorityId]);
        $countResultGroup = $stmtDebugGroup->fetch(PDO::FETCH_ASSOC);
        error_log("Debug: Total group events for user: " . $countResultGroup['count']);
        
        // DEBUG: Prüfe, ob zukünftige Events für diesen Authority existieren
        $sqlDebug2 = "SELECT COUNT(*) as count 
                     FROM kdd_calendar c
                     WHERE c.authority_id = ? AND c.is_deleted = 0 AND c.start_date >= NOW()";
        $stmtDebug2 = $pdo->prepare($sqlDebug2);
        $stmtDebug2->execute([$authorityId]);
        $countResult2 = $stmtDebug2->fetch(PDO::FETCH_ASSOC);
        error_log("Debug 2: Total future events for authority: " . $countResult2['count']);
        
        // Verbesserte Abfrage - berücksichtigt sowohl direkt zugewiesene als auch Gruppenevents
        $sql = "SELECT c.id, c.title, c.content, c.start_date as start, c.end_date as end, 
               c.color, c.contentFull, c.recurring, c.group_id, c.is_private,
               g.name as group_name, g.color as group_color
               FROM kdd_calendar c
               LEFT JOIN kdd_calendar_groups g ON c.group_id = g.id AND g.is_deleted = 0
               WHERE c.authority_id = ? AND c.is_deleted = 0 
               AND (
                   -- Entweder direkt dem Benutzer zugewiesen
                   EXISTS (
                       SELECT 1 FROM kdd_calendar_assigned ca 
                       WHERE ca.event_id = c.id AND ca.user_id = ?
                   )
                   OR
                   -- Oder einer Gruppe zugeordnet, in der der Benutzer Mitglied ist
                   (
                       c.group_id IS NOT NULL
                       AND EXISTS (
                           SELECT 1 FROM kdd_calendar_group_members gm 
                           WHERE gm.group_id = c.group_id AND gm.user_id = ? AND gm.is_deleted = 0
                       )
                   )
               )
               AND c.start_date >= NOW()
               ORDER BY c.start_date ASC
               LIMIT 5";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $requestingUserId, $requestingUserId]);
        $eventRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($eventRows) . " events for user (including group events)");
        
        // Events für Ausgabe vorbereiten (gleiche Struktur wie fetchEventsWithAssignments)
        $events = [];
        $eventIds = [];
        
        foreach ($eventRows as $row) {
            $row['assignedUsers'] = []; // Initialize empty assignments array
            $events[$row['id']] = $row;
            $eventIds[] = $row['id'];
        }
        
        // Falls Events gefunden wurden, hole die zugewiesenen Benutzer
        if (!empty($eventIds)) {
            // Erstelle Platzhalter (?, ?, ?)
            $placeholders = rtrim(str_repeat('?,', count($eventIds)), ',');
            
            // Hole zugewiesene Benutzer
            $sqlAssignments = "SELECT ca.event_id, ca.user_id, u.username 
                            FROM kdd_calendar_assigned ca 
                            JOIN kdd_users u ON ca.user_id = u.id
                            WHERE ca.event_id IN ({$placeholders})";
            
            $stmtAssignments = $pdo->prepare($sqlAssignments);
            $stmtAssignments->execute($eventIds);
            $assignmentRows = $stmtAssignments->fetchAll(PDO::FETCH_ASSOC);
            
            // Zuweisungen den Events zuordnen
            foreach ($assignmentRows as $assignment) {
                if (isset($events[$assignment['event_id']])) {
                    $events[$assignment['event_id']]['assignedUsers'][] = [
                        'user_id' => (int)$assignment['user_id'],
                        'username' => $assignment['username']
                    ];
                }
            }
        }
        
        // Indexed-Array für Ausgabe erzeugen
        $result = array_values($events);
        error_log("Final result: " . json_encode($result));
        
        http_response_code(200);
        echo json_encode($result);
        
    } catch (\PDOException $e) {
        error_log("DB error in getUpcomingAssignedEvents ($authority, UserID: $requestingUserId): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve upcoming assigned events."]);
    } catch (\Throwable $e) {
        error_log("Unexpected error in getUpcomingAssignedEvents: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Unexpected error retrieving upcoming assigned events."]);
    }
}

/**
 * Determines the most likely event type based on the title.
 * Used for color-coding or categorizing events.
 */
function determineEventType(string $title): string {
    $title = strtolower($title);
    
    // Map of keywords to event types
    $typeKeywords = [
        'meeting' => ['meeting', 'besprechung', 'konferenz', 'briefing'],
        'training' => ['training', 'übung', 'schulung', 'education'],
        'operation' => ['einsatz', 'operation', 'mission', 'notfall'],
        'administrative' => ['verwaltung', 'admin', 'büro', 'paperwork'],
        'social' => ['feier', 'party', 'event', 'social']
    ];
    
    foreach ($typeKeywords as $type => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($title, $keyword) !== false) {
                return $type;
            }
        }
    }
    
    // Default type if no keywords match
    return 'other';
}

// --- Calendar CRUD Functions (For calendar.php) ---

/**
 * Functions below are intended to be called from other modules
 * that need to integrate with the calendar system.
 */

/**
 * Creates an event in the calendar system.
 * For use by other modules.
 */
function createCalendarEvent(PDO $pdo, string $title, string $content, string $startDate, string $startTime, 
                           int $durationMinutes, string $authority, int $authorityId, 
                           string $linkedTable = null, int $linkedId = null): bool {
    try {
        // Format datetime
        $startDateTime = $startDate . ' ' . $startTime;
        $startTimestamp = strtotime($startDateTime);
        
        if (!$startTimestamp) {
            error_log("Invalid date/time format in createCalendarEvent: $startDate $startTime");
            return false;
        }
        
        $start = date('Y-m-d H:i:s', $startTimestamp);
        $end = date('Y-m-d H:i:s', $startTimestamp + ($durationMinutes * 60));
        
        // Determine color based on event type
        $eventType = determineEventType($title);
        $color = '#3788d8'; // Default blue
        
        switch ($eventType) {
            case 'meeting': $color = '#28a745'; break; // Green
            case 'training': $color = '#17a2b8'; break; // Teal
            case 'operation': $color = '#dc3545'; break; // Red
            case 'administrative': $color = '#6c757d'; break; // Gray
            case 'social': $color = '#ffc107'; break; // Yellow
        }
        
        $sql = "INSERT INTO kdd_calendar 
                (authority_id, title, content, start_date, end_date, color, linked_to_table, linked_to_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            $authorityId,
            $title,
            $content,
            $start,
            $end,
            $color,
            $linkedTable,
            $linkedId
        ]);

        if (!$success) {
            error_log("Failed to insert calendar event in createCalendarEvent");
            return false;
        }

        // Log event creation
        $eventId = $pdo->lastInsertId();
        $changes = [
            ['column_name' => 'title', 'old_value' => null, 'new_value' => $title],
            ['column_name' => 'start_date', 'old_value' => null, 'new_value' => $start],
            ['column_name' => 'end_date', 'old_value' => null, 'new_value' => $end],
            ['column_name' => 'linked_to_table', 'old_value' => null, 'new_value' => $linkedTable],
            ['column_name' => 'linked_to_id', 'old_value' => null, 'new_value' => $linkedId]
        ];
        logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_calendar', $eventId, 0, $changes);
        
        return true;
    } catch (\Throwable $e) {
        error_log("Error in createCalendarEvent: " . $e->getMessage());
        return false;
    }
}

/**
 * Updates an existing calendar event by its linked table and ID.
 * For use by other modules.
 */
function updateCalendarEventByLink(PDO $pdo, string $title, string $content, string $startDate, string $startTime, 
                                 int $durationMinutes, string $authority, int $authorityId,
                                 string $linkedTable, int $linkedId): bool {
    try {
        // Format datetime
        $startDateTime = $startDate . ' ' . $startTime;
        $startTimestamp = strtotime($startDateTime);
        
        if (!$startTimestamp) {
            error_log("Invalid date/time format in updateCalendarEventByLink: $startDate $startTime");
            return false;
        }
        
        $start = date('Y-m-d H:i:s', $startTimestamp);
        $end = date('Y-m-d H:i:s', $startTimestamp + ($durationMinutes * 60));
        
        // Check if event exists
        $sqlCheck = "SELECT id FROM kdd_calendar 
                    WHERE authority_id = ? AND linked_to_table = ? AND linked_to_id = ? AND is_deleted = 0";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$authorityId, $linkedTable, $linkedId]);
        
        if ($stmtCheck->fetch()) {
            // Update existing event
            $sql = "UPDATE kdd_calendar 
                    SET title = ?, content = ?, start_date = ?, end_date = ?
                    WHERE authority_id = ? AND linked_to_table = ? AND linked_to_id = ? AND is_deleted = 0";
            
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                $title,
                $content,
                $start,
                $end,
                $authorityId,
                $linkedTable,
                $linkedId
            ]);
            
            return $success;
        } else {
            // No event exists, create new one
            return createCalendarEvent($pdo, $title, $content, $startDate, $startTime, 
                                     $durationMinutes, $authority, $authorityId, $linkedTable, $linkedId);
        }
    } catch (\Throwable $e) {
        error_log("Error in updateCalendarEventByLink: " . $e->getMessage());
        return false;
    }
}

/**
 * Deletes a calendar event by its linked table and ID.
 * For use by other modules.
 */
function deleteCalendarEventByLink(PDO $pdo, string $authority, int $authorityId, string $linkedTable, int $linkedId): bool {
    try {
        // Get all events that will be deleted for logging
        $getEventsSql = "SELECT id FROM kdd_calendar WHERE authority_id = ? AND linked_to_table = ? AND linked_to_id = ? AND is_deleted = 0";
        $getEventsStmt = $pdo->prepare($getEventsSql);
        $getEventsStmt->execute([$authorityId, $linkedTable, $linkedId]);
        $events = $getEventsStmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "UPDATE kdd_calendar SET is_deleted = 1
                WHERE authority_id = ? AND linked_to_table = ? AND linked_to_id = ? AND is_deleted = 0";

        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $linkedTable, $linkedId]);

        // Log each event soft delete
        if ($success && !empty($events)) {
            foreach ($events as $event) {
                $changes = [
                    ['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]
                ];
                logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_calendar', $event['id'], 0, $changes);
            }
        }

        return $success;
    } catch (\Throwable $e) {
        error_log("Error in deleteCalendarEventByLink: " . $e->getMessage());
        return false;
    }
}

// --- Calendar Group Tables ---

// Add new group management functions
function getGroups(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    try {
        // Get groups where:
        // 1. The user is a member, OR
        // 2. The user is the creator of the group
        $sql = "SELECT 
                    g.id,
                    g.name,
                    g.description,
                    g.color,
                    g.created_by,
                    g.created_at,
                    CASE 
                        WHEN gm.user_id = ? THEN true 
                        ELSE false 
                    END as is_member,
                    CASE 
                        WHEN gm.user_id = ? AND gm.can_manage = 1 THEN true
                        WHEN g.created_by = ? THEN true 
                        ELSE false 
                    END as can_manage,
                    COUNT(DISTINCT m.user_id) as member_count
                FROM kdd_calendar_groups g
                LEFT JOIN kdd_calendar_group_members gm ON g.id = gm.group_id AND gm.user_id = ? AND gm.is_deleted = 0
                LEFT JOIN kdd_calendar_group_members m ON g.id = m.group_id AND m.is_deleted = 0
                WHERE g.authority_id = ? AND g.is_deleted = 0
                AND (
                    g.created_by = ? OR
                    EXISTS (
                        SELECT 1 FROM kdd_calendar_group_members 
                        WHERE group_id = g.id AND user_id = ? AND is_deleted = 0
                    )
                )
                GROUP BY g.id
                ORDER BY g.name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $requestingUserId, 
            $requestingUserId, 
            $requestingUserId, 
            $requestingUserId, 
            $authorityId,
            $requestingUserId,
            $requestingUserId
        ]);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Hole die Mitglieder für jede Gruppe
        foreach ($groups as &$group) {
            $memberQuery = "SELECT 
                u.id as user_id,
                u.username,
                gm.can_edit,
                gm.can_delete,
                gm.can_manage,
                (CASE WHEN gm.can_manage = 1 THEN true ELSE false END) as can_manage_members
            FROM kdd_calendar_group_members gm
            JOIN kdd_users u ON gm.user_id = u.id
            WHERE gm.group_id = ? AND gm.is_deleted = 0";
            
            $memberStmt = $pdo->prepare($memberQuery);
            $memberStmt->execute([$group['id']]);
            $group['members'] = $memberStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        http_response_code(200);
        echo json_encode($groups);
        
    } catch (\PDOException $e) {
        error_log("DB error in getGroups: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Fehler beim Abrufen der Kalendergruppen."]);
    } catch (\Throwable $e) {
        error_log("Unexpected error in getGroups: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Unerwarteter Fehler beim Abrufen der Kalendergruppen."]);
    }
}

function createGroup(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validierung der Eingabedaten
        if (!$data || empty($data['name'])) {
            http_response_code(400);
            echo json_encode(["error" => "Gruppenname ist erforderlich"]);
            return;
        }

        // Validiere die Gruppenfarbe (Hex-Farbe)
        $color = $data['color'] ?? '#3788d8';
        if (!preg_match('/^#[a-f0-9]{6}$/i', $color)) {
            $color = '#3788d8'; // Fallback auf Standardfarbe
        }

        $pdo->beginTransaction();

        // Erstelle neue Gruppe
        $sql = "INSERT INTO kdd_calendar_groups 
                (authority_id, name, description, color, created_by)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $authorityId,
            $data['name'],
            $data['description'] ?? null,
            $color,
            $userId
        ]);

        $groupId = $pdo->lastInsertId();

        // Log group creation
        $changes = [
            ['column_name' => 'name', 'old_value' => null, 'new_value' => $data['name']],
            ['column_name' => 'description', 'old_value' => null, 'new_value' => $data['description'] ?? null],
            ['column_name' => 'color', 'old_value' => null, 'new_value' => $color],
            ['column_name' => 'created_by', 'old_value' => null, 'new_value' => $userId]
        ];
        logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_calendar_groups', $groupId, $userId, $changes);

        // Füge den Ersteller als Mitglied mit vollen Rechten hinzu
        $sql = "INSERT INTO kdd_calendar_group_members
                (group_id, user_id, authority_id, can_edit, can_delete, can_manage, is_deleted)
                VALUES (?, ?, ?, 1, 1, 1, 0)
                ON DUPLICATE KEY UPDATE
                can_edit = 1,
                can_delete = 1,
                can_manage = 1,
                is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId, $userId, $authorityId]);

        // Log creator member addition
        $memberId = $pdo->lastInsertId();
        if ($memberId) {
            $memberChanges = [
                ['column_name' => 'group_id', 'old_value' => null, 'new_value' => $groupId],
                ['column_name' => 'user_id', 'old_value' => null, 'new_value' => $userId],
                ['column_name' => 'can_edit', 'old_value' => null, 'new_value' => 1],
                ['column_name' => 'can_delete', 'old_value' => null, 'new_value' => 1],
                ['column_name' => 'can_manage', 'old_value' => null, 'new_value' => 1]
            ];
            logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_calendar_group_members', $memberId, $userId, $memberChanges);
        }

        // Füge weitere Mitglieder hinzu, falls vorhanden
        if (!empty($data['members'])) {
            $sql = "INSERT INTO kdd_calendar_group_members 
                    (group_id, user_id, authority_id, can_edit, can_delete, can_manage, is_deleted)
                    VALUES (?, ?, ?, ?, ?, ?, 0)
                    ON DUPLICATE KEY UPDATE 
                    can_edit = VALUES(can_edit), 
                    can_delete = VALUES(can_delete), 
                    can_manage = VALUES(can_manage),
                    is_deleted = 0";
            $stmt = $pdo->prepare($sql);
            
            foreach ($data['members'] as $member) {
                // Validiere Benutzer-ID
                if (!isset($member['user_id']) || !is_numeric($member['user_id'])) {
                    continue;
                }

                // Support both field naming conventions: can_manage and can_manage_members
                $canManage = isset($member['can_manage']) ? $member['can_manage'] :
                           (isset($member['can_manage_members']) ? $member['can_manage_members'] : false);

                $canEdit = $member['can_edit'] ?? false;
                $canDelete = $member['can_delete'] ?? false;

                $stmt->execute([
                    $groupId,
                    $member['user_id'],
                    $authorityId,
                    $canEdit,
                    $canDelete,
                    $canManage
                ]);

                // Log member addition
                $addedMemberId = $pdo->lastInsertId();
                if ($addedMemberId) {
                    $memberChanges = [
                        ['column_name' => 'group_id', 'old_value' => null, 'new_value' => $groupId],
                        ['column_name' => 'user_id', 'old_value' => null, 'new_value' => $member['user_id']],
                        ['column_name' => 'can_edit', 'old_value' => null, 'new_value' => $canEdit],
                        ['column_name' => 'can_delete', 'old_value' => null, 'new_value' => $canDelete],
                        ['column_name' => 'can_manage', 'old_value' => null, 'new_value' => $canManage]
                    ];
                    logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_calendar_group_members', $addedMemberId, $userId, $memberChanges);
                }
            }
        }

        $pdo->commit();
        
        // Hole die erstellte Gruppe mit allen Details
        $sql = "SELECT 
                    g.id,
                    g.name,
                    g.description,
                    g.color,
                    g.created_by,
                    g.created_at,
                    true as is_member,
                    true as can_manage,
                    1 as member_count
                FROM kdd_calendar_groups g
                WHERE g.id = ? AND g.authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId, $authorityId]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Hole die Mitglieder für die Gruppe
        if ($group) {
            $memberQuery = "SELECT 
                u.id as user_id,
                u.username,
                gm.can_edit,
                gm.can_delete,
                gm.can_manage
            FROM kdd_calendar_group_members gm
            JOIN kdd_users u ON gm.user_id = u.id
            WHERE gm.group_id = ? AND gm.is_deleted = 0";
            
            $memberStmt = $pdo->prepare($memberQuery);
            $memberStmt->execute([$group['id']]);
            $group['members'] = $memberStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Gruppe erfolgreich erstellt",
            "group" => $group
        ]);
        
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in createGroup: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Gruppe konnte nicht erstellt werden"]);
    }
}

function updateGroup(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validierung
        if (empty($data['id']) || empty($data['name'])) {
            http_response_code(400);
            echo json_encode(["error" => "Gruppen-ID und Name sind erforderlich"]);
            return;
        }

        // Prüfen, ob der Benutzer Berechtigungen hat, die Gruppe zu bearbeiten
        $permQuery = "SELECT COUNT(*) FROM kdd_calendar_group_members 
                      WHERE group_id = ? AND user_id = ? AND (can_edit = 1 OR can_manage = 1) AND is_deleted = 0";
        $permStmt = $pdo->prepare($permQuery);
        $permStmt->execute([$data['id'], $userId]);
        
        if ($permStmt->fetchColumn() == 0) {
            http_response_code(403);
            echo json_encode(["error" => "Keine Berechtigung zum Bearbeiten dieser Gruppe"]);
            return;
        }

        $pdo->beginTransaction();

        // Get old group data for logging
        $oldGroup = getEntryById($pdo, $data['id'], 'kdd_calendar_groups', $authorityId);

        // Gruppe aktualisieren
        $updateSql = "UPDATE kdd_calendar_groups SET
                      name = ?,
                      description = ?,
                      color = ?
                      WHERE id = ? AND is_deleted = 0 AND authority_id = ?";

        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            $data['name'],
            $data['description'] ?? '',
            $data['color'] ?? '#3788d8',
            $data['id'],
            $authorityId
        ]);

        // Log group update
        if ($oldGroup) {
            $changes = [];
            if ($oldGroup['name'] != $data['name']) {
                $changes[] = ['column_name' => 'name', 'old_value' => $oldGroup['name'], 'new_value' => $data['name']];
            }
            if ($oldGroup['description'] != ($data['description'] ?? '')) {
                $changes[] = ['column_name' => 'description', 'old_value' => $oldGroup['description'], 'new_value' => $data['description'] ?? ''];
            }
            if ($oldGroup['color'] != ($data['color'] ?? '#3788d8')) {
                $changes[] = ['column_name' => 'color', 'old_value' => $oldGroup['color'], 'new_value' => $data['color'] ?? '#3788d8'];
            }
            if (!empty($changes)) {
                logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_calendar_groups', $data['id'], $userId, $changes);
            }
        }

        // Get existing members before soft-deleting them
        $oldMembersQuery = "SELECT id, user_id, can_edit, can_delete, can_manage FROM kdd_calendar_group_members WHERE group_id = ? AND is_deleted = 0";
        $oldMembersStmt = $pdo->prepare($oldMembersQuery);
        $oldMembersStmt->execute([$data['id']]);
        $oldMembers = $oldMembersStmt->fetchAll(PDO::FETCH_ASSOC);

        // Bestehende Mitglieder als gelöscht markieren
        $deleteSql = "UPDATE kdd_calendar_group_members
                      SET is_deleted = 1
                      WHERE group_id = ?";
        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->execute([$data['id']]);

        // Log soft delete of each member
        foreach ($oldMembers as $oldMember) {
            $changes = [
                ['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]
            ];
            logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_calendar_group_members', $oldMember['id'], $userId, $changes);
        }

        // Neue Mitglieder hinzufügen
        if (!empty($data['members'])) {
            $memberSql = "INSERT INTO kdd_calendar_group_members 
                         (group_id, user_id, authority_id, can_edit, can_delete, can_manage, is_deleted) 
                         VALUES (?, ?, ?, ?, ?, ?, 0)
                         ON DUPLICATE KEY UPDATE 
                         can_edit = VALUES(can_edit), 
                         can_delete = VALUES(can_delete), 
                         can_manage = VALUES(can_manage),
                         is_deleted = 0";
            
            $memberStmt = $pdo->prepare($memberSql);
            
            foreach ($data['members'] as $member) {
                // Support both field naming conventions: can_manage and can_manage_members
                $canManage = isset($member['can_manage']) ? $member['can_manage'] :
                           (isset($member['can_manage_members']) ? $member['can_manage_members'] : false);

                $canEdit = $member['can_edit'] ?? 0;
                $canDelete = $member['can_delete'] ?? 0;

                $memberStmt->execute([
                    $data['id'],
                    $member['user_id'],
                    $authorityId,
                    $canEdit,
                    $canDelete,
                    $canManage
                ]);

                // Log member re-addition
                $newMemberId = $pdo->lastInsertId();
                if ($newMemberId) {
                    $memberChanges = [
                        ['column_name' => 'group_id', 'old_value' => null, 'new_value' => $data['id']],
                        ['column_name' => 'user_id', 'old_value' => null, 'new_value' => $member['user_id']],
                        ['column_name' => 'can_edit', 'old_value' => null, 'new_value' => $canEdit],
                        ['column_name' => 'can_delete', 'old_value' => null, 'new_value' => $canDelete],
                        ['column_name' => 'can_manage', 'old_value' => null, 'new_value' => $canManage],
                        ['column_name' => 'is_deleted', 'old_value' => null, 'new_value' => 0]
                    ];
                    logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_calendar_group_members', $newMemberId, $userId, $memberChanges);
                }
            }
        }

        $pdo->commit();
        
        // Hole die aktualisierte Gruppe mit allen Details
        $groupSql = "SELECT 
                    g.id,
                    g.name,
                    g.description,
                    g.color,
                    g.created_by,
                    g.created_at,
                    MAX(CASE WHEN gm.user_id = ? THEN 1 ELSE 0 END) as is_member,
                    MAX(CASE WHEN gm.user_id = ? THEN gm.can_manage ELSE 0 END) as can_manage,
                    COUNT(DISTINCT gm.user_id) as member_count
                FROM kdd_calendar_groups g
                LEFT JOIN kdd_calendar_group_members gm ON g.id = gm.group_id AND gm.is_deleted = 0
                WHERE g.id = ? AND g.is_deleted = 0 AND g.authority_id = ?
                GROUP BY g.id";
        
        $groupStmt = $pdo->prepare($groupSql);
        $groupStmt->execute([$userId, $userId, $data['id'], $authorityId]);
        $group = $groupStmt->fetch(PDO::FETCH_ASSOC);
        
        // Hole die Mitglieder für die Gruppe
        if ($group) {
            $memberQuery = "SELECT 
                u.id as user_id,
                u.username,
                gm.can_edit,
                gm.can_delete,
                gm.can_manage
            FROM kdd_calendar_group_members gm
            JOIN kdd_users u ON gm.user_id = u.id
            WHERE gm.group_id = ? AND gm.is_deleted = 0";
            
            $memberStmt = $pdo->prepare($memberQuery);
            $memberStmt->execute([$group['id']]);
            $group['members'] = $memberStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        http_response_code(200);
        echo json_encode($group);
        
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in updateGroup: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Fehler beim Aktualisieren der Gruppe: " . $e->getMessage()]);
    }
}

function deleteGroup(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    try {
        $data = getJsonRequestData();
        if (!$data || !isset($data['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid request data"]);
            return;
        }

        // Check if user has permission to delete this group
        $sql = "SELECT 1 FROM kdd_calendar_groups g
                LEFT JOIN kdd_calendar_group_members gm ON g.id = gm.group_id AND gm.user_id = ?
                WHERE g.id = ? AND g.authority_id = ? AND g.is_deleted = 0
                AND (g.created_by = ? OR (gm.user_id IS NOT NULL AND gm.can_delete = 1))";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $data['id'], $authorityId, $userId]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(["error" => "You don't have permission to delete this group"]);
            return;
        }

        $pdo->beginTransaction();

        // Get old group data for logging
        $oldGroup = getEntryById($pdo, $data['id'], 'kdd_calendar_groups', $authorityId);

        // Soft delete the group
        $sql = "UPDATE kdd_calendar_groups
                SET is_deleted = 1
                WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data['id'], $authorityId]);

        // Log group soft delete
        if ($oldGroup) {
            $changes = [
                ['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]
            ];
            logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_calendar_groups', $data['id'], $userId, $changes);
        }

        $pdo->commit();
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Calendar group deleted successfully"
        ]);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in deleteGroup: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete calendar group."]);
    }
}

function getGroupMembers(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    try {
        $groupId = $_GET['groupId'] ?? null;
        if (!$groupId) {
            http_response_code(400);
            echo json_encode(["error" => "Group ID is required"]);
            return;
        }

        // Check if user has access to this group
        $sql = "SELECT 1 FROM kdd_calendar_groups g
                LEFT JOIN kdd_calendar_group_members gm ON g.id = gm.group_id AND gm.user_id = ?
                WHERE g.id = ? AND g.authority_id = ? AND g.is_deleted = 0
                AND (g.created_by = ? OR gm.user_id IS NOT NULL)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $groupId, $authorityId, $userId]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(["error" => "You don't have access to this group"]);
            return;
        }

        // Get group members
        $sql = "SELECT u.id, u.username, gm.can_edit, gm.can_delete, gm.can_manage,
                CASE WHEN g.created_by = u.id THEN true ELSE false END as is_creator
                FROM kdd_calendar_group_members gm
                JOIN kdd_users u ON gm.user_id = u.id
                JOIN kdd_calendar_groups g ON gm.group_id = g.id
                WHERE gm.group_id = ? AND gm.authority_id = ?
                ORDER BY u.username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId, $authorityId]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($members);
    } catch (PDOException $e) {
        error_log("DB error in getGroupMembers: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve group members."]);
    }
}

function addGroupMember(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    try {
        $data = getJsonRequestData();
        if (!$data || !isset($data['groupId']) || !isset($data['memberId'])) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid request data"]);
            return;
        }

        // Check if user has permission to manage members
        $sql = "SELECT 1 FROM kdd_calendar_groups g
                LEFT JOIN kdd_calendar_group_members gm ON g.id = gm.group_id AND gm.user_id = ?
                WHERE g.id = ? AND g.authority_id = ? AND g.is_deleted = 0
                AND (g.created_by = ? OR (gm.user_id IS NOT NULL AND gm.can_manage = 1))";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $data['groupId'], $authorityId, $userId]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(["error" => "You don't have permission to manage members"]);
            return;
        }

        // Support both field naming conventions: can_manage and can_manage_members
        $canManage = isset($data['canManage']) ? $data['canManage'] :
                   (isset($data['canManageMembers']) ? $data['canManageMembers'] : false);

        $canEdit = $data['canEdit'] ?? false;
        $canDelete = $data['canDelete'] ?? false;

        // Add new member
        $sql = "INSERT INTO kdd_calendar_group_members (group_id, user_id, authority_id, can_edit, can_delete, can_manage)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['groupId'],
            $data['memberId'],
            $authorityId,
            $canEdit,
            $canDelete,
            $canManage
        ]);

        // Log member addition
        $newMemberId = $pdo->lastInsertId();
        $changes = [
            ['column_name' => 'group_id', 'old_value' => null, 'new_value' => $data['groupId']],
            ['column_name' => 'user_id', 'old_value' => null, 'new_value' => $data['memberId']],
            ['column_name' => 'can_edit', 'old_value' => null, 'new_value' => $canEdit],
            ['column_name' => 'can_delete', 'old_value' => null, 'new_value' => $canDelete],
            ['column_name' => 'can_manage', 'old_value' => null, 'new_value' => $canManage]
        ];
        logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_calendar_group_members', $newMemberId, $userId, $changes);

        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Member added successfully"
        ]);
    } catch (PDOException $e) {
        error_log("DB error in addGroupMember: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not add member to group."]);
    }
}

function removeGroupMember(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    try {
        $data = getJsonRequestData();
        if (!$data || !isset($data['groupId']) || !isset($data['memberId'])) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid request data"]);
            return;
        }

        // Check if user has permission to manage members
        $sql = "SELECT 1 FROM kdd_calendar_groups g
                LEFT JOIN kdd_calendar_group_members gm ON g.id = gm.group_id AND gm.user_id = ?
                WHERE g.id = ? AND g.authority_id = ? AND g.is_deleted = 0
                AND (g.created_by = ? OR (gm.user_id IS NOT NULL AND gm.can_manage = 1))";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $data['groupId'], $authorityId, $userId]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(["error" => "You don't have permission to manage members"]);
            return;
        }

        // Don't allow removing the creator
        $sql = "SELECT created_by FROM kdd_calendar_groups WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data['groupId'], $authorityId]);
        $creatorId = $stmt->fetchColumn();
        
        if ($creatorId == $data['memberId']) {
            http_response_code(400);
            echo json_encode(["error" => "Cannot remove the group creator"]);
            return;
        }

        // Get member data before deletion for logging
        $getMemberSql = "SELECT * FROM kdd_calendar_group_members WHERE group_id = ? AND user_id = ? AND authority_id = ?";
        $getMemberStmt = $pdo->prepare($getMemberSql);
        $getMemberStmt->execute([$data['groupId'], $data['memberId'], $authorityId]);
        $oldMember = $getMemberStmt->fetch(PDO::FETCH_ASSOC);

        // Remove member (HARD DELETE)
        $sql = "DELETE FROM kdd_calendar_group_members
                WHERE group_id = ? AND user_id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data['groupId'], $data['memberId'], $authorityId]);

        // Log hard delete
        if ($oldMember) {
            $changes = [
                ['column_name' => 'PERMANENT_DELETE', 'old_value' => json_encode($oldMember), 'new_value' => null]
            ];
            logDatabaseChange($authorityId, $pdo, 'DELETE', 'kdd_calendar_group_members', $oldMember['id'], $userId, $changes);
        }

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Member removed successfully"
        ]);
    } catch (PDOException $e) {
        error_log("DB error in removeGroupMember: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not remove member from group."]);
    }
}

function updateGroupMember(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    try {
        $data = getJsonRequestData();
        if (!$data || !isset($data['groupId']) || !isset($data['memberId'])) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid request data"]);
            return;
        }

        // Check if user has permission to manage members
        $sql = "SELECT 1 FROM kdd_calendar_groups g
                LEFT JOIN kdd_calendar_group_members gm ON g.id = gm.group_id AND gm.user_id = ?
                WHERE g.id = ? AND g.authority_id = ? AND g.is_deleted = 0
                AND (g.created_by = ? OR (gm.user_id IS NOT NULL AND gm.can_manage = 1))";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $data['groupId'], $authorityId, $userId]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(["error" => "You don't have permission to manage members"]);
            return;
        }

        // Don't allow modifying the creator's permissions
        $sql = "SELECT created_by FROM kdd_calendar_groups WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data['groupId'], $authorityId]);
        $creatorId = $stmt->fetchColumn();
        
        if ($creatorId == $data['memberId']) {
            http_response_code(400);
            echo json_encode(["error" => "Cannot modify the group creator's permissions"]);
            return;
        }

        // Support both field naming conventions: can_manage and can_manage_members
        $canManage = isset($data['canManage']) ? $data['canManage'] :
                   (isset($data['canManageMembers']) ? $data['canManageMembers'] : false);

        // Get old member data for logging
        $getMemberSql = "SELECT * FROM kdd_calendar_group_members WHERE group_id = ? AND user_id = ? AND authority_id = ?";
        $getMemberStmt = $pdo->prepare($getMemberSql);
        $getMemberStmt->execute([$data['groupId'], $data['memberId'], $authorityId]);
        $oldMember = $getMemberStmt->fetch(PDO::FETCH_ASSOC);

        $canEdit = $data['canEdit'] ?? false;
        $canDelete = $data['canDelete'] ?? false;

        // Update member permissions
        $sql = "UPDATE kdd_calendar_group_members
                SET can_edit = ?, can_delete = ?, can_manage = ?
                WHERE group_id = ? AND user_id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $canEdit,
            $canDelete,
            $canManage,
            $data['groupId'],
            $data['memberId'],
            $authorityId
        ]);

        // Log member permission update
        if ($oldMember) {
            $changes = [];
            if ($oldMember['can_edit'] != $canEdit) {
                $changes[] = ['column_name' => 'can_edit', 'old_value' => $oldMember['can_edit'], 'new_value' => $canEdit];
            }
            if ($oldMember['can_delete'] != $canDelete) {
                $changes[] = ['column_name' => 'can_delete', 'old_value' => $oldMember['can_delete'], 'new_value' => $canDelete];
            }
            if ($oldMember['can_manage'] != $canManage) {
                $changes[] = ['column_name' => 'can_manage', 'old_value' => $oldMember['can_manage'], 'new_value' => $canManage];
            }
            if (!empty($changes)) {
                logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_calendar_group_members', $oldMember['id'], $userId, $changes);
            }
        }

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Member permissions updated successfully"
        ]);
    } catch (PDOException $e) {
        error_log("DB error in updateGroupMember: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update member permissions."]);
    }
}


?>