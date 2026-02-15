<?php
/**
 * Socket.io Notifier Klasse
 * 
 * Dient zum Senden von Benachrichtigungen und Toasts an den Socket.io-Server
 */

namespace Utils;

class SocketNotifier {
    private $socketUrl;
    private $apiKey;
    private $lastError = null;
    
    /**
     * Konstruktor
     * 
     * @param string $socketUrl Socket.io Server URL (Standard: http://localhost:3001)
     * @param string $apiKey API-Schlüssel für die Authentifizierung
     */
    public function __construct($socketUrl = null, $apiKey = null) {
        $this->socketUrl = $socketUrl ?? getenv('SOCKET_SERVER_URL') ?? 'http://localhost:3001';
        $this->apiKey = $apiKey ?? getenv('SOCKET_API_KEY');
        
        if (!$this->apiKey) {
            throw new \Exception('Socket.io API-Schlüssel ist erforderlich');
        }
    }
    
    /**
     * Sendet einen Toast an einen bestimmten Benutzer
     * 
     * @param int|string $userId Benutzer-ID
     * @param string $message Nachrichtentext
     * @param string $title Titel (optional)
     * @param string $type Benachrichtigungstyp (info, success, warning, error)
     * @param string $priority Priorität (low, normal, high)
     * @param array $data Zusätzliche Daten (optional)
     * @param string $link Link URL oder Route (optional)
     * @return array|false Antwort oder False bei Fehler
     */
    public function sendToast($userId, $message, $title = null, $type = 'info', $priority = 'normal', $data = [], $link = null) {
        // Debug output
        error_log("🔔 SocketNotifier::sendToast - Sending toast to user $userId: $message");
        
        $payload = [
            'userId' => $userId,
            'message' => $message,
            'type' => $type,
            'priority' => $priority
        ];
        
        if ($title) {
            $payload['title'] = $title;
        }
        
        // Make sure sender_name is included at the root level 
        // since the socket server looks for it there
        if (!empty($data)) {
            $payload['data'] = $data;
            
            // Extract sender_name from data if present and add it directly to payload root
            if (isset($data['sender_name'])) {
                $payload['sender_name'] = $data['sender_name'];
            }
            if (isset($data['sender_id'])) {
                $payload['sender_id'] = $data['sender_id'];
            }
        }
        
        if ($link) {
            $payload['link'] = $link;
        }
        
        // Debug output
        error_log("📦 SocketNotifier::sendToast - Payload: " . json_encode($payload));
        
        $result = $this->sendRequest('/api/toast', $payload);
        
        if ($result === false) {
            error_log("❌ SocketNotifier::sendToast - Failed to send toast: " . $this->getLastError());
        } else {
            error_log("✅ SocketNotifier::sendToast - Toast sent successfully: " . json_encode($result));
        }
        
        return $result;
    }
    
    /**
     * Sendet eine Benachrichtigung an einen bestimmten Benutzer
     * 
     * @param int|string $userId Benutzer-ID
     * @param string $message Nachrichtentext
     * @param string $title Titel (optional)
     * @param string $type Benachrichtigungstyp (info, success, warning, error)
     * @param array $data Zusätzliche Daten (optional)
     * @param string $link Link URL oder Route (optional)
     * @param string $expiresAt Ablaufdatum (ISO 8601)
     * @return array|false Antwort oder False bei Fehler
     */
    public function sendNotification($userId, $message, $title = null, $type = 'info', $data = [], $link = null, $expiresAt = null) {
        $payload = [
            'userId' => $userId,
            'message' => $message,
            'type' => $type
        ];
        
        if ($title) {
            $payload['title'] = $title;
        }
        
        // Make sure sender_name is included at the root level for regular notifications too
        if (!empty($data)) {
            $payload['data'] = $data;
            
            // Extract sender_name from data if present and add it directly to payload root
            if (isset($data['sender_name'])) {
                $payload['sender_name'] = $data['sender_name'];
            }
            if (isset($data['sender_id'])) {
                $payload['sender_id'] = $data['sender_id'];
            }
        }
        
        if ($link) {
            $payload['link'] = $link;
        }
        
        if ($expiresAt) {
            $payload['expiresAt'] = $expiresAt;
        }
        
        return $this->sendRequest('/api/notification', $payload);
    }
    
