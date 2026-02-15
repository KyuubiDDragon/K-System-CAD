<?php
/**
 * logging.php
 * Enthält Hilfsfunktionen für das Logging von Datenbankänderungen
 * und zum Abrufen von Daten für Vergleichszwecke.
 * Verwendet PDO für Datenbankzugriffe.
 */
declare(strict_types=1);

/**
 * Loggt Datenbankänderungen in eine spezifische Log-Tabelle.
 * OPTIMIERT: Konsolidiert UPDATEs mit vielen Änderungen in einen einzelnen Log-Eintrag.
 *
 * @param int $authorityId Die Authority ID.
 * @param PDO $pdo Das PDO-Datenbankobjekt.
 * @param string $action Aktionstyp (z.B. 'INSERT', 'UPDATE', 'DELETE').
 * @param string $tableName Name der Tabelle, in der die Änderung stattfand.
 * @param int|string $recordId ID des betroffenen Datensatzes.
 * @param int $userId ID des Benutzers, der die Änderung durchgeführt hat.
 * @param array $changes Ein Array von Änderungen, wobei jedes Element ein Array ist mit ['column_name', 'old_value', 'new_value'].
 * @param bool $consolidate Bei true: Konsolidiert alle Änderungen in 1 Log-Eintrag (empfohlen für UPDATEs mit >3 Änderungen)
 * @return bool True bei Erfolg, False bei Fehlern.
 */
if (!function_exists('logDatabaseChange')) {
function logDatabaseChange(int $authorityId, PDO $pdo, string $action, string $tableName, $recordId, int $userId, array $changes, bool $consolidate = null): bool {
    try {

        if (!$authorityId) {
            error_log("Authority not found for logging: authority_id={$authorityId}");
            return false;
        }

        // Auto-Konsolidierung: Bei UPDATEs mit >3 Änderungen automatisch konsolidieren
        if ($consolidate === null) {
            $consolidate = ($action === 'UPDATE' && count($changes) > 3);
        }

        $sql = "INSERT INTO `kdd_database_logs` (action, table_name, record_id, user_id, column_name, old_value, new_value, timestamp, authority_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
        $stmt = $pdo->prepare($sql);

        // KONSOLIDIERTER MODUS: Alle Änderungen in 1 Log-Eintrag
        if ($consolidate && $action === 'UPDATE') {
            $oldValues = [];
            $newValues = [];

            foreach ($changes as $change) {
                $columnName = $change['column_name'] ?? null;
                $oldValues[$columnName] = $change['old_value'] ?? null;
                $newValues[$columnName] = $change['new_value'] ?? null;
            }

            $success = $stmt->execute([
                $action,
                $tableName,
                $recordId,
                $userId,
                'BATCH_UPDATE', // Special marker für konsolidierte Updates
                json_encode($oldValues),
                json_encode($newValues),
                $authorityId
            ]);

            return $success;
        }

        // NORMALER MODUS: Jede Änderung einzeln (INSERT, DELETE, oder kleine UPDATEs)
        $all_successful = true;

        foreach ($changes as $change) {
            $columnName = $change['column_name'] ?? null;
            // Konvertiere Arrays/Objekte zu JSON
            $oldValue = is_array($change['old_value']) || is_object($change['old_value']) ? json_encode($change['old_value']) : ($change['old_value'] ?? null);
            $newValue = is_array($change['new_value']) || is_object($change['new_value']) ? json_encode($change['new_value']) : ($change['new_value'] ?? null);

            $success = $stmt->execute([
                $action,
                $tableName,
                $recordId,
                $userId,
                $columnName,
                $oldValue,
                $newValue,
                $authorityId
            ]);

            if (!$success) {
                $all_successful = false;
                error_log("Failed to log change for authority '{$authorityId}': Action={$action}, Table={$tableName}, RecordID={$recordId}, Column={$columnName}");
            }
        }

        return $all_successful;

    } catch (\PDOException $e) {
        error_log("Database error in logDatabaseChange for authority '{$authorityId}': " . $e->getMessage());
        return false;
    }
}
}

