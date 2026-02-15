<?php
/**
 * Simple script to run the logs table migration
 * Connects directly to the database without using .env
 */

// Database connection parameters - update these as needed
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: 3306;
$dbName = getenv('DB_DATABASE') ?: 'ksystems';
$dbUser = getenv('DB_USERNAME') ?: 'root';
$dbPass = getenv('DB_PASSWORD') ?: '';

// Data Source Name (DSN) for PDO
$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";

// PDO options
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // Create PDO connection
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    echo "Connected to database successfully.\n";

    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/db/migrations/create_log_entries_table.sql');
    
    // Execute the SQL
    $result = $pdo->exec($sql);
    echo "Migration executed successfully.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} 