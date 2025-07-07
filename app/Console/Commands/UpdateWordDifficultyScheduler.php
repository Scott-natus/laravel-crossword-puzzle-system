<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PzWord;
use App\Services\GeminiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateWordDifficultyScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'puzzle:update-word-difficulty {--limit=50 : 한 번에 처리할 단어 수} {--dry-run : 실제 업데이트하지 않고 테스트만}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '기존 단어들의 난이도를 Gemini API로 일괄 업데이트하는 스케줄러';

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

        $logMessage = "🚀 단어 난이도 업데이트 스케줄러 시작 - " . now()->format('Y-m-d H:i:s');
        $this->info($logMessage);
        $this->writeToLog($logMessage);

        $this->info("📊 처리할 단어 수: {$limit}개");
        $this->info("🧪 테스트 모드: " . ($dryRun ? '예' : '아니오'));

        // 업데이트 대상 단어 조회
        $wordsToUpdate = DB::table('tmp_pz_word_difficulty as tmp')
            ->join('pz_words as pw', 'tmp.id', '=', 'pw.id')
            ->where('tmp.update_yn', 'n')
            ->where('pw.is_active', true)
            ->select('pw.id', 'pw.word', 'pw.category')
            ->limit($limit)
            ->get();

        if ($wordsToUpdate->isEmpty()) {
            $logMessage = "✅ 업데이트할 단어가 없습니다!";
            $this->info($logMessage);
            $this->writeToLog($logMessage);
            return 0;
        }

        $logMessage = "📝 업데이트 대상 단어: {$wordsToUpdate->count()}개 발견";
        $this->info($logMessage);
        $this->writeToLog($logMessage);

        if ($dryRun) {
            $this->info("🧪 테스트 모드 - 실제 업데이트하지 않습니다.");
            $this->table(
                ['ID', '단어', '카테고리', '현재 난이도'],
                $wordsToUpdate->map(function ($word) {
                    return [
                        $word->id,
                        $word->word,
                        $word->category,
                        PzWord::find($word->id)->difficulty ?? 'N/A'
                    ];
                })->toArray()
            );
            return 0;
        }

        // 진행 상황 표시
        $progressBar = $this->output->createProgressBar($wordsToUpdate->count());
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        // 단어들을 50개씩 묶어서 처리
        $wordChunks = $wordsToUpdate->chunk(50);
        
        foreach ($wordChunks as $chunk) {
            try {
                $result = $this->updateWordDifficulties($chunk);
                
                if ($result['success']) {
                    $successCount += $result['updated_count'];
                    $errorCount += $result['error_count'];
                    $errors = array_merge($errors, $result['errors']);
                } else {
                    $errorCount += $chunk->count();
                    $errors[] = $result['error'];
                }
                
                $progressBar->advance($chunk->count());
                
                // API 호출 간격 조절 (3초 대기)
                sleep(3);
                
            } catch (\Exception $e) {
                $errorCount += $chunk->count();
                $errorMsg = "청크 처리 중 오류: " . $e->getMessage();
                $errors[] = $errorMsg;
                $this->writeToLog($errorMsg);
                $progressBar->advance($chunk->count());
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // 결과 출력
        $resultMessage = "📊 난이도 업데이트 완료! 성공: {$successCount}개, 실패: {$errorCount}개";
        $this->info("📊 난이도 업데이트 완료!");
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
        $totalWords = DB::table('tmp_pz_word_difficulty')->count();
        $updatedWords = DB::table('tmp_pz_word_difficulty')->where('update_yn', 'y')->count();
        $remainingWords = $totalWords - $updatedWords;

        $statsMessage = "📈 전체 통계 - 전체: {$totalWords}, 업데이트완료: {$updatedWords}, 남은단어: {$remainingWords}";
        $this->writeToLog($statsMessage);

        $this->newLine();
        $this->info("📈 전체 통계:");
        $this->table(
            ['구분', '개수', '비율'],
            [
                ['전체 단어', $totalWords, '100%'],
                ['업데이트 완료', $updatedWords, round(($updatedWords / $totalWords) * 100, 1) . '%'],
                ['남은 단어', $remainingWords, round(($remainingWords / $totalWords) * 100, 1) . '%'],
            ]
        );

        return 0;
    }

    /**
     * 단어 난이도 일괄 업데이트
     */
    private function updateWordDifficulties($words)
    {
        try {
            // 단어 목록을 쉼표로 구분된 문자열로 변환
            $wordList = $words->pluck('word')->implode(',');
            
            $prompt = "아래 단어들을 십자낱말 퀴즈에 제출한다고 가정할 때, 각 단어의 난이도를 1~5 숫자로 평가해줘.

단어 목록: \"{$wordList}\"

난이도 기준:
1: 매우 쉬움 (초등학생도 쉽게 알 수 있는 단어)
2: 쉬움 (일반인들이 쉽게 알 수 있는 단어)
3: 보통 (일반적인 지식을 가진 사람이 알 수 있는 단어)
4: 어려움 (전문 지식이 필요한 단어)
5: 매우 어려움 (전문가 수준의 고급 단어)

응답 형식: [단어,난이도] 형태로 한 줄에 하나씩
예시:
단어1,3
단어2,4
단어3,2
...";

            $requestData = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 1024,
                ]
            ];

            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . config('services.gemini.api_key');
            $response = Http::timeout(60)->post($url, $requestData);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $text = $data['candidates'][0]['content']['parts'][0]['text'];
                    $this->writeToLog("Gemini 응답: " . $text);
                    
                    return $this->processDifficultyResponse($text, $words);
                }
            }
            
            return [
                'success' => false,
                'error' => 'API 응답 실패',
                'updated_count' => 0,
                'error_count' => $words->count(),
                'errors' => ['API 응답 실패']
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => '서비스 오류: ' . $e->getMessage(),
                'updated_count' => 0,
                'error_count' => $words->count(),
                'errors' => ['서비스 오류: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * 난이도 응답 처리
     */
    private function processDifficultyResponse($text, $words)
    {
        $updatedCount = 0;
        $errorCount = 0;
        $errors = [];
        
        $lines = preg_split('/\r?\n|\r/', $text);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;
            
            // [단어,난이도] 형식 파싱
            if (preg_match('/^([^,]+),(\d+)$/', $line, $matches)) {
                $word = trim($matches[1]);
                $difficulty = (int)$matches[2];
                
                // 난이도 범위 검증
                if ($difficulty < 1 || $difficulty > 5) {
                    $errorCount++;
                    $errors[] = "단어 '{$word}': 난이도 범위 오류 ({$difficulty})";
                    continue;
                }
                
                // 해당 단어 찾기
                $wordRecord = $words->first(function($w) use ($word) {
                    return $w->word === $word;
                });
                
                if ($wordRecord) {
                    try {
                        DB::beginTransaction();
                        
                        // pz_words 테이블 업데이트
                        PzWord::where('id', $wordRecord->id)->update(['difficulty' => $difficulty]);
                        
                        // tmp_pz_word_difficulty 테이블 업데이트
                        DB::table('tmp_pz_word_difficulty')
                            ->where('id', $wordRecord->id)
                            ->update(['update_yn' => 'y']);
                        
                        DB::commit();
                        
                        $updatedCount++;
                        $this->writeToLog("단어 '{$word}' 난이도 업데이트: {$difficulty}");
                        
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $errorCount++;
                        $errors[] = "단어 '{$word}': DB 업데이트 실패 - " . $e->getMessage();
                    }
                } else {
                    $errorCount++;
                    $errors[] = "단어 '{$word}': DB에서 찾을 수 없음";
                }
            } else {
                $errorCount++;
                $errors[] = "파싱 실패: {$line}";
            }
        }
        
        return [
            'success' => true,
            'updated_count' => $updatedCount,
            'error_count' => $errorCount,
            'errors' => $errors
        ];
    }

    /**
     * 로그 파일에 직접 기록
     */
    private function writeToLog($message)
    {
        $logFile = storage_path('logs/word-difficulty-update.log');
        $timestamp = now()->format('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}" . PHP_EOL;
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