/**
 * Vergleicht zwei assoziative Arrays (alter vs. neuer Zustand eines DB-Eintrags)
 * und gibt ein Array der geänderten Spalten zurück.
 *
 * @param array|object|null $oldState Assoziatives Array oder Objekt des alten Zustands.
 * @param array|object $newState Assoziatives Array oder Objekt des neuen Zustands.
 * @return array Array von Änderungen [['column_name', 'old_value', 'new_value'], ...].
 */
function getEntryChanges($oldState, $newState): array {
    $changes = [];
    // Konvertiere Objekte zu Arrays für einfachen Vergleich
    $oldData = is_object($oldState) ? (array)$oldState : ($oldState ?? []);
    $newData = is_object($newState) ? (array)$newState : ($newState ?? []);

    // Gehe durch den neuen Zustand, um geänderte oder neue Schlüssel zu finden
    foreach ($newData as $key => $newValue) {
        $oldValue = $oldData[$key] ?? null; // Hole alten Wert oder null wenn neu
        // Vergleiche Werte (Typ-unsicherer Vergleich oft ausreichend, da DB-Typen variieren können)
        if ($newValue != $oldValue) {
            $changes[] = [
                'column_name' => $key,
                'old_value' => $oldValue, // Kann null sein für neue Felder
                'new_value' => $newValue
            ];
        }
    }

    // Optional: Prüfe auf im neuen Zustand entfernte Schlüssel (falls das vorkommen kann)
    foreach ($oldData as $key => $oldValue) {
        if (!array_key_exists($key, $newData)) {
            $changes[] = [
                'column_name' => $key,
                'old_value' => $oldValue,
                'new_value' => null // Markiert als entfernt
            ];
        }
    }

    return $changes;
}

/**
 * Holt einen einzelnen Datenbankeintrag anhand seiner ID.
 *
 * @param PDO $pdo Das PDO Datenbankobjekt.
 * @param int|string $id Die ID des gesuchten Eintrags.
 * @param string $tableName Der Name der Tabelle.
 * @param int|null $authorityId Die authority_id für den Filter.
 * @return array|false Das assoziative Array des Eintrags oder false bei Fehler/Nicht gefunden.
 */
function getEntryById(PDO $pdo, $id, string $tableName, ?int $authorityId = null) {
    try {
        if ($authorityId) {
            $sql = "SELECT * FROM {$tableName} WHERE id = ? AND authority_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id, $authorityId]);
        } else {
            $sql = "SELECT * FROM {$tableName} WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
        }
        // Fetch als assoziatives Array (Standard durch db.php, aber explizit ist sicherer)
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        return $entry; // Gibt das Array oder false zurück, wenn nichts gefunden wurde

    } catch (\PDOException $e) {
        error_log("Database error in getEntryById (Table: {$tableName}, ID: {$id}): " . $e->getMessage());
        return false; // Fehler signalisieren
    }
}

/**
 * Archiviert alte Log-Einträge und löscht sie aus der Haupt-Tabelle.
 * OPTIMIERT: Reduziert Datenbankgröße und verbessert Performance.
 *
 * @param PDO $pdo Das PDO-Datenbankobjekt.
 * @param int $retentionDays Anzahl Tage, die Logs in Haupt-Tabelle bleiben (Standard: 90)
 * @param int $batchSize Anzahl Datensätze pro Batch (Standard: 10000)
 * @param bool $dryRun Wenn true: Nur zählen, nicht archivieren (Standard: false)
 * @return array ['archived' => int, 'deleted' => int, 'errors' => array]
 */
