<?php
declare(strict_types=1);
if(getenv('DB_DATABASE')!=='social_test')throw new RuntimeException('Only the disposable social_test database is allowed.');
require __DIR__.'/../../laws/Service.php';
require __DIR__.'/../../helpers/RoleGuard.php';
$pdo=new PDO('mysql:host='.getenv('DB_HOST').';dbname=social_test;charset=utf8mb4',getenv('DB_USERNAME'),getenv('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
function sql(string $s,array $a=[]): PDOStatement {global $pdo;$q=$pdo->prepare($s);$q->execute($a);return $q;}
function check(bool $v,string $message): void {if(!$v)throw new RuntimeException($message);echo "PASS $message\n";}
function denies(int $status,callable $fn,string $message): void {try{$fn();}catch(\Kyuubi\Laws\Error $e){check($e->status===$status,$message);return;}throw new RuntimeException('Expected denial: '.$message);}
function actor(string $name,array $perms): array {
 global $pdo;
 sql('INSERT INTO kdd_authorities(name,display_name,authority_type) VALUES(?,?,?)',['laws_'.$name,'Gesetze-Test '.$name,'government']);$authority=(int)$pdo->lastInsertId();
 sql('INSERT INTO kdd_users(username,email,password,authority,authority_id) VALUES(?,?,?,?,?)',['laws_'.$name,'laws_'.$name.'@test.invalid',password_hash(bin2hex(random_bytes(20)),PASSWORD_DEFAULT),'laws_'.$name,$authority]);$id=(int)$pdo->lastInsertId();
 sql('INSERT INTO kdd_roles(name,authority_id) VALUES(?,?)',['laws_'.$name,$authority]);$role=(int)$pdo->lastInsertId();sql('INSERT INTO kdd_user_roles(user_id,role_id,authority_id) VALUES(?,?,?)',[$id,$role,$authority]);
 foreach($perms as $p)sql('INSERT INTO kdd_role_permissions(role_id,permission_id,authority_id) SELECT ?,id,? FROM kdd_permissions WHERE name=?',[$role,$authority,$p]);
 return ['id'=>$id,'authority_id'=>$authority,'role'=>$role,'username'=>'laws_'.$name,'authority'=>'laws_'.$name];
}
$root=actor('root',['ALL_PERMISSIONS']);
check((int)sql("SELECT COUNT(*) FROM kdd_role_permissions rp JOIN kdd_permissions p ON p.id=rp.permission_id WHERE rp.role_id=? AND p.module='system'",[$root['role']])->fetchColumn()>0,'fixture has actual system grant');
$writer=actor('writer',['READ_LAWS_DRAFTS','WRITE_LAWS_DRAFTS','ADMIN_WRITE_USERS']);
$publisher=actor('publisher',['READ_LAWS_DRAFTS','WRITE_LAWS_PUBLICATION','WRITE_LAWS_REPEAL']);
$outsider=actor('outsider',['READ_LAWS_DRAFTS','WRITE_LAWS_DRAFTS','WRITE_LAWS_PUBLICATION']);
function service(?array $who=null,bool $social=false): \Kyuubi\Laws\Service {global $pdo;return new \Kyuubi\Laws\Service($pdo,$who,$social);}
$book=service($root)->dispatch('save_book','POST',['title'=>'Testgesetzbuch','description'=>'Prüfung'])['id'];
check(!service()->dispatch('books','GET')['items'],'new books start unpublished');
denies(404,fn()=>service()->dispatch('book','GET',[],['id'=>$book]),'unpublished book direct access denied');
service($root)->dispatch('book_visibility','POST',['id'=>$book,'revision'=>1,'published'=>true]);
$draft=['book_id'=>$book,'number'=>'1','title'=>'Erste Fassung','chapter'=>'Allgemeines','body'=>'Öffentlicher Text','reason'=>'Erstanlage','subsections'=>[['number'=>'1','title'=>'Sorgfalt','body'=>'Besondere Vorsicht im Verkehr']],'amount_kind'=>'fine','amount_min'=>'125.50','amount_max'=>'500'];
denies(403,fn()=>service($writer)->dispatch('save_draft','POST',$draft),'role alone does not grant app access');
foreach([$writer,$publisher] as $a)service($root)->dispatch('grant','POST',['authority_id'=>$a['authority_id'],'draft_read'=>true,'edit'=>true,'publish'=>true,'repeal'=>true]);
$second=service($writer)->dispatch('save_book','POST',['title'=>'Zweites Gesetzbuch','description'=>'App-Zuständigkeit'])['id'];
check(service($writer)->rights($second)['edit'],'app grant covers all books, including new books');
denies(403,fn()=>service($writer)->dispatch('book_visibility','POST',['id'=>$second,'revision'=>1,'published'=>true]),'book visibility requires publishing permission');
denies(403,fn()=>service($writer)->dispatch('grant','POST',['authority_id'=>$outsider['authority_id'],'edit'=>true]),'assigned editor cannot distribute app access');
check(service($root)->rights($second)['publish'],'system has full app access without assignment');
$id=service($writer)->dispatch('save_draft','POST',$draft)['id'];
$view=fn($who)=>service($who)->dispatch('book','GET',[],['id'=>$book]);
$article=$view($writer)['articles'][0];
check(!$article['current']&&$article['draft']['title']==='Erste Fassung','new law starts as draft');
check($article['draft']['subsections'][0]['number']==='1'&&(float)$article['draft']['amount_min']===125.50,'structured draft and RP amount round trip');
denies(422,fn()=>service($writer)->dispatch('save_draft','POST',[...$draft,'article_id'=>$id,'revision'=>$article['revision'],'amount_max'=>'100']),'inverted amount range rejected');
denies(422,fn()=>service($writer)->dispatch('save_draft','POST',[...$draft,'article_id'=>$id,'revision'=>$article['revision'],'subsections'=>[$draft['subsections'][0],$draft['subsections'][0]]]),'duplicate subsection numbers rejected');
check(count($view($outsider)['articles'])===0,'other faction cannot discover draft');
denies(404,fn()=>service($outsider)->dispatch('history','GET',[],['id'=>$id]),'draft history does not expose article');
denies(403,fn()=>service($writer)->dispatch('publish','POST',['article_id'=>$id,'revision'=>$article['revision']]),'editing does not imply publication');
denies(403,fn()=>service($publisher)->dispatch('save_draft','POST',[...$draft,'article_id'=>$id,'revision'=>$article['revision']]),'publication does not imply editing');
service($publisher)->dispatch('publish','POST',['article_id'=>$id,'revision'=>$article['revision']]);
check($view($outsider)['articles'][0]['current']['title']==='Erste Fassung','published law is shared across factions');
service($root)->dispatch('book_visibility','POST',['id'=>$book,'revision'=>2,'published'=>false]);
denies(404,fn()=>service()->dispatch('history','GET',[],['id'=>$id]),'withdrawn book history is not public');
check(!service()->dispatch('search','GET',[],['q'=>'Öffentlicher Text'])['items'],'withdrawn books disappear from search');
service($root)->dispatch('book_visibility','POST',['id'=>$book,'revision'=>3,'published'=>true]);
$a=$view($writer)['articles'][0];service($writer)->dispatch('save_draft','POST',[...$draft,'article_id'=>$id,'revision'=>$a['revision'],'title'=>'Neue Fassung','body'=>'Entwurf geheim']);
check($view($outsider)['articles'][0]['draft']===null&&$view($outsider)['articles'][0]['current']['body']==='Öffentlicher Text','editing preserves current published text');
check(!service($outsider)->dispatch('book','GET',[],['id'=>$book,'q'=>'Entwurf geheim'])['articles'],'search does not leak drafts');
check(service($root)->dispatch('book','GET',[],['id'=>$book,'view'=>'reader'])['articles'][0]['draft']===null,'reader hides drafts even for system user');
check(!service($root)->dispatch('search','GET',[],['q'=>'Entwurf geheim','view'=>'reader'])['items'],'system reader search excludes drafts');
check(count(service()->dispatch('search','GET',[],['q'=>'Entwurf geheim'])['items'])===0,'global search does not expose unpublished text');
denies(409,fn()=>service($writer)->dispatch('save_draft','POST',[...$draft,'article_id'=>$id,'revision'=>$a['revision']]),'stale draft update is rejected');
$a=$view($publisher)['articles'][0];service($publisher)->dispatch('publish','POST',['article_id'=>$id,'revision'=>$a['revision']]);
check(count(service($outsider)->dispatch('history','GET',[],['id'=>$id])['items'])===2,'published versions remain in history');
check((float)service($outsider)->dispatch('history','GET',[],['id'=>$id])['items'][1]['amount_min']===125.50,'historical amounts preserved');
check(count(service()->dispatch('search','GET',[],['q'=>'Besondere Vorsicht'])['items'])===1,'global search finds subsection text');
$a=$view($publisher)['articles'][0];service($publisher)->dispatch('repeal','POST',['article_id'=>$id,'revision'=>$a['revision'],'reason'=>'Aufgehoben']);
check((bool)$view($outsider)['articles'][0]['current']['repealed_at'],'repeal is explicit and does not restore older version');
$futureDraft=[...$draft,'number'=>'2','title'=>'Zukünftige Regel'];
$futureId=service($root)->dispatch('save_draft','POST',$futureDraft)['id'];
$futureArticle=array_values(array_filter($view($root)['articles'],fn($v)=>$v['id']===$futureId))[0];
service($root)->dispatch('publish','POST',['article_id'=>$futureId,'revision'=>$futureArticle['revision'],'effective_at'=>gmdate('Y-m-d\TH:i:s\Z',time()+3600)]);
$scheduled=array_values(array_filter($view($outsider)['articles'],fn($v)=>$v['id']===$futureId))[0];
check($scheduled['current']===null&&$scheduled['scheduled']['title']==='Zukünftige Regel','future publication is announced but not yet in force');
check(service()->dispatch('bootstrap','GET')['can_read'],'laws public by default');
check(count(service()->dispatch('search','GET',[],['q'=>'Öffentlicher Text'])['items'])>0,'global search finds published content');
$settings=service($root)->dispatch('bootstrap','GET');service($root)->dispatch('settings','POST',['enabled'=>true,'guest'=>false,'revision'=>$settings['revision']]);
$settings=service($root)->dispatch('bootstrap','GET');denies(401,fn()=>service()->dispatch('books','GET'),'guest access disabled independently');
service($root)->dispatch('settings','POST',['enabled'=>true,'guest'=>true,'revision'=>$settings['revision']]);
check(count(service()->dispatch('books','GET')['items'])===1,'law guest access can be enabled');
denies(403,fn()=>service(null,true)->dispatch('settings','POST',['enabled'=>true,'guest'=>true,'revision'=>2]),'Social session cannot administer laws');
service($root)->dispatch('grant','POST',['authority_id'=>$writer['authority_id']]);
check(!service($writer)->rights($book)['edit'],'revoked jurisdiction takes effect immediately');
service($root)->dispatch('grant','POST',['authority_id'=>$writer['authority_id'],'edit'=>true]);
sql("DELETE rp FROM kdd_role_permissions rp JOIN kdd_permissions p ON p.id=rp.permission_id WHERE rp.role_id=? AND p.name='WRITE_LAWS_DRAFTS'",[$writer['role']]);
check(!service($writer)->rights($book)['edit'],'revoked CAD role permission takes effect immediately');
try {RoleGuard::validate($pdo,$root['id'],$root['authority_id'],$writer['role'],[],[]);throw new RuntimeException('cross-tenant role accepted');}catch(DomainException $e){check(true,'role management rejects foreign tenant even for admin');}
$systemId=(int)sql("SELECT id FROM kdd_permissions WHERE module='system' LIMIT 1")->fetchColumn();
try {RoleGuard::validate($pdo,$writer['id'],$writer['authority_id'],null,[$systemId],[]);throw new RuntimeException('escalation accepted');}catch(DomainException $e){check(true,'role administrator cannot grant system permission');}
$settings=service($root)->dispatch('bootstrap','GET');service($root)->dispatch('settings','POST',['enabled'=>false,'guest'=>true,'revision'=>$settings['revision']]);
denies(404,fn()=>service()->dispatch('book','GET',[],['id'=>$book]),'disabled module rejects direct links');
$settings=service($root)->dispatch('bootstrap','GET');service($root)->dispatch('settings','POST',['enabled'=>true,'guest'=>true,'revision'=>$settings['revision']]);
// A real, short-lived CAD session for the subsequent browser test. Never printed.
require __DIR__.'/../../vendor/autoload.php';
require __DIR__.'/../../jwt.php';
require __DIR__.'/../../utils/session_manager.php';
$token=create_jwt($root,$pdo);saveSession($pdo,$root['id'],$root['authority_id'],$token,get_jti_from_jwt($token),time()+900);
function httpCall(string $url,array $data,string $token): array {
    $context=stream_context_create(['http'=>['method'=>'POST','ignore_errors'=>true,'header'=>"Content-Type: application/json\r\nX-Laws-Request: 1\r\nCookie: auth_token=".$token,"content"=>json_encode($data)]]);
    $body=file_get_contents('http://127.0.0.1'.$url,false,$context);
    preg_match('/HTTP\/\S+ (\d+)/',$http_response_header[0],$match);
    return [(int)$match[1],json_decode($body,true)];
}
[$status]=httpCall('/admin/roles/index.php?action=updateRole',['id'=>$writer['role'],'name'=>'Forbidden change','permissions'=>[]],$token);
check($status===403,'HTTP role endpoint rejects cross-tenant edit');
check(sql('SELECT name FROM kdd_roles WHERE id=?',[$writer['role']])->fetchColumn()==='laws_writer','rejected edit preserves original role');
$writerToken=create_jwt($writer,$pdo);saveSession($pdo,$writer['id'],$writer['authority_id'],$writerToken,get_jti_from_jwt($writerToken),time()+900);
[$status]=httpCall('/admin/roles/index.php?action=createRole',['name'=>'Escalation','permissions'=>[$systemId]],$writerToken);
check($status===403,'HTTP role endpoint rejects privilege escalation');
[$status]=httpCall('/social/laws.php?action=settings',['enabled'=>true,'guest'=>true,'revision'=>1],'');
check($status===401||$status===403,'unauthenticated HTTP mutation rejected');
file_put_contents('/tmp/laws-test-session',$token);chmod('/tmp/laws-test-session',0600);
echo "Laws and role integration checks complete.\n";
