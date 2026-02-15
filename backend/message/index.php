<?php
/**
 * Backend Endpoint: messages/index.php
 * Handles message operations including sending, reading, organizing and notification via WebSocket.
 * Uses Cookie-based Authentication and PDO database connection.
 */
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use WebSocket\Client;
// Direkt die SocketNotifier-Klasse einbinden
require_once __DIR__ . '/../utils/SocketNotifier.php';
use Utils\SocketNotifier;

// OPTIONS Preflight Handler
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200); exit();
}

// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../db.php'; // Defines $pdo
} catch (Exception $e) {
    http_response_code(500); echo json_encode(["error" => "Database connection failed."]); exit();
}
require_once __DIR__ . '/../logging/logging.php'; // For logDatabaseChange

// --- Authentication via JWT/Cookie ---
try {
    require_once '../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;


if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority)) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'messaging')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to messaging features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    'getMessages'           => ['module' => 'messaging', 'action' => 'read'],
    'addMessage'            => ['module' => 'messaging', 'action' => 'write'],
    'markAsRead'            => ['module' => 'messaging', 'action' => 'write'],
    'markAsPinned'          => ['module' => 'messaging', 'action' => 'write'],
    'addOrUpdateNote'       => ['module' => 'messaging', 'action' => 'write'],
    'addGroup'              => ['module' => 'messaging', 'action' => 'write'],
    'getUnreadMessagesCount'=> ['module' => 'messaging', 'action' => 'read'],
    'getUsers'              => ['module' => 'messaging', 'action' => 'read'],
    'getGroups'             => ['module' => 'messaging', 'action' => 'read'],
    'getFolders'            => ['module' => 'messaging', 'action' => 'read'],
    'addFolder'             => ['module' => 'messaging', 'action' => 'write'],
    'updateFolder'          => ['module' => 'messaging', 'action' => 'write'],
    'deleteFolder'          => ['module' => 'messaging', 'action' => 'write'],
    'updateMessage'         => ['module' => 'messaging', 'action' => 'write'],
    'deleteMessage'         => ['module' => 'messaging', 'action' => 'write'],
    'restoreMessage'        => ['module' => 'messaging', 'action' => 'write'],
    'deleteMessagePermanent' => ['module' => 'messaging', 'action' => 'delete'],
];

$required_permission = $permissions_map[$action] ?? null;
$has_permission = false;

if ($action === '') {
    http_response_code(400);
    echo json_encode(["error" => "No action specified."]);
    exit();
}

if ($required_permission === null) {
    http_response_code(404);
    echo json_encode(['error' => 'Invalid action specified.']);
    exit();
}

// Load permission helper
require_once __DIR__ . '/../utils/permission_helper.php';

/**
 * Ermittelt die authority_id anhand des authority-Namens
 * 
 * @param PDO $pdo Die Datenbankverbindung
 * @param string $authority Der Name der Authority
 * @return int|null Die ID der Authority oder null, wenn nicht gefunden
 */
function getAuthorityId(PDO $pdo, ?string $authority): ?int {
    if (!$authority) return null;
    
    try {
        $sqlAuthority = "SELECT id FROM kdd_authorities WHERE name = ?";
        $stmtAuthority = $pdo->prepare($sqlAuthority);
        $stmtAuthority->execute([$authority]);
        $authorityId = $stmtAuthority->fetchColumn();
        
        return $authorityId ? (int)$authorityId : null;
    } catch (\PDOException $e) {
        error_log("Error fetching authority_id: " . $e->getMessage());
        return null;
    }
}

$module = $required_permission['module'];
$actionType = $required_permission['action'];

// Check permission levels using module-based permissions
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} elseif (hasModulePermission($userPermissions, $module, $actionType)) {
    $has_permission = true; // Has specific permission
} elseif ($actionType === 'read' && hasModulePermission($userPermissions, $module, 'write')) {
    $has_permission = true; // WRITE implies READ
} elseif ($actionType === 'read' && hasModulePermission($userPermissions, $module, 'delete')) {
    $has_permission = true; // DELETE implies READ
} elseif ($actionType === 'write' && hasModulePermission($userPermissions, $module, 'delete')) {
    $has_permission = true; // DELETE implies WRITE
}

