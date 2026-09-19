<?php
declare(strict_types=1);
require_once __DIR__.'/../bootstrap.php';
require_once __DIR__.'/Service.php';
require_once __DIR__.'/Bridge.php';
header('Cache-Control: no-store');
try {
 if(!filter_var(getEnvVar('SOCIAL_ENABLED','false'),FILTER_VALIDATE_BOOLEAN))throw new \Kyuubi\Social\ApiError(404,'Social ist deaktiviert.');
 require __DIR__.'/../auth_check.php';
 $method=$_SERVER['REQUEST_METHOD'];$action=(string)($_GET['action']??'list');
 if(!in_array($method,['GET','POST'],true)||($method==='GET'&&!in_array($action,['list','company_mapping'],true)))throw new \Kyuubi\Social\ApiError(405,'Methode nicht erlaubt.');
 if($method==='POST' && (($_SERVER['HTTP_X_SOCIAL_CAD_REQUEST']??'')!=='1'||($_SERVER['HTTP_SEC_FETCH_SITE']??'')==='cross-site'||(!empty($_SERVER['HTTP_ORIGIN'])&&$_SERVER['HTTP_ORIGIN']!==rtrim((string)getEnvVar('FRONTEND_URL_PROD',''),'/'))))throw new \Kyuubi\Social\ApiError(403,'Ungültiger Anfrageursprung.');
 $d=$method==='POST'?json_decode(file_get_contents('php://input'),true,32,JSON_THROW_ON_ERROR):[];
 if(!is_array($d))throw new \Kyuubi\Social\ApiError(400,'Ungültige Anfrage.');
 if($method==='POST'){$lock=$pdo->query("SELECT GET_LOCK('social_access',10)")->fetchColumn();if(!$lock)throw new \Kyuubi\Social\ApiError(503,'Bitte erneut versuchen.');}
 $bridge=new \Kyuubi\Social\Bridge($pdo);
 echo json_encode($bridge->cad($action,['id'=>(int)$decoded_jwt->userId,'authority_id'=>(int)$decoded_jwt->authority_id],hash('sha256',$jwt),$d));
} catch(\Kyuubi\Social\ApiError $e){if($pdo->inTransaction())$pdo->rollBack();http_response_code($e->status);echo json_encode(['error'=>$e->getMessage()]);}
catch(\JsonException $e){http_response_code(400);echo json_encode(['error'=>'Ungültige JSON-Daten.']);}
catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('Social bridge: '.$e->getMessage());http_response_code(500);echo json_encode(['error'=>'Verbindung konnte nicht verarbeitet werden.']);}
finally {if(!empty($lock))$pdo->query("SELECT RELEASE_LOCK('social_access')");}
