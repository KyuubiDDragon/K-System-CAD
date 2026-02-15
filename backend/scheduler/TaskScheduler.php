<?php
/**
 * TaskScheduler.php
 *
 * PHP-basierter Task-Scheduler als Crontab-Alternative.
 * Wird bei jedem Request ausgeführt und prüft ob Tasks fällig sind.
 *
 * WICHTIG: Führt Tasks nur aus, wenn:
 * 1. Noch nie ausgeführt ODER
 * 2. Letzte Ausführung > 24 Stunden her
 *
 * Alternative zu Crontab - funktioniert ohne Systemrechte.
 */
declare(strict_types=1);

class TaskScheduler {
    private PDO $pdo;
    private string $lockDir;

    public function __construct(PDO $pdo, string $lockDir = '/tmp/k-systems-scheduler') {
        $this->pdo = $pdo;
        $this->lockDir = $lockDir;

        // Erstelle Lock-Verzeichnis falls nicht existiert
        if (!is_dir($this->lockDir)) {
            mkdir($this->lockDir, 0755, true);
        }
    }

    /**
     * Haupt-Scheduler-Methode
     * Rufe diese in bootstrap.php oder einer häufig besuchten Seite auf
     */
    public function run(): void {
        // Verhindere gleichzeitige Ausführung
        if ($this->isRunning()) {
            return;
        }

        try {
            $this->setRunning(true);

            // Prüfe und führe Tasks aus
            $this->runDailyTasks();

        } finally {
            $this->setRunning(false);
        }
    }

    /**
     * Führt tägliche Tasks aus (wenn fällig)
     */
    private function runDailyTasks(): void {
        // Task 1: Log-Archivierung (täglich um 2 Uhr ODER wenn >24h her)
        $this->runTaskIfDue('log_archiving', 24, function() {
            $this->logMessage("Starting log archiving...");

            require_once __DIR__ . '/../logging/logging.php';

            // Archiviere Logs älter als 90 Tage
            $archiveResult = archiveOldLogs($this->pdo, 90, 10000, false);

            if (!empty($archiveResult['errors'])) {
                $this->logMessage("ERRORS: " . implode(', ', $archiveResult['errors']));
                return false;
            }

            $this->logMessage("SUCCESS: Archived {$archiveResult['archived']}, Deleted {$archiveResult['deleted']}");

            // Bereinige Archive älter als 1 Jahr
            $cleanupResult = cleanupArchive($this->pdo, 365);
            $this->logMessage("Cleanup: Deleted {$cleanupResult['deleted']} from archive");

            // Optimiere Tabellen
            $this->pdo->exec("OPTIMIZE TABLE kdd_database_logs");
            $this->pdo->exec("OPTIMIZE TABLE kdd_database_logs_archive");
            $this->logMessage("Tables optimized");

            return true;
        });

        // Task 2: Weitere tägliche Tasks können hier hinzugefügt werden
        // $this->runTaskIfDue('other_task', 24, function() { ... });
    }

    /**
     * Führt einen Task aus, wenn er fällig ist
     *
     * @param string $taskName Eindeutiger Task-Name
     * @param int $intervalHours Intervall in Stunden
     * @param callable $callback Task-Funktion
     */
    private function runTaskIfDue(string $taskName, int $intervalHours, callable $callback): void {
        $lastRun = $this->getLastRun($taskName);
        $now = time();

        // Berechne nächste fällige Zeit
        if ($lastRun === null) {
            // Noch nie ausgeführt - bei erster Ausführung bis 2 Uhr warten
            $nextRun = strtotime('today 02:00:00');
            if ($nextRun < $now) {
                $nextRun = strtotime('tomorrow 02:00:00');
            }
        } else {
            // Letzte Ausführung + Intervall
            $nextRun = $lastRun + ($intervalHours * 3600);

            // Passe auf 2 Uhr an (für tägliche Tasks)
            if ($intervalHours >= 24) {
                $nextRunDate = date('Y-m-d', $nextRun);
                $nextRun = strtotime($nextRunDate . ' 02:00:00');
            }
        }

        // Prüfe ob Task fällig ist
        if ($now < $nextRun) {
            // Noch nicht fällig
            return;
        }

        $this->logMessage("Running task: {$taskName}");

        try {
            $success = $callback();

            if ($success !== false) {
                $this->setLastRun($taskName, $now);
                $this->logMessage("Task completed: {$taskName}");
            } else {
                $this->logMessage("Task failed: {$taskName}");
            }

        } catch (\Exception $e) {
            $this->logMessage("Task error ({$taskName}): " . $e->getMessage());
        }
    }

    /**
     * Prüft ob Scheduler bereits läuft
     */
    private function isRunning(): bool {
        $lockFile = $this->lockDir . '/scheduler.lock';

        if (file_exists($lockFile)) {
            $lockTime = (int)file_get_contents($lockFile);
            $age = time() - $lockTime;

            // Lock älter als 30 Minuten? Wahrscheinlich Deadlock, ignoriere Lock
            if ($age > 1800) {
                unlink($lockFile);
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Setzt Running-Status
     */
    private function setRunning(bool $running): void {
        $lockFile = $this->lockDir . '/scheduler.lock';

        if ($running) {
            file_put_contents($lockFile, time());
        } else {
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
        }
    }

    /**
     * Holt letzte Ausführungszeit eines Tasks
     */
    private function getLastRun(string $taskName): ?int {
        $file = $this->lockDir . '/' . $taskName . '.lastrun';

        if (file_exists($file)) {
            return (int)file_get_contents($file);
        }

        return null;
    }

    /**
     * Speichert letzte Ausführungszeit eines Tasks
     */
    private function setLastRun(string $taskName, int $timestamp): void {
        $file = $this->lockDir . '/' . $taskName . '.lastrun';
        file_put_contents($file, $timestamp);
    }

    /**
     * Schreibt Log-Nachricht
     */
    private function logMessage(string $message): void {
        $logFile = $this->lockDir . '/scheduler.log';
        $timestamp = date('Y-m-d H:i:s');
        $line = "[{$timestamp}] {$message}\n";

        file_put_contents($logFile, $line, FILE_APPEND);
        error_log("TaskScheduler: {$message}");
    }
}