function archiveOldLogs(PDO $pdo, int $retentionDays = 90, int $batchSize = 10000, bool $dryRun = false): array {
    $result = [
        'archived' => 0,
        'deleted' => 0,
        'errors' => []
    ];

    try {
        // Prüfe ob Archive-Tabelle existiert
        $checkTable = $pdo->query("SHOW TABLES LIKE 'kdd_database_logs_archive'");
        if ($checkTable->rowCount() === 0) {
            $result['errors'][] = "Archive table 'kdd_database_logs_archive' does not exist. Run migration first.";
            return $result;
        }

        // Zähle zu archivierende Logs
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));
        $countSql = "SELECT COUNT(*) as total FROM kdd_database_logs WHERE timestamp < ?";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute([$cutoffDate]);
        $totalToArchive = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        if ($totalToArchive === 0) {
            $result['message'] = "No logs to archive (retention: {$retentionDays} days)";
            return $result;
        }

        if ($dryRun) {
            $result['message'] = "DRY RUN: Would archive {$totalToArchive} logs older than {$cutoffDate}";
            return $result;
        }

        // Archiviere in Batches
        $pdo->beginTransaction();

        $archived = 0;
        $offset = 0;

        while ($archived < $totalToArchive) {
            // Kopiere Batch in Archive
            $archiveSql = "INSERT INTO kdd_database_logs_archive
                          (id, action, table_name, record_id, user_id, column_name, old_value, new_value, timestamp, last_login, authority_id, archived_at)
                          SELECT id, action, table_name, record_id, user_id, column_name, old_value, new_value, timestamp, last_login, authority_id, NOW()
                          FROM kdd_database_logs
                          WHERE timestamp < ?
                          LIMIT ? OFFSET ?";

            $stmt = $pdo->prepare($archiveSql);
            $stmt->execute([$cutoffDate, $batchSize, $offset]);
            $archivedThisBatch = $stmt->rowCount();

            if ($archivedThisBatch === 0) {
                break; // Keine weiteren Datensätze
            }

            $archived += $archivedThisBatch;
            $offset += $batchSize;

            // Fortschritt loggen (alle 50.000 Datensätze)
            if ($archived % 50000 === 0) {
                error_log("Log archiving progress: {$archived}/{$totalToArchive} archived");
            }
        }

        // Lösche archivierte Logs aus Haupt-Tabelle
        $deleteSql = "DELETE FROM kdd_database_logs WHERE timestamp < ?";
        $stmt = $pdo->prepare($deleteSql);
        $stmt->execute([$cutoffDate]);
        $deleted = $stmt->rowCount();

        $pdo->commit();

        $result['archived'] = $archived;
        $result['deleted'] = $deleted;
        $result['message'] = "Successfully archived {$archived} and deleted {$deleted} logs older than {$cutoffDate}";

        error_log("Log archiving completed: {$archived} archived, {$deleted} deleted (retention: {$retentionDays} days)");

    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $result['errors'][] = "Database error during archiving: " . $e->getMessage();
        error_log("Log archiving failed: " . $e->getMessage());
    }

    return $result;
}

/**
 * Bereinigt die Archive-Tabelle (löscht sehr alte Archive).
 *
 * @param PDO $pdo Das PDO-Datenbankobjekt.
 * @param int $archiveRetentionDays Anzahl Tage, die Archive behalten werden (Standard: 365)
 * @return array ['deleted' => int, 'errors' => array]
 */
function cleanupArchive(PDO $pdo, int $archiveRetentionDays = 365): array {
    $result = [
        'deleted' => 0,
        'errors' => []
    ];

    try {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$archiveRetentionDays} days"));

        $sql = "DELETE FROM kdd_database_logs_archive WHERE timestamp < ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cutoffDate]);
        $deleted = $stmt->rowCount();

        $result['deleted'] = $deleted;
        $result['message'] = "Cleaned up {$deleted} archived logs older than {$cutoffDate}";

        if ($deleted > 0) {
            error_log("Archive cleanup: {$deleted} logs deleted (archive retention: {$archiveRetentionDays} days)");
        }

    } catch (\PDOException $e) {
        $result['errors'][] = "Database error during cleanup: " . $e->getMessage();
        error_log("Archive cleanup failed: " . $e->getMessage());
    }

    return $result;
}

?>