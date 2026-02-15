<?php
/**
 * Desktop Settings Database Fix Script
 * Fixes the multi-tenant database schema issues
 * Can be run via web browser
 */

// Only allow access if we can establish DB connection
try {
    require_once __DIR__ . '/db.php';
    // Test connection (db.php creates $pdo variable)
    $pdo->query('SELECT 1');
} catch (Exception $e) {
    http_response_code(500);
    die('Database connection failed: ' . $e->getMessage());
}

// $pdo is already available from db.php

// Start output
header('Content-Type: text/plain; charset=utf-8');

echo "=== Desktop Settings Database Fix ===\n";
echo "Starting fix at " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Check if kdd_desktop_settings table exists
    $tableCheckQuery = "SHOW TABLES LIKE 'kdd_desktop_settings'";
    $tableResult = $pdo->query($tableCheckQuery);
    
    if ($tableResult->rowCount() === 0) {
        echo "❌ Table kdd_desktop_settings does not exist. Cannot apply fix.\n";
        echo "Please ensure the main database schema is imported first.\n";
        exit(1);
    }
    
    echo "✓ Table kdd_desktop_settings exists\n";
    
    // Step 1: Check and add widget_state column
    echo "\n--- Step 1: Widget State Column ---\n";
    $columnCheckQuery = "SHOW COLUMNS FROM kdd_desktop_settings LIKE 'widget_state'";
    $columnResult = $pdo->query($columnCheckQuery);
    
    if ($columnResult->rowCount() === 0) {
        echo "→ Adding widget_state column...\n";
        $alterQuery = "ALTER TABLE kdd_desktop_settings 
                      ADD COLUMN widget_state LONGTEXT NOT NULL DEFAULT '{\"active\":[],\"positions\":{}}' 
                      AFTER theme";
        $pdo->exec($alterQuery);
        echo "✓ Successfully added widget_state column\n";
    } else {
        echo "✓ widget_state column already exists\n";
    }
    
    // Step 2: Check current indexes
    echo "\n--- Step 2: Index Analysis ---\n";
    $indexQuery = "SHOW INDEX FROM kdd_desktop_settings WHERE Key_name = 'user_id'";
    $indexResult = $pdo->query($indexQuery);
    
    $hasOldIndex = $indexResult->rowCount() > 0;
    
    if ($hasOldIndex) {
        echo "⚠️  Found problematic user_id unique constraint\n";
        
        // Check if we have the new compound index
        $compoundIndexQuery = "SHOW INDEX FROM kdd_desktop_settings WHERE Key_name = 'authority_user'";
        $compoundResult = $pdo->query($compoundIndexQuery);
        $hasCompoundIndex = $compoundResult->rowCount() > 0;
        
        echo "→ Fixing unique constraint for multi-tenant support...\n";
        
        // Step 2a: Remove old unique constraint
        echo "  → Removing old user_id unique constraint...\n";
        try {
            $pdo->exec("ALTER TABLE kdd_desktop_settings DROP INDEX user_id");
            echo "  ✓ Removed old user_id constraint\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                echo "  ℹ️  user_id index doesn't exist (already removed)\n";
            } else {
                throw $e;
            }
        }
        
        // Step 2b: Add compound unique constraint
        if (!$hasCompoundIndex) {
            echo "  → Adding authority_user compound constraint...\n";
            $pdo->exec("ALTER TABLE kdd_desktop_settings ADD UNIQUE KEY authority_user (authority_id, user_id)");
            echo "  ✓ Added authority_user compound constraint\n";
        } else {
            echo "  ✓ authority_user constraint already exists\n";
        }
        
    } else {
        echo "✓ No problematic user_id constraint found\n";
        
        // Check if compound index exists
        $compoundIndexQuery = "SHOW INDEX FROM kdd_desktop_settings WHERE Key_name = 'authority_user'";
        $compoundResult = $pdo->query($compoundIndexQuery);
        
        if ($compoundResult->rowCount() === 0) {
            echo "→ Adding authority_user compound constraint...\n";
            $pdo->exec("ALTER TABLE kdd_desktop_settings ADD UNIQUE KEY authority_user (authority_id, user_id)");
            echo "✓ Added authority_user compound constraint\n";
        } else {
            echo "✓ authority_user constraint already exists\n";
        }
    }
    
    // Step 3: Verify final schema
    echo "\n--- Step 3: Schema Verification ---\n";
    
    // Check columns
    $columnsQuery = "SHOW COLUMNS FROM kdd_desktop_settings";
    $columns = $pdo->query($columnsQuery)->fetchAll();
    $columnNames = array_column($columns, 'Field');
    
    $requiredColumns = ['id', 'user_id', 'authority_id', 'widget_state', 'theme', 'background', 'icon_positions', 'window_layouts'];
    $missingColumns = array_diff($requiredColumns, $columnNames);
    
    if (empty($missingColumns)) {
        echo "✓ All required columns present\n";
    } else {
        echo "⚠️  Missing columns: " . implode(', ', $missingColumns) . "\n";
    }
    
    // Check indexes
    $indexesQuery = "SHOW INDEX FROM kdd_desktop_settings";
    $indexes = $pdo->query($indexesQuery)->fetchAll();
    
    $hasCompoundIndex = false;
    $hasOldUserIndex = false;
    
    foreach ($indexes as $index) {
        if ($index['Key_name'] === 'authority_user') {
            $hasCompoundIndex = true;
        }
        if ($index['Key_name'] === 'user_id' && $index['Non_unique'] == 0) {
            $hasOldUserIndex = true;
        }
    }
    
    if ($hasCompoundIndex && !$hasOldUserIndex) {
        echo "✓ Correct multi-tenant indexing in place\n";
    } else {
        echo "⚠️  Index configuration may still have issues\n";
        if ($hasOldUserIndex) {
            echo "  - Old user_id unique constraint still exists\n";
        }
        if (!$hasCompoundIndex) {
            echo "  - Missing authority_user compound constraint\n";
        }
    }
    
    // Step 4: Test insert capability
    echo "\n--- Step 4: Testing Multi-Tenant Capability ---\n";
    
    // Try to create test records for same user_id but different authority_id
    $testUserId = 99999;
    $testAuthority1 = 1;
    $testAuthority2 = 2;
    
    // Clean up any existing test data
    $pdo->prepare("DELETE FROM kdd_desktop_settings WHERE user_id = ? AND authority_id IN (?, ?)")
        ->execute([$testUserId, $testAuthority1, $testAuthority2]);
    
    try {
        // Insert first record
        $stmt1 = $pdo->prepare("INSERT INTO kdd_desktop_settings (user_id, authority_id, theme, widget_state, icon_positions, window_layouts) VALUES (?, ?, 'dark', '{}', '{}', '{}')");
        $stmt1->execute([$testUserId, $testAuthority1]);
        
        // Insert second record with same user_id but different authority_id
        $stmt2 = $pdo->prepare("INSERT INTO kdd_desktop_settings (user_id, authority_id, theme, widget_state, icon_positions, window_layouts) VALUES (?, ?, 'dark', '{}', '{}', '{}')");
        $stmt2->execute([$testUserId, $testAuthority2]);
        
        echo "✓ Multi-tenant test successful - same user_id can exist across authorities\n";
        
        // Clean up test data
        $pdo->prepare("DELETE FROM kdd_desktop_settings WHERE user_id = ? AND authority_id IN (?, ?)")
            ->execute([$testUserId, $testAuthority1, $testAuthority2]);
        
    } catch (PDOException $e) {
        echo "❌ Multi-tenant test failed: " . $e->getMessage() . "\n";
        echo "   This indicates the unique constraint fix did not work properly.\n";
    }
    
    echo "\n=== Fix Complete ===\n";
    echo "Finished at " . date('Y-m-d H:i:s') . "\n\n";
    
    echo "Summary:\n";
    echo "- Widget state column: Present\n";
    echo "- Multi-tenant support: " . ($hasCompoundIndex && !$hasOldUserIndex ? "✓ Enabled" : "⚠️ Needs attention") . "\n";
    echo "- Database schema: Ready for widget state saving\n\n";
    
    echo "You can now close this window and return to the application.\n";
    echo "Widget state saving should work properly now.\n";
    
} catch (PDOException $e) {
    $errorMsg = "Database Fix Error: " . $e->getMessage();
    error_log($errorMsg);
    
    http_response_code(500);
    echo "\n❌ ERROR: " . $errorMsg . "\n";
    echo "\nPlease check the error and try again.\n";
    echo "You may need to run the SQL commands manually.\n";
    exit(1);
}
?>