// --- Execute Action or Deny ---
if ($has_permission) {
    $is_post_request = ($request_method === 'POST');

    switch ($action) {
        // GET Actions
        case 'getMessages':          if ($request_method === 'GET') getMessages($pdo, $authority, $userId); else methodNotAllowed(); break;
        case 'getUnreadMessagesCount': if ($request_method === 'GET') getUnreadMessagesCount($pdo, $authority, $userId); else methodNotAllowed(); break;
        case 'getUsers':             if ($request_method === 'GET') getUsers($pdo, $authority, $userId); else methodNotAllowed(); break;
        case 'getGroups':            if ($request_method === 'GET') getGroups($pdo, $authority, $userId); else methodNotAllowed(); break;
        case 'getFolders':           if ($request_method === 'GET') getFolders($pdo, $authority, $userId); else methodNotAllowed(); break;

        // POST Actions
        case 'addMessage':           if ($is_post_request) addMessage($pdo, $authority); else methodNotAllowed(); break;
        case 'markAsRead':           if ($is_post_request) markAsRead($pdo, $authority); else methodNotAllowed(); break;
        case 'markAsPinned':         if ($is_post_request) markAsPinned($pdo, $authority); else methodNotAllowed(); break;
        case 'addOrUpdateNote':      if ($is_post_request) addOrUpdateNote($pdo, $authority); else methodNotAllowed(); break;
        case 'addGroup':             if ($is_post_request) addGroup($pdo, $authority); else methodNotAllowed(); break;
        case 'addFolder':            if ($is_post_request) addFolder($pdo, $authority, $userId); else methodNotAllowed(); break;
        case 'updateFolder':         if ($is_post_request) updateFolder($pdo, $authority); else methodNotAllowed(); break;
        case 'deleteFolder':         if ($is_post_request) deleteFolder($pdo, $authority); else methodNotAllowed(); break;
        case 'updateMessage':        if ($is_post_request) updateMessage($pdo, $authority, $userId); else methodNotAllowed(); break;
        case 'deleteMessage':        if ($is_post_request) deleteMessage($pdo, $authority); else methodNotAllowed(); break;
        case 'restoreMessage':       if ($is_post_request) restoreMessage($pdo, $authority); else methodNotAllowed(); break;
        case 'deleteMessagePermanent': if ($is_post_request) deleteMessagePermanent($pdo, $authority); else methodNotAllowed(); break;

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); 
    echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();




// --- Function Implementations ---
function addMessage(PDO $pdo, string $authority): void {
    // Get data from JSON input
    $data = getJsonRequestData();
    
    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }
    
    // Prüfe, ob ein messages-Array gesendet wurde
    if (isset($data['messages']) && is_array($data['messages'])) {
        // Mehrere Nachrichten im Batch-Modus verarbeiten
        try {
            $pdo->beginTransaction();
            $results = [];
            
            foreach ($data['messages'] as $messageData) {
                // Extrahiere und validiere erforderliche Felder für jede Nachricht
                $title = trim($messageData['title'] ?? '');
                $body = trim($messageData['body'] ?? '');
                $sender_id = $messageData['sender_id'] ?? null;
                $sender_type = $messageData['sender_type'] ?? 'user';
                $recipient_id = $messageData['recipient_id'] ?? null;
                $recipient_type = $messageData['recipient_type'] ?? 'user';
                $is_anonymous = isset($messageData['is_anonymous']) ? 
                            (filter_var($messageData['is_anonymous'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0) : 0;
                
                // Validiere die erforderlichen Felder
                if ($title === '' || $body === '' || $sender_id === null || $recipient_id === null) {
                    throw new \Exception("Fehlende Pflichtfelder in einer der Nachrichten: title, body, sender_id und recipient_id sind erforderlich");
                }
                
                // SQL für das Einfügen der Nachricht
                $sql = "INSERT INTO `kdd_messages` (authority_id, title, body, sender_id, sender_type, recipient_id, recipient_type, is_anonymous) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                
                if (!$stmt) {
                    throw new \Exception("SQL prepare statement failed: " . implode(" ", $pdo->errorInfo()));
                }
                
                $stmt->execute([$authorityId, $title, $body, $sender_id, $sender_type, $recipient_id, $recipient_type, $is_anonymous]);
                $message_id = $pdo->lastInsertId();

                // Log the message creation
                $changes = [
                    ['column_name' => 'title', 'old_value' => null, 'new_value' => $title],
                    ['column_name' => 'body', 'old_value' => null, 'new_value' => $body],
                    ['column_name' => 'sender_id', 'old_value' => null, 'new_value' => $sender_id],
                    ['column_name' => 'sender_type', 'old_value' => null, 'new_value' => $sender_type],
                    ['column_name' => 'recipient_id', 'old_value' => null, 'new_value' => $recipient_id],
                    ['column_name' => 'recipient_type', 'old_value' => null, 'new_value' => $recipient_type],
                    ['column_name' => 'is_anonymous', 'old_value' => null, 'new_value' => $is_anonymous]
                ];
                logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_messages', $message_id, $sender_id, $changes);

                // Sammle Ergebnisse für die Antwort
                $results[] = [
                    'id' => $message_id,
                    'title' => $title,
                    'recipient_id' => $recipient_id,
                    'recipient_type' => $recipient_type
                ];

                // WebSocket-Benachrichtigung senden
                $sender_name = getSenderName($pdo, $authority, $sender_id, $sender_type);
                sendWebSocketMessage($title, $sender_id, $sender_name, $recipient_id);
            }
            
            $pdo->commit();
            
            http_response_code(201); // Created
            echo json_encode([
                "success" => true,
                "message" => "Alle Nachrichten erfolgreich hinzugefügt",
                "results" => $results
            ]);
            
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error in addMessage (batch mode): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["error" => "Ein Fehler ist aufgetreten: " . $e->getMessage()]);
        }
    } else {
        // Ursprünglicher Code für einzelne Nachricht
        // Extract and validate required fields
        $title = trim($data['title'] ?? '');
        $body = trim($data['body'] ?? '');
        $sender = $data['sender_id'] ?? null;
        $recipients = $data['recipient_id'] ?? [];
        $is_anonymous = isset($data['is_anonymous']) ? 
                        (filter_var($data['is_anonymous'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0) : 0;

        if (!is_array($recipients)) {
            $recipients = [$recipients];
        }

        if ($title === '' || $body === '' || empty($recipients) || $sender === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Title, body, sender, and at least one recipient are required']);
            exit;
        }

        if (strpos($sender, 'user_') === 0) {
            $sender_id = intval(substr($sender, 5));
            $sender_type = 'user';
        } elseif (strpos($sender, 'group_') === 0) {
            $sender_id = intval(substr($sender, 6));
            $sender_type = 'group';
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid sender format']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO `kdd_messages` (authority_id, title, body, sender_id, sender_type, recipient_id, recipient_type, is_anonymous) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            if (!$stmt) {
                throw new \Exception("SQL prepare statement failed: " . implode(" ", $pdo->errorInfo()));
            }

            $recipientsData = [];
            foreach ($recipients as $recipient) {
                if (strpos($recipient, 'user_') === 0) {
                    $recipient_id = intval(substr($recipient, 5));
                    $recipient_type = 'user';
                    $recipientsData[] = ['id' => $recipient_id, 'type' => 'user'];
                } elseif (strpos($recipient, 'group_') === 0) {
                    $recipient_id = intval(substr($recipient, 6));
                    $recipient_type = 'group';
                    $group_members = getGroupMembers($pdo, $authority, $recipient_id);
                    foreach ($group_members as $member) {
                        $recipientsData[] = ['id' => $member, 'type' => 'user'];
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid recipient format']);
                    exit;
                }

                $stmt->execute([$authorityId, $title, $body, $sender_id, $sender_type, $recipient_id, $recipient_type, $is_anonymous]);
                $message_id = $pdo->lastInsertId();

                // Log the message creation
                $changes = [
                    ['column_name' => 'title', 'old_value' => null, 'new_value' => $title],
                    ['column_name' => 'body', 'old_value' => null, 'new_value' => $body],
                    ['column_name' => 'sender_id', 'old_value' => null, 'new_value' => $sender_id],
                    ['column_name' => 'sender_type', 'old_value' => null, 'new_value' => $sender_type],
                    ['column_name' => 'recipient_id', 'old_value' => null, 'new_value' => $recipient_id],
                    ['column_name' => 'recipient_type', 'old_value' => null, 'new_value' => $recipient_type],
                    ['column_name' => 'is_anonymous', 'old_value' => null, 'new_value' => $is_anonymous]
                ];
                logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_messages', $message_id, $sender_id, $changes);
            }

            $pdo->commit();

            // Fetch sender name
            $sender_name = getSenderName($pdo, $authority, $sender_id, $sender_type);

            // Send WebSocket message
            foreach ($recipientsData as $recipient) {
                sendWebSocketMessage($title, $sender_id, $sender_name, $recipient['id']);
            }

            http_response_code(201); // Created
            echo json_encode([
                "success" => true,
                "message" => "Message added successfully"        
            ]);

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error in addMessage: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
        }
    }
}

function getSenderName(PDO $pdo, string $authority, int $sender_id, string $sender_type): string {
    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            return 'Unknown'; // Authority not found
        }

        if ($sender_type === 'user') {
            $sql = "SELECT CONCAT('[', e.servicenumber, '] ', e.name) AS sender_name 
                    FROM kdd_users u
                    LEFT JOIN kdd_employee e ON u.linked_employee = e.id AND e.authority_id = ?
                    WHERE u.id = ? AND u.authority_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$authorityId, $sender_id, $authorityId]);
        } else {
            $sql = "SELECT name AS sender_name FROM kdd_message_groups WHERE id = ? AND authority_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sender_id, $authorityId]);
        }
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['sender_name'] ?? 'Unknown';
    } catch (\PDOException $e) {
        error_log("Error in getSenderName: " . $e->getMessage());
        return 'Unknown';
    }
}

