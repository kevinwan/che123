<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Input;
use DB;

class RecommendCarController extends Controller
{

    public function index(Request $request)
    {
        $this->validate($request, [
           'car_id' => 'required',
           'job_code' => 'required'
        ]);
        $connection = DB::reconnect('pingjia');
        $car_id = Input::get('car_id');
        $job_code = Input::get('job_code');
        //鏍规嵁car_id鑾峰彇model_slug
        $model_slug = $connection->table('car_source')->where('id', $car_id)->pluck('model_slug');
        if(is_null($model_slug)) {
            exit('model_slug is null');
        }
        //like_car_record鑱旇〃car_source鍙栫浉鍚宮odel_slug鐨勮褰�
        $likeRecords = $connection->select('select * from like_car_record left join car_source on like_car_record.car_id=car_source.id
          where car_source.model_slug=? group by device_code',[$model_slug]);
        //view_car_source鑱旇〃car_source鍙栫浉鍚宮odel_slug鐨勮褰�
        $viewRecords = $connection->select('select * from view_car_source left join car_source on view_car_source.car_id=car_source.id
          where car_source.model_slug=? group by device_code',[$model_slug]);
        //鍚堝苟涓旀牴鎹澶囩爜鍘婚噸
        foreach($likeRecords as &$likeRecord) {
            $likeMaps[$likeRecord->device_code] = 1;
            $likeRecord = get_object_vars($likeRecord);
        }
        $records = $likeRecords;
        foreach($viewRecords as $viewRecord) {
            if(!isset($likeMaps[$viewRecord->device_code])) {
                $records[] = get_object_vars($viewRecord);
            }
        }
        //娑堟伅鍏ュ簱
        foreach($records as $record) {
            $device_type = $connection->table('mobile_access_operation')->where('device_code', $record['device_code'])->pluck('device');
            if(is_null($device_type)) {
                $device_type = '';
            }
            $insert_data['created_on'] = date("Y-m-d H:i:s");
            $insert_data['job_name'] = $job_code;
            $insert_data['job_content'] = json_encode([
                'car_id' => $record['car_id'],
                'device_code' => $record['device_code'],
                'device_type' => $device_type
            ]);
            $connection->table('push_record')->insert($insert_data);
        }
    }
}