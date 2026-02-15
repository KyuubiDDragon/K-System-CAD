<?php
/**
 * Backend Endpoint: personfile/fields.php
 * Returns the field configuration for the authority-specific fields
 * Uses Cookie-based Authentication and PDO database connection.
 */
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

// --- Core Initialisation ---
if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development') {
    error_reporting(E_ALL); ini_set('display_errors', '1');
} else {
    error_reporting(0); ini_set('display_errors', '0');
}

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

// Check permission
require_once __DIR__ . '/../utils/permission_helper.php';
if (!hasPermission($userPermissions, 'READ_PERSON_FILE') && !hasAllPermissions($userPermissions)) {
    http_response_code(403); echo json_encode(["error" => "Permission denied for person file field access."]); exit();
}

// --- Function Implementation ---
try {
    $stmt = $pdo->prepare("
        SELECT * FROM kdd_authority_fields 
        WHERE authority_id = ? 
        ORDER BY display_order ASC
    ");
    $stmt->execute([$authorityId]);
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process options stored as JSON
    foreach ($fields as &$field) {
        if (!empty($field['options'])) {
            $field['options'] = json_decode($field['options'], true);
        }
    }
    
    http_response_code(200);
    echo json_encode($fields);
} catch (\PDOException $e) {
    error_log("DB error in personfile/fields.php: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Could not retrieve authority fields: " . $e->getMessage()]);
} 