<?php

/**
 * Backend Endpoint: report/share.php
 * Handles all report sharing functionality including:
 * - Sharing reports with other authorities
 * - Setting report visibility within an authority
 * - Managing group (role) access to reports
 * - Retrieving shared reports and sharing options
 */

declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../bootstrap.php';

// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../db.php'; // Defines $pdo
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed."]);
    exit();
}
require_once __DIR__ . '/../logging/logging.php'; // For logDatabaseChange and helpers

// --- Authentication ---
try {
    require_once __DIR__ . '/../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Authentication system error."]);
    exit();
}

// Load permission helper
require_once __DIR__ . '/../utils/permission_helper.php';

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority) || $authorityId === null || !is_int($authorityId)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid token payload."]);
    exit();
}
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'reports')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to report features."]); exit();
}

// --- Request Parameter Parsing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Zusätzlich prüfen, ob Sharing-Feature verfügbar ist für relevante Aktionen
if (in_array($action, ['shareReport', 'removeSharing', 'updateSharingSettings']) && !hasFeatureAccess($pdo, $authorityId, 'share_reports')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to report sharing features."]); exit();
}



// --- Authorization & Action Routing ---
// Map actions to required permissions
$permissions_map = [
    'shareReport'           => 'SHARE_REPORT',
    'removeSharing'         => 'SHARE_REPORT',
    'setReportVisibility'   => 'WRITE_REPORT',
    'addGroupAccess'        => 'WRITE_REPORT',
    'removeGroupAccess'     => 'WRITE_REPORT',
    'getReportSharing'      => 'READ_REPORT',
    'getSharingOptions'     => 'READ_REPORT',
    'getSharedReports'      => 'READ_REPORT',
    'getSharedReport'       => 'READ_REPORT',
    'updateSharingSettings' => 'SHARE_REPORT',
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

// Permission check logic
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} elseif ($required_permission && hasPermission($userPermissions, $required_permission)) {
    $has_permission = true; // Has the specific required permission
} else {
    // Check hierarchy: Does WRITE imply READ for the same entity type?
    $readEquivalent = str_replace(['WRITE_', 'DELETE_'], 'READ_', $required_permission);
    $writeEquivalent = str_replace(['READ_', 'DELETE_'], 'WRITE_', $required_permission);

    if ($required_permission === $readEquivalent && hasPermission($userPermissions, $writeEquivalent)) {
        $has_permission = true; // User has WRITE which implies READ
    }
}

// --- Execute Action or Deny ---
if ($has_permission) {
    // Route the request based on action and method
    switch ($action) {
        // GET Actions
        case 'getReportSharing':
            if ($request_method === 'GET') getReportSharing($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getSharingOptions':
            if ($request_method === 'GET') getSharingOptions($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getSharedReports':
            if ($request_method === 'GET') getSharedReports($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'getSharedReport':
            if ($request_method === 'GET') getSharedReport($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
            
        // POST/PUT Actions
        case 'shareReport':
            if ($request_method === 'POST') shareReport($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'removeSharing':
            if ($request_method === 'POST' || $request_method === 'DELETE') removeSharing($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'setReportVisibility':
            if ($request_method === 'POST' || $request_method === 'PUT') setReportVisibility($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'addGroupAccess':
            if ($request_method === 'POST') addGroupAccess($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'removeGroupAccess':
            if ($request_method === 'POST' || $request_method === 'DELETE') removeGroupAccess($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
        case 'updateSharingSettings':
            if ($request_method === 'POST' || $request_method === 'PUT') updateSharingSettings($pdo, $userId, $authority, $authorityId);
            else MethodNotAllowed();
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Action not implemented.']);
            break;
    }
} else {
    // Permission denied
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied for this action.']);
}

exit();



/**
 * Share a report with another authority
 */
function shareReport(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    // Get request data
    $data = getJsonRequestData();
    if (!isset($data['report_id']) || !isset($data['authority_id']) || !isset($data['access_level'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        return;
    }

    $reportId = (int)$data['report_id'];
    $targetAuthorityId = (int)$data['authority_id'];
    $accessLevel = $data['access_level'];

    // Validate access level
    if ($accessLevel !== 'read' && $accessLevel !== 'edit') {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid access level. Must be "read" or "edit".']);
        return;
    }

    // Verify report belongs to current authority
    $stmt = $pdo->prepare("SELECT id FROM kdd_reports WHERE id = ? AND authority_id = ? AND is_deleted = 0");
    $stmt->execute([$reportId, $authorityId]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Report not found or you do not have access to share it']);
        return;
    }

    // Verify target authority exists and has SHARE_REPORTS feature
    $stmt = $pdo->prepare("
        SELECT a.id FROM kdd_authorities a
        JOIN kdd_authority_features_rel afr ON a.id = afr.authority_id
        JOIN kdd_authority_features af ON afr.feature_id = af.id
        WHERE a.id = ? AND a.active = 1 AND af.code = 'share_reports'
    ");
    $stmt->execute([$targetAuthorityId]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Target authority not found or does not have sharing capability']);
        return;
    }

    // Check if already shared, if so update the access level
    $stmt = $pdo->prepare("
        SELECT id FROM kdd_report_sharing 
        WHERE report_id = ? AND target_authority_id = ?
    ");
    $stmt->execute([$reportId, $targetAuthorityId]);
    
    try {
        $pdo->beginTransaction();
        
        if ($stmt->rowCount() > 0) {
            // Update existing sharing
            $sharingId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
            $stmt = $pdo->prepare("
                UPDATE kdd_report_sharing 
                SET access_level = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$accessLevel, $sharingId]);
        } else {
            // Create new sharing
            $stmt = $pdo->prepare("
                INSERT INTO kdd_report_sharing 
                (report_id, source_authority_id, target_authority_id, access_level, shared_by_user_id) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$reportId, $authorityId, $targetAuthorityId, $accessLevel, $userId]);
        }
        
        $pdo->commit();
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Report shared successfully"]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error in shareReport: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to share report: ' . $e->getMessage()]);
    }
}

/**
 * Remove report sharing with an authority
 */
function removeSharing(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    // Get request data
    $data = getJsonRequestData();
    if (!isset($data['report_id']) || !isset($data['authority_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        return;
    }

    $reportId = (int)$data['report_id'];
    $targetAuthorityId = (int)$data['authority_id'];

    // Verify report belongs to current authority
    $stmt = $pdo->prepare("SELECT id FROM kdd_reports WHERE id = ? AND authority_id = ? AND is_deleted = 0");
    $stmt->execute([$reportId, $authorityId]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Report not found or you do not have access to manage its sharing']);
        return;
    }

    try {
        // Remove sharing
        $stmt = $pdo->prepare("
            DELETE FROM kdd_report_sharing 
            WHERE report_id = ? AND target_authority_id = ?
        ");
        $stmt->execute([$reportId, $targetAuthorityId]);
        
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Report sharing removed successfully"]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'No sharing record found to remove']);
        }
    } catch (PDOException $e) {
        error_log("Error in removeSharing: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to remove sharing: ' . $e->getMessage()]);
    }
}

/**
 * Set report visibility (public, private, specific_roles)
 */
function setReportVisibility(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    // Get request data
    $data = getJsonRequestData();
    if (!isset($data['report_id']) || !isset($data['visibility'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        return;
    }

    $reportId = (int)$data['report_id'];
    $visibility = $data['visibility'];

    // Validate visibility
    $validVisibilities = ['public', 'private', 'specific_roles'];
    if (!in_array($visibility, $validVisibilities)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid visibility setting. Must be one of: ' . implode(', ', $validVisibilities)]);
        return;
    }

    // Verify report belongs to current authority
    $stmt = $pdo->prepare("SELECT id FROM kdd_reports WHERE id = ? AND authority_id = ? AND is_deleted = 0");
    $stmt->execute([$reportId, $authorityId]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Report not found or you do not have access to change its visibility']);
        return;
    }

    try {
        $pdo->beginTransaction();
        
        // Update report visibility
        $stmt = $pdo->prepare("
            UPDATE kdd_reports 
            SET visibility_type = ?
            WHERE id = ?
        ");
        $stmt->execute([$visibility, $reportId]);

        // If changing from specific_roles to another visibility, remove all group accesses
        if ($visibility !== 'specific_roles') {
            $stmt = $pdo->prepare("
                DELETE FROM kdd_report_role_access 
                WHERE report_id = ?
            ");
            $stmt->execute([$reportId]);
        }
        
        $pdo->commit();
        
        // Log the change
        $changes = [
            ['column_name' => 'visibility_type', 'old_value' => 'previous_value', 'new_value' => $visibility]
        ];
        logDatabaseChange($authorityId, $pdo, 'UPDATE', "reports", $reportId, $userId, $changes);
        
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Report visibility updated successfully"]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error in setReportVisibility: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update report visibility: ' . $e->getMessage()]);
    }
}

/**
 * Add group (role) access to a report
 */
function addGroupAccess(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    // Get request data
    $data = getJsonRequestData();
    if (!isset($data['report_id']) || !isset($data['group_ids']) || !is_array($data['group_ids'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        return;
    }

    $reportId = (int)$data['report_id'];
    $groupIds = array_map('intval', $data['group_ids']);

    // Verify report belongs to current authority
    $stmt = $pdo->prepare("SELECT id, visibility FROM kdd_reports WHERE id = ? AND authority_id = ? AND is_deleted = 0");
    $stmt->execute([$reportId, $authorityId]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Report not found or you do not have access to manage its group access']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($report['visibility'] !== 'specific_roles') {
            // Update visibility to specific_roles first
            $stmt = $pdo->prepare("
                UPDATE kdd_reports 
                SET visibility = 'specific_roles', updated_at = NOW(), updated_by = ? 
                WHERE id = ?
            ");
            $stmt->execute([$userId, $reportId]);
        }

        // Delete all existing group access first
        $stmt = $pdo->prepare("DELETE FROM kdd_report_role_access WHERE report_id = ?");
        $stmt->execute([$reportId]);

        // Insert new group access records
        $addedCount = 0;
        foreach ($groupIds as $groupId) {
            // Verify group exists and belongs to current authority
            $stmt = $pdo->prepare("SELECT id FROM kdd_roles WHERE id = ? AND authority_id = ? AND is_deleted = 0");
            $stmt->execute([$groupId, $authorityId]);
            
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO kdd_report_role_access 
                    (report_id, role_id, created_by) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$reportId, $groupId, $userId]);
                
                if ($stmt->rowCount() > 0) {
                    $addedCount++;
                }
            }
        }
        
        $pdo->commit();
        
        // Log the change
        $changes = [
            ['column_name' => 'group_access', 'old_value' => 'previous_groups', 'new_value' => json_encode($groupIds)]
        ];
        logDatabaseChange($authorityId, $pdo, 'UPDATE', "report_group_access", $reportId, $userId, $changes);
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Added access for $addedCount groups",
            "added_count" => $addedCount
        ]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error in addGroupAccess: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add group access: ' . $e->getMessage()]);
    }
}

/**
 * Remove group (role) access from a report
 */
function removeGroupAccess(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    // Get request data
    $data = getJsonRequestData();
    if (!isset($data['report_id']) || !isset($data['group_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        return;
    }

    $reportId = (int)$data['report_id'];
    $groupId = (int)$data['group_id'];

    // Verify report belongs to current authority
    $stmt = $pdo->prepare("SELECT id FROM kdd_reports WHERE id = ? AND authority_id = ? AND is_deleted = 0");
    $stmt->execute([$reportId, $authorityId]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Report not found or you do not have access to manage its group access']);
        return;
    }

    try {
        // Remove group access
        $stmt = $pdo->prepare("
            DELETE FROM kdd_report_role_access 
            WHERE report_id = ? AND role_id = ?
        ");
        $stmt->execute([$reportId, $groupId]);
        
        if ($stmt->rowCount() > 0) {
            // Log the change
            $changes = [
                ['column_name' => 'group_removed', 'old_value' => (string)$groupId, 'new_value' => '']
            ];
            logDatabaseChange($authorityId, $pdo, 'DELETE', "report_group_access", $reportId, $userId, $changes);
            
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Group access removed successfully"]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'No group access record found to remove']);
        }
    } catch (PDOException $e) {
        error_log("Error in removeGroupAccess: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to remove group access: ' . $e->getMessage()]);
    }
}

/**
 * Get report sharing information
 */
function getReportSharing(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    // Get report ID from request
    if (!isset($_REQUEST['report_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing report_id parameter']);
        return;
    }

    $reportId = (int)$_REQUEST['report_id'];

    try {
        // First determine if this is a report owned by current authority or a shared report
        $stmt = $pdo->prepare("
            SELECT r.*, a.name as authority_name, a.display_name as authority_display_name 
            FROM kdd_reports r
            JOIN kdd_authorities a ON r.authority_id = a.id
            WHERE r.id = ? AND r.is_deleted = 0
        ");
        $stmt->execute([$reportId]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Report not found']);
            return;
        }

        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        $reportData = [
            'id' => $report['id'],
            'title' => $report['title'],
            'created_at' => $report['created_at'],
            'updated_at' => $report['updated_at']
        ];

        $sharingInfo = [
            'report' => $reportData,
            'visibility' => 'private', // Default
            'groups' => [],
            'shared_with' => []
        ];

        // Check if this is a report owned by current authority
        if ($report['authority_id'] == $authorityId) {
            // Add visibility setting
            $sharingInfo['visibility'] = $report['visibility_type'] ?? 'private';

            // Get groups that have access (if visibility is specific_roles)
            if (($report['visibility_type'] ?? 'private') === 'specific_roles') {
                $stmt = $pdo->prepare("
                    SELECT r.id, r.name, r.description
                    FROM kdd_report_role_access ga
                    JOIN kdd_roles r ON ga.role_id = r.id
                    WHERE ga.report_id = ? AND r.is_deleted = 0
                ");
                $stmt->execute([$reportId]);
                
                $sharingInfo['groups'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Get authorities this report is shared with
            $stmt = $pdo->prepare("
                SELECT a.id, a.name, a.display_name, rs.access_level, rs.created_at
                FROM kdd_report_sharing rs
                JOIN kdd_authorities a ON rs.target_authority_id = a.id
                WHERE rs.report_id = ? AND a.active = 1
            ");
            $stmt->execute([$reportId]);
            
            $sharingInfo['shared_with'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // This is a shared report - check if it's shared with us
            $stmt = $pdo->prepare("
                SELECT rs.*, a.name as owner_name, a.display_name as owner_display_name
                FROM kdd_report_sharing rs
                JOIN kdd_authorities a ON rs.source_authority_id = a.id
                WHERE rs.report_id = ? AND rs.target_authority_id = ?
            ");
            $stmt->execute([$reportId, $authorityId]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(403);
                echo json_encode(['error' => 'This report is not shared with your authority']);
                return;
            }

            $sharingData = $stmt->fetch(PDO::FETCH_ASSOC);
            $sharingInfo['shared_from'] = [
                'owner_id' => $sharingData['source_authority_id'],
                'owner_name' => $sharingData['owner_name'],
                'owner_display_name' => $sharingData['owner_display_name'],
                'access_level' => $sharingData['access_level']
            ];
        }

        http_response_code(200);
        echo json_encode($sharingInfo);
    } catch (PDOException $e) {
        error_log("Error in getReportSharing: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Error retrieving report sharing information: ' . $e->getMessage()]);
    }
}

/**
 * Get available sharing options (authorities with SHARE_REPORTS feature and roles)
 */
function getSharingOptions(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        // Get authorities with SHARE_REPORTS feature, excluding current authority
        $stmt = $pdo->prepare("
            SELECT DISTINCT a.id, a.name, a.display_name
            FROM kdd_authorities a
            JOIN kdd_authority_features_rel afr ON a.id = afr.authority_id
            JOIN kdd_authority_features af ON afr.feature_id = af.id
            WHERE a.active = 1 AND a.id != ? AND af.code = 'share_reports'
            ORDER BY a.display_name
        ");
        $stmt->execute([$authorityId]);
        $authorities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get groups (roles) from the current authority
        $stmt = $pdo->prepare("
            SELECT id, name, description, power
            FROM kdd_roles
            WHERE authority_id = ? AND is_deleted = 0
            ORDER BY sort_order, name
        ");
        $stmt->execute([$authorityId]);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'authorities' => $authorities,
            'groups' => $groups
        ]);
    } catch (PDOException $e) {
        error_log("Error in getSharingOptions: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Error retrieving sharing options: ' . $e->getMessage()]);
    }
}

/**
 * Get reports shared with the current authority
 */
function getSharedReports(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        // Get reports shared with current authority with more details
        $stmt = $pdo->prepare("
            SELECT r.id, r.title, r.report_date, r.description, r.text, r.report_status_id, r.location,
                   r.category_id, r.report_code_id, r.created_at, r.updated_at, 
                   rs.access_level, rs.created_at as shared_at,
                   a.id as source_authority_id, a.name as source_authority, a.display_name as source_authority_display,
                   r.creator as creator_id, 
                   COALESCE(u.username, 'Unbekannter Benutzer') as creator_name,
                   COALESCE(CONCAT('[', e.servicenumber, '] ', e.name), 'Unbekannter Ersteller') as creator_display_name,
                   c.title as category_name, s.name as status_name, rc.code as report_code_name
            FROM kdd_report_sharing rs
            JOIN kdd_reports r ON rs.report_id = r.id
            JOIN kdd_authorities a ON rs.source_authority_id = a.id
            LEFT JOIN kdd_users u ON r.creator = u.id
            LEFT JOIN kdd_employee e ON u.linked_employee = e.id AND e.is_terminated = 0
            LEFT JOIN kdd_report_category c ON r.category_id = c.id
            LEFT JOIN kdd_report_status s ON r.report_status_id = s.id
            LEFT JOIN kdd_report_code rc ON r.report_code_id = rc.id
            WHERE rs.target_authority_id = ? AND r.is_deleted = 0
            ORDER BY rs.created_at DESC
        ");
        $stmt->execute([$authorityId]);
        
        $sharedReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        // Antwort im Format { data: [...] } zurückgeben, um mit anderen API-Endpunkten konsistent zu sein
        echo json_encode(['data' => $sharedReports]);
    } catch (PDOException $e) {
        error_log("Error in getSharedReports: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Error retrieving shared reports: ' . $e->getMessage()]);
    }
}

/**
 * Get a single shared report with full details
 */
function getSharedReport(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        // Get report ID from request parameters
        $reportId = isset($_GET['id']) ? (int)$_GET['id'] : null;
        
        if (!$reportId) {
            http_response_code(400);
            echo json_encode(['error' => 'Report ID is required.']);
            return;
        }

        // Check if this report is shared with the current authority
        $sharingCheck = $pdo->prepare("
            SELECT rs.access_level, rs.created_at as shared_at,
                   a.id as source_authority_id, a.name as source_authority, 
                   a.display_name as source_authority_display
            FROM kdd_report_sharing rs
            JOIN kdd_authorities a ON rs.source_authority_id = a.id
            WHERE rs.report_id = ? AND rs.target_authority_id = ? 
        ");
        $sharingCheck->execute([$reportId, $authorityId]);
        
        if ($sharingCheck->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Report is not shared with your authority.']);
            return;
        }
        
        $sharingInfo = $sharingCheck->fetch(PDO::FETCH_ASSOC);

        // Main report query with all necessary joins
        $stmt = $pdo->prepare("
            SELECT r.*, 
                   c.title as category_name, 
                   rc.code as report_code,
                   rc.description as report_code_description,
                   rs.name as status_name,
                   COALESCE(u.username, 'Unbekannter Benutzer') as creator_name,
                   COALESCE(CONCAT('[', e.servicenumber, '] ', e.name), 'Unbekannter Ersteller') as creator_display_name
            FROM kdd_reports r
            LEFT JOIN kdd_report_category c ON r.category_id = c.id
            LEFT JOIN kdd_report_code rc ON r.report_code_id = rc.id
            LEFT JOIN kdd_report_status rs ON r.report_status_id = rs.id
            LEFT JOIN kdd_users u ON r.creator = u.id
            LEFT JOIN kdd_employee e ON u.linked_employee = e.id AND e.is_terminated = 0
            WHERE r.id = ? AND r.is_deleted = 0
        ");
        $stmt->execute([$reportId]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Report not found.']);
            return;
        }
        
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Add sharing information to the report
        $report['access_level'] = $sharingInfo['access_level'];
        $report['shared_at'] = $sharingInfo['shared_at'];
        $report['source_authority_id'] = $sharingInfo['source_authority_id'];
        $report['source_authority'] = $sharingInfo['source_authority'];
        $report['source_authority_display'] = $sharingInfo['source_authority_display'];
        
        // Get linked persons
        $stmt = $pdo->prepare("
            SELECT id_person as person_id 
            FROM kdd_report_person_rel 
            WHERE id_report = ?
        ");
        $stmt->execute([$reportId]);
        $linkedPersons = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $report['linked_persons'] = $linkedPersons ?: [];
        
        // Get linked companies
        $stmt = $pdo->prepare("
            SELECT id_company as company_id 
            FROM kdd_report_company_rel 
            WHERE id_report = ?
        ");
        $stmt->execute([$reportId]);
        $linkedCompanies = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $report['linked_companies'] = $linkedCompanies ?: [];
        
        // Get missing employees
        $stmt = $pdo->prepare("
            SELECT employee_id 
            FROM kdd_report_missing_rel 
            WHERE report_id = ?
        ");
        $stmt->execute([$reportId]);
        $missingEmployees = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $report['missing_employees'] = $missingEmployees ?: [];
        
        // Get additionals
        $stmt = $pdo->prepare("
            SELECT rel.id, rel.additionals_id, rel.price, rel.units, rel.amount, a.name, a.description
            FROM kdd_report_additionals_rel rel
            JOIN kdd_report_additionals a ON rel.additionals_id = a.id
            WHERE rel.report_id = ?
        ");
        $stmt->execute([$reportId]);
        $additionals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $report['additionals'] = $additionals ?: [];
        
        // Make sure scalar properties are in correct format
        $report['approved'] = (bool)$report['approved'];
        $report['is_deleted'] = (bool)$report['is_deleted'];
        $report['pinned'] = isset($report['pinned']) ? (bool)$report['pinned'] : false;
        
        // Parse custom_fields if it exists and is JSON
        if (isset($report['custom_fields']) && !empty($report['custom_fields'])) {
            try {
                $customFields = json_decode($report['custom_fields'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $report['custom_fields'] = $customFields;
                }
            } catch (\Exception $e) {
                // Keep as string if parsing fails
            }
        }
        
        http_response_code(200);
        echo json_encode(['data' => $report]);
    } catch (PDOException $e) {
        error_log("Database error in getSharedReport: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error. Please try again later.']);
    }
}

/**
 * Update all sharing settings at once
 */
function updateSharingSettings(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    // Get request data
    $data = getJsonRequestData();
    if (!isset($data['report_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing report_id parameter']);
        return;
    }

    $reportId = (int)$data['report_id'];

    // Verify report belongs to current authority
    $stmt = $pdo->prepare("SELECT id FROM kdd_reports WHERE id = ? AND authority_id = ? AND is_deleted = 0");
    $stmt->execute([$reportId, $authorityId]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Report not found or you do not have access to update its sharing settings']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // 1. Update visibility if provided
        if (isset($data['visibility_type'])) {
            $visibility = $data['visibility_type'];
            $validVisibilities = ['public', 'private', 'specific_roles'];
            if (!in_array($visibility, $validVisibilities)) {
                throw new Exception('Invalid visibility setting');
            }

            $stmt = $pdo->prepare("
                UPDATE kdd_reports 
                SET visibility_type = ? 
                WHERE id = ?
            ");
            $stmt->execute([$visibility, $reportId]);

            // If changing from specific_roles to another visibility, remove all group accesses
            if ($visibility !== 'specific_roles') {
                $stmt = $pdo->prepare("DELETE FROM kdd_report_role_access WHERE report_id = ?");
                $stmt->execute([$reportId]);
            }
        }

        // 2. Update group access if provided and visibility is specific_roles
        if (
            isset($data['groups']) && is_array($data['groups']) &&
            (isset($data['visibility_type']) && $data['visibility_type'] === 'specific_roles')
        ) {
            // First remove all existing group access
            $stmt = $pdo->prepare("DELETE FROM kdd_report_role_access WHERE report_id = ?");
            $stmt->execute([$reportId]);

            // Add new group accesses
            foreach ($data['groups'] as $groupId) {
                $groupId = (int)$groupId;

                // Verify group exists and belongs to current authority
                $stmt = $pdo->prepare("SELECT id FROM kdd_roles WHERE id = ? AND authority_id = ? AND is_deleted = 0");
                $stmt->execute([$groupId, $authorityId]);
                
                if ($stmt->rowCount() > 0) {
                    $stmt = $pdo->prepare("
                        INSERT INTO kdd_report_role_access 
                        (report_id, role_id, created_by, authority_id) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$reportId, $groupId, $userId, $authorityId]);
                }
            }
        }

        // 3. Update authority sharing if provided
        if (
            isset($data['sharedAuthorities']) && is_array($data['sharedAuthorities']) &&
            isset($data['accessLevels']) && is_array($data['accessLevels'])
        ) {
            // Get current sharing to determine what needs to be added/removed/updated
            $stmt = $pdo->prepare("
                SELECT target_authority_id, access_level 
                FROM kdd_report_sharing 
                WHERE report_id = ?
            ");
            $stmt->execute([$reportId]);
            
            $currentSharing = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $currentSharing[$row['target_authority_id']] = $row['access_level'];
            }

            // Process each authority from the data
            foreach ($data['sharedAuthorities'] as $authId => $isShared) {
                $targetAuthorityId = (int)$authId;
                $wasShared = isset($currentSharing[$targetAuthorityId]);

                // Skip if authority ID is not valid
                if ($targetAuthorityId <= 0) continue;

                if ($isShared) {
                    $accessLevel = $data['accessLevels'][$authId] ?? 'read';
                    if ($accessLevel !== 'read' && $accessLevel !== 'edit') {
                        $accessLevel = 'read'; // Default to read if invalid
                    }

                    if ($wasShared) {
                        // Authority was already shared, update access level if changed
                        $stmt = $pdo->prepare("
                            UPDATE kdd_report_sharing 
                            SET access_level = ?, updated_at = NOW() 
                            WHERE report_id = ? AND target_authority_id = ?
                        ");
                        $stmt->execute([$accessLevel, $reportId, $targetAuthorityId]);
                    } else {
                        // New sharing
                        $stmt = $pdo->prepare("
                            INSERT INTO kdd_report_sharing 
                            (report_id, source_authority_id, target_authority_id, access_level) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$reportId, $authorityId, $targetAuthorityId, $accessLevel]);
                    }
                } else if ($wasShared) {
                    // Remove sharing
                    $stmt = $pdo->prepare("
                        DELETE FROM kdd_report_sharing 
                        WHERE report_id = ? AND target_authority_id = ?
                    ");
                    $stmt->execute([$reportId, $targetAuthorityId]);
                }
            }
        }

        $pdo->commit();
        
        // Log the change
        $changes = [
            ['column_name' => 'sharing_settings', 'old_value' => 'previous_settings', 'new_value' => 'updated_settings']
        ];
        logDatabaseChange($authorityId, $pdo, 'UPDATE', "report_sharing", $reportId, $userId, $changes);
        
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Sharing settings updated successfully"]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error in updateSharingSettings: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update sharing settings: ' . $e->getMessage()]);
    }
}

/**
 * Check if a user can access a report based on visibility and group settings
 */
function canUserAccessReport(PDO $pdo, int $reportId, int $userId): bool
{
    try {
        // Get report details
        $stmt = $pdo->prepare("
            SELECT r.*, u.id as creator_id
            FROM kdd_reports r 
            LEFT JOIN kdd_users u ON r.created_by = u.id
            WHERE r.id = ? AND r.is_deleted = 0
        ");
        $stmt->execute([$reportId]);
        
        if ($stmt->rowCount() === 0) {
            return false;
        }

        $report = $stmt->fetch(PDO::FETCH_ASSOC);

        // Admin users can access all reports
        if (hasPermissionDirect($pdo, $userId, 'ADMIN')) {
            return true;
        }

        // Creator can always access the report
        if ($report['creator_id'] == $userId) {
            return true;
        }

        // Check visibility
        if ($report['visibility_type'] === 'public') {
            return true;
        } else if ($report['visibility_type'] === 'private') {
            // Only admins and creator (checked above) can access private reports
            return false;
        } else if ($report['visibility_type'] === 'specific_roles') {
            // Get user's roles
            $stmt = $pdo->prepare("
                SELECT role_id FROM kdd_user_roles WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            
            $userRoles = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($userRoles)) {
                return false;
            }

            // Check if any of the user's roles have access
            $placeholders = implode(',', array_fill(0, count($userRoles), '?'));
            $params = array_merge([$reportId], $userRoles);
            
            $stmt = $pdo->prepare("
                SELECT 1 FROM kdd_report_role_access 
                WHERE report_id = ? AND role_id IN ($placeholders)
                LIMIT 1
            ");
            $stmt->execute($params);
            
            return $stmt->rowCount() > 0;
        }

        return false;
    } catch (PDOException $e) {
        error_log('Error checking report access: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check if user has a specific permission (direct DB query for this file)
 */
function hasPermissionDirect(PDO $pdo, int $userId, string $permissionKey): bool
{
    try {
        $stmt = $pdo->prepare("
            SELECT 1 
            FROM kdd_user_permissions up
            JOIN kdd_permissions p ON up.permission_id = p.id
            WHERE up.user_id = ? AND p.permission_key = ?
            LIMIT 1
        ");
        $stmt->execute([$userId, $permissionKey]);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log('Error checking permission: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get Authority ID from authority name
 */
function getAuthorityId(PDO $pdo, ?string $authority): ?int
{
    if (!$authority) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = ?");
    $stmt->execute([$authority]);

    if ($stmt->rowCount() === 0) {
        return null;
    }

    return (int)$stmt->fetchColumn();
}

/**
 * Send a JSON success response
 */
function responseSuccess($data): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Send a JSON error response
 */
function responseError(string $message, int $statusCode = 400): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $message]);
    exit;
}
