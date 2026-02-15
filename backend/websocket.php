<?php
/**
 * websocket.php
 * Ratchet WebSocket Server for real-time communication.
 * Authenticates users via JWT in query parameter on connection.
 * Uses PDO for specific database lookups (group membership).
 */
declare(strict_types=1);

// Change to the script's directory to ensure relative paths work
chdir(__DIR__);

// --- Core Initialisation ---
require_once __DIR__ . '/bootstrap.php';

// --- Configure Error Handling for long-running process ---
// Log errors rather than displaying them, especially in production
ini_set('display_errors', ($_ENV['APP_ENV'] ?? 'production') === 'development' ? '1' : '0');
ini_set('display_startup_errors', ($_ENV['APP_ENV'] ?? 'production') === 'development' ? '1' : '0');
error_reporting(E_ALL);
// Ensure log file path from .env is used, provide a default fallback
$logFilePath = $_ENV['WEBSOCKET_LOG_FILE'] ?? __DIR__.'/ws_error.log';
ini_set('log_errors', '1');
ini_set('error_log', $logFilePath); // Log PHP errors to a file

// --- Dependencies ---
// jwt.php is needed for decode_jwt
require_once __DIR__ . '/../jwt.php'; // Should already use .env
// db.php is needed for $pdo instance
try {
    require_once __DIR__ . '/../db.php'; // Defines $pdo using .env
} catch (Exception $e) {
     fwrite(STDERR, "Error: Database connection failed: " . $e->getMessage() . PHP_EOL);
     exit(1);
}

// Use Ratchet and ReactPHP components
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use React\Socket\SecureServer;
// Import JWT specific exceptions if needed for finer error handling in onOpen
use Firebase\JWT\ExpiredException;

/**
 * Logging helper function specific to the WebSocket server.
 */
function logMessage($message): void {
    // Use the same log file path as configured for PHP errors
    $logFile = $_ENV['WEBSOCKET_LOG_FILE'] ?? __DIR__.'/ws_error.log';
    $logEntry = date('Y-m-d H:i:s') . " - WS - " . $message . PHP_EOL; // Add prefix
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    // Optionally echo to console if running interactively in dev mode
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

       // echo $logEntry;
    }
}

/**
 * Main WebSocket application logic.
 */
class WebSocketServer implements MessageComponentInterface {
    protected \SplObjectStorage $clients;             // Stores connection -> userInfo mapping
    protected array $userConnections;                 // Stores userId -> SplObjectStorage of connections mapping
    protected PDO $pdo;                               // Store PDO connection

    public function __construct(PDO $pdo) {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
        $this->pdo = $pdo; // Inject PDO object
        logMessage("WebSocket Server handler initialized.");
    }

    public function onOpen(ConnectionInterface $conn): void {
        // Extract token from query string (ws://host?token=JWT_HERE)
        $queryString = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryString, $queryParams);
        $jwt = $queryParams['token'] ?? null;

        if (!$jwt) {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

            logMessage("Connection {$conn->resourceId} closed: No token provided in query string.");
            $conn->send(json_encode(['status' => 'error', 'message' => 'Authentication token missing.']));
            $conn->close();
            return;
        }

