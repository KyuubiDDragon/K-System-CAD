<?php
/**
 * Socket.io Debug Tool
 * Diese Datei dient zum Testen der Verbindung zwischen PHP und Socket.io Server
 * 
 * Aufruf: socket-debug.php?user_id=123&action=test
 */

header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Autoloader laden
require_once __DIR__ . '/bootstrap.php';

// Socket Notifier einbinden
require_once __DIR__ . '/utils/SocketNotifier.php';
use Utils\SocketNotifier;

// Environment setup - optional
$dotenvLoaded = false;
$envError = '';
try {
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
        $dotenvLoaded = true;
    } else {
        $envError = "Die Klasse Dotenv\Dotenv wurde nicht gefunden. Umgebungsvariablen müssen manuell gesetzt werden.";
    }
} catch (\Throwable $e) {
    $envError = $e->getMessage();
}

// Parameter von der URL holen
$user_id = $_GET['user_id'] ?? null;
$action = $_GET['action'] ?? 'info';
$title = $_GET['title'] ?? 'Debug Test';
$message = $_GET['message'] ?? 'Dies ist eine Test-Nachricht vom PHP-Backend';
$type = $_GET['type'] ?? 'info';
$priority = $_GET['priority'] ?? 'high';

// HTML Header ausgeben
echo "<!DOCTYPE html>
<html>
<head>
    <title>Socket.io Debug Tool</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1, h2 { color: #333; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .card { border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        label { display: inline-block; width: 100px; }
    </style>
</head>
<body>
    <h1>Socket.io Debug Tool</h1>
";

// Konfiguration anzeigen
echo "<div class='card'>
    <h2>Konfiguration</h2>";

if (!$dotenvLoaded && !empty($envError)) {
    echo "<p class='warning'>⚠️ .env-Datei konnte nicht geladen werden: " . htmlspecialchars($envError) . "</p>";
    echo "<p>Du kannst die Socket-URL und den API-Key direkt in den URL-Parametern übergeben:</p>";
    echo "<p><code>?socket_url=http://localhost:3001&api_key=dein_api_key&user_id=123</code></p>";
}

// Funktionen hinzufügen für direkte HTTP-Anfragen
function makeDirectHttpRequest($url, $method, $apiKey, $payload) {
    // Zuerst mit CURL versuchen
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        
        // JSON codieren
        $jsonPayload = json_encode($payload);
        
        // Headers mit API-Key
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-API-Key: ' . $apiKey
        ];
        
        // CURL-Optionen
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        }
        
        // Request ausführen
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => $error,
                'code' => $httpCode
            ];
        }
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'code' => $httpCode,
            'response' => json_decode($response, true),
            'raw' => $response
        ];
    }
    
    // Alternative mit file_get_contents
    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-API-Key: ' . $apiKey
            ],
            'content' => json_encode($payload),
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return [
            'success' => false,
            'error' => error_get_last()['message'] ?? 'Unbekannter Fehler',
            'code' => 0
        ];
    }
    
    // HTTP Status aus Header extrahieren
    $httpCode = 200;
    if (isset($http_response_header[0])) {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
        $httpCode = $matches[1] ?? 200;
    }
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'code' => $httpCode,
        'response' => json_decode($response, true),
        'raw' => $response
    ];
}

// URL-Parameter für Socket-URL und API-Key prüfen
$socket_url_param = $_GET['socket_url'] ?? null;
$api_key_param = $_GET['api_key'] ?? null;

// Socket-URL aus Parametern oder Umgebung oder Standardwert
$socketUrl = $socket_url_param ?? $_ENV['SOCKET_SERVER_URL'] ?? 'http://localhost:3001';

// API-Schlüssel aus Parametern oder Umgebung
$apiKey = $api_key_param ?? $_ENV['SOCKET_API_KEY'] ?? '';

// Stellen Sie sicher, dass der API-Key keine Anführungszeichen enthält
$apiKey = trim($apiKey, '"\'');

