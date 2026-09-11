<?php
/**
 * Backend Endpoint: weather/index.php
 * Handles fetching weather forecast data.
 * Uses Cookie-based Authentication and PDO database connection.
 */
declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../bootstrap.php';

// --- Dependencies ---
require_once __DIR__ . '/../logging/logging.php'; // For logDatabaseChange

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
$authority = $decoded_jwt->authority ?? null; // Extract authority for logging
$authorityId = $decoded_jwt->authority_id ?? null; // Extract authority ID for queries

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority) || $authorityId === null) { 
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}

require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'default')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to global features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_GET['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions
$permissions_map = [
    'getWeather' => 'CAN_LOGIN', // Assume any logged-in user can see weather
    'getCurrentWeather' => 'CAN_LOGIN', // Current weather for dashboard widget
];

$required_permission = $permissions_map[$action] ?? 'ACTION_NOT_DEFINED';
$has_permission = false;

if ($action === '') { http_response_code(400); echo json_encode(["error" => "No action specified."]); exit(); }
if ($required_permission === 'ACTION_NOT_DEFINED') { http_response_code(404); echo json_encode(['error' => 'Invalid action specified.']); exit(); }

// Load permission helper
require_once __DIR__ . '/../utils/permission_helper.php';

// Check permission levels
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} elseif ($required_permission && hasPermission($userPermissions, $required_permission)) {
    $has_permission = true; // Has the specific permission (e.g., CAN_LOGIN)
}

// --- Execute Action or Deny ---
if ($has_permission) {
    if ($request_method === 'GET') { // Only allow GET
        switch ($action) {
            case 'getWeather':
                getWeather($pdo, $authority, $authorityId); // Pass authority and authorityId
                break;
            case 'getCurrentWeather':
                getCurrentWeather($pdo, $authority, $authorityId); // Current weather for widget
                break;
            default:
                http_response_code(500); echo json_encode(['error' => 'Action routing error.']);
                break;
        }
    } else {
        MethodNotAllowed();
    }
} else {
    error_log("Permission denied for user {$userId} on action '{$action}'. Required: {$required_permission}"); // Log details
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



// --- Function Implementation (PDO Refactored) ---

/**
 * Fetches weather data for the next 7 days (including today).
 */
function getWeather(PDO $pdo, string $authority, int $authorityId): void {
    try {
        // Fixed SQL query - corrected table name and authority_id filter
        $sql = "SELECT * FROM `kdd_weather` 
                WHERE authority_id = ? 
                AND date >= CURDATE() 
                AND date < CURDATE() + INTERVAL 7 DAY
                ORDER BY date ASC"; 
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $weatherData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert numeric types for consistent JSON output
        foreach ($weatherData as &$day) {
            $day['id'] = (int)$day['id'];
            $day['min_temp'] = (float)$day['min_temp'];
            $day['max_temp'] = (float)$day['max_temp'];
            $day['humidity'] = $day['humidity'] !== null ? (int)$day['humidity'] : null;
            $day['wind_speed'] = $day['wind_speed'] !== null ? (float)$day['wind_speed'] : null;
        }
        unset($day); // Unset reference

        http_response_code(200);
        echo json_encode($weatherData);

    } catch (\PDOException $e) {
        error_log("DB error in getWeather ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve weather data."]);
    }
}

/**
 * Fetches current weather and forecast for the dashboard widget
 */
function getCurrentWeather(PDO $pdo, string $authority, int $authorityId): void {
    try {
        // Get weather data for today and the next 3 days for the forecast
        $sql = "SELECT * FROM `kdd_weather`
                WHERE authority_id = ?
                AND date >= CURDATE()
                AND date < CURDATE() + INTERVAL 4 DAY
                ORDER BY date ASC
                LIMIT 4";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $weatherData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($weatherData)) {
            /*
               Kein Anzeigetext aus der Schnittstelle.

               Hier stand 'No data available' - ein englischer Satz, der in der
               deutschen Oberflaeche unter dem Wetterbaustein erschien. Die
               Abwesenheit von Daten ist ein Zustand, kein Text: temperature
               null genuegt, den Leerzustand zeichnet die Oberflaeche selbst.
            */
            http_response_code(200);
            echo json_encode([
                'temperature' => null,
                'condition' => null,
                'forecast' => []
            ]);
            return;
        }

        // First entry is today's weather (current)
        $current = $weatherData[0];
        $forecast = array_slice($weatherData, 1, 3); // Next 3 days

        // Format the response for the widget
        $response = [
            'temperature' => round(($current['min_temp'] + $current['max_temp']) / 2), // Average temp
            'condition' => ucfirst($current['condition'] ?? 'unknown'),
            'forecast' => array_map(function($day) {
                return [
                    'date' => $day['date'],
                    'condition' => ucfirst($day['condition'] ?? 'unknown'),
                    'temp_high' => (int)round($day['max_temp']),
                    'temp_low' => (int)round($day['min_temp'])
                ];
            }, $forecast)
        ];

        http_response_code(200);
        echo json_encode($response);

    } catch (\PDOException $e) {
        error_log("DB error in getCurrentWeather ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve current weather."]);
    }
}

?>