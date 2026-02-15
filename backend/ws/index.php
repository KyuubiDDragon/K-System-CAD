<?php
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\Socket\SocketServer;
use React\Socket\SecureServer;

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../jwt.php';

function logMessage($message) {
    $logFile = __DIR__ . '/logfile.log';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, FILE_APPEND);
}


class WebSocketServer implements MessageComponentInterface {
    protected $clients;
    protected $userConnections;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $queryString = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryString, $queryParams);

        if (!isset($queryParams['token'])) {
            logMessage("Connection closed: No token provided", $conn);
            $conn->close();
            return;
        }

        $jwt = $queryParams['token'];
        try {
            logMessage("JWT", $jwt);
            $decoded = decode_jwt($jwt); // Stellen Sie sicher, dass diese Funktion korrekt ist und den Token dekodiert.
            $userId = $decoded->id;
            logMessage("decodedJWT", $decoded);

            $this->clients->attach($conn, $userId);
            if (!isset($this->userConnections[$userId])) {
                $this->userConnections[$userId] = [];
            }
            $this->userConnections[$userId][] = $conn;

            logMessage("User connected: User ID: {$userId}", $conn);
        } catch (Exception $e) {
            logMessage("Connection closed: Invalid token - " . $e->getMessage(), $conn);
            $conn->close();
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $jsonError = json_last_error_msg();
                throw new Exception("JSON decode error: {$jsonError}");
            }

            if (!isset($data['site']) || !isset($data['type'])) {
                throw new Exception("Invalid message format: missing 'site' or 'type'");
            }

            $site = $data['site'];
            $type = $data['type'];

            if ($site === 'message' && $type === 'update') {
                if (!isset($data['title']) || !isset($data['sender_id']) || !isset($data['sender_name']) || !isset($data['recipient_id'])) {
                    throw new Exception("Invalid message format for site 'message': missing 'message_id', 'title', 'sender_id', 'sender_name', or 'recipient_id'");
                }

                $title = $data['title'];
                $senderId = $data['sender_id'];
                $senderName = $data['sender_name'];
                $recipientId = $data['recipient_id'];

                if (isset($this->userConnections[$recipientId])) {
                    $message = [
                        'site' => $site,
                        'type' => $type,
                        'title' => $title,
                        'sender_name' => $senderName
                    ];
                    foreach ($this->userConnections[$recipientId] as $client) {
                        $client->send(json_encode($message));
                    }
                } else {
                    logMessage("No connections found for recipient {$recipientId}");
                }

                $response = json_encode(['status' => 'success', 'message' => 'Message sent to recipient']);
            } elseif ($site === 'dispatch') {
                $this->sendMessageToAllClients($data, $type);
                $response = json_encode(['status' => 'success', 'message' => 'Dispatch processed successfully']);
            } else {
                throw new Exception("Unknown site: {$site}");
            }
        } catch (Exception $e) {
            logMessage("Error processing message: " . $e->getMessage());
            $response = json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        } finally {
            $from->send($response);
        }
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $errorMessage = "An error has occurred: {$e->getMessage()}";
        $errorCode = $e->getCode();
        $errorFile = $e->getFile();
        $errorLine = $e->getLine();
        $errorTrace = $e->getTraceAsString();
    
        // Detaillierte Fehlermeldung erstellen
        $detailedErrorMessage = sprintf(
            "Error message: %s\nError code: %d\nFile: %s\nLine: %d\nTrace: %s",
            $errorMessage, $errorCode, $errorFile, $errorLine, $errorTrace
        );
    
        // Fehler protokollieren
        logMessage($detailedErrorMessage, $conn);
    
        // Fehler an den Client senden (optional)
        $conn->send(json_encode(['status' => 'error', 'message' => $errorMessage]));
    
        // Verbindung schließen
        $conn->close();
    }

    public function onClose(ConnectionInterface $conn) {
        logMessage("Connection {$conn->resourceId} has disconnected");

        $userId = $this->clients[$conn];
        $this->clients->detach($conn);
        if (isset($this->userConnections[$userId])) {
            $this->userConnections[$userId] = array_filter(
                $this->userConnections[$userId],
                function ($client) use ($conn) {
                    return $client !== $conn;
                }
            );
            if (empty($this->userConnections[$userId])) {
                unset($this->userConnections[$userId]);
            }
        }
    }


    private function isUserInGroup($userId, $groupId) {
        require "../db.php";

        $sql = "SELECT * FROM `kdd_reports` AS WHERE authority_id = ? group_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            logMessage("Prepare failed: " . $conn->error);
            return false;
        }
        $stmt->bind_param("ii", $groupId, $userId);
        if (!$stmt->execute()) {
            logMessage("Execute failed: " . $stmt->error);
            return false;
        }
        $result = $stmt->get_result();
        if (!$result) {
            logMessage("Get result failed: " . $stmt->error);
            return false;
        }

        $isInGroup = $result->num_rows > 0;

        $stmt->close();
        $conn->close();

        return $isInGroup;
    }

    private function sendMessageToUser($userId, $message, $type, $senderId) {
        if ($userId === $senderId) {
            return; // Skip sending message to the sender
        }

        $message['type'] = $type;  // Add the type to the message
        logMessage("Sending message to user {$userId}: " . json_encode($message));
        if (isset($this->userConnections[$userId])) {
            foreach ($this->userConnections[$userId] as $client) {
                logMessage("Sending to connection {$client->resourceId}");
                $client->send(json_encode($message));
            }
        } else {
            logMessage("No connections found for user {$userId}");
        }
    }

    private function sendMessageToGroup($groupId, $message, $type, $senderId) {
        $message['type'] = $type;  // Add the type to the message
        logMessage("Sending message to group {$groupId}: " . json_encode($message));
        foreach ($this->userConnections as $userId => $connections) {
            if ($userId === $senderId) {
                continue; // Skip sending message to the sender
            }

            if ($this->isUserInGroup($userId, $groupId)) {
                foreach ($connections as $client) {
                    logMessage("Sending to connection {$client->resourceId} in group {$groupId}");
                    $client->send(json_encode($message));
                }
            }
        }
    }

    private function sendMessageToAllClients($message, $type) {
        $message['type'] = $type;  // Add the type to the message
        logMessage("Sending message to all clients: " . json_encode($message));
        foreach ($this->clients as $client) {
            $client->send(json_encode($message));
        }
    }
}

$loop = \React\EventLoop\Loop::get();
$bindAddress = $_ENV['WS_BIND_ADDRESS'] ?? '0.0.0.0';
$bindPort = $_ENV['WS_BIND_PORT'] ?? '8080';
$socket = new SocketServer("{$bindAddress}:{$bindPort}", [], $loop);

$certFile = $_ENV['SSL_CERT_PATH'] ?? null;
$keyFile = $_ENV['SSL_KEY_PATH'] ?? null;

if ($certFile && $keyFile && file_exists($certFile) && file_exists($keyFile)) {
    $secureSocket = new SecureServer($socket, $loop, [
        'local_cert' => $certFile,
        'local_pk' => $keyFile,
        'allow_self_signed' => false,
        'verify_peer' => false
    ]);
    $listenSocket = $secureSocket;
} else {
    $listenSocket = $socket;
}

$server = new IoServer(
    new HttpServer(
        new WsServer(
            new WebSocketServer()
        )
    ),
    $listenSocket,
    $loop
);

$server->run();