// Neue Debug-Sektion
if ($action === 'debug_api_key') {
    echo "<div class='card'>";
    echo "<h2>API-Key Debug</h2>";
    
    // Zeige den API-Key exakt an (mit Länge und Hexadezimal-Darstellung)
    echo "<p><strong>API-Key (exakt):</strong> <code>" . htmlspecialchars($apiKey) . "</code></p>";
    echo "<p><strong>Länge:</strong> " . strlen($apiKey) . " Zeichen</p>";
    echo "<p><strong>Hex-Darstellung:</strong></p><pre>";
    for ($i = 0; $i < strlen($apiKey); $i++) {
        echo sprintf("%02x", ord($apiKey[$i])) . " ";
        if (($i + 1) % 16 === 0) echo "\n";
    }
    echo "</pre>";
    
    // Vergleiche mit der Referenz
    $referenceKey = "0j8BD6CY2k7nYtWgQ9PhKO8HxbQJFiD5UXieXmvRkLk=";
    echo "<p><strong>Referenz-Key:</strong> <code>" . htmlspecialchars($referenceKey) . "</code></p>";
    echo "<p><strong>Länge:</strong> " . strlen($referenceKey) . " Zeichen</p>";
    echo "<p><strong>Hex-Darstellung:</strong></p><pre>";
    for ($i = 0; $i < strlen($referenceKey); $i++) {
        echo sprintf("%02x", ord($referenceKey[$i])) . " ";
        if (($i + 1) % 16 === 0) echo "\n";
    }
    echo "</pre>";
    
    // Unterschiede finden
    if ($apiKey === $referenceKey) {
        echo "<p class='success'>✅ Die API-Keys sind identisch</p>";
    } else {
        echo "<p class='error'>❌ Die API-Keys sind unterschiedlich</p>";
        echo "<p><strong>Unterschiede:</strong></p><pre>";
        $maxLen = max(strlen($apiKey), strlen($referenceKey));
        for ($i = 0; $i < $maxLen; $i++) {
            $charRef = $i < strlen($referenceKey) ? $referenceKey[$i] : '';
            $charKey = $i < strlen($apiKey) ? $apiKey[$i] : '';
            $match = $charRef === $charKey ? '=' : '≠';
            printf("Position %2d: %s %s %s (hex: %s %s %s)\n", 
                $i, 
                htmlspecialchars($charRef), 
                $match, 
                htmlspecialchars($charKey),
                $charRef !== '' ? sprintf("%02x", ord($charRef)) : '--',
                $match,
                $charKey !== '' ? sprintf("%02x", ord($charKey)) : '--'
            );
        }
        echo "</pre>";
    }
    
    // Direkten HTTP-Request testen
    echo "<h3>API-Key-Validierung testen</h3>";
    $testUrl = $socketUrl . '/api/toast';
    
    // Test mit der Referenz
    $resultRef = makeDirectHttpRequest(
        $testUrl,
        'POST',
        $referenceKey, 
        ['userId' => $user_id, 'message' => 'Test mit Referenz-Key', 'title' => 'API-Key Test (Referenz)']
    );
    echo "<p><strong>Test mit Referenz-Key:</strong> ";
    if ($resultRef['success']) {
        echo "<span class='success'>✅ Erfolgreich</span></p>";
    } else {
        echo "<span class='error'>❌ Fehlgeschlagen (Code: {$resultRef['code']})</span></p>";
        echo "<pre>" . htmlspecialchars($resultRef['raw'] ?? 'Keine Antwort') . "</pre>";
    }
    
    // Test mit dem aktuellen Key
    $resultCurrent = makeDirectHttpRequest(
        $testUrl,
        'POST',
        $apiKey, 
        ['userId' => $user_id, 'message' => 'Test mit aktuellem Key', 'title' => 'API-Key Test (Aktuell)']
    );
    echo "<p><strong>Test mit aktuellem Key:</strong> ";
    if ($resultCurrent['success']) {
        echo "<span class='success'>✅ Erfolgreich</span></p>";
    } else {
        echo "<span class='error'>❌ Fehlgeschlagen (Code: {$resultCurrent['code']})</span></p>";
        echo "<pre>" . htmlspecialchars($resultCurrent['raw'] ?? 'Keine Antwort') . "</pre>";
    }
    
    // Vergleiche mit curl_getinfo oder Header-Inspektion
    if (function_exists('curl_init')) {
        echo "<h3>API-Key in HTTP-Header prüfen</h3>";
        $ch = curl_init($testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-API-Key: ' . $apiKey
        ]);
        $response = curl_exec($ch);
        
        // Request mit Header anzeigen
        echo "<p><strong>HTTP-Request mit Header:</strong></p>";
        echo "<pre>POST " . parse_url($testUrl, PHP_URL_PATH) . " HTTP/1.1\n";
        echo "Host: " . parse_url($testUrl, PHP_URL_HOST) . "\n";
        echo "Content-Type: application/json\n";
        echo "Accept: application/json\n";
        echo "X-API-Key: " . htmlspecialchars($apiKey) . "\n</pre>";
        
        curl_close($ch);
    }
    
    echo "</div>";
    
    // Hinzufüge eine SocketNotifier-Instanz als Test
    echo "<div class='card'>";
    echo "<h2>SocketNotifier Test</h2>";
    
    try {
        $notifier = new SocketNotifier($socketUrl, $apiKey);
        echo "<p>SocketNotifier-Instanz wurde erstellt.</p>";
        
        // Teste die sendToast-Methode
        $toastResult = $notifier->sendToast(
            $user_id,
            "Debug-Test der SocketNotifier-Klasse",
            "SocketNotifier Test",
            "info",
            "high",
            ["debug" => true, "test_id" => uniqid()]
        );
        
        if ($toastResult === false) {
            echo "<p class='error'>❌ SocketNotifier.sendToast fehlgeschlagen: " . htmlspecialchars($notifier->getLastError()) . "</p>";
        } else {
            echo "<p class='success'>✅ SocketNotifier.sendToast erfolgreich!</p>";
            echo "<pre>" . htmlspecialchars(json_encode($toastResult, JSON_PRETTY_PRINT)) . "</pre>";
        }
    } catch (\Exception $e) {
        echo "<p class='error'>❌ SocketNotifier-Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "</div>";
}