function getGroupMembers(PDO $pdo, string $authority, int $group_id): array {
    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            return []; // Authority not found
        }

        $sql = "SELECT user_id FROM kdd_message_group_members mgm 
                JOIN kdd_message_groups mg ON mgm.group_id = mg.id
                WHERE mgm.group_id = ? AND mg.authority_id =?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$group_id, $authorityId]);
        
        $members = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $members[] = $row['user_id'];
        }
        return $members;
    } catch (\PDOException $e) {
        error_log("Error in getGroupMembers: " . $e->getMessage());
        return [];
    }
}

function sendWebSocketMessage(string $title, int $sender_id, string $sender_name, int $recipient_id): void {
    try {
        // API-Schlüssel aus der Umgebung oder direkt angeben
        $apiKey = getenv('SOCKET_API_KEY') ?: '0j8BD6CY2k7nYtWgQ9PhKO8HxbQJFiD5UXieXmvRkLk=';
        
        // Socket.io-Server URL aus der Umgebung oder Standardwert
        $socketUrl = getenv('SOCKET_SERVER_URL') ?: 'http://localhost:3001';
        
        // SocketNotifier initialisieren
        $notifier = new SocketNotifier($socketUrl, $apiKey);
        
        // Toast-Benachrichtigung an den Empfänger senden
        $result = $notifier->sendToast(
            $recipient_id,
            'Neue Nachricht von ' . $sender_name,
            $title,
            'info',
            'high',
            [
                'sender_id' => $sender_id,
                'sender_name' => $sender_name
            ],
            '/messages'
        );
        
        if (!$result) {
            error_log("Socket.io Toast konnte nicht gesendet werden: " . $notifier->getLastError());
        }
    } catch (\Exception $e) {
        // Log error but don't halt execution
        error_log("Failed to send message notification: " . $e->getMessage());
    }
}

