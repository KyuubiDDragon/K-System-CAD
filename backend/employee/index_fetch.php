<?php
/**
 * Backend Endpoint: company/index.php (?)
 * Handles fetching job types/roles for a specific authority.
 * Uses Cookie-based Authentication and PDO database connection.
 */
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

// --- Core Initialisation ---


// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../db.php'; // Defines $pdo
} catch (Exception $e) {
    http_response_code(500); echo json_encode(["error" => "Database connection failed."]); exit();
}
// logging.php is not directly used here, but maybe included for consistency or future use
// require_once __DIR__ . '/../logging/logging.php';

// --- Authentication ---
try {
    require_once __DIR__ . '/../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null; // Although not used directly in getJobTypes, good practice to have it
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
if (!hasFeatureAccess($pdo, $authorityId, 'employee')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to employee features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_GET['action'] ?? ''; // Expect action via GET
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions
// **WICHTIG:** Ersetze 'READ_COMPANY_DATA' mit der korrekten Berechtigung!
$permissions_map = [
    'getJobTypes' => 'READ_COMPANY', // Annahme: Man braucht dieses Recht, um Job-Rollen zu sehen
];

$required_permission = $permissions_map[$action] ?? 'ACTION_NOT_DEFINED';
$has_permission = false;

if ($action === '') { http_response_code(400); echo json_encode(["error" => "No action specified."]); exit(); }
if ($required_permission === 'ACTION_NOT_DEFINED') { http_response_code(404); echo json_encode(['error' => 'Invalid action specified.']); exit(); }

// Load permission helper
require_once __DIR__ . '/../utils/permission_helper.php';

// Load permission helper
require_once __DIR__ . '/../utils/permission_helper.php';



// Check permission levels (Adjust hierarchy if WRITE_COMPANY_DATA exists and implies READ)
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} elseif ($required_permission && hasPermission($userPermissions, $required_permission)) {
    $has_permission = true; // Has specific permission (READ_COMPANY_DATA)
}
// Example if WRITE implies READ:
// elseif ($required_permission === 'READ_COMPANY_DATA' && hasPermission($userPermissions, 'WRITE_COMPANY_DATA')) {
//     $has_permission = true;
// }


// --- Execute Action or Deny ---
if ($has_permission) {
    // Only GET method is expected for actions here
    if ($request_method === 'GET') {
        switch ($action) {
            case 'getJobTypes':
                getJobTypes($pdo, $authority);
                break;
            default:
                // Should not be reached if action check is correct
                http_response_code(500); echo json_encode(['error' => 'Action routing error.']);
                break;
        }
    } else {
        MethodNotAllowed(); // Send 405 if method is not GET
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



// --- Function Implementation (PDO Refactored) ---

/**
 * Fetches all job roles for the given authority.
 */
function getJobTypes(PDO $pdo, string $authority): void {
    // Removed: global $authority, require "../db.php", $conn->close()
    try {
        // $authority is validated before calling this function
        global $decoded_jwt;
        $authorityId = $decoded_jwt->authority_id ?? 0;
        $sql = "SELECT * FROM `kdd_jobroles` WHERE authority_id = ?"; // Added basic sorting
        $stmt = $pdo->query($sql); // Simple query, no user input needed besides validated $authority
        $stmt->execute([$authorityId]);	

        // Fetch all results as associative array (default from db.php options)
        $jobTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($jobTypes);

    } catch (\PDOException $e) {
        error_log("DB error in getJobTypes ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve job types."]);
    }
}

?>