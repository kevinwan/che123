<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use DB;
use Log;

class RecommendCarToAllUsers extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'recommend:all';

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
        //promo_car 
        $promoCars = $connection->table('promo_car')
            ->where('status', 'sale')->orderBy('id', 'desc')->get();
        $uniqueCityCars = [];
        foreach ($promoCars as $car) {
            $beginTime = $car->begin_time;
            $lastDays = $car->last_days;
            $city = $car->city;
            if (strtotime($beginTime)+$lastDays*3600*24 > time()+3600) {
                if (!isset($uniqueCityCars[$city])) {
                    $uniqueCityCars[$city] = $car;
                }
            }
        }
        //device_code
        $deviceCodes = $connection->table('user_login_record')
            ->select(DB::raw('distinct device_code, city'))
            ->whereNull('user_id')
            ->where('device_code', '<>', '')
            ->where('city', '<>', '')
//             ->whereIn('device_code', ['ffffffff-f14c-861f-bdaa-c8ac0033c587','f81119e53dc56cc945bb38b466ea92155a867ce0'])
            ->get();
        $deviceCodes = array_filter($deviceCodes);
        
        foreach ($uniqueCityCars as $car) {
            $id = $car->id;
            $carCity = $car->city;
            foreach ($deviceCodes as $device) {
                $deviceCode = $device->device_code;
                $deviceCity = $device->city;
                if ($carCity == $deviceCity) {
                    $device_type = $connection->table('mobile_access_operation')->where('device_code', $deviceCode)->pluck('device');
                    $arr['job_name'] = 'hctj-2';
                    $arr['job_content'] = json_encode([
                        'promo_id' => $id,
                        'device_code' => $deviceCode,
                        'device_type' => $device_type
                    ]);
                    $arr['created_on'] = date('Y-m-d H:i:s');
                    $insertData[] = $arr;
                }

            }
        }
        $connection->table('push_record')->insert($insertData);
        Log::info('recommend:all execute success');
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
