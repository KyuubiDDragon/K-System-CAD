<?php
declare(strict_types=1);
/** Tenant and delegation boundaries shared by role create/update/delete. */
final class RoleGuard {
    public static function validate(PDO $pdo,int $actor,int $authority,?int $role,array $permissions,array $categories): void {
        $q=$pdo->prepare('SELECT DISTINCT p.id,p.module FROM kdd_user_roles ur JOIN kdd_roles r ON r.id=ur.role_id AND r.authority_id=ur.authority_id AND r.is_deleted=0 JOIN kdd_role_permissions rp ON rp.role_id=r.id AND rp.authority_id=r.authority_id JOIN kdd_permissions p ON p.id=rp.permission_id WHERE ur.user_id=? AND ur.authority_id=?');
        $q->execute([$actor,$authority]);$own=$q->fetchAll(PDO::FETCH_ASSOC);$system=in_array('system',array_column($own,'module'),true);
        if($role) {
            $q=$pdo->prepare('SELECT id FROM kdd_roles WHERE id=? AND authority_id=? AND is_deleted=0 FOR UPDATE');$q->execute([$role,$authority]);
            if(!$q->fetchColumn())throw new DomainException('Diese Rolle gehört nicht zum aktuellen Mandanten.');
            $q=$pdo->prepare('SELECT permission_id FROM kdd_role_permissions WHERE role_id=? AND authority_id=?');$q->execute([$role,$authority]);$existing=$q->fetchAll(PDO::FETCH_COLUMN);
            if(!$system&&array_diff($existing,array_column($own,'id')))throw new DomainException('Eine höher berechtigte Rolle darf nicht verändert werden.');
        }
        if(!$system&&array_diff($permissions,array_column($own,'id')))throw new DomainException('Es dürfen nur eigene Berechtigungen weitergegeben werden.');
        foreach($permissions as $id) {
            $q=$pdo->prepare('SELECT id FROM kdd_permissions WHERE id=?');$q->execute([$id]);if(!$q->fetchColumn())throw new DomainException('Unbekannte Berechtigung.');
        }
        foreach($categories as $id) {
            $q=$pdo->prepare('SELECT id FROM kdd_report_category WHERE id=? AND authority_id=?');$q->execute([$id,$authority]);if(!$q->fetchColumn())throw new DomainException('Kategorie gehört nicht zum aktuellen Mandanten.');
        }
    }
}
