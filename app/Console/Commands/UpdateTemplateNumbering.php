<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateTemplateNumbering extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'template:update-numbering {template_ids?* : 템플릿 ID 목록 (기본값: 11,12,13,14)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '기존 템플릿들의 넘버링을 새로운 로직으로 업데이트';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $templateIds = $this->argument('template_ids');
        
        if (empty($templateIds)) {
            $templateIds = [11, 12, 13, 14];
        }
        
        $this->info("템플릿 넘버링 업데이트 시작...");
        $this->info("대상 템플릿 ID: " . implode(', ', $templateIds));
        
        $results = [];
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($templateIds as $templateId) {
            $this->info("\n템플릿 ID {$templateId} 처리 중...");
            
            try {
                $template = DB::table('puzzle_grid_templates')
                    ->where('id', $templateId)
                    ->where('is_active', true)
                    ->first();
                
                if (!$template) {
                    $this->error("템플릿 ID {$templateId}를 찾을 수 없습니다.");
                    $errorCount++;
                    continue;
                }
                
                $gridPattern = json_decode($template->grid_pattern, true);
                $wordPositions = json_decode($template->word_positions, true);
                
                if (!$gridPattern || !$wordPositions) {
                    $this->error("템플릿 ID {$templateId}의 그리드 패턴 또는 단어 위치 데이터가 유효하지 않습니다.");
                    $errorCount++;
                    continue;
                }
                
                // 새로운 넘버링 로직 적용
                $newWordOrder = $this->determineWordOrder($wordPositions, $gridPattern);
                
                // 새로운 넘버링 정보를 JSON으로 저장
                $newNumberingData = json_encode($newWordOrder, JSON_UNESCAPED_UNICODE);
                
                // 데이터베이스 업데이트
                DB::table('puzzle_grid_templates')
                    ->where('id', $templateId)
                    ->update([
                        'word_numbering' => $newNumberingData,
                        'updated_at' => now()
                    ]);
                
                $this->info("✅ 템플릿 ID {$templateId} ({$template->template_name}) 넘버링 업데이트 완료");
                $this->info("   - 단어 개수: " . count($newWordOrder));
                
                $successCount++;
                
                Log::info("템플릿 넘버링 업데이트 완료", [
                    'template_id' => $templateId,
                    'template_name' => $template->template_name,
                    'word_count' => count($newWordOrder)
                ]);
                
            } catch (\Exception $e) {
                $this->error("템플릿 ID {$templateId} 처리 중 오류 발생: " . $e->getMessage());
                $errorCount++;
                
                Log::error("템플릿 넘버링 업데이트 오류", [
                    'template_id' => $templateId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        $this->info("\n=== 업데이트 완료 ===");
        $this->info("성공: {$successCount}개");
        $this->info("실패: {$errorCount}개");
        
        if ($errorCount > 0) {
            return 1;
        }
        
        return 0;
    }
    
    /**
     * 새로운 넘버링 로직 (2025-06-25)
     * 1. 넘버링은 1에서 시작
     * 2. 좌측 맨위부터 가로열 우선으로 진행
     * 3. 최초 만나는 검은색칸에서 넘버링 시작
     * 4. 교차점으로 연결된 단어들을 연쇄적으로 넘버링
     * 5. 연결된 단어가 없으면 순차적으로 진행
     */
    private function determineWordOrder($wordPositions, $gridPattern)
    {
        $wordOrder = [];
        $usedWords = [];
        $gridSize = count($gridPattern);
        $visitedPositions = [];
        $currentNumber = 1;
        
        // 1. 좌측 맨위부터 가로열 우선으로 진행
        for ($y = 0; $y < $gridSize; $y++) {
            for ($x = 0; $x < $gridSize; $x++) {
                $positionKey = $x . ',' . $y;
                if (in_array($positionKey, $visitedPositions)) continue;
                
                // 2. 최초 만나는 검은색칸(단어가 있는 칸)인지 확인
                if ($gridPattern[$y][$x] === 1) {
                    // 이 위치에서 시작하는 단어들 찾기
                    $wordsAtPosition = $this->findWordsAtPosition($x, $y, $wordPositions, $usedWords);
                    
                    if (!empty($wordsAtPosition)) {
                        // 3. 최초 넘버링 단어 처리
                        $firstWord = $wordsAtPosition[0];
                        $wordOrder[] = [
                            'word_id' => $firstWord['id'],
                            'position' => $firstWord,
                            'type' => 'first_word',
                            'order' => $currentNumber,
                            'start_x' => $x,
                            'start_y' => $y
                        ];
                        $usedWords[] = $firstWord['id'];
                        $this->markWordPositionsAsVisited($firstWord, $visitedPositions);
                        
                        // 4-5. 교차점으로 연결된 단어들을 연쇄적으로 넘버링
                        $this->processConnectedWordsChain($wordOrder, $usedWords, $wordPositions, $visitedPositions, $currentNumber);
                        
                        // 6. 연결된 단어가 없으면 다음 검은색칸으로 진행
                        $currentNumber = count($wordOrder) + 1;
                    }
                }
            }
        }
        
        // 7-8. 남은 단어들 처리 (교차점이 없는 단어들)
        foreach ($wordPositions as $word) {
            if (!in_array($word['id'], $usedWords)) {
                $wordOrder[] = [
                    'word_id' => $word['id'],
                    'position' => $word,
                    'type' => 'remaining_word',
                    'order' => $currentNumber
                ];
                $usedWords[] = $word['id'];
                $currentNumber++;
            }
        }
        
        return $wordOrder;
    }
    
    /**
     * 교차점으로 연결된 단어들을 연쇄적으로 넘버링
     */
    private function processConnectedWordsChain(&$wordOrder, &$usedWords, $wordPositions, &$visitedPositions, &$currentNumber)
    {
        $changed = true;
        
        while ($changed) {
            $changed = false;
            $newConnectedWords = [];
            
            // 현재 넘버링된 단어들과 교차점으로 연결된 단어들 찾기
            foreach ($wordOrder as $orderedWord) {
                $connectedWords = $this->findConnectedWordsByIntersection($orderedWord['position'], $wordPositions, $usedWords);
                
                foreach ($connectedWords as $connectedWord) {
                    if (!in_array($connectedWord['id'], $usedWords)) {
                        $newConnectedWords[] = [
                            'word' => $connectedWord,
                            'connected_to' => $orderedWord['word_id']
                        ];
                    }
                }
            }
            
            // 새로 연결된 단어들을 넘버링
            foreach ($newConnectedWords as $connectedInfo) {
                $word = $connectedInfo['word'];
                $currentNumber++;
                
                $wordOrder[] = [
                    'word_id' => $word['id'],
                    'position' => $word,
                    'type' => 'connected_word',
                    'order' => $currentNumber,
                    'connected_to' => $connectedInfo['connected_to']
                ];
                $usedWords[] = $word['id'];
                $this->markWordPositionsAsVisited($word, $visitedPositions);
                $changed = true;
            }
        }
    }
    
    /**
     * 특정 위치에서 시작하는 단어들 찾기
     */
    private function findWordsAtPosition($x, $y, $wordPositions, $usedWords)
    {
        $words = [];
        
        foreach ($wordPositions as $word) {
            if (in_array($word['id'], $usedWords)) continue;
            
            if ($word['start_x'] == $x && $word['start_y'] == $y) {
                $words[] = $word;
            }
        }
        
        return $words;
    }
    
    /**
     * 단어가 차지한 위치들을 방문된 것으로 표시
     */
    private function markWordPositionsAsVisited($word, &$visitedPositions)
    {
        if ($word['direction'] === 'horizontal') {
            for ($x = $word['start_x']; $x <= $word['end_x']; $x++) {
                $positionKey = $x . ',' . $word['start_y'];
                if (!in_array($positionKey, $visitedPositions)) {
                    $visitedPositions[] = $positionKey;
                }
            }
        } else {
            for ($y = $word['start_y']; $y <= $word['end_y']; $y++) {
                $positionKey = $word['start_x'] . ',' . $y;
                if (!in_array($positionKey, $visitedPositions)) {
                    $visitedPositions[] = $positionKey;
                }
            }
        }
    }
    
    /**
     * 교차점으로 연결된 단어들 찾기
     */
    private function findConnectedWordsByIntersection($word, $wordPositions, $usedWords)
    {
        $connectedWords = [];
        
        foreach ($wordPositions as $otherWord) {
            if (in_array($otherWord['id'], $usedWords)) continue;
            if ($word['id'] == $otherWord['id']) continue;
            
            // 교차점 찾기
            $intersection = $this->findIntersection($word, $otherWord);
            if ($intersection) {
                $connectedWords[] = $otherWord;
            }
        }
        
        return $connectedWords;
    }
    
    /**
     * 두 단어 간의 교차점 찾기
     */
    private function findIntersection($word1, $word2)
    {
        if ($word1['direction'] === 'horizontal' && $word2['direction'] === 'vertical') {
            $horizontal = $word1;
            $vertical = $word2;
            
            if ($horizontal['start_y'] >= $vertical['start_y'] && $horizontal['start_y'] <= $vertical['end_y'] &&
                $vertical['start_x'] >= $horizontal['start_x'] && $vertical['start_x'] <= $horizontal['end_x']) {
                return [
                    'x' => $vertical['start_x'],
                    'y' => $horizontal['start_y']
                ];
            }
        } else if ($word1['direction'] === 'vertical' && $word2['direction'] === 'horizontal') {
            $vertical = $word1;
            $horizontal = $word2;
            
            if ($horizontal['start_y'] >= $vertical['start_y'] && $horizontal['start_y'] <= $vertical['end_y'] &&
                $vertical['start_x'] >= $horizontal['start_x'] && $vertical['start_x'] <= $horizontal['end_x']) {
                return [
                    'x' => $vertical['start_x'],
                    'y' => $horizontal['start_y']
                ];
            }
        }
        
        return null;
    }
} 