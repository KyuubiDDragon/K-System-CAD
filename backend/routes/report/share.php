<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../models/Report.php';
require_once __DIR__ . '/../../models/Authority.php';
require_once __DIR__ . '/../../models/Role.php';
require_once __DIR__ . '/../../helpers/permission_helper.php';

// Prüfen, ob Benutzer angemeldet ist
if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Nicht angemeldet']);
    exit;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// Für alle Share-bezogenen Aktionen SHARE_REPORT-Berechtigung verlangen (außer getSharedReports)
if ($action !== 'getSharedReports' && $action !== 'getReportSharing' && !hasPermission('SHARE_REPORT')) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Keine Berechtigung für diese Aktion']);
    exit;
}

// POST-Daten bei JSON-Request-Body parsen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $postData = json_decode(file_get_contents('php://input'), true);
    if ($postData) {
        foreach ($postData as $key => $value) {
            $_POST[$key] = $value;
        }
    }
}

switch ($action) {
    case 'getSharedReports':
        getSharedReports();
        break;
    case 'getReportSharing':
        getReportSharing();
        break;
    case 'getSharingOptions':
        getSharingOptions();
        break;
    case 'setVisibility':
        setReportVisibility();
        break;
    case 'shareReport':
        shareReport();
        break;
    case 'unshareReport':
        unshareReport();
        break;
    case 'addRoleAccess':
        addRoleAccess();
        break;
    case 'removeRoleAccess':
        removeRoleAccess();
        break;
    default:
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Ungültige Aktion']);
        exit;
}

/**
 * Gibt alle mit dem aktuellen Benutzer geteilten Berichte zurück
 */
