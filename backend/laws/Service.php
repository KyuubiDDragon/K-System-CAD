<?php
declare(strict_types=1);
namespace Kyuubi\Laws;
final class Error extends \RuntimeException {
    public function __construct(public int $status, string $message) { parent::__construct($message); }
}
/** Actor comes exclusively from a validated CAD session; Social grants reading only. */
final class Service {
    private array $settings;
    private array $permissions = [];
    private bool $system = false;
    private ?array $actor;
    public function __construct(private \PDO $db, ?array $actor = null, private bool $socialReader = false) {
        $db->exec("SET time_zone = '+00:00'");
        $this->settings = $this->one('SELECT * FROM kdd_law_settings WHERE id=1');
        $this->actor = $actor;
        if ($actor) {
            $valid = $this->one('SELECT u.id FROM kdd_users u JOIN kdd_authorities a ON a.id=u.authority_id WHERE u.id=? AND u.authority_id=? AND u.banned=0 AND a.active=1',[$actor['id'],$actor['authority_id']]);
            if (!$valid) throw new Error(401,'CAD-Zugang nicht mehr gültig.');
            $rows = $this->all('SELECT DISTINCT p.module,p.sub_module,p.action FROM kdd_user_roles ur JOIN kdd_roles r ON r.id=ur.role_id AND r.authority_id=ur.authority_id AND r.is_deleted=0 JOIN kdd_role_permissions rp ON rp.role_id=r.id AND rp.authority_id=r.authority_id JOIN kdd_permissions p ON p.id=rp.permission_id WHERE ur.user_id=? AND ur.authority_id=?',[$actor['id'],$actor['authority_id']]);
            foreach ($rows as $p) {
                if ($p['module']==='system') $this->system=true;
                $this->permissions[$p['module'].($p['sub_module']?'.'.$p['sub_module']:'').'.'.$p['action']]=true;
            }
        }
    }
    private function all(string $sql,array $args=[]): array { $s=$this->db->prepare($sql);$s->execute($args);return $s->fetchAll(\PDO::FETCH_ASSOC); }
    private function one(string $sql,array $args=[]): ?array { return $this->all($sql,$args)[0]??null; }
    private function exec(string $sql,array $args=[]): void { $s=$this->db->prepare($sql);$s->execute($args); }
    private function text(array $d,string $key,int $max,bool $required=true): string {
        $v=$d[$key]??'';if(!is_string($v)||mb_strlen(trim($v))>$max||($required&&trim($v)===''))throw new Error(422,'Bitte '.$key.' prüfen.');return trim($v);
    }
    private function audit(string $action,array $data): void { $this->exec('INSERT INTO kdd_law_audit(actor_id,authority_id,action,details) VALUES(?,?,?,?)',[$this->actor['id'],$this->actor['authority_id'],$action,json_encode($data,JSON_UNESCAPED_UNICODE)]); }
    private function admin(): void { if(!$this->system)throw new Error(403,'Nur die technische CAD-Administration darf Zuständigkeiten verwalten.'); }
    private function reader(): void {
        if (!$this->settings['enabled']&&!$this->system)throw new Error(404,'Die Gesetze-App ist deaktiviert.');
        if (!$this->actor&&!$this->socialReader&&!$this->settings['guest'])throw new Error(401,'Zum Lesen bitte anmelden.');
    }
    public function rights(int $book): array {
        $result=['draft_read'=>false,'edit'=>false,'publish'=>false,'repeal'=>false];
        if ($this->system)return array_fill_keys(array_keys($result),true);
        if(!$this->actor)return $result;
        $feature=$this->one("SELECT 1 FROM kdd_authority_features_rel r JOIN kdd_authority_features f ON f.id=r.feature_id WHERE r.authority_id=? AND f.code='laws'",[$this->actor['authority_id']]);
        if(!$feature)return $result;
        $grant=$this->one('SELECT * FROM kdd_law_grants WHERE book_id=? AND authority_id=?',[$book,$this->actor['authority_id']]);
        foreach(['draft_read'=>'laws.drafts.read','edit'=>'laws.drafts.write','publish'=>'laws.publication.write','repeal'=>'laws.repeal.write'] as $key=>$permission) $result[$key]=!empty($grant[$key])&&!empty($this->permissions[$permission]);
        // Anyone able to edit or publish must be able to inspect the draft they act on.
        $result['draft_read']=$result['draft_read']||$result['edit']||$result['publish'];
        return $result;
    }
    private function requireRight(int $book,string $right): void { if(!$this->rights($book)[$right])throw new Error(403,'Keine Befugnis für dieses Gesetzbuch.'); }
    private function book(int $id): array { $b=$this->one('SELECT * FROM kdd_law_books WHERE id=?',[$id]);if(!$b)throw new Error(404,'Gesetzbuch nicht gefunden.');return $b; }
    private function article(int $id): array { $a=$this->one('SELECT * FROM kdd_law_articles WHERE id=?',[$id]);if(!$a)throw new Error(404,'Paragraph nicht gefunden.');return $a; }
    private function versionView(array $v): array {
        $a=$this->one('SELECT display_name,name FROM kdd_authorities WHERE id=?',[$v['authority_id']]);
        return array_merge(array_intersect_key($v,array_flip(['id','article_id','title','chapter','body','state','reason','created_at','published_at','effective_at','repealed_at','repeal_reason'])),['publisher'=>$a['display_name']??$a['name']??'']);
    }
    public function dispatch(string $action,string $method,array $d=[],array $q=[]): array {
        $reads=['bootstrap','books','book','search','history','administration'];
        if (($method==='GET')!==in_array($action,$reads,true))throw new Error(405,'Methode nicht erlaubt.');
        if ($action==='bootstrap')return ['enabled'=>(bool)$this->settings['enabled'],'guest'=>(bool)$this->settings['guest'],'revision'=>(int)$this->settings['revision'],'admin'=>$this->system,'can_read'=>(bool)($this->actor||$this->socialReader||$this->settings['guest']),'context'=>$this->actor?['authority_id'=>$this->actor['authority_id'],'name'=>$this->one('SELECT display_name FROM kdd_authorities WHERE id=?',[$this->actor['authority_id']])['display_name']]:null];
        $this->reader();
        if ($action==='books')return ['items'=>$this->all('SELECT id,title,description,revision FROM kdd_law_books ORDER BY title,id')];
        if ($action==='search') {
            $query=trim((string)($q['q']??''));
            if(mb_strlen($query)>200)throw new Error(400,'Suchbegriff ist zu lang.');
            $items=[];
            if($query!=='')foreach($this->all('SELECT id,title FROM kdd_law_books ORDER BY title,id') as $book) {
                $result=$this->dispatch('book','GET',[],['id'=>$book['id'],'q'=>$query]);
                foreach($result['articles'] as $article)$items[]=['book_id'=>(int)$book['id'],'book_title'=>$book['title'],'article'=>$article];
            }
            return ['items'=>$items];
        }
        if ($action==='book') {
            $id=(int)($q['id']??0);$book=$this->book($id);$rights=$this->rights($id);$articles=[];$search=mb_strtolower(trim((string)($q['q']??'')));
            foreach($this->all('SELECT * FROM kdd_law_articles WHERE book_id=? ORDER BY id',[$id]) as $a) {
                $v=$this->one("SELECT * FROM kdd_law_versions WHERE article_id=? AND state='published' AND effective_at<=UTC_TIMESTAMP() ORDER BY effective_at DESC,id DESC LIMIT 1",[$a['id']]);
                $draft=$rights['draft_read']?$this->one("SELECT * FROM kdd_law_versions WHERE article_id=? AND state='draft' ORDER BY id DESC LIMIT 1",[$a['id']]):null;
                $future=$this->one("SELECT * FROM kdd_law_versions WHERE article_id=? AND state='published' AND effective_at>UTC_TIMESTAMP() ORDER BY effective_at,id LIMIT 1",[$a['id']]);
                if(!$v&&!$draft&&!$future)continue;
                $haystack=$a['number'].' '.implode(' ',array_map(fn($x)=>$x?($x['title'].' '.$x['chapter'].' '.$x['body']):'',[$v,$draft,$future]));
                if($search!==''&&!str_contains(mb_strtolower($haystack),$search))continue;
                $articles[]=['id'=>(int)$a['id'],'number'=>$a['number'],'revision'=>(int)$a['revision'],'current'=>$v?$this->versionView($v):null,'draft'=>$draft?$this->versionView($draft):null,'scheduled'=>$future?$this->versionView($future):null];
            }
            return ['book'=>$book,'rights'=>$rights,'articles'=>$articles];
        }
        if($action==='history') {
            $a=$this->article((int)($q['id']??0));$drafts=$this->rights((int)$a['book_id'])['draft_read'];
            $rows=$this->all("SELECT * FROM kdd_law_versions WHERE article_id=? AND state='published' ORDER BY effective_at DESC,id DESC",[$a['id']]);
            if(!$rows&&!$drafts)throw new Error(404,'Paragraph nicht gefunden.');
            return ['items'=>array_map(fn($v)=>$this->versionView($v),$rows)];
        }
        if($action==='administration') {
            $this->admin();return ['authorities'=>$this->all("SELECT id,display_name FROM kdd_authorities WHERE active=1 AND authority_type<>'personal' ORDER BY display_name"),'grants'=>$this->all('SELECT * FROM kdd_law_grants'),'audit'=>$this->all('SELECT id,actor_id,authority_id,action,details,created_at FROM kdd_law_audit ORDER BY id DESC LIMIT 100')];
        }
        $this->db->beginTransaction();
        try {
            $result=$this->mutate($action,$d);
            $this->db->commit();return $result;
        } catch(\Throwable $e) { if($this->db->inTransaction())$this->db->rollBack();throw $e; }
    }
    private function mutate(string $action,array $d): array {
        if($action==='settings') {
            $this->admin();$s=$this->one('SELECT revision FROM kdd_law_settings WHERE id=1 FOR UPDATE');
            if((int)$s['revision']!==(int)($d['revision']??0))throw new Error(409,'Einstellungen wurden inzwischen geändert. Bitte neu laden.');
            $this->exec('UPDATE kdd_law_settings SET enabled=?,guest=?,revision=revision+1 WHERE id=1',[!empty($d['enabled'])?1:0,!empty($d['guest'])?1:0]);$this->audit('settings',$d);return ['ok'=>true];
        }
        if($action==='save_book') {
            $this->admin();$id=(int)($d['id']??0);$title=$this->text($d,'title',160);$description=$this->text($d,'description',4000,false);
            if($id) { $b=$this->one('SELECT * FROM kdd_law_books WHERE id=? FOR UPDATE',[$id]);if(!$b)throw new Error(404,'Gesetzbuch nicht gefunden.');if((int)$b['revision']!==(int)($d['revision']??0))throw new Error(409,'Gesetzbuch wurde inzwischen geändert.');$this->exec('UPDATE kdd_law_books SET title=?,description=?,revision=revision+1 WHERE id=?',[$title,$description,$id]); }
            else { $this->exec('INSERT INTO kdd_law_books(title,description,created_by) VALUES(?,?,?)',[$title,$description,$this->actor['id']]);$id=(int)$this->db->lastInsertId(); }
            $this->audit('save_book',['id'=>$id,'title'=>$title]);return ['id'=>$id];
        }
        if($action==='grant') {
            $this->admin();$book=(int)($d['book_id']??0);$this->book($book);$authority=(int)($d['authority_id']??0);
            if(!$this->one("SELECT id FROM kdd_authorities WHERE id=? AND active=1 AND authority_type<>'personal'",[$authority]))throw new Error(422,'Fraktion ungültig.');
            $flags=array_map(fn($k)=>!empty($d[$k])?1:0,['draft_read','edit','publish','repeal']);
            $this->exec('INSERT INTO kdd_law_grants(book_id,authority_id,draft_read,edit,publish,repeal) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE draft_read=VALUES(draft_read),edit=VALUES(edit),publish=VALUES(publish),repeal=VALUES(repeal)',[$book,$authority,...$flags]);
            // Grants also activate the editorial feature; role rights remain necessary.
            if(array_sum($flags))$this->exec("INSERT IGNORE INTO kdd_authority_features_rel(authority_id,feature_id) SELECT ?,id FROM kdd_authority_features WHERE code='laws'",[$authority]);
            $this->audit('grant',['book_id'=>$book,'authority_id'=>$authority,'flags'=>$flags]);return ['ok'=>true];
        }
        $id=(int)($d['article_id']??0);
        if($id) { $article=$this->one('SELECT * FROM kdd_law_articles WHERE id=? FOR UPDATE',[$id]);if(!$article)throw new Error(404,'Paragraph nicht gefunden.');$book=(int)$article['book_id']; }
        else { $book=(int)($d['book_id']??0);$this->book($book);$article=null; }
        if(!$id&&$action!=='save_draft')throw new Error(422,'Paragraph fehlt.');
        $right=match($action){'save_draft','discard'=>'edit','publish'=>'publish','repeal'=>'repeal',default=>throw new Error(404,'Aktion nicht gefunden.')};
        $this->requireRight($book,$right);
        if($article&&(int)$article['revision']!==(int)($d['revision']??0))throw new Error(409,'Paragraph wurde inzwischen geändert. Bitte neu laden.');
        if($action==='save_draft') {
            $title=$this->text($d,'title',200);$chapter=$this->text($d,'chapter',160,false);$body=$this->text($d,'body',100000);$reason=$this->text($d,'reason',4000);
            if(!$article) { $number=$this->text($d,'number',40);if($this->one('SELECT id FROM kdd_law_articles WHERE book_id=? AND number=?',[$book,$number]))throw new Error(409,'Diese Paragraphennummer existiert bereits.');$this->exec('INSERT INTO kdd_law_articles(book_id,number) VALUES(?,?)',[$book,$number]);$id=(int)$this->db->lastInsertId(); }
            $draft=$this->one("SELECT id FROM kdd_law_versions WHERE article_id=? AND state='draft'",[$id]);
            if($draft)$this->exec('UPDATE kdd_law_versions SET title=?,chapter=?,body=?,reason=?,author_id=?,authority_id=?,created_at=UTC_TIMESTAMP() WHERE id=?',[$title,$chapter,$body,$reason,$this->actor['id'],$this->actor['authority_id'],$draft['id']]);
            else $this->exec('INSERT INTO kdd_law_versions(article_id,title,chapter,body,reason,author_id,authority_id) VALUES(?,?,?,?,?,?,?)',[$id,$title,$chapter,$body,$reason,$this->actor['id'],$this->actor['authority_id']]);
        } elseif($action==='discard') {
            $this->exec("DELETE FROM kdd_law_versions WHERE article_id=? AND state='draft'",[$id]);
        } elseif($action==='publish') {
            $draft=$this->one("SELECT * FROM kdd_law_versions WHERE article_id=? AND state='draft'",[$id]);if(!$draft)throw new Error(409,'Kein Entwurf vorhanden.');
            if($this->one("SELECT id FROM kdd_law_versions WHERE article_id=? AND state='published' AND effective_at>UTC_TIMESTAMP()",[$id]))throw new Error(409,'Es ist bereits eine zukünftige Fassung veröffentlicht.');
            $effective=gmdate('Y-m-d H:i:s');
            if(!empty($d['effective_at'])) { $date=\DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z',(string)$d['effective_at'],new \DateTimeZone('UTC'));if(!$date||$date->format('Y-m-d\TH:i:s\Z')!==$d['effective_at']||$date->getTimestamp()<time()-60)throw new Error(422,'Gültigkeitsdatum muss jetzt oder in der Zukunft liegen.');$effective=$date->format('Y-m-d H:i:s'); }
            $this->exec("UPDATE kdd_law_versions SET state='published',published_at=UTC_TIMESTAMP(),published_by=?,effective_at=? WHERE id=?",[$this->actor['id'],$effective,$draft['id']]);
        } elseif($action==='repeal') {
            $reason=$this->text($d,'reason',4000);$v=$this->one("SELECT * FROM kdd_law_versions WHERE article_id=? AND state='published' AND effective_at<=UTC_TIMESTAMP() ORDER BY effective_at DESC,id DESC LIMIT 1",[$id]);
            if(!$v||$v['repealed_at'])throw new Error(409,'Keine geltende Fassung vorhanden.');
            if($this->one("SELECT id FROM kdd_law_versions WHERE article_id=? AND state='published' AND effective_at>UTC_TIMESTAMP()",[$id]))throw new Error(409,'Eine zukünftige Fassung ist bereits veröffentlicht. Zuerst deren Gültigkeit abwarten.');
            $this->exec('UPDATE kdd_law_versions SET repealed_at=UTC_TIMESTAMP(),repeal_reason=?,repealed_by=? WHERE id=?',[$reason,$this->actor['id'],$v['id']]);
        }
        $this->exec('UPDATE kdd_law_articles SET revision=revision+1 WHERE id=?',[$id]);$this->audit($action,['article_id'=>$id,'book_id'=>$book,'reason'=>$d['reason']??null]);return ['id'=>$id];
    }
}
