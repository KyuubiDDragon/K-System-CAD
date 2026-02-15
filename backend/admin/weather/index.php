<?php
/**
 * Backend Endpoint: admin/weather/index.php
 * Handles ADMIN CRUD operations for Weather data.
 * Uses Cookie-based Authentication and PDO database connection.
 */
declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../../bootstrap.php';

// --- Authentication ---
try {
    require_once __DIR__ . '/../../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../../db.php'; // Defines $pdo
} catch (Exception $e) {
    http_response_code(500); echo json_encode(["error" => "Database connection failed."]); exit();
}
require_once __DIR__ . '/../../logging/logging.php'; // For logDatabaseChange
require_once __DIR__ . '/../../utils/permission_helper.php';

// --- Role Check ---
$userPermissions = $decoded_jwt->permissions ?? [];
if (!hasPermission($userPermissions, 'ADMIN') && !hasAllPermissions($userPermissions)) {
    http_response_code(403); echo json_encode(["error" => "Forbidden. Admin role or ALL_PERMISSIONS required."]); exit();
}

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null; // Needed for logging context
$authorityId = $decoded_jwt->authority_id ?? null;

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority)) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
// No authority check needed here as weather table is global
require_once __DIR__ . '/../../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'weather')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to weather features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required ADMIN permissions
// **WICHTIG:** Passe diese Berechtigungsnamen genau an dein System an!
$permissions_map = [
    'getWeather'      => 'ADMIN_READ_WEATHER', // Renamed function below
    'getAllWeather'   => 'ADMIN_READ_WEATHER', // Renamed function below
    'insertWeather'   => 'ADMIN_WRITE_WEATHER',
    'updateWeather'   => 'ADMIN_WRITE_WEATHER',
    'deleteWeather'   => 'ADMIN_WRITE_WEATHER', // Assumed from original
];

$required_permission = $permissions_map[$action] ?? 'ACTION_NOT_DEFINED';
$has_permission = false;

if ($action === '') { http_response_code(400); echo json_encode(["error" => "No action specified."]); exit(); }
if ($required_permission === 'ACTION_NOT_DEFINED') { http_response_code(404); echo json_encode(['error' => 'Invalid action specified.']); exit(); }

// Load permission helper
require_once __DIR__ . '/../../utils/permission_helper.php';

// Check permission levels (ALL > ADMIN_WRITE > ADMIN_READ)
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} elseif ($required_permission && hasPermission($userPermissions, $required_permission)) {
    $has_permission = true; // Has specific admin permission
} elseif ($required_permission === 'ADMIN_READ_WEATHER' && hasPermission($userPermissions, 'ADMIN_WRITE_WEATHER')) {
    $has_permission = true; // WRITE implies READ
}

