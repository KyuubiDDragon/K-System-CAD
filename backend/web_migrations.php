<?php
/**
 * Web-accessible Database Migration Runner for K-Systems
 * 
 * This script manages database migrations via web browser:
 * - Creates migration tracking table if not exists
 * - Scans migrations directory for .sql files
 * - Executes only new migrations in order
 * - Records applied migrations
 * - Provides detailed web output
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

class WebMigrationRunner {
    private $db;
    private $migrationsDir;
    private $migrationsTable = 'schema_migrations';
    
    public function __construct($db) {
        $this->db = $db;
        $this->migrationsDir = __DIR__ . '/migrations';
    }
    
    /**
     * Run all pending migrations
     */
    public function run() {
        echo "=== K-Systems Database Migration Runner ===\n";
        echo "Starting migration process at " . date('Y-m-d H:i:s') . "\n\n";
        
        try {
            // Ensure migrations table exists
            $this->createMigrationsTable();
            
            // Get list of migration files
            $migrations = $this->getMigrationFiles();
            
            if (empty($migrations)) {
                echo "No migration files found in: {$this->migrationsDir}\n";
                return true;
            }
            
            // Get already applied migrations
            $appliedMigrations = $this->getAppliedMigrations();
            
            // Show status
            echo "Migration Status:\n";
            echo "- Total migration files: " . count($migrations) . "\n";
            echo "- Already applied: " . count($appliedMigrations) . "\n";
            echo "- Pending: " . (count($migrations) - count($appliedMigrations)) . "\n\n";
            
            // Run pending migrations
            $pendingCount = 0;
            foreach ($migrations as $migration) {
                if (!in_array($migration, $appliedMigrations)) {
                    $this->runMigration($migration);
                    $pendingCount++;
                } else {
                    echo "⏭️  Skipping {$migration} (already applied)\n";
                }
            }
            
            echo "\n";
            if ($pendingCount === 0) {
                echo "✅ All migrations are already applied - database is up to date!\n";
            } else {
                echo "✅ Successfully applied {$pendingCount} migration(s)!\n";
            }
            
            return true;
            
        } catch (Exception $e) {
            echo "❌ Migration failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Create migrations tracking table if it doesn't exist
     */
    private function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL UNIQUE,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_filename (filename)
        )";
        
        $this->db->exec($sql);
        echo "📋 Migrations tracking table ready\n";
    }
    
    /**
     * Get list of migration files sorted by name
     */
    private function getMigrationFiles() {
        $files = [];
        
        if (!is_dir($this->migrationsDir)) {
            throw new Exception("Migrations directory not found: {$this->migrationsDir}");
        }
        
        $dir = opendir($this->migrationsDir);
        while (($file = readdir($dir)) !== false) {
            if (preg_match('/^\d{4}_.*\.sql$/', $file)) {
                $files[] = $file;
            }
        }
        closedir($dir);
        
        // Sort files by name (which includes timestamp)
        sort($files);
        
        echo "📁 Found " . count($files) . " migration file(s)\n";
        if (!empty($files)) {
            foreach ($files as $file) {
                echo "   - {$file}\n";
            }
        }
        echo "\n";
        
        return $files;
    }
    
    /**
     * Get list of already applied migrations
     */
    private function getAppliedMigrations() {
        try {
            $stmt = $this->db->query("SELECT filename FROM {$this->migrationsTable} ORDER BY applied_at");
            $applied = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $applied[] = $row['filename'];
            }
            
            return $applied;
        } catch (PDOException $e) {
            // Table might not exist yet
            return [];
        }
    }
    
    /**
     * Run a single migration file
     */
    private function runMigration($filename) {
        $filepath = $this->migrationsDir . '/' . $filename;
        
        echo "🔄 Applying migration: {$filename}\n";
        
        // Read migration file
        $sql = file_get_contents($filepath);
        if ($sql === false) {
            throw new Exception("Failed to read migration file: {$filepath}");
        }
        
        try {
            // Execute migration SQL
            // Remove comment lines but preserve the actual SQL
            $cleanedSql = '';
            $lines = explode("\n", $sql);
            foreach ($lines as $line) {
                // Skip pure comment lines (lines that start with --)
                if (!preg_match('/^\s*--/', $line)) {
                    $cleanedSql .= $line . "\n";
                }
            }
            
            // Split by semicolon to handle multiple statements
            $statements = array_filter(
                array_map('trim', explode(';', $cleanedSql)),
                function($stmt) { return !empty($stmt); }
            );
            
            if (count($statements) > 0) {
                echo "   📋 Found " . count($statements) . " SQL statement(s) to process\n";
            }
            
            $executedStatements = 0;
            $skippedStatements = 0;
            $anySuccessful = false;
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    try {
                        // For CREATE TABLE IF NOT EXISTS, check if table exists first
                        if (preg_match('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+[`"]?(\w+)[`"]?/i', $statement, $matches)) {
                            $tableName = $matches[1];
                            $checkStmt = $this->db->query("SHOW TABLES LIKE '{$tableName}'");
                            if ($checkStmt->rowCount() > 0) {
                                echo "   ℹ️  Table '{$tableName}' already exists, skipping\n";
                                $skippedStatements++;
                                continue;
                            }
                        }
                        
                        $result = $this->db->exec($statement);
                        if ($result === 0) {
                            // No rows affected might mean the change was already applied
                            echo "   ℹ️  Statement executed but no changes made (possibly already applied)\n";
                        } else {
                            echo "   ✔️  Statement executed successfully ({$result} rows affected)\n";
                        }
                        $executedStatements++;
                        $anySuccessful = true;
                    } catch (PDOException $e) {
                        // Check for specific errors we want to ignore
                        $errorCode = $e->getCode();
                        $errorMessage = $e->getMessage();
                        
                        // MySQL error codes:
                        // 42S21 = Duplicate column
                        // 42S01 = Table already exists
                        // 42S02 = Table doesn't exist (for ALTER TABLE on non-existent table)
                        // 42000 = Duplicate key name
                        if ($errorCode == '42S21' || strpos($errorMessage, 'Duplicate column') !== false) {
                            echo "   ℹ️  Column already exists, skipping statement\n";
                            $skippedStatements++;
                            continue;
                        } elseif ($errorCode == '42S02' || strpos($errorMessage, "doesn't exist") !== false) {
                            echo "   ℹ️  Table doesn't exist, skipping statement\n";
                            $skippedStatements++;
                            continue;
                        } elseif ($errorCode == '42S01' || strpos($errorMessage, 'already exists') !== false) {
                            echo "   ℹ️  Table/Index already exists, skipping statement\n";
                            $skippedStatements++;
                            continue;
                        } elseif ($errorCode == '42000' && (strpos($errorMessage, 'Duplicate key') !== false || strpos($errorMessage, 'already exists') !== false)) {
                            echo "   ℹ️  Index/key already exists, skipping statement\n";
                            $skippedStatements++;
                            continue;
                        } else {
                            // Re-throw other errors
                            throw $e;
                        }
                    }
                }
            }
            
            echo "   📝 Executed {$executedStatements} SQL statement(s)";
            if ($skippedStatements > 0) {
                echo " (skipped {$skippedStatements} already applied)";
            }
            echo "\n";
            
            // Record successful migration (no transaction needed for this)
            try {
                $stmt = $this->db->prepare("INSERT INTO {$this->migrationsTable} (filename) VALUES (:filename)");
                $stmt->execute(['filename' => $filename]);
            } catch (PDOException $e) {
                // If the migration was already recorded, that's ok
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    echo "   ℹ️  Migration already recorded in database\n";
                } else {
                    throw $e;
                }
            }
            
            // Create applied directory if it doesn't exist
            $appliedDir = $this->migrationsDir . '/applied';
            if (!is_dir($appliedDir)) {
                mkdir($appliedDir, 0755, true);
            }
            
            // Copy to applied directory for tracking
            $appliedPath = $appliedDir . '/' . $filename;
            if (!file_exists($appliedPath)) {
                copy($filepath, $appliedPath);
            }
            
            echo "   ✅ Migration applied successfully\n\n";
            
        } catch (Exception $e) {
            throw new Exception("Migration {$filename} failed: " . $e->getMessage());
        }
    }
}

// Set output headers for web display
header('Content-Type: text/plain; charset=utf-8');

// Run migrations
try {
    // Use the $pdo connection from db.php
    $runner = new WebMigrationRunner($pdo);
    $success = $runner->run();
    
    echo "\n=== Migration Process Complete ===\n";
    echo "Finished at " . date('Y-m-d H:i:s') . "\n";
    echo "Status: " . ($success ? "✅ SUCCESS" : "❌ FAILED") . "\n\n";
    
    if ($success) {
        echo "🎉 All migrations have been applied successfully!\n";
        echo "Your database is now up to date.\n\n";
        echo "You can now:\n";
        echo "- Close this window\n";
        echo "- Return to the application\n";
        echo "- Test the widget functionality\n";
    } else {
        echo "❌ Some migrations failed to apply.\n";
        echo "Please check the errors above and try again.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "\nPlease check your database configuration and try again.\n";
    http_response_code(500);
}
?>