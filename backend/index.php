<?php

// --- Konfiguration & Initialisierung ---

// CORS Header setzen (Anpassen nach Bedarf für Sicherheit)
header("Access-Control-Allow-Origin: *"); // Erlaube Anfragen von jeder Domain (oder spezifiziere deine Frontend-Domain)
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Preflight-Anfragen (OPTIONS) abfangen
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Zeitzone setzen (optional, aber empfohlen)
date_default_timezone_set('Europe/Berlin');

// Composer Autoloader (wenn verwendet, z.B. für JWT-Bibliothek und Dotenv)
// require_once __DIR__ . '/vendor/autoload.php';

// .env-Datei laden (Beispiel mit vlucas/phpdotenv, falls installiert)
// try {
//     $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
//     $dotenv->load();
//     $dotenv->required(['JWT_SECRET', 'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'])->notEmpty(); // Beispiel für erforderliche Variablen
// } catch (\Dotenv\Exception\InvalidPathException $e) {
//     error_log("Could not find .env file: " . $e->getMessage());
//     // Fallback oder Fehlermeldung
// }

// Session starten (falls für andere Zwecke benötigt)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Benötigte Hilfsdateien einbinden (Beispiele)
// require_once __DIR__ . '/config/database.php';
// require_once __DIR__ . '/includes/functions.php';
// require_once __DIR__ . '/auth.php'; // Eigene Datei für Auth-Funktionen erstellen?
// require_once __DIR__ . '/account/index.php'; // Bestehende Logik für Account-Aktionen?

// --- Request Handling ---

// Aktion aus dem Request holen (GET oder POST)
$action = $_REQUEST['action'] ?? null;

// Content Type auf JSON setzen
header('Content-Type: application/json');

// Basierend auf der Aktion entscheiden
switch ($action) {
    case 'login':
        // TODO: Implementiere Login-Logik hier oder rufe Funktion auf
        // Beispiel: handleLogin($_POST['username'], $_POST['password']);
        echo json_encode(['status' => 'error', 'message' => 'Login action not yet implemented']);
        break;

    case 'logout':
        // TODO: Implementiere Logout-Logik hier oder rufe Funktion auf
        // Beispiel: handleLogout();
        echo json_encode(['status' => 'error', 'message' => 'Logout action not yet implemented']);
        break;

    case 'verify-token':
        // TODO: Implementiere Token-Verifizierungslogik hier oder rufe Funktion auf
        // Beispiel: handleVerifyToken();
        echo json_encode(['status' => 'error', 'message' => 'Verify-token action not yet implemented']);
        break;

    // Weitere Aktionen hier hinzufügen...
    // case 'get_data':
    //     handleGetData();
    //     break;

    default:
        // Ungültige oder keine Aktion
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing action']);
        break;
}

exit(); // Skriptausführung beenden
?>