// --- Execute Action or Deny ---
if ($has_permission) {
    $is_post_request = ($request_method === 'POST');

    switch ($action) {
        // Renamed functions for clarity
        case 'getAllWeather':       if ($request_method === 'GET') getAllWeatherDataAdmin($pdo); else MethodNotAllowed(); break; // Gets ALL historical data
        case 'getWeather':          if ($request_method === 'GET') getUpcomingWeatherAdmin($pdo); else MethodNotAllowed(); break; // Gets next 7 days

        // POST/DELETE actions
        case 'insertWeather':       if ($is_post_request) insertWeather($pdo, $userId, $authority); else MethodNotAllowed(); break;
        case 'updateWeather':       if ($is_post_request) updateWeather($pdo, $userId, $authority); else MethodNotAllowed(); break; // Consider PUT
        case 'deleteWeather':       if ($is_post_request) deleteWeather($pdo, $userId, $authority); else MethodNotAllowed(); break; // Consider DELETE

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error or action mismatch.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



// --- Function Implementations (PDO Refactored) ---

/** Fetches ALL weather data (Admin view) */
function getAllWeatherDataAdmin(PDO $pdo): void {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    try {
        $sql = "SELECT * FROM `kdd_weather` WHERE authority_id = ? ORDER BY date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $weatherData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($weatherData);
    } catch (\PDOException $e) {
        error_log("DB error in getAllWeatherDataAdmin: " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not retrieve all weather data."]);
    }
}

/** Fetches upcoming weather data (next 7 days including today - Admin view) */
function getUpcomingWeatherAdmin(PDO $pdo): void {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    try {
        $sql = "SELECT * FROM `kdd_weather` WHERE authority_id = ? AND date >= CURDATE() ORDER BY date ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $weatherData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($weatherData);
    } catch (\PDOException $e) {
        error_log("DB error in getUpcomingWeatherAdmin: " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not retrieve upcoming weather data."]);
    }
}

function insertWeather(PDO $pdo, int $requestingUserId, string $adminAuthority): void {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        MethodNotAllowed(); 
        return; 
    }

    // Get data from JSON instead of POST
    $data = getJsonRequestData();
    if (!$data) return;

    // Validate and sanitize input
    $date = !empty($data['date']) ? date('Y-m-d', strtotime($data['date'])) : null;
    $day = $data['day'] ?? null; // Maybe derive from date?
    $mintemp = filter_var($data['min_temp'] ?? null, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
    $maxtemp = filter_var($data['max_temp'] ?? null, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
    $icon = $data['icon'] ?? null;
    $humidity = filter_var($data['humidity'] ?? null, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
    $warning = $data['warning'] ?? '';
    $wind_speed = filter_var($data['wind_speed'] ?? null, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);

    if (!$date || !$day || $mintemp === null || $maxtemp === null || !$icon || $humidity === null || $wind_speed === null) {
        http_response_code(400); echo json_encode(['error' => 'Missing or invalid required weather data fields. Check types (temp, humidity, wind_speed should be numbers).']); return;
    }

    try {
        // Check if entry for this date already exists? Prevent duplicates?
        $stmtCheck = $pdo->prepare("SELECT id FROM `kdd_weather` WHERE authority_id = ? AND date = ?");
        $stmtCheck->execute([$authorityId, $date]);
        if ($stmtCheck->fetch()) {
            http_response_code(409); // Conflict
            echo json_encode(["error" => "Weather data for this date already exists."]);
            return;
        }

        $sql = "INSERT INTO `kdd_weather` (authority_id, date, day, min_temp, max_temp, icon, humidity, warning, wind_speed)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $date, $day, $mintemp, $maxtemp, $icon, $humidity, $warning, $wind_speed]);

        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201); echo json_encode(["success" => true, "message" => "Weather record created successfully.", "id" => $newId]);
            // Log change (pass adminAuthority for context, table name 'weather')
             $logData = json_encode(['date'=>$date, 'day'=>$day, 'min'=>$mintemp, 'max'=>$maxtemp, 'icon'=>$icon]);
             $changes = [['column_name' => 'row_data', 'old_value' => null, 'new_value' => $logData]];
             logDatabaseChange($authorityId, $pdo, 'INSERT', "weather", $newId, $requestingUserId, $changes);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to insert weather record.']); }
    } catch (\PDOException $e) {
         if ($e->getCode() == '23000') { // Unique constraint (likely date)
            http_response_code(409); echo json_encode(["error" => "Weather data for this date already exists (DB constraint)."]);
         } else {
            error_log("DB error in insertWeather [ADMIN]: " . $e->getMessage());
            http_response_code(500); echo json_encode(["error" => "Could not insert weather record."]);
         }
    }
}

function updateWeather(PDO $pdo, int $requestingUserId, string $adminAuthority): void {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        MethodNotAllowed(); 
        return; 
    }

    // Get data from JSON instead of POST
    $data = getJsonRequestData();
    if (!$data) return;

    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    // Validate and sanitize other inputs
    $date = !empty($data['date']) ? date('Y-m-d', strtotime($data['date'])) : null;
    $day = $data['day'] ?? null;
    $mintemp = filter_var($data['min_temp'] ?? null, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
    $maxtemp = filter_var($data['max_temp'] ?? null, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
    $icon = $data['icon'] ?? null;
    $humidity = filter_var($data['humidity'] ?? null, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
    $warning = $data['warning'] ?? '';
    $wind_speed = filter_var($data['wind_speed'] ?? null, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);

    // Require ID and at least one field to update
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Weather record ID is required.']); return; }
    if (!$date && !$day && $mintemp === null && $maxtemp === null && !$icon && $humidity === null && !isset($data['warning']) && $wind_speed === null) {
         http_response_code(400); echo json_encode(['error' => 'No update data provided.']); return;
    }

    try {
        // First, find the existing weather record and get its authority_id
        $findSql = "SELECT authority_id FROM `kdd_weather` WHERE id = ?";
        $findStmt = $pdo->prepare($findSql);
        $findStmt->execute([$id]);
        $recordAuthorityId = $findStmt->fetchColumn();
        
        if ($recordAuthorityId === false) {
            http_response_code(404);
            echo json_encode(['error' => 'Weather record not found']);
            return;
        }
        
        // Build SET clause dynamically based on provided fields
        $setParts = []; $params = [];
        if ($date !== null) { $setParts[] = "date = ?"; $params[] = $date; }
        if ($day !== null) { $setParts[] = "day = ?"; $params[] = $day; }
        if ($mintemp !== null) { $setParts[] = "min_temp = ?"; $params[] = $mintemp; }
        if ($maxtemp !== null) { $setParts[] = "max_temp = ?"; $params[] = $maxtemp; }
        if ($icon !== null) { $setParts[] = "icon = ?"; $params[] = $icon; }
        if ($humidity !== null) { $setParts[] = "humidity = ?"; $params[] = $humidity; }
        if (isset($data['warning'])) { $setParts[] = "warning = ?"; $params[] = $warning; } // Update even if empty string
        if ($wind_speed !== null) { $setParts[] = "wind_speed = ?"; $params[] = $wind_speed; }

        if (empty($setParts)) { http_response_code(400); echo json_encode(['error' => 'No valid update fields provided.']); return; }

        // Add the WHERE clause parameters
        $params[] = $recordAuthorityId;
        $params[] = $id;

        // $oldEntry = getEntryById($pdo, $id, "kdd_weather"); // For logging

        $sql = "UPDATE `kdd_weather` SET " . implode(', ', $setParts) . " WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);

        if ($success) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Weather record updated successfully."]);
            // Log change if needed
            // if($oldEntry) { $changes = getEntryChanges(...); logDatabaseChange(...); }
        } else { 
            http_response_code(500); echo json_encode(['error' => 'Failed to execute weather update.']); 
        }
    } catch (\PDOException $e) {
        if ($e->getCode() == '23000') { // Unique constraint (date?)
           http_response_code(409); echo json_encode(["error" => "Could not update weather: Date might already exist if changed."]);
        } else {
           error_log("DB error in updateWeather [ADMIN] (ID: $id): " . $e->getMessage());
           http_response_code(500); echo json_encode(["error" => "Could not update weather record."]);
        }
    }
}

function deleteWeather(PDO $pdo, int $requestingUserId, string $adminAuthority): void {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    // Get data from JSON instead of POST
    $data = getJsonRequestData();
    if (!$data) return;
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400); 
        echo json_encode(['error' => 'Invalid or missing weather record ID.']); 
        return; 
    }

    try {
        // Hard delete
        // $oldEntry = getEntryById($pdo, $id, "kdd_weather"); // For logging

        $sql = "DELETE FROM kdd_weather WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Weather record deleted successfully."]);
            // Log change
             // if($oldEntry) { $changes = [...]; logDatabaseChange($adminAuthority, $pdo, 'DELETE', "weather", $id, $requestingUserId, $changes); }
        } elseif ($success) { http_response_code(404); echo json_encode(["error" => "Weather record not found."]);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to execute weather record deletion.']); }
    } catch (\PDOException $e) {
        error_log("DB error in deleteWeather [ADMIN] (ID: $id): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not delete weather record."]);
    }
}

?>