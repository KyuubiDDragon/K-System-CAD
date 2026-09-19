<?php
declare(strict_types=1);
ob_start();
if(getenv('DB_DATABASE')!=='social_test')throw new RuntimeException('Only disposable social_test allowed.');
function getEnvVar($k,$d=null){return getenv($k)!==false?getenv($k):$d;}
require __DIR__.'/../Service.php';
$db=new PDO('mysql:host='.getenv('DB_HOST').';dbname=social_test;charset=utf8mb4',getenv('DB_USERNAME'),getenv('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$a=new \Kyuubi\Social\Accounts($db);$pass='Accounts-test-password-123';$wallets=[];$profiles=[];$count=0;
function check($value,$label){global $count;if(!$value)throw new RuntimeException($label);$count++;echo "PASS $label\n";}
function deny(int $status,callable $fn,string $label){global $db;try{$fn();}catch(\Kyuubi\Social\ApiError $e){if($db->inTransaction())$db->rollBack();check($status===$e->status,$label.' ('.$e->status.')');return;}throw new RuntimeException('Expected denial: '.$label);}
function who(string $name,int $acting=0){global $wallets,$profiles;$_COOKIE=['social_wallet'=>$wallets[$name]];$_SERVER['HTTP_X_SOCIAL_ACCOUNT']=(string)$profiles[$name]['id'];$_SERVER['HTTP_X_SOCIAL_ACTING']=(string)$acting;}
function call(string $action,array $data=[],array $query=[]){global $db;$method=in_array($action,['bootstrap','accounts','companies','posts','post','messages','friends','admin','notifications'],true)?'GET':'POST';return (new \Kyuubi\Social\Service($db))->dispatch($action,$method,$data,$query);}
foreach(['owner','member','other','second','leaver'] as $name){
 $_COOKIE=[];$_SERVER['HTTP_X_SOCIAL_ACCOUNT']='0';$_SERVER['HTTP_X_SOCIAL_ACTING']='0';
 $handle='access_'.$name.'_'.bin2hex(random_bytes(3));$r=call('register',['handle'=>$handle,'password'=>$pass,'display_name'=>$name]);
 $a->exec("UPDATE kdd_social_profiles SET status='active' WHERE id=?",[$r['profile_id']]);
 $profiles[$name]=$a->one('SELECT * FROM kdd_social_profiles WHERE id=?',[$r['profile_id']]);$wallets[$name]=$_COOKIE['social_wallet'];
}
who('owner');$owner=$profiles['owner'];$member=$profiles['member'];
$private=call('save_post',['module'=>'social','body'=>'private owner text','visibility'=>'friends'])['id'];
$public=call('save_post',['module'=>'social','body'=>'public owner text','visibility'=>'public'])['id'];
$a->exec("INSERT INTO kdd_social_media(owner_id,uploaded_by,module,purpose,storage_key,mime,filename,bytes) VALUES(?,?,'social','post','private-unpublished-test','image/png','private.png',1)",[$owner['id'],$owner['id']]);$privateMedia=(int)$db->lastInsertId();
call('grant_access',['handle'=>$member['handle'],'rights'=>['posts'],'password'=>$pass]);
who('member',$owner['id']);deny(403,fn()=>call('bootstrap'),'pending invitation cannot act');
who('member');call('accept_access',['kind'=>'profile','id'=>$owner['id'],'accept'=>true]);
who('member',$owner['id']);$b=call('bootstrap');check($b['me']['delegated']&&$b['me']['role']==='member','delegation shows actor and never inherits administrator');
check(!isset($b['me']['signature'])&&!isset($b['me']['recovery_hash']),'delegated bootstrap omits private fields');
deny(403,fn()=>call('messages'),'delegation cannot read inbox');deny(403,fn()=>call('friends'),'delegation cannot read friends');
deny(403,fn()=>call('grant_access',['handle'=>$profiles['other']['handle'],'rights'=>['posts'],'password'=>$pass]),'delegate cannot grant access');
deny(403,fn()=>call('admin'),'delegate cannot use administration');
deny(404,fn()=>call('post',[],['id'=>$private]),'delegate cannot read private owner posts');
deny(403,fn()=>call('save_profile',['display_name'=>'forged']),'post right does not imply profile edit');
deny(403,fn()=>call('save_post',['module'=>'social','body'=>'leak','visibility'=>'public','media'=>[$privateMedia]]),'delegate cannot publish owner unpublished uploads');
call('save_post',['module'=>'social','body'=>'delegated publication','visibility'=>'public']);check(true,'accepted delegated public publication');
who('owner');deny(403,fn()=>call('revoke_access',['handle'=>$member['handle'],'password'=>'wrong']),'revocation requires current owner password');
call('revoke_access',['handle'=>$member['handle'],'password'=>$pass]);
who('member',$owner['id']);deny(403,fn()=>call('save_post',['module'=>'social','body'=>'stale','visibility'=>'public']),'open delegated context loses write access after revocation');
who('member');check(call('bootstrap')['me']['id']==$member['id'],'private member account unaffected');
// Separate browser wallet: own credentials are required, a profile selector is insufficient.
$_SERVER['HTTP_X_SOCIAL_ACCOUNT']=(string)$owner['id'];deny(401,fn()=>call('bootstrap'),'foreign profile ID cannot select unsaved account');
who('member');$r=call('login',['handle'=>$profiles['second']['handle'],'password'=>$pass]);
$wallets['second_saved']=$wallets['member'];$profiles['second_saved']=$profiles['second'];
check(count(call('accounts')['own'])===2,'two independently authenticated accounts saved');
who('second_saved');check(call('bootstrap')['me']['id']==$profiles['second']['id'],'saved account can be selected per window');
who('member');check(call('bootstrap')['me']['id']==$member['id'],'other window retains its chosen identity');
call('forget_account',['id'=>$profiles['second']['id']]);who('second_saved');deny(401,fn()=>call('bootstrap'),'forgotten wallet reference cannot be reused');
// Company access and independent memberships.
who('owner');$a->exec("UPDATE kdd_social_profiles SET role='admin' WHERE id=?",[$owner['id']]);
$c1=call('save_company',['name'=>'Access Company A'])['id'];$c2=call('save_company',['name'=>'Access Company B'])['id'];
foreach([$c1,$c2] as $c)call('grant_access',['kind'=>'company','id'=>$c,'handle'=>$member['handle'],'rights'=>['posts'],'password'=>$pass]);
who('member');foreach([$c1,$c2] as $c)call('accept_access',['kind'=>'company','id'=>$c,'accept'=>true]);
check(count(call('accounts')['companies'])===2,'multiple company memberships retained');
deny(403,fn()=>call('company_open',['id'=>$c1,'minutes'=>60]),'posting membership cannot change opening status');
$cp=call('save_post',['module'=>'social','body'=>'company','visibility'=>'public','company_id'=>$c1])['id'];
who('owner');call('save_post',['id'=>$cp,'module'=>'social','body'=>'owner edits company post','visibility'=>'public']);check(true,'company owner can manage employee company posts');
call('revoke_access',['kind'=>'company','id'=>$c1,'handle'=>$member['handle'],'password'=>$pass]);
who('member');deny(403,fn()=>call('save_post',['module'=>'social','body'=>'stale company','visibility'=>'public','company_id'=>$c1]),'company revocation enforced on new posts');
deny(403,fn()=>call('save_post',['id'=>$cp,'module'=>'social','body'=>'stale edit','visibility'=>'public']),'omitting company ID cannot edit after revocation');
deny(403,fn()=>call('delete_post',['id'=>$cp]),'revoked member cannot delete company post');
call('save_post',['module'=>'social','body'=>'still allowed','visibility'=>'public','company_id'=>$c2]);check(true,'other company unaffected');
deny(403,fn()=>call('revoke_access',['kind'=>'company','id'=>$c2,'handle'=>$owner['handle'],'password'=>$pass]),'member cannot remove main owner');
who('owner');call('transfer_owner',['kind'=>'company','id'=>$c2,'handle'=>$member['handle'],'password'=>$pass]);
check((int)$a->one('SELECT owner_id FROM kdd_social_companies WHERE id=?',[$c2])['owner_id']===(int)$owner['id'],'transfer preserves owner until accepted');
who('member');call('accept_owner',['id'=>$c2,'password'=>$pass]);
check((int)$a->one('SELECT owner_id FROM kdd_social_companies WHERE id=?',[$c2])['owner_id']===(int)$member['id'],'new owner explicitly accepts');
who('owner');deny(403,fn()=>call('grant_access',['kind'=>'company','id'=>$c2,'handle'=>$profiles['other']['handle'],'rights'=>['posts'],'password'=>$pass]),'former owner cannot grant access');
who('owner');call('grant_access',['kind'=>'company','id'=>$c1,'handle'=>$profiles['leaver']['handle'],'rights'=>['posts'],'password'=>$pass]);
who('leaver');call('accept_access',['kind'=>'company','id'=>$c1,'accept'=>true]);$retained=call('save_post',['module'=>'social','body'=>'company record','visibility'=>'public','company_id'=>$c1])['id'];
who('owner');call('revoke_access',['kind'=>'company','id'=>$c1,'handle'=>$profiles['leaver']['handle'],'password'=>$pass]);
who('leaver');call('delete_account',['password'=>$pass,'confirm'=>'DELETE']);$_SERVER['HTTP_X_SOCIAL_ACCOUNT']='-1';
check(call('post',[],['id'=>$retained])['post']['company_id']==$c1,'personal account deletion cannot erase former company records');
// CAD handshake: bind current session, single use, state, and persistent link.
putenv('SOCIAL_ORIGIN=https://social.test.invalid');$bridge=new \Kyuubi\Social\Bridge($db);
$cad=['id'=>(int)$profiles['other']['user_id'],'authority_id'=>(int)$profiles['other']['authority_id']];$sh=hash('sha256',random_bytes(30));
$a->exec("INSERT INTO kdd_sessions(user_id,authority_id,token_hash,jti,expires_at,is_active) VALUES(?,?,?,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 HOUR),1)",[$cad['id'],$cad['authority_id'],$sh,bin2hex(random_bytes(20))]);
$state=bin2hex(random_bytes(20));$code=$bridge->cad('issue',$cad,$sh,['purpose'=>'link','state'=>$state]);
deny(403,fn()=>$bridge->consume('bridge_link',['code'=>$code['code'],'state'=>'wrong','password'=>$pass],$owner),'bridge state mismatch denied');
putenv('SOCIAL_ORIGIN=https://wrong.test.invalid');deny(403,fn()=>$bridge->consume('bridge_link',['code'=>$code['code'],'state'=>$state,'password'=>$pass],$owner),'bridge target origin mismatch denied');putenv('SOCIAL_ORIGIN=https://social.test.invalid');
$bridge->consume('bridge_link',['code'=>$code['code'],'state'=>$state,'password'=>$pass],$owner);
deny(403,fn()=>$bridge->consume('bridge_link',['code'=>$code['code'],'state'=>$state,'password'=>$pass],$owner),'bridge code cannot be replayed');
$extra=$bridge->cad('issue',$cad,$sh,['purpose'=>'link','state'=>$state]);$bridge->consume('bridge_link',['code'=>$extra['code'],'state'=>$state,'password'=>$pass],$profiles['second']);check(count($bridge->cad('list',$cad,$sh,[])['items'])===2,'multiple Social accounts linked to one CAD account');
$link=$bridge->cad('list',$cad,$sh,[])['items'][0];$code=$bridge->cad('issue',$cad,$sh,['purpose'=>'login','state'=>$state,'id'=>$link['id']]);
$_COOKIE=[];$_SERVER['HTTP_X_SOCIAL_ACCOUNT']='-1';$_SERVER['HTTP_X_SOCIAL_ACTING']='0';
$r=call('bridge_login',['code'=>$code['code'],'state'=>$state]);check($r['profile_id']==$owner['id'],'CAD code opens linked Social identity');
$_SERVER['HTTP_X_SOCIAL_ACCOUNT']=(string)$owner['id'];check(call('bootstrap')['me']['id']==$owner['id'],'linked session usable');
$a->exec('UPDATE kdd_sessions SET is_active=0 WHERE token_hash=?',[$sh]);deny(401,fn()=>call('bootstrap'),'CAD logout invalidates derived Social session');
who('owner');check(call('bootstrap')['me']['id']==$owner['id'],'independent manual Social login survives CAD logout');
$a->exec('UPDATE kdd_sessions SET is_active=1 WHERE token_hash=?',[$sh]);
$code=$bridge->cad('issue',$cad,$sh,['purpose'=>'login','state'=>$state,'id'=>$link['id']]);call('unlink_cad',['id'=>$link['id'],'password'=>$pass]);
deny(403,fn()=>$bridge->consume('bridge_login',['code'=>$code['code'],'state'=>$state],null),'unlink invalidates outstanding login code');
// Expired codes and inactive CAD sessions fail closed.
$expired=$bridge->cad('issue',$cad,$sh,['purpose'=>'link','state'=>$state]);$a->exec('UPDATE kdd_social_bridge_codes SET expires_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 SECOND) WHERE token_hash=?',[hash('sha256',$expired['code'])]);deny(403,fn()=>$bridge->consume('bridge_link',['code'=>$expired['code'],'state'=>$state,'password'=>$pass],$owner),'expired bridge code denied');
$a->exec('UPDATE kdd_sessions SET is_active=0 WHERE token_hash=?',[$sh]);deny(401,fn()=>$bridge->cad('list',$cad,$sh,[]),'revoked CAD session cannot list links');
require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../../jwt.php';
require_once __DIR__.'/../../utils/session_manager.php';
$cadUser=$a->one('SELECT * FROM kdd_users WHERE id=?',[$cad['id']]);
$a->exec('DELETE FROM kdd_social_cad_links WHERE cad_user_id=?',[$cad['id']]);
$cadToken=create_jwt($cadUser,$db);saveSession($db,$cad['id'],$cad['authority_id'],$cadToken,get_jti_from_jwt($cadToken),time()+900);
file_put_contents('/tmp/accounts-cad-token',$cadToken);
file_put_contents('/tmp/accounts-test-fixture',json_encode(['owner'=>$profiles['owner'],'member'=>$profiles['member'],'second'=>$profiles['second'],'password'=>$pass]));
echo "$count account and access checks passed.\n";
ob_end_flush();