        try {
            // Validate and decode the JWT (uses function from jwt.php)
            // decode_jwt already handles signature, expiry, nbf, issuer, audience based on .env
            $decoded = decode_jwt($jwt); // Throws exception on failure

            // Ensure essential data is in the token payload
            $userId = $decoded->userId ?? null; // Use the key set in create_jwt (userId)
            $userAuthority = $decoded->authority ?? null;

            if ($userId === null || !is_int($userId) || $userAuthority === null || !is_string($userAuthority)) {
                 throw new \UnexpectedValueException("Invalid token payload: missing userId or authority.");
            }

            // Store connection and user info together
            $userInfo = ['userId' => $userId, 'authority' => $userAuthority];
            $this->clients->attach($conn, $userInfo); // Use attach for SplObjectStorage

            // Add connection to user-specific list
            if (!isset($this->userConnections[$userId])) {
                // Use SplObjectStorage for efficient object removal
                $this->userConnections[$userId] = new \SplObjectStorage();
            }
            $this->userConnections[$userId]->attach($conn);

            logMessage("Connection {$conn->resourceId} opened. User ID: {$userId}, Authority: {$userAuthority}");
            $conn->send(json_encode(['status'=>'success', 'message'=>'Connection established.']));

        } catch (ExpiredException $e) {
            logMessage("Connection {$conn->resourceId} closed: Expired token - " . $e->getMessage());
            $conn->send(json_encode(['status' => 'error', 'message' => 'Token expired. Please refresh.']));
            $conn->close();
        } catch (\Throwable $e) { // Catch other JWT errors or general exceptions
            logMessage("Connection {$conn->resourceId} closed: Invalid token - " . $e->getMessage());
             $conn->send(json_encode(['status' => 'error', 'message' => 'Invalid authentication token.']));
            $conn->close();
        }
    }

    public function onMessage(ConnectionInterface $from, $msg): void {
        $senderInfo = $this->clients[$from] ?? null;
        if (!$senderInfo || !isset($senderInfo['userId']) || !isset($senderInfo['authority'])) {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

             logMessage("Received message from unknown/unauthenticated connection {$from->resourceId}. Ignoring.");
             return;
        }
        $senderId = $senderInfo['userId'];
        $senderAuthority = $senderInfo['authority'];

        logMessage("Received message from User {$senderId} (Auth: {$senderAuthority}): {$msg}");

        try {
            $data = json_decode($msg, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException("Invalid JSON received: " . json_last_error_msg());
            }

            $site = $data['site'] ?? null;
            $type = $data['type'] ?? null;

            // Determine authority context FOR THE MESSAGE/GROUP ACTION
            // *** This logic needs review based on your application's needs ***
            // Example: Assume a context is passed, otherwise default to sender's authority
            $messageAuthorityContext = $data['authorityContext'] ?? $senderAuthority;

            $response = ['status' => 'received', 'message' => 'Message received.']; // Default ack

            // --- Message Handling Logic ---
            if ($site === 'message' && $type === 'update') {
                $recipientId = filter_var($data['recipient_id'] ?? null, FILTER_VALIDATE_INT);
                $groupId = filter_var($data['group_id'] ?? null, FILTER_VALIDATE_INT); // Check for group ID

                if (!$recipientId && !$groupId) { throw new \InvalidArgumentException("Missing 'recipient_id' or 'group_id' for message."); }

                // Prepare payload to forward (only essential info)
                $messagePayload = [
                     'site' => $site, 'type' => $type,
                     'title' => $data['title'] ?? 'New Message',
                     'sender_name' => $data['sender_name'] ?? 'Unknown Sender',
                     'message_id' => $data['message_id'] ?? null,
                     // Add other relevant fields like timestamp, preview etc.
                 ];

                if ($recipientId) {
                    $this->sendMessageToUser($recipientId, $messagePayload, $senderId);
                    $response = ['status' => 'success', 'message' => "Message forwarded to user {$recipientId}."];
                } elseif ($groupId) {
                     // Pass the determined authority context for the group check
                    $this->sendMessageToGroup($messageAuthorityContext, $groupId, $messagePayload, $senderId);
                    $response = ['status' => 'success', 'message' => "Message forwarded to group {$groupId}."];
                }

            } elseif ($site === 'dispatch') {
                // Example: Broadcast dispatch updates
                 $messagePayload = ['site' => $site, 'type' => $type, 'data' => $data['data'] ?? null];
                $this->sendMessageToAllClients($messagePayload);
                $response = ['status' => 'success', 'message' => 'Dispatch update broadcasted.'];
            }
            // Add other site/type handlers here...
            else {
                // Unknown site/type, just acknowledge receipt maybe
                 logMessage("Received message with unhandled site/type from User {$senderId}: {$site}/{$type}");
                 $response = ['status' => 'warning', 'message' => "Unhandled message type received: {$site}/{$type}"];
            }

            // Send acknowledgement back to sender
            $from->send(json_encode($response));

        } catch (\Throwable $e) { // Catch JSON errors or handler errors
            logMessage("Error processing message from User {$senderId}: " . $e->getMessage());
            $from->send(json_encode(['status' => 'error', 'message' => "Processing failed: " . $e->getMessage()]));
        }
    }

    public function onClose(ConnectionInterface $conn): void {
        $userInfo = $this->clients[$conn] ?? null; // Retrieve the stored info

        if ($userInfo && isset($userInfo['userId'])) {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

            $userId = $userInfo['userId'];
            logMessage("Connection {$conn->resourceId} (User {$userId}) disconnected.");

            // Remove from user-specific connection list
            if (isset($this->userConnections[$userId])) {
                $this->userConnections[$userId]->detach($conn);
                if (count($this->userConnections[$userId]) === 0) {
                    unset($this->userConnections[$userId]);
                    logMessage("User {$userId} now fully disconnected.");
                }
            }
        } else {
             logMessage("Connection {$conn->resourceId} (Unknown User) disconnected.");
        }
        // Always detach from the main client list
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void {
        $userInfo = $this->clients[$conn] ?? ['userId' => 'Unknown'];
        $userId = $userInfo['userId'] ?? 'Unknown';
        logMessage("Error for User {$userId} on connection {$conn->resourceId}: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        // Avoid sending detailed errors to client in production
        if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

             $conn->send(json_encode(['status'=>'error', 'message'=>"Error: {$e->getMessage()}"]));
        } else {
             $conn->send(json_encode(['status'=>'error', 'message'=>'An internal server error occurred.']));
        }
        $conn->close();
    }

    // --- Helper Methods ---

    /** Checks if a user is in a specific message group for a given authority context */
    private function isUserInGroup(PDO $pdo, string $authority, int $userId, int $groupId): bool {
         if (empty($authority)) {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

             logMessage("Error in isUserInGroup: Authority cannot be empty.");
             return false;
         }
        try {
            // Authority name is validated before being used in table name
            $sql = "SELECT 1 FROM `kdd_reports` AS WHERE authority_id = ? group_id = ? AND user_id = ? LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$authorityId, $groupId, $userId]);
            return $stmt->fetchColumn() !== false;
        } catch (\PDOException $e) {
            logMessage("DB Error checking group membership (User: {$userId}, Group: {$groupId}, Auth: {$authority}): " . $e->getMessage());
            return false;
        }
    }

    /** Sends a message to all connections associated with a specific User ID */
    private function sendMessageToUser(int $recipientUserId, array $message, int $senderId): void {
        if ($recipientUserId === $senderId && ($_ENV['WEBSOCKET_SEND_TO_SELF'] ?? 'false') !== 'true') {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

             logMessage("Skipping sending message to self (User {$senderId}).");
             return;
        }

        $encodedMessage = json_encode($message);
        if (isset($this->userConnections[$recipientUserId])) {
            logMessage("Sending message to User {$recipientUserId} ({$this->userConnections[$recipientUserId]->count()} connections)");
            foreach ($this->userConnections[$recipientUserId] as $client) {
                $client->send($encodedMessage);
            }
        } else {
            logMessage("No active connections found for recipient User {$recipientUserId}.");
        }
    }

     /** Sends a message to all users in a specific group (for a given authority) */
     private function sendMessageToGroup(string $authority, int $groupId, array $message, int $senderId): void {
         logMessage("Sending message to Group {$groupId} (Auth: {$authority})");
         $encodedMessage = json_encode($message);
         $sendToSelf = ($_ENV['WEBSOCKET_SEND_TO_SELF'] ?? 'false') === 'true';

         // Consider fetching group members first for efficiency on very large user counts
         // $members = fetchGroupMembers($this->pdo, $authority, $groupId);
         // foreach ($members as $userId) {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }
 ... check $this->userConnections[$userId] ... }

         foreach ($this->userConnections as $userId => $connections) {
             if (!$sendToSelf && $userId === $senderId) continue;

             // Check group membership using the correct authority context
             if ($this->isUserInGroup($this->pdo, $authority, $userId, $groupId)) {
                  logMessage("User {$userId} is in group {$groupId}. Sending to {$connections->count()} connections.");
                 foreach ($connections as $client) {
                     $client->send($encodedMessage);
                 }
             }
         }
     }

     /** Sends a message to ALL connected clients */
    private function sendMessageToAllClients(array $message): void {
        $encodedMessage = json_encode($message);
        logMessage("Broadcasting message to {$this->clients->count()} clients: {$encodedMessage}");
        foreach ($this->clients as $client) {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

            $client->send($encodedMessage);
        }
    }
} // End WebSocketServer Class


