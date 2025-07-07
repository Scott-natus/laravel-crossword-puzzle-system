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
     * 생성 가능한 카테고리 목록 (데이터베이스에서 가져옴)
     */
    private function getAvailableCategories()
    {
        return DB::table('pz_base_categories')
            ->where('is_active', true)
            ->pluck('category')
            ->toArray();
    }

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
        
        // 음절 조건 표시
        $syllableCondition = $this->getSyllableCondition(now()->minute);
        $this->info("🔤 음절 조건: {$syllableCondition}");

        // 재미나이 API로 단어 생성 요청
        $prompt = $this->buildPrompt($targetCategory, $limit);
        
        // 선택된 요구사항 로그 기록
        $selectedRequirement = $this->getSelectedRequirement();
        $this->writeToLog("선택된 카테고리: {$targetCategory}");
        $this->writeToLog("생성 요구사항: " . $selectedRequirement);
        
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
        $categories = $this->getAvailableCategories();
        return $categories[array_rand($categories)];
    }

    /**
     * 십자낱말 퍼즐용 단어 생성 프롬프트
     */
    private function buildPrompt($category, $limit)
    {
        $selectedRequirement = $this->getSelectedRequirement();
        
        return "{$category}라는 카테고리 내에서 '{$selectedRequirement}' 2~5음절 단어를 {$limit}개 추천해줘

**중요: 쉬운 단어만 생성해주세요!**
- 초보자도 쉽게 알 수 있는 단어
- 일상생활에서 자주 사용하는 단어
- 어린이도 이해할 수 있는 단어
- 복잡하거나 전문적인 단어는 제외

다음 품사에 해당하는 단어만 생성해주세요:
- 명사 (일반명사)
- 대명사
- 고유명사 (인명, 지명, 회사명 등)
- 외래어 (영어, 일본어, 중국어 등에서 유래한 단어)
- 신조어 (새로 만들어진 단어나 최근 유행하는 단어)

동사, 형용사, 부사, 조사 등은 제외하고 위의 품사에 해당하는 단어만 생성해주세요.

한줄에 [카테고리,단어] 형태로 보여줘

예시:
[{$category},단어1]
[{$category},단어2]
[{$category},단어3]

각 줄에 하나씩, 총 {$limit}개를 제시해주세요.

주의: 반드시 [카테고리,단어] 형식으로만 응답해주세요.";
    }

    /**
     * 난이도별 분포 설정
     */
    private function getDifficultyFocus()
    {
        $total = 20; // 기본 생성 개수
        $easy = round($total * 0.3); // 30% 쉬운 단어
        $medium = round($total * 0.5); // 50% 보통 단어
        $hard = $total - $easy - $medium; // 20% 어려운 단어
        
        return [
            'easy' => $easy,
            'medium' => $medium,
            'hard' => $hard
        ];
    }

    /**
     * 십자낱말 퍼즐용 음절 조건 반환
     */
    private function getSyllableCondition($minute)
    {
        return "2음절에서 5음절 사이의 단어";
    }

    /**
     * 선택된 요구사항 반환 (로깅용)
     */
    private function getSelectedRequirement()
    {
        $requirements = [
            '명사, 대명사, 고유명사, 외래어, 신조어 중에서 2~5음절 단어',
            '명사류 (일반명사, 고유명사) 2~5음절 단어',
            '외래어나 신조어로 이루어진 2~5음절 단어',
            '일상생활에서 사용되는 명사류 2~5음절 단어',
            '비즈니스나 전문 분야의 명사류 2~5음절 단어',
            '최근 유행하는 신조어나 외래어 2~5음절 단어',
            '초보자도 알 수 있는 기본적인 명사류 2~5음절 단어',
            '전문가 수준의 고급 명사류 2~5음절 단어',
            '다양한 난이도의 명사류 2~5음절 단어'
        ];
        
        return $requirements[array_rand($requirements)];
    }

    /**
     * 중복 체크 (테스트용)
     */
    private function checkDuplicates($suggestedWords, $category)
    {
        $result = [];
        
        foreach ($suggestedWords as $wordData) {
            $word = $wordData['word'] ?? '';
            $wordCategory = $wordData['category'] ?? $category;
            $isDuplicate = PzWord::where('word', $word)
                ->where('category', $wordCategory)
                ->exists();
                
            $result[] = [
                $wordCategory,
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
        $difficultyCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        
        foreach ($suggestedWords as $wordData) {
            $word = trim($wordData['word'] ?? '');
            $category = trim($wordData['category'] ?? $category);
            
            // 유효성 검사 (2~5음절)
            if (empty($word) || mb_strlen($word) < 2 || mb_strlen($word) > 5) {
                continue;
            }
            
            // 중복 체크 (카테고리와 단어 조합으로)
            $exists = PzWord::where('word', $word)
                ->where('category', $category)
                ->exists();
                
            if ($exists) {
                $this->writeToLog("중복 단어 스킵: [{$category}, {$word}]");
                continue;
            }
            
            // 난이도 결정 (단어 길이와 복잡성 기반)
            $difficulty = $this->determineDifficulty($word);
            
            // 새 단어 저장
            try {
                $newWord = PzWord::create([
                    'word' => $word,
                    'category' => $category,
                    'difficulty' => $difficulty,
                    'is_active' => true,
                ]);
                
                $difficultyCounts[$difficulty]++;
                
                $newWords[] = [
                    'category' => $category,
                    'word' => $word,
                    'id' => $newWord->id,
                    'difficulty' => $difficulty
                ];
                
                $this->writeToLog("새 단어 추가: [{$category}, {$word}] (ID: {$newWord->id}, 난이도: {$difficulty})");
                
            } catch (\Exception $e) {
                $this->writeToLog("단어 저장 실패: [{$category}, {$word}] - " . $e->getMessage());
            }
        }
        
        // 난이도 분포 로그
        $this->writeToLog("난이도 분포: " . json_encode($difficultyCounts));
        
        return $newWords;
    }

    /**
     * 단어 난이도 결정
     */
    private function determineDifficulty($word)
    {
        // 오늘은 모든 단어를 쉬운 단어로 설정
        return 1; // 난이도 1 (쉬움)
        
        // 내일부터는 아래 로직 사용
        /*
        $length = mb_strlen($word);
        
        // 길이 기반 기본 난이도
        if ($length <= 2) {
            $baseDifficulty = 1;
        } elseif ($length == 3) {
            $baseDifficulty = 2;
        } elseif ($length == 4) {
            $baseDifficulty = 3;
        } else {
            $baseDifficulty = 4;
        }
        
        // 특수 문자나 복잡한 조합 확인
        $hasSpecialChars = preg_match('/[^가-힣]/', $word);
        $hasComplexPattern = preg_match('/([가-힣])\1/', $word); // 같은 글자 반복
        
        // 난이도 조정
        if ($hasSpecialChars) {
            $baseDifficulty = min(5, $baseDifficulty + 1);
        }
        
        if ($hasComplexPattern) {
            $baseDifficulty = min(5, $baseDifficulty + 1);
        }
        
        // 너무 어려운 단어는 난이도 조정
        if ($baseDifficulty > 4) {
            $baseDifficulty = 4;
        }
        
        return $baseDifficulty;
        */
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