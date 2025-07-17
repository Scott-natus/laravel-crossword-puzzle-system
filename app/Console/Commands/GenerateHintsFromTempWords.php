<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\GeminiService;
use App\Models\PzWord;
use App\Models\PzHint;
use Illuminate\Support\Facades\Log;

class GenerateHintsFromTempWords extends Command
{
    protected $signature = 'puzzle:generate-hints-from-temp-words {--limit=50} {--word=}';
    protected $description = '임시테이블의 단어들로 카테고리와 힌트를 생성하여 pz_words와 pz_hints 테이블에 저장';

    private $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        parent::__construct();
        $this->geminiService = $geminiService;
    }

    public function handle()
    {
        $limit = $this->option('limit');
        $specificWord = $this->option('word');

        $this->info("힌트 생성 시작 (처리 개수: {$limit})");

        // 처리할 단어 조회 (중복 제거된 것들)
        $query = DB::table('temp_collected_words')
            ->where('processed_at', null)
            ->orderBy('created_at', 'asc');

        if ($specificWord) {
            $query->where('word', $specificWord);
        }

        $words = $query->limit($limit)->get();

        if ($words->isEmpty()) {
            $this->info('처리할 단어가 없습니다.');
            return;
        }

        $successCount = 0;
        $errorCount = 0;
        $skipCount = 0;

        foreach ($words as $tempWord) {
            $this->info("처리 중: {$tempWord->word} (난이도: {$tempWord->difficulty})");

            try {
                // 1. Gemini API로 카테고리와 힌트 생성
                $result = $this->geminiService->generateCategoryAndHints($tempWord->word);

                if ($result['success']) {
                    // 2. [단어, 카테고리] 조합으로 중복 체크
                    $existingWordWithCategory = PzWord::where('word', $tempWord->word)
                        ->where('category', $result['category'])
                        ->first();
                    
                    if ($existingWordWithCategory) {
                        $this->warn("⚠️ 스킵: {$tempWord->word} (카테고리 '{$result['category']}'로 이미 존재)");
                        
                        // 임시테이블 마킹 (중복으로 처리됨, 카테고리도 저장)
                        DB::table('temp_collected_words')
                            ->where('id', $tempWord->id)
                            ->update([
                                'processed_at' => now(),
                                'processing_status' => 'duplicate_category',
                                'category' => $result['category']
                            ]);
                        
                        $skipCount++;
                        continue;
                    }

                    // 3. pz_words 테이블에 단어 저장
                    $word = PzWord::create([
                        'word' => $tempWord->word,
                        'category' => $result['category'],
                        'difficulty' => $tempWord->difficulty,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    // 4. pz_hints 테이블에 힌트들 저장
                    $hintCount = 0;
                    foreach ($result['hints'] as $difficulty => $hintData) {
                        if ($hintData['success']) {
                            PzHint::create([
                                'word_id' => $word->id,
                                'hint_text' => $hintData['hint'],
                                'hint_type' => 'text',
                                'difficulty' => $difficulty,
                                'is_primary' => ($difficulty == 1),
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                            $hintCount++;
                        }
                    }

                    // 5. 임시테이블 마킹 (성공으로 처리됨)
                    DB::table('temp_collected_words')
                        ->where('id', $tempWord->id)
                        ->update([
                            'processed_at' => now(),
                            'processing_status' => 'success',
                            'category' => $result['category'],
                            'hint_count' => $hintCount
                        ]);

                    $successCount++;
                    $this->info("✓ 성공: {$tempWord->word} (카테고리: {$result['category']}, 힌트: {$hintCount}개)");
                } else {
                    $errorCount++;
                    $this->error("✗ 실패: {$tempWord->word} - {$result['error']}");
                    
                    // 임시테이블 마킹 (실패로 처리됨)
                    DB::table('temp_collected_words')
                        ->where('id', $tempWord->id)
                        ->update([
                            'processed_at' => now(),
                            'processing_status' => 'failed',
                            'error_message' => $result['error']
                        ]);
                }

                // API 호출 간격 조절
                sleep(1);

            } catch (\Exception $e) {
                $errorCount++;
                $this->error("✗ 오류: {$tempWord->word} - {$e->getMessage()}");
                Log::error('힌트 생성 오류', [
                    'word' => $tempWord->word,
                    'error' => $e->getMessage()
                ]);
                
                // 임시테이블 마킹 (오류로 처리됨)
                DB::table('temp_collected_words')
                    ->where('id', $tempWord->id)
                    ->update([
                        'processed_at' => now(),
                        'processing_status' => 'error',
                        'error_message' => $e->getMessage()
                    ]);
            }
        }

        $this->info("처리 완료: 성공 {$successCount}개, 실패 {$errorCount}개, 스킵 {$skipCount}개");
    }
} 