function getMessages(PDO $pdo, string $authority, int $userId): void {
    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(["error" => "Authority not found in database."]);
            return;
        }

        // Wir holen Nachrichten, bei denen der Benutzer der Empfänger ist
        // oder Teil einer Gruppe ist, die der Empfänger ist
        $sql = "SELECT m.*,
                    CASE
                        WHEN m.sender_type = 'user' THEN CONCAT('[', es.servicenumber, '] ', es.name)
                        WHEN m.sender_type = 'group' THEN gs.name
                    END AS sender_name,
                    CASE
                        WHEN m.recipient_type = 'user' THEN CONCAT('[', er.servicenumber, '] ', er.name)
                        WHEN m.recipient_type = 'group' THEN gr.name
                    END AS recipient_name,
                    sf.name AS sender_folder_name, rf.name AS recipient_folder_name
                FROM kdd_messages m
                -- Sender joins
                LEFT JOIN kdd_users us ON m.sender_id = us.id AND m.sender_type = 'user'
                LEFT JOIN kdd_employee es ON us.linked_employee = es.id
                LEFT JOIN kdd_message_groups gs ON m.sender_id = gs.id AND m.sender_type = 'group'
                -- Recipient joins
                LEFT JOIN kdd_users ur ON m.recipient_id = ur.id AND m.recipient_type = 'user'
                LEFT JOIN kdd_employee er ON ur.linked_employee = er.id
                LEFT JOIN kdd_message_groups gr ON m.recipient_id = gr.id AND m.recipient_type = 'group'
                -- Folder joins
                LEFT JOIN kdd_message_folders sf ON m.sender_folder_id = sf.id
                LEFT JOIN kdd_message_folders rf ON m.recipient_folder_id = rf.id
                WHERE m.authority_id = ?
                AND ((m.recipient_id = ? AND m.recipient_type = 'user' AND m.deleted_by_recipient = 0)
                    OR (m.sender_id = ? AND m.sender_type = 'user' AND m.deleted_by_sender = 0)
                    OR (m.recipient_id IN (SELECT mg.id FROM kdd_message_groups mg 
                                          JOIN kdd_message_group_members mgm ON mg.id = mgm.group_id
                                          WHERE mgm.user_id = ? AND mg.authority_id = ?)
                        AND m.recipient_type = 'group' AND m.deleted_by_recipient = 0))
                ORDER BY m.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $authorityId,
            $userId,
            $userId,
            $userId,
            $authorityId
        ]);
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($messages);
    } catch (\PDOException $e) {
        error_log("Error in getMessages: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Ein Fehler ist aufgetreten: " . $e->getMessage()]);
    }
}

function markAsRead(PDO $pdo, string $authority): void {
    $data = getJsonRequestData();
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Message ID is required']);
        exit;
    }

    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

    try {
        // Get the current is_read status
        $sql = "SELECT is_read FROM kdd_messages WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $authorityId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            http_response_code(404);
            echo json_encode(["error" => "Message not found"]);
            exit;
        }

        $currentStatus = $result['is_read'];
        $newStatus = $currentStatus ? 0 : 1;

        // Update message status and read_at timestamp
        $sql = "UPDATE kdd_messages SET is_read = ?, 
                    read_at = CASE WHEN ? = 1 THEN CURRENT_TIMESTAMP ELSE NULL END 
                WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$newStatus, $newStatus, $id, $authorityId]);

        // Log the change
        $changes = [
            ['column_name' => 'is_read', 'old_value' => $currentStatus, 'new_value' => $newStatus]
        ];
        global $decoded_jwt;
        $userId = $decoded_jwt->userId ?? 0;
        logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_messages', $id, $userId, $changes);

        http_response_code(200);
        echo json_encode([
            "success" => "Message read status toggled",
            "new_status" => $newStatus
        ]);
    } catch (\PDOException $e) {
        error_log("DB error in markAsRead (MessageID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
    }
}

