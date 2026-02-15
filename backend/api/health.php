<?php
/**
 * Health Check Endpoint
 * Simple status endpoint for Docker healthchecks and monitoring
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

// CORS Headers for health check
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");

// OPTIONS Preflight Handler
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Build basic response, always return 200 for health check
$response = [
    'status' => 'ok',
    'timestamp' => time(),
    'db_connected' => false,
    'environment' => $_ENV['APP_ENV'] ?? 'production',
    'version' => '1.0.0'
];

// Basic DB check - ignoring failures for healthcheck
try {
    // Simple query to verify DB connection
    $stmt = $pdo->query("SELECT 1");
    $response['db_connected'] = ($stmt !== false);
} catch (Exception $e) {
    // Don't fail healthcheck for DB error, just log and report status
    error_log("Health check DB error: " . $e->getMessage());
    $response['db_connected'] = false;
    $response['db_error'] = 'Database connection error';
}

// Always return 200 OK for health checks
http_response_code(200);

// Output health status
echo json_encode($response); 