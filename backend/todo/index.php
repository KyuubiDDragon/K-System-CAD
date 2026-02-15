<?php

/**
 * Backend Endpoint: todo/index.php
 * Handles CRUD operations for Todo Lists, Todos, Checkboxes, and Permissions.
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
    http_response_code(500);
    echo json_encode(["error" => "Authentication system error."]);
    exit();
}

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid token payload."]);
    exit();
}
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'todo')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to todo features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? ''; // Use $_REQUEST to handle GET/POST actions
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    'getTodoLists'                => ['module' => 'todo', 'action' => 'read'],
    'getTodos'                    => ['module' => 'todo', 'action' => 'read'],
    'getTodo'                     => ['module' => 'todo', 'action' => 'read'],
    'getAllTodos'                 => ['module' => 'todo', 'action' => 'read'],
    'getPermissions'              => ['module' => 'todo', 'action' => 'read'],
    'getTodosToday'               => ['module' => 'todo', 'action' => 'read'],
    'getTodos7Days'               => ['module' => 'todo', 'action' => 'read'],
    'getUsersWithTodoPermissions' => ['module' => 'todo', 'action' => 'read'],
    'createTodoList'              => ['module' => 'todo', 'action' => 'write'],
    'createTodo'                  => ['module' => 'todo', 'action' => 'write'],
    'addDashboardTodo'            => ['module' => 'todo', 'action' => 'write'],
    'createTodoCheckbox'          => ['module' => 'todo', 'action' => 'write'],
    'updateTodo'                  => ['module' => 'todo', 'action' => 'write'],
    'updatePermissions'           => ['module' => 'todo', 'action' => 'write'],
    'updateTodoCompletionStatus'  => ['module' => 'todo', 'action' => 'write'],
    'updateTodoListName'          => ['module' => 'todo', 'action' => 'write'],
    'updateTodoDescription'       => ['module' => 'todo', 'action' => 'write'],
    'updateTodoTitle'             => ['module' => 'todo', 'action' => 'write'],
    'updateTodoImportance'        => ['module' => 'todo', 'action' => 'write'],
    'updateTodoDueDate'           => ['module' => 'todo', 'action' => 'write'],
    'updateTodoAssignedUser'      => ['module' => 'todo', 'action' => 'write'],
    'updateCheckbox'              => ['module' => 'todo', 'action' => 'write'],
    'updateTodoCheckboxText'      => ['module' => 'todo', 'action' => 'write'],
    'deleteCheckbox'              => ['module' => 'todo', 'action' => 'delete'],
    'deleteTodo'                  => ['module' => 'todo', 'action' => 'delete'],
    'deleteTodoList'              => ['module' => 'todo', 'action' => 'delete'],
];

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
    // Pass $pdo, $userId, $authority to functions
    switch ($action) {
            // GET Actions
        case 'getTodoLists':
            if ($request_method === 'GET') getTodoLists($pdo, $userId, $authority);
            else MethodNotAllowed();
            break;
        case 'getTodos':
            if ($request_method === 'GET') getTodos($pdo, $userId, $authority);
            else MethodNotAllowed();
            break;
        case 'getAllTodos':
            if ($request_method === 'GET') getAllTodos($pdo, $userId, $authority);
            else MethodNotAllowed();
            break;
        case 'getTodo':
            if ($request_method === 'GET') getTodo($pdo, $userId, $authority);
            else MethodNotAllowed();
            break;
        case 'getPermissions':
            if ($request_method === 'GET') getPermissions($pdo, $userId, $authority);
            else MethodNotAllowed();
            break;
        case 'getTodosToday':
            if ($request_method === 'GET') getTodosToday($pdo, $userId, $authority);
            else MethodNotAllowed();
            break;
        case 'getTodos7Days':
            if ($request_method === 'GET') getTodos7Days($pdo, $userId, $authority);
            else MethodNotAllowed();
            break;
        case 'getUsersWithTodoPermissions':
            if ($request_method === 'GET') getUsersWithTodoPermissions($pdo, $userId, $authority);
            else MethodNotAllowed();
            break;

            // POST Actions (or PUT/DELETE)
        case 'createTodoList':
            if ($request_method === 'POST') createTodoList($pdo, $userId, $authority);
            else MethodNotAllowed();
            break;
        case 'createTodo':
            if ($request_method === 'POST') createTodo($pdo, $userId, $authority);
            else MethodNotAllowed();
            break;
        case 'addDashboardTodo':
            if ($request_method === 'POST') addDashboardTodo($pdo, $userId, $authority);
            else MethodNotAllowed();
            break;
        case 'createTodoCheckbox':
            if ($request_method === 'POST') createTodoCheckbox($pdo, $userId, $authority);
            else MethodNotAllowed();
            break;
        case 'updateTodo':
            if ($request_method === 'POST') updateTodo($pdo, $userId, $authority);
            else MethodNotAllowed();
            break; // Consider PUT
        case 'updatePermissions':
            if ($request_method === 'POST') updatePermissions($pdo, $userId, $authority);
            else MethodNotAllowed();
            break; // Consider PUT
        case 'updateTodoCompletionStatus':
            if ($request_method === 'POST') updateTodoCompletionStatus($pdo, $userId, $authority);
            else MethodNotAllowed();
            break; // Consider PUT/PATCH
        case 'updateTodoListName':
            if ($request_method === 'POST') updateTodoListName($pdo, $userId, $authority);
            else MethodNotAllowed();
            break; // Consider PUT
        case 'updateTodoDescription':
            if ($request_method === 'POST') updateTodoDescription($pdo, $userId, $authority);
            else MethodNotAllowed();
            break; // Consider PUT
        case 'updateTodoTitle':
            if ($request_method === 'POST') updateTodoTitle($pdo, $userId, $authority);
            else MethodNotAllowed();
            break; // Consider PUT
        case 'updateTodoImportance':
            if ($request_method === 'POST') updateTodoImportance($pdo, $userId, $authority);
            else MethodNotAllowed();
            break; // Consider PUT
        case 'updateTodoDueDate':
            if ($request_method === 'POST') updateTodoDueDate($pdo, $userId, $authority);
            else MethodNotAllowed();
            break; // Consider PUT
        case 'updateTodoAssignedUser':
            if ($request_method === 'POST') updateTodoAssignedUser($pdo, $userId, $authority);
            else MethodNotAllowed();
            break; // Consider PUT
        case 'updateCheckbox':
            if ($request_method === 'POST') updateCheckbox($pdo, $userId, $authority);
            else MethodNotAllowed();
            break; // Consider PUT/PATCH
        case 'updateTodoCheckboxText':
            if ($request_method === 'POST') updateTodoCheckboxText($pdo, $userId, $authority);
            else MethodNotAllowed();
            break; // Consider PUT
        case 'deleteTodo':
            if ($request_method === 'POST') deleteTodo($pdo, $userId, $authority);
            else MethodNotAllowed();
            break; // Consider DELETE
        case 'deleteTodoList':
            if ($request_method === 'POST') deleteTodoList($pdo, $userId, $authority);
            else MethodNotAllowed();
            break; // Consider DELETE
        case 'deleteCheckbox':
            if ($request_method === 'POST') deleteCheckbox($pdo, $userId, $authority);
            else MethodNotAllowed();
            break; // Consider DELETE

        default:
            http_response_code(500);
            echo json_encode(['error' => 'Action routing error.']);
            break;
    }
} else {
    http_response_code(403);
    echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



/**
 * Helper function to get authority ID from the authority name
 * 
 * @param PDO $pdo Database connection
 * @param string|null $authority Authority name
 * @return int|null Authority ID or null if not found
 */
