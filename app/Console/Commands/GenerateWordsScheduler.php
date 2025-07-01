<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PzWord;
use App\Services\GeminiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateWordsScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'puzzle:generate-words-scheduler {--limit=20 : 한 번에 생성할 단어 수} {--category= : 특정 카테고리만 생성} {--dry-run : 실제 생성하지 않고 테스트만}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '재미나이 API를 활용하여 새로운 단어들을 자동으로 생성하는 스케줄러';

    private $geminiService;

    /**
     * 생성 가능한 카테고리 목록
     */
    private $availableCategories = [
        'IT', '자연과학', '의학/보건', '예술', '정치', '체육', 
        '사자성어', '사회', '인문', '문화', '공학', '경제'
    ];

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
        $category = $this->option('category');
        $dryRun = $this->option('dry-run');

        $logMessage = "🚀 단어 생성 스케줄러 시작 - " . now()->format('Y-m-d H:i:s');
        $this->info($logMessage);
        $this->writeToLog($logMessage);

        $this->info("📊 생성할 단어 수: {$limit}개");
        $this->info("🏷️ 대상 카테고리: " . ($category ?: '랜덤 선택'));
        $this->info("🧪 테스트 모드: " . ($dryRun ? '예' : '아니오'));

        // 카테고리 선택
        $targetCategory = $category ?: $this->selectRandomCategory();
        $this->info("🎯 선택된 카테고리: {$targetCategory}");

        // 재미나이 API로 단어 생성 요청
        $prompt = $this->buildPrompt($targetCategory, $limit);
        
        try {
            $result = $this->geminiService->generateWords($prompt);
            
            if (!$result['success']) {
                $this->error("❌ 재미나이 API 호출 실패: " . ($result['error'] ?? '알 수 없는 오류'));
                $this->writeToLog("재미나이 API 호출 실패: " . ($result['error'] ?? '알 수 없는 오류'));
                return 1;
            }

            $suggestedWords = $result['words'] ?? [];
            
            if (empty($suggestedWords)) {
                $this->warn("⚠️ 생성된 단어가 없습니다.");
                $this->writeToLog("생성된 단어가 없습니다.");
                return 0;
            }

            $this->info("📝 재미나이에서 제안한 단어: " . count($suggestedWords) . "개");

            if ($dryRun) {
                $this->info("🧪 테스트 모드 - 실제 저장하지 않습니다.");
                $this->table(
                    ['카테고리', '단어', '길이', '중복여부'],
                    $this->checkDuplicates($suggestedWords, $targetCategory)
                );
                return 0;
            }

            // 중복 체크 및 저장
            $newWords = $this->processAndSaveWords($suggestedWords, $targetCategory);

            // 결과 출력
            $this->info("📊 단어 생성 완료!");
            $this->info("✅ 새로 추가된 단어: " . count($newWords) . "개");
            $this->info("⏭️ 중복으로 스킵된 단어: " . (count($suggestedWords) - count($newWords)) . "개");

            if (!empty($newWords)) {
                $this->table(
                    ['카테고리', '단어', '길이'],
                    array_map(function($word) {
                        return [$word['category'], $word['word'], mb_strlen($word['word'])];
                    }, $newWords)
                );
            }

            // 통계 정보
            $totalWords = PzWord::active()->count();
            $categoryWords = PzWord::active()->where('category', $targetCategory)->count();
            
            $statsMessage = "📈 카테고리 통계 - {$targetCategory}: {$categoryWords}개 (전체: {$totalWords}개)";
            $this->writeToLog($statsMessage);

            $this->newLine();
            $this->info("📈 카테고리 통계:");
            $this->table(
                ['카테고리', '단어 수', '전체 대비'],
                [
                    [$targetCategory, $categoryWords, round(($categoryWords / $totalWords) * 100, 1) . '%'],
                    ['전체', $totalWords, '100%'],
                ]
            );

        } catch (\Exception $e) {
            $this->error("❌ 단어 생성 중 오류 발생: " . $e->getMessage());
            $this->writeToLog("단어 생성 중 오류 발생: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * 랜덤 카테고리 선택
     */
    private function selectRandomCategory()
    {
        return $this->availableCategories[array_rand($this->availableCategories)];
    }

    /**
     * 재미나이 API용 프롬프트 생성
     */
    private function buildPrompt($category, $limit)
    {
        return "다음 조건에 맞는 {$limit}개의 단어를 추천해주세요:

분야: {$category}
조건:
1. 명사, 고유명사 포함
2. 영어 제외 (한글만)
3. 한 글자 제외, 두 글자에서 네 글자 사이

응답 형식:
[{$category},단어1]
[{$category},단어2]
[{$category},단어3]
...

예시:
[{$category},단어]
[{$category},용어]
[{$category},개념]

각 줄에 하나씩, 총 {$limit}개를 제시해주세요.

주의: 반드시 [카테고리,단어] 형식으로만 응답해주세요.";
    }

    /**
     * 중복 체크 (테스트용)
     */
    private function checkDuplicates($suggestedWords, $category)
    {
        $result = [];
        
        foreach ($suggestedWords as $wordData) {
            $word = $wordData['word'] ?? '';
            $isDuplicate = PzWord::where('word', $word)
                ->where('category', $category)
                ->exists();
                
            $result[] = [
                $category,
                $word,
                mb_strlen($word),
                $isDuplicate ? '중복' : '신규'
            ];
        }
        
        return $result;
    }

    /**
     * 단어 처리 및 저장
     */
    private function processAndSaveWords($suggestedWords, $category)
    {
        $newWords = [];
        
        foreach ($suggestedWords as $wordData) {
            $word = trim($wordData['word'] ?? '');
            
            // 유효성 검사
            if (empty($word) || mb_strlen($word) < 2 || mb_strlen($word) > 4) {
                continue;
            }
            
            // 중복 체크
            $exists = PzWord::where('word', $word)
                ->where('category', $category)
                ->exists();
                
            if ($exists) {
                $this->writeToLog("중복 단어 스킵: [{$category}, {$word}]");
                continue;
            }
            
            // 새 단어 저장
            try {
                $newWord = PzWord::create([
                    'word' => $word,
                    'category' => $category,
                    'difficulty' => 2, // 기본 난이도
                    'is_active' => true,
                ]);
                
                $newWords[] = [
                    'category' => $category,
                    'word' => $word,
                    'id' => $newWord->id
                ];
                
                $this->writeToLog("새 단어 추가: [{$category}, {$word}] (ID: {$newWord->id})");
                
            } catch (\Exception $e) {
                $this->writeToLog("단어 저장 실패: [{$category}, {$word}] - " . $e->getMessage());
            }
        }
        
        return $newWords;
    }

    /**
     * 로그 파일에 직접 기록
     */
    private function writeToLog($message)
    {
        $logFile = storage_path('logs/word-scheduler.log');
        $timestamp = now()->format('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}" . PHP_EOL;
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
} 