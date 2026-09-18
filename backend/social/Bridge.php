<?php
declare(strict_types=1);
namespace Kyuubi\Social;
final class Bridge {
    private Accounts $a;
    public function __construct(private \PDO $db) {$this->a=new Accounts($db);}
    private function fail(int $s,string $m):never {throw new ApiError($s,$m);}
    private function audit(int $user,string $action,array $data):void {$this->a->exec('INSERT INTO kdd_social_cad_audit(cad_user_id,action,details) VALUES(?,?,?)',[$user,$action,json_encode($data)]);}
    private function session(string $hash):?array {return $this->a->one('SELECT s.user_id id,s.authority_id FROM kdd_sessions s JOIN kdd_users u ON u.id=s.user_id AND u.authority_id=s.authority_id JOIN kdd_authorities a ON a.id=u.authority_id WHERE s.token_hash=? AND s.is_active=1 AND s.expires_at>UTC_TIMESTAMP() AND u.banned=0 AND a.active=1',[$hash]);}
    public function cad(string $action,array $actor,string $sessionHash,array $d):array {
        $live=$this->session($sessionHash);if(!$live||(int)$live['id']!==(int)$actor['id'])$this->fail(401,'CAD-Sitzung nicht mehr gültig.');
        $uid=$actor['id'];
        $system=$this->a->one("SELECT 1 FROM kdd_user_roles ur JOIN kdd_roles r ON r.id=ur.role_id AND r.authority_id=ur.authority_id AND r.is_deleted=0 JOIN kdd_role_permissions rp ON rp.role_id=r.id AND rp.authority_id=r.authority_id JOIN kdd_permissions p ON p.id=rp.permission_id WHERE ur.user_id=? AND ur.authority_id=? AND p.module='system'",[$uid,$actor['authority_id']]);
        if($action==='list')return ['can_manage_mapping'=>(bool)$system,'items'=>$this->a->rows("SELECT l.id,l.is_default,p.handle,p.display_name FROM kdd_social_cad_links l JOIN kdd_social_profiles p ON p.id=l.profile_id WHERE l.cad_user_id=? AND p.status='active'",[$uid])];
        if($action==='unlink'||$action==='default'){
            // Authenticated CAD endpoint also requires current CAD password for changes.
            $u=$this->a->one('SELECT password FROM kdd_users WHERE id=?',[$uid]);
            if(!password_verify((string)($d['password']??''),$u['password']))$this->fail(403,'Bitte mit deinem CAD-Passwort bestätigen.');
            if($action==='unlink')$this->a->exec('DELETE FROM kdd_social_cad_links WHERE id=? AND cad_user_id=?',[(int)($d['id']??0),$uid]);
            else {$this->db->beginTransaction();$this->a->exec('UPDATE kdd_social_cad_links SET is_default=(id=?) WHERE cad_user_id=?',[(int)($d['id']??0),$uid]);$this->db->commit();}
            $this->audit((int)$uid,$action,['link_id'=>(int)($d['id']??0)]);return ['ok'=>true];
        }
        if($action==='company_mapping') {
            if(!$system)$this->fail(403,'Nur die technische Systemverwaltung darf Unternehmenszuordnungen ändern.');
            if(empty($d))return ['companies'=>$this->a->rows('SELECT id,name,cad_authority_id FROM kdd_social_companies ORDER BY name'),'authorities'=>$this->a->rows("SELECT id,display_name FROM kdd_authorities WHERE active=1 AND authority_type='company'")];
            $authority=(int)($d['authority_id']??0);
            if($authority&&!$this->a->one("SELECT 1 FROM kdd_authorities WHERE id=? AND authority_type='company' AND active=1",[$authority]))$this->fail(422,'Bitte ein aktives CAD-Unternehmen auswählen.');
            $this->a->exec('UPDATE kdd_social_companies SET cad_authority_id=? WHERE id=?',[$authority?:null,(int)($d['company_id']??0)]);$this->audit((int)$uid,'company_mapping',['company_id'=>(int)($d['company_id']??0),'authority_id'=>$authority?:null]);return ['ok'=>true];
        }
        if($action!=='issue')$this->fail(404,'Unbekannte Aktion.');
        $purpose=$d['purpose']??'';$state=$d['state']??'';
        if(!in_array($purpose,['link','login'],true)||!is_string($state)||!preg_match('/^[a-zA-Z0-9-]{32,100}$/D',$state))$this->fail(422,'Ungültige Verbindungsanfrage.');
        $origin=rtrim((string)\getEnvVar('SOCIAL_ORIGIN',''),'/');
        if(!preg_match('~^https?://[^/]+$~D',$origin))$this->fail(503,'SOCIAL_ORIGIN ist noch nicht eingerichtet.');
        $link=null;
        if($purpose==='login'){$link=$this->a->one('SELECT id FROM kdd_social_cad_links WHERE id=? AND cad_user_id=?',[(int)($d['id']??0),$uid]);if(!$link)$this->fail(403,'Kontoverbindung nicht vorhanden.');}
        $code=bin2hex(random_bytes(32));
        $this->a->exec('DELETE FROM kdd_social_bridge_codes WHERE expires_at<UTC_TIMESTAMP()');
        $this->a->exec('INSERT INTO kdd_social_bridge_codes VALUES(?,?,?,?,?,?,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 60 SECOND))',[hash('sha256',$code),$uid,$sessionHash,$link['id']??null,$purpose,$state,$origin]);
        return ['code'=>$code,'origin'=>$origin];
    }
    public function consume(string $action,array $d,?array $actor):array {
        $this->db->beginTransaction();
        $r=$this->a->one('SELECT * FROM kdd_social_bridge_codes WHERE token_hash=? AND expires_at>UTC_TIMESTAMP() FOR UPDATE',[hash('sha256',(string)($d['code']??''))]);
        $purpose=$action==='bridge_link'?'link':'login';
        if(!$r||$r['purpose']!==$purpose||!hash_equals($r['request_state'],(string)($d['state']??''))||$r['target_origin']!==rtrim((string)\getEnvVar('SOCIAL_ORIGIN',''),'/')||!$this->session($r['cad_session_hash']))$this->fail(403,'Verbindungsanfrage abgelaufen oder ungültig.');
        if($purpose==='link') {
            if(!$actor||$actor['status']!=='active')$this->fail(401,'Bitte zuerst das gewünschte Social-Konto anmelden.');
            $this->a->reauth($actor,$d);
            $this->a->exec('INSERT IGNORE INTO kdd_social_cad_links(cad_user_id,profile_id) VALUES(?,?)',[$r['cad_user_id'],$actor['id']]);
            $this->a->exec('INSERT INTO kdd_social_audit(actor,action,details) VALUES(?,?,?)',[$actor['id'],'cad_link',json_encode(['cad_user_id'=>$r['cad_user_id']])]);
            $result=['ok'=>true];
        } else {
            $l=$this->a->one('SELECT l.*,p.status FROM kdd_social_cad_links l JOIN kdd_social_profiles p ON p.id=l.profile_id JOIN kdd_users u ON u.id=p.user_id JOIN kdd_authorities a ON a.id=p.authority_id WHERE l.id=? AND l.cad_user_id=? AND u.banned=0 AND a.active=1',[$r['link_id'],$r['cad_user_id']]);
            if(!$l||$l['status']!=='active')$this->fail(403,'Kontoverbindung nicht mehr verfügbar.');
            $result=['profile_id'=>(int)$l['profile_id'],'link_id'=>(int)$l['id'],'cad_session_hash'=>$r['cad_session_hash']];
        }
        $this->a->exec('DELETE FROM kdd_social_bridge_codes WHERE token_hash=?',[$r['token_hash']]);$this->db->commit();return $result;
    }
}