function getAuthorityId(PDO $pdo, ?string $authority): ?int
{
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



// --- Function Implementations (PDO Refactored) ---

function getUsersWithTodoPermissions(PDO $pdo, int $userId, string $authority): void
{
    $listId = filter_var($_GET['list_id'] ?? null, FILTER_VALIDATE_INT); // Expect list_id via GET
    if (!$listId) {
        http_response_code(400);
        echo json_encode(['error' => 'List ID is required.']);
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

    try {
        // Check if the list or its parent is global
        $sqlIsGlobal = "SELECT COUNT(*) FROM `kdd_ta_todolists` 
                        WHERE authority_id = ? AND 
                        (id = ? OR id = (SELECT parent_list_id FROM `kdd_ta_todolists` WHERE authority_id = ? AND id = ?))
                        AND is_global = 1";
        $stmtIsGlobal = $pdo->prepare($sqlIsGlobal);
        $stmtIsGlobal->execute([$authorityId, $listId, $authorityId, $listId]);
        $isGlobal = (int) $stmtIsGlobal->fetchColumn() > 0;

        if ($isGlobal) {
            // If global, return all users for the current authority
            // Format as [ServiceNumber] [Name]
            $sqlUsers = "SELECT u.id, 
                              CASE 
                                WHEN e.servicenumber IS NOT NULL AND e.name IS NOT NULL 
                                THEN CONCAT('[', e.servicenumber, '] ', e.name) 
                                ELSE u.username 
                              END AS username
                       FROM kdd_users u
                       LEFT JOIN `kdd_employee` e ON e.id = u.linked_employee
                       WHERE u.authority = ? 
                       ORDER BY username";
            $stmtUsers = $pdo->prepare($sqlUsers);
            $stmtUsers->execute([$authority]);
            $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // If not global, fetch users with specific permissions on the list or its parent
            // Get parent ID first
            $stmtParent = $pdo->prepare("SELECT parent_list_id FROM `kdd_ta_todolists` WHERE authority_id = ? AND id = ?");
            $stmtParent->execute([$authorityId, $listId]);
            $parentId = $stmtParent->fetchColumn();
            $parentId = ($parentId !== false && $parentId !== null) ? (int)$parentId : null;

            // Format as [ServiceNumber] [Name]
            $sqlUsers = "SELECT DISTINCT u.id, 
                               CASE 
                                 WHEN e.servicenumber IS NOT NULL AND e.name IS NOT NULL 
                                 THEN CONCAT('[', e.servicenumber, '] ', e.name) 
                                 ELSE u.username 
                               END AS username
                        FROM kdd_users u
                        JOIN `kdd_ta_todolist_permissions` tp ON u.id = tp.user_id
                        LEFT JOIN `kdd_employee` e ON e.id = u.linked_employee
                        WHERE u.authority = ? AND (tp.list_id = ?";
            $params = [$authority, $listId];

            if ($parentId !== null) {
                $sqlUsers .= " OR tp.list_id = ?";
                $params[] = $parentId;
            }
            // Also include the list owner?
            $sqlUsers .= " OR u.id = (SELECT user_id FROM `kdd_ta_todolists` WHERE authority_id = ? AND id = ?))";
            $params[] = $authorityId;
            $params[] = $listId;

            $sqlUsers .= " ORDER BY username";
            $stmtUsers = $pdo->prepare($sqlUsers);
            $stmtUsers->execute($params);
            $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
        }

        http_response_code(200);
        echo json_encode($users);
    } catch (\PDOException $e) {
        error_log("DB error in getUsersWithTodoPermissions (List: $listId, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve users with permissions."]);
    }
}

/** Fetches Todo lists accessible by the user */
function getTodoLists(PDO $pdo, int $userId, string $authority): void
{
    // Get authority ID for proper querying
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    try {
        // Query checks direct permission, parent permission, ownership, or global lists
        $sql = "SELECT DISTINCT tl.* FROM `kdd_ta_todolists` tl 
                 LEFT JOIN `kdd_ta_todolist_permissions` tp ON tl.id = tp.list_id
                 LEFT JOIN `kdd_ta_todolists` parent ON tl.parent_list_id = parent.id
                 LEFT JOIN `kdd_ta_todolist_permissions` tpp ON parent.id = tpp.list_id
                 WHERE tl.authority_id = ? AND tl.is_deleted = 0
                 AND (
                        tp.user_id = ?
                     OR tpp.user_id = ?
                     OR tl.user_id = ?
                     OR tl.is_global = 1
                     OR parent.is_global = 1
                 )
                 ORDER BY tl.name ASC"; // Add ordering
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $userId, $userId, $userId]); // Use positional parameters
        $lists = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($lists);
    } catch (\PDOException $e) {
        error_log("DB error in getTodoLists (User: $userId, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve todo lists."]);
    }
}

