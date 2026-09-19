<?php
declare(strict_types=1);
namespace Kyuubi\Social;
require_once __DIR__.'/Policy.php';
require_once __DIR__.'/Accounts.php';
require_once __DIR__.'/Bridge.php';
final class ApiError extends \RuntimeException { public function __construct(public int $status, string $message, public ?string $reason=null) { parent::__construct($message); } }
final class Service {
    private ?array $me=null;
    private array $settings;
    private string $sessionHash='';
    private Accounts $accounts;
    private ?array $actor=null;
    private array $delegatedRights=[];
    public function __construct(private \PDO $db) {
        $db->exec("SET time_zone = '+00:00'");
        $this->settings=array_replace(Policy::defaults(), json_decode($this->one('SELECT settings FROM kdd_social_settings WHERE id=1')['settings'] ?? '{}',true));
        $this->accounts=new Accounts($db);
        [$this->me,$this->sessionHash]=$this->accounts->resolve();
        $this->actor=$this->me;
        $acting=(int)($_SERVER['HTTP_X_SOCIAL_ACTING']??0);
        if($acting && $acting!==$this->id()){
            $this->auth();$this->delegatedRights=$this->accounts->delegation($acting,$this->id());
            $this->me=$this->profile($acting);
        }
        if($this->me)$this->exec('UPDATE kdd_social_profiles SET last_seen=UTC_TIMESTAMP() WHERE id=?',[$this->id()]);
        if ($this->me && $this->me['status']==='deleted') $this->me=null;
        if ($this->me && $this->me['suspended_until'] && $this->me['suspended_until']<=gmdate('Y-m-d H:i:s')) { $this->exec("UPDATE kdd_social_profiles SET status='active',suspended_until=NULL WHERE id=?",[$this->id()]); $this->me['status']='active'; }
    }
    private function all(string $sql,array $args=[]):array { $q=$this->db->prepare($sql);$q->execute($args);return $q->fetchAll(\PDO::FETCH_ASSOC); }
    private function one(string $sql,array $args=[]):?array { return $this->all($sql,$args)[0]??null; }
    private function exec(string $sql,array $args=[]):int { $q=$this->db->prepare($sql);$q->execute($args);return $q->rowCount(); }
    private function insert(string $sql,array $args=[]):int { $this->exec($sql,$args);return (int)$this->db->lastInsertId(); }
    private function id():int { return (int)($this->me['id']??0); }
    private function fail(int $status,string $message):never { throw new ApiError($status,$message); }
    private function text(array $d,string $key,int $max,bool $required=false):string { $s=trim((string)($d[$key]??''));if(mb_strlen($s)>$max || ($required && $s==='')) $this->fail(422,'Bitte '.$key.' prüfen (max. '.$max.' Zeichen).');return $s; }
    private function enum(mixed $v,array $values):string { if(!is_string($v)||!in_array($v,$values,true))$this->fail(422,'Ungültiger Wert.');return $v; }
    private function module(string $module):void { if(empty($this->settings['modules'][$module]))$this->fail(404,'Dieses Modul ist deaktiviert.'); }
    private function auth(bool $active=true):void { if(!$this->me)$this->fail(401,'Bitte anmelden.');if($active && $this->me['status']!=='active')$this->fail(403,'Dein Konto ist noch nicht freigegeben oder gesperrt.'); }
    private function role(string $role):void { $this->auth();if(!$this->can($role))$this->fail(403,'Diese Berechtigung fehlt.'); }
    private function can(string $role):bool { return !$this->delegatedRights && (($this->me['role']??'')==='admin'||($role==='moderator'&&($this->me['role']??'')==='moderator')||($role==='advertiser'&&($this->me['role']??'')==='advertiser')); }
    private function rate(string $bucket,int $limit,int $seconds):void {
        $key=hash('sha256',$bucket);$this->exec('INSERT INTO kdd_social_limits(bucket,hits,expires_at) VALUES(?,1,DATE_ADD(UTC_TIMESTAMP(),INTERVAL ? SECOND)) ON DUPLICATE KEY UPDATE hits=IF(expires_at<UTC_TIMESTAMP(),1,hits+1),expires_at=IF(expires_at<UTC_TIMESTAMP(),VALUES(expires_at),expires_at)',[$key,$seconds]);
        if((int)$this->one('SELECT hits FROM kdd_social_limits WHERE bucket=?',[$key])['hits']>$limit)$this->fail(429,'Zu viele Versuche. Bitte später erneut versuchen.');
    }
    private function notice(int $id,string $body,string $target=''):void { $prefs=json_decode($this->one('SELECT preferences FROM kdd_social_profiles WHERE id=?',[$id])['preferences']??'{}',true);if(isset($prefs['notifications'])&&!$prefs['notifications']&&$target!=='ads'&&$target!=='account')return; $this->exec('INSERT INTO kdd_social_notifications(profile_id,body,target) VALUES(?,?,?)',[$id,$body,$target]); }
    private function audit(string $action,array $details):void { $this->exec('INSERT INTO kdd_social_audit(actor,action,details) VALUES(?,?,?)',[(int)($this->actor['id']??$this->id()),$action,json_encode(['represented_profile'=>$this->id()]+$details,JSON_UNESCAPED_UNICODE)]); }
    private function blocked(int $a,int $b):bool { return (bool)$this->one('SELECT 1 FROM kdd_social_blocks WHERE (blocker=? AND blocked=?) OR (blocker=? AND blocked=?)',[$a,$b,$b,$a]); }
    private function friend(int $a,int $b):bool { return (bool)$this->one("SELECT 1 FROM kdd_social_friends WHERE status='accepted' AND ((sender=? AND recipient=?) OR(sender=? AND recipient=?))",[$a,$b,$b,$a]); }
    private function friends(int $id):array { return array_map('intval',array_column($this->all("SELECT IF(sender=?,recipient,sender) AS id FROM kdd_social_friends WHERE status='accepted' AND (sender=? OR recipient=?)",[$id,$id,$id]),'id')); }
    private function visible(string $v,int $owner):bool {
        if($this->delegatedRights)return $v==='public'&&!$this->blocked((int)$this->actor['id'],$owner)&&!$this->blocked($this->id(),$owner);
        if(!$this->id())return !empty($this->settings['guest'])&&$v==='public';
        return Policy::visible($v,$owner===$this->id(),$this->blocked($owner,$this->id()),$this->friend($owner,$this->id()),$v==='friends_of_friends' && count(array_intersect($this->friends($owner),$this->friends($this->id())))>0);
    }
    private function profile(int $id):array {
        $p=$this->one('SELECT * FROM kdd_social_profiles WHERE id=?',[$id]);if(!$p)$this->fail(404,'Profil nicht gefunden.');return $p;
    }
    private function publicProfile(array $p,bool $details=false):array {
        $out=array_intersect_key($p,array_flip(['id','handle','display_name']));
        $out['id']=(int)$p['id'];
        if($this->visible($p['privacy'],(int)$p['id'])) {
            $out['avatar_id']=$p['avatar_id'];
            if(!empty(json_decode($p['preferences'],true)['online']))$out['online']=$p['last_seen']&&strtotime($p['last_seen'].' UTC')>time()-180;
            if($details){ $out+=array_intersect_key($p,array_flip(['bio','cover_id','privacy','created_at']));$out['friend_count']=count($this->friends((int)$p['id'])); }
        } else $out['restricted']=true;
        return $out;
    }
    private function post(int $id,bool $review=false,array $seen=[]):array {
        if(in_array($id,$seen,true)||count($seen)>3)$this->fail(404,'Inhalt nicht verfügbar.');
        $p=$this->one('SELECT * FROM kdd_social_posts WHERE id=?',[$id]);if(!$p)$this->fail(404,'Beitrag nicht gefunden.');$this->module($p['module']);
        $author=$this->profile((int)$p['author_id']);$owner=(int)$p['author_id']===$this->id();
        if(!($review&&$this->can('moderator'))) {
            if($p['state']!=='published'&&!$owner)$this->fail(404,'Inhalt nicht verfügbar.');
            if($p['state']==='deleted'||($author['status']!=='active'&&!($p['company_id']&&$author['status']==='deleted')))$this->fail(404,'Inhalt nicht verfügbar.');
            if(!$this->visible($p['visibility'],(int)$p['author_id']))$this->fail(404,'Inhalt nicht verfügbar.');
            if($p['wall_id'] && !$this->visible($this->profile((int)$p['wall_id'])['privacy'],(int)$p['wall_id']))$this->fail(404,'Inhalt nicht verfügbar.');
        }
        $p['author']=$this->publicProfile($author);$p['id']=(int)$p['id'];
        $p['can_edit']=$this->id()>0&&($p['company_id']?(!$this->delegatedRights&&$this->accounts->companyRight((int)$p['company_id'],$this->id(),'posts')):($owner&&(!$this->delegatedRights||in_array('posts',$this->delegatedRights,true))));
        $p['media']=$this->all('SELECT id,mime,filename,state FROM kdd_social_media WHERE post_id=?',[$id]);
        $p['likes']=(int)$this->one('SELECT COUNT(*) AS n FROM kdd_social_reactions WHERE post_id=? AND value=1',[$id])['n'];
        $p['dislikes']=(int)$this->one('SELECT COUNT(*) AS n FROM kdd_social_reactions WHERE post_id=? AND value=-1',[$id])['n'];
        $p['reaction']=(int)($this->one('SELECT value FROM kdd_social_reactions WHERE post_id=? AND profile_id=?',[$id,$this->id()])['value']??0);
        $p['bookmarked']=(bool)$this->one('SELECT 1 FROM kdd_social_bookmarks WHERE profile_id=? AND post_id=?',[$this->id(),$id]);
        $p['comments']=(int)$this->one('SELECT COUNT(*) AS n FROM kdd_social_comments WHERE post_id=?',[$id])['n'];
        if($p['company_id'])$p['company']=$this->one('SELECT id,name,verified FROM kdd_social_companies WHERE id=?',[$p['company_id']]);
        if($p['shared_id']) {try{$p['shared']=$this->post((int)$p['shared_id'],false,[...$seen,$id]);}catch(ApiError $e){$p['shared']=null;}}
        return $p;
    }
    private function loginSession(array $profile,?array $bridge=null):array {
        $token=bin2hex(random_bytes(32));$this->exec('INSERT INTO kdd_social_sessions VALUES(?,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 7 DAY))',[hash('sha256',$token),$profile['id']]);
        setcookie('social_session',$token,['expires'=>time()+604800,'path'=>'/api/social/','secure'=>filter_var(\getEnvVar('COOKIE_SECURE','true'),FILTER_VALIDATE_BOOLEAN),'httponly'=>true,'samesite'=>'Strict']);
        $hash=hash('sha256',$token);
        if($bridge)$this->exec('INSERT INTO kdd_social_session_links VALUES(?,?,?)',[$hash,$bridge['link_id'],$bridge['cad_session_hash']]);
        $this->accounts->remember((int)$profile['id'],$hash);
        $this->me=$profile;return ['ok'=>true,'profile_id'=>(int)$profile['id']];
    }
    public function dispatch(string $action,string $method,array $d,array $q):array {
        $reads=['accounts','bookmarks','discovery','bootstrap','posts','post','comments','profile','people','friends','messages','notifications','companies','ads','slots','admin','media','search'];
        if(($method==='GET')!==in_array($action,$reads,true))$this->fail(405,'Methode nicht erlaubt.');
        if($method==='POST')$this->rate('write:'.($this->id()?:($_SERVER['REMOTE_ADDR']??'unknown')),180,60);
        if($this->delegatedRights){
            $allowed=['media','bootstrap','posts','post','profile','comments','discovery','companies','ads','accounts'];
            $needed=['save_post'=>'posts','delete_post'=>'posts','save_profile'=>'profile'];
            if($action==='upload'){$purpose=$_POST['purpose']??'post';$needed['upload']=$purpose==='profile'?'profile':($purpose==='post'?'posts':'forbidden');}
            if(!in_array($action,$allowed,true)&&(!isset($needed[$action])||!in_array($needed[$action],$this->delegatedRights,true)))$this->fail(403,'Diese Aktion ist für den freigegebenen Zugang nicht erlaubt.');
            if($action==='ads'&&empty($q['live']))$this->fail(403,'Werbeverwaltung gehört nicht zu diesem Zugang.');
            if($action==='save_post'&&(($d['visibility']??'public')!=='public'||!empty($d['company_id'])||!empty($d['wall_id'])))$this->fail(403,'Dieser Zugang darf nur öffentliche Beiträge dieses Profils erstellen.');
            if($action==='save_profile')$d=array_merge($this->me,array_intersect_key($d,array_flip(['display_name','bio','avatar_id','cover_id'])));
            if($method==='POST')$this->audit('delegated_'.$action,['id'=>$d['id']??null]);
        }
        if($action==='accounts')return $this->accounts->listing($this->actor);
        if($action==='bridge_login'||$action==='bridge_link'){
            $r=(new Bridge($this->db))->consume($action,$d,$this->actor);
            return $action==='bridge_login'?$this->loginSession($this->profile($r['profile_id']),$r):$r;
        }
        if(in_array($action,['transfer_owner','accept_owner','forget_account','unlink_cad','grant_access','revoke_access','accept_access','access_members'],true)){$this->auth();if(in_array($action,['transfer_owner','accept_owner','unlink_cad','grant_access','revoke_access'],true))$this->rate('account-security:'.$this->id(),40,900);return $this->accounts->action($action,$this->actor,$d);}
        if($action==='bootstrap') {
            $mine=$this->me?array_diff_key($this->me,array_flip(['recovery_hash','user_id','authority_id'])):null;
            if($mine){$mine['preferences']=json_decode($mine['preferences'],true);$mine['account_notice']=$this->one("SELECT body FROM kdd_social_notifications WHERE profile_id=? AND target='account' ORDER BY id DESC LIMIT 1",[$this->id()])['body']??null;}
            if($mine&&$this->delegatedRights){$mine=array_intersect_key($mine,array_flip(['id','handle','display_name','avatar_id','cover_id','bio','status']));$mine['role']='member';$mine['preferences']=[];$mine['delegated']=true;$mine['actor_name']=$this->actor['display_name'];$mine['rights']=$this->delegatedRights;}
            $public=$this->settings;$public['laws_enabled']=(bool)($this->one('SELECT enabled FROM kdd_law_settings WHERE id=1')['enabled']??false);$public['cad_url']=(string)\getEnvVar('FRONTEND_URL_PROD','');unset($public['accept_template'],$public['reject_template'],$public['payment_instructions']);
            return ['settings'=>$public,'me'=>$mine,'actor_id'=>$this->actor['id']??null,'revision'=>(int)$this->one('SELECT revision FROM kdd_social_settings WHERE id=1')['revision']];
        }
        if(in_array($action,['register','login','recover'],true))return $this->credentials($action,$d);
        if($this->me&&!in_array($action,['logout','appeal'],true))$this->auth();
        if($action==='media'){if($this->me)$this->auth();$this->serveMedia((int)($q['id']??0));return [];}
        if(!(in_array($action,['discovery','posts','post','profile','companies','comments'],true)||($action==='ads'&&!empty($q['live'])))||!$this->settings['guest'])$this->auth(!in_array($action,['logout','appeal'],true));
        switch($action){
            case 'logout':$this->exec('DELETE FROM kdd_social_sessions WHERE token_hash=?',[$this->sessionHash]);setcookie('social_session','',['expires'=>1,'path'=>'/api/social/','httponly'=>true,'samesite'=>'Strict']);return ['ok'=>true];
            case 'posts':return $this->feed($q);
            case 'post':return ['post'=>$this->post((int)($q['id']??0))];
            case 'save_post':return $this->savePost($d);
            case 'delete_post':$p=$this->post((int)$d['id'],true);if($p['company_id']&&!$this->accounts->companyRight((int)$p['company_id'],$this->id(),'posts')&&!$this->can('moderator'))$this->fail(403,'Unternehmenszugriff wurde entzogen.');if(!$p['can_edit'])$this->role('moderator');$this->db->beginTransaction();$this->queueMedia('post_id=?',[$p['id']]);$this->exec("UPDATE kdd_social_posts SET state='deleted' WHERE id=?",[$p['id']]);$this->db->commit();$this->audit('delete_post',['id'=>$p['id'],'reason'=>$this->text($d,'reason',1000)]);return ['ok'=>true];
            case 'bookmark':
                $id=(int)($d['id']??0);
                if(!empty($d['remove']))$this->exec('DELETE FROM kdd_social_bookmarks WHERE profile_id=? AND post_id=?',[$this->id(),$id]);
                else {$p=$this->post($id);if($p['state']!=='published')$this->fail(409,'Noch nicht veröffentlicht.');$this->exec('INSERT IGNORE INTO kdd_social_bookmarks(profile_id,post_id) VALUES(?,?)',[$this->id(),$id]);}
                return ['ok'=>true];
            case 'bookmarks':
                $rows=$this->all('SELECT post_id FROM kdd_social_bookmarks WHERE profile_id=? AND post_id<? ORDER BY post_id DESC LIMIT 100',[$this->id(),max(0,(int)($q['before']??PHP_INT_MAX))]);$items=[];
                foreach($rows as $row)try{$p=$this->post((int)$row['post_id']);if(!empty($q['hide_ad_posts'])&&$this->advertisingPost($p))continue;$items[]=$p;}catch(ApiError $e){if($e->status!==404)throw $e;}
                return ['items'=>$items,'next'=>count($rows)===100?(int)end($rows)['post_id']:null];
            case 'reaction':$p=$this->post((int)$d['id']);if($p['state']!=='published')$this->fail(409,'Noch nicht veröffentlicht.');$v=(int)($d['value']??0);if(!in_array($v,[-1,0,1],true))$this->fail(422,'Ungültige Reaktion.');if(!$v)$this->exec('DELETE FROM kdd_social_reactions WHERE post_id=? AND profile_id=?',[$p['id'],$this->id()]);else $this->exec('INSERT INTO kdd_social_reactions VALUES(?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)',[$p['id'],$this->id(),$v]);return ['post'=>$this->post($p['id'])];
            case 'comments':$this->post((int)$q['id']);$items=$this->all('SELECT * FROM kdd_social_comments WHERE post_id=? AND id>? ORDER BY id LIMIT 100',[(int)$q['id'],(int)($q['after']??0)]);return ['items'=>array_values(array_filter(array_map(function($c){if($this->blocked($this->id(),(int)$c['author_id']))return null;$c['author']=$this->publicProfile($this->profile((int)$c['author_id']));return $c;},$items)))];
            case 'comment':$p=$this->post((int)$d['id']);if($p['state']!=='published')$this->fail(409,'Noch nicht veröffentlicht.');$id=$this->insert('INSERT INTO kdd_social_comments(post_id,author_id,body) VALUES(?,?,?)',[$p['id'],$this->id(),$this->text($d,'body',4000,true)]);$this->notice((int)$p['author_id'],'Neuer Kommentar von '.$this->me['display_name'],'post/'.$p['id']);return ['id'=>$id];
            case 'delete_comment':$c=$this->one('SELECT * FROM kdd_social_comments WHERE id=?',[(int)$d['id']]);if(!$c)$this->fail(404,'Kommentar nicht gefunden.');$this->post((int)$c['post_id'],true);if((int)$c['author_id']!==$this->id())$this->role('moderator');$this->exec('DELETE FROM kdd_social_comments WHERE id=?',[$c['id']]);return ['ok'=>true];
            case 'profile':$p=$this->profile((int)($q['id']??$this->id()));if($p['status']!=='active')$this->fail(404,'Profil nicht verfügbar.');return ['profile'=>$this->publicProfile($p,true),'friend'=>$this->friend($this->id(),(int)$p['id'])];
            case 'save_profile':return $this->saveProfile($d);
            case 'search':$text=$this->text($q,'q',100,true);$found=[];foreach(['social','gram','market','video'] as $m)if(!empty($this->settings['modules'][$m]))$found=array_merge($found,$this->feed(['module'=>$m,'q'=>$text,'hide_ad_posts'=>$q['hide_ad_posts']??0])['items']);$persons=$this->all("SELECT * FROM kdd_social_profiles WHERE status='active' AND (handle LIKE ? OR display_name LIKE ?) LIMIT 30",['%'.$text.'%','%'.$text.'%']);return ['posts'=>$found,'people'=>array_values(array_map(fn($p)=>$this->publicProfile($p),array_filter($persons,fn($p)=>!$this->blocked($this->id(),(int)$p['id'])))),'companies'=>$this->all('SELECT id,name,description,verified FROM kdd_social_companies WHERE name LIKE ? LIMIT 30',['%'.$text.'%'])];
            case 'people':$search=$this->text($q,'q',80);$items=$this->all("SELECT * FROM kdd_social_profiles WHERE status='active' AND (display_name LIKE ? OR handle LIKE ?) ORDER BY display_name LIMIT 40",['%'.$search.'%','%'.$search.'%']);return ['items'=>array_values(array_map(fn($p)=>$this->publicProfile($p),array_filter($items,fn($p)=>!$this->blocked($this->id(),(int)$p['id']))))];
            case 'friends':return ['items'=>array_map(function($f){$other=(int)$f['sender']===$this->id()?(int)$f['recipient']:(int)$f['sender'];$f['profile']=$this->publicProfile($this->profile($other));return $f;},$this->all('SELECT * FROM kdd_social_friends WHERE sender=? OR recipient=?',[$this->id(),$this->id()])),'blocked'=>$this->all('SELECT p.id,p.handle,p.display_name FROM kdd_social_blocks b JOIN kdd_social_profiles p ON p.id=b.blocked WHERE b.blocker=?',[$this->id()])];
            case 'friend':return $this->friendAction($d);
            case 'block':$target=(int)$d['id'];if($target===$this->id())$this->fail(422,'Ungültiges Profil.');if(!empty($d['remove']))$this->exec('DELETE FROM kdd_social_blocks WHERE blocker=? AND blocked=?',[$this->id(),$target]);else{$this->profile($target);$this->exec('INSERT IGNORE INTO kdd_social_blocks VALUES(?,?)',[$this->id(),$target]);$this->exec('DELETE FROM kdd_social_friends WHERE(sender=? AND recipient=?) OR(sender=? AND recipient=?)',[$this->id(),$target,$target,$this->id()]);}return ['ok'=>true];
            case 'messages':return $this->messages($q);
            case 'send':return $this->send($d);
            case 'notifications':return ['items'=>$this->all('SELECT * FROM kdd_social_notifications WHERE profile_id=? ORDER BY id DESC LIMIT 100',[$this->id()])];
            case 'read_notifications':$this->exec('UPDATE kdd_social_notifications SET read_at=UTC_TIMESTAMP() WHERE profile_id=? AND id<=?',[$this->id(),(int)$d['through']]);return ['ok'=>true];
            case 'upload':return $this->upload();
            case 'discovery':
                $videos=!empty($this->settings['modules']['video'])?$this->feed(['module'=>'video','hide_ad_posts'=>$q['hide_ad_posts']??0])['items']:[];
                $videos=array_values(array_filter($videos,fn($p)=>$p['state']==='published'&&count($p['media'])>0&&!array_filter($p['media'],fn($m)=>$m['state']!=='ready')));
                $highlights=!empty($this->settings['modules']['social'])?$this->feed(['module'=>'social','hide_ad_posts'=>$q['hide_ad_posts']??0])['items']:[];
                $highlights=array_values(array_filter($highlights,fn($p)=>$p['state']==='published'));
                usort($highlights,fn($a,$b)=>($b['likes']+$b['comments'])<=>($a['likes']+$a['comments']));
                return ['video'=>$videos[0]??null,'highlights'=>array_slice($highlights,0,3),'open_companies'=>$this->all('SELECT c.id,c.name,c.description,c.verified,c.location,o.open_until FROM kdd_social_company_openings o JOIN kdd_social_companies c ON c.id=o.company_id WHERE o.open_until>UTC_TIMESTAMP() ORDER BY c.name LIMIT 12')];
            case 'company_open':
                $id=(int)($d['id']??0);
                if(!$this->accounts->companyRight($id,$this->id(),'profile'))$this->role('moderator');
                if(!$this->one('SELECT 1 FROM kdd_social_companies WHERE id=?',[$id]))$this->fail(404,'Unternehmen nicht gefunden.');
                $minutes=(int)($d['minutes']??0);if($minutes<0||$minutes>720)$this->fail(422,'Öffnungsstatus maximal zwölf Stunden.');
                if(!$minutes)$this->exec('DELETE FROM kdd_social_company_openings WHERE company_id=?',[$id]);
                else $this->exec('INSERT INTO kdd_social_company_openings(company_id,open_until,updated_by) VALUES(?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL ? MINUTE),?) ON DUPLICATE KEY UPDATE open_until=VALUES(open_until),updated_by=VALUES(updated_by)',[$id,$minutes,$this->id()]);
                return ['ok'=>true];
            case 'companies':
                $items=$this->all('SELECT c.*,(SELECT o.open_until FROM kdd_social_company_openings o WHERE o.company_id=c.id AND o.open_until>UTC_TIMESTAMP()) AS open_until FROM kdd_social_companies c ORDER BY name');
                foreach($items as &$company){
                    foreach(['posts'=>'can_post','profile'=>'can_edit','ads'=>'can_ads'] as $right=>$key)$company[$key]=!$this->delegatedRights&&$this->accounts->companyRight((int)$company['id'],$this->id(),$right);
                    $company['is_owner']=!$this->delegatedRights&&(int)$company['owner_id']===$this->id();
                    if($this->can('moderator')||$company['is_owner'])$company['members']=$this->all("SELECT p.id,p.display_name,p.handle FROM kdd_social_members m JOIN kdd_social_profiles p ON p.id=m.profile_id WHERE m.company_id=? AND m.status='active'",[$company['id']]);
                }return ['items'=>$items];
            case 'company_details':
                $id=(int)($d['id']??0);if(!$this->accounts->companyRight($id,$this->id(),'profile'))$this->role('moderator');
                $this->saveCompanyInfo($id,$d);return ['ok'=>true];
            case 'save_company':$this->role('moderator');$this->db->beginTransaction();$id=(int)($d['id']??0);$name=$this->text($d,'name',100,true);$body=$this->text($d,'description',4000);if($id)$this->exec('UPDATE kdd_social_companies SET name=?,description=? WHERE id=?',[$name,$body,$id]);else $id=$this->insert('INSERT INTO kdd_social_companies(name,description,created_by,owner_id) VALUES(?,?,?,?)',[$name,$body,$this->id(),$this->id()]);$this->saveCompanyInfo($id,$d);$verified=filter_var($d['verified']??false,FILTER_VALIDATE_BOOLEAN);$this->exec('UPDATE kdd_social_companies SET verified=?,location=?,contact=? WHERE id=?',[(int)$verified,$this->text($d,'location',200),$this->text($d,'contact',200),$id]);$this->db->commit();$this->audit('company',['id'=>$id]);return ['id'=>$id];
            case 'slots':$this->module('social');return ['items'=>$this->all('SELECT * FROM kdd_social_slots WHERE active=1 AND ends_at>UTC_TIMESTAMP() ORDER BY starts_at')];
            case 'ads':return $this->ads($q);
            case 'request_ad':return $this->requestAd($d);
            case 'save_slot':return $this->saveSlot($d);
            case 'decide_ad':return $this->decideAd($d);
            case 'report':$kind=$this->enum($d['kind']??'', ['post','message','profile']);$target=(int)$d['id'];if($kind==='post')$this->post($target);if($kind==='profile')$this->profile($target);if($kind==='message'&&!$this->one('SELECT 1 FROM kdd_social_messages WHERE id=? AND (sender=? OR recipient=?)',[$target,$this->id(),$this->id()]))$this->fail(404,'Nachricht nicht gefunden.');$id=$this->insert('INSERT INTO kdd_social_reports(reporter,kind,target_id,reason) VALUES(?,?,?,?)',[$this->id(),$kind,$target,$this->text($d,'reason',2000,true)]);return ['id'=>$id];
            case 'appeal':$id=$this->insert("INSERT INTO kdd_social_reports(reporter,kind,target_id,reason) VALUES(?,'appeal',?,?)",[$this->id(),$this->id(),$this->text($d,'reason',2000,true)]);return ['id'=>$id];
            case 'admin':return $this->admin();
            case 'moderate':return $this->moderate($d);
            case 'save_settings':return $this->saveSettings($d);
            case 'purge':return $this->purge($d);
            case 'password':return $this->changePassword($d);
            case 'delete_account':return $this->deleteAccount($d);
            default:$this->fail(404,'Unbekannte Aktion.');
        }
    }
    private function credentials(string $action,array $d):array {
        $handle=strtolower($this->text($d,'handle',40,true));
        $this->rate('auth-ip:'.($_SERVER['REMOTE_ADDR']??'local'),25,900);$this->rate('auth:'.$handle,10,900);
        $password=$this->text($d,'password',200,true);
        if($action==='register'){
            if(!preg_match('/^[a-z0-9][a-z0-9_.-]{2,39}$/D',$handle))$this->fail(422,'Benutzername: 3–40 Buchstaben, Zahlen, Punkt, Bindestrich oder Unterstrich.');
            if(strlen($password)<12)$this->fail(422,'Das Passwort benötigt mindestens 12 Zeichen.');
            if($this->one('SELECT id FROM kdd_social_profiles WHERE handle=?',[$handle]))$this->fail(409,'Benutzername bereits vergeben.');
            $display=$this->text($d,'display_name',100,true);$recovery=bin2hex(random_bytes(24));
            $this->db->beginTransaction();
            $tenant='personal_'.bin2hex(random_bytes(12));
            $authority=$this->insert("INSERT INTO kdd_authorities(name,display_name,authority_type) VALUES(?,?,'personal')",[$tenant,'Social '.$handle]);
            $uid=$this->insert('INSERT INTO kdd_users(username,email,password,authority,authority_id) VALUES(?,?,?,?,?)',[$handle,$handle.'@social.invalid',password_hash($password,PASSWORD_DEFAULT),$tenant,$authority]);
            $role=$this->insert("INSERT INTO kdd_roles(name,description,authority_id,power) VALUES('Zivilist','Persönliches Konto ohne Behördenrechte',?,0)",[$authority]);
            $this->exec('INSERT INTO kdd_user_roles(user_id,role_id,authority_id) VALUES(?,?,?)',[$uid,$role,$authority]);
            $status=$this->settings['registration']==='immediate'?'active':'pending';
            $id=$this->insert('INSERT INTO kdd_social_profiles(user_id,authority_id,handle,display_name,preferences,status,recovery_hash) VALUES(?,?,?,?,?,?,?)',[$uid,$authority,$handle,$display,json_encode(['messages'=>'friends','requests'=>'public','wall'=>'friends','default_visibility'=>'friends','online'=>false,'receipts'=>false,'theme'=>'system']),$status,password_hash($recovery,PASSWORD_DEFAULT)]);
            $this->db->commit();$this->loginSession($this->profile($id));return ['ok'=>true,'profile_id'=>$id,'recovery_code'=>$recovery];
        }
        $p=$this->one('SELECT p.*,u.password,u.banned FROM kdd_social_profiles p JOIN kdd_users u ON u.id=p.user_id WHERE p.handle=? AND p.status<>?',[$handle,'deleted']);
        $valid=$p && password_verify($action==='recover'?(string)($d['recovery_code']??''):$password,$action==='recover'?$p['recovery_hash']:$p['password']);
        if(!$valid || $p['banned'])$this->fail(401,'Anmeldedaten sind ungültig.');
        if($action==='recover'){
            if(strlen($password)<12)$this->fail(422,'Das Passwort benötigt mindestens 12 Zeichen.');
            $recovery=bin2hex(random_bytes(24));$this->db->beginTransaction();
            $this->exec('UPDATE kdd_users SET password=? WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$p['user_id']]);
            $this->exec('UPDATE kdd_social_profiles SET recovery_hash=? WHERE id=?',[password_hash($recovery,PASSWORD_DEFAULT),$p['id']]);
            $this->exec('DELETE FROM kdd_social_sessions WHERE profile_id=?',[$p['id']]);$this->db->commit();
            return ['ok'=>true,'recovery_code'=>$recovery];
        }
        return $this->loginSession($this->profile((int)$p['id']));
    }
    private function advertisingPost(array $p):bool {
        return mb_strtolower(trim((string)($p['category']??'')))==='werbung'
            || (bool)preg_match('/(?<![\p{L}\p{N}_])#werbung(?![\p{L}\p{N}_])/iu',($p['title']??'').' '.($p['body']??''))
            || (!empty($p['shared'])&&$this->advertisingPost($p['shared']));
    }
    private function feed(array $q):array {
        $module=$this->enum($q['module']??'social',['social','gram','market','video']);$this->module($module);
        $cursor=max(0,(int)($q['before']??PHP_INT_MAX));$items=[];$scanned=0;$search=$this->text($q,'q',100);
        // Overfetch with a stable descending cursor. Authorization is evaluated before serialization.
        $rows=$this->all('SELECT id,author_id,wall_id,company_id,category,price FROM kdd_social_posts WHERE module=? AND id<? AND (title LIKE ? OR body LIKE ?) ORDER BY id DESC LIMIT 200',[$module,$cursor,'%'.$search.'%','%'.$search.'%']);
        foreach($rows as $row){$cursor=(int)$row['id'];$scanned++;
            if(!empty($q['author']) && (int)$row['author_id']!==(int)$q['author']&&(int)$row['wall_id']!==(int)$q['author'])continue;
            if(!empty($q['company']) && (int)$row['company_id']!==(int)$q['company'])continue;
            if(($q['filter']??'')==='friends'&&!$this->friend($this->id(),(int)$row['author_id']))continue;
            if(!empty($q['category'])&&$row['category']!==$q['category'])continue;
            if(isset($q['min'])&&$q['min']!==''&&(float)$row['price']<(float)$q['min'])continue;
            if(isset($q['max'])&&$q['max']!==''&&(float)$row['price']>(float)$q['max'])continue;
            try{$p=$this->post((int)$row['id']);if(!empty($q['hide_ad_posts'])&&$this->advertisingPost($p))continue;$items[]=$p;}catch(ApiError $e){if($e->status!==404)throw $e;}
            if(count($items)===20)break;
        }
        $tags=[];foreach($items as $p){preg_match_all('/#[\p{L}\p{N}_]+/u',$p['body'],$matches);foreach(array_unique($matches[0]) as $tag)$tags[$tag]=($tags[$tag]??0)+1;}arsort($tags);
        return ['items'=>$items,'next'=>($scanned===200||count($items)===20)?$cursor:null,'tags'=>$tags];
    }
    private function savePost(array $d):array {
        $this->auth();$module=$this->enum($d['module']??'social',['social','gram','market','video']);$this->module($module);
        $id=(int)($d['id']??0);$old=$id?$this->post($id):null;
        if($old&&(!$old['can_edit']||$old['module']!==$module))$this->fail(403,'Nur eigene Beiträge können bearbeitet werden.');
        if($old&&!empty($old['company_id'])&&!$this->accounts->companyRight((int)$old['company_id'],$this->id(),'posts'))$this->fail(403,'Unternehmenszugriff wurde entzogen.');
        $body=$this->text($d,'body',12000);$title=$this->text($d,'title',160,$module==='video'||$module==='market');
        $visibility=$this->enum($d['visibility']??'friends',['public','friends','friends_of_friends']);
        $shared=(int)($d['shared_id']??0);if($shared){$this->module('social');$source=$this->post($shared);if($source['state']!=='published'||$source['shared_id'])$this->fail(422,'Dieser Inhalt kann nicht geteilt werden.');if($module!=='social')$this->fail(422,'Teilen ist im Social-Feed möglich.');}
        $company=(int)($old['company_id']??$d['company_id']??0);if($company&&!$this->accounts->companyRight($company,$this->id(),'posts'))$this->fail(403,'Keine Berechtigung für dieses Unternehmen.');
        $wall=(int)($d['wall_id']??0);if($wall){$target=$this->profile($wall);$prefs=json_decode($target['preferences'],true);if(!$this->visible($target['privacy'],$wall)||!$this->visible($prefs['wall']??'friends',$wall))$this->fail(403,'Du darfst nicht auf diese Pinnwand schreiben.');}
        $media=array_values(array_unique(array_map('intval',$d['media']??[])));if(count($media)>8)$this->fail(422,'Maximal acht Anhänge.');
        if(!$body&&!$media&&!$shared)$this->fail(422,'Der Beitrag ist leer.');
        if(in_array($module,['gram','video'],true)&&!$media)$this->fail(422,'Bitte eine Datei hinzufügen.');
        if($module==='video'&&count($media)!==1)$this->fail(422,'Bitte genau ein Video hochladen.');
        $price=$module==='market'?filter_var($d['price']??null,FILTER_VALIDATE_FLOAT):null;
        if($module==='market'&&($price===false||$price<0||$price>9999999999))$this->fail(422,'Preis ungültig.');
        $sale=$this->enum($d['sale_state']??'available',['available','reserved','sold']);
        $category=$this->text($d,'category',60);
        if($module!=='market'){
            $category=$category?:$this->settings['post_categories'][0];
            if(!in_array($category,$this->settings['post_categories'],true)&&$category!==($old['category']??null))$this->fail(422,'Bitte eine vorhandene Kategorie auswählen.');
        }
        $ai=filter_var($d['ai_generated']??($old['ai_generated']??false),FILTER_VALIDATE_BOOLEAN,FILTER_NULL_ON_FAILURE);
        if($ai===null)$this->fail(422,'KI-Kennzeichnung ungültig.');
        $state=$module==='video'?'pending':'published';
        $this->db->beginTransaction();
        foreach($media as $mid){$m=$this->one('SELECT * FROM kdd_social_media WHERE id=? FOR UPDATE',[$mid]);if(!$m||((int)$m['owner_id']!==$this->id()&&!($old&&$company&&(int)$m['post_id']===$id))||$m['module']!==$module||$m['purpose']!=='post'||($m['post_id']&&(int)$m['post_id']!==$id)||$m['message_id']||$m['ad_id'])$this->fail(422,'Datei kann nicht verwendet werden.');if($this->delegatedRights&&(int)$m['uploaded_by']!==(int)$this->actor['id']&&!($old&&(int)$m['post_id']===$id))$this->fail(403,'Nur selbst hochgeladene oder bereits am öffentlichen Beitrag verwendete Dateien sind freigegeben.');if($module==='video'&&!str_starts_with($m['mime'],'video/'))$this->fail(422,'Videodatei erforderlich.');if($module!=='video'&&!str_starts_with($m['mime'],'image/'))$this->fail(422,'Bilddatei erforderlich.');}
        if($id){$this->exec('UPDATE kdd_social_posts SET title=?,body=?,visibility=?,state=?,review_reason=NULL,price=?,category=?,sale_state=?,updated_at=UTC_TIMESTAMP() WHERE id=?',[$title,$body,$visibility,$state,$price,$category,$sale,$id]);$this->exec('UPDATE kdd_social_media SET post_id=NULL WHERE post_id=?',[$id]);}
        else $id=$this->insert('INSERT INTO kdd_social_posts(author_id,company_id,wall_id,module,title,body,visibility,state,price,category,sale_state,shared_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)',[$this->id(),$company?:null,$wall?:null,$module,$title,$body,$visibility,$state,$price,$category,$sale,$shared?:null]);
        $this->exec('UPDATE kdd_social_posts SET ai_generated=? WHERE id=?',[(int)$ai,$id]);
        foreach($media as $mid)$this->exec('UPDATE kdd_social_media SET post_id=? WHERE id=?',[$id,$mid]);$this->db->commit();
        if($wall&&$wall!==$this->id())$this->notice($wall,'Neuer Pinnwandeintrag','post/'.$id);
        $this->audit('save_post',['id'=>$id,'company_id'=>$company?:null]);
        return ['id'=>$id];
    }
    private function saveCompanyInfo(int $id,array $d):void {
        $old=$this->one('SELECT * FROM kdd_social_companies WHERE id=?',[$id]);if(!$old)$this->fail(404,'Unternehmen nicht gefunden.');
        $d=array_replace($old,$d);$photo=(int)($d['photo_id']??0);
        if($photo&&(int)$old['photo_id']!==$photo&&!$this->one("SELECT 1 FROM kdd_social_media WHERE id=? AND owner_id=? AND purpose='company' AND mime LIKE 'image/%'",[$photo,$this->id()]))$this->fail(422,'Unternehmensbild ungültig.');
        $x=$d['map_x'];$y=$d['map_y'];
        if(($x===null)!==($y===null))$this->fail(422,'Bitte einen vollständigen Kartenpunkt setzen.');
        if($x!==null&&(!is_numeric($x)||!is_numeric($y)||$x<0||$x>100||$y<0||$y>100))$this->fail(422,'Kartenpunkt liegt außerhalb der Karte.');
        $this->exec('UPDATE kdd_social_companies SET description=?,location=?,contact=?,services=?,service_area=?,opening_hours=?,photo_id=?,map_x=?,map_y=? WHERE id=?',[$this->text($d,'description',4000),$this->text($d,'location',200),$this->text($d,'contact',200),$this->text($d,'services',1000),$this->text($d,'service_area',500),$this->text($d,'opening_hours',1500),$photo?:null,$x,$y,$id]);
    }
    private function saveProfile(array $d):array {
        $prefs=json_decode($this->me['preferences'],true);foreach(['messages','requests','wall','default_visibility'] as $key)if(isset($d[$key]))$prefs[$key]=$this->enum($d[$key],['public','friends','friends_of_friends','nobody']);
        foreach(['online','receipts','notifications'] as $key)if(isset($d[$key]))$prefs[$key]=(bool)$d[$key];
        if(isset($d['theme']))$prefs['theme']=$this->enum($d['theme'],['light','dark','system']);
        $avatar=(int)($d['avatar_id']??$this->me['avatar_id']);$cover=(int)($d['cover_id']??$this->me['cover_id']);
        foreach([$avatar,$cover] as $mid)if($mid&&!$this->one("SELECT 1 FROM kdd_social_media WHERE id=? AND owner_id=? AND purpose='profile' AND mime LIKE 'image/%'",[$mid,$this->id()]))$this->fail(422,'Ungültiges Profilbild.');
        if($this->delegatedRights)foreach([$avatar,$cover] as $mid)if($mid&&!in_array($mid,[(int)$this->me['avatar_id'],(int)$this->me['cover_id']],true)&&!$this->one('SELECT 1 FROM kdd_social_media WHERE id=? AND uploaded_by=?',[$mid,$this->actor['id']]))$this->fail(403,'Nur selbst hochgeladene Profilbilder sind freigegeben.');
        $this->exec('UPDATE kdd_social_profiles SET display_name=?,bio=?,signature=?,privacy=?,preferences=?,avatar_id=?,cover_id=? WHERE id=?',[$this->text($d,'display_name',100,true),$this->text($d,'bio',2000),$this->text($d,'signature',2000),$this->enum($d['privacy']??'public',['public','friends','friends_of_friends']),json_encode($prefs),$avatar?:null,$cover?:null,$this->id()]);return ['ok'=>true];
    }
    private function friendAction(array $d):array {
        $other=(int)$d['id'];if($other===$this->id())$this->fail(422,'Ungültiges Profil.');$p=$this->profile($other);if($p['status']!=='active')$this->fail(404,'Profil nicht verfügbar.');if($this->blocked($this->id(),$other))$this->fail(403,'Anfrage nicht möglich.');
        $action=$this->enum($d['action']??'request',['request','accept','remove']);
        if($action==='remove')$this->exec('DELETE FROM kdd_social_friends WHERE(sender=? AND recipient=?) OR(sender=? AND recipient=?)',[$other,$this->id(),$this->id(),$other]);
        elseif($action==='accept'){$n=$this->exec("UPDATE kdd_social_friends SET status='accepted' WHERE sender=? AND recipient=? AND status='pending'",[$other,$this->id()]);if(!$n)$this->fail(404,'Anfrage nicht gefunden.');$this->notice($other,$this->me['display_name'].' hat deine Freundschaftsanfrage angenommen.','profile/'.$this->id());}
        else{$prefs=json_decode($p['preferences'],true);if(!$this->visible($prefs['requests']??'public',$other))$this->fail(403,'Dieses Profil nimmt keine Anfrage an.');if($this->one('SELECT 1 FROM kdd_social_friends WHERE(sender=? AND recipient=?) OR(sender=? AND recipient=?)',[$other,$this->id(),$this->id(),$other]))$this->fail(409,'Es besteht bereits eine Anfrage oder Freundschaft.');$this->exec('INSERT INTO kdd_social_friends(sender,recipient) VALUES(?,?)',[$this->id(),$other]);$this->notice($other,'Freundschaftsanfrage von '.$this->me['display_name'],'friends');}return ['ok'=>true];
    }
    private function messages(array $q):array {
        $this->module('messages');$other=(int)($q['with']??0);
        if(!$other){$rows=$this->all('SELECT * FROM kdd_social_messages WHERE sender=? OR recipient=? ORDER BY id DESC LIMIT 500',[$this->id(),$this->id()]);$people=[];foreach($rows as $m){$pid=(int)$m['sender']===$this->id()?(int)$m['recipient']:(int)$m['sender'];if(!isset($people[$pid])&&!$this->blocked($this->id(),$pid)){$people[$pid]=['profile'=>$this->publicProfile($this->profile($pid)),'last'=>$m];}}return ['items'=>array_values($people)];}
        if($this->blocked($this->id(),$other))$this->fail(403,'Gespräch blockiert.');
        $after=(int)($q['after']??0);$before=max(1,(int)($q['before']??PHP_INT_MAX));
        $rows=$this->all('SELECT * FROM kdd_social_messages WHERE ((sender=? AND recipient=?) OR(sender=? AND recipient=?)) AND id>? AND id<? ORDER BY id '.($after?'ASC':'DESC').' LIMIT 100',[$other,$this->id(),$this->id(),$other,$after,$before]);
        if(!$after)$rows=array_reverse($rows);
        foreach($rows as &$m){$m['media']=$this->all('SELECT id,mime,filename FROM kdd_social_media WHERE message_id=?',[$m['id']]);$prefs=json_decode($this->profile((int)$m['recipient'])['preferences'],true);if(empty($prefs['receipts']))$m['read_at']=null;}unset($m);
        if($rows)$this->exec('UPDATE kdd_social_messages SET read_at=COALESCE(read_at,UTC_TIMESTAMP()) WHERE sender=? AND recipient=? AND id<=?',[$other,$this->id(),end($rows)['id']]);
        return ['items'=>$rows,'profile'=>$this->publicProfile($this->profile($other))];
    }
    private function send(array $d):array {
        $this->module('messages');$body=$this->text($d,'body',12000,true);if(!empty($d['signature']))$body.="\n\n".$this->me['signature'];
        $recipients=array_values(array_unique(array_map('intval',$d['recipients']??[])));if(!$recipients||count($recipients)>20)$this->fail(422,'Bitte 1–20 Empfänger wählen.');
        $media=array_unique(array_map('intval',$d['media']??[]));if(count($media)>8)$this->fail(422,'Maximal acht Anhänge.');
        foreach($recipients as $other){$p=$this->profile($other);$prefs=json_decode($p['preferences'],true);if($other===$this->id()||$p['status']!=='active'||!$this->visible($prefs['messages']??'friends',$other))$this->fail(403,'Ein Empfänger erlaubt keine Nachricht.');}
        $this->db->beginTransaction();$files=[];foreach($media as $mid){$m=$this->one('SELECT * FROM kdd_social_media WHERE id=? FOR UPDATE',[$mid]);if(!$m||(int)$m['owner_id']!==$this->id()||$m['module']!=='messages'||$m['message_id']||$m['post_id']||$m['ad_id'])$this->fail(422,'Anhang nicht verfügbar.');$files[]=$m;}
        foreach($recipients as $i=>$other){$id=$this->insert('INSERT INTO kdd_social_messages(sender,recipient,body) VALUES(?,?,?)',[$this->id(),$other,$body]);foreach($files as $m){if(!$i)$this->exec('UPDATE kdd_social_media SET message_id=? WHERE id=?',[$id,$m['id']]);else $this->exec('INSERT INTO kdd_social_media(owner_id,module,purpose,storage,storage_key,mime,filename,bytes,state,message_id) VALUES(?,?,?,?,?,?,?,?,?,?)',[$this->id(),'messages','message',$m['storage'],$m['storage_key'],$m['mime'],$m['filename'],$m['bytes'],$m['state'],$id]);}$this->notice($other,'Neue Nachricht von '.$this->me['display_name'],'messages/'.$this->id());}$this->db->commit();return ['ok'=>true];
    }
    private function upload():array {
        $this->auth();$module=$this->enum($_POST['module']??'social',['social','gram','market','video','messages']);
        $purpose=$this->enum($_POST['purpose']??'post',['post','profile','branding','company','message','ad']);
        if(($purpose==='message')!==($module==='messages')||($purpose==='ad'&&$module!=='social'))$this->fail(422,'Dateizweck und Modul stimmen nicht überein.');
        if(!in_array($purpose,['profile','branding','company'],true))$this->module($module);
        if($purpose==='branding')$this->role('admin');
        $f=$_FILES['file']??null;if(!$f||$f['error']!==UPLOAD_ERR_OK)$this->fail(422,'Upload fehlgeschlagen. Dateigröße prüfen.');
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $image=in_array($mime,['image/jpeg','image/png','image/webp'],true);$video=in_array($mime,['video/mp4','video/webm','video/quicktime'],true);
        if(!$image&&!($purpose==='post'&&$module==='video'&&$video)&&!($purpose==='message'&&$module==='messages'&&in_array($mime,['application/pdf','text/plain'],true)))$this->fail(422,'Erlaubt sind JPEG, PNG, WebP, Video-Dateien bzw. PDF/Text als Nachrichtenanhang.');
        $limit=($video?$this->settings['video_mb']:$this->settings['upload_mb'])*1048576;
        if($f['size']>$limit)$this->fail(422,'Datei überschreitet das Uploadlimit.');
        $this->db->beginTransaction();$this->one('SELECT id FROM kdd_social_profiles WHERE id=? FOR UPDATE',[$this->id()]);
        $used=(int)$this->one('SELECT COALESCE(SUM(bytes),0) n FROM kdd_social_media WHERE owner_id=?',[$this->id()])['n'];if($used+$f['size']>$this->settings['quota_mb']*1048576)$this->fail(422,'Speicherlimit erreicht.');
        $path=$f['tmp_name'];$temporary=null;
        if($image){$size=getimagesize($path);if(!$size||$size[0]*$size[1]>20000000)$this->fail(422,'Bildauflösung zu groß (max. 20 Megapixel).');$img=imagecreatefromstring(file_get_contents($path));if(!$img)$this->fail(422,'Bild ungültig.');$temporary=tempnam(sys_get_temp_dir(),'social-image-');imagewebp($img,$temporary,86);imagedestroy($img);$path=$temporary;$mime='image/webp';}
        $key=bin2hex(random_bytes(24));require_once __DIR__.'/Storage.php';
        try{$storage=Storage::put($path,$key,$mime);$id=$this->insert('INSERT INTO kdd_social_media(owner_id,module,purpose,storage,storage_key,mime,filename,bytes,state) VALUES(?,?,?,?,?,?,?,?,?)',[$this->id(),$module,$purpose,$storage,$key,$mime,mb_substr(basename($f['name']),0,180),filesize($path),$video?'queued':'ready']);$this->exec('UPDATE kdd_social_media SET uploaded_by=? WHERE id=?',[(int)($this->actor['id']??$this->id()),$id]);$this->db->commit();}finally{if($temporary)unlink($temporary);}
        return ['id'=>$id,'mime'=>$mime,'state'=>$video?'queued':'ready'];
    }
    private function serveMedia(int $id):void {
        $m=$this->one('SELECT * FROM kdd_social_media WHERE id=?',[$id]);if(!$m)$this->fail(404,'Datei nicht gefunden.');if(!in_array($m['purpose'],['profile','branding','company'],true))$this->module($m['module']);
        $allowed=$m['purpose']==='company'&&($this->me||$this->settings['guest'])&&(bool)$this->one('SELECT 1 FROM kdd_social_companies WHERE photo_id=?',[$id]);
        if($m['purpose']==='branding'){$allowed=in_array($id,array_map('intval',$this->settings['icons']),true)||in_array($id,array_map('intval',[$this->settings['background_id'],$this->settings['logo_id'],$this->settings['header_id']]),true);}
        if($this->delegatedRights&&$m['purpose']==='message')$this->fail(404,'Datei nicht verfügbar.');
        if(!$this->delegatedRights&&$this->me&&$this->me['status']==='active'&&(int)$m['owner_id']===$this->id())$allowed=true;
        if($this->delegatedRights&&(int)$m['owner_id']===$this->id()){
            $right=$m['purpose']==='profile'?'profile':($m['purpose']==='post'?'posts':'');
            if(in_array($right,$this->delegatedRights,true)&&!$m['post_id']&&!$m['message_id']&&!$m['ad_id']&&(int)$m['uploaded_by']===(int)$this->actor['id'])$allowed=true;
            if($right==='profile'&&in_array('profile',$this->delegatedRights,true)&&in_array($id,[(int)$this->me['avatar_id'],(int)$this->me['cover_id']],true))$allowed=true;
        }
        if(!$allowed&&$m['post_id']){try{$this->post((int)$m['post_id']);$allowed=true;}catch(ApiError $e){}}
        if(!$allowed&&$m['message_id']&&$this->me&&$this->me['status']==='active'){$msg=$this->one('SELECT * FROM kdd_social_messages WHERE id=?',[$m['message_id']]);$allowed=$msg&&((int)$msg['sender']===$this->id()||(int)$msg['recipient']===$this->id())&&!$this->blocked((int)$msg['sender'],(int)$msg['recipient']);}
        if(!$allowed&&$m['purpose']==='profile'){$p=$this->profile((int)$m['owner_id']);$allowed=$p['status']==='active'&&((int)$p['avatar_id']===$id||(int)$p['cover_id']===$id)&&$this->visible($p['privacy'],(int)$p['id']);}
        if(!$allowed&&$m['ad_id']){$ad=$this->one('SELECT a.*,s.active FROM kdd_social_ads a JOIN kdd_social_slots s ON s.id=a.slot_id WHERE a.id=?',[$m['ad_id']]);$allowed=$ad&&$ad['state']==='accepted'&&($ad['paid']||(float)$ad['amount']===0.0)&&$ad['active']&&$ad['starts_at']<=gmdate('Y-m-d H:i:s')&&$ad['ends_at']>gmdate('Y-m-d H:i:s')&&($this->me||$this->settings['guest']);}
        if(!$allowed&&$this->me&&$this->me['status']==='active'){
            $allowed=($this->can('moderator')&&$m['post_id'])||($this->can('advertiser')&&$m['ad_id'])||($this->can('admin')&&$m['purpose']==='branding');
            // Private attachments become reviewable only after their exact message is reported.
            if($m['message_id']&&$this->can('moderator'))$allowed=(bool)$this->one("SELECT 1 FROM kdd_social_reports WHERE kind='message' AND target_id=? AND state='open'",[$m['message_id']]);
        }
        if(!$allowed||$m['state']!=='ready')$this->fail(404,'Datei nicht verfügbar.');
        require_once __DIR__.'/Storage.php';$path=Storage::local($m);if(!is_file($path))$this->fail(404,'Datei nicht gefunden.');
        $size=filesize($path);$start=0;$end=$size-1;
        header('Content-Type: '.$m['mime']);header('Accept-Ranges: bytes');header("Content-Security-Policy: sandbox; default-src 'none'");
        header('Content-Disposition: '.(str_starts_with($m['mime'],'image/')||str_starts_with($m['mime'],'video/')?'inline':'attachment').'; filename="'.rawurlencode($m['filename']).'"');
        if(isset($_SERVER['HTTP_RANGE'])){if(!preg_match('/^bytes=(\d*)-(\d*)$/D',$_SERVER['HTTP_RANGE'],$r))$this->fail(416,'Ungültiger Bereich.');if($r[1]===''){$start=max(0,$size-(int)$r[2]);}else{$start=(int)$r[1];if($r[2]!=='')$end=min($end,(int)$r[2]);}if($start>$end||$start>=$size)$this->fail(416,'Ungültiger Bereich.');http_response_code(206);header("Content-Range: bytes $start-$end/$size");}
        header('Content-Length: '.($end-$start+1));$fp=fopen($path,'rb');fseek($fp,$start);$remaining=$end-$start+1;while($remaining>0&&!feof($fp)){ $chunk=fread($fp,min(65536,$remaining));echo $chunk;$remaining-=strlen($chunk); }fclose($fp);if($m['storage']!=='local')unlink($path);exit;
    }
    private function date(array $d,string $key):string {$v=$this->text($d,$key,40,true);$t=strtotime($v);if($t===false)$this->fail(422,'Datum ungültig.');return gmdate('Y-m-d H:i:s',$t);}
    private function ads(array $q):array {
        $this->module('social');
        if(!empty($q['live']))$items=$this->all("SELECT a.*,s.placement FROM kdd_social_ads a JOIN kdd_social_slots s ON s.id=a.slot_id WHERE a.state='accepted' AND (a.paid=1 OR a.amount=0) AND s.active=1 AND a.starts_at<=UTC_TIMESTAMP() AND a.ends_at>UTC_TIMESTAMP() ORDER BY a.starts_at");
        elseif($this->can('advertiser'))$items=$this->all('SELECT a.*,s.name AS slot_name,c.name AS company_name FROM kdd_social_ads a JOIN kdd_social_slots s ON s.id=a.slot_id JOIN kdd_social_companies c ON c.id=a.company_id ORDER BY a.id DESC LIMIT 100');
        else $items=array_values(array_filter($this->all('SELECT a.*,s.name AS slot_name FROM kdd_social_ads a JOIN kdd_social_slots s ON s.id=a.slot_id ORDER BY a.id DESC LIMIT 500'),fn($ad)=>$this->accounts->companyRight((int)$ad['company_id'],$this->id(),'ads')));
        foreach($items as &$a){$a['media']=$this->all('SELECT id,mime,filename FROM kdd_social_media WHERE ad_id=?',[$a['id']]);if(!empty($q['live']))$a=array_intersect_key($a,array_flip(['id','title','body','target','countdown_at','media','placement','ends_at']));}return ['items'=>$items];
    }
    private function requestAd(array $d):array {
        $this->module('social');$company=(int)$d['company_id'];if(!$this->accounts->companyRight($company,$this->id(),'ads'))$this->fail(403,'Keine Unternehmensberechtigung.');
        $slot=$this->one('SELECT * FROM kdd_social_slots WHERE id=? AND active=1',[(int)$d['slot_id']]);if(!$slot)$this->fail(404,'Werbeplatz nicht verfügbar.');$start=$this->date($d,'starts_at');$end=$this->date($d,'ends_at');
        if($start>=$end||$start<gmdate('Y-m-d H:i:s')||$start<$slot['starts_at']||$end>$slot['ends_at'])$this->fail(422,'Zeitraum liegt außerhalb der Verfügbarkeit.');
        $target=$this->text($d,'target',500);if($target&&!preg_match('~^https?://~i',$target)&&!preg_match('~^#/(post|profile)/[0-9]+$~D',$target))$this->fail(422,'Ziel muss ein Weblink oder interner Beitrags-/Profillink sein.');
        $this->db->beginTransaction();
        $id=$this->insert('INSERT INTO kdd_social_ads(company_id,applicant,slot_id,title,body,target,starts_at,ends_at,countdown_at,amount) VALUES(?,?,?,?,?,?,?,?,?,?)',[$company,$this->id(),$slot['id'],$this->text($d,'title',120,true),$this->text($d,'body',3000),$target,$start,$end,empty($d['countdown_at'])?null:$this->date($d,'countdown_at'),$slot['price']]);
        foreach(array_slice(array_unique(array_map('intval',$d['media']??[])),0,1) as $mid){$m=$this->one('SELECT * FROM kdd_social_media WHERE id=? FOR UPDATE',[$mid]);if(!$m||(int)$m['owner_id']!==$this->id()||$m['purpose']!=='ad'||$m['ad_id'])$this->fail(422,'Ungültiges Werbebild.');$this->exec('UPDATE kdd_social_media SET ad_id=? WHERE id=?',[$id,$mid]);}$this->db->commit();return ['id'=>$id];
    }
    private function saveSlot(array $d):array {
        $this->role('advertiser');$this->module('social');$id=(int)($d['id']??0);$start=$this->date($d,'starts_at');$end=$this->date($d,'ends_at');if($start>=$end)$this->fail(422,'Ende muss nach Beginn liegen.');
        $placement=$this->enum($d['placement']??'', ['header','sidebar','pinned']);$price=(float)($d['price']??0);if($price<0||$price>9999999999)$this->fail(422,'Preis ungültig.');
        // A slot is an exclusive inventory unit for a placement. Overlapping inventory is not allowed.
        $this->db->beginTransaction();$this->one('SELECT id FROM kdd_social_settings WHERE id=1 FOR UPDATE');
        if($this->one('SELECT id FROM kdd_social_slots WHERE placement=? AND id<>? AND active=1 AND starts_at<? AND ends_at>?',[$placement,$id,$end,$start]))$this->fail(409,'Für diesen Platz besteht bereits ein überlappender Zeitraum.');
        if($id&&$this->one("SELECT id FROM kdd_social_ads WHERE slot_id=? AND state='accepted'",[$id]))$this->fail(409,'Gebuchte Plätze können nicht verändert werden.');
        $args=[$this->text($d,'name',100,true),$placement,$start,$end,$price,$this->text($d,'conditions',2000),empty($d['active'])?0:1];
        if($id)$this->exec('UPDATE kdd_social_slots SET name=?,placement=?,starts_at=?,ends_at=?,price=?,conditions=?,active=? WHERE id=?',[...$args,$id]);else $id=$this->insert('INSERT INTO kdd_social_slots(name,placement,starts_at,ends_at,price,conditions,active) VALUES(?,?,?,?,?,?,?)',$args);
        $this->audit('slot',['id'=>$id]);$this->db->commit();return ['id'=>$id];
    }
    private function decideAd(array $d):array {
        $this->role('advertiser');$this->module('social');$id=(int)$d['id'];$action=$this->enum($d['decision']??'',['accepted','rejected','paid']);
        $this->db->beginTransaction();$ad=$this->one('SELECT * FROM kdd_social_ads WHERE id=? FOR UPDATE',[$id]);if(!$ad)$this->fail(404,'Anfrage nicht gefunden.');
        $slot=$this->one('SELECT * FROM kdd_social_slots WHERE id=? FOR UPDATE',[$ad['slot_id']]);
        if($action==='paid'){if($ad['state']!=='accepted')$this->fail(409,'Anfrage zuerst annehmen.');$this->exec('UPDATE kdd_social_ads SET paid=1 WHERE id=?',[$id]);$this->notice((int)$ad['applicant'],'Zahlung für „'.$ad['title'].'“ bestätigt.','ads');}
        else{
            if($ad['state']!=='pending')$this->fail(409,'Anfrage wurde bereits entschieden.');
            if($action==='accepted'){
                if(!$slot['active']||$ad['starts_at']<gmdate('Y-m-d H:i:s'))$this->fail(409,'Der Zeitraum ist nicht mehr verfügbar.');
                if($this->one("SELECT id FROM kdd_social_ads WHERE slot_id=? AND state='accepted' AND starts_at<? AND ends_at>?",[$ad['slot_id'],$ad['ends_at'],$ad['starts_at']]))$this->fail(409,'Dieser Zeitraum ist bereits belegt.');
            }
            $reason=$this->text($d,'reason',2000,$action==='rejected');$amount=$action==='accepted'?(float)($d['amount']??$ad['amount']):(float)$ad['amount'];if($amount<0||$amount>9999999999)$this->fail(422,'Betrag ungültig.');
            $this->exec('UPDATE kdd_social_ads SET state=?,amount=?,decision=? WHERE id=?',[$action,$amount,$reason,$id]);
            $template=$this->text($d,'message',6000)?:$this->settings[$action==='accepted'?'accept_template':'reject_template'];
            $body=strtr($template,['{name}'=>$this->profile((int)$ad['applicant'])['display_name'],'{title}'=>$ad['title'],'{start}'=>$ad['starts_at'],'{end}'=>$ad['ends_at'],'{amount}'=>number_format($amount,2,',','.'),'{instructions}'=>$this->settings['payment_instructions'],'{reason}'=>$reason]);
            // System decision is internal and remains available even when direct messages are disabled.
            $this->notice((int)$ad['applicant'],$body,'ads');
        }
        $this->audit('ad_'.$action,['id'=>$id]);$this->db->commit();return ['ok'=>true];
    }
    private function admin():array {
        if(!$this->can('moderator')&&!$this->can('advertiser'))$this->fail(403,'Keine Verwaltungsrechte.');
        $out=['settings'=>$this->settings,'revision'=>(int)$this->one('SELECT revision FROM kdd_social_settings WHERE id=1')['revision']];
        if($this->can('moderator')){
            $out['profiles']=$this->all('SELECT id,handle,display_name,status,role,suspended_until FROM kdd_social_profiles ORDER BY id DESC LIMIT 200');
            $out['videos']=[];if(!empty($this->settings['modules']['video']))foreach($this->all("SELECT id FROM kdd_social_posts WHERE module='video' AND state='pending' ORDER BY id LIMIT 100") as $p)$out['videos'][]=$this->post((int)$p['id'],true);
            $out['reports']=$this->all("SELECT * FROM kdd_social_reports WHERE state='open' ORDER BY id LIMIT 100");
            foreach($out['reports'] as &$report){if($report['kind']==='message'){$report['content']=$this->one('SELECT id,body,sender,recipient FROM kdd_social_messages WHERE id=?',[$report['target_id']]);$report['media']=$this->all('SELECT id,mime,filename FROM kdd_social_media WHERE message_id=?',[$report['target_id']]);}elseif($report['kind']==='post'){try{$report['content']=$this->post((int)$report['target_id'],true);}catch(ApiError $e){$report['content']=null;}}}unset($report);
        }
        if($this->can('advertiser')){$out['slots']=$this->all('SELECT * FROM kdd_social_slots ORDER BY starts_at DESC LIMIT 100');$out['ads']=!empty($this->settings['modules']['social'])?$this->ads([])['items']:[];}
        if($this->can('admin'))$out['audit']=$this->all('SELECT * FROM kdd_social_audit ORDER BY id DESC LIMIT 100');
        return $out;
    }
    private function moderate(array $d):array {
        $this->role('moderator');$kind=$this->enum($d['kind']??'',['profile','video','report','post','role']);$id=(int)$d['id'];$reason=$this->text($d,'reason',2000,true);
        if($kind==='role'){$this->role('admin');$role=$this->enum($d['value']??'',['member','moderator','advertiser','admin']);$this->profile($id);if($id===$this->id())$this->fail(422,'Die eigene Rolle kann hier nicht geändert werden.');$this->exec('UPDATE kdd_social_profiles SET role=? WHERE id=?',[$role,$id]);}
        if($kind==='profile'){$p=$this->profile($id);if($p['role']==='admin')$this->role('admin');if($id===$this->id())$this->fail(422,'Eigene Sperre nicht möglich.');$value=$this->enum($d['value']??'',['active','rejected','suspended']);$until=$value==='suspended'&&!empty($d['until'])?$this->date($d,'until'):null;if($until&&$until<=gmdate('Y-m-d H:i:s'))$this->fail(422,'Sperrende muss in der Zukunft liegen.');$this->exec('UPDATE kdd_social_profiles SET status=?,suspended_until=? WHERE id=?',[$value,$until,$id]);$this->notice($id,'Kontostatus: '.$value.'. '.$reason,'account');}
        if($kind==='video'||$kind==='post'){$p=$this->post($id,true);$value=$this->enum($d['value']??'',['published','rejected','hidden']);if($p['module']==='video'&&$value==='published'&&(!$p['media']||array_filter($p['media'],fn($m)=>$m['state']!=='ready')))$this->fail(409,'Video muss zuerst fertig verarbeitet sein.');$this->exec('UPDATE kdd_social_posts SET state=?,review_reason=? WHERE id=?',[$value,$reason,$id]);$this->notice((int)$p['author_id'],'Beitragsentscheidung: '.$value.'. '.$reason,'post/'.$id);}
        if($kind==='report')$this->exec("UPDATE kdd_social_reports SET state='resolved',resolution=? WHERE id=?",[$reason,$id]);
        $this->audit('moderate_'.$kind,['id'=>$id,'reason'=>$reason,'value'=>$d['value']??null]);return ['ok'=>true];
    }
    private function saveSettings(array $d):array {
        $this->auth();$config=$d['settings']??[];if(!is_array($config))$this->fail(422,'Einstellungen ungültig.');
        $allowed=$this->can('admin')?array_keys(Policy::defaults()):['links','info_blocks'];
        if(!$this->can('admin')&&!$this->can('moderator')&&!$this->can('advertiser'))$this->fail(403,'Keine Berechtigung.');
        if($this->can('advertiser'))$allowed=array_merge($allowed,['accept_template','reject_template','payment_instructions']);
        $next=$this->settings;
        foreach($config as $key=>$v){if(!in_array($key,$allowed,true))continue;
            if($key==='modules'){foreach(array_keys(Policy::defaults()['modules']) as $module)if(isset($v[$module]))$next['modules'][$module]=(bool)$v[$module];continue;}
            if($key==='post_categories'){if(!is_array($v)||!count($v)||count($v)>30)$this->fail(422,'Bitte 1 bis 30 Kategorien angeben.');$v=array_values(array_unique(array_map(fn($item)=>$this->text(['category'=>$item],'category',60,true),$v)));}
            if($key==='names'){foreach(array_keys(Policy::defaults()['names']) as $module)if(isset($v[$module]))$next['names'][$module]=$this->text($v,$module,40,true);continue;}
            if(in_array($key,['upload_mb','video_mb','quota_mb','video_seconds'])){$v=(int)$v;if($v<1||$v>($key==='quota_mb'?100000:($key==='video_seconds'?7200:1024)))$this->fail(422,'Ungültiges Limit.');}
            if($key==='theme_colors'){
                if(!is_array($v))$this->fail(422,'Farbeinstellungen ungültig.');$palette=[];
                foreach($v as $mode=>$colors){if(!in_array($mode,['light','dark'],true)||!is_array($colors))$this->fail(422,'Farbmodus ungültig.');$palette[$mode]=[];
                    foreach($colors as $token=>$color){if(!in_array($token,['canvas','surface','subtle','text','muted','line','soft','nav','nav_text'],true)||!is_string($color)||!preg_match('/^#[0-9a-fA-F]{6}$/D',$color))$this->fail(422,'Bitte gültige Hex-Farben auswählen.');$palette[$mode][$token]=$color;}
                }$v=$palette;
            }
            if($key==='accent'&&!preg_match('/^#[0-9a-fA-F]{6}$/D',(string)$v))$this->fail(422,'Farbe ungültig.');
            if($key==='registration')$v=$this->enum($v,['manual','immediate']);
            if($key==='icon_set')$v=$this->enum($v,['coastal','pacific','night','signs','studio']);
            if($key==='icons'){if(!is_array($v))$this->fail(422,'Icons ungültig.');$icons=[];foreach(array_keys(Policy::defaults()['names']) as $module){$mid=(int)($v[$module]??0);if($mid&&!$this->one("SELECT 1 FROM kdd_social_media WHERE id=? AND purpose='branding' AND mime LIKE 'image/%'",[$mid]))$this->fail(422,'Icon ungültig.');if($mid)$icons[$module]=$mid;}$v=$icons;}
            if(in_array($key,['background_id','logo_id','header_id'],true)){$v=(int)$v;if($v&&!$this->one("SELECT 1 FROM kdd_social_media WHERE id=? AND purpose='branding' AND mime LIKE 'image/%'",[$v]))$this->fail(422,'Hintergrund ungültig.');}
            if($key==='info_blocks'){if(!is_array($v)||count($v)>8)$this->fail(422,'Maximal acht Infoblöcke.');$blocks=[];foreach($v as $block)$blocks[]=['title'=>$this->text($block,'title',80,true),'body'=>$this->text($block,'body',1500,true)];$v=$blocks;}
            if($key==='links'){if(!is_array($v)||count($v)>12)$this->fail(422,'Maximal zwölf Links.');$links=[];foreach($v as $link){$url=$this->text($link,'url',500,true);if(!filter_var($url,FILTER_VALIDATE_URL)||!preg_match('~^https?://~i',$url))$this->fail(422,'Link ungültig.');$links[]=['label'=>$this->text($link,'label',60,true),'url'=>$url];}$v=$links;}
            if($key==='guest')$v=(bool)$v;
            if(is_string($v)&&mb_strlen($v)>6000)$this->fail(422,'Text zu lang.');$next[$key]=$v;
        }
        $this->db->beginTransaction();$n=$this->exec('UPDATE kdd_social_settings SET settings=?,revision=revision+1 WHERE id=1 AND revision=?',[json_encode($next,JSON_UNESCAPED_UNICODE),(int)($d['revision']??0)]);if(!$n)$this->fail(409,'Einstellungen wurden inzwischen geändert. Bitte neu laden.');$this->audit('settings',array_keys($config));$this->db->commit();return ['ok'=>true];
    }
    private function purge(array $d):array {
        $this->role('admin');$module=$this->enum($d['module']??'',array_keys(Policy::defaults()['modules']));
        if(!empty($this->settings['modules'][$module]))$this->fail(409,'Modul zuerst deaktivieren.');
        $count=(int)$this->one("SELECT COUNT(*) n FROM kdd_social_media WHERE module=? AND purpose NOT IN ('profile','branding','company')",[$module])['n'];
        $posts=(int)$this->one('SELECT COUNT(*) n FROM kdd_social_posts WHERE module=?',[$module])['n'];
        $messages=$module==='messages'?(int)$this->one('SELECT COUNT(*) n FROM kdd_social_messages')['n']:0;
        if(empty($d['confirm']))return ['files'=>$count,'posts'=>$posts,'messages'=>$messages,'confirmation'=>'DELETE '.$module];
        if($d['confirm']!=='DELETE '.$module)$this->fail(422,'Bestätigung stimmt nicht.');
        require_once __DIR__.'/Storage.php';$this->db->beginTransaction();
        $files=$this->all("SELECT * FROM kdd_social_media WHERE module=? AND purpose NOT IN ('profile','branding','company')",[$module]);
        foreach($files as $file)$this->exec('INSERT IGNORE INTO kdd_social_garbage(storage,storage_key) VALUES(?,?)',[$file['storage'],$file['storage_key']]);
        $this->exec('DELETE b FROM kdd_social_bookmarks b JOIN kdd_social_posts p ON p.id=b.post_id WHERE p.module=?',[$module]);
        $this->exec('DELETE c FROM kdd_social_comments c JOIN kdd_social_posts p ON p.id=c.post_id WHERE p.module=?',[$module]);
        $this->exec('DELETE r FROM kdd_social_reactions r JOIN kdd_social_posts p ON p.id=r.post_id WHERE p.module=?',[$module]);
        $this->exec('DELETE FROM kdd_social_posts WHERE module=?',[$module]);
        if($module==='messages')$this->exec('DELETE FROM kdd_social_messages');
        if($module==='social'){$this->exec('DELETE FROM kdd_social_ads');$this->exec('DELETE FROM kdd_social_slots');}
        $this->exec("DELETE FROM kdd_social_media WHERE module=? AND purpose NOT IN ('profile','branding','company')",[$module]);$this->audit('purge',['module'=>$module,'files'=>$count,'posts'=>$posts,'messages'=>$messages]);$this->db->commit();
        $failed=0;foreach($files as $m){try{if(!$this->one('SELECT 1 FROM kdd_social_media WHERE storage=? AND storage_key=?',[$m['storage'],$m['storage_key']]))Storage::delete($m);$this->exec('DELETE FROM kdd_social_garbage WHERE storage=? AND storage_key=?',[$m['storage'],$m['storage_key']]);}catch(\Throwable $e){$failed++;error_log('Social media cleanup failed: '.$m['id']);}}
        return ['ok'=>true,'file_cleanup_failed'=>$failed];
    }
    private function queueMedia(string $where,array $args):void {
        $this->exec('INSERT IGNORE INTO kdd_social_garbage(storage,storage_key) SELECT storage,storage_key FROM kdd_social_media WHERE '.$where,$args);
        $this->exec('DELETE FROM kdd_social_media WHERE '.$where,$args);
    }
    private function changePassword(array $d):array {
        $u=$this->one('SELECT password FROM kdd_users WHERE id=?',[$this->me['user_id']]);if(!password_verify((string)($d['current']??''),$u['password']))$this->fail(403,'Aktuelles Passwort ungültig.');
        $password=$this->text($d,'password',200,true);if(strlen($password)<12)$this->fail(422,'Mindestens 12 Zeichen.');$this->db->beginTransaction();$this->exec('UPDATE kdd_users SET password=? WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$this->me['user_id']]);$this->exec('DELETE FROM kdd_social_sessions WHERE profile_id=?',[$this->id()]);$this->db->commit();return $this->loginSession($this->me);
    }
    private function deleteAccount(array $d):array {
        if($this->one('SELECT 1 FROM kdd_social_companies WHERE owner_id=?',[$this->id()]))$this->fail(409,'Bitte zuerst die Unternehmensinhaberschaft übertragen.');
        if($this->can('admin'))$this->fail(409,'Technische Rolle zuerst durch einen anderen Administrator entfernen lassen.');
        $u=$this->one('SELECT password FROM kdd_users WHERE id=?',[$this->me['user_id']]);if(!password_verify((string)($d['password']??''),$u['password'])||($d['confirm']??'')!=='DELETE')$this->fail(422,'Passwort und Bestätigung erforderlich.');
        $this->db->beginTransaction();$this->queueMedia('owner_id=? AND (post_id IS NULL OR post_id NOT IN (SELECT id FROM kdd_social_posts WHERE company_id IS NOT NULL)) AND id NOT IN (SELECT photo_id FROM kdd_social_companies WHERE photo_id IS NOT NULL)',[$this->id()]);$this->exec('DELETE FROM kdd_social_bookmarks WHERE profile_id=?',[$this->id()]);$this->exec('DELETE FROM kdd_social_comments WHERE author_id=?',[$this->id()]);$this->exec('DELETE FROM kdd_social_reactions WHERE profile_id=?',[$this->id()]);$this->exec('DELETE FROM kdd_social_friends WHERE sender=? OR recipient=?',[$this->id(),$this->id()]);$this->exec('DELETE FROM kdd_social_members WHERE profile_id=?',[$this->id()]);$this->exec("UPDATE kdd_social_profiles SET status='deleted',handle=CONCAT('deleted_',id),display_name='Gelöschtes Konto',bio='',signature='',avatar_id=NULL,cover_id=NULL,recovery_hash='' WHERE id=?",[$this->id()]);$this->exec("UPDATE kdd_social_posts SET state='deleted' WHERE author_id=? AND company_id IS NULL",[$this->id()]);$this->exec('DELETE FROM kdd_social_sessions WHERE profile_id=?',[$this->id()]);$this->exec("UPDATE kdd_users SET banned=1,username=CONCAT('deleted_',id),email=CONCAT('deleted_',id,'@social.invalid'),password='' WHERE id=?",[$this->me['user_id']]);$this->db->commit();return ['ok'=>true];
    }
}
