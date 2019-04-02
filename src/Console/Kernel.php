<?php
namespace Cuatromedios\Kusikusi\Console;

use Cuatromedios\Kusikusi\Console\Commands\PostInstall;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

/**
 * Class Kernel
 *
 * @package Cuatromedios\Kusikusi\Console
 */
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        PostInstall::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }
}
