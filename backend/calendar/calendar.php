<?php

/**
 * calendar/calendar.php (oder äquivalenter Code-Block)
 * Enthält Hilfsfunktionen zur Verwaltung von Kalendereinträgen,
 * die mit anderen Tabellen (z.B. Bewerbungen) verknüpft sind.
 * Verwendet PDO für Datenbankzugriffe.
 */

declare(strict_types=1);

// Annahme: logging.php (mit logDatabaseChange) ist bereits inkludiert
// require_once __DIR__ . '/../logging/logging.php'; // Nicht hier, sondern im aufrufenden Skript

/**
 * Prüft, ob bereits ein Kalendereintrag für eine bestimmte Tabelle und ID existiert.
 *
 * @param PDO $pdo Das PDO Datenbankobjekt.
 * @param string $authority Die aktuelle Authority (für Tabellennamen).
 * @param string $linked_table Der Name der verknüpften Tabelle (z.B. 'kdd_fire_applicant').
 * @param int $linked_id Die ID des verknüpften Eintrags.
 * @return bool True, wenn ein Eintrag existiert, sonst False.
 */
function calendarEntryExist(PDO $pdo, string $authority, string $linked_table, int $linked_id): bool
{
    try {
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        $sql = "SELECT 1 FROM `kdd_calenda` WHERE authority_id = ?r WHERE linked_to_table = ? AND linked_to_id = ? LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $linked_table, $linked_id]);
        // fetchColumn() gibt den Wert der ersten Spalte zurück oder false, wenn keine Zeile gefunden wird.
        return $stmt->fetchColumn() !== false;
    } catch (\PDOException $e) {
        error_log("DB error in calendarEntryExist (Table: $linked_table, ID: $linked_id, Authority: $authority): " . $e->getMessage());
        // Im Fehlerfall annehmen, dass es nicht existiert oder Fehler signalisieren?
        // Hier wird false zurückgegeben, um ggf. das Erstellen zu ermöglichen.
        return false;
    }
}

/**
 * Fügt einen neuen Eintrag zum Kalender hinzu.
 *
 * @param PDO $pdo PDO Objekt.
 * @param int $requestingUserId ID des Benutzers, der die Aktion ausführt (für Logging).
 * @param string $authority Aktuelle Authority.
 * @param string $title Titel des Events.
 * @param string $content Kurzer Inhalt.
 * @param string $date Datum (YYYY-MM-DD).
 * @param string $time Zeit (HH:MM oder HH:MM:SS).
 * @param string $contentFull Längerer Inhalt/Details.
 * @param string $linked_table Name der verknüpften Tabelle.
 * @param int $linked_id ID des verknüpften Eintrags.
 * @return bool True bei Erfolg, False bei Fehler.
 */
function addEntryToCalendar(PDO $pdo, int $requestingUserId, string $authority, string $title, string $content, string $date, string $time, string $contentFull, string $linked_table, int $linked_id): bool
{
    try {
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        // Datum und Zeit verarbeiten
        $timezone = new DateTimeZone('Europe/Berlin'); // Oder aus Config/Env holen
        // Versuche, Zeit flexibler zu parsen (HH:MM oder HH:MM:SS)
        $timeFormatted = date('H:i:s', strtotime($time)); // Versucht, verschiedene Formate zu parsen
        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $date . ' ' . $timeFormatted, $timezone);

        if ($dateTime === false) {
            // Get authority ID
            global $decoded;
            $authority = $decoded->authority ?? null;
            global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
            if (!$authorityId) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid authority']);
                exit;
            }

            error_log("Failed to create DateTime object in addEntryToCalendar. Date: $date, Time: $time, FormattedTime: $timeFormatted");
            return false; // Fehler signalisieren
        }
        $start_date = $dateTime->format('Y-m-d H:i:s');
        // End_date auf 30 Minuten nach start_date setzen (anpassbar)
        $dateTime->modify('+30 minutes');
        $end_date = $dateTime->format('Y-m-d H:i:s');

        // Standardwerte
        $recurring = ''; // Oder null, je nach DB-Schema
        $color = 'orange'; // Standardfarbe

        // SQL vorbereiten und ausführen
        $sql = "INSERT INTO `kdd_calendar` (authority_id, title, content, start_date, end_date, color, contentFull, recurring, linked_to_table, linked_to_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            $authorityId,
            $title,
            $content,
            $start_date,
            $end_date,
            $color,
            $contentFull,
            $recurring,
            $linked_table,
            $linked_id
        ]);

        if ($success) {
            $newId = $pdo->lastInsertId();
            // Logbucheintrag (angepasst an PDO)
            $eventJson = json_encode(['title' => $title, 'start_date' => $start_date, 'end_date' => $end_date, 'color' => $color, /* ... andere Felder ... */]);
            $changes = [['column_name' => 'row_data', 'old_value' => null, 'new_value' => $eventJson]];
            // Annahme: logDatabaseChange akzeptiert $pdo als zweiten Parameter
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "kdd_calendar", $newId, $requestingUserId, $changes);
            return true;
        } else {
            // Execute() gab false zurück, aber keine Exception (weniger wahrscheinlich mit ERRMODE_EXCEPTION)
            error_log("Failed to execute addEntryToCalendar statement for authority $authority.");
            return false;
        }
    } catch (\PDOException | \Exception $e) { // Auch generelle Exceptions (z.B. von DateTime) fangen
        error_log("Error in addEntryToCalendar (Table: $linked_table, ID: $linked_id, Authority: $authority): " . $e->getMessage());
        return false; // Fehler signalisieren
    }
}