function markAsPinned(PDO $pdo, string $authority): void {
    $data = getJsonRequestData();
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Message ID is required']);
        exit;
    }

    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

    try {
        // Get the current pinned status
        $sql = "SELECT pinned FROM kdd_messages WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $authorityId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            http_response_code(404);
            echo json_encode(['error' => 'Message not found']);
            exit;
        }

        $currentStatus = $result['pinned'];
        $newStatus = $currentStatus ? 0 : 1;

        // Update message pinned status
        $sql = "UPDATE kdd_messages SET pinned = ? WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$newStatus, $id, $authorityId]);

        // Log the change
        $changes = [
            ['column_name' => 'pinned', 'old_value' => $currentStatus, 'new_value' => $newStatus]
        ];
        $userId = $decoded_jwt->userId ?? 0;
        logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_messages', $id, $userId, $changes);

        http_response_code(200);
        echo json_encode([
            'success' => 'Message pinned status toggled',
            'new_status' => $newStatus
        ]);
    } catch (\PDOException $e) {
        error_log("DB error in markAsPinned (MessageID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
    }
}

function addOrUpdateNote(PDO $pdo, string $authority): void {
    $data = getJsonRequestData();
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $note = trim($data['note'] ?? '');
    $is_sender = filter_var($data['is_sender'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if (!$id || $note === '') {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Message ID and note are required']);
        exit;
    }

    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        
        // Get old value for logging
        $oldEntry = getEntryById($pdo, $id, 'kdd_messages', $authorityId);

        $field = $is_sender ? 'sender_note' : 'recipient_note';
        $sql = "UPDATE `kdd_messages` SET $field = ? WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$note, $authorityId, $id]);

        // Log the change
        if ($oldEntry) {
            $changes = [
                ['column_name' => $field, 'old_value' => $oldEntry[$field] ?? null, 'new_value' => $note]
            ];
            $userId = $decoded_jwt->userId ?? 0;
            logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_messages', $id, $userId, $changes);
        }

        http_response_code(200);
        echo json_encode(["success" => "Note added/updated successfully"]);
    } catch (\PDOException $e) {
        error_log("DB error in addOrUpdateNote (MessageID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
    }
}

function addGroup(PDO $pdo, string $authority): void {
    $data = getJsonRequestData();
    
    $name = trim($data['name'] ?? '');
    $members = $data['members'] ?? [];

    if ($name === '' || empty($members)) {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Group name and members are required']);
        exit;
    }

    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        
        $pdo->beginTransaction();
        
        // Insert group
        $sql = "INSERT INTO `kdd_message_groups` (authority_id, name) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $name]);
        $group_id = (int)$pdo->lastInsertId();

        // Log group creation
        $changes = [
            ['column_name' => 'name', 'old_value' => null, 'new_value' => $name]
        ];
        global $decoded_jwt;
        $userId = $decoded_jwt->userId ?? 0;
        logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_message_groups', $group_id, $userId, $changes);

        // Insert members
        $sql = "INSERT INTO `kdd_message_group_members` (authority_id, group_id, user_id) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        foreach ($members as $member) {
            $member_id = filter_var($member, FILTER_VALIDATE_INT);
            if ($member_id) {
                $stmt->execute([$authorityId, $group_id, $member_id]);
                $member_insert_id = (int)$pdo->lastInsertId();

                // Log member addition
                $memberChanges = [
                    ['column_name' => 'group_id', 'old_value' => null, 'new_value' => $group_id],
                    ['column_name' => 'user_id', 'old_value' => null, 'new_value' => $member_id]
                ];
                logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_message_group_members', $member_insert_id, $userId, $memberChanges);
            }
        }

        $pdo->commit();
        
        http_response_code(201); // Created
        echo json_encode([
            "success" => true,
            "message" => "Group added successfully",
            "group_id" => $group_id
        ]);
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in addGroup (Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
    }
}

function getUnreadMessagesCount(PDO $pdo, string $authority, int $userId): void {
    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        
        $sql = "SELECT COUNT(*) as unread_count FROM `kdd_messages` WHERE authority_id = ? AND ((recipient_id = ? AND recipient_type = 'user') 
               OR (recipient_id IN (SELECT group_id FROM `kdd_message_group_members` WHERE user_id = ?) 
                   AND recipient_type = 'group'))
               AND is_read = 0 
               AND deleted_by_recipient = 0";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $userId, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode(['unread_count' => $result['unread_count'] ?? 0]);
    } catch (\PDOException $e) {
        error_log("DB error in getUnreadMessagesCount (UserID: $userId, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
    }
}

function getUsers(PDO $pdo, string $authority, int $userId): void {
    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        
        $sql = "SELECT u.id, u.username, (u.id = ?) AS is_current_user, 
                       CONCAT('[', e.servicenumber, '] ', e.name) AS employee 
                FROM kdd_users u
                LEFT JOIN `kdd_employee` e ON e.authority_id = ? AND e.id = u.linked_employee
                WHERE u.linked_employee != 0 AND u.authority = ?
                ORDER BY e.servicenumber";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $authorityId, $authority]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($users);
    } catch (\PDOException $e) {
        error_log("DB error in getUsers (Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
    }
}

