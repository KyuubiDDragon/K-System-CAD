<?php
/**
 * Socket.io Client für PHP
 * 
 * Diese Klasse bietet eine einfache Integration für die Kommunikation
 * mit dem Socket.io Node.js Server aus PHP heraus.
 */

namespace Utils;

class SocketClient {
    private $socketUrl;
    private $options;
    private $lastError = null;

    /**
     * Konstruktor
     * 
     * @param string $socketUrl Socket.io Server URL (Standard: http://localhost:3000)
     * @param array $options HTTP Client Optionen
     */
    public function __construct($socketUrl = null, $options = []) {
        // Socket-URL aus Umgebungsvariablen oder Konfiguration laden
        $this->socketUrl = $socketUrl ?? getenv('SOCKET_SERVER_URL') ?? 'http://localhost:3001';
        
        // Standard-Optionen
        $this->options = array_merge([
            'timeout' => 2, // Zeitlimit in Sekunden
            'http_errors' => false,
            'verify' => false // SSL-Verifizierung deaktivieren (nur für Entwicklung)
        ], $options);
    }
    
    /**
     * Prüft, ob der Socket-Server erreichbar ist
     * 
     * @return bool
     */
    public function isServerReachable() {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->socketUrl . '/status');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->options['timeout']);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->options['timeout']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->options['verify']);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode == 200 && $response) {
                $data = json_decode($response, true);
                return isset($data['status']) && $data['status'] === 'online';
            }
            
            return false;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    /**
     * Sendet ein Event an den Socket.io Server
     * 
     * @param string $endpoint API Endpunkt (broadcast, notification, unread, calendar)
     * @param array $data Event Daten
     * @return array|bool Response Daten oder False bei Fehler
     */
    private function sendToSocketServer($endpoint, $data) {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->socketUrl . '/' . $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->options['timeout']);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->options['timeout']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->options['verify']);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                $this->lastError = "CURL Error: " . $error;
                return false;
            }
            
            if ($httpCode >= 400) {
                $this->lastError = "HTTP Error: " . $httpCode . " - " . $response;
                return false;
            }
            
            return json_decode($response, true);
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    /**
     * Sendet ein Broadcast-Event an alle verbundenen Clients oder an einen bestimmten Raum/Benutzer
     * 
     * @param string $event Event-Name
     * @param array $data Event-Daten
     * @param string|null $userId Benutzer-ID (optional)
     * @param string|null $room Raum-Name (optional)
     * @return bool Erfolg
     */
    public function broadcastEvent($event, $data, $userId = null, $room = null) {
        $payload = [
            'event' => $event,
            'data' => $data
        ];
        
        if ($userId) {
            $payload['userId'] = $userId;
        }
        
        if ($room) {
            $payload['room'] = $room;
        }
        
        $response = $this->sendToSocketServer('broadcast', $payload);
        return $response && isset($response['success']) && $response['success'] === true;
    }
    
    /**
     * Sendet eine Benachrichtigung an einen bestimmten Benutzer
     * 
     * @param int|string $userId Benutzer-ID
     * @param string $message Nachrichtentext
     * @param string $type Benachrichtigungstyp (info, success, warning, error)
     * @param string|null $title Titel (optional)
     * @param array $additionalData Zusätzliche Daten (optional)
     * @return bool Erfolg
     */
    public function sendNotification($userId, $message, $type = 'info', $title = null, $additionalData = []) {
        $payload = [
            'userId' => $userId,
            'message' => $message,
            'type' => $type,
        ];
        
        if ($title) {
            $payload['title'] = $title;
        }
        
        if (!empty($additionalData)) {
            $payload['data'] = $additionalData;
        }
        
        $response = $this->sendToSocketServer('notification', $payload);
        return $response && isset($response['success']) && $response['success'] === true;
    }
    
    /**
     * Aktualisiert den Zähler für ungelesene Nachrichten
     * 
     * @param int|string $userId Benutzer-ID
     * @param int $count Anzahl ungelesener Nachrichten
     * @return bool Erfolg
     */
    public function updateUnreadCount($userId, $count) {
        $payload = [
            'userId' => $userId,
            'count' => (int) $count
        ];
        
        $response = $this->sendToSocketServer('unread', $payload);
        return $response && isset($response['success']) && $response['success'] === true;
    }
    
    /**
     * Sendet Kalender-Updates an einen Benutzer
     * 
     * @param int|string $userId Benutzer-ID
     * @param array $events Kalender-Ereignisse
     * @return bool Erfolg
     */
    public function updateCalendar($userId, $events) {
        $payload = [
            'userId' => $userId,
            'events' => $events
        ];
        
        $response = $this->sendToSocketServer('calendar', $payload);
        return $response && isset($response['success']) && $response['success'] === true;
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