    /**
     * Sendet eine Broadcast-Benachrichtigung an alle verbundenen Benutzer
     * 
     * @param string $message Nachrichtentext
     * @param string $title Titel (optional)
     * @param string $type Benachrichtigungstyp (info, success, warning, error)
     * @param array $data Zusätzliche Daten (optional)
     * @param string $link Link URL oder Route (optional)
     * @param string $expiresAt Ablaufdatum (ISO 8601)
     * @return array|false Antwort oder False bei Fehler
     */
    public function sendBroadcast($message, $title = null, $type = 'info', $data = [], $link = null, $expiresAt = null) {
        $payload = [
            'message' => $message,
            'type' => $type
        ];
        
        if ($title) {
            $payload['title'] = $title;
        }
        
        if (!empty($data)) {
            $payload['data'] = $data;
        }
        
        if ($link) {
            $payload['link'] = $link;
        }
        
        if ($expiresAt) {
            $payload['expiresAt'] = $expiresAt;
        }
        
        return $this->sendRequest('/api/broadcast', $payload);
    }
    
    /**
     * Sendet eine Anfrage an den Socket.io-Server
     * 
     * @param string $endpoint API-Endpunkt
     * @param array $data Anfragedaten
     * @return array|false Antwort oder False bei Fehler
     */
    private function sendRequest($endpoint, $data) {
        try {
            $url = $this->socketUrl . $endpoint;
            $jsonData = json_encode($data);
            
            error_log("🔗 SocketNotifier::sendRequest - Sending request to: $url");
            error_log("📤 SocketNotifier::sendRequest - Request data: $jsonData");
            
            // Stream-Kontext für POST-Anfrage erstellen
            $options = [
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/json',
                        'Accept: application/json',
                        'X-API-Key: ' . $this->apiKey,
                        'Content-Length: ' . strlen($jsonData)
                    ],
                    'content' => $jsonData,
                    'timeout' => 5,
                    'ignore_errors' => true // Allow getting response body even on HTTP error codes
                ]
            ];
            
            error_log("🔑 SocketNotifier::sendRequest - Using API Key: " . substr($this->apiKey, 0, 10) . "...");
            
            $context = stream_context_create($options);
            
            // Anfrage senden
            error_log("⏳ SocketNotifier::sendRequest - Sending request...");
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                $errorMessage = error_get_last()['message'] ?? 'Unbekannter Fehler';
                $this->lastError = "Fehler bei der Anfrage: " . $errorMessage;
                error_log("❌ SocketNotifier::sendRequest - Request failed: " . $errorMessage);
                return false;
            }
            
            // HTTP-Statuscode prüfen
            $statusCode = 200;
            if (isset($http_response_header[0])) {
                preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
                $statusCode = $matches[1] ?? 200;
                error_log("🔢 SocketNotifier::sendRequest - Response status code: $statusCode");
            }
            
            // Log the response
            error_log("📥 SocketNotifier::sendRequest - Response: " . substr($response, 0, 200) . (strlen($response) > 200 ? '...' : ''));
            
            if ($statusCode >= 400) {
                $this->lastError = "HTTP Error: " . $statusCode . " - " . $response;
                error_log("❌ SocketNotifier::sendRequest - HTTP Error: $statusCode - $response");
                return false;
            }
            
            $decodedResponse = json_decode($response, true);
            error_log("✅ SocketNotifier::sendRequest - Request successful");
            
            return $decodedResponse;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("❌ SocketNotifier::sendRequest - Exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gibt den letzten Fehler zurück
     * 
     * @return string|null
     */
    public function getLastError() {
        return $this->lastError;
    }
} 