// --- Server Setup ---
$wsIP = $_ENV['WEBSOCKET_IP'] ?? '0.0.0.0';
$wsPort = $_ENV['WEBSOCKET_PORT'] ?? 8080;
$certPath = $_ENV['WEBSOCKET_SSL_CERT'] ?? null;
$keyPath = $_ENV['WEBSOCKET_SSL_KEY'] ?? null;

if (!$certPath || !$keyPath) { fwrite(STDERR, "Error: WEBSOCKET_SSL_CERT or WEBSOCKET_SSL_KEY not set in .env" . PHP_EOL); exit(1); }
if (!file_exists($certPath)) { fwrite(STDERR, "Error: SSL Certificate file not found at: " . $certPath . PHP_EOL); exit(1); }
if (!file_exists($keyPath)) { fwrite(STDERR, "Error: SSL Key file not found at: " . $keyPath . PHP_EOL); exit(1); }

$loop = Loop::get();

// Basic socket server
$socket = new SocketServer("{$wsIP}:{$wsPort}", [], $loop);

// Secure the socket server
$secureSocket = new SecureServer($socket, $loop, [
    'local_cert' => $certPath,
    'local_pk'   => $keyPath,
    'allow_self_signed' => false,
    'verify_peer' => false
]);

// Create and run the WebSocket server, injecting $pdo
$server = new IoServer(
    new HttpServer(
        new WsServer(
            new WebSocketServer($pdo) // Pass PDO connection
        )
    ),
    $secureSocket,
    $loop
);

logMessage("WebSocket Server starting on wss://{$wsIP}:{$wsPort}");
echo "WebSocket server starting on wss://{$wsIP}:{$wsPort}...\n"; // Output to console

$server->run(); // Start the event loop