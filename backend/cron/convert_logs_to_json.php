#!/usr/bin/env php
<?php
/**
 * Einmalige Konvertierung: TEXT zu JSON
 *
 * Konvertiert existierende TEXT-Werte in kdd_database_logs zu gültigem JSON.
 * Sollte VOR der SQL-Migration ausgeführt werden, wenn bereits Daten existieren.
 *
 * Aufruf:
 * php backend/cron/convert_logs_to_json.php
 */
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from command line.');
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../db.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting TEXT to JSON conversion...\n\n";

try {
    $pdo->beginTransaction();

    // Zähle Datensätze
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM kdd_database_logs");
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total records to check: {$totalRecords}\n";

    $batchSize = 1000;
    $converted = 0;
    $errors = 0;
    $offset = 0;

    $updateStmt = $pdo->prepare("UPDATE kdd_database_logs SET old_value = ?, new_value = ? WHERE id = ?");

    while ($offset < $totalRecords) {
        $stmt = $pdo->prepare("SELECT id, old_value, new_value FROM kdd_database_logs LIMIT ? OFFSET ?");
        $stmt->execute([$batchSize, $offset]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($logs as $log) {
            $needsUpdate = false;
            $oldValue = $log['old_value'];
            $newValue = $log['new_value'];

            // Konvertiere old_value wenn nicht NULL und nicht bereits JSON
            if ($oldValue !== null && !isValidJson($oldValue)) {
                // Einfache Strings als JSON-String speichern
                $oldValue = json_encode($oldValue);
                $needsUpdate = true;
            }

            // Konvertiere new_value wenn nicht NULL und nicht bereits JSON
            if ($newValue !== null && !isValidJson($newValue)) {
                $newValue = json_encode($newValue);
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                try {
                    $updateStmt->execute([$oldValue, $newValue, $log['id']]);
                    $converted++;
                } catch (\PDOException $e) {
                    echo "ERROR converting log ID {$log['id']}: " . $e->getMessage() . "\n";
                    $errors++;
                }
            }
        }

        $offset += $batchSize;

        // Progress
        if ($offset % 10000 === 0) {
            echo "Progress: {$offset}/{$totalRecords} checked, {$converted} converted\n";
        }
    }

    $pdo->commit();

    echo "\n[" . date('Y-m-d H:i:s') . "] Conversion completed.\n";
    echo "Summary:\n";
    echo "  - Total checked: {$totalRecords}\n";
    echo "  - Converted: {$converted}\n";
    echo "  - Errors: {$errors}\n";

    if ($errors === 0) {
        echo "\nYou can now run the SQL migration:\n";
        echo "mysql -u root -p ksystems < database/migrations/migrate_logs_to_json.sql\n";
    }

    exit(0);

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\nFATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

function isValidJson($string) {
    if (empty($string)) return true;
    json_decode($string);
    return (json_last_error() === JSON_ERROR_NONE);
}
