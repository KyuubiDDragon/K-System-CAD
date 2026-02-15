<?php
/**
 * Beispiel für die Verwendung der SocketNotifier-Klasse
 */

require_once __DIR__ . '/../autoload.php';

use Utils\SocketNotifier;

// API-Schlüssel aus der Umgebung oder direkt angeben
$apiKey = getenv('SOCKET_API_KEY') ?: 'your_api_key_here';

try {
    // Socket.io-Notifier initialisieren
    $notifier = new SocketNotifier(null, $apiKey);
    
    // Beispiel 1: Toast-Benachrichtigung an einen Benutzer senden
    $userId = 123; // Benutzer-ID
    $result = $notifier->sendToast(
        $userId,
        'Dies ist eine Test-Benachrichtigung',
        'Test Toast',
        'info',
        'normal',
        ['testData' => true],
        '/messages'
    );
    
    if ($result) {
        echo "Toast erfolgreich gesendet: " . json_encode($result) . "\n";
    } else {
        echo "Fehler beim Senden des Toasts: " . $notifier->getLastError() . "\n";
    }
    
    // Beispiel 2: Benachrichtigung an einen Benutzer senden
    $result = $notifier->sendNotification(
        $userId,
        'Sie haben eine neue Nachricht erhalten',
        'Neue Nachricht',
        'info',
        ['sender' => 'System', 'messageId' => 456],
        '/inbox',
        (new DateTime('tomorrow'))->format('c')
    );
    
    if ($result) {
        echo "Benachrichtigung erfolgreich gesendet: " . json_encode($result) . "\n";
    } else {
        echo "Fehler beim Senden der Benachrichtigung: " . $notifier->getLastError() . "\n";
    }
    
    // Beispiel 3: Broadcast an alle verbundenen Benutzer senden
    $result = $notifier->sendBroadcast(
        'Systemwartung am 15.07.2023',
        'Wartungshinweis',
        'warning',
        ['startTime' => '22:00', 'endTime' => '23:00'],
        '/maintenance'
    );
    
    if ($result) {
        echo "Broadcast erfolgreich gesendet: " . json_encode($result) . "\n";
    } else {
        echo "Fehler beim Senden des Broadcasts: " . $notifier->getLastError() . "\n";
    }
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
} 