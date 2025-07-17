<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GeminiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateWordsFromSyllables extends Command
{
    protected $signature = 'puzzle:generate-words-from-syllables 
                            {--length=2 : 단어 길이 (2-4)}
                            {--count=50 : 한 번에 생성할 단어 수}
                            {--consonant= : 특정 자음으로 시작하는 단어만 생성}';

    protected $description = '저장된 음절들을 조합해서 실제 사용되는 단어 생성';

    private $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        parent::__construct();
        $this->geminiService = $geminiService;
    }

    public function handle()
    {
        $length = (int)$this->option('length');
        $count = (int)$this->option('count');
        $consonant = $this->option('consonant');

        if ($length < 2 || $length > 4) {
            $this->error('단어 길이는 2-4 사이여야 합니다.');
            return;
        }

        $this->info("음절 조합으로 {$length}글자 단어 생성 시작...");

        // 음절 조합으로 단어 후보 생성
        $wordCandidates = $this->generateWordCandidates($length, $count, $consonant);
        
        if (empty($wordCandidates)) {
            $this->error('단어 후보가 생성되지 않았습니다.');
            return;
        }

        $this->info(count($wordCandidates) . "개의 단어 후보 생성 완료");

        // Gemini API로 실제 사용되는 단어 검증
        $validWords = $this->validateWordsWithAPI($wordCandidates);

        if (empty($validWords)) {
            $this->error('검증된 단어가 없습니다.');
            return;
        }

        $this->info(count($validWords) . "개의 검증된 단어 생성 완료");

        // 데이터베이스에 저장
        $this->saveWordsToDatabase($validWords);

        $this->info('단어 생성 및 저장 완료!');
    }

    /**
     * 음절 조합으로 단어 후보 생성 (개선된 버전)
     */
    private function generateWordCandidates(int $length, int $count, ?string $consonant): array
    {
        $candidates = [];
        $attempts = 0;
        $maxAttempts = $count * 20; // 최대 시도 횟수 증가

        // 자주 쓰이는 첫 글자들 (기존 단어 분석 결과)
        $frequentFirstChars = ['인', '지', '사', '정', '스', '수', '유', '자', '기', '공', '대', '전', '시', '관', '분', '과', '리', '화', '학', '제'];
        
        // 자주 쓰이는 두 번째 글자들
        $frequentSecondChars = ['구', '회', '원', '자', '사', '력', '성', '화', '학', '제', '관', '리', '과', '부', '실', '장', '소', '단', '체', '계'];

        while (count($candidates) < $count && $attempts < $maxAttempts) {
            $attempts++;

            $word = '';
            
            if ($length == 2) {
                // 2글자 단어: 자주 쓰이는 조합 사용
                if (rand(1, 100) <= 70) {
                    // 70% 확률로 자주 쓰이는 조합 사용
                    $firstChar = $frequentFirstChars[array_rand($frequentFirstChars)];
                    $secondChar = $frequentSecondChars[array_rand($frequentSecondChars)];
                    $word = $firstChar . $secondChar;
                } else {
                    // 30% 확률로 랜덤 조합
                    $syllables = DB::table('temp_hangul_syllables')
                        ->inRandomOrder()
                        ->limit(2)
                        ->pluck('syllable')
                        ->toArray();
                    
                    if (count($syllables) === 2) {
                        $word = implode('', $syllables);
                    }
                }
            } else {
                // 3글자 이상: 첫 글자는 자주 쓰이는 것, 나머지는 랜덤
                $firstChar = $frequentFirstChars[array_rand($frequentFirstChars)];
                $remainingSyllables = DB::table('temp_hangul_syllables')
                    ->inRandomOrder()
                    ->limit($length - 1)
                    ->pluck('syllable')
                    ->toArray();
                
                if (count($remainingSyllables) === $length - 1) {
                    $word = $firstChar . implode('', $remainingSyllables);
                }
            }

            // 특정 자음으로 시작하는 경우 필터링
            if ($consonant && !empty($word)) {
                $firstSyllable = mb_substr($word, 0, 1);
                $firstConsonant = $this->getInitialConsonant($firstSyllable);
                if ($firstConsonant !== $consonant) {
                    continue;
                }
            }

            // 유효한 단어이고 중복되지 않는 경우만 추가
            if (!empty($word) && !in_array($word, $candidates)) {
                $candidates[] = $word;
            }
        }

        return $candidates;
    }

    /**
     * 한글 음절의 초성 추출
     */
    private function getInitialConsonant(string $syllable): string
    {
        $code = ord($syllable);
        
        if ($code >= 0xAC00 && $code <= 0xD7A3) {
            $initial = (($code - 0xAC00) / 28) / 21;
            $consonants = ['ㄱ', 'ㄲ', 'ㄴ', 'ㄷ', 'ㄸ', 'ㄹ', 'ㅁ', 'ㅂ', 'ㅃ', 'ㅅ', 'ㅆ', 'ㅇ', 'ㅈ', 'ㅉ', 'ㅊ', 'ㅋ', 'ㅌ', 'ㅍ', 'ㅎ'];
            return $consonants[$initial];
        }
        
        return '';
    }

    /**
     * Gemini API로 실제 사용되는 단어 검증
     */
    private function validateWordsWithAPI(array $wordCandidates): array
    {
        $validWords = [];
        $batchSize = 10; // 한 번에 10개씩 처리

        for ($i = 0; $i < count($wordCandidates); $i += $batchSize) {
            $batch = array_slice($wordCandidates, $i, $batchSize);
            
            $prompt = $this->createValidationPrompt($batch);
            
            try {
                $result = $this->geminiService->generateWords($prompt);
                
                if ($result['success'] && !empty($result['words'])) {
                    foreach ($result['words'] as $wordData) {
                        if (isset($wordData['word']) && isset($wordData['category']) && isset($wordData['difficulty'])) {
                            $validWords[] = [
                                'word' => $wordData['word'],
                                'category' => $wordData['category'],
                                'difficulty' => $wordData['difficulty']
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('단어 검증 API 호출 실패', ['error' => $e->getMessage()]);
                $this->warn("배치 {$i}-" . ($i + $batchSize - 1) . " 처리 중 오류 발생");
            }

            // API 호출 간격 조절
            sleep(1);
        }

        return $validWords;
    }

    /**
     * 단어 검증용 프롬프트 생성
     */
    private function createValidationPrompt(array $words): string
    {
        $wordList = implode(', ', $words);
        
        return "다음 한글 단어들 중에서 실제로 사용되는 단어만 선별해주세요.
표준어 사전에 등재되어 있고 실제로 의미가 있는 단어만 선택해주세요.

단어 목록: {$wordList}

선별된 단어들을 다음 형식으로 응답해주세요:
[카테고리,단어,난이도]

예시:
[동물,강아지,2]
[음식,김치,1]
[직업,의사,3]

카테고리는 다음 중 하나로 분류해주세요:
- 동물, 음식, 직업, 장소, 물건, 감정, 색깔, 숫자, 이름, 기타

난이도는 1-5로 평가해주세요 (1: 쉬움, 5: 어려움)

실제 사용되지 않는 단어는 제외하고, 실제 사용되는 단어만 응답해주세요.";
    }

    /**
     * 검증된 단어를 데이터베이스에 저장
     */
    private function saveWordsToDatabase(array $validWords): void
    {
        $savedCount = 0;

        foreach ($validWords as $wordData) {
            try {
                // 이미 존재하는 단어인지 확인
                $existingWord = DB::table('pz_words')
                    ->where('word', $wordData['word'])
                    ->first();

                if (!$existingWord) {
                    DB::table('pz_words')->insert([
                        'word' => $wordData['word'],
                        'category' => $wordData['category'],
                        'difficulty' => $wordData['difficulty'],
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $savedCount++;
                }
            } catch (\Exception $e) {
                Log::error('단어 저장 실패', [
                    'word' => $wordData['word'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("총 {$savedCount}개의 새로운 단어가 저장되었습니다.");
    }
} 