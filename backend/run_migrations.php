#!/usr/bin/env php
<?php
/**
 * Database Migration Runner for K-Systems
 * 
 * This script manages database migrations:
 * - Creates migration tracking table if not exists
 * - Scans migrations directory for .sql files
 * - Executes only new migrations in order
 * - Records applied migrations
 * - Provides detailed output
 */

require_once __DIR__ . '/db.php';

class MigrationRunner {
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
        echo "Starting migration process...\n";
        
        try {
            // Ensure migrations table exists
            $this->createMigrationsTable();
            
            // Get list of migration files
            $migrations = $this->getMigrationFiles();
            
            if (empty($migrations)) {
                echo "No migration files found.\n";
                return true;
            }
            
            // Get already applied migrations
            $appliedMigrations = $this->getAppliedMigrations();
            
            // Run pending migrations
            $pendingCount = 0;
            foreach ($migrations as $migration) {
                if (!in_array($migration, $appliedMigrations)) {
                    $this->runMigration($migration);
                    $pendingCount++;
                }
            }
            
            if ($pendingCount === 0) {
                echo "All migrations are already applied.\n";
            } else {
                echo "Successfully applied {$pendingCount} migration(s).\n";
            }
            
            return true;
            
        } catch (Exception $e) {
            echo "Migration failed: " . $e->getMessage() . "\n";
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
        echo "Migrations table ready.\n";
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
        
        echo "Found " . count($files) . " migration file(s).\n";
        return $files;
    }
    
    /**
     * Get list of already applied migrations
     */
    private function getAppliedMigrations() {
        $stmt = $this->db->query("SELECT filename FROM {$this->migrationsTable}");
        $applied = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $applied[] = $row['filename'];
        }
        
        return $applied;
    }
    
    /**
     * Run a single migration file
     */
    private function runMigration($filename) {
        $filepath = $this->migrationsDir . '/' . $filename;

        echo "Applying migration: {$filename}...\n";

        // Read migration file
        $sql = file_get_contents($filepath);
        if ($sql === false) {
            throw new Exception("Failed to read migration file: {$filepath}");
        }

        // Check if migration contains DDL statements (CREATE, ALTER, DROP)
        $hasDDL = preg_match('/^\s*(CREATE|ALTER|DROP)\s+/im', $sql);

        // Only use transaction for non-DDL migrations
        // DDL statements in MySQL cause implicit commits
        $useTransaction = !$hasDDL;

        if ($useTransaction) {
            $this->db->beginTransaction();
        } else {
            echo "  ℹ️  DDL detected, running without transaction.\n";
        }

        try {
            // Execute migration SQL
            // Split by semicolon to handle multiple statements
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($stmt) { return !empty($stmt); }
            );

            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    try {
                        $this->db->exec($statement);
                    } catch (PDOException $e) {
                        // Check for specific errors we want to ignore
                        $errorCode = $e->getCode();
                        $errorMessage = $e->getMessage();

                        // MySQL error codes:
                        // 42S21 = Duplicate column
                        // 42S01 = Table already exists
                        // 42000 = Duplicate key name
                        // 23000 = Duplicate entry
                        if ($errorCode == '42S21' || strpos($errorMessage, 'Duplicate column') !== false) {
                            echo "  ℹ️  Column already exists, skipping.\n";
                            continue;
                        } elseif ($errorCode == '42S01' || strpos($errorMessage, 'already exists') !== false) {
                            echo "  ℹ️  Table/Object already exists, skipping.\n";
                            continue;
                        } elseif ($errorCode == '42000' || strpos($errorMessage, 'Duplicate key name') !== false) {
                            echo "  ℹ️  Index already exists, skipping.\n";
                            continue;
                        } elseif (strpos($errorMessage, "Can't DROP") !== false) {
                            echo "  ℹ️  Object doesn't exist, skipping DROP.\n";
                            continue;
                        } else {
                            // Re-throw other errors
                            throw $e;
                        }
                    }
                }
            }

            // Record successful migration
            $stmt = $this->db->prepare("INSERT INTO {$this->migrationsTable} (filename) VALUES (:filename)");
            $stmt->execute(['filename' => $filename]);

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

            // Commit transaction only if we started one
            if ($useTransaction) {
                $this->db->commit();
            }

            echo "  ✓ Migration applied successfully.\n";

        } catch (Exception $e) {
            // Rollback on error only if we started a transaction
            if ($useTransaction && $this->db->inTransaction()) {
                $this->db->rollback();
            }
            throw new Exception("Migration {$filename} failed: " . $e->getMessage());
        }
    }
}

// Run migrations
try {
    // Check if $pdo exists from db.php
    if (isset($pdo)) {
        echo "Using existing database connection from db.php.\n";
        $db = $pdo;
    } else {
        // Fallback: Use environment variables directly
        $host = $_ENV['DB_HOST'] ?? 'db';
        $dbname = $_ENV['DB_DATABASE'] ?? 'ksystems';
        $username = $_ENV['DB_USERNAME'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? 'rootpassword';
        
        // Wait for database to be ready (for Docker)
        $maxAttempts = 30;
        $attempt = 0;
        $connected = false;
        
        echo "Waiting for database connection...\n";
        
        while ($attempt < $maxAttempts && !$connected) {
            try {
                $db = new PDO(
                    "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
                $connected = true;
                echo "Database connection established.\n";
            } catch (PDOException $e) {
                $attempt++;
                if ($attempt < $maxAttempts) {
                    echo "Connection attempt {$attempt}/{$maxAttempts} failed. Retrying in 2 seconds...\n";
                    sleep(2);
                } else {
                    throw new Exception("Failed to connect to database after {$maxAttempts} attempts.");
                }
            }
        }
    }
    
    // Run migrations
    $runner = new MigrationRunner($db);
    $success = $runner->run();
    
    exit($success ? 0 : 1);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}