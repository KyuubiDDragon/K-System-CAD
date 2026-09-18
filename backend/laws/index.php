<?php
declare(strict_types=1);
require_once __DIR__.'/../bootstrap.php';
require_once __DIR__.'/Service.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    $method=$_SERVER['REQUEST_METHOD'];
    if(!in_array($method,['GET','POST'],true))throw new \Kyuubi\Laws\Error(405,'Methode nicht erlaubt.');
    if($method==='POST' && (($_SERVER['HTTP_X_LAWS_REQUEST']??'')!=='1'||($_SERVER['HTTP_SEC_FETCH_SITE']??'')==='cross-site'))throw new \Kyuubi\Laws\Error(403,'Ungültiger Anfrageursprung.');
    $actor=null;
    if(!empty($_COOKIE['auth_token'])||!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        require __DIR__.'/../auth_check.php';
        $actor=['id'=>(int)$decoded_jwt->userId,'authority_id'=>(int)$decoded_jwt->authority_id];
    }
    $social=false;
    require_once __DIR__.'/../social/Service.php';
    try {[$profile]=(new \Kyuubi\Social\Accounts($pdo))->resolve();$social=$profile&&$profile['status']==='active';}
    catch(\Kyuubi\Social\ApiError $e){$social=false;}
    $service=new \Kyuubi\Laws\Service($pdo,$actor,$social);
    $data=$method==='POST'?json_decode(file_get_contents('php://input'),true,32,JSON_THROW_ON_ERROR):[];
    if(!is_array($data))throw new \Kyuubi\Laws\Error(400,'Ungültige Anfrage.');
    echo json_encode($service->dispatch((string)($_GET['action']??'bootstrap'),$method,$data,$_GET),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
} catch(\Kyuubi\Laws\Error $e) { http_response_code($e->status);echo json_encode(['error'=>$e->getMessage()]);
} catch(\JsonException $e) { http_response_code(400);echo json_encode(['error'=>'Ungültige JSON-Daten.']);
} catch(\Throwable $e) { if($pdo->inTransaction())$pdo->rollBack();error_log('Laws: '.$e->getMessage());http_response_code(500);echo json_encode(['error'=>'Die Anfrage konnte nicht verarbeitet werden.']); }