echo "<p><strong>Socket.io Server URL:</strong> " . htmlspecialchars($socketUrl) . ($socket_url_param ? ' (aus URL-Parameter)' : '') . "</p>";
echo "<p><strong>Socket.io API Key:</strong> " . (strlen($apiKey) > 0 ? substr($apiKey, 0, 3) . '...' . substr($apiKey, -3) : 'nicht gesetzt') . ($api_key_param ? ' (aus URL-Parameter)' : '') . "</p>";
echo "</div>";

// Wenn keine User ID angegeben ist, Form anzeigen
if (!$user_id) {
    echo "<div class='card'>
        <h2>Test ausführen</h2>
        <p>Bitte gib unten die Parameter für den Test ein:</p>
        
        <form method='get'>
            <p>
                <label for='socket_url'>Socket URL:</label>
                <input type='text' name='socket_url' id='socket_url' value='" . htmlspecialchars($socketUrl) . "'>
            </p>
            <p>
                <label for='api_key'>API Key:</label>
                <input type='text' name='api_key' id='api_key' value='" . htmlspecialchars($apiKey) . "'>
            </p>
            <p>
                <label for='user_id'>User ID:</label>
                <input type='number' name='user_id' id='user_id' required>
            </p>
            <p>
                <label for='action'>Aktion:</label>
                <select name='action' id='action'>
                    <option value='info'>Info ausgeben</option>
                    <option value='test'>Test-Toast senden</option>
                    <option value='notification'>Test-Benachrichtigung senden</option>
                    <option value='both'>Beides senden</option>
                    <option value='direct_api'>Direkter API-Test</option>
                    <option value='debug_api_key'>API-Key Debug</option>
                </select>
            </p>
            <p>
                <label for='title'>Titel:</label>
                <input type='text' name='title' id='title' value='Debug Test'>
            </p>
            <p>
                <label for='message'>Nachricht:</label>
                <input type='text' name='message' id='message' value='Dies ist eine Test-Nachricht vom PHP-Backend'>
            </p>
            <p>
                <label for='type'>Typ:</label>
                <select name='type' id='type'>
                    <option value='info'>Info</option>
                    <option value='success'>Success</option>
                    <option value='warning'>Warning</option>
                    <option value='error'>Error</option>
                </select>
            </p>
            <p>
                <label for='priority'>Priorität:</label>
                <select name='priority' id='priority'>
                    <option value='high'>High</option>
                    <option value='normal'>Normal</option>
                    <option value='low'>Low</option>
                </select>
            </p>
            <p>
                <input type='submit' value='Test ausführen'>
            </p>
        </form>
    </div>";
    
    echo "<div class='card'>
        <h2>Hilfe</h2>
        <p>Dieses Tool hilft dir, die Verbindung zwischen dem PHP-Backend und dem Socket.io-Server zu testen.</p>
        <p>Gib eine Benutzer-ID ein und wähle eine Aktion aus, um einen Test durchzuführen.</p>
    </div>";
    
    exit();
}

