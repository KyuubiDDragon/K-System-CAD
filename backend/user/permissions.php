<?php
/**
 * User Permissions Endpoint
 * Fetches permissions for the currently authenticated user
 */
declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../bootstrap.php';

// Enable gzip compression if supported
if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
    ini_set('zlib.output_compression', '1');
    ini_set('zlib.output_compression_level', '9');
}


// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../db.php'; // Defines $pdo
} catch (Exception $e) {
    error_log("Database connection failed in user/permissions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed."]);
    exit();
}

// --- Authentication ---
try {
    require_once __DIR__ . '/../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check in user/permissions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Authentication system error."]);
    exit();
}

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null;
$currentUserAuthorityId = $decoded_jwt->authority_id ?? null;

if ($userId === null || !is_int($userId) || $currentUserAuthorityId === null) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid token payload."]);
    exit();
}

// Only GET method is supported
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit();
}

// Fetch user permissions
try {
    // Get roles and permissions for the user - NEW SCHEMA: Without 'site' column
    $sqlRolesPerms = "SELECT DISTINCT r.name as role_name, p.name as permission_name
                      FROM kdd_user_roles ur
                      JOIN kdd_roles r ON r.authority_id = ur.authority_id AND ur.role_id = r.id
                      JOIN kdd_role_permissions rp ON rp.authority_id = ur.authority_id AND r.id = rp.role_id
                      JOIN kdd_permissions p ON rp.permission_id = p.id
                      WHERE ur.user_id = ? AND ur.authority_id = ?";
    $stmtRolesPerms = $pdo->prepare($sqlRolesPerms);
    $stmtRolesPerms->execute([$userId, $currentUserAuthorityId]);
    $results = $stmtRolesPerms->fetchAll();

    $roles = [];
    $permissions = [];

    // Collect all permissions and roles
    foreach ($results as $row) {
        $roles[$row['role_name']] = $row['role_name']; // Make roles unique
        $permissions[] = $row['permission_name'];
    }

    // Use all permissions (no filtering by features for now)
    $filteredPermissions = array_unique($permissions);

    // Return unique permissions and roles
    http_response_code(200);
    
    // Ensure all permissions are unique
    $uniquePermissions = array_values(array_unique($filteredPermissions));
    
    // Get pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
    $page = max(1, $page); // Ensure page is at least 1
    $limit = min(100, max(1, $limit)); // Limit between 1 and 100
    
    // Calculate total pages
    $totalPermissions = count($uniquePermissions);
    $totalPages = ceil($totalPermissions / $limit);
    
    // Get the current batch of permissions
    $offset = ($page - 1) * $limit;
    $currentBatch = array_slice($uniquePermissions, $offset, $limit);
    
    // Prepare the response
    $response = [
        "roles" => array_values($roles),
        "permissions" => $currentBatch,
        "pagination" => [
            "page" => $page,
            "limit" => $limit,
            "total" => $totalPermissions,
            "pages" => $totalPages
        ]
    ];
    
    // Encode with JSON_NUMERIC_CHECK to convert numeric strings to numbers (smaller)
    echo json_encode($response, JSON_NUMERIC_CHECK);

} catch (PDOException $e) {
    error_log("Database error in user/permissions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Failed to fetch user permissions."]);
    exit();
} 