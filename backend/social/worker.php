<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../vendor/autoload.php';
function getEnvVar($key,$default=null){return $_ENV[$key]??(getenv($key)!==false?getenv($key):$default);}
require_once __DIR__.'/Storage.php';
use Kyuubi\Social\Storage;
$db=new PDO('mysql:host='.getEnvVar('DB_HOST','db').';port='.getEnvVar('DB_PORT','3306').';dbname='.getEnvVar('DB_DATABASE','ksystems').';charset=utf8mb4',getEnvVar('DB_USERNAME','ksystems'),getEnvVar('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec("SET time_zone='+00:00'");
while(true){
 try{
  foreach($db->query('SELECT * FROM kdd_social_garbage ORDER BY created_at LIMIT 50')->fetchAll() as $garbage){
    $check=$db->prepare('SELECT 1 FROM kdd_social_media WHERE storage=? AND storage_key=?');$check->execute([$garbage['storage'],$garbage['storage_key']]);
    if(!$check->fetch())Storage::delete($garbage);
    $db->prepare('DELETE FROM kdd_social_garbage WHERE storage=? AND storage_key=?')->execute([$garbage['storage'],$garbage['storage_key']]);
  }
  // Recover a conversion whose process died beyond the ffmpeg timeout.
  $db->exec("UPDATE kdd_social_media SET state='queued' WHERE state='processing' AND claimed_at<DATE_SUB(UTC_TIMESTAMP(), INTERVAL 35 MINUTE)");
  $settings=json_decode($db->query('SELECT settings FROM kdd_social_settings WHERE id=1')->fetchColumn(),true);
  if(!filter_var(getEnvVar('SOCIAL_ENABLED','false'),FILTER_VALIDATE_BOOLEAN)||isset($settings['modules']['video'])&&!$settings['modules']['video']){if(in_array('--once',$argv))break;sleep(5);continue;}
  $db->beginTransaction();$m=$db->query("SELECT * FROM kdd_social_media WHERE state='queued' AND module='video' ORDER BY id LIMIT 1 FOR UPDATE SKIP LOCKED")->fetch();
  if(!$m){$db->commit();if(in_array('--once',$argv)){break;}sleep(3);continue;}
  // Claim the job transactionally; final UPDATE checks that it survived deletion.
  $db->prepare("UPDATE kdd_social_media SET state='processing',claimed_at=UTC_TIMESTAMP() WHERE id=?")->execute([$m['id']]);$db->commit();
  $input=Storage::local($m);$out=tempnam(sys_get_temp_dir(),'social-video-');
  $duration=(float)shell_exec('ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '.escapeshellarg($input));
  if($duration<=0||$duration>($settings['video_seconds']??600))throw new RuntimeException('Videolänge ungültig.');
  $command='timeout 1800 ffmpeg -nostdin -v error -y -i '.escapeshellarg($input).' -map 0:v:0 -map 0:a:0? -vf '.escapeshellarg('scale=min(1920\,iw):-2').' -c:v libx264 -preset veryfast -crf 24 -pix_fmt yuv420p -c:a aac -movflags +faststart -map_metadata -1 -f mp4 '.escapeshellarg($out).' 2>&1';
  exec($command,$logs,$code);if($code!==0||!is_file($out))throw new RuntimeException('Video konnte nicht konvertiert werden.');
  $key=bin2hex(random_bytes(24));$storage=Storage::put($out,$key,'video/mp4');
  $q=$db->prepare("UPDATE kdd_social_media SET state='ready',storage=?,storage_key=?,mime='video/mp4',bytes=? WHERE id=? AND state='processing'");$q->execute([$storage,$key,filesize($out),$m['id']]);
  if($q->rowCount())Storage::delete($m);else Storage::delete(['storage'=>$storage,'storage_key'=>$key]);
 }catch(Throwable $e){if($db->inTransaction())$db->rollBack();error_log('Social worker: '.$e->getMessage());if(!empty($m['id']))$db->prepare("UPDATE kdd_social_media SET state='failed' WHERE id=?")->execute([$m['id']]);}
 finally{if(isset($out)&&is_file($out))unlink($out);if(isset($m,$input)&&$m['storage']!=='local'&&is_file($input))unlink($input);unset($input,$out,$m);}
 if(in_array('--once',$argv))break;
}
