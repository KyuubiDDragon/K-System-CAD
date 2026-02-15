<?php

/**
 * Backend Endpoint: dashboard/index.php
 * Handles dashboard layout management including load, save, export, import, and templates
 * Uses Cookie-based Authentication and PDO database connection.
 */

declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../bootstrap.php';

// --- Authentication & User Context ---
try {
    require_once __DIR__ . '/../auth_check.php';
} catch (\Throwable $e) {
    error_log("Critical error during authentication check: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Authentication system error."]);
    exit();
}

// Extract Context from Decoded Token
$user_id = $decoded_jwt->userId ?? null;
$permissions = $decoded_jwt->permissions ?? [];
$authority_id = $decoded_jwt->authority_id ?? null;

if (!$user_id || !$authority_id) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid authentication token."]);
    exit();
}

// Get action from query string
$action = $_GET['action'] ?? '';

try {
    switch ($action) {

        /**
         * GET LAYOUT
         * Returns user's dashboard layout or generates one based on permissions
         */
        case 'getLayout':
            // Check if user has custom layout
            $stmt = $pdo->prepare("
                SELECT id, layout, widgets, template, created_at, updated_at
                FROM kdd_dashboard_layout
                WHERE user_id = :user_id AND authority_id = :authority_id
            ");
            $stmt->execute([
                'user_id' => $user_id,
                'authority_id' => $authority_id
            ]);

            $customLayout = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($customLayout) {
                // User has custom layout
                echo json_encode([
                    'success' => true,
                    'layout' => json_decode($customLayout['layout']),
                    'widgets' => json_decode($customLayout['widgets']),
                    'template' => $customLayout['template'],
                    'isCustom' => true,
                    'lastUpdated' => $customLayout['updated_at']
                ]);
            } else {
                // Generate layout from permissions - find best matching template
                $bestTemplate = findBestTemplate($pdo, $permissions);

                if ($bestTemplate) {
                    echo json_encode([
                        'success' => true,
                        'layout' => json_decode($bestTemplate['layout']),
                        'widgets' => json_decode($bestTemplate['widgets']),
                        'template' => $bestTemplate['template_key'],
                        'isCustom' => false,
                        'suggestedTemplate' => [
                            'key' => $bestTemplate['template_key'],
                            'name' => $bestTemplate['name'],
                            'description' => $bestTemplate['description'],
                            'icon' => $bestTemplate['icon']
                        ]
                    ]);
                } else {
                    // Fallback to minimal member dashboard
                    echo json_encode([
                        'success' => true,
                        'layout' => getDefaultLayout(),
                        'widgets' => getDefaultWidgets(),
                        'template' => 'member',
                        'isCustom' => false
                    ]);
                }
            }
            break;

        /**
         * SAVE LAYOUT
         * Saves user's custom dashboard layout
         */
        case 'saveLayout':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['layout']) || !isset($data['widgets'])) {
                throw new Exception('Missing required fields: layout and widgets');
            }

            $layout = json_encode($data['layout']);
            $widgets = json_encode($data['widgets']);
            $template = $data['template'] ?? 'custom';

            // Check if layout exists
            $stmt = $pdo->prepare("
                SELECT id FROM kdd_dashboard_layout
                WHERE user_id = :user_id AND authority_id = :authority_id
            ");
            $stmt->execute([
                'user_id' => $user_id,
                'authority_id' => $authority_id
            ]);

            $existing = $stmt->fetch();

            if ($existing) {
                // Update existing layout
                $stmt = $pdo->prepare("
                    UPDATE kdd_dashboard_layout
                    SET layout = :layout, widgets = :widgets, template = :template, updated_at = NOW()
                    WHERE user_id = :user_id AND authority_id = :authority_id
                ");
                $stmt->execute([
                    'layout' => $layout,
                    'widgets' => $widgets,
                    'template' => $template,
                    'user_id' => $user_id,
                    'authority_id' => $authority_id
                ]);

                $layoutId = $existing['id'];
                $actionType = 'updated';
            } else {
                // Insert new layout
                $stmt = $pdo->prepare("
                    INSERT INTO kdd_dashboard_layout (user_id, authority_id, layout, widgets, template)
                    VALUES (:user_id, :authority_id, :layout, :widgets, :template)
                ");
                $stmt->execute([
                    'user_id' => $user_id,
                    'authority_id' => $authority_id,
                    'layout' => $layout,
                    'widgets' => $widgets,
                    'template' => $template
                ]);

                $layoutId = $pdo->lastInsertId();
                $actionType = 'created';
            }

            // Log the action
            logDashboardAudit($pdo, $user_id, $authority_id, $actionType, $layoutId);

            echo json_encode([
                'success' => true,
                'message' => 'Dashboard layout saved successfully',
                'layoutId' => $layoutId
            ]);
            break;

        /**
         * EXPORT LAYOUT
         * Exports user's layout with unique hash for sharing
         */
        case 'exportLayout':
            $stmt = $pdo->prepare("
                SELECT id, layout, widgets, template
                FROM kdd_dashboard_layout
                WHERE user_id = :user_id AND authority_id = :authority_id
            ");
            $stmt->execute([
                'user_id' => $user_id,
                'authority_id' => $authority_id
            ]);

            $layout = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$layout) {
                throw new Exception('No layout found to export');
            }

            // Generate unique export hash
            $exportHash = bin2hex(random_bytes(32));

            // Update layout with export hash
            $stmt = $pdo->prepare("
                UPDATE kdd_dashboard_layout
                SET export_hash = :export_hash
                WHERE id = :id
            ");
            $stmt->execute([
                'export_hash' => $exportHash,
                'id' => $layout['id']
            ]);

            // Log export
            logDashboardAudit($pdo, $user_id, $authority_id, 'exported', $layout['id']);

            // Prepare export data
            $exportData = [
                'version' => '1.0',
                'exportHash' => $exportHash,
                'exportedBy' => $decoded_jwt->username ?? 'Unknown',
                'exportedAt' => date('Y-m-d H:i:s'),
                'template' => $layout['template'],
                'layout' => json_decode($layout['layout']),
                'widgets' => json_decode($layout['widgets'])
            ];

            echo json_encode([
                'success' => true,
                'exportData' => $exportData,
                'exportHash' => $exportHash
            ]);
            break;

        /**
         * IMPORT LAYOUT
         * Imports layout from export hash or JSON data
         */
        case 'importLayout':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (isset($data['exportHash'])) {
                // Import from hash
                $stmt = $pdo->prepare("
                    SELECT layout, widgets, template
                    FROM kdd_dashboard_layout
                    WHERE export_hash = :export_hash AND is_public = 1
                ");
                $stmt->execute(['export_hash' => $data['exportHash']]);

                $sourceLayout = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$sourceLayout) {
                    throw new Exception('Invalid export hash or layout is not public');
                }

                $layout = $sourceLayout['layout'];
                $widgets = $sourceLayout['widgets'];
                $template = $sourceLayout['template'];
                $importedFrom = $data['exportHash'];

            } elseif (isset($data['importData'])) {
                // Import from JSON data
                $importData = $data['importData'];

                if (!isset($importData['layout']) || !isset($importData['widgets'])) {
                    throw new Exception('Invalid import data format');
                }

                $layout = json_encode($importData['layout']);
                $widgets = json_encode($importData['widgets']);
                $template = $importData['template'] ?? 'custom';
                $importedFrom = $importData['exportHash'] ?? null;

            } else {
                throw new Exception('Missing exportHash or importData');
            }

            // Save imported layout
            $stmt = $pdo->prepare("
                INSERT INTO kdd_dashboard_layout (user_id, authority_id, layout, widgets, template)
                VALUES (:user_id, :authority_id, :layout, :widgets, :template)
                ON DUPLICATE KEY UPDATE
                    layout = VALUES(layout),
                    widgets = VALUES(widgets),
                    template = VALUES(template),
                    updated_at = NOW()
            ");
            $stmt->execute([
                'user_id' => $user_id,
                'authority_id' => $authority_id,
                'layout' => $layout,
                'widgets' => $widgets,
                'template' => $template
            ]);

            $layoutId = $pdo->lastInsertId() ?: $pdo->query("SELECT LAST_INSERT_ID()")->fetchColumn();

            // Log import
            logDashboardAudit($pdo, $user_id, $authority_id, 'imported', $layoutId, null, $importedFrom);

            echo json_encode([
                'success' => true,
                'message' => 'Dashboard layout imported successfully'
            ]);
            break;

        /**
         * GET TEMPLATES
         * Returns available dashboard templates based on user permissions
         */
        case 'getTemplates':
            $stmt = $pdo->prepare("
                SELECT id, template_key, name, description, icon, required_permissions,
                       layout, widgets, category, sort_order
                FROM kdd_dashboard_templates
                WHERE is_active = 1
                ORDER BY category, sort_order
            ");
            $stmt->execute();

            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Filter templates by user permissions
            $availableTemplates = [];
            foreach ($templates as $template) {
                $requiredPerms = json_decode($template['required_permissions'], true) ?? [];

                if (hasRequiredPermissions($permissions, $requiredPerms)) {
                    $availableTemplates[] = [
                        'key' => $template['template_key'],
                        'name' => $template['name'],
                        'description' => $template['description'],
                        'icon' => $template['icon'],
                        'category' => $template['category'],
                        'layout' => json_decode($template['layout']),
                        'widgets' => json_decode($template['widgets']),
                        'preview' => generateTemplatePreview($template)
                    ];
                }
            }

            echo json_encode([
                'success' => true,
                'templates' => $availableTemplates
            ]);
            break;

        /**
         * APPLY TEMPLATE
         * Applies a predefined template to user's dashboard
         */
        case 'applyTemplate':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $templateKey = $data['templateKey'] ?? '';

            if (!$templateKey) {
                throw new Exception('Template key is required');
            }

            // Get template
            $stmt = $pdo->prepare("
                SELECT template_key, layout, widgets, required_permissions
                FROM kdd_dashboard_templates
                WHERE template_key = :template_key AND is_active = 1
            ");
            $stmt->execute(['template_key' => $templateKey]);

            $template = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$template) {
                throw new Exception('Template not found');
            }

            // Check permissions
            $requiredPerms = json_decode($template['required_permissions'], true) ?? [];
            if (!hasRequiredPermissions($permissions, $requiredPerms)) {
                throw new Exception('Insufficient permissions for this template');
            }

            // Apply template
            $stmt = $pdo->prepare("
                INSERT INTO kdd_dashboard_layout (user_id, authority_id, layout, widgets, template)
                VALUES (:user_id, :authority_id, :layout, :widgets, :template)
                ON DUPLICATE KEY UPDATE
                    layout = VALUES(layout),
                    widgets = VALUES(widgets),
                    template = VALUES(template),
                    updated_at = NOW()
            ");
            $stmt->execute([
                'user_id' => $user_id,
                'authority_id' => $authority_id,
                'layout' => $template['layout'],
                'widgets' => $template['widgets'],
                'template' => $templateKey
            ]);

            $layoutId = $pdo->lastInsertId() ?: $pdo->query("SELECT LAST_INSERT_ID()")->fetchColumn();

            // Log template application
            logDashboardAudit($pdo, $user_id, $authority_id, 'template_applied', $layoutId, $templateKey);

            echo json_encode([
                'success' => true,
                'message' => 'Template applied successfully',
                'layout' => json_decode($template['layout']),
                'widgets' => json_decode($template['widgets'])
            ]);
            break;

        /**
         * GET AVAILABLE WIDGETS
         * Returns list of widgets user can add based on permissions
         */
        case 'getAvailableWidgets':
            $widgetRegistry = getWidgetRegistry();
            $availableWidgets = [];

            foreach ($widgetRegistry as $widgetKey => $widgetInfo) {
                $requiredPerms = $widgetInfo['requiredPermissions'] ?? [];

                if (hasRequiredPermissions($permissions, $requiredPerms)) {
                    $availableWidgets[] = [
                        'key' => $widgetKey,
                        'name' => $widgetInfo['name'],
                        'description' => $widgetInfo['description'],
                        'icon' => $widgetInfo['icon'],
                        'category' => $widgetInfo['category'],
                        'minSize' => $widgetInfo['minSize'],
                        'defaultSize' => $widgetInfo['defaultSize'],
                        'realtime' => $widgetInfo['realtime'] ?? false
                    ];
                }
            }

            echo json_encode([
                'success' => true,
                'widgets' => $availableWidgets
            ]);
            break;

        /**
         * DELETE LAYOUT
         * Resets to default permission-based layout
         */
        case 'deleteLayout':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                throw new Exception('Method not allowed');
            }

            $stmt = $pdo->prepare("
                DELETE FROM kdd_dashboard_layout
                WHERE user_id = :user_id AND authority_id = :authority_id
            ");
            $stmt->execute([
                'user_id' => $user_id,
                'authority_id' => $authority_id
            ]);

            // Log deletion
            logDashboardAudit($pdo, $user_id, $authority_id, 'deleted');

            echo json_encode([
                'success' => true,
                'message' => 'Dashboard layout reset to default'
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// ==================== HELPER FUNCTIONS ====================

/**
 * Find best matching template based on user permissions
 */
function findBestTemplate($pdo, $userPermissions) {
    $stmt = $pdo->prepare("
        SELECT template_key, name, description, icon, required_permissions, layout, widgets
        FROM kdd_dashboard_templates
        WHERE is_active = 1
        ORDER BY sort_order DESC
    ");
    $stmt->execute();

    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $bestMatch = null;
    $highestScore = 0;

    foreach ($templates as $template) {
        $requiredPerms = json_decode($template['required_permissions'], true) ?? [];

        if (hasRequiredPermissions($userPermissions, $requiredPerms)) {
            // Score based on how many permissions match
            $score = count($requiredPerms);

            if ($score > $highestScore) {
                $highestScore = $score;
                $bestMatch = $template;
            }
        }
    }

    return $bestMatch;
}

/**
 * Check if user has all required permissions
 */
function hasRequiredPermissions($userPermissions, $requiredPermissions) {
    require_once __DIR__ . '/../utils/permission_helper.php';

    if (empty($requiredPermissions)) {
        return true; // No permissions required
    }

    // Check if user has ALL_PERMISSIONS (super admin)
    if (hasAllPermissions($userPermissions)) {
        return true;
    }

    // Check each required permission using the new bitmask-compatible hasPermission()
    foreach ($requiredPermissions as $required) {
        // hasPermission() handles both legacy strings and bitmask format
        if (!hasPermission($userPermissions, $required)) {
            return false;
        }
    }

    return true;
}

/**
 * Log dashboard audit action
 */
function logDashboardAudit($pdo, $userId, $authorityId, $action, $layoutId = null, $templateKey = null, $importedFrom = null) {
    $stmt = $pdo->prepare("
        INSERT INTO kdd_dashboard_audit (user_id, authority_id, action, layout_id, template_key, imported_from, ip_address, user_agent)
        VALUES (:user_id, :authority_id, :action, :layout_id, :template_key, :imported_from, :ip_address, :user_agent)
    ");

    $stmt->execute([
        'user_id' => $userId,
        'authority_id' => $authorityId,
        'action' => $action,
        'layout_id' => $layoutId,
        'template_key' => $templateKey,
        'imported_from' => $importedFrom,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

/**
 * Get default layout for fallback
 */
function getDefaultLayout() {
    return [
        ['i' => 'welcome-banner', 'x' => 0, 'y' => 0, 'w' => 12, 'h' => 2],
        ['i' => 'messages-feed', 'x' => 0, 'y' => 2, 'w' => 6, 'h' => 4],
        ['i' => 'calendar-widget', 'x' => 6, 'y' => 2, 'w' => 6, 'h' => 4]
    ];
}

/**
 * Get default widgets for fallback
 */
function getDefaultWidgets() {
    return [
        'welcome-banner' => ['type' => 'welcome-banner', 'title' => 'Welcome'],
        'messages-feed' => ['type' => 'messages-feed', 'title' => 'Messages', 'maxItems' => 5],
        'calendar-widget' => ['type' => 'calendar-widget', 'title' => 'Calendar']
    ];
}

/**
 * Generate template preview (simplified representation)
 */
function generateTemplatePreview($template) {
    $layout = json_decode($template['layout'], true);
    return [
        'widgetCount' => count($layout),
        'gridSize' => ['width' => 12, 'height' => max(array_column($layout, 'y')) + max(array_column($layout, 'h'))]
    ];
}

/**
 * Get widget registry with permission requirements
 */
function getWidgetRegistry() {
    return [
        'welcome-banner' => [
            'name' => 'Welcome Banner',
            'description' => 'Personalized welcome message with quick stats',
            'icon' => 'mdi-home',
            'category' => 'information',
            'requiredPermissions' => ['CAN_LOGIN'],
            'minSize' => ['w' => 6, 'h' => 2],
            'defaultSize' => ['w' => 12, 'h' => 2],
            'realtime' => false
        ],
        'messages-feed' => [
            'name' => 'Messages Feed',
            'description' => 'Recent messages and notifications',
            'icon' => 'mdi-message-text',
            'category' => 'communication',
            'requiredPermissions' => ['CAN_LOGIN'],
            'minSize' => ['w' => 4, 'h' => 3],
            'defaultSize' => ['w' => 6, 'h' => 4],
            'realtime' => true
        ],
        'calendar-widget' => [
            'name' => 'Calendar',
            'description' => 'Upcoming events and schedule',
            'icon' => 'mdi-calendar',
            'category' => 'information',
            'requiredPermissions' => ['READ_CALENDAR'],
            'minSize' => ['w' => 4, 'h' => 3],
            'defaultSize' => ['w' => 6, 'h' => 4],
            'realtime' => true
        ],
        'my-vacations' => [
            'name' => 'My Vacations',
            'description' => 'Your vacation and absence overview',
            'icon' => 'mdi-beach',
            'category' => 'personal',
            'requiredPermissions' => ['CAN_LOGIN'],
            'minSize' => ['w' => 4, 'h' => 3],
            'defaultSize' => ['w' => 6, 'h' => 4],
            'realtime' => true
        ],
        'todo-list' => [
            'name' => 'Todo List',
            'description' => 'Your tasks and todos',
            'icon' => 'mdi-checkbox-marked-circle-outline',
            'category' => 'personal',
            'requiredPermissions' => ['READ_TODO'],
            'minSize' => ['w' => 4, 'h' => 3],
            'defaultSize' => ['w' => 6, 'h' => 4],
            'realtime' => true
        ],
        'weather-widget' => [
            'name' => 'Weather',
            'description' => 'Current weather and forecast',
            'icon' => 'mdi-weather-partly-cloudy',
            'category' => 'information',
            'requiredPermissions' => ['CAN_LOGIN'],
            'minSize' => ['w' => 4, 'h' => 3],
            'defaultSize' => ['w' => 4, 'h' => 3],
            'realtime' => false
        ],
        'active-users' => [
            'name' => 'Active Users',
            'description' => 'Currently logged in users',
            'icon' => 'mdi-account-multiple',
            'category' => 'information',
            'requiredPermissions' => ['READ_EMPLOYEE'],
            'minSize' => ['w' => 3, 'h' => 2],
            'defaultSize' => ['w' => 4, 'h' => 2],
            'realtime' => true
        ],
        'quick-dispatch' => [
            'name' => 'Quick Dispatch',
            'description' => 'Dispatch control board',
            'icon' => 'mdi-truck-fast',
            'category' => 'operational',
            'requiredPermissions' => ['READ_DISPATCH'],
            'minSize' => ['w' => 6, 'h' => 3],
            'defaultSize' => ['w' => 12, 'h' => 4],
            'realtime' => true
        ],
        'employee-overview' => [
            'name' => 'Employee Overview',
            'description' => 'Employee statistics and charts',
            'icon' => 'mdi-account-group',
            'category' => 'analytics',
            'requiredPermissions' => ['READ_EMPLOYEE'],
            'minSize' => ['w' => 4, 'h' => 3],
            'defaultSize' => ['w' => 6, 'h' => 4],
            'realtime' => true
        ],
        'report-analytics' => [
            'name' => 'Report Analytics',
            'description' => 'Report statistics and trends',
            'icon' => 'mdi-chart-line',
            'category' => 'analytics',
            'requiredPermissions' => ['READ_REPORT'],
            'minSize' => ['w' => 6, 'h' => 4],
            'defaultSize' => ['w' => 8, 'h' => 5],
            'realtime' => true
        ]
        // Add more widgets as needed...
    ];
}