/**
 * Bearbeitet einen existierenden Kalendereintrag basierend auf der verknüpften Tabelle/ID.
 *
 * @param PDO $pdo PDO Objekt.
 * @param int $requestingUserId ID des Benutzers, der die Aktion ausführt (für Logging).
 * @param string $authority Aktuelle Authority.
 * @param string $title Neuer Titel.
 * @param string $content Neuer kurzer Inhalt.
 * @param string $date Neues Datum (YYYY-MM-DD).
 * @param string $time Neue Zeit (HH:MM oder HH:MM:SS).
 * @param string $contentFull Neuer langer Inhalt.
 * @param string $linked_table Name der verknüpften Tabelle.
 * @param int $linked_id ID des verknüpften Eintrags.
 * @return bool True bei Erfolg, False bei Fehler.
 */
function editEntryToCalendar(PDO $pdo, int $requestingUserId, string $authority, string $title, string $content, string $date, string $time, string $contentFull, string $linked_table, int $linked_id): bool
{
    try {
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        // Datum und Zeit verarbeiten
        $timezone = new DateTimeZone('Europe/Berlin');
        $timeFormatted = date('H:i:s', strtotime($time));
        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $date . ' ' . $timeFormatted, $timezone);

        if ($dateTime === false) {
            // Get authority ID
            global $decoded;
            $authority = $decoded->authority ?? null;
            global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
            if (!$authorityId) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid authority']);
                exit;
            }

            error_log("Failed to create DateTime object in editEntryToCalendar. Date: $date, Time: $time, FormattedTime: $timeFormatted");
            return false;
        }
        $start_date = $dateTime->format('Y-m-d H:i:s');
        $dateTime->modify('+30 minutes');
        $end_date = $dateTime->format('Y-m-d H:i:s');

        // Standardwerte (oder aus bestehendem Eintrag holen, falls nur Teile geändert werden sollen)
        $recurring = '';
        $color = 'orange';

        // Optional: Alten Eintrag für Logging holen
        // $oldEntry = getEntryById($pdo, $linked_id, $linked_table); // Braucht eine ID des Kalendereintrags selbst, nicht die linked_id!

        // SQL vorbereiten und ausführen
        $sql = "UPDATE `kdd_calendar` SET authority_id = ?, title = ?, content = ?, start_date = ?, end_date = ?, color = ?,
                    contentFull = ?, recurring = ?
                WHERE linked_to_table = ? AND linked_to_id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            $authorityId,
            $title,
            $content,
            $start_date,
            $end_date,
            $color,
            $contentFull,
            $recurring,
            $linked_table,
            $linked_id
        ]);

        if ($success) {
            // Optional: Logging hinzufügen, falls benötigt (komplexer, da die ID des Kalendereintrags benötigt wird)
            // $calendarEntryId = ... // ID des Kalendereintrags holen (braucht evtl. separaten SELECT)
            // $updatedEntry = getEntryById($pdo, $calendarEntryId, "kdd_{{$authority}}_calendar");
            // $changes = getEntryChanges($oldEntry, $updatedEntry);
            // global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "kdd_{{$authority}}_calendar", $calendarEntryId, $requestingUserId, $changes);
            return true;
        } else {
            error_log("Failed to execute editEntryToCalendar statement for authority $authority.");
            return false;
        }
    } catch (\PDOException | \Exception $e) {
        error_log("Error in editEntryToCalendar (Table: $linked_table, ID: $linked_id, Authority: $authority): " . $e->getMessage());
        return false;
    }
}

/**
 * Löscht einen Kalendereintrag basierend auf der verknüpften Tabelle/ID.
 *
 * @param PDO $pdo PDO Objekt.
 * @param int $requestingUserId ID des Benutzers, der die Aktion ausführt (für Logging).
 * @param string $authority Aktuelle Authority.
 * @param string $linked_table Name der verknüpften Tabelle.
 * @param int $linked_id ID des verknüpften Eintrags.
 * @return bool True bei Erfolg, False bei Fehler.
 */
function deleteEntryFromCalendar(PDO $pdo, int $requestingUserId, string $authority, string $linked_table, int $linked_id): bool
{
    try {
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;

        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            exit;
        }


        // SQL vorbereiten und ausführen
        $sql = "DELETE FROM kdd_calendar WHERE linked_to_table = ? AND linked_to_id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $linked_table, $linked_id]);

        if ($success && $stmt->rowCount() > 0) { // Prüfen ob etwas gelöscht wurde
            // Optional: Logging hinzufügen
            // global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "kdd_calendar", $calendarEntryId ?? $linked_id, $requestingUserId, []);
            return true;
        } elseif ($success) {
            // Kein Fehler, aber auch nichts gelöscht (existierte nicht)
            return true; // Oder false signalisieren? Hängt von der Erwartung ab.
        } else {
            error_log("Failed to execute deleteEntryFromCalendar statement for authority $authority.");
            return false;
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteEntryFromCalendar (Table: $linked_table, ID: $linked_id, Authority: $authority): " . $e->getMessage());
        return false;
    }
}
