<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PuzzleLevel;
use Illuminate\Support\Facades\Auth;

class GeneratePuzzleLevels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'puzzle:generate-levels';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate default puzzle levels data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('퍼즐 레벨 기본 데이터 생성을 시작합니다...');

        try {
            // 기존 데이터 삭제
            PuzzleLevel::truncate();
            $this->info('기존 데이터를 삭제했습니다.');
            
            $levels = [];
            
            // 레벨 1-100까지 기본 데이터 생성 (백업 데이터 패턴 기반)
            for ($level = 1; $level <= 100; $level++) {
                $levelName = PuzzleLevel::getLevelName($level);
                
                // 백업 데이터 패턴 기반 설정
                if ($level >= 1 && $level <= 10) {
                    // 실마리 발견자 (Clue Spotter)
                    $wordCount = 5 + floor(($level - 1) / 2); // 5,5,6,6,6,7,7,7,8,8
                    $wordDifficulty = 3;
                    $hintDifficulty = '쉬움';
                    $intersectionCount = 2;
                    $timeLimit = 0;
                } elseif ($level >= 11 && $level <= 25) {
                    // 단서 수집가 (Clue Collector)
                    $wordCount = 9 + floor(($level - 11) / 3); // 9,9,9,10,10,10,11,11,11,12,12,12,13,13,13
                    $wordDifficulty = ($level >= 21) ? 2 : 1; // 1,1,1,1,1,1,1,1,1,1,2,2,2,2,2
                    $hintDifficulty = '쉬움';
                    $intersectionCount = ($level >= 20) ? 4 : 3; // 3,3,3,3,3,3,3,3,3,3,4,4,4,4,4
                    $timeLimit = 900 - ($level - 11) * 7; // 900,893,887,880,873,867,860,853,847,840,833,827,820,813,807
                } elseif ($level >= 26 && $level <= 50) {
                    // 논리적 추적자 (Logical Tracer)
                    $wordCount = 14 + floor(($level - 26) / 3); // 14,14,14,15,15,15,16,16,16,17,17,17,18,18,18,19,19,19,20,20,20,21,21,21,22
                    $wordDifficulty = ($level >= 34) ? 3 : 2; // 2,2,2,2,2,2,2,2,2,2,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3
                    $hintDifficulty = ($level >= 34) ? '보통' : '쉬움';
                    $intersectionCount = ($level >= 31) ? 5 : 4; // 4,4,4,4,4,4,5,5,5,5,5,5,5,5,5,6,6,6,6,6,6,6,6,6,7
                    $timeLimit = 800 - ($level - 26) * 7; // 800,793,787,780,773,767,760,753,747,740,733,727,720,713,707,700,693,687,680,673,667,660,653,647,640
                } elseif ($level >= 51 && $level <= 75) {
                    // 패턴 인식자 (Pattern Recognizer)
                    $wordCount = 23 + floor(($level - 51) / 2); // 23,23,24,24,25,25,26,26,27,27,28,28,29,29,30,30,31,31,32,32,33,33,34,34,35
                    $wordDifficulty = 3 + floor(($level - 51) / 10); // 3,3,3,3,3,3,3,3,3,3,4,4,4,4,4,4,4,4,4,4,5,5,5,5,5
                    $hintDifficulty = ($level >= 61) ? '어려움' : '보통';
                    $intersectionCount = 7 + floor(($level - 51) / 5); // 7,7,7,7,7,8,8,8,8,8,9,9,9,9,9,10,10,10,10,10,11,11,11,11,11
                    $timeLimit = 633 - ($level - 51) * 7; // 633,626,620,613,607,600,593,587,580,573,567,560,553,547,540,533,527,520,513,507,500,493,487,480,473
                } elseif ($level >= 76 && $level <= 99) {
                    // 마스터 해결사 (Master Solver)
                    $wordCount = 36 + floor(($level - 76) / 2); // 36,36,37,37,38,38,39,39,40,40,41,41,42,42,43,43,44,44,45,45,46,46,47,47,48
                    $wordDifficulty = 4 + floor(($level - 76) / 10); // 4,4,4,4,4,4,4,4,4,4,5,5,5,5,5,5,5,5,5,5,6,6,6,6,6
                    $hintDifficulty = '어려움';
                    $intersectionCount = 12 + floor(($level - 76) / 5); // 12,12,12,12,12,13,13,13,13,13,14,14,14,14,14,15,15,15,15,15,16,16,16,16,16
                    $timeLimit = 466 - ($level - 76) * 7; // 466,459,453,446,440,433,427,420,413,407,400,393,387,380,373,367,360,353,347,340,333,327,320,313,307
                } else { // level 100
                    // 그랜드 마스터 (Grand Master)
                    $wordCount = 49;
                    $wordDifficulty = 5;
                    $hintDifficulty = '어려움';
                    $intersectionCount = 17;
                    $timeLimit = 300;
                }
                
                $levels[] = [
                    'level' => $level,
                    'level_name' => $levelName,
                    'word_count' => $wordCount,
                    'word_difficulty' => $wordDifficulty,
                    'hint_difficulty' => $hintDifficulty,
                    'intersection_count' => $intersectionCount,
                    'time_limit' => $timeLimit,
                    'updated_by' => 'system',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($level % 10 == 0) {
                    $this->info("레벨 {$level}까지 처리 완료");
                }
            }
            
            // 대량 삽입
            PuzzleLevel::insert($levels);
            
            $this->info('기본 데이터가 성공적으로 생성되었습니다!');
            $this->info('총 ' . count($levels) . '개의 레벨이 생성되었습니다.');
            
        } catch (\Exception $e) {
            $this->error('데이터 생성 중 오류가 발생했습니다: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
