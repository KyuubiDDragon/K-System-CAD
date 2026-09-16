<?php
declare(strict_types=1);
namespace Kyuubi\Social;
final class Storage {
    public static function root():string { return (string)\getEnvVar('SOCIAL_STORAGE_PATH','/var/www/social-private'); }
    private static function client():\Aws\S3\S3Client {
        $args=['version'=>'latest','region'=>\getEnvVar('SOCIAL_S3_REGION','us-east-1'),'use_path_style_endpoint'=>true,'credentials'=>['key'=>\getEnvVar('SOCIAL_S3_KEY'),'secret'=>\getEnvVar('SOCIAL_S3_SECRET')]];
        if(\getEnvVar('SOCIAL_S3_ENDPOINT'))$args['endpoint']=\getEnvVar('SOCIAL_S3_ENDPOINT');return new \Aws\S3\S3Client($args);
    }
    public static function put(string $file,string $key,string $mime):string {
        $provider=\getEnvVar('SOCIAL_STORAGE','local');
        if($provider==='s3'){self::client()->putObject(['Bucket'=>\getEnvVar('SOCIAL_S3_BUCKET'),'Key'=>$key,'SourceFile'=>$file,'ContentType'=>$mime]);return 's3';}
        if(!is_dir(self::root())&&!mkdir(self::root(),0700,true))throw new \RuntimeException('Storage directory unavailable');
        if(!copy($file,self::root().'/'.$key))throw new \RuntimeException('Storage write failed');return 'local';
    }
    public static function local(array $m):string {
        if($m['storage']==='local')return self::root().'/'.$m['storage_key'];
        $path=tempnam(sys_get_temp_dir(),'social-');self::client()->getObject(['Bucket'=>\getEnvVar('SOCIAL_S3_BUCKET'),'Key'=>$m['storage_key'],'SaveAs'=>$path]);return $path;
    }
    public static function delete(array $m):void {
        if($m['storage']==='s3')self::client()->deleteObject(['Bucket'=>\getEnvVar('SOCIAL_S3_BUCKET'),'Key'=>$m['storage_key']]);
        elseif(is_file(self::root().'/'.$m['storage_key']))unlink(self::root().'/'.$m['storage_key']);
    }
}
