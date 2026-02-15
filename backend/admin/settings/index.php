<?php
/**
 * Merged Settings/Authority Branding Endpoint
 * This version stores settings directly in kdd_authorities table
 * instead of kdd_global_settings
 */

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../utils/auth_check.php';
require_once __DIR__ . '/../../utils/request_helpers.php';

// Get user from JWT (auth_check.php provides $decoded_jwt)
$userId = $decoded_jwt->userId ?? 0;
$authority = $decoded_jwt->authority ?? '';
$userPermissions = $decoded_jwt->permissions ?? [];
$authorityId = $decoded_jwt->authority_id ?? 0;

// Get action
$request_method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Permissions map
$permissions_map = [
    'updateSettings' => 'ADMIN_WRITE_SETTINGS',
    'getGlobalSettings' => 'ADMIN_READ_SETTINGS',
];

$required_permission = $permissions_map[$action] ?? 'ACTION_NOT_DEFINED';
$has_permission = false;

if ($action === '') {
    http_response_code(400);
    echo json_encode(["error" => "No action specified."]);
    exit();
}
if ($required_permission === 'ACTION_NOT_DEFINED') {
    http_response_code(404);
    echo json_encode(['error' => 'Invalid action specified.']);
    exit();
}

// Load permission helper
require_once __DIR__ . '/../../utils/permission_helper.php';

// Check permission levels
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} elseif ($required_permission && hasPermission($userPermissions, $required_permission)) {
    $has_permission = true;
}

// Execute Action or Deny
if ($has_permission) {
    if ($action === 'updateSettings' && $request_method === 'POST') {
        updateSettingsToAuthority($pdo, $userId, $authorityId);
    } elseif ($action === 'getGlobalSettings' && $request_method === 'GET') {
        getSettingsFromAuthority($pdo, $authorityId);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} else {
    http_response_code(403);
    echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();

/**
 * Updates settings - stores in kdd_authorities table
 */
function updateSettingsToAuthority(PDO $pdo, int $requestingUserId, int $authorityId): void {
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    // Get data from JSON
    $data = getJsonRequestData();
    if (!$data) return;

    // Expect data within a 'settings' key in the JSON payload
    $settingsData = $data['settings'] ?? null;
    if (!is_array($settingsData)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing settings data in request.']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Map settings to authority columns
        $columnMap = [
            'siteName' => 'app_title',
            'siteLogo' => 'logo_url',
            'primaryColor' => 'primary_color',
            'secondaryColor' => 'secondary_color',
            'companyName' => 'display_name',
            'defaultBackground' => 'default_background',
        ];

        $updateParts = [];
        $params = [];

        // Handle direct column mappings
        foreach ($columnMap as $settingKey => $columnName) {
            if (isset($settingsData[$settingKey])) {
                $updateParts[] = "`{$columnName}` = ?";
                $params[] = $settingsData[$settingKey];
            }
        }

        // Collect all other settings for theme_settings JSON
        $themeSettings = [];
        $directKeys = array_keys($columnMap);
        foreach ($settingsData as $key => $value) {
            if (!in_array($key, $directKeys)) {
                $themeSettings[$key] = $value;
            }
        }

        // Add theme_settings JSON if there are any
        if (!empty($themeSettings)) {
            $updateParts[] = "`theme_settings` = ?";
            $params[] = json_encode($themeSettings);
        }

        // Build and execute UPDATE query
        if (!empty($updateParts)) {
            $sql = "UPDATE `kdd_authorities` SET " . implode(', ', $updateParts) . ", `updated_at` = NOW() WHERE `id` = ?";
            $params[] = $authorityId;

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);

            if ($result) {
                // Log the change
                $logStmt = $pdo->prepare("INSERT INTO kdd_audit_log (user_id, authority_id, action, table_name, record_id, details)
                                          VALUES (?, ?, 'UPDATE', 'kdd_authorities', ?, ?)");
                $logStmt->execute([
                    $requestingUserId,
                    $authorityId,
                    $authorityId,
                    json_encode(['type' => 'settings_update', 'keys' => array_keys($settingsData)])
                ]);

                $pdo->commit();
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Settings updated successfully',
                    'updated_count' => count($settingsData)
                ]);
            } else {
                throw new Exception('Failed to update authority settings');
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'No valid settings to update']);
        }

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("DB error in updateSettingsToAuthority: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error while updating settings"]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error in updateSettingsToAuthority: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
}

/**
 * Gets settings from kdd_authorities table
 */
function getSettingsFromAuthority(PDO $pdo, int $authorityId): void {
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    try {
        // Get authority data including theme_settings
        $stmt = $pdo->prepare("SELECT app_title, logo_url, primary_color, secondary_color, display_name,
                                     default_background, theme_settings
                              FROM `kdd_authorities`
                              WHERE id = ?");
        $stmt->execute([$authorityId]);
        $authority = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$authority) {
            http_response_code(404);
            echo json_encode(['error' => 'Authority not found']);
            return;
        }

        // Parse theme_settings JSON
        $themeSettings = [];
        if (!empty($authority['theme_settings'])) {
            $themeSettings = json_decode($authority['theme_settings'], true) ?? [];
        }

        // Build response with mapped keys
        $settings = array_merge($themeSettings, [
            'siteName' => $authority['app_title'] ?? 'K-Systems',
            'siteLogo' => $authority['logo_url'] ?? '/logo.png',
            'primaryColor' => $authority['primary_color'] ?? '#3B82F6',
            'secondaryColor' => $authority['secondary_color'] ?? '#6B7280',
            'companyName' => $authority['display_name'] ?? '',
            'defaultBackground' => $authority['default_background'] ?? null,
        ]);

        http_response_code(200);
        echo json_encode(['settings' => $settings]);

    } catch (PDOException $e) {
        error_log("DB error in getSettingsFromAuthority: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not fetch settings."]);
    }
}
?>