/** Fetches Todos for a specific list, including checkboxes and assigned users */
function getTodos(PDO $pdo, int $userId, string $authority): void
{
    $listId = filter_var($_GET['list_id'] ?? null, FILTER_VALIDATE_INT); // Get list_id from GET
    if (!$listId) {
        http_response_code(400);
        echo json_encode(['error' => 'List ID is required.']);
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

    $todos = [];
    $todosById = [];
    $todoIds = [];

    try {
        // 1. Check if user has access to this list (re-use logic from getTodoLists but simplified for one list)
        $sqlAccess = "SELECT 1 FROM `kdd_ta_todolists` tl
                       LEFT JOIN `kdd_ta_todolist_permissions` tp ON tl.id = tp.list_id
                       LEFT JOIN `kdd_ta_todolists` parent ON tl.parent_list_id = parent.id
                       LEFT JOIN `kdd_ta_todolist_permissions` tpp ON parent.id = tpp.list_id
                       WHERE tl.authority_id = ? AND tl.id = ? AND tl.is_deleted = 0
                       AND (tp.user_id = ? OR tpp.user_id = ? OR tl.user_id = ? OR tl.is_global = 1 OR parent.is_global = 1)
                       LIMIT 1";
        $stmtAccess = $pdo->prepare($sqlAccess);
        $stmtAccess->execute([$authorityId, $listId, $userId, $userId, $userId]);
        if ($stmtAccess->fetchColumn() === false) {
            http_response_code(403);
            echo json_encode(["error" => "Access denied to this todo list."]);
            return;
        }

        // 2. Fetch Todos for the list
        $sqlTodos = "SELECT t.*,
                            (SELECT COUNT(*) FROM `kdd_ta_todo_checkboxes` c WHERE c.todo_id = t.id) AS all_checkboxes_count,
                            (SELECT COUNT(*) FROM `kdd_ta_todo_checkboxes` c WHERE c.todo_id = t.id AND c.completed = 1) AS completed_checkboxes_count
                     FROM kdd_ta_todos t
                     WHERE t.authority_id = ? AND t.list_id = ?
                     ORDER BY IFNULL(t.due_date, '9999-12-31') ASC, t.id ASC";
        $stmtTodos = $pdo->prepare($sqlTodos);
        $stmtTodos->execute([$authorityId, $listId]);
        $todoRows = $stmtTodos->fetchAll(PDO::FETCH_ASSOC);

        if (empty($todoRows)) {
            http_response_code(200);
            echo json_encode([]);
            return;
        }

        // Prepare todos array and collect IDs
        foreach ($todoRows as $row) {
            $todoId = $row['id'];
            $row['completed'] = (bool)$row['completed'];
            $row['all_checkboxes_count'] = (int)$row['all_checkboxes_count'];
            $row['completed_checkboxes_count'] = (int)$row['completed_checkboxes_count'];
            $row['checkboxes'] = [];
            $row['assigned_users'] = []; // Will hold user IDs
            $todosById[$todoId] = $row;
            $todoIds[] = $todoId;
        }

        // 3. Fetch Checkboxes for these Todos
        $placeholders = rtrim(str_repeat('?,', count($todoIds)), ',');
        $params = array_merge([$authorityId], $todoIds);
        $sqlCheckboxes = "SELECT id, todo_id, title, completed FROM `kdd_ta_todo_checkboxes` 
                          WHERE authority_id = ? AND todo_id IN ({$placeholders}) ORDER BY id";
        $stmtCheckboxes = $pdo->prepare($sqlCheckboxes);
        $stmtCheckboxes->execute($params);
        while ($row = $stmtCheckboxes->fetch(PDO::FETCH_ASSOC)) {
            if (isset($todosById[$row['todo_id']])) {
                $todosById[$row['todo_id']]['checkboxes'][] = [
                    'id' => (int)$row['id'],
                    'title' => $row['title'],
                    'completed' => (bool)$row['completed']
                ];
            }
        }

        // 4. Fetch Assigned Users for these Todos
        $sqlAssigned = "SELECT todo_id, user_id FROM `kdd_ta_todo_assigned_users` 
                         WHERE authority_id = ? AND todo_id IN ({$placeholders})";
        $stmtAssigned = $pdo->prepare($sqlAssigned);
        $stmtAssigned->execute($params);
        while ($row = $stmtAssigned->fetch(PDO::FETCH_ASSOC)) {
            if (isset($todosById[$row['todo_id']])) {
                // Store only user IDs for simplicity, frontend can map to names if needed
                $todosById[$row['todo_id']]['assigned_users'][] = (int)$row['user_id'];
            }
        }

        http_response_code(200);
        echo json_encode(array_values($todosById)); // Return indexed array

    } catch (\PDOException $e) {
        error_log("DB error in getTodos (List: $listId, User: $userId, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve todos."]);
    }
}

/** Fetches all incomplete Todos for the current user across all accessible lists */
function getAllTodos(PDO $pdo, int $userId, string $authority): void
{
    // Get authority ID for proper querying
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    try {
        // Query to get all incomplete todos that the user has access to
        // Note: kdd_ta_todos doesn't have is_deleted column, only kdd_ta_todolists does
        $sql = "SELECT t.id, t.title, t.completed, t.due_date, t.list_id,
                       tl.name as list_name
                FROM kdd_ta_todos t
                JOIN `kdd_ta_todolists` tl ON t.list_id = tl.id
                LEFT JOIN `kdd_ta_todolists` parent ON tl.parent_list_id = parent.id
                LEFT JOIN `kdd_ta_todolist_permissions` tp ON tl.id = tp.list_id OR tl.parent_list_id = tp.list_id
                LEFT JOIN `kdd_ta_todo_assigned_users` tau ON t.id = tau.todo_id
                WHERE t.authority_id = ? AND tl.authority_id = ?
                  AND tl.is_deleted = 0
                  AND t.completed = 0
                  AND (tp.user_id = ? OR tl.user_id = ? OR tl.is_global = 1 OR parent.is_global = 1 OR tau.user_id = ?)
                GROUP BY t.id
                ORDER BY IFNULL(t.due_date, '9999-12-31') ASC, t.id ASC
                LIMIT 50"; // Limit to 50 for widget performance
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $authorityId, $userId, $userId, $userId]);
        $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format todos for the widget
        $formattedTodos = [];
        foreach ($todos as $todo) {
            $formattedTodos[] = [
                'id' => (int)$todo['id'],
                'title' => $todo['title'],
                'completed' => (bool)$todo['completed'],
                'due_date' => $todo['due_date'],
                'list_name' => $todo['list_name']
            ];
        }

        http_response_code(200);
        echo json_encode(['todos' => $formattedTodos]);
    } catch (\PDOException $e) {
        error_log("DB error in getAllTodos (User: $userId, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve todos."]);
    }
}

