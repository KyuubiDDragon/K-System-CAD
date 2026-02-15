<?php
/**
 * Migration: Add spreadsheet support columns to kdd_doc_documents table
 *
 * This migration adds three new columns to support spreadsheet functionality:
 * - view_type: VARCHAR(20) - Defines whether document is 'document', 'spreadsheet', or 'both'
 * - spreadsheet_data: LONGTEXT - Stores Univer spreadsheet data as JSON
 * - default_view: VARCHAR(20) - Defines which view to show by default ('document' or 'spreadsheet')
 *
 * Usage: Run this via Docker:
 *   docker exec -it k-systems-backend-1 php /var/www/html/backend/migrations/add_spreadsheet_columns.php
 *
 * Or with direct database credentials:
 *   php add_spreadsheet_columns.php
 */

// Get database credentials from environment or use defaults
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'kdd';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Starting migration: Add spreadsheet columns to kdd_doc_documents...\n";

    // Check if columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM kdd_doc_documents LIKE 'view_type'");
    $viewTypeExists = $stmt->fetch();

    if ($viewTypeExists) {
        echo "Columns already exist. Skipping migration.\n";
        exit(0);
    }

    // Add the columns
    $sql = "
        ALTER TABLE `kdd_doc_documents`
        ADD COLUMN `view_type` VARCHAR(20) NOT NULL DEFAULT 'document' AFTER `authority_id`,
        ADD COLUMN `spreadsheet_data` LONGTEXT NULL AFTER `view_type`,
        ADD COLUMN `default_view` VARCHAR(20) NOT NULL DEFAULT 'document' AFTER `spreadsheet_data`
    ";

    $pdo->exec($sql);

    echo "✓ Successfully added columns: view_type, spreadsheet_data, default_view\n";
    echo "Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
