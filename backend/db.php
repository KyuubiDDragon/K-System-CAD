<?php

// Hole Datenbank-Credentials aus Umgebungsvariablen
$dbHost = $_ENV['DB_HOST'] ?? '127.0.0.1';
$dbName = $_ENV['DB_DATABASE'] ?? null;
$dbUser = $_ENV['DB_USERNAME'] ?? null;
$dbPass = $_ENV['DB_PASSWORD'] ?? null;
$dbPort = $_ENV['DB_PORT'] ?? 3306; // Standard-MySQL-Port

// Prüfe, ob essentielle Variablen gesetzt sind
if (!$dbName || !$dbUser) { // Passwort kann leer sein
    error_log("Database configuration missing in .env file (DB_DATABASE, DB_USERNAME)");
    die("Database configuration error.");
}

// Data Source Name (DSN) für PDO
$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";

// PDO Optionen für bessere Fehlerbehandlung und Fetch-Modus
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Wirft Exceptions bei Fehlern
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Holt Ergebnisse als assoziatives Array
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Echte Prepared Statements verwenden
];

try {
    // Erstelle die PDO-Verbindung
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    // $pdo ist jetzt deine globale Datenbankverbindungsvariable

    // Optional: Log oder Meldung bei erfolgreicher Verbindung (nur für Debugging)
    // error_log("Database connection successful.");

} catch (\PDOException $e) {
    // Fange Verbindungsfehler ab
    error_log("Database Connection failed: " . $e->getMessage());
    // Gib eine generische Fehlermeldung aus, nicht die Details der Exception
    die("Database connection failed. Please check configuration.");
}

// Ab hier kannst du $pdo in anderen Skripten verwenden, die db.php includen.
// Beispiel: $stmt = $pdo->prepare("SELECT * FROM users");
?>