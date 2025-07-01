<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RestorePuzzleLevels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'puzzle:restore-levels';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore puzzle levels from origin table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('퍼즐 레벨 기본 데이터 복원을 시작합니다...');

        try {
            // puzzle_levels 테이블 비우기
            DB::table('puzzle_levels')->truncate();
            $this->info('기존 puzzle_levels 데이터를 삭제했습니다.');

            // puzzle_levels_origin에서 데이터 복사
            $originData = DB::table('puzzle_levels_origin')->get();
            
            if ($originData->isEmpty()) {
                $this->error('puzzle_levels_origin 테이블에 데이터가 없습니다.');
                return 1;
            }

            // 데이터 복원
            foreach ($originData as $row) {
                DB::table('puzzle_levels')->insert([
                    'level' => $row->level,
                    'level_name' => $row->level_name,
                    'word_count' => $row->word_count,
                    'word_difficulty' => $row->word_difficulty,
                    'hint_difficulty' => $row->hint_difficulty,
                    'intersection_count' => $row->intersection_count,
                    'time_limit' => $row->time_limit,
                    'updated_by' => 'system (restored)',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->info('기본 데이터가 성공적으로 복원되었습니다!');
            $this->info('총 ' . $originData->count() . '개의 레벨이 복원되었습니다.');

        } catch (\Exception $e) {
            $this->error('데이터 복원 중 오류가 발생했습니다: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
