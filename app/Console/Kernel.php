<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        
        // 10분마다 힌트가 없는 단어 50개씩 자동 생성
        $schedule->command('puzzle:generate-hints-scheduler --limit=50')
                ->everyTenMinutes()
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/hint-scheduler.log'));
        
        // 매시간 5,15,25,35,45,55분마다 새로운 단어 20개씩 자동 생성 (힌트는 기존 스케줄러가 자동 생성)
        $schedule->command('puzzle:generate-words-scheduler --limit=20')
                ->cron('5,15,25,35,45,55 * * * *')
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/word-scheduler.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
