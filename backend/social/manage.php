#!/usr/bin/env php
<?php
// Local CLI only: provisioning is deliberately not a public HTTP operation.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../vendor/autoload.php';
if(is_file(__DIR__.'/../.env'))Dotenv\Dotenv::createImmutable(__DIR__.'/..')->load();
function envSocial($k,$fallback=''){return $_ENV[$k]??getenv($k)?:$fallback;}
$db=new PDO('mysql:host='.envSocial('DB_HOST','db').';port='.envSocial('DB_PORT','3306').';dbname='.envSocial('DB_DATABASE','ksystems').';charset=utf8mb4',envSocial('DB_USERNAME','ksystems'),envSocial('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if(($argv[1]??'')==='admin'&&!empty($argv[2])){$q=$db->prepare("UPDATE kdd_social_profiles SET role='admin',status='active' WHERE handle=? AND status<>'deleted'");$q->execute([$argv[2]]);echo $q->rowCount()?"Administrator eingerichtet.\n":"Profil nicht gefunden oder bereits eingerichtet.\n";}
else {fwrite(STDERR,"Usage: php social/manage.php admin HANDLE\n");exit(1);}