function getGroups(PDO $pdo, string $authority, int $userId): void {
    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        
        $sql = "SELECT g.*, 
                       CASE WHEN gm.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_member
                FROM kdd_message_groups g
                LEFT JOIN `kdd_message_group_members` gm ON gm.authority_id = ? AND g.id = gm.group_id AND gm.user_id = ?";
                    
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $userId]);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($groups);
    } catch (\PDOException $e) {
        error_log("DB error in getGroups (UserID: $userId, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
    }
}

function getFolders(PDO $pdo, string $authority, int $userId): void {
    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        
        $sql = "SELECT * FROM `kdd_message_folders` WHERE authority_id = ? AND (user_id = ? 
               OR group_id IN (SELECT group_id FROM `kdd_message_group_members` WHERE user_id = ?))";
                   
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $userId, $userId]);
        $folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($folders);
    } catch (\PDOException $e) {
        error_log("DB error in getFolders (UserID: $userId, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
    }
}

function addFolder(PDO $pdo, string $authority, int $userId): void {
    $data = getJsonRequestData();
    
    $name = trim($data['name'] ?? '');
    $group_id = filter_var($data['group_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    $user_id = $group_id === null ? $userId : null;

    if ($name === '') {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Folder name is required']);
        exit;
    }

    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        
        $sql = "INSERT INTO `kdd_message_folders` (authority_id, name, user_id, group_id) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $name, $user_id, $group_id]);
        $folder_id = (int)$pdo->lastInsertId();

        // Log the folder creation
        $changes = [
            ['column_name' => 'name', 'old_value' => null, 'new_value' => $name],
            ['column_name' => 'user_id', 'old_value' => null, 'new_value' => $user_id],
            ['column_name' => 'group_id', 'old_value' => null, 'new_value' => $group_id]
        ];
        logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_message_folders', $folder_id, $userId, $changes);

        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Folder added successfully",
            "folder_id" => $folder_id
        ]);
    } catch (\PDOException $e) {
        error_log("DB error in addFolder (Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
    }
}

function updateFolder(PDO $pdo, string $authority): void {
    $data = getJsonRequestData();
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $name = trim($data['name'] ?? '');

    if (!$id || $name === '') {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Folder ID and name are required']);
        exit;
    }

    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        
        // Get old value for logging
        $oldEntry = getEntryById($pdo, $id, 'kdd_message_folders', $authorityId);

        $sql = "UPDATE `kdd_message_folders` SET name = ? WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $authorityId, $id]);

        // Log the change
        if ($oldEntry) {
            $changes = [
                ['column_name' => 'name', 'old_value' => $oldEntry['name'], 'new_value' => $name]
            ];
            $userId = $decoded_jwt->userId ?? 0;
            logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_message_folders', $id, $userId, $changes);
        }

        http_response_code(200);
        echo json_encode(["success" => "Folder updated successfully"]);
    } catch (\PDOException $e) {
        error_log("DB error in updateFolder (FolderID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
    }
}

function deleteFolder(PDO $pdo, string $authority): void {
    $data = getJsonRequestData();
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Folder ID is required']);
        exit;
    }

    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        
        // Get folder data BEFORE deletion for logging
        $oldEntry = getEntryById($pdo, $id, 'kdd_message_folders', $authorityId);

        $sql = "DELETE FROM kdd_message_folders WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $authorityId]);

        // Log the deletion
        if ($oldEntry) {
            $changes = [
                ['column_name' => 'name', 'old_value' => $oldEntry['name'], 'new_value' => null]
            ];
            $userId = $decoded_jwt->userId ?? 0;
            logDatabaseChange($authorityId, $pdo, 'DELETE', 'kdd_message_folders', $id, $userId, $changes);
        }

        http_response_code(200);
        echo json_encode(["success" => "Folder deleted successfully"]);
    } catch (\PDOException $e) {
        error_log("DB error in deleteFolder (FolderID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
    }
}

