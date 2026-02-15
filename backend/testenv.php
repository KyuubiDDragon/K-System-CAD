<?php


// --- Core Initialisation ---
// Adjust relative paths for includes (assuming this file is in admin/application/)
require_once __DIR__ . '/vendor/autoload.php';
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__); // Load .env from backend root
    $dotenv->load();


    echo "Dotenv loaded.\n";

    echo "--- Environment AFTER Dotenv load ---\n";
    // Alternative/Ergänzung: var_dump($_ENV, $_SERVER);

    echo "TEST_VAR: " . $_ENV['TEST_VAR'] . "\n";
} catch (\Throwable $e) {
    error_log("Could not load .env file in admin/application/index.php: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Server configuration error."]); exit();
}

if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development') {
    error_reporting(E_ALL); ini_set('display_errors', '1');
} else {
    error_reporting(0); ini_set('display_errors', '0');
}
?>