/** Fetches Todos due today */
function getTodosToday(PDO $pdo, int $userId, string $authority): void
{
    // Get authority ID for proper querying
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    try {
        // Simpler query focusing only on todos accessible by the user and due today
        $sql = "SELECT t.id, t.list_id, t.due_date, t.completed, t.title AS TodoPoint,
                       tl.name as SubTodo, t.importance, parent.name AS MainTodo
                FROM kdd_ta_todos t
                JOIN `kdd_ta_todolists` tl ON t.list_id = tl.id
                LEFT JOIN `kdd_ta_todolists` parent ON tl.parent_list_id = parent.id
                LEFT JOIN `kdd_ta_todolist_permissions` tp ON tl.id = tp.list_id OR tl.parent_list_id = tp.list_id
                WHERE t.authority_id = ? AND tl.authority_id = ?
                  AND tl.is_deleted = 0
                  AND (tp.user_id = ? OR tl.user_id = ? OR tl.is_global = 1 OR parent.is_global = 1)
                  AND DATE(t.due_date) = CURDATE() AND t.completed = 0
                GROUP BY t.id
                ORDER BY tl.name, t.title";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $authorityId, $userId, $userId]);
        $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($todos);
    } catch (\PDOException $e) {
        error_log("DB error in getTodosToday (User: $userId, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve today's todos."]);
    }
}

/** Fetches Todos due in the next 7 days (excluding today) */
function getTodos7Days(PDO $pdo, int $userId, string $authority): void
{
    // Get authority ID for proper querying
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    try {
        $sql = "SELECT t.id, t.list_id, t.due_date, t.completed, t.title AS TodoPoint,
                       tl.name as SubTodo, t.importance, parent.name AS MainTodo
                FROM kdd_ta_todos t
                JOIN `kdd_ta_todolists` tl ON t.list_id = tl.id
                LEFT JOIN `kdd_ta_todolists` parent ON tl.parent_list_id = parent.id
                LEFT JOIN `kdd_ta_todolist_permissions` tp ON tl.id = tp.list_id OR tl.parent_list_id = tp.list_id
                WHERE t.authority_id = ? AND tl.authority_id = ?
                  AND tl.is_deleted = 0
                  AND (tp.user_id = ? OR tl.user_id = ? OR tl.is_global = 1 OR parent.is_global = 1)
                  AND DATE(t.due_date) > CURDATE() AND DATE(t.due_date) <= CURDATE() + INTERVAL 7 DAY
                  AND t.completed = 0
                GROUP BY t.id
                ORDER BY t.due_date ASC, tl.name, t.title";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $authorityId, $userId, $userId]);
        $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($todos);
    } catch (\PDOException $e) {
        error_log("DB error in getTodos7Days (User: $userId, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve upcoming todos."]);
    }
}

/** Fetches a single Todo by ID, checking permissions */
function getTodo(PDO $pdo, int $userId, string $authority): void
{
    $todoId = filter_var($_GET['todo_id'] ?? null, FILTER_VALIDATE_INT); // Expect ID via GET
    if (!$todoId) {
        http_response_code(400);
        echo json_encode(['error' => 'Todo ID is required.']);
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

    try {
        // Query joins necessary tables to check permissions and get details
        $sql = "SELECT t.* FROM `kdd_ta_todos` t
                JOIN `kdd_ta_todolists` tl ON t.list_id = tl.id
                LEFT JOIN `kdd_ta_todolists` parent ON tl.parent_list_id = parent.id
                LEFT JOIN `kdd_ta_todolist_permissions` tp ON tl.id = tp.list_id OR tl.parent_list_id = tp.list_id
                WHERE t.authority_id = ? AND tl.authority_id = ?
                  AND t.id = ? AND tl.is_deleted = 0
                  AND (tp.user_id = ? OR tl.user_id = ? OR tl.is_global = 1 OR parent.is_global = 1)
                LIMIT 1"; // Limit 1 as we only need one row + permission check
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $authorityId, $todoId, $userId, $userId]);
        $todo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($todo) {
            $todo['completed'] = (bool)$todo['completed'];
            // Fetch checkboxes and assigned users separately if needed for detail view
            // ... (logic similar to getTodos but filtered for this todoId) ...
            $todo['checkboxes'] = []; // Placeholder
            $todo['assigned_users'] = []; // Placeholder

            http_response_code(200);
            echo json_encode($todo);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Todo not found or access denied."]);
        }
    } catch (\PDOException $e) {
        error_log("DB error in getTodo (ID: $todoId, User: $userId, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve todo."]);
    }
}

/** Fetches user permissions for a specific list */
function getPermissions(PDO $pdo, int $userId, string $authority): void
{
    $listId = filter_var($_GET['list_id'] ?? null, FILTER_VALIDATE_INT); // Expect ID via GET
    if (!$listId) {
        http_response_code(400);
        echo json_encode(['error' => 'List ID is required.']);
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

    try {
        // Fetch permissions for the list, potentially joining user table for names
        $sql = "SELECT tp.id, tp.user_id, tp.can_write, u.username 
                FROM `kdd_ta_todolist_permissions` tp 
                JOIN `kdd_users` u ON tp.user_id = u.id
                WHERE tp.authority_id = ? AND tp.list_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $listId]);
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert can_write to boolean
        $permissions = array_map(function ($p) {
            $p['can_write'] = (bool)$p['can_write'];
            return $p;
        }, $permissions);

        http_response_code(200);
        echo json_encode($permissions);
    } catch (\PDOException $e) {
        error_log("DB error in getPermissions (List: $listId, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve permissions."]);
    }
}

