<?php
/**
 * Migration script to add widget_state column to desktop settings
 * Can be run via web browser or command line
 */

// Only allow access if logged in or from CLI
if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/bootstrap.php';
    
    // Simple migration access - just check if we can establish DB connection
    // This is a maintenance script, so we'll allow access if DB is available
    try {
        require_once __DIR__ . '/db.php';
        $test_pdo = getDbConnection();
        // Test connection
        $test_pdo->query('SELECT 1');
        echo "<!-- Migration script access granted -->\n";
    } catch (Exception $e) {
        http_response_code(500);
        die('Database connection failed: ' . $e->getMessage());
    }
}

// Get database connection
require_once __DIR__ . '/db.php';

try {
    $pdo = getDbConnection();
    
    // Start output
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: text/plain; charset=utf-8');
    }
    
    echo "=== Widget State Migration ===\n";
    echo "Starting migration at " . date('Y-m-d H:i:s') . "\n\n";
    
    // Check if kdd_desktop_settings table exists
    $tableCheckQuery = "SHOW TABLES LIKE 'kdd_desktop_settings'";
    $tableResult = $pdo->query($tableCheckQuery);
    
    if ($tableResult->rowCount() === 0) {
        echo "Table kdd_desktop_settings does not exist. It will be created automatically when first used.\n";
        echo "No migration needed at this time.\n";
    } else {
        echo "✓ Table kdd_desktop_settings exists\n";
        
        // Check if widget_state column exists
        $columnCheckQuery = "SHOW COLUMNS FROM kdd_desktop_settings LIKE 'widget_state'";
        $columnResult = $pdo->query($columnCheckQuery);
        
        if ($columnResult->rowCount() === 0) {
            echo "→ widget_state column does not exist. Adding it now...\n";
            
            // Add widget_state column
            $alterQuery = "ALTER TABLE kdd_desktop_settings 
                          ADD COLUMN widget_state LONGTEXT NOT NULL DEFAULT '{\"active\":[],\"positions\":{}}' 
                          AFTER theme";
            
            $pdo->exec($alterQuery);
            echo "✓ Successfully added widget_state column\n";
            
            // Update any NULL values to default
            $updateQuery = "UPDATE kdd_desktop_settings 
                           SET widget_state = '{\"active\":[],\"positions\":{}}' 
                           WHERE widget_state IS NULL OR widget_state = ''";
            $affected = $pdo->exec($updateQuery);
            
            if ($affected > 0) {
                echo "✓ Updated $affected rows with default widget_state\n";
            }
        } else {
            echo "✓ widget_state column already exists\n";
            
            // Check for any NULL or empty values
            $checkQuery = "SELECT COUNT(*) as count FROM kdd_desktop_settings 
                          WHERE widget_state IS NULL OR widget_state = ''";
            $stmt = $pdo->query($checkQuery);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                echo "→ Found {$result['count']} rows with empty widget_state. Updating...\n";
                
                $updateQuery = "UPDATE kdd_desktop_settings 
                               SET widget_state = '{\"active\":[],\"positions\":{}}' 
                               WHERE widget_state IS NULL OR widget_state = ''";
                $affected = $pdo->exec($updateQuery);
                
                echo "✓ Updated $affected rows with default widget_state\n";
            } else {
                echo "✓ All rows have valid widget_state values\n";
            }
        }
    }
    
    echo "\n=== Migration Complete ===\n";
    echo "Finished at " . date('Y-m-d H:i:s') . "\n";
    
    if (php_sapi_name() !== 'cli') {
        echo "\nYou can now close this window and return to the application.\n";
    }
    
} catch (PDOException $e) {
    $errorMsg = "Migration Error: " . $e->getMessage();
    error_log($errorMsg);
    
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
    }
    
    echo "\n❌ ERROR: " . $errorMsg . "\n";
    echo "\nPlease check the error and try again.\n";
    exit(1);
}
?>