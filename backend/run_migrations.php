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
            
            /*
               Je Datei frisch nachsehen statt einmal vorab.
               Eine Migration darf eintragen, dass eine andere bereits von Hand
               angewendet wurde - mit einer einmal vorab geladenen Liste wuerde
               dieser Eintrag im selben Lauf uebergangen.
            */
            $pendingCount = 0;
            foreach ($migrations as $migration) {
                if (!$this->isApplied($migration)) {
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
            /*
               Vier Ziffern am Anfang genuegen - der Unterstrich dahinter ist
               nicht mehr Pflicht. Zuvor lautete das Muster ^\d{4}_ und
               uebersprang damit stillschweigend jede Datei der Form
               20251028_... : drei Migrationen liefen deshalb nie automatisch,
               und der Lauf meldete trotzdem "All migrations are already
               applied". Dateien ganz ohne Jahreszahl bleiben aussen vor, das
               sind Hilfsskripte und keine Migrationen.
            */
            if (preg_match('/^\d{4}.*\.sql$/', $file)) {
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
     * Steht diese Datei bereits als angewendet in der Tabelle?
     */
    private function isApplied($filename) {
        $stmt = $this->db->prepare("SELECT 1 FROM {$this->migrationsTable} WHERE filename = ? LIMIT 1");
        $stmt->execute([$filename]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Zerlegt eine Migrationsdatei in einzelne Anweisungen.
     *
     * Zuvor stand hier explode(';', $sql). Ein Semikolon in einem Kommentar
     * oder in einer Zeichenkette zerschnitt damit die Anweisung dahinter - die
     * Migration lief auf einen Syntaxfehler oder, schlimmer, zur Haelfte durch,
     * und der Lauf meldete trotzdem Erfolg. Diese Fassung laeuft einmal durch
     * den Text und trennt nur an Semikolons, die wirklich eine Anweisung
     * beenden.
     */
    private function splitStatements($sql) {
        $statements = [];
        $current = '';
        $len = strlen($sql);
        $i = 0;

        while ($i < $len) {
            $c = $sql[$i];
            $next = ($i + 1 < $len) ? $sql[$i + 1] : '';

            // Zeilenkommentar
            if (($c === '-' && $next === '-') || $c === '#') {
                $ende = strpos($sql, "\n", $i);
                if ($ende === false) { break; }
                $current .= substr($sql, $i, $ende - $i + 1);
                $i = $ende + 1;
                continue;
            }

            // Blockkommentar
            if ($c === '/' && $next === '*') {
                $ende = strpos($sql, '*/', $i + 2);
                if ($ende === false) { break; }
                $current .= substr($sql, $i, $ende - $i + 2);
                $i = $ende + 2;
                continue;
            }

            // Zeichenkette oder Bezeichner in Anfuehrungszeichen
            if ($c === "'" || $c === '"' || $c === '`') {
                $quote = $c;
                $current .= $c;
                $i++;
                while ($i < $len) {
                    $z = $sql[$i];
                    if ($z === '\\' && $i + 1 < $len) {
                        $current .= $z . $sql[$i + 1];
                        $i += 2;
                        continue;
                    }
                    $current .= $z;
                    $i++;
                    if ($z === $quote) {
                        // Verdoppeltes Anfuehrungszeichen gehoert noch dazu
                        if ($i < $len && $sql[$i] === $quote) {
                            $current .= $quote;
                            $i++;
                            continue;
                        }
                        break;
                    }
                }
                continue;
            }

            if ($c === ';') {
                $statements[] = $current;
                $current = '';
                $i++;
                continue;
            }

            $current .= $c;
            $i++;
        }
        $statements[] = $current;

        // Was ausser Kommentaren und Leerraum nichts enthaelt, ist keine Anweisung.
        $echte = [];
        foreach ($statements as $stmt) {
            $ohne = preg_replace('#/\*.*?\*/#s', '', $stmt);
            $ohne = preg_replace('/^\s*(--|\#).*$/m', '', (string)$ohne);
            if (trim((string)$ohne) !== '') {
                $echte[] = trim($stmt);
            }
        }

        return $echte;
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
            $statements = $this->splitStatements($sql);

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
        $password = $_ENV['DB_PASSWORD'] ?? '';
        
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