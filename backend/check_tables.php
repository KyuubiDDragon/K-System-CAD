<?php
require_once __DIR__ . '/bootstrap.php';

require_once __DIR__ . '/db.php';

try {
    // Check if the report fields table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'kdd_report_fields'");
    $reportFieldsTableExists = $stmt->rowCount() > 0;
    
    // Check if the report custom fields table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'kdd_report_custom_fields'");
    $reportCustomFieldsTableExists = $stmt->rowCount() > 0;
    
    echo "kdd_report_fields table exists: " . ($reportFieldsTableExists ? "Yes" : "No") . "\n";
    echo "kdd_report_custom_fields table exists: " . ($reportCustomFieldsTableExists ? "Yes" : "No") . "\n";
    
    // Show all tables
    echo "\nAll tables:\n";
    $result = $pdo->query("SHOW TABLES");
    while($row = $result->fetch()) {
        echo $row[0] . "\n";
    }
} catch (Exception $e) {
    die("Error checking tables: " . $e->getMessage() . "\n");
} 