function deleteMessage(PDO $pdo, string $authority): void {
    $data = getJsonRequestData();
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $deleted_by_sender = isset($data['deleted_by_sender']) ? 
                         (filter_var($data['deleted_by_sender'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0) : null;
    $deleted_by_recipient = isset($data['deleted_by_recipient']) ? 
                           (filter_var($data['deleted_by_recipient'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0) : null;

    if (!$id) {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Message ID is required']);
        exit;
    }

    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        
        $fields = [];
        $params = [];
        $types = [];

        if ($deleted_by_sender !== null) {
            $fields[] = 'deleted_by_sender = ?';
            $params[] = $deleted_by_sender;
            $types[] = \PDO::PARAM_INT;
        }

        if ($deleted_by_recipient !== null) {
            $fields[] = 'deleted_by_recipient = ?';
            $params[] = $deleted_by_recipient;
            $types[] = \PDO::PARAM_INT;
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            exit;
        }

        $params[] = $authorityId;
        $params[] = $id;
        $types[] = \PDO::PARAM_INT;
        $types[] = \PDO::PARAM_INT;

        // Get old state for logging
        $oldEntry = getEntryById($pdo, $id, 'kdd_messages', $authorityId);

        $sql = "UPDATE `kdd_messages` SET " . implode(', ', $fields) . " WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);

        // Bind parameters with their types
        foreach ($params as $i => $param) {
            $paramIndex = $i + 1; // PDO parameters are 1-indexed
            $stmt->bindValue($paramIndex, $param, $types[$i]);
        }

        $stmt->execute();

        // Log the soft delete
        if ($oldEntry) {
            $changes = [];
            if ($deleted_by_sender !== null) {
                $changes[] = ['column_name' => 'deleted_by_sender', 'old_value' => $oldEntry['deleted_by_sender'] ?? 0, 'new_value' => $deleted_by_sender];
            }
            if ($deleted_by_recipient !== null) {
                $changes[] = ['column_name' => 'deleted_by_recipient', 'old_value' => $oldEntry['deleted_by_recipient'] ?? 0, 'new_value' => $deleted_by_recipient];
            }
            $userId = $decoded_jwt->userId ?? 0;
            logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_messages', $id, $userId, $changes);
        }

        http_response_code(200);
        echo json_encode(["success" => "Message updated successfully"]);
    } catch (\PDOException $e) {
        error_log("DB error in deleteMessage (MessageID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
    }
}

function checkIfSender(PDO $pdo, string $authority, int $message_id, int $user_id): bool {
    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            return false;
        }
        
        $sql = "SELECT sender_id, sender_type FROM `kdd_messages` WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $message_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return false;
        }

        $sender_id = $result['sender_id'];
        $sender_type = $result['sender_type'];

        if ($sender_type === 'user' && $sender_id == $user_id) {
            return true;
        } elseif ($sender_type === 'group') {
            $sql = "SELECT 1 FROM `kdd_message_group_members` WHERE group_id = ? AND user_id = ? LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sender_id, $user_id]);
            return $stmt->fetchColumn() !== false;
        }

        return false;
    } catch (\PDOException $e) {
        error_log("Error in checkIfSender: " . $e->getMessage());
        return false;
    }
}

function checkIfRecipient(PDO $pdo, string $authority, int $message_id, int $user_id): bool {
    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            return false;
        }
        
        $sql = "SELECT recipient_id, recipient_type FROM `kdd_messages` WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $message_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return false;
        }

        $recipient_id = $result['recipient_id'];
        $recipient_type = $result['recipient_type'];

        if ($recipient_type === 'user' && $recipient_id == $user_id) {
            return true;
        } elseif ($recipient_type === 'group') {
            $sql = "SELECT 1 FROM `kdd_message_group_members` WHERE group_id = ? AND user_id = ? LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$recipient_id, $user_id]);
            return $stmt->fetchColumn() !== false;
        }

        return false;
    } catch (\PDOException $e) {
        error_log("Error in checkIfRecipient: " . $e->getMessage());
        return false;
    }
}

