#!/usr/bin/env php
<?php
/**
 * Cron Job: Log Archivierung
 *
 * Führt automatische Archivierung alter Logs aus.
 * Empfohlene Cron-Konfiguration:
 *
 * # Täglich um 2 Uhr morgens
 * 0 2 * * * /usr/bin/php /path/to/K-Systems/backend/cron/archive_logs.php
 *
 * # Oder wöchentlich Sonntags um 3 Uhr
 * 0 3 * * 0 /usr/bin/php /path/to/K-Systems/backend/cron/archive_logs.php
 */
declare(strict_types=1);

// Verhindere Browser-Zugriff
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from command line.');
}

// Bootstrap
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../logging/logging.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting log archiving process...\n";

try {
    // Konfiguration (kann in .env ausgelagert werden)
    $retentionDays = (int)($_ENV['LOG_RETENTION_DAYS'] ?? 90);  // Logs älter als 90 Tage archivieren
    $archiveRetentionDays = (int)($_ENV['ARCHIVE_RETENTION_DAYS'] ?? 365); // Archive älter als 1 Jahr löschen
    $batchSize = 10000;

    echo "Configuration:\n";
    echo "  - Active logs retention: {$retentionDays} days\n";
    echo "  - Archive retention: {$archiveRetentionDays} days\n";
    echo "  - Batch size: {$batchSize}\n\n";

    // 1. Archiviere alte Logs
    echo "Step 1: Archiving old logs...\n";
    $archiveResult = archiveOldLogs($pdo, $retentionDays, $batchSize, false);

    if (!empty($archiveResult['errors'])) {
        echo "ERRORS during archiving:\n";
        foreach ($archiveResult['errors'] as $error) {
            echo "  - {$error}\n";
        }
    } else {
        echo "SUCCESS: {$archiveResult['message']}\n";
        echo "  - Archived: {$archiveResult['archived']} records\n";
        echo "  - Deleted: {$archiveResult['deleted']} records\n";
    }

    echo "\n";

    // 2. Bereinige sehr alte Archive
    echo "Step 2: Cleaning up old archives...\n";
    $cleanupResult = cleanupArchive($pdo, $archiveRetentionDays);

    if (!empty($cleanupResult['errors'])) {
        echo "ERRORS during cleanup:\n";
        foreach ($cleanupResult['errors'] as $error) {
            echo "  - {$error}\n";
        }
    } else {
        echo "SUCCESS: {$cleanupResult['message']}\n";
        echo "  - Deleted from archive: {$cleanupResult['deleted']} records\n";
    }

    echo "\n";

    // 3. Optimiere Tabellen (komprimiert und defragmentiert)
    echo "Step 3: Optimizing tables...\n";
    $pdo->exec("OPTIMIZE TABLE kdd_database_logs");
    $pdo->exec("OPTIMIZE TABLE kdd_database_logs_archive");
    echo "SUCCESS: Tables optimized\n";

    echo "\n[" . date('Y-m-d H:i:s') . "] Log archiving completed successfully.\n";
    exit(0);

} catch (\Exception $e) {
    echo "\nFATAL ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
