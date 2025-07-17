<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\GeminiService;

class CollectWordsFromSyllables extends Command
{
    protected $signature = 'puzzle:collect-words-from-syllables';
    protected $description = 'temp_hangul_syllables의 syllable별로 단어/난이도 리스트를 수집하여 temp_collected_words에 저장하고 마킹, 실행 로그 남김';

    private $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        parent::__construct();
        $this->geminiService = $geminiService;
    }

    public function handle()
    {
        // 1. 처리 안 된 syllable 1개 추출
        $syllableRow = DB::table('temp_hangul_syllables')
            ->whereNull('processed_at')
            ->orderBy('id')
            ->first();

        if (!$syllableRow) {
            $this->info('모든 음절이 처리되었습니다.');
            Log::info('[CollectWordsFromSyllables] 모든 음절 처리 완료');
            return 0;
        }

        $syllable = $syllableRow->syllable;
        $this->info("처리할 음절: {$syllable}");
        Log::info("[CollectWordsFromSyllables] 처리할 음절: {$syllable}");

        // 2. API2 프롬프트 생성
        $prompt = "당신은 한글 단어를 가르치는 교사입니다. 사람들에게 한글 십자 낱말 퀴즈를 제공하려 합니다.\n한글로 표현되는 단어에서 '{$syllable}'로 시작되는 단어를 추출해 주세요\n낱말퀴즈로 볼 때 난이도도 같이 제공해 주세요\n\n*** 중요 ****\n\n - 반드시 '{$syllable}'로 시작되는 단어만\n - 외래어, 신조어 포함\n - 동사, 형용사, 부사, 조사, 지시대명사 등은 제외\n - 영문, 숫자나 기호가 들어간 단어는 제외 (삼박사일과 같은 단어 허용, 케이팝과 같은 외래어도 허용 )\n\n조건 \n - (1음절이 한글자라고 할때 ) 2글자에서 ~ 5글자이내\n - 난이도는 1~5 ( 낮을수록 쉬운 단어 )\n - 한줄에 한단어만 보여주세요 \n - 반드시 '단어,난이도' 형식으로만 응답해주세요 (예: 가위,1 가족,2 가슴,3)\n - 대괄호나 다른 기호 없이 단순히 '단어,난이도' 형식으로만 만들어주세요 ";

        // 3. Gemini API 호출
        $result = $this->geminiService->generateWords($prompt);
        if (!$result['success']) {
            $this->error('API 호출 실패: ' . ($result['error'] ?? '알 수 없는 오류'));
            Log::error("[CollectWordsFromSyllables] API 호출 실패: " . ($result['error'] ?? '알 수 없는 오류'));
            // 더 이상 processed_at 마킹하지 않음 (롤백)
            return 1;
        }

        $words = $result['words'] ?? [];
        $inserted = 0;
        foreach ($words as $wordData) {
            $word = trim($wordData['word'] ?? '');
            $difficulty = (int)($wordData['difficulty'] ?? 2);
            if (empty($word) || mb_strlen($word) < 2 || mb_strlen($word) > 5) continue;
            DB::table('temp_collected_words')->insert([
                'syllable' => $syllable,
                'word' => $word,
                'difficulty' => $difficulty,
                'created_at' => now()
            ]);
            $inserted++;
        }
        $this->info("{$inserted}개 단어 저장 완료");
        Log::info("[CollectWordsFromSyllables] {$syllable} → {$inserted}개 단어 저장 완료");

        // 4. syllable 마킹 (단어가 1개 이상 저장된 경우에만)
        if ($inserted > 0) {
            DB::table('temp_hangul_syllables')->where('id', $syllableRow->id)->update(['processed_at' => now()]);
            $this->info("음절 마킹 완료: {$syllable}");
            Log::info("[CollectWordsFromSyllables] 음절 마킹 완료: {$syllable}");
        } else {
            $this->warn("단어가 하나도 저장되지 않아 음절 마킹을 건너뜀: {$syllable}");
            Log::warning("[CollectWordsFromSyllables] 단어 없음, 음절 마킹 건너뜀: {$syllable}");
        }

        return 0;
    }
} 