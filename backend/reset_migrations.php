<?php
/**
 * Reset Migration Status - K-Systems
 * 
 * This script allows you to reset the migration status for specific migrations
 * so they can be re-run. Use with caution!
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

// Set output headers for web display
header('Content-Type: text/plain; charset=utf-8');

echo "=== K-Systems Migration Reset Tool ===\n";
echo "⚠️  WARNING: This tool allows you to reset migration status!\n";
echo "Use with caution as it may cause migrations to run again.\n\n";

// Check if specific migration is requested
$resetMigration = isset($_GET['migration']) ? $_GET['migration'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

try {
    // Check if migrations table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'schema_migrations'");
    if ($tableCheck->rowCount() == 0) {
        echo "❌ Migrations table does not exist yet.\n";
        echo "Run the migration tool first to create it.\n";
        exit;
    }
    
    if ($action === 'list') {
        // List all applied migrations
        echo "📋 Applied Migrations:\n";
        echo "--------------------\n";
        
        $stmt = $pdo->query("SELECT filename, applied_at FROM schema_migrations ORDER BY applied_at");
        $migrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($migrations)) {
            echo "No migrations have been applied yet.\n";
        } else {
            foreach ($migrations as $migration) {
                echo "- {$migration['filename']} (applied at {$migration['applied_at']})\n";
            }
            
            echo "\n📌 To reset a specific migration, add ?action=reset&migration=FILENAME to the URL\n";
            echo "Example: ?action=reset&migration=0001_add_widget_state_column.sql\n";
        }
        
    } elseif ($action === 'reset' && $resetMigration) {
        echo "🔄 Attempting to reset migration: {$resetMigration}\n";
        
        // Check if migration exists in database
        $stmt = $pdo->prepare("SELECT * FROM schema_migrations WHERE filename = :filename");
        $stmt->execute(['filename' => $resetMigration]);
        
        if ($stmt->rowCount() == 0) {
            echo "❌ Migration '{$resetMigration}' not found in applied migrations.\n";
        } else {
            // Remove from migrations table
            $deleteStmt = $pdo->prepare("DELETE FROM schema_migrations WHERE filename = :filename");
            $deleteStmt->execute(['filename' => $resetMigration]);
            
            echo "✅ Migration '{$resetMigration}' has been reset.\n";
            echo "You can now run the migration tool again to re-apply it.\n";
        }
        
    } elseif ($action === 'reset-all') {
        echo "⚠️  RESETTING ALL MIGRATIONS!\n";
        
        $pdo->exec("TRUNCATE TABLE schema_migrations");
        
        echo "✅ All migration records have been cleared.\n";
        echo "You can now run the migration tool to re-apply all migrations.\n";
        
    } else {
        echo "❌ Invalid action or missing parameters.\n";
        echo "Valid actions:\n";
        echo "- ?action=list (default)\n";
        echo "- ?action=reset&migration=FILENAME\n";
        echo "- ?action=reset-all (use with extreme caution!)\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    http_response_code(500);
}

echo "\n=== End of Migration Reset Tool ===\n";
?>