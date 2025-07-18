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
        
        // 5분마다 임시테이블의 단어들로 힌트 생성 (100개씩) - 일시 중지
        // $schedule->command('puzzle:generate-hints-from-temp-words --limit=100')
        //         ->everyFiveMinutes()
        //         ->withoutOverlapping()
        //         ->runInBackground()
        //         ->appendOutputTo(storage_path('logs/temp-words-hint-scheduler.log'));
        
        // 12시간마다 새로운 단어를 임시테이블에 추가하고, 1분마다 50개씩 처리 (일시 중지)
        // $schedule->command('puzzle:update-word-difficulty --continuous')
        //         ->everyMinute()
        //         ->withoutOverlapping()
        //         ->runInBackground()
        //         ->appendOutputTo(storage_path('logs/word-difficulty-update.log'));

        // 음절별 단어 수집 - 일시 중지
        // $schedule->command('puzzle:collect-words-from-syllables')
        //     ->everyMinute()
        //     ->withoutOverlapping()
        //     ->runInBackground()
        //     ->appendOutputTo(storage_path('logs/collect-words-scheduler.log'));
        
        // 매일 새벽 2시에 로그 로테이션 실행
        $schedule->call(function () {
            \Log::info('로그 로테이션 시작');
            
            // 현재 날짜로 새 로그 파일 생성
            $today = now()->format('Y-m-d');
            $logPath = storage_path('logs/laravel-' . $today . '.log');
            
            // 새 로그 파일이 없으면 생성
            if (!file_exists($logPath)) {
                touch($logPath);
                chmod($logPath, 0664);
                chown($logPath, 'www-data');
                chgrp($logPath, 'www-data');
                
                \Log::info('새 로그 파일 생성: ' . $logPath);
            }
            
            // 30일 이상 된 로그 파일 삭제
            $oldLogs = glob(storage_path('logs/laravel-*.log'));
            $cutoffDate = now()->subDays(30);
            
            foreach ($oldLogs as $logFile) {
                $fileDate = filemtime($logFile);
                if ($fileDate < $cutoffDate->timestamp) {
                    unlink($logFile);
                    \Log::info('오래된 로그 파일 삭제: ' . basename($logFile));
                }
            }
            
            \Log::info('로그 로테이션 완료');
        })->name('log-rotation')->dailyAt('02:00')->withoutOverlapping();
        
        // 기존 퍼즐 힌트 생성 스케줄러
        $schedule->command('puzzle:generate-hints-scheduler')->everyMinute()->withoutOverlapping();
        
        // 기존 단어 난이도 업데이트 스케줄러
        $schedule->command('puzzle:update-word-difficulty')->everyTenMinutes()->withoutOverlapping();
        
        // 매일 새벽 1시에 퍼즐 단어 정리 및 비활성화
        $schedule->command('puzzle:cleanup-words')
                ->dailyAt('01:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/word-cleanup.log'));
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