try {
    // SocketNotifier initialisieren
    $notifier = new SocketNotifier($socketUrl, $apiKey);
    
    echo "<div class='card'>";
    echo "<h2>Test-Konfiguration</h2>";
    echo "<p><strong>Benutzer-ID:</strong> " . htmlspecialchars($user_id) . "</p>";
    echo "<p><strong>Aktion:</strong> " . htmlspecialchars($action) . "</p>";
    echo "<p><strong>Titel:</strong> " . htmlspecialchars($title) . "</p>";
    echo "<p><strong>Nachricht:</strong> " . htmlspecialchars($message) . "</p>";
    echo "<p><strong>Typ:</strong> " . htmlspecialchars($type) . "</p>";
    echo "<p><strong>Priorität:</strong> " . htmlspecialchars($priority) . "</p>";
    echo "</div>";
    
    // Je nach Aktion verschiedene Tests durchführen
    switch ($action) {
        case 'test':
            echo "<div class='card'>";
            echo "<h2>Toast-Test</h2>";
            
            // Toast-Benachrichtigung senden
            $result = $notifier->sendToast(
                $user_id,
                $message,
                $title,
                $type,
                $priority,
                [
                    'debug' => true,
                    'test_id' => uniqid('test_'),
                    'timestamp' => date('c')
                ]
            );
            
            if ($result === false) {
                echo "<p class='error'>❌ Toast konnte nicht gesendet werden: " . htmlspecialchars($notifier->getLastError()) . "</p>";
            } else {
                echo "<p class='success'>✅ Toast erfolgreich gesendet!</p>";
                echo "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . "</pre>";
            }
            
            echo "</div>";
            break;
            
        case 'notification':
            echo "<div class='card'>";
            echo "<h2>Benachrichtigungs-Test</h2>";
            
            // Persistente Benachrichtigung senden
            $result = $notifier->sendNotification(
                $user_id,
                $message,
                $title,
                $type,
                [
                    'debug' => true,
                    'test_id' => uniqid('test_'),
                    'timestamp' => date('c')
                ]
            );
            
            if ($result === false) {
                echo "<p class='error'>❌ Benachrichtigung konnte nicht gesendet werden: " . htmlspecialchars($notifier->getLastError()) . "</p>";
            } else {
                echo "<p class='success'>✅ Benachrichtigung erfolgreich gesendet!</p>";
                echo "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . "</pre>";
            }
            
            echo "</div>";
            break;
            
        case 'both':
            echo "<div class='card'>";
            echo "<h2>Toast und Benachrichtigungs-Test</h2>";
            
            // Toast-Benachrichtigung senden
            $toastResult = $notifier->sendToast(
                $user_id,
                $message,
                $title,
                $type,
                $priority,
                [
                    'debug' => true,
                    'test_id' => uniqid('test_'),
                    'timestamp' => date('c')
                ]
            );
            
            // Persistente Benachrichtigung senden
            $notificationResult = $notifier->sendNotification(
                $user_id,
                $message,
                $title,
                $type,
                [
                    'debug' => true,
                    'test_id' => uniqid('test_'),
                    'timestamp' => date('c')
                ]
            );
            
            if ($toastResult === false) {
                echo "<p class='error'>❌ Toast konnte nicht gesendet werden: " . htmlspecialchars($notifier->getLastError()) . "</p>";
            } else {
                echo "<p class='success'>✅ Toast erfolgreich gesendet!</p>";
                echo "<pre>" . htmlspecialchars(json_encode($toastResult, JSON_PRETTY_PRINT)) . "</pre>";
            }
            
            if ($notificationResult === false) {
                echo "<p class='error'>❌ Benachrichtigung konnte nicht gesendet werden: " . htmlspecialchars($notifier->getLastError()) . "</p>";
            } else {
                echo "<p class='success'>✅ Benachrichtigung erfolgreich gesendet!</p>";
                echo "<pre>" . htmlspecialchars(json_encode($notificationResult, JSON_PRETTY_PRINT)) . "</pre>";
            }
            
            echo "</div>";
            break;
            
        case 'direct_api':
            echo "<div class='card'>";
            echo "<h2>Direkter API-Test</h2>";
            
            // Zuerst einen API-Test mit direktem HTTP-Request machen
            $testUrl = $socketUrl . '/api/toast';
            echo "<p>Direkter HTTP-Request an: <strong>" . htmlspecialchars($testUrl) . "</strong></p>";
            
            $payload = [
                'userId' => $user_id,
                'title' => $title . ' (direkter API-Test)',
                'message' => $message,
                'type' => $type,
                'priority' => $priority,
                'sender_name' => 'Debug Tool'
            ];
            
            echo "<p><strong>API Key:</strong> " . (strlen($apiKey) > 0 ? substr($apiKey, 0, 3) . '...' . substr($apiKey, -3) : 'nicht gesetzt') . "</p>";
            echo "<p><strong>Request Payload:</strong></p>";
            echo "<pre>" . htmlspecialchars(json_encode($payload, JSON_PRETTY_PRINT)) . "</pre>";
            
            $result = makeDirectHttpRequest($testUrl, 'POST', $apiKey, $payload);
            
            if ($result['success']) {
                echo "<p class='success'>✅ Direkter API-Request war erfolgreich (HTTP Code: " . $result['code'] . ")</p>";
                echo "<p><strong>Antwort:</strong></p>";
                echo "<pre>" . htmlspecialchars(json_encode($result['response'], JSON_PRETTY_PRINT)) . "</pre>";
            } else {
                echo "<p class='error'>❌ Direkter API-Request fehlgeschlagen: " . htmlspecialchars($result['error'] ?? 'Unbekannter Fehler') . "</p>";
                echo "<p><strong>HTTP Code:</strong> " . $result['code'] . "</p>";
                echo "<p><strong>Antwort:</strong></p>";
                echo "<pre>" . htmlspecialchars($result['raw'] ?? 'Keine Antwort') . "</pre>";
            }
            
            echo "</div>";
            break;
            
        case 'info':
        default:
            echo "<div class='card'>";
            echo "<h2>Verbindungs-Info</h2>";
            
            echo "<p><strong>Socket.io Server URL:</strong> " . htmlspecialchars($socketUrl) . "</p>";
            echo "<p><strong>API Key:</strong> " . (strlen($apiKey) > 0 ? substr($apiKey, 0, 3) . '...' . substr($apiKey, -3) : 'nicht gesetzt') . "</p>";
            
            // Versuche eine einfache Anfrage zu senden
            $healthCheckUrl = $socketUrl . '/health';
            echo "<p>Prüfe Verbindung zur Socket.io Server health URL: <strong>" . htmlspecialchars($healthCheckUrl) . "</strong></p>";
            
            try {
                if (function_exists('curl_init')) {
                    $ch = curl_init($healthCheckUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($httpCode >= 200 && $httpCode < 300) {
                        echo "<p class='success'>✅ Verbindung erfolgreich (HTTP Code: $httpCode)</p>";
                        $data = json_decode($response, true);
                        echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
                    } else {
                        echo "<p class='error'>❌ Verbindung fehlgeschlagen (HTTP Code: $httpCode)</p>";
                    }
                } else {
                    // Alternative mit file_get_contents versuchen
                    echo "<p class='warning'>⚠️ CURL ist nicht verfügbar, versuche es mit file_get_contents...</p>";
                    $context = stream_context_create([
                        'http' => [
                            'timeout' => 5,
                        ]
                    ]);
                    $response = @file_get_contents($healthCheckUrl, false, $context);
                    
                    if ($response !== false) {
                        echo "<p class='success'>✅ Verbindung erfolgreich</p>";
                        $data = json_decode($response, true);
                        echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
                    } else {
                        echo "<p class='error'>❌ Verbindung fehlgeschlagen</p>";
                    }
                }
            } catch (\Exception $e) {
                echo "<p class='error'>❌ Verbindungsfehler: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            
            echo "</div>";
    }
    
} catch (\Exception $e) {
    echo "<div class='card'>";
    echo "<h2 class='error'>❌ Fehler</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

// Formen erzeugen für direktes Testen der API-Endpunkte
echo "<div class='card'>";
echo "<h2>API-Endpunkte direkt testen</h2>";
echo "<p>Hier kannst du die API-Endpunkte des Socket.io-Servers direkt testen:</p>";

// Toast-Endpunkt
echo "<div style='margin-bottom: 20px;'>";
echo "<h3>Toast-Endpunkt</h3>";
echo "<pre>" . htmlspecialchars($socketUrl . '/api/toast') . "</pre>";
echo "<form id='toastForm' target='_blank' method='post' action='" . htmlspecialchars($socketUrl . '/api/toast') . "'>";
echo "<p><label>API Key: <input type='text' name='apiKey' value='" . htmlspecialchars($apiKey) . "'></label> <small class='warning'>(Ohne Anführungszeichen!)</small></p>";
echo "<p><label>User ID: <input type='text' name='userId' value='" . htmlspecialchars($user_id) . "'></label></p>";
echo "<p><label>Title: <input type='text' name='title' value='API Test'></label></p>";
echo "<p><label>Message: <input type='text' name='message' value='Direkter API-Test'></label></p>";
echo "<p><label>Type: <select name='type'><option>info</option><option>success</option><option>warning</option><option>error</option></select></label></p>";
echo "<p><label>Priority: <select name='priority'><option>high</option><option>normal</option><option>low</option></select></label></p>";
echo "<p><button type='submit'>Toast senden</button></p>";
echo "</form>";
echo "<p><button onclick='sendDirectApiRequest(\"toast\")'>Mit JavaScript senden</button></p>";
echo "</div>";

// Notification-Endpunkt
echo "<div>";
echo "<h3>Notification-Endpunkt</h3>";
echo "<pre>" . htmlspecialchars($socketUrl . '/api/notification') . "</pre>";
echo "<form id='notificationForm' target='_blank' method='post' action='" . htmlspecialchars($socketUrl . '/api/notification') . "'>";
echo "<p><label>API Key: <input type='text' name='apiKey' value='" . htmlspecialchars($apiKey) . "'></label> <small class='warning'>(Ohne Anführungszeichen!)</small></p>";
echo "<p><label>User ID: <input type='text' name='userId' value='" . htmlspecialchars($user_id) . "'></label></p>";
echo "<p><label>Title: <input type='text' name='title' value='API Test'></label></p>";
echo "<p><label>Message: <input type='text' name='message' value='Direkter API-Test'></label></p>";
echo "<p><label>Type: <select name='type'><option>info</option><option>success</option><option>warning</option><option>error</option></select></label></p>";
echo "<p><button type='submit'>Notification senden</button></p>";
echo "</form>";
echo "<p><button onclick='sendDirectApiRequest(\"notification\")'>Mit JavaScript senden</button></p>";
echo "</div>";

// JavaScript zur korrekten Bearbeitung des API-Keys und zur Verwendung von fetch hinzufügen
echo "<script>
function sendDirectApiRequest(type) {
    const formId = type === 'toast' ? 'toastForm' : 'notificationForm';
    const form = document.getElementById(formId);
    const url = form.action;
    
    // Formular-Daten lesen
    const formData = new FormData(form);
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // API-Key extrahieren und sicherstellen, dass keine Anführungszeichen vorhanden sind
    const apiKey = formData.get('apiKey').trim().replace(/[\"\']/g, '');
    
    // Fetch API verwenden
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-API-Key': apiKey
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        return response.json().then(data => {
            return {
                status: response.status,
                statusText: response.statusText,
                data
            };
        });
    })
    .then(result => {
        alert('Status: ' + result.status + '\\n\\nAntwort: ' + JSON.stringify(result.data, null, 2));
    })
    .catch(error => {
        alert('Fehler: ' + error.message);
    });
}
</script>";

echo "</div>";

// Link zurück zum Formular
echo "<p><a href='socket-debug.php'>Zurück zum Formular</a></p>";

// Debug-Output für die SocketNotifier-Klasse
echo "<div class='card'>";
echo "<h2>PHP Umgebung</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>CURL installiert:</strong> " . (function_exists('curl_init') ? 'Ja' : 'Nein') . "</p>";
echo "<p><strong>JSON Unterstützung:</strong> " . (function_exists('json_encode') ? 'Ja' : 'Nein') . "</p>";
echo "<p><strong>Stream-Context Unterstützung:</strong> " . (function_exists('stream_context_create') ? 'Ja' : 'Nein') . "</p>";
echo "</div>";

// Composer-Status prüfen
echo "<div class='card'>";
echo "<h2>Composer Status</h2>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<p class='success'>✅ vendor/autoload.php gefunden</p>";
    
    // Prüfen, ob dotenv installiert ist
    if (class_exists('Dotenv\Dotenv')) {
        echo "<p class='success'>✅ Dotenv\Dotenv Klasse gefunden</p>";
    } else {
        echo "<p class='error'>❌ Dotenv\Dotenv Klasse nicht gefunden. Du kannst sie mit dem folgenden Befehl installieren:</p>";
        echo "<pre>composer require vlucas/phpdotenv</pre>";
    }
} else {
    echo "<p class='error'>❌ vendor/autoload.php nicht gefunden. Führe bitte zuerst Composer aus:</p>";
    echo "<pre>composer install</pre>";
}
echo "</div>";

// HTML Footer
echo "</body></html>"; 