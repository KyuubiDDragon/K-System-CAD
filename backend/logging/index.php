<?php
/**
 * Backend Endpoint: logging/index.php
 * Handles logging user access and retrieving access logs.
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
// This script defines logging actions, it shouldn't include logging helpers unless they are different
// require_once __DIR__ . '/../logging/logging.php'; // Removed self-include


// --- Authentication ---
try {
    // Checks cookie, defines $decoded_jwt or exits with 401
    require_once __DIR__ . '/../auth_check.php';
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null; // ID of user performing the action
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority)) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}


// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions
// **WICHTIG:** Passe diese Berechtigungsnamen genau an dein System an!
$permissions_map = [
    'logAccess' => 'CAN_LOGIN', // Or null if logging should happen for any authenticated user implicitly?
    'getLogs'   => 'VIEW_ACCESS_LOGS', // Specific permission to view logs
];

$required_permission = $permissions_map[$action] ?? 'ACTION_NOT_DEFINED';
$has_permission = false;

if ($action === '') { http_response_code(400); echo json_encode(["error" => "No action specified."]); exit(); }
if ($required_permission === 'ACTION_NOT_DEFINED') { http_response_code(404); echo json_encode(['error' => 'Invalid action specified.']); exit(); }

// Load permission helper
require_once __DIR__ . '/../utils/permission_helper.php';

// Load permission helper
require_once __DIR__ . '/../utils/permission_helper.php';



// Check permission levels
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} elseif ($required_permission === null) {
     $has_permission = true; // Only login needed
} elseif ($required_permission && hasPermission($userPermissions, $required_permission)) {
    $has_permission = true; // Has the specific required permission
}

// --- Execute Action or Deny ---
if ($has_permission) {
    switch ($action) {
        case 'logAccess':
            if ($request_method === 'POST') logAccess($pdo, $userId, $authority); else MethodNotAllowed(); break;
        case 'getLogs':
            if ($request_method === 'GET') getLogs($pdo, $authority); else MethodNotAllowed(); break;
        default:
            http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



/**
 * Ermittelt die authority_id anhand des authority-Namens
 * 
 * @param PDO $pdo Die Datenbankverbindung
 * @param string $authority Der Name der Authority
 * @return int|null Die ID der Authority oder null, wenn nicht gefunden
 */
function getAuthorityId(PDO $pdo, string $authority): ?int {
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

// --- Function Implementations (PDO Refactored) ---

/**
 * Loggt einen Zugriffsversuch auf einen bestimmten Tabelleneintrag.
 */
function logAccess(PDO $pdo, int $requestingUserId, string $authority): void {
    $table = $_POST['table'] ?? null;
    // entry_id kann optional sein oder 0/null, wenn kein spezifischer Eintrag geloggt wird
    $entry_id = filter_var($_POST['entry_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['default' => null]]);

    if (empty($table)) {
        http_response_code(400); 
        echo json_encode(["error" => "Table name is required."]); 
        return;
    }

    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }
    
    // Validate table name against a whitelist? Security measure.
    // $allowedTables = ['kdd_users', 'kdd_reports', ...];
    // if (!in_array($table, $allowedTables)) { ... error ... }

    try {
        // Tabelle und Spalte `table` escapen könnte nötig sein, wenn $table dynamisch ist.
        // Aber hier wird $table direkt verwendet - Whitelist wäre sicherer.
        // Backticks um `table` sind wichtig, da es ein reserviertes Wort sein kann.
        $logTableName = "kdd_access_logs";
        $sql = "INSERT INTO {$logTableName} (`table`, userid, entry_id, `date`, authority_id) VALUES (?, ?, ?, NOW(), ?)";
        $stmt = $pdo->prepare($sql);

        // Execute mit Parametern
        $success = $stmt->execute([$table, $requestingUserId, $entry_id, $authorityId]);

        if ($success) {
            http_response_code(201); // Created (Log Entry)
            echo json_encode(["success" => true, "message" => "Access logged successfully."]);
        } else {
            http_response_code(500); 
            echo json_encode(["error" => "Failed to log access."]);
        }
    } catch (\PDOException $e) {
        error_log("DB error in logAccess (User: $requestingUserId, Authority: $authority, Table: $table): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not log access."]);
    }
}

/**
 * Ruft Zugriffslogs ab, optional gefiltert nach Tabelle und/oder Benutzer.
 */
function getLogs(PDO $pdo, string $authority): void {
    $table_filter = $_GET['table'] ?? null;
    $userid_filter = filter_var($_GET['userid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['default' => null]]);

    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

    $params = [$authorityId];
    $sql = "SELECT l.*, u.username 
            FROM `kdd_access_logs` AS l 
            LEFT JOIN `kdd_users` u ON l.userid = u.id 
            WHERE l.authority_id = ?";

    if ($table_filter !== null) {
        $sql .= " AND l.`table` = ?"; // Backticks um table
        $params[] = $table_filter;
    }

    if ($userid_filter !== null) {
        $sql .= " AND l.userid = ?";
        $params[] = $userid_filter;
    }

    $sql .= " ORDER BY l.date DESC LIMIT 100"; // Beispiel: Limit und Sortierung hinzufügen

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($logs);

    } catch (\PDOException $e) {
        error_log("DB error in getLogs (Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve logs."]);
    }
}

?>