<?php namespace App;
//require __DIR__.'/../vendor/Qiniu/Auth.php';
//require __DIR__.'/../vendor/Qiniu/Storage/UploadManager.php';

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

Class FileHandler {

    public static function uploadPic($filePath)
    {
        // 设置信息
        $APP_ACCESS_KEY = 'VDo2clWr4g7DJ2d1S8h_8W17d2RzmMdrywI-TiBm';
        $APP_SECRET_KEY = 'H7Axjej_QhlpgbAry4rVNyoBOnNj9etSfWYcHXi7';
        $bucket = 'che123';

        $auth = new Auth($APP_ACCESS_KEY, $APP_SECRET_KEY);
        $token = $auth->uploadToken($bucket);
        $uploadManager = new UploadManager();

        $name = time().'_'.rand().'.png';
        list($ret, $err) = $uploadManager->putFile($token, 'app_ico/'.$name, $filePath);

        if ($err != null) {
            return ["success"=>false, "msg"=>"上传失败。错误消息：".$err->message()];
        } else {
            return ["success"=>true, "msg"=>"上传成功。Key：".$ret["key"], 'name'=>$name];
        }
    }

}