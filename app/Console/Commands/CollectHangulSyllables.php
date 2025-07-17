<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GeminiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CollectHangulSyllables extends Command
{
    protected $signature = 'puzzle:collect-syllables {--consonant=} {--all}';
    protected $description = '한글 자음별 음절 수집';

    private $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        parent::__construct();
        $this->geminiService = $geminiService;
    }

    public function handle()
    {
        $consonants = ['ㄱ', 'ㄴ', 'ㄷ', 'ㄹ', 'ㅁ', 'ㅂ', 'ㅅ', 'ㅇ', 'ㅈ', 'ㅊ', 'ㅋ', 'ㅌ', 'ㅍ', 'ㅎ'];
        
        if ($this->option('consonant')) {
            $targetConsonant = $this->option('consonant');
            if (!in_array($targetConsonant, $consonants)) {
                $this->error("잘못된 자음입니다. 사용 가능한 자음: " . implode(', ', $consonants));
                return 1;
            }
            $this->collectSyllablesForConsonant($targetConsonant);
        } elseif ($this->option('all')) {
            $this->info("모든 자음에 대해 음절 수집을 시작합니다...");
            foreach ($consonants as $consonant) {
                $this->collectSyllablesForConsonant($consonant);
                sleep(2); // API 호출 간격
            }
        } else {
            $this->error("--consonant 또는 --all 옵션을 사용해주세요.");
            $this->info("예시: php artisan puzzle:collect-syllables --consonant=ㄱ");
            $this->info("예시: php artisan puzzle:collect-syllables --all");
            return 1;
        }

        return 0;
    }

    private function collectSyllablesForConsonant($consonant)
    {
        $this->info("'{$consonant}' 자음 음절 수집 중...");
        
        $prompt = "한글에서 '{$consonant}'으로 시작하는 실제 사용되는 한글 음절들을 리스트업해줘.

중요: 반드시 '{$consonant}' 자음으로 시작하는 음절만 나열해주세요.

조건:
1. 표준어 사전에 등재된 음절만
2. 실제로 의미가 있는 음절만 (조사, 어미 등은 제외)
3. 한 글자 음절만
4. 반드시 '{$consonant}' 자음으로 시작하는 음절만
5. 네가 알고 있는 '실제 사용되는 음절'은 전부 제공해줘 (최소 50개 이상)

응답 형식:
- 한 줄에 하나씩 음절만 나열
- 반드시 '{$consonant}' 자음으로 시작하는 음절만";

        try {
            $result = $this->geminiService->generateSyllables($prompt);
            
            if (!$result['success']) {
                $this->error("API 호출 실패: " . ($result['error'] ?? '알 수 없는 오류'));
                return;
            }

            $syllables = $result['words'] ?? [];
            $count = 0;
            
            // 로그 추가
            $this->info("API 응답 음절 수: " . count($syllables));
            Log::info("음절 수집 API 응답", [
                'consonant' => $consonant,
                'total_syllables' => count($syllables),
                'syllables' => $syllables
            ]);

            foreach ($syllables as $syllableData) {
                $syllable = trim($syllableData['word'] ?? '');
                
                // 로그 추가
                $this->info("처리 중인 음절: '{$syllable}' (길이: " . mb_strlen($syllable) . ")");
                
                if (empty($syllable) || mb_strlen($syllable) !== 1) {
                    $this->warn("유효하지 않은 음절 스킵: '{$syllable}'");
                    continue;
                }

                // 중복 체크 없이 모든 음절 저장
                DB::table('temp_hangul_syllables')->insert([
                    'consonant' => $consonant,
                    'syllable' => $syllable,
                    'created_at' => now()
                ]);
                $count++;
                
                $this->info("음절 저장 완료: '{$syllable}'");
            }

            $this->info("'{$consonant}' 자음: {$count}개 음절 저장 완료");

        } catch (\Exception $e) {
            $this->error("음절 수집 중 오류: " . $e->getMessage());
        }
    }
} 