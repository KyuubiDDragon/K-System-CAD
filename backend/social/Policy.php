<?php
declare(strict_types=1);
namespace Kyuubi\Social;
final class Policy {
    public static function visible(string $visibility, bool $owner, bool $blocked, bool $friend, bool $mutual): bool {
        if ($owner) return true;
        if ($blocked) return false;
        return $visibility === 'public' || ($visibility === 'friends' && $friend) || ($visibility === 'friends_of_friends' && ($friend || $mutual));
    }
    public static function overlap(string $start, string $end, string $otherStart, string $otherEnd): bool {
        return $start < $otherEnd && $end > $otherStart;
    }
    public static function defaults(): array {
        return [
            'theme_colors'=>['light'=>[],'dark'=>[]], 'community'=>'San Andreas', 'operator_name'=>'', 'accent'=>'#087f80', 'icon_set'=>'coastal', 'background_id'=>null, 'logo_id'=>null, 'header_id'=>null,
            'registration'=>'manual', 'registration_hint'=>'Melde dich zur Freigabe bei der Betreiberfirma.', 'guest'=>false,
            'modules'=>['social'=>true,'gram'=>true,'market'=>true,'video'=>true,'messages'=>true],
            'names'=>['social'=>'Social Media','gram'=>'Gram','market'=>'Marktplatz','video'=>'Video','companies'=>'Unternehmen'],
            'post_categories'=>['Allgemein','Neuigkeiten','Veranstaltungen','Fragen','Unternehmen','Freizeit'], 'icons'=>[], 'links'=>[], 'info_blocks'=>[], 'upload_mb'=>20, 'video_mb'=>200, 'video_seconds'=>600, 'quota_mb'=>1024,
            'accept_template'=>'Guten Tag {name}, Ihre Werbung „{title}“ wurde für {start} bis {end} angenommen. Offen: {amount} RP-Dollar. {instructions}',
            'reject_template'=>'Guten Tag {name}, Ihre Werbung „{title}“ wurde abgelehnt: {reason}',
            'payment_instructions'=>'Bitte melde dich bei der Betreiberfirma.',
        ];
    }
}
