<?php
declare(strict_types=1);
namespace Kyuubi\Social;
/** Authentication remains personal. Saved references and delegated grants never confer ownership. */
final class Accounts {
    public function __construct(private \PDO $db) {}
    public function rows(string $sql,array $a=[]):array {$q=$this->db->prepare($sql);$q->execute($a);return $q->fetchAll(\PDO::FETCH_ASSOC);}
    public function one(string $sql,array $a=[]):?array {return $this->rows($sql,$a)[0]??null;}
    public function exec(string $sql,array $a=[]):void {$q=$this->db->prepare($sql);$q->execute($a);}
    private function fail(int $s,string $m):never {throw new ApiError($s,$m);}
    public function validSession(string $hash):?array {
        return $this->one("SELECT p.* FROM kdd_social_sessions s JOIN kdd_social_profiles p ON p.id=s.profile_id JOIN kdd_users u ON u.id=p.user_id JOIN kdd_authorities a ON a.id=p.authority_id
          LEFT JOIN kdd_social_session_links sl ON sl.session_hash=s.token_hash
          LEFT JOIN kdd_social_cad_links l ON l.id=sl.link_id
          LEFT JOIN kdd_sessions cs ON cs.token_hash=BINARY sl.cad_session_hash
          LEFT JOIN kdd_users cu ON cu.id=cs.user_id LEFT JOIN kdd_authorities ca ON ca.id=cu.authority_id
          WHERE s.token_hash=? AND s.expires_at>UTC_TIMESTAMP() AND u.banned=0 AND a.active=1 AND p.status<>'deleted'
          AND (sl.session_hash IS NULL OR (l.id IS NOT NULL AND l.cad_user_id=cs.user_id AND l.profile_id=p.id AND cs.is_active=1 AND cs.expires_at>UTC_TIMESTAMP() AND cu.banned=0 AND ca.active=1))",[$hash]);
    }
    private function wallet(bool $create=false):string {
        $token=$_COOKIE['social_wallet']??'';$hash=hash('sha256',$token);
        if($token&&$this->one('SELECT 1 FROM kdd_social_wallets WHERE token_hash=? AND expires_at>UTC_TIMESTAMP()',[$hash]))return $hash;
        if(!$create)return '';
        $token=bin2hex(random_bytes(32));$hash=hash('sha256',$token);
        $this->exec('INSERT INTO kdd_social_wallets VALUES(?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 DAY))',[$hash]);
        setcookie('social_wallet',$token,['expires'=>time()+2592000,'path'=>'/api/social/','secure'=>filter_var(\getEnvVar('COOKIE_SECURE','true'),FILTER_VALIDATE_BOOLEAN),'httponly'=>true,'samesite'=>'Strict']);
        $_COOKIE['social_wallet']=$token;return $hash;
    }
    public function remember(int $id,string $hash):void {$this->exec('INSERT INTO kdd_social_wallet_accounts VALUES(?,?,?) ON DUPLICATE KEY UPDATE session_hash=VALUES(session_hash)',[$this->wallet(true),$id,$hash]);}
    public function resolve():array {
        $requested=(int)($_SERVER['HTTP_X_SOCIAL_ACCOUNT']??0);
        if($requested<0)return [null,'']; // Explicitly signed out in this window.
        $hash='';
        if($requested){$row=$this->one('SELECT session_hash FROM kdd_social_wallet_accounts WHERE wallet_hash=? AND profile_id=?',[$this->wallet(),$requested]);$hash=$row['session_hash']??'';}
        elseif(!empty($_COOKIE['social_session']))$hash=hash('sha256',$_COOKIE['social_session']);
        $profile=$hash?$this->validSession($hash):null;
        if(!$requested&&$profile)$this->remember((int)$profile['id'],$hash);
        if($requested&&!$profile)$this->fail(401,'Dieses gespeicherte Konto ist nicht mehr angemeldet. Bitte erneut anmelden.');
        return [$profile,$hash];
    }
    public function listing(?array $actor):array {
        $own=[];
        foreach($this->rows('SELECT profile_id,session_hash FROM kdd_social_wallet_accounts WHERE wallet_hash=?',[$this->wallet()]) as $r){$p=$this->validSession($r['session_hash']);if($p)$own[]=array_intersect_key($p,array_flip(['id','handle','display_name','avatar_id']));}
        if(!$actor)return ['own'=>$own,'delegated'=>[],'invitations'=>[],'companies'=>[],'links'=>[],'members'=>[]];
        $id=$actor['id'];
        return ['own'=>$own,
          'delegated'=>$this->rows("SELECT p.id,p.handle,p.display_name,g.rights_json FROM kdd_social_account_grants g JOIN kdd_social_profiles p ON p.id=g.owner_id WHERE g.member_id=? AND g.status='active' AND p.status='active'",[$id]),
          'invitations'=>$this->rows("SELECT 'profile' kind,p.id,p.display_name name FROM kdd_social_account_grants g JOIN kdd_social_profiles p ON p.id=g.owner_id WHERE g.member_id=? AND g.status='pending' UNION ALL SELECT 'company',c.id,c.name FROM kdd_social_members m JOIN kdd_social_companies c ON c.id=m.company_id WHERE m.profile_id=? AND m.status='pending'",[$id,$id]),
          'companies'=>$this->rows("SELECT c.id,c.name,c.owner_id,m.rights_json FROM kdd_social_companies c LEFT JOIN kdd_social_members m ON m.company_id=c.id AND m.profile_id=? AND m.status='active' WHERE c.owner_id=? OR m.profile_id IS NOT NULL",[$id,$id]),
          'members'=>$this->rows('SELECT g.member_id,p.handle,p.display_name,g.status,g.rights_json FROM kdd_social_account_grants g JOIN kdd_social_profiles p ON p.id=g.member_id WHERE g.owner_id=?',[$id]),
          'transfers'=>$this->rows('SELECT c.id,c.name FROM kdd_social_owner_transfers t JOIN kdd_social_companies c ON c.id=t.company_id AND c.owner_id=t.from_id WHERE t.to_id=?',[$id]),
          'links'=>$this->rows('SELECT l.id,u.username,a.display_name authority_name FROM kdd_social_cad_links l JOIN kdd_users u ON u.id=l.cad_user_id JOIN kdd_authorities a ON a.id=u.authority_id WHERE l.profile_id=?',[$id])];
    }
    public function reauth(array $actor,array $d):void {
        $r=$this->one('SELECT password FROM kdd_users WHERE id=?',[$actor['user_id']]);
        if(!$r||!password_verify((string)($d['password']??''),$r['password']))$this->fail(403,'Bitte mit deinem aktuellen Passwort bestätigen.');
    }
    public function companyRight(int $company,int $member,string $right):bool {
        $c=$this->one('SELECT owner_id FROM kdd_social_companies WHERE id=?',[$company]);
        if($c&&(int)$c['owner_id']===$member)return true;
        $m=$this->one("SELECT rights_json FROM kdd_social_members WHERE company_id=? AND profile_id=? AND status='active'",[$company,$member]);
        return $m&&($m['rights_json']===null||in_array($right,json_decode($m['rights_json'],true)??[],true));
    }
    public function delegation(int $owner,int $member):array {
        $r=$this->one("SELECT g.rights_json FROM kdd_social_account_grants g JOIN kdd_social_profiles p ON p.id=g.owner_id JOIN kdd_users u ON u.id=p.user_id JOIN kdd_authorities a ON a.id=p.authority_id WHERE g.owner_id=? AND g.member_id=? AND g.status='active' AND p.status='active' AND u.banned=0 AND a.active=1",[$owner,$member]);
        if(!$r)throw new ApiError(403,'Der Zugriff auf dieses Konto wurde entzogen oder nicht freigegeben.','access_revoked');
        return json_decode($r['rights_json'],true)??[];
    }
    private function audit(int $actor,string $action,array $d):void {$this->exec('INSERT INTO kdd_social_audit(actor,action,details) VALUES(?,?,?)',[$actor,$action,json_encode($d)]);}
    public function action(string $action,array $actor,array $d):array {
        $id=(int)$actor['id'];
        if($action==='accounts')return $this->listing($actor);
        if($action==='forget_account') {
            $target=(int)($d['id']??0);$this->exec('DELETE FROM kdd_social_wallet_accounts WHERE wallet_hash=? AND profile_id=?',[$this->wallet(),$target]);return ['ok'=>true];
        }
        if($action==='unlink_cad') {
            $this->reauth($actor,$d);$this->exec('DELETE FROM kdd_social_cad_links WHERE id=? AND profile_id=?',[(int)($d['id']??0),$id]);$this->audit($id,'unlink_cad',['id'=>$d['id']]);return ['ok'=>true];
        }
        if($action==='accept_owner'){
            $this->reauth($actor,$d);$company=(int)($d['id']??0);
            $t=$this->one('SELECT * FROM kdd_social_owner_transfers WHERE company_id=? AND to_id=?',[$company,$id]);
            if(!$t)$this->fail(404,'Übertragung nicht gefunden.');
            $this->exec('UPDATE kdd_social_companies SET owner_id=? WHERE id=? AND owner_id=?',[$id,$company,$t['from_id']]);
            $this->exec('DELETE FROM kdd_social_owner_transfers WHERE company_id=?',[$company]);
            $this->audit($id,'accept_owner',['company'=>$company,'previous_owner'=>$t['from_id']]);return ['ok'=>true];
        }
        $kind=$d['kind']??'profile';if(!in_array($kind,['profile','company'],true))$this->fail(422,'Ungültige Kontoart.');
        $target=$kind==='profile'?$id:(int)($d['id']??0);
        if($action==='accept_access') {
            $target=(int)($d['id']??0);$status=empty($d['accept'])?'revoked':'active';
            if($kind==='profile')$this->exec("UPDATE kdd_social_account_grants SET status=? WHERE owner_id=? AND member_id=? AND status='pending'",[$status,$target,$id]);
            else $this->exec("UPDATE kdd_social_members SET status=? WHERE company_id=? AND profile_id=? AND status='pending'",[$status,$target,$id]);
            $this->audit($id,'accept_access',['kind'=>$kind,'id'=>$target,'status'=>$status]);return ['ok'=>true];
        }
        if($kind==='company'){$c=$this->one('SELECT owner_id FROM kdd_social_companies WHERE id=?',[$target]);if(!$c||(int)$c['owner_id']!==$id)$this->fail(403,'Nur der Haupteigner darf Zugriffe verwalten.');}
        if($action==='access_members')return ['cad_candidates'=>$kind==='company'?$this->rows('SELECT DISTINCT p.handle,p.display_name,u.username FROM kdd_social_companies c JOIN kdd_users u ON u.authority_id=c.cad_authority_id AND u.banned=0 JOIN kdd_social_cad_links l ON l.cad_user_id=u.id JOIN kdd_social_profiles p ON p.id=l.profile_id AND p.status="active" WHERE c.id=?',[$target]):[],'items'=>$kind==='company'?$this->rows('SELECT m.profile_id member_id,p.handle,p.display_name,m.status,m.rights_json FROM kdd_social_members m JOIN kdd_social_profiles p ON p.id=m.profile_id WHERE m.company_id=?',[$target]):$this->listing($actor)['members']];
        $this->reauth($actor,$d);
        $member=$this->one("SELECT id FROM kdd_social_profiles WHERE handle=? AND status='active'",[strtolower(trim((string)($d['handle']??'')))]);
        if(!$member)$this->fail(404,'Aktives Profil nicht gefunden.');$mid=(int)$member['id'];
        if($mid===$id)$this->fail(422,'Der Haupteigner kann nicht als Mitarbeiter geändert oder entfernt werden.');
        if($action==='transfer_owner'){
            if($kind!=='company')$this->fail(422,'Persönliche Konten behalten ihren Eigentümer.');
            $this->exec('INSERT INTO kdd_social_owner_transfers(company_id,from_id,to_id) VALUES(?,?,?) ON DUPLICATE KEY UPDATE from_id=VALUES(from_id),to_id=VALUES(to_id),created_at=UTC_TIMESTAMP()',[$target,$id,$mid]);
            $this->audit($id,$action,['company'=>$target,'new_owner'=>$mid]);return ['ok'=>true];
        }
        if($action==='grant_access') {
            $rights=$d['rights']??[];$allowed=$kind==='company'?['posts','profile','ads']:['posts','profile'];
            if(!is_array($rights)||!$rights||array_diff($rights,$allowed))$this->fail(422,'Bitte gültige Einzelrechte auswählen.');
            $rights=json_encode(array_values(array_unique($rights)));
            if($kind==='company')$this->exec("INSERT INTO kdd_social_members(company_id,profile_id,rights_json,status) VALUES(?,?,?,'pending') ON DUPLICATE KEY UPDATE rights_json=VALUES(rights_json),status='pending'",[$target,$mid,$rights]);
            else $this->exec("INSERT INTO kdd_social_account_grants(owner_id,member_id,rights_json) VALUES(?,?,?) ON DUPLICATE KEY UPDATE rights_json=VALUES(rights_json),status='pending'",[$target,$mid,$rights]);
        } elseif($action==='revoke_access') {
            if($kind==='company'){$this->exec('DELETE FROM kdd_social_members WHERE company_id=? AND profile_id=?',[$target,$mid]);$this->exec('DELETE FROM kdd_social_owner_transfers WHERE company_id=? AND to_id=?',[$target,$mid]);}
            else $this->exec('DELETE FROM kdd_social_account_grants WHERE owner_id=? AND member_id=?',[$target,$mid]);
        } else $this->fail(404,'Unbekannte Kontoaktion.');
        $this->audit($id,$action,['kind'=>$kind,'target'=>$target,'member'=>$mid]);return ['ok'=>true];
    }
}