/** Creates a new Todo list */
function createTodoList(PDO $pdo, int $requestingUserId, string $authority): void
{
    $data = getJsonRequestData();
    $name = $data['name'] ?? null;
    $parentListId = filter_var($data['parent_list_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    $is_global = filter_var($data['is_global'] ?? false, FILTER_VALIDATE_BOOLEAN); // Add global flag

    if (empty(trim($name ?? ''))) {
        http_response_code(400);
        echo json_encode(['error' => 'List name is required.']);
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

    try {
        // Ensure parentListId exists if provided? Optional check.

        $sql = "INSERT INTO `kdd_ta_todolists` (authority_id, user_id, parent_list_id, name, is_global) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $requestingUserId, $parentListId, $name, $is_global ? 1 : 0]);

        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Todo list created.', 'list_id' => $newId]);
            // Log change
            $changes = [['column_name' => 'name', 'old_value' => null, 'new_value' => $name]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "ta_todolists", $newId, $requestingUserId, $changes);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create todo list.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in createTodoList ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not create todo list."]);
    }
}

/** Creates a new Todo item */
function createTodo(PDO $pdo, int $requestingUserId, string $authority): void
{
    $data = getJsonRequestData();

    $listId = filter_var($data['list_id'] ?? null, FILTER_VALIDATE_INT);
    $title = $data['title'] ?? null;
    $description = $data['description'] ?? "";
    $importance = $data['importance'] ?? "low"; // Default importance
    $dueDate = !empty($data['due_date']) ? date('Y-m-d', strtotime($data['due_date'])) : null;
    $parentTodoId = filter_var($data['parent_todo_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    // Add assigned users if provided
    $assignedUsers = isset($data['assigned_users']) && is_array($data['assigned_users']) ? array_map('intval', $data['assigned_users']) : [];

    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }



    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO `kdd_ta_todos` (authority_id, user_id, list_id, parent_todo_id, title, description, importance, due_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $requestingUserId, $listId, $parentTodoId, $title, $description, $importance, $dueDate]);
        if (!$success) throw new \Exception("Failed to insert todo.");
        $todoId = $pdo->lastInsertId();

        // Log todo creation
        $changes = [
            ['column_name' => 'list_id', 'old_value' => null, 'new_value' => $listId],
            ['column_name' => 'parent_todo_id', 'old_value' => null, 'new_value' => $parentTodoId],
            ['column_name' => 'title', 'old_value' => null, 'new_value' => $title],
            ['column_name' => 'description', 'old_value' => null, 'new_value' => $description],
            ['column_name' => 'importance', 'old_value' => null, 'new_value' => $importance],
            ['column_name' => 'due_date', 'old_value' => null, 'new_value' => $dueDate]
        ];
        logDatabaseChange($authorityId, $pdo, 'INSERT', "ta_todos", $todoId, $requestingUserId, $changes);

        // Assign users
        if (!empty($assignedUsers)) {
            $sqlAssign = "INSERT INTO `kdd_ta_todo_assigned_users` (authority_id, todo_id, user_id) VALUES (?, ?, ?)";
            $stmtAssign = $pdo->prepare($sqlAssign);
            foreach ($assignedUsers as $assignUserId) {
                if ($assignUserId > 0) {
                    $stmtAssign->execute([$authorityId, $todoId, $assignUserId]);
                    $assignId = $pdo->lastInsertId();

                    // Log user assignment
                    $assignChanges = [
                        ['column_name' => 'todo_id', 'old_value' => null, 'new_value' => $todoId],
                        ['column_name' => 'user_id', 'old_value' => null, 'new_value' => $assignUserId]
                    ];
                    logDatabaseChange($authorityId, $pdo, 'INSERT', "ta_todo_assigned_users", $assignId, $requestingUserId, $assignChanges);
                }
            }
        }

        $pdo->commit();
        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Todo created successfully.', 'todo_id' => $todoId]);

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in createTodo (List: $listId, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not create todo: " . $e->getMessage()]);
    }
}

/** Creates a todo in the user's personal "Dashboard Tasks" list (creates list if needed) */
function addDashboardTodo(PDO $pdo, int $requestingUserId, string $authority): void
{
    $data = getJsonRequestData();

    $title = $data['title'] ?? null;
    $dueDate = !empty($data['due_date']) ? date('Y-m-d', strtotime($data['due_date'])) : null;

    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    if (empty(trim($title ?? ''))) {
        http_response_code(400);
        echo json_encode(['error' => 'Todo title is required.']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Check if user has a "Dashboard Tasks" list
        $sqlCheckList = "SELECT id FROM `kdd_ta_todolists`
                         WHERE authority_id = ? AND user_id = ? AND name = 'Dashboard Tasks'
                         AND is_deleted = 0 LIMIT 1";
        $stmtCheckList = $pdo->prepare($sqlCheckList);
        $stmtCheckList->execute([$authorityId, $requestingUserId]);
        $listId = $stmtCheckList->fetchColumn();

        // Create list if it doesn't exist
        if (!$listId) {
            $sqlCreateList = "INSERT INTO `kdd_ta_todolists` (authority_id, user_id, parent_list_id, name, is_global)
                              VALUES (?, ?, NULL, 'Dashboard Tasks', 0)";
            $stmtCreateList = $pdo->prepare($sqlCreateList);
            $stmtCreateList->execute([$authorityId, $requestingUserId]);
            $listId = $pdo->lastInsertId();

            // Log list creation
            $changes = [['column_name' => 'name', 'old_value' => null, 'new_value' => 'Dashboard Tasks']];
            logDatabaseChange($authorityId, $pdo, 'INSERT', "ta_todolists", $listId, $requestingUserId, $changes);
        }

        // Insert the todo
        $sqlInsertTodo = "INSERT INTO `kdd_ta_todos` (authority_id, user_id, list_id, parent_todo_id, title, description, importance, due_date)
                          VALUES (?, ?, ?, NULL, ?, '', 'low', ?)";
        $stmtInsertTodo = $pdo->prepare($sqlInsertTodo);
        $success = $stmtInsertTodo->execute([$authorityId, $requestingUserId, $listId, $title, $dueDate]);

        if (!$success) {
            throw new \Exception("Failed to insert todo.");
        }

        $todoId = $pdo->lastInsertId();

        // Log todo creation
        $changes = [
            ['column_name' => 'list_id', 'old_value' => null, 'new_value' => $listId],
            ['column_name' => 'title', 'old_value' => null, 'new_value' => $title],
            ['column_name' => 'due_date', 'old_value' => null, 'new_value' => $dueDate]
        ];
        logDatabaseChange($authorityId, $pdo, 'INSERT', "ta_todos", $todoId, $requestingUserId, $changes);

        $pdo->commit();
        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Todo added successfully.', 'todo_id' => $todoId]);

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in addDashboardTodo (Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not add todo: " . $e->getMessage()]);
    }
}

/** Creates a checkbox for a todo item */
function createTodoCheckbox(PDO $pdo, int $requestingUserId, string $authority): void
{
    $data = getJsonRequestData();

    $todoId = filter_var($data['todo_id'] ?? null, FILTER_VALIDATE_INT);
    $title = $data['title'] ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;


    if (!$todoId || empty(trim($title ?? ''))) {
        // Get authority ID

        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        http_response_code(400);
        echo json_encode(['error' => 'Todo ID and checkbox title are required.']);
        return;
    }

    try {
        $sql = "INSERT INTO `kdd_ta_todo_checkboxes` (authority_id, todo_id, title) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $todoId, $title]);

        if ($success) {
            $checkbox_id = $pdo->lastInsertId();
            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Checkbox added.', 'checkbox_id' => $checkbox_id]);

            // Log checkbox creation
            $changes = [
                ['column_name' => 'todo_id', 'old_value' => null, 'new_value' => $todoId],
                ['column_name' => 'title', 'old_value' => null, 'new_value' => $title]
            ];
            logDatabaseChange($authorityId, $pdo, 'INSERT', "ta_todo_checkboxes", $checkbox_id, $requestingUserId, $changes);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add checkbox.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in createTodoCheckbox (Todo: $todoId, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not add checkbox."]);
    }
}


// --- UPDATE Functions ---

/** Generic update function for single todo fields */
function updateTodoField(PDO $pdo, int $requestingUserId, string $authority, string $field, $allowedValues = null): void
{
    $data = getJsonRequestData();

    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $value = $data[$field] ?? null; // Get value by field name
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;

    if (!$id) {
        // Get authority ID
        global $decoded;
        
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        http_response_code(400);
        echo json_encode(['error' => 'Todo ID is required.']);
        return;
    }
    if ($value === null) {
        http_response_code(400);
        echo json_encode(['error' => "Value for field '{$field}' is required."]);
        return;
    }

    // Validate value if a list of allowed values is provided
    if (is_array($allowedValues) && !in_array($value, $allowedValues)) {
        http_response_code(400);
        echo json_encode(['error' => "Invalid value provided for field '{$field}'."]);
        return;
    }
    // Sanitize/Format specific fields
    if ($field == 'due_date') {
        $value = !empty($value) ? date('Y-m-d', strtotime($value)) : null;
    } elseif ($field === 'completed') {
        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    try {
        // Get old value for logging
        $sqlOld = "SELECT `{$field}` FROM `kdd_ta_todos` WHERE authority_id = ? AND id = ?";
        $stmtOld = $pdo->prepare($sqlOld);
        $stmtOld->execute([$authorityId, $id]);
        $oldValue = $stmtOld->fetchColumn();

        // Use backticks for safety, although field name comes from code here
        $sql = "UPDATE `kdd_ta_todos` SET `{$field}` = ? WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        // Bind dynamically based on type (simplified: assumes string or int/bool)
        $paramType = (is_int($value) || is_bool($value)) ? PDO::PARAM_INT : PDO::PARAM_STR;
        if ($value === null) $paramType = PDO::PARAM_NULL;
        $stmt->bindValue(1, $value, $paramType);
        $stmt->bindValue(2, $authorityId, PDO::PARAM_INT);
        $stmt->bindValue(3, $id, PDO::PARAM_INT);
        $success = $stmt->execute();

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Todo field '{$field}' updated."]);

            // Log change
            if ($oldValue != $value) {
                $changes = [
                    ['column_name' => $field, 'old_value' => $oldValue, 'new_value' => $value]
                ];
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "ta_todos", $id, $requestingUserId, $changes);
            }
        } elseif ($success) {
            http_response_code(200);
            echo json_encode(["error" => "No changes made to todo field '{$field}'."]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => "Failed to update todo field '{$field}'."]);
        }
    } catch (\PDOException $e) {
        error_log("DB error updating field {$field} for todo $id (Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update todo field '{$field}'."]);
    }
}

function updateTodo(PDO $pdo, int $userId, string $authority): void
{ /* Placeholder - Use specific update functions */
    http_response_code(501);
    echo json_encode(['error' => 'Deprecated: Use specific update actions.']);
}
function updateTodoCompletionStatus(PDO $pdo, int $userId, string $authority): void
{
    updateTodoField($pdo, $userId, $authority, 'completed');
}
function updateTodoDescription(PDO $pdo, int $userId, string $authority): void
{
    updateTodoField($pdo, $userId, $authority, 'description');
}
function updateTodoTitle(PDO $pdo, int $userId, string $authority): void
{
    updateTodoField($pdo, $userId, $authority, 'title');
}
function updateTodoImportance(PDO $pdo, int $userId, string $authority): void
{
    updateTodoField($pdo, $userId, $authority, 'importance', ['low', 'medium', 'high']);
} // Example validation
function updateTodoDueDate(PDO $pdo, int $userId, string $authority): void
{
    updateTodoField($pdo, $userId, $authority, 'due_date');
}

function updateTodoListName(PDO $pdo, int $requestingUserId, string $authority): void
{
    $data = getJsonRequestData();

    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $name = $data['name'] ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;

    if (!$id || empty(trim($name ?? ''))) {
        // Get authority ID

        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        http_response_code(400);
        echo json_encode(['error' => 'List ID and name are required.']);
        return;
    }

    try {
        // Get old name for logging
        $sqlOld = "SELECT name FROM `kdd_ta_todolists` WHERE authority_id = ? AND id = ? AND is_deleted = 0";
        $stmtOld = $pdo->prepare($sqlOld);
        $stmtOld->execute([$authorityId, $id]);
        $oldName = $stmtOld->fetchColumn();

        $sql = "UPDATE `kdd_ta_todolists` SET name = ?, updated_by = ?, updated_at = NOW() WHERE authority_id = ? AND id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$name, $requestingUserId, $authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Todo list name updated."]);

            // Log change
            if ($oldName != $name) {
                $changes = [
                    ['column_name' => 'name', 'old_value' => $oldName, 'new_value' => $name]
                ];
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "ta_todolists", $id, $requestingUserId, $changes);
            }
        } elseif ($success) {
            http_response_code(200);
            echo json_encode(["error" => "No changes made."]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update list name.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in updateTodoListName (ID: $id, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update list name."]);
    }
}


function updatePermissions(PDO $pdo, int $requestingUserId, string $authority): void
{
    $data = getJsonRequestData();

    $listId = filter_var($data['list_id'] ?? null, FILTER_VALIDATE_INT);
    $permissions = $data['permissions'] ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;


    if (!$listId || !is_array($permissions)) {
        // Get authority ID

        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        http_response_code(400);
        echo json_encode(['error' => 'List ID and permissions array are required.']);
        return;
    }

    try {
        // TODO: Verify requesting user has permission to *change* permissions for this list (e.g., is owner or admin)

        $pdo->beginTransaction();

        // Get old permissions for logging
        $sqlGetOld = "SELECT * FROM kdd_ta_todolist_permissions WHERE list_id = ?";
        $stmtGetOld = $pdo->prepare($sqlGetOld);
        $stmtGetOld->execute([$listId]);
        $oldPermissions = $stmtGetOld->fetchAll(PDO::FETCH_ASSOC);

        // 1. Delete existing permissions for this list
        $sqlDelete = "DELETE FROM kdd_ta_todolist_permissions WHERE list_id = ?";
        $stmtDelete = $pdo->prepare($sqlDelete);
        if (!$stmtDelete->execute([$listId])) throw new \Exception("Failed to delete old permissions.");

        // Log deleted permissions
        foreach ($oldPermissions as $oldPerm) {
            $changes = [
                ['column_name' => 'PERMANENT_DELETE', 'old_value' => json_encode($oldPerm), 'new_value' => null]
            ];
            logDatabaseChange($authorityId, $pdo, 'DELETE', "ta_todolist_permissions", $oldPerm['id'], $requestingUserId, $changes);
        }

        // 2. Insert new permissions
        $sqlInsert = "INSERT INTO `kdd_ta_todolist_permissions` (authority_id, list_id, user_id, can_write, granted_by, granted_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmtInsert = $pdo->prepare($sqlInsert);
        foreach ($permissions as $perm) {
            $permUserId = filter_var($perm['user_id'] ?? null, FILTER_VALIDATE_INT);
            $canWrite = filter_var($perm['can_write'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($permUserId) { // Only insert if user ID is valid
                if (!$stmtInsert->execute([$authorityId, $listId, $permUserId, $canWrite ? 1 : 0, $requestingUserId])) {
                    throw new \Exception("Failed to insert permission for user ID: {$permUserId}");
                }

                // Log new permission
                $permId = $pdo->lastInsertId();
                $changes = [
                    ['column_name' => 'list_id', 'old_value' => null, 'new_value' => $listId],
                    ['column_name' => 'user_id', 'old_value' => null, 'new_value' => $permUserId],
                    ['column_name' => 'can_write', 'old_value' => null, 'new_value' => $canWrite ? 1 : 0]
                ];
                logDatabaseChange($authorityId, $pdo, 'INSERT', "ta_todolist_permissions", $permId, $requestingUserId, $changes);
            }
        }

        $pdo->commit();
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Permissions updated successfully."]);

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in updatePermissions (List: $listId, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update permissions: " . $e->getMessage()]);
    }
}


function updateTodoAssignedUser(PDO $pdo, int $requestingUserId, string $authority): void
{
    $data = getJsonRequestData();

    $todoId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    // Expect an array of user IDs
    $assignedUsers = isset($data['assigned_users']) && is_array($data['assigned_users']) ? array_map('intval', $data['assigned_users']) : [];
    // Filter out non-positive IDs
    $assignedUsers = array_filter($assignedUsers, fn($id) => $id > 0);
    $assignedUsers = array_unique($assignedUsers); // Ensure uniqueness
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;


    if (!$todoId) {
        // Get authority ID
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        http_response_code(400);
        echo json_encode(['error' => 'Todo ID is required.']);
        return;
    }
    // Note: $assignedUsers can be empty to remove all assignments

    try {
        $pdo->beginTransaction();

        // Get old assignments for logging
        $sqlGetOld = "SELECT * FROM kdd_ta_todo_assigned_users WHERE todo_id = ?";
        $stmtGetOld = $pdo->prepare($sqlGetOld);
        $stmtGetOld->execute([$todoId]);
        $oldAssignments = $stmtGetOld->fetchAll(PDO::FETCH_ASSOC);

        // Delete existing assignments
        $sqlDelete = "DELETE FROM kdd_ta_todo_assigned_users WHERE todo_id = ?";
        $stmtDelete = $pdo->prepare($sqlDelete);
        if (!$stmtDelete->execute([$todoId])) throw new \Exception("Failed to delete old assignments.");

        // Log deleted assignments
        foreach ($oldAssignments as $oldAssign) {
            $changes = [
                ['column_name' => 'PERMANENT_DELETE', 'old_value' => json_encode($oldAssign), 'new_value' => null]
            ];
            logDatabaseChange($authorityId, $pdo, 'DELETE', "ta_todo_assigned_users", $oldAssign['id'], $requestingUserId, $changes);
        }

        // Add new assignments
        if (!empty($assignedUsers)) {
            $sqlInsert = "INSERT INTO `kdd_ta_todo_assigned_users` (authority_id, todo_id, user_id) VALUES (?, ?, ?)";
            $stmtInsert = $pdo->prepare($sqlInsert);
            foreach ($assignedUsers as $assignUserId) {
                if (!$stmtInsert->execute([$authorityId, $todoId, $assignUserId])) throw new \Exception("Failed to insert assignment for user ID: {$assignUserId}");

                // Log new assignment
                $assignId = $pdo->lastInsertId();
                $changes = [
                    ['column_name' => 'todo_id', 'old_value' => null, 'new_value' => $todoId],
                    ['column_name' => 'user_id', 'old_value' => null, 'new_value' => $assignUserId]
                ];
                logDatabaseChange($authorityId, $pdo, 'INSERT', "ta_todo_assigned_users", $assignId, $requestingUserId, $changes);
            }
        }

        $pdo->commit();
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Todo assignment updated successfully."]);

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in updateTodoAssignedUser (Todo: $todoId, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update assignment: " . $e->getMessage()]);
    }
}

function updateCheckbox(PDO $pdo, int $requestingUserId, string $authority): void
{
    $data = getJsonRequestData();

    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT); // Checkbox ID
    $completed = filter_var($data['completed'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;


    if (!$id || $completed === null) {
        // Get authority ID

        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        http_response_code(400);
        echo json_encode(['error' => 'Checkbox ID and completed status are required.']);
        return;
    }

    try {
        // Get old completed status for logging
        $sqlOld = "SELECT completed FROM `kdd_ta_todo_checkboxes` WHERE authority_id = ? AND id = ?";
        $stmtOld = $pdo->prepare($sqlOld);
        $stmtOld->execute([$authorityId, $id]);
        $oldCompleted = $stmtOld->fetchColumn();

        $sql = "UPDATE `kdd_ta_todo_checkboxes` SET completed = ? WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $completedValue = $completed ? 1 : 0;
        $success = $stmt->execute([$completedValue, $authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Checkbox status updated."]);

            // Log change
            if ($oldCompleted != $completedValue) {
                $changes = [
                    ['column_name' => 'completed', 'old_value' => $oldCompleted, 'new_value' => $completedValue]
                ];
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "ta_todo_checkboxes", $id, $requestingUserId, $changes);
            }
        } elseif ($success) {
            http_response_code(200);
            echo json_encode(["error" => "No changes made."]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update checkbox.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in updateCheckbox (ID: $id, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update checkbox."]);
    }
}

function updateTodoCheckboxText(PDO $pdo, int $requestingUserId, string $authority): void
{
    $data = getJsonRequestData();

    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT); // Checkbox ID
    $title = $data['title'] ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;


    if (!$id || empty(trim($title ?? ''))) {

        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        http_response_code(400);
        echo json_encode(['error' => 'Checkbox ID and title are required.']);
        return;
    }

    try {
        // Get old title for logging
        $sqlOld = "SELECT title FROM `kdd_ta_todo_checkboxes` WHERE authority_id = ? AND id = ?";
        $stmtOld = $pdo->prepare($sqlOld);
        $stmtOld->execute([$authorityId, $id]);
        $oldTitle = $stmtOld->fetchColumn();

        $sql = "UPDATE `kdd_ta_todo_checkboxes` SET title = ? WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$title, $authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Checkbox text updated."]);

            // Log change
            if ($oldTitle != $title) {
                $changes = [
                    ['column_name' => 'title', 'old_value' => $oldTitle, 'new_value' => $title]
                ];
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "ta_todo_checkboxes", $id, $requestingUserId, $changes);
            }
        } elseif ($success) {
            http_response_code(200);
            echo json_encode(["error" => "No changes made."]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update checkbox text.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in updateTodoCheckboxText (ID: $id, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not update checkbox text."]);
    }
}

function deleteTodoList(PDO $pdo, int $requestingUserId, string $authority): void
{
    $data = getJsonRequestData();

    $list_id = filter_var($data['list_id'] ?? null, FILTER_VALIDATE_INT);
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;

    if (!$list_id) {
        // Get authority ID

        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing list ID.']);
        return;
    }

    try {
        // TODO: Decide on cascade delete behavior for sub-lists and todos, or prevent deletion if not empty.
        // Current logic only soft-deletes the list itself.

        $sql = "DELETE FROM `kdd_ta_todolists` WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $list_id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Todo list marked as deleted."]);
            // Log change
            $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "ta_todolists", $list_id, $requestingUserId, $changes);
        } elseif ($success) {
            http_response_code(404);
            echo json_encode(["error" => "List not found or already deleted."]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to execute list deletion.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteTodoList (ID: $list_id, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete todo list."]);
    }
}


function deleteCheckbox(PDO $pdo, int $requestingUserId, string $authority): void
{
    $data = getJsonRequestData();

    $checkboxId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;

    if (!$checkboxId) {
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing checkbox ID.']);
        return;
    }

    try {
        // Delete the checkbox
        $sql = "DELETE FROM `kdd_ta_todo_checkboxes` WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $checkboxId]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Checkbox deleted successfully."]);
            // Log change
            $changes = [['column_name' => 'deleted', 'old_value' => 0, 'new_value' => 1]];
            logDatabaseChange($authorityId, $pdo, 'DELETE', "ta_todo_checkboxes", $checkboxId, $requestingUserId, $changes);
        } elseif ($success) {
            http_response_code(404);
            echo json_encode(["error" => "Checkbox not found."]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to execute checkbox deletion.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteCheckbox (ID: $checkboxId, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete checkbox."]);
    }
}

function deleteTodo(PDO $pdo, int $requestingUserId, string $authority): void
{
    $data = getJsonRequestData();

    $todoId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;

    if (!$todoId) {
        // Get authority ID
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing todo ID.']);
        return;
    }

    try {
        // Soft delete the todo
        $sql = "DELETE FROM `kdd_ta_todos` WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $todoId]);

        // Optionally delete/mark related checkboxes and assignments?
        // Currently only the main todo is marked.

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Todo marked as deleted."]);
            // Log change
            $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "ta_todos", $todoId, $requestingUserId, $changes);
        } elseif ($success) {
            http_response_code(404);
            echo json_encode(["error" => "Todo not found or already deleted."]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to execute todo deletion.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteTodo (ID: $todoId, Auth: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete todo."]);
    }
}
