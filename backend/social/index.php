<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/Service.php';
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
try {
    if (!filter_var(getEnvVar('SOCIAL_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN)) throw new \Kyuubi\Social\ApiError(404, 'Social ist nicht aktiviert.');
    $service = new \Kyuubi\Social\Service($pdo);
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, ['GET','POST'], true)) throw new \Kyuubi\Social\ApiError(405, 'Methode nicht erlaubt.');
    if ($method === 'POST') {
        // Same-origin proxy, custom header and no cross-origin mutation support.
        if (($_SERVER['HTTP_X_SOCIAL_REQUEST'] ?? '') !== '1' || ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site') throw new \Kyuubi\Social\ApiError(403, 'Ungültiger Anfrageursprung.');
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $expected = rtrim((string)getEnvVar('SOCIAL_ORIGIN', ''), '/');
        if ($origin && $expected && $origin !== $expected) throw new \Kyuubi\Social\ApiError(403, 'Ungültiger Anfrageursprung.');
    }
    $data = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') ? json_decode(file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR) : $_POST;
    if (!is_array($data)) throw new \Kyuubi\Social\ApiError(400, 'Ungültige Anfrage.');
    $result = $service->dispatch((string)($_GET['action'] ?? 'bootstrap'), $method, $data, $_GET);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (\Kyuubi\Social\ApiError $e) { if ($pdo->inTransaction()) $pdo->rollBack(); http_response_code($e->status); echo json_encode(['error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (\JsonException $e) { http_response_code(400); echo json_encode(['error'=>'Ungültige JSON-Daten.']);
} catch (\Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); error_log('Social: '.$e->getMessage()); http_response_code(500); echo json_encode(['error'=>'Die Anfrage konnte nicht verarbeitet werden.']); }
