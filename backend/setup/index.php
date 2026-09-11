<?php
/**
 * Ersteinrichtung.
 *
 * Der einzige Endpunkt ohne Anmeldung, und er ist nur so lange offen, wie es
 * noch keinen einzigen Benutzer gibt. Sobald einer angelegt ist, verweigert er
 * jede weitere Anfrage.
 *
 * Warum es ihn gibt: der Grundstock der Datenbank liefert bewusst kein Konto
 * mehr mit. Frueher stand dort ein "admin" mit dem allgemein bekannten Hash
 * eines Standardpassworts - jede Anlage waere mit demselben Zugang offen
 * gewesen. registerUser() im Konto-Endpunkt hilft hier nicht: es setzt ein
 * gueltiges Token voraus, das es vor dem ersten Konto nicht geben kann.
 *
 *   GET  ?action=status           -> { needsSetup: true|false }
 *   POST ?action=createFirstUser  -> legt das erste Konto an
 *
 * Das angelegte Konto bekommt die Rolle 20 "System Administrator". Ueber sie
 * haengen ALL_PERMISSIONS und SYSTEM_ADMIN daran - damit traegt der erste
 * Zugang saemtliche Rechte, ohne dass jemand in der Datenbank nacharbeiten
 * muss.
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once __DIR__ . '/../db.php';

/** Die Behoerde, die der Grundstock anlegt. */
const EINRICHTUNG_BEHOERDE_ID = 1;

/** Rolle mit ALL_PERMISSIONS und SYSTEM_ADMIN. */
const EINRICHTUNG_ROLLE_ID = 20;

/** Kuerzer als das ist kein Passwort, das diesen Zugang schuetzt. */
const MINDESTLAENGE_PASSWORT = 12;

/**
 * Gibt es schon einen Benutzer? Diese eine Frage entscheidet alles.
 */
function istEingerichtet(PDO $pdo): bool
{
    return (int)$pdo->query('SELECT COUNT(*) FROM kdd_users')->fetchColumn() > 0;
}

function antwort(int $status, array $inhalt): void
{
    http_response_code($status);
    echo json_encode($inhalt);
}

$action = isset($_GET['action']) ? (string)$_GET['action'] : '';
$methode = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($action === 'status' && $methode === 'GET') {
        antwort(200, ['needsSetup' => !istEingerichtet($pdo)]);
        exit();
    }

    if ($action === 'createFirstUser' && $methode === 'POST') {
        if (istEingerichtet($pdo)) {
            antwort(409, ['error' => 'Die Einrichtung ist bereits abgeschlossen.']);
            exit();
        }

        $roh = file_get_contents('php://input');
        $daten = json_decode($roh ?: '', true);
        if (!is_array($daten)) {
            antwort(400, ['error' => 'Ungueltige Anfrage.']);
            exit();
        }

        $benutzername = trim((string)($daten['username'] ?? ''));
        $email = trim((string)($daten['email'] ?? ''));
        $passwort = (string)($daten['password'] ?? '');

        if ($benutzername === '' || $email === '' || $passwort === '') {
            antwort(400, ['error' => 'Benutzername, E-Mail und Passwort sind erforderlich.']);
            exit();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            antwort(400, ['error' => 'Die E-Mail-Adresse ist nicht gueltig.']);
            exit();
        }
        if (mb_strlen($passwort) < MINDESTLAENGE_PASSWORT) {
            antwort(422, ['error' => 'Das Passwort braucht mindestens ' . MINDESTLAENGE_PASSWORT . ' Zeichen.']);
            exit();
        }

        // Ohne Behoerde und Rolle waere das Konto zwar da, aber ohne Rechte.
        $stmt = $pdo->prepare('SELECT name FROM kdd_authorities WHERE id = ?');
        $stmt->execute([EINRICHTUNG_BEHOERDE_ID]);
        $behoerde = $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM kdd_roles WHERE id = ? AND authority_id = ?');
        $stmt->execute([EINRICHTUNG_ROLLE_ID, EINRICHTUNG_BEHOERDE_ID]);
        $rolleDa = (int)$stmt->fetchColumn() > 0;

        if ($behoerde === false || !$rolleDa) {
            error_log('Ersteinrichtung: Behoerde ' . EINRICHTUNG_BEHOERDE_ID
                . ' oder Rolle ' . EINRICHTUNG_ROLLE_ID . ' fehlt - der Grundstock wurde nicht eingespielt.');
            antwort(500, ['error' => 'Die Grunddaten der Datenbank fehlen. Bitte den Grundstock einspielen.']);
            exit();
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO kdd_users (authority_id, username, email, password, authority, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                EINRICHTUNG_BEHOERDE_ID,
                $benutzername,
                $email,
                password_hash($passwort, PASSWORD_DEFAULT),
                $behoerde,
            ]);
            $benutzerId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO kdd_user_roles (user_id, role_id, authority_id) VALUES (?, ?, ?)'
            );
            $stmt->execute([$benutzerId, EINRICHTUNG_ROLLE_ID, EINRICHTUNG_BEHOERDE_ID]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        error_log('Ersteinrichtung abgeschlossen: Benutzer ' . $benutzerId . ' mit Rolle ' . EINRICHTUNG_ROLLE_ID);
        antwort(201, [
            'message' => 'Die Einrichtung ist abgeschlossen.',
            'userId' => $benutzerId,
            'authority' => $behoerde,
        ]);
        exit();
    }

    antwort(404, ['error' => 'Unbekannte Aktion.']);
} catch (Throwable $e) {
    error_log('Fehler in der Ersteinrichtung: ' . get_class($e) . ' ' . $e->getMessage()
        . ' @ ' . $e->getFile() . ':' . $e->getLine());
    antwort(500, ['error' => 'Die Einrichtung ist fehlgeschlagen.']);
}