function updateMessage(PDO $pdo, string $authority, int $userId): void {
    $data = getJsonRequestData();
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $sender_folder_id = filter_var($data['sender_folder_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    $recipient_folder_id = filter_var($data['recipient_folder_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    $sender_note = $data['sender_note'] ?? '';
    $recipient_note = $data['recipient_note'] ?? '';

    if (!$id) {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Message ID is required']);
        exit;
    }

    if ($sender_note === '' && $recipient_note === '' && $sender_folder_id === null && $recipient_folder_id === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Either sender_note, recipient_note, sender_folder_id, or recipient_folder_id must be provided']);
        exit;
    }

    try {
        // Get authority ID
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        
        $is_sender = checkIfSender($pdo, $authority, $id, $userId);
        $is_recipient = checkIfRecipient($pdo, $authority, $id, $userId);

        if (!$is_sender && !$is_recipient) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have permission to update this message']);
            exit;
        }

        $fields = [];
        $params = [];
        $types = [];

        if ($sender_folder_id !== null && $is_sender) {
            $fields[] = 'sender_folder_id = ?';
            $params[] = $sender_folder_id;
            $types[] = \PDO::PARAM_INT;
        }

        if ($recipient_folder_id !== null && $is_recipient) {
            $fields[] = 'recipient_folder_id = ?';
            $params[] = $recipient_folder_id;
            $types[] = \PDO::PARAM_INT;
        }

        if ($sender_note !== '' && $is_sender) {
            $fields[] = 'sender_note = ?';
            $params[] = $sender_note;
            $types[] = \PDO::PARAM_STR;
        }

        if ($recipient_note !== '' && $is_recipient) {
            $fields[] = 'recipient_note = ?';
            $params[] = $recipient_note;
            $types[] = \PDO::PARAM_STR;
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            exit;
        }

        $params[] = $authorityId;
        $params[] = $id;
        $types[] = \PDO::PARAM_INT;
        $types[] = \PDO::PARAM_INT;

        // Get old state for logging
        $oldEntry = getEntryById($pdo, $id, 'kdd_messages', $authorityId);

        $sql = "UPDATE `kdd_messages` SET " . implode(', ', $fields) . " WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);

        // Bind parameters with their types
        foreach ($params as $i => $param) {
            $paramIndex = $i + 1; // PDO parameters are 1-indexed
            $stmt->bindValue($paramIndex, $param, $types[$i]);
        }

        $stmt->execute();

        // Log the changes
        if ($oldEntry) {
            $changes = [];
            if ($is_sender && $sender_note !== '') {
                $changes[] = ['column_name' => 'sender_note', 'old_value' => $oldEntry['sender_note'] ?? '', 'new_value' => $sender_note];
            }
            if ($is_sender && $sender_folder_id !== null) {
                $changes[] = ['column_name' => 'sender_folder_id', 'old_value' => $oldEntry['sender_folder_id'] ?? null, 'new_value' => $sender_folder_id];
            }
            if ($is_recipient && $recipient_note !== '') {
                $changes[] = ['column_name' => 'recipient_note', 'old_value' => $oldEntry['recipient_note'] ?? '', 'new_value' => $recipient_note];
            }
            if ($is_recipient && $recipient_folder_id !== null) {
                $changes[] = ['column_name' => 'recipient_folder_id', 'old_value' => $oldEntry['recipient_folder_id'] ?? null, 'new_value' => $recipient_folder_id];
            }
            if (!empty($changes)) {
                logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_messages', $id, $userId, $changes);
            }
        }

        http_response_code(200);
        echo json_encode(["success" => "Message updated successfully"]);
    } catch (\PDOException $e) {
        error_log("DB error in updateMessage (MessageID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
    }
}

function restoreMessage(PDO $pdo, string $authority): void {
    $data = getJsonRequestData();
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $restore_for_sender = isset($data['restore_for_sender']) ? 
                         (filter_var($data['restore_for_sender'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0) : null;
    $restore_for_recipient = isset($data['restore_for_recipient']) ? 
                           (filter_var($data['restore_for_recipient'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0) : null;

    if (!$id) {
        global $decoded_jwt;
        $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Message ID is required']);
        exit;
    }

    try {
        global $decoded_jwt;
        $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        
        $fields = [];
        $params = [];

        if ($restore_for_sender !== null) {
            $fields[] = 'deleted_by_sender = ?';
            $params[] = 0; // Restore = set to 0
        }

        if ($restore_for_recipient !== null) {
            $fields[] = 'deleted_by_recipient = ?';
            $params[] = 0; // Restore = set to 0
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No restore options specified']);
            exit;
        }

        $params[] = $authorityId;
        $params[] = $id;

        // Get old state for logging
        $oldEntry = getEntryById($pdo, $id, 'kdd_messages', $authorityId);

        $sql = "UPDATE `kdd_messages` SET " . implode(', ', $fields) . " WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Log the restore
        if ($oldEntry) {
            $changes = [];
            if ($restore_for_sender !== null) {
                $changes[] = ['column_name' => 'deleted_by_sender', 'old_value' => $oldEntry['deleted_by_sender'] ?? 0, 'new_value' => 0];
            }
            if ($restore_for_recipient !== null) {
                $changes[] = ['column_name' => 'deleted_by_recipient', 'old_value' => $oldEntry['deleted_by_recipient'] ?? 0, 'new_value' => 0];
            }
            $userId = $decoded_jwt->userId ?? 0;
            logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_messages', $id, $userId, $changes);
        }

        http_response_code(200);
        echo json_encode(["success" => "Message restored successfully"]);
    } catch (\PDOException $e) {
        error_log("DB error in restoreMessage (MessageID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
    }
}

function deleteMessagePermanent(PDO $pdo, string $authority): void {
    $data = getJsonRequestData();
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        global $decoded_jwt;
        $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Message ID is required']);
        exit;
    }

    try {
        global $decoded_jwt;
        $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }
        
        // Get message data BEFORE deletion for logging
        $oldEntry = getEntryById($pdo, $id, 'kdd_messages', $authorityId);

        $sql = "DELETE FROM `kdd_messages` WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $id]);

        if ($stmt->rowCount() > 0) {
            // Log the permanent deletion
            if ($oldEntry) {
                $changes = [
                    ['column_name' => 'PERMANENT_DELETE', 'old_value' => json_encode($oldEntry), 'new_value' => null]
                ];
                $userId = $decoded_jwt->userId ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', 'kdd_messages', $id, $userId, $changes);
            }

            http_response_code(200);
            echo json_encode(["success" => "Message permanently deleted"]);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Message not found"]);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteMessagePermanent (MessageID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
    }
}

function logMessage(string $message): void {
    $logFile = __DIR__ . '/../ws/logfile.log';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, FILE_APPEND);
}