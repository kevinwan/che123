<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use DB;
use Log;

class RecommendCar extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'recommend:car';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
        $connection = DB::reconnect('pingjia');
        //like_car_record联表car_source取model_slug
        $likeRecords = $connection->table('like_car_record')
            ->leftJoin('car_source', 'like_car_record.car_id', '=', 'car_source.id')
            ->leftJoin('user_login_record', 'like_car_record.device_code', '=', 'user_login_record.device_code')
            ->select('user_login_record.city', 'car_source.model_slug', 'like_car_record.device_code', 'like_car_record.create_time')
            ->where('like_car_record.status', 'like')
//             ->whereIn('like_car_record.device_code', ['ffffffff-f14c-861f-bdaa-c8ac0033c587','f81119e53dc56cc945bb38b466ea92155a867ce0'])
            ->get();
        //view_car_source联表car_source取model_slug
        $viewRecords = $connection->table('view_car_source')
            ->leftJoin('car_source', 'view_car_source.car_id', '=', 'car_source.id')
            ->leftJoin('user_login_record', 'view_car_source.device_code', '=', 'user_login_record.device_code')
            ->select('user_login_record.city', 'car_source.model_slug', 'view_car_source.device_code', 'view_car_source.create_time')
            ->where('view_car_source.status', 'enable')
//             ->whereIn('view_car_source.device_code', ['ffffffff-f14c-861f-bdaa-c8ac0033c587','f81119e53dc56cc945bb38b466ea92155a867ce0'])
            ->get();
        //合并且根据设备码去重
        $records = array_merge($likeRecords, $viewRecords);
        $uniqueDeviceCodeRecord = [];
        foreach ($records as $record) {
            $device_code = $record->device_code;
            if(!is_null($device_code) && !is_null($record->city) && !is_null($record->model_slug)
                    && (!isset($uniqueDeviceCodeRecord[$device_code])
                            || strtotime($record->create_time) > strtotime($uniqueDeviceCodeRecord[$device_code]->create_time))) {
                $uniqueDeviceCodeRecord[$device_code] = $record;
            }
        }
        $uniqueModelSlugCityRecord = [];
        //合并相同的city跟model_slug
        foreach ($uniqueDeviceCodeRecord as $record) {
            $key = $record->city. '___'. $record->model_slug;
            $uniqueModelSlugCityRecord[$key][] = $record->device_code;
        }
        //查询数据库
        foreach ($uniqueModelSlugCityRecord as $key => $deviceCodes) {
            $citySlugArray = explode('___', $key);
            $modelSlug = $citySlugArray[1];
            $city = $citySlugArray[0];
            $carSource = $connection->table('car_source')->where('model_slug', $modelSlug)
                ->where('city', $city)
                ->whereIn('source_type', ['cpersonal','cpo','dealer'])
                ->where('status', 'sale')
                ->orderBy('pub_time', 'desc')
                ->first();
            foreach ($deviceCodes as $deviceCode) {
                $device_type = $connection->table('mobile_access_operation')->where('device_code', $deviceCode)->pluck('device');
                $arr['job_name'] = 'hctj-1';
                $arr['job_content'] = json_encode([
                    'car_id' => $carSource->id,
                    'device_code' => $deviceCode,
                    'device_type' => $device_type
                ]);
                $arr['created_on'] = date('Y-m-d H:i:s');
                $insertData[] = $arr;
            }
        }
        $connection->table('push_record')->insert($insertData);
        Log::info('recommend:car execute success');
    }

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [
			['example', InputArgument::OPTIONAL, 'An example argument.'],
		];
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return [
			['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
		];
	}

}
