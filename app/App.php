<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class App extends Model {

	public function category()
    {
        return $this->belongsTo('App\Category');
    }

    /*
     * APP数据处理
     */
    public static function dataHandle($data)
    {
        $client_type = self::isIosOrAndroid();
        //判断是否数组
        if(isset($data->id)) $data = self::_handle($data, $client_type);
        else {
            foreach($data as $info) {
                self::_handle($info, $client_type);
            }
        }
        return $data;
    }

    private static function _handle($data, $client_type) {
        if(isset($data->$client_type))  $data->url = $data->$client_type;
        if(isset($data->download) && $data->download>=10000){
            $data->downloadShow = round($data->download/10000, 1).'万';
        } else $data->downloadShow = $data->download;

        return $data;
    }

    /*
     * 判断手机系统
     */
    private static function isIosOrAndroid()
    {
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if(strpos($agent, 'iPhone') || strpos($agent, 'iPad') || strpos($agent, 'Mac')) {
            return 'ios';
        }
        return 'android';
    }

}
