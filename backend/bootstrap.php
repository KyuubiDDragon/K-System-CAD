<?php
/**
 * Zentrale Bootstrap-Datei für die Backend-API.
 * Stellt Autoloader, Konfiguration aus Umgebungsvariablen (.env in Dev, Docker in Prod),
 * Fehlerbehandlung, CORS-Header und Datenbankverbindung bereit.
 * Wird von allen API-Endpunkt-Dateien inkludiert.
 */
declare(strict_types=1);

// --- 1. Composer Autoloader laden ---
// Der Pfad muss korrekt relativ zur bootstrap.php Datei sein.
// Wenn bootstrap.php im ./backend/ Verzeichnis liegt, dann ist vendor/autoload.php direkt darin.
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    // Dies sollte in einer Docker-Umgebung, in der Composer läuft, nicht passieren.
    // Wenn doch, ist etwas beim Build/Deploy schief gelaufen.
    error_log('Autoload file not found. Make sure composer dependencies are installed.');
    http_response_code(500);
    echo json_encode(["error" => "Server configuration error: Composer dependencies not found."]);
    exit();
}
require_once $autoloadPath;

// --- 2. Umgebung bestimmen (früh im Prozess!) ---
// Debug: Log the paths we're trying
error_log("Current directory (__DIR__): " . __DIR__);
error_log("Parent directory: " . dirname(__DIR__));

// Helper function to get environment variables from either source
function getEnvVar(string $key, $default = null) {
    // Try $_ENV first (for .env loaded variables)
    if (isset($_ENV[$key])) {
        return $_ENV[$key];
    }
    // Fallback to getenv() (for Docker/System environment)
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

// Try loading .env file first, regardless of APP_ENV
$dotenvPath = dirname(__DIR__); // Try parent directory first
$dotenvFile = $dotenvPath . '/.env';

$envLoaded = false;
if (file_exists($dotenvFile)) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable($dotenvPath);
        $dotenv->load();
        error_log("Successfully loaded .env file from parent directory");
        $envLoaded = true;
    } catch (\Throwable $e) {
        error_log("Error loading .env from parent directory: " . $e->getMessage());
    }
}

// If parent directory failed, try current directory
if (!$envLoaded) {
    $dotenvPath = __DIR__;
    $dotenvFile = $dotenvPath . '/.env';
    
    if (file_exists($dotenvFile)) {
        try {
            $dotenv = Dotenv\Dotenv::createImmutable($dotenvPath);
            $dotenv->load();
            error_log("Successfully loaded .env file from current directory");
            $envLoaded = true;
        } catch (\Throwable $e) {
            error_log("Error loading .env from current directory: " . $e->getMessage());
        }
    }
}

// Now check APP_ENV after attempting to load .env
$appEnv = getEnvVar('APP_ENV', 'production');


// Datenbank-Variablen
$dbHost = getEnvVar('DB_HOST', 'db'); // Default: 'db' (Docker Service Name)
$dbPort = getEnvVar('DB_PORT', '3306');
$dbName = getEnvVar('DB_DATABASE', 'ksystems');
$dbUser = getEnvVar('DB_USERNAME', 'ksystems');
$dbPass = getEnvVar('DB_PASSWORD', 'ksystems_password');

// Frontend URLs für CORS
$frontendUrlDev = getEnvVar('FRONTEND_URL_DEV');
$frontendUrlProd = getEnvVar('FRONTEND_URL_PROD');

// Weitere globale Konfigurationen
$jwtSecret = getEnvVar('JWT_SECRET_KEY', 'default_jwt_secret');
$socketApiKey = getEnvVar('SOCKET_API_KEY', 'default_socket_api_key');
// ... lies hier alle weiteren benötigten Umgebungsvariablen aus ...


// Optional: Gelesene Konfiguration zentral speichern (empfohlen!)
// Endpunkt-Dateien können dann über $GLOBALS['config']['db']['host'] etc. darauf zugreifen
$GLOBALS['config'] = [
    'app_env' => $appEnv, // Speichere die bestimmte Umgebung auch in der Config
    'db' => [
        'host' => $dbHost,
        'port' => $dbPort,
        'name' => $dbName,
        'user' => $dbUser,
        'pass' => $dbPass,
    ],
    'cors' => [
        'allowed_origins' => array_filter([$frontendUrlDev, $frontendUrlProd]), // Filtert NULLs raus
    ],
    'jwt_secret_key' => $jwtSecret,
    'socket_api_key' => $socketApiKey,
    // ... füge hier alle gelesenen Variablen hinzu ...
];


// --- 4. Fehler-Reporting basierend auf APP_ENV einstellen ---
// Nutzt die $appEnv Variable aus der Konfiguration
if ($appEnv === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1'); // Hilfreich beim Debugging
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    // Fehler in Log-Datei schreiben konfigurieren (Passe den Pfad an)
    ini_set('log_errors', 1);
    // Log-Pfad aus Config oder fix:
    $logPath = $_ENV['LOG_PATH'] ?? (__DIR__ . '/../logs/php_errors.log'); // Beispiel mit ENV-Variable oder fixem Pfad
    // Stelle sicher, dass das Verzeichnis existiert und beschreibbar ist (wichtig in Docker)
    $logDir = dirname($logPath);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true); // Erstelle Verzeichnis rekursiv, wenn nötig
    }
    if (is_writable($logDir)) {
        ini_set('error_log', $logPath);
    } else {
        error_log("Warning: PHP error log directory not writable: " . $logDir);
        // Fehler gehen dann standardmäßig an SAPI-Log (Apache Log in Docker)
    }
}

