<?php
/**
 * Scheduler Endpoint: scheduler/index.php
 *
 * PHP-basierter Task-Scheduler als Crontab-Alternative.
 *
 * VERWENDUNG:
 *
 * Option 1: Manueller Aufruf (z.B. täglich)
 * curl http://localhost:8080/scheduler/?action=runTasks&key=YOUR_SECRET_KEY
 *
 * Option 2: Kostenloser externer Cron-Service
 * - https://cron-job.org
 * - https://www.easycron.com
 * Einfach URL eintragen: http://your-domain.com/scheduler/?action=runTasks&key=YOUR_SECRET_KEY
 *
 * Option 3: Aus dem Frontend (Admin-Panel Button)
 * fetch('/scheduler/?action=runTasks&key=...')
 *
 * SICHERHEIT: Nur mit korrektem Secret-Key ausführbar!
 */
declare(strict_types=1);

// --- Bootstrap ---
require_once __DIR__ . '/../bootstrap.php';

// --- Sicherheit: Secret-Key prüfen ---
$schedulerKey = getEnvVar('SCHEDULER_SECRET_KEY', 'change_me_in_production');
$providedKey = $_GET['key'] ?? $_POST['key'] ?? '';

if (empty($providedKey) || $providedKey !== $schedulerKey) {
    http_response_code(403);
    echo json_encode([
        'error' => 'Forbidden: Invalid or missing scheduler key',
        'hint' => 'Set SCHEDULER_SECRET_KEY in .env and provide it as ?key=...'
    ]);
    exit();
}

// --- Action Routing ---
$action = $_GET['action'] ?? 'status';

switch ($action) {
    case 'runTasks':
        runScheduledTasks();
        break;

    case 'status':
        getSchedulerStatus();
        break;

    case 'forceArchive':
        forceArchiveNow();
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action. Available: runTasks, status, forceArchive']);
        break;
}

exit();


// --- FUNCTIONS ---

/**
 * Führt alle geplanten Tasks aus
 */
function runScheduledTasks(): void {
    global $pdo;

    require_once __DIR__ . '/TaskScheduler.php';

    try {
        $scheduler = new TaskScheduler($pdo);
        $scheduler->run();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Scheduler executed successfully',
            'timestamp' => date('Y-m-d H:i:s'),
            'log' => getSchedulerLog()
        ]);

    } catch (\Exception $e) {
        error_log("Scheduler error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Scheduler execution failed',
            'details' => $e->getMessage()
        ]);
    }
}

/**
 * Zeigt Scheduler-Status an
 */
function getSchedulerStatus(): void {
    $lockDir = '/tmp/k-systems-scheduler';
    $status = [
        'scheduler_active' => file_exists($lockDir . '/scheduler.lock'),
        'last_runs' => []
    ];

    // Lese alle .lastrun Dateien
    if (is_dir($lockDir)) {
        $files = glob($lockDir . '/*.lastrun');
        foreach ($files as $file) {
            $taskName = basename($file, '.lastrun');
            $lastRun = (int)file_get_contents($file);
            $status['last_runs'][$taskName] = [
                'timestamp' => $lastRun,
                'datetime' => date('Y-m-d H:i:s', $lastRun),
                'hours_ago' => round((time() - $lastRun) / 3600, 2)
            ];
        }
    }

    $status['log'] = getSchedulerLog(50); // Letzte 50 Zeilen

    http_response_code(200);
    echo json_encode($status, JSON_PRETTY_PRINT);
}

/**
 * Erzwingt sofortige Archivierung (ignoriert Zeitplan)
 */
function forceArchiveNow(): void {
    global $pdo;

    require_once __DIR__ . '/../logging/logging.php';

    try {
        error_log("Force archive triggered via scheduler endpoint");

        // Archiviere Logs älter als 90 Tage
        $archiveResult = archiveOldLogs($pdo, 90, 10000, false);

        // Bereinige Archive älter als 1 Jahr
        $cleanupResult = cleanupArchive($pdo, 365);

        // Optimiere Tabellen
        $pdo->exec("OPTIMIZE TABLE kdd_database_logs");
        $pdo->exec("OPTIMIZE TABLE kdd_database_logs_archive");

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'archive' => $archiveResult,
            'cleanup' => $cleanupResult,
            'message' => 'Archive completed successfully'
        ]);

    } catch (\Exception $e) {
        error_log("Force archive error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Archive failed',
            'details' => $e->getMessage()
        ]);
    }
}

/**
 * Liest Scheduler-Log
 */
function getSchedulerLog(int $lines = 100): array {
    $logFile = '/tmp/k-systems-scheduler/scheduler.log';

    if (!file_exists($logFile)) {
        return ['No log file found'];
    }

    $content = file($logFile);
    $lastLines = array_slice($content, -$lines);

    return array_map('trim', $lastLines);
}