function getSharedReports() {
    global $db, $loggedInUser;
    
    try {
        // Berichte abrufen, die mit der Authority des aktuellen Benutzers geteilt wurden
        $stmt = $db->prepare("
            SELECT 
                r.id, 
                r.title, 
                r.description, 
                r.created_at, 
                r.updated_at,
                r.author_id,
                r.status,
                rs.access_level,
                a.display_name AS source_authority_display,
                a.id AS source_authority_id
            FROM kdd_reports r
            JOIN kdd_report_sharing rs ON r.id = rs.report_id
            JOIN kdd_authorities a ON r.authority_id = a.id
            WHERE rs.shared_with_authority_id = ?
            ORDER BY r.updated_at DESC
        ");
        
        $stmt->bind_param('i', $loggedInUser['authority_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sharedReports = [];
        while ($row = $result->fetch_assoc()) {
            $sharedReports[] = $row;
        }
        
        echo json_encode($sharedReports);
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Fehler beim Abrufen geteilter Berichte: ' . $e->getMessage()]);
        exit;
    }
}

/**
 * Gibt Informationen zur Freigabe eines Berichts zurück
 */
function getReportSharing() {
    global $db, $loggedInUser;
    
    // Erforderliche Parameter prüfen
    if (!isset($_REQUEST['report_id'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'report_id ist erforderlich']);
        exit;
    }
    
    $reportId = intval($_REQUEST['report_id']);
    
    try {
        // Berichtsinformationen abrufen
        $reportStmt = $db->prepare("
            SELECT 
                r.id, 
                r.title, 
                r.authority_id,
                r.visibility_type,
                a.display_name AS authority_display
            FROM kdd_reports r
            JOIN kdd_authorities a ON r.authority_id = a.id
            WHERE r.id = ?
        ");
        
        $reportStmt->bind_param('i', $reportId);
        $reportStmt->execute();
        $reportResult = $reportStmt->get_result();
        
        if ($reportResult->num_rows === 0) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Bericht nicht gefunden']);
            exit;
        }
        
        $report = $reportResult->fetch_assoc();
        
        // Prüfen, ob der Benutzer Zugriff auf den Bericht hat
        $canAccess = false;
        
        // Fall 1: Benutzer ist in derselben Authority wie der Bericht
        if ($report['authority_id'] == $loggedInUser['authority_id']) {
            $canAccess = true;
        } 
        // Fall 2: Der Bericht wurde mit der Authority des Benutzers geteilt
        else {
            $sharingStmt = $db->prepare("
                SELECT access_level, shared_at
                FROM kdd_report_sharing
                WHERE report_id = ? AND shared_with_authority_id = ?
            ");
            
            $sharingStmt->bind_param('ii', $reportId, $loggedInUser['authority_id']);
            $sharingStmt->execute();
            $sharingResult = $sharingStmt->get_result();
            
            if ($sharingResult->num_rows > 0) {
                $canAccess = true;
                $sharingInfo = $sharingResult->fetch_assoc();
                // Informationen zum gemeinsamen Bericht hinzufügen
                $report['shared_from'] = [
                    'authority_id' => $report['authority_id'],
                    'authority_display' => $report['authority_display'],
                    'access_level' => $sharingInfo['access_level'],
                    'shared_at' => $sharingInfo['shared_at']
                ];
            }
        }
        
        if (!$canAccess) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error' => 'Keine Berechtigung für diesen Bericht']);
            exit;
        }
        
        // Nur für Eigentümer des Berichts die Freigabedetails abrufen
        $sharedWith = [];
        $roles = [];
        
        if ($report['authority_id'] == $loggedInUser['authority_id']) {
            // 1. Mit welchen Authorities ist der Bericht geteilt
            $sharedWithStmt = $db->prepare("
                SELECT 
                    rs.shared_with_authority_id AS authority_id,
                    a.display_name,
                    rs.access_level,
                    rs.shared_at
                FROM kdd_report_sharing rs
                JOIN kdd_authorities a ON rs.shared_with_authority_id = a.id
                WHERE rs.report_id = ?
            ");
            
            $sharedWithStmt->bind_param('i', $reportId);
            $sharedWithStmt->execute();
            $sharedWithResult = $sharedWithStmt->get_result();
            
            while ($row = $sharedWithResult->fetch_assoc()) {
                $sharedWith[] = $row;
            }
            
            // 2. Wenn visibility_type 'specific_roles' ist, Rollen mit Zugriff abrufen
            if ($report['visibility_type'] === 'specific_roles') {
                $rolesStmt = $db->prepare("
                    SELECT 
                        r.id,
                        r.name,
                        r.display_name
                    FROM kdd_user_roles r
                    JOIN kdd_report_role_access ra ON r.id = ra.role_id
                    WHERE ra.report_id = ?
                ");
                
                $rolesStmt->bind_param('i', $reportId);
                $rolesStmt->execute();
                $rolesResult = $rolesStmt->get_result();
                
                while ($row = $rolesResult->fetch_assoc()) {
                    $roles[] = $row;
                }
            }
        }
        
        // Antwort zusammenstellen
        $response = [
            'report' => $report,
            'shared_with' => $sharedWith,
            'roles' => $roles
        ];
        
        echo json_encode($response);
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Fehler beim Abrufen der Freigabeinformationen: ' . $e->getMessage()]);
        exit;
    }
}

/**
 * Gibt Optionen für die Freigabe zurück (verfügbare Authorities und Rollen)
 */
function getSharingOptions() {
    global $db, $loggedInUser;
    
    try {
        // 1. Verfügbare Authorities für die Freigabe abrufen (ohne die eigene)
        $authoritiesStmt = $db->prepare("
            SELECT id, display_name, name
            FROM kdd_authorities
            WHERE id != ?
            ORDER BY display_name
        ");
        
        $authoritiesStmt->bind_param('i', $loggedInUser['authority_id']);
        $authoritiesStmt->execute();
        $authoritiesResult = $authoritiesStmt->get_result();
        
        $authorities = [];
        while ($row = $authoritiesResult->fetch_assoc()) {
            $authorities[] = $row;
        }
        
        // 2. Verfügbare Rollen in der eigenen Authority abrufen
        $rolesStmt = $db->prepare("
            SELECT id, name, display_name
            FROM kdd_user_roles
            WHERE authority_id = ?
            ORDER BY display_name
        ");
        
        $rolesStmt->bind_param('i', $loggedInUser['authority_id']);
        $rolesStmt->execute();
        $rolesResult = $rolesStmt->get_result();
        
        $roles = [];
        while ($row = $rolesResult->fetch_assoc()) {
            $roles[] = $row;
        }
        
        // Antwort zusammenstellen
        $response = [
            'authorities' => $authorities,
            'roles' => $roles
        ];
        
        echo json_encode($response);
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Fehler beim Abrufen der Freigabeoptionen: ' . $e->getMessage()]);
        exit;
    }
}

/**
 * Setzt die Sichtbarkeit eines Berichts
 */
function setReportVisibility() {
    global $db, $loggedInUser;
    
    // Erforderliche Parameter prüfen
    if (!isset($_POST['report_id']) || !isset($_POST['visibility_type'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'report_id und visibility_type sind erforderlich']);
        exit;
    }
    
    $reportId = intval($_POST['report_id']);
    $visibilityType = $_POST['visibility_type'];
    
    // Nur gültige Sichtbarkeitstypen erlauben
    $validTypes = ['private', 'public', 'specific_roles'];
    if (!in_array($visibilityType, $validTypes)) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Ungültiger visibility_type. Mögliche Werte: ' . implode(', ', $validTypes)]);
        exit;
    }
    
    try {
        // Prüfen, ob der Benutzer der Eigentümer des Berichts ist
        $ownerCheckStmt = $db->prepare("
            SELECT authority_id 
            FROM kdd_reports 
            WHERE id = ?
        ");
        
        $ownerCheckStmt->bind_param('i', $reportId);
        $ownerCheckStmt->execute();
        $ownerResult = $ownerCheckStmt->get_result();
        
        if ($ownerResult->num_rows === 0) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Bericht nicht gefunden']);
            exit;
        }
        
        $reportData = $ownerResult->fetch_assoc();
        
        if ($reportData['authority_id'] != $loggedInUser['authority_id']) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error' => 'Sie können nur die Sichtbarkeit von Berichten Ihrer eigenen Authority ändern']);
            exit;
        }
        
        // Sichtbarkeit aktualisieren
        $updateStmt = $db->prepare("
            UPDATE kdd_reports
            SET visibility_type = ?
            WHERE id = ?
        ");
        
        $updateStmt->bind_param('si', $visibilityType, $reportId);
        $updateStmt->execute();
        
        // Wenn nicht mehr 'specific_roles', dann alle Rollenzuweisungen entfernen
        if ($visibilityType !== 'specific_roles') {
            $removeRolesStmt = $db->prepare("
                DELETE FROM kdd_report_role_access
                WHERE report_id = ?
            ");
            
            $removeRolesStmt->bind_param('i', $reportId);
            $removeRolesStmt->execute();
        }
        
        echo json_encode(['success' => true, 'message' => 'Sichtbarkeit aktualisiert']);
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Fehler beim Aktualisieren der Sichtbarkeit: ' . $e->getMessage()]);
        exit;
    }
}

/**
 * Teilt einen Bericht mit einer anderen Authority
 */
function shareReport() {
    global $db, $loggedInUser;
    
    // Erforderliche Parameter prüfen
    if (!isset($_POST['report_id']) || !isset($_POST['target_authority_id']) || !isset($_POST['access_level'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'report_id, target_authority_id und access_level sind erforderlich']);
        exit;
    }
    
    $reportId = intval($_POST['report_id']);
    $targetAuthorityId = intval($_POST['target_authority_id']);
    $accessLevel = $_POST['access_level'];
    
    // Nur gültige Zugriffsebenen erlauben
    $validLevels = ['read', 'edit'];
    if (!in_array($accessLevel, $validLevels)) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Ungültiger access_level. Mögliche Werte: ' . implode(', ', $validLevels)]);
        exit;
    }
    
    try {
        // Prüfen, ob der Benutzer der Eigentümer des Berichts ist
        $ownerCheckStmt = $db->prepare("
            SELECT authority_id 
            FROM kdd_reports 
            WHERE id = ?
        ");
        
        $ownerCheckStmt->bind_param('i', $reportId);
        $ownerCheckStmt->execute();
        $ownerResult = $ownerCheckStmt->get_result();
        
        if ($ownerResult->num_rows === 0) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Bericht nicht gefunden']);
            exit;
        }
        
        $reportData = $ownerResult->fetch_assoc();
        
        if ($reportData['authority_id'] != $loggedInUser['authority_id']) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error' => 'Sie können nur Berichte Ihrer eigenen Authority teilen']);
            exit;
        }
        
        // Prüfen, ob die Ziel-Authority existiert
        $authorityCheckStmt = $db->prepare("
            SELECT id FROM kdd_authorities WHERE id = ?
        ");
        
        $authorityCheckStmt->bind_param('i', $targetAuthorityId);
        $authorityCheckStmt->execute();
        
        if ($authorityCheckStmt->get_result()->num_rows === 0) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Ziel-Authority nicht gefunden']);
            exit;
        }
        
        // Nicht mit sich selbst teilen
        if ($targetAuthorityId == $loggedInUser['authority_id']) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Sie können einen Bericht nicht mit Ihrer eigenen Authority teilen']);
            exit;
        }
        
        // Prüfen, ob der Bericht bereits geteilt ist, und ggf. aktualisieren
        $sharingCheckStmt = $db->prepare("
            SELECT id FROM kdd_report_sharing 
            WHERE report_id = ? AND shared_with_authority_id = ?
        ");
        
        $sharingCheckStmt->bind_param('ii', $reportId, $targetAuthorityId);
        $sharingCheckStmt->execute();
        $sharingResult = $sharingCheckStmt->get_result();
        
        if ($sharingResult->num_rows > 0) {
            // Vorhandene Freigabe aktualisieren
            $updateStmt = $db->prepare("
                UPDATE kdd_report_sharing
                SET access_level = ?
                WHERE report_id = ? AND shared_with_authority_id = ?
            ");
            
            $updateStmt->bind_param('sii', $accessLevel, $reportId, $targetAuthorityId);
            $updateStmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Freigabe aktualisiert']);
        } else {
            // Neue Freigabe erstellen
            $now = date('Y-m-d H:i:s');
            $insertStmt = $db->prepare("
                INSERT INTO kdd_report_sharing (report_id, shared_with_authority_id, access_level, shared_at)
                VALUES (?, ?, ?, ?)
            ");
            
            $insertStmt->bind_param('iiss', $reportId, $targetAuthorityId, $accessLevel, $now);
            $insertStmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Bericht erfolgreich geteilt']);
        }
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Fehler beim Teilen des Berichts: ' . $e->getMessage()]);
        exit;
    }
}

/**
 * Hebt die Freigabe eines Berichts für eine Authority auf
 */
function unshareReport() {
    global $db, $loggedInUser;
    
    // Erforderliche Parameter prüfen
    if (!isset($_POST['report_id']) || !isset($_POST['target_authority_id'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'report_id und target_authority_id sind erforderlich']);
        exit;
    }
    
    $reportId = intval($_POST['report_id']);
    $targetAuthorityId = intval($_POST['target_authority_id']);
    
    try {
        // Prüfen, ob der Benutzer der Eigentümer des Berichts ist
        $ownerCheckStmt = $db->prepare("
            SELECT authority_id 
            FROM kdd_reports 
            WHERE id = ?
        ");
        
        $ownerCheckStmt->bind_param('i', $reportId);
        $ownerCheckStmt->execute();
        $ownerResult = $ownerCheckStmt->get_result();
        
        if ($ownerResult->num_rows === 0) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Bericht nicht gefunden']);
            exit;
        }
        
        $reportData = $ownerResult->fetch_assoc();
        
        if ($reportData['authority_id'] != $loggedInUser['authority_id']) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error' => 'Sie können nur Freigaben von Berichten Ihrer eigenen Authority aufheben']);
            exit;
        }
        
        // Freigabe aufheben
        $deleteStmt = $db->prepare("
            DELETE FROM kdd_report_sharing
            WHERE report_id = ? AND shared_with_authority_id = ?
        ");
        
        $deleteStmt->bind_param('ii', $reportId, $targetAuthorityId);
        $deleteStmt->execute();
        
        if ($deleteStmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Freigabe erfolgreich aufgehoben']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Keine Freigabe gefunden']);
        }
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Fehler beim Aufheben der Freigabe: ' . $e->getMessage()]);
        exit;
    }
}

/**
 * Fügt eine Rolle zum Berichtszugriff hinzu
 */
function addRoleAccess() {
    global $db, $loggedInUser;
    
    // Erforderliche Parameter prüfen
    if (!isset($_POST['report_id']) || !isset($_POST['role_id'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'report_id und role_id sind erforderlich']);
        exit;
    }
    
    $reportId = intval($_POST['report_id']);
    $roleId = intval($_POST['role_id']);
    
    try {
        // Prüfen, ob der Benutzer der Eigentümer des Berichts ist
        $ownerCheckStmt = $db->prepare("
            SELECT r.authority_id, r.visibility_type
            FROM kdd_reports r
            WHERE r.id = ?
        ");
        
        $ownerCheckStmt->bind_param('i', $reportId);
        $ownerCheckStmt->execute();
        $ownerResult = $ownerCheckStmt->get_result();
        
        if ($ownerResult->num_rows === 0) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Bericht nicht gefunden']);
            exit;
        }
        
        $reportData = $ownerResult->fetch_assoc();
        
        if ($reportData['authority_id'] != $loggedInUser['authority_id']) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error' => 'Sie können nur Rollenzugriffe für Berichte Ihrer eigenen Authority verwalten']);
            exit;
        }
        
        // Sicherstellen, dass der Bericht auf 'specific_roles' gesetzt ist
        if ($reportData['visibility_type'] !== 'specific_roles') {
            // Aktualisieren auf specific_roles
            $updateVisibilityStmt = $db->prepare("
                UPDATE kdd_reports
                SET visibility_type = 'specific_roles'
                WHERE id = ?
            ");
            
            $updateVisibilityStmt->bind_param('i', $reportId);
            $updateVisibilityStmt->execute();
        }
        
        // Prüfen, ob die Rolle zur Authority des Benutzers gehört
        $roleCheckStmt = $db->prepare("
            SELECT id FROM kdd_user_roles 
            WHERE id = ? AND authority_id = ?
        ");
        
        $roleCheckStmt->bind_param('ii', $roleId, $loggedInUser['authority_id']);
        $roleCheckStmt->execute();
        
        if ($roleCheckStmt->get_result()->num_rows === 0) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Rolle nicht gefunden oder gehört nicht zu Ihrer Authority']);
            exit;
        }
        
        // Prüfen, ob der Zugriff bereits existiert
        $accessCheckStmt = $db->prepare("
            SELECT id FROM kdd_report_role_access 
            WHERE report_id = ? AND role_id = ?
        ");
        
        $accessCheckStmt->bind_param('ii', $reportId, $roleId);
        $accessCheckStmt->execute();
        
        if ($accessCheckStmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Zugriff bereits vorhanden']);
            exit;
        }
        
        // Rollenzugriff hinzufügen
        $insertStmt = $db->prepare("
            INSERT INTO kdd_report_role_access (report_id, role_id)
            VALUES (?, ?)
        ");
        
        $insertStmt->bind_param('ii', $reportId, $roleId);
        $insertStmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Rollenzugriff hinzugefügt']);
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Fehler beim Hinzufügen des Rollenzugriffs: ' . $e->getMessage()]);
        exit;
    }
}

/**
 * Entfernt eine Rolle vom Berichtszugriff
 */
function removeRoleAccess() {
    global $db, $loggedInUser;
    
    // Erforderliche Parameter prüfen
    if (!isset($_POST['report_id']) || !isset($_POST['role_id'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'report_id und role_id sind erforderlich']);
        exit;
    }
    
    $reportId = intval($_POST['report_id']);
    $roleId = intval($_POST['role_id']);
    
    try {
        // Prüfen, ob der Benutzer der Eigentümer des Berichts ist
        $ownerCheckStmt = $db->prepare("
            SELECT authority_id 
            FROM kdd_reports 
            WHERE id = ?
        ");
        
        $ownerCheckStmt->bind_param('i', $reportId);
        $ownerCheckStmt->execute();
        $ownerResult = $ownerCheckStmt->get_result();
        
        if ($ownerResult->num_rows === 0) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Bericht nicht gefunden']);
            exit;
        }
        
        $reportData = $ownerResult->fetch_assoc();
        
        if ($reportData['authority_id'] != $loggedInUser['authority_id']) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error' => 'Sie können nur Rollenzugriffe für Berichte Ihrer eigenen Authority verwalten']);
            exit;
        }
        
        // Rollenzugriff entfernen
        $deleteStmt = $db->prepare("
            DELETE FROM kdd_report_role_access
            WHERE report_id = ? AND role_id = ?
        ");
        
        $deleteStmt->bind_param('ii', $reportId, $roleId);
        $deleteStmt->execute();
        
        if ($deleteStmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Rollenzugriff entfernt']);
            
            // Prüfen, ob noch andere Rollen Zugriff haben
            $countStmt = $db->prepare("
                SELECT COUNT(*) as count FROM kdd_report_role_access 
                WHERE report_id = ?
            ");
            
            $countStmt->bind_param('i', $reportId);
            $countStmt->execute();
            $countResult = $countStmt->get_result()->fetch_assoc();
            
            // Wenn keine Rollen mehr Zugriff haben, die Sichtbarkeit auf 'private' setzen
            if ($countResult['count'] == 0) {
                $updateStmt = $db->prepare("
                    UPDATE kdd_reports
                    SET visibility_type = 'private'
                    WHERE id = ?
                ");
                
                $updateStmt->bind_param('i', $reportId);
                $updateStmt->execute();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Kein Rollenzugriff gefunden']);
        }
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Fehler beim Entfernen des Rollenzugriffs: ' . $e->getMessage()]);
        exit;
    }
} 