// --- 5. CORS Header setzen & OPTIONS Preflight Handler ---
// Nutzt die $GLOBALS['config']
$allowed_origins = $GLOBALS['config']['cors']['allowed_origins'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Special handling for FiveM/CEF browsers
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isFiveM = strpos($userAgent, 'FiveM') !== false || strpos($userAgent, 'CEF') !== false;

if ($isFiveM) {
    // FiveM/CEF browsers may not send proper origin headers
    // Use the origin if provided, otherwise use a fallback
    if (!empty($origin)) {
        header("Access-Control-Allow-Origin: " . $origin);
        error_log("FiveM/CEF browser detected with origin: " . $origin);
    } else {
        // If no origin, we can't use credentials, but we still allow the request
        header("Access-Control-Allow-Origin: " . ($_ENV['FRONTEND_URL_PROD'] ?? 'http://localhost:5173'));
        error_log("FiveM/CEF browser detected without origin, using default");
    }
    header("Vary: Origin");
} elseif (!empty($origin) && in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $origin);
    header("Vary: Origin"); // Wichtig für Caching
} else {
    if (!empty($origin) && $appEnv === 'development') { // Logge dies nur in Dev oder auf Wunsch
         error_log("CORS Error: Origin " . $origin . " not in allowed list: " . implode(', ', $allowed_origins));
    }
}

// Allgemeine CORS-Header (Methoden und Header an deine API anpassen)
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS"); // Erlaube Methoden, die deine API nutzt
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With, Authorization"); // Füge Authorization hinzu, falls du JWTs nutzt

// Content Security Policy headers for FiveM compatibility
if ($isFiveM) {
    // Relaxed CSP for FiveM to allow external resources
    header("Content-Security-Policy: default-src 'self' https:; img-src 'self' data: blob: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self' 'unsafe-inline' 'unsafe-eval'; connect-src 'self' https:;");
    error_log("CSP headers set for FiveM browser");
}

// OPTIONS Preflight Handler
// Wenn die Anfrage nur ein OPTIONS-Request ist, beenden wir das Skript hier nach dem Setzen der Header.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(); // WICHTIG: Beende die Ausführung nach einem OPTIONS-Preflight
}


// --- 6. Datenbankverbindung herstellen ---
// Nutzt die gelesenen DB-Variablen aus $GLOBALS['config']
$dbConfig = $GLOBALS['config']['db'];
$dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset=utf8mb4";

try {
    // Definiere $pdo als global, damit es in den Endpunkt-Dateien verfügbar ist (durch 'global $pdo;' dort)
    global $pdo;
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Fehler als Exceptions werfen
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Optional: Standard Fetch Mode
} catch (\PDOException $e) {
    // Fehler beim Verbinden loggen und API mit 500er Fehler beenden
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed."]);
    exit(); // WICHTIG: Skript beenden, wenn keine DB-Verbindung möglich ist
}

// --- 7. Gemeinsame Helferfunktionen/Klassen laden ---
// Lade hier Dateien mit Funktionen, die JEDER Endpunkt benötigt, z.B. Logging, allgemeine Auth-Helfer
// Pfade korrekt relativ zur bootstrap.php anpassen!
require_once __DIR__ . '/logging/logging.php'; // Beispielpfad, Annahme: Datei liegt in ./backend/logging/logging.php
require_once __DIR__ . '/utils/authority_helper.php'; // Beispielpfad, Annahme: Datei liegt in ./backend/utils/authority_helper.php
// require_once __DIR__ . '/jwt.php'; // Beispielpfad, falls JWT-Helfer global benötigt werden


// --- Ende der Bootstrap-Initialisierung ---
// Ab hier ist Autoloader geladen, Konfiguration verfügbar ($GLOBALS['config']), DB verbunden ($pdo),
// CORS/Fehler gesetzt, gemeinsame Helfer geladen.
// Die Endpunkt-Datei, die diese Bootstrap-Datei inkludiert, kann nun mit ihrer spezifischen Logik beginnen
// und sollte 'global $pdo;' sowie 'global $config;' deklarieren, um auf die Ressourcen zuzugreifen.

// --- Gemeinsame Hilfsfunktionen für alle Endpunkte ---

/**
 * Parse JSON request data
 * @return ?array The parsed JSON data or null if invalid
 */
function getJsonRequestData(): ?array {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400); 
        echo json_encode(['error' => 'Invalid JSON data provided: ' . json_last_error_msg()]); 
        exit();
    }
    
    return $data;
}

/**
 * Send method not allowed response
 */
function MethodNotAllowed(): void {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed for this action.']);
}
?>