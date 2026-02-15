<?php
/**
 * DEBUG ENDPOINT - Shows exactly what's in the JWT token
 */
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/auth_check.php';
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(["error" => "Auth check failed: " . $e->getMessage()]);
    exit();
}

require_once __DIR__ . '/utils/permission_helper.php';

// Extract everything from JWT
$debug = [
    'jwt_payload' => [
        'userId' => $decoded_jwt->userId ?? null,
        'username' => $decoded_jwt->username ?? null,
        'authority' => $decoded_jwt->authority ?? null,
        'authority_id' => $decoded_jwt->authority_id ?? null,
        'permissions_type' => gettype($decoded_jwt->permissions ?? null),
        'permissions_raw' => $decoded_jwt->permissions ?? null,
        'roles' => $decoded_jwt->roles ?? null,
    ],
    'permission_checks' => [
        'hasAllPermissions' => hasAllPermissions($decoded_jwt->permissions ?? []),
        'permissions_is_object' => is_object($decoded_jwt->permissions ?? null),
        'permissions_is_array' => is_array($decoded_jwt->permissions ?? null),
        'permissions_count' => is_array($decoded_jwt->permissions ?? null) ? count($decoded_jwt->permissions) : 0,
    ],
    'test_checks' => []
];

// Test some common permission checks
$userPerms = $decoded_jwt->permissions ?? [];

$testModules = [
    ['module' => 'system', 'action' => 'admin'],
    ['module' => 'dispatch', 'action' => 'read'],
    ['module' => 'dispatch.vehicle', 'action' => 'read'],
    ['module' => 'employee', 'action' => 'read'],
    ['module' => 'admin.users', 'action' => 'read'],
];

foreach ($testModules as $test) {
    $result = hasModulePermission($userPerms, $test['module'], $test['action']);
    $debug['test_checks'][] = [
        'module' => $test['module'],
        'action' => $test['action'],
        'result' => $result,
        'status' => $result ? '✅' : '❌'
    ];
}

// Test legacy permission checks
$legacyTests = [
    'READ_EMPLOYEE',
    'READ_VEHICLE',
    'ADMIN_READ_USERS',
];

foreach ($legacyTests as $legacy) {
    $result = hasPermission($userPerms, $legacy);
    $debug['legacy_checks'][] = [
        'permission' => $legacy,
        'result' => $result,
        'status' => $result ? '✅' : '❌'
    ];
}

echo json_encode($debug, JSON_PRETTY_PRINT);
