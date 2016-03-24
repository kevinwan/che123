<?php namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {

	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		'App\Console\Commands\Inspire',
        'App\Console\Commands\Lab',
        'App\Console\Commands\RecommendCar',
        'App\Console\Commands\RecommendCarToAllUsers',
	];
	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
// 		$schedule->command('recommend:car')->weeklyOn(4, '17:04');
// 		$schedule->command('recommend:all')->weeklyOn(4, '17:04');

//        $schedule->command('recommend:car')->cron('* * * * *');
//        $schedule->command('recommend:all')->cron('* * * * *');
    }
 }