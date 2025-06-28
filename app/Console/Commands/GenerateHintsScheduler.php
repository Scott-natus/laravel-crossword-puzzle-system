<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PzWord;
use App\Services\GeminiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateHintsScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'puzzle:generate-hints-scheduler {--limit=100 : 한 번에 처리할 단어 수} {--dry-run : 실제 생성하지 않고 테스트만}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '힌트가 없는 단어들을 자동으로 생성하는 스케줄러';

    private $geminiService;

    /**
     * Create a new command instance.
     */
    public function __construct(GeminiService $geminiService)
    {
        parent::__construct();
        $this->geminiService = $geminiService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        $logMessage = "🚀 힌트 생성 스케줄러 시작 - " . now()->format('Y-m-d H:i:s');
        $this->info($logMessage);
        $this->writeToLog($logMessage);

        $this->info("📊 처리할 단어 수: {$limit}개");
        $this->info("🧪 테스트 모드: " . ($dryRun ? '예' : '아니오'));

        // 힌트가 없는 단어들 조회
        $wordsWithoutHints = PzWord::active()
            ->doesntHave('hints')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        if ($wordsWithoutHints->isEmpty()) {
            $logMessage = "✅ 모든 단어에 힌트가 생성되어 있습니다!";
            $this->info($logMessage);
            $this->writeToLog($logMessage);
            return 0;
        }

        $logMessage = "📝 힌트가 없는 단어: {$wordsWithoutHints->count()}개 발견";
        $this->info($logMessage);
        $this->writeToLog($logMessage);

        if ($dryRun) {
            $this->info("🧪 테스트 모드 - 실제 생성하지 않습니다.");
            $this->table(
                ['ID', '단어', '카테고리', '난이도'],
                $wordsWithoutHints->map(function ($word) {
                    return [
                        $word->id,
                        $word->word,
                        $word->category,
                        $word->difficulty_text
                    ];
                })->toArray()
            );
            return 0;
        }

        // 진행 상황 표시
        $progressBar = $this->output->createProgressBar($wordsWithoutHints->count());
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($wordsWithoutHints as $word) {
            try {
                $result = $this->geminiService->generateHint(
                    $word->word,
                    $word->category
                );

                if ($result['success']) {
                    // 기존 힌트 삭제
                    $word->hints()->delete();

                    // 사용빈도 정보로 단어 난이도 업데이트
                    if (isset($result['frequency']) && $result['frequency'] !== null) {
                        $word->update(['difficulty' => $result['frequency']]);
                    }

                    // 세 가지 난이도의 힌트를 모두 저장
                    foreach ($result['hints'] as $difficulty => $hintData) {
                        if ($hintData['success']) {
                            // 난이도 매핑 (1,2,3 -> easy,medium,hard)
                            $difficultyMap = [
                                1 => 'easy',
                                2 => 'medium', 
                                3 => 'hard'
                            ];
                            
                            $word->hints()->create([
                                'hint_text' => $hintData['hint'],
                                'hint_type' => 'text',
                                'difficulty' => $difficultyMap[$difficulty] ?? 'medium',
                                'is_primary' => ($difficulty == $word->difficulty),
                            ]);
                        }
                    }

                    $successCount++;
                    $this->writeToLog("힌트 생성 성공 - 단어: {$word->word}, 카테고리: {$word->category}");
                } else {
                    $errorCount++;
                    $errorMsg = "단어 '{$word->word}': " . ($result['error'] ?? '알 수 없는 오류');
                    $errors[] = $errorMsg;
                    $this->writeToLog($errorMsg);
                }
            } catch (\Exception $e) {
                $errorCount++;
                $errorMsg = "단어 '{$word->word}': " . $e->getMessage();
                $errors[] = $errorMsg;
                $this->writeToLog("힌트 생성 실패 - 단어 ID: {$word->id}, 오류: " . $e->getMessage());
            }

            $progressBar->advance();

            // API 호출 간격 조절 (3초 대기)
            sleep(3);
        }

        $progressBar->finish();
        $this->newLine(2);

        // 결과 출력
        $resultMessage = "📊 힌트 생성 완료! 성공: {$successCount}개, 실패: {$errorCount}개";
        $this->info("📊 힌트 생성 완료!");
        $this->info("✅ 성공: {$successCount}개");
        $this->info("❌ 실패: {$errorCount}개");
        $this->writeToLog($resultMessage);

        if (!empty($errors)) {
            $this->warn("⚠️ 실패한 단어들:");
            foreach (array_slice($errors, 0, 10) as $error) { // 최대 10개만 표시
                $this->line("  - {$error}");
            }
            if (count($errors) > 10) {
                $this->line("  ... 외 " . (count($errors) - 10) . "개");
            }
        }

        // 통계 정보
        $totalWords = PzWord::active()->count();
        $wordsWithHints = PzWord::active()->whereHas('hints')->count();
        $wordsWithoutHints = PzWord::active()->doesntHave('hints')->count();

        $statsMessage = "📈 전체 통계 - 전체: {$totalWords}, 힌트보유: {$wordsWithHints}, 힌트없음: {$wordsWithoutHints}";
        $this->writeToLog($statsMessage);

        $this->newLine();
        $this->info("📈 전체 통계:");
        $this->table(
            ['구분', '개수', '비율'],
            [
                ['전체 단어', $totalWords, '100%'],
                ['힌트 보유', $wordsWithHints, round(($wordsWithHints / $totalWords) * 100, 1) . '%'],
                ['힌트 없음', $wordsWithoutHints, round(($wordsWithoutHints / $totalWords) * 100, 1) . '%'],
            ]
        );

        return 0;
    }

    /**
     * 로그 파일에 직접 기록
     */
    private function writeToLog($message)
    {
        $logFile = storage_path('logs/hint-scheduler.log');
        $timestamp = now()->format('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}" . PHP_EOL;
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
