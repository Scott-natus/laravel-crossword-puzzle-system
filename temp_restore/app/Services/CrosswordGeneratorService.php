<?php

namespace App\Services;

use App\Models\PuzzleLevel;
use App\Models\PzWord;
use App\Models\PzHint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CrosswordGeneratorService
{
    /**
     * 레벨에 맞는 크로스워드 퍼즐 생성
     */
    public function generateCrossword(int $levelId): array
    {
        $level = PuzzleLevel::findOrFail($levelId);
        
        // 1단계: 단어 후보군 선정
        $wordCombination = $this->selectWordCombination($level);
        
        if (!$wordCombination) {
            throw new \Exception("레벨 {$levelId}에 대한 적절한 단어 조합을 찾을 수 없습니다.");
        }
        
        // 2단계: 동적 그리드 크기 계산
        $gridSize = $this->calculateGridSize($wordCombination, $level->intersection_count);
        
        // 3단계: 백트래킹 기반 단어 배치
        $placement = $this->placeWordsWithBacktracking($wordCombination, $gridSize, $level->intersection_count);
        
        if (!$placement) {
            throw new \Exception("단어 배치에 실패했습니다. 다른 단어 조합을 시도해주세요.");
        }
        
        // 4단계: 힌트 생성
        $hints = $this->generateHints($wordCombination);
        
        return [
            'level' => $level->toArray(),
            'grid' => $placement['grid'],
            'words' => $placement['words'],
            'word_positions' => $placement['word_positions'],
            'hints' => $hints,
            'stats' => [
                'word_count' => count($wordCombination),
                'intersection_count' => $level->intersection_count,
                'grid_size' => $gridSize
            ]
        ];
    }
    
    /**
     * 단어 후보군 선정 (교차점 조건 만족하는 조합 찾기)
     */
    private function selectWordCombination(PuzzleLevel $level): ?Collection
    {
        $maxAttempts = 50;
        $attempts = 0;
        
        while ($attempts < $maxAttempts) {
            // 레벨에 맞는 단어들 추출
            $words = $this->getWordsForLevel($level);
            
            // 교차점 조건을 만족하는 조합 찾기
            $combination = $this->findValidCombination($words, $level->word_count, $level->intersection_count);
            
            if ($combination) {
                return $combination;
            }
            
            $attempts++;
        }
        
        return null;
    }
    
    /**
     * 레벨에 맞는 단어들 가져오기
     */
    private function getWordsForLevel(PuzzleLevel $level): Collection
    {
        // word_difficulty가 3이면 3 이하 단어만 추출
        if ($level->word_difficulty == 3) {
            return PzWord::active()
                ->where('difficulty', '<=', 3)
                ->where('is_active', true)
                ->inRandomOrder()
                ->limit(20)
                ->get();
        } else {
            return PzWord::active()
                ->where('difficulty', $level->word_difficulty)
                ->where('is_active', true)
                ->inRandomOrder()
                ->limit(20)
                ->get();
        }
    }
    
    /**
     * 교차점 조건을 만족하는 단어 조합 찾기
     */
    private function findValidCombination(Collection $words, int $wordCount, int $requiredIntersections): ?Collection
    {
        $wordArray = $words->toArray();
        $combinations = $this->generateCombinations($wordArray, $wordCount);
        
        foreach ($combinations as $combination) {
            if ($this->canCreateIntersections($combination, $requiredIntersections)) {
                return collect($combination);
            }
        }
        
        return null;
    }
    
    /**
     * 단어 조합 생성 (nCr)
     */
    private function generateCombinations(array $words, int $r): array
    {
        $n = count($words);
        if ($r > $n) return [];
        
        $combinations = [];
        $this->combinationsHelper($words, $r, 0, [], $combinations);
        
        return $combinations;
    }
    
    /**
     * 조합 생성 헬퍼 함수
     */
    private function combinationsHelper(array $words, int $r, int $start, array $current, array &$result): void
    {
        if (count($current) == $r) {
            $result[] = $current;
            return;
        }
        
        for ($i = $start; $i < count($words); $i++) {
            $current[] = $words[$i];
            $this->combinationsHelper($words, $r, $i + 1, $current, $result);
            array_pop($current);
        }
    }
    
    /**
     * 주어진 단어들로 필요한 교차점 개수를 만들 수 있는지 확인
     */
    private function canCreateIntersections(array $words, int $requiredIntersections): bool
    {
        $intersections = 0;
        
        for ($i = 0; $i < count($words); $i++) {
            for ($j = $i + 1; $j < count($words); $j++) {
                $word1 = $words[$i]['word'];
                $word2 = $words[$j]['word'];
                
                // 두 단어 간의 교차점 찾기
                $commonSyllables = $this->findCommonSyllables($word1, $word2);
                $intersections += count($commonSyllables);
                
                if ($intersections >= $requiredIntersections) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * 두 단어 간의 공통 음절 찾기
     */
    private function findCommonSyllables(string $word1, string $word2): array
    {
        $syllables1 = $this->splitIntoSyllables($word1);
        $syllables2 = $this->splitIntoSyllables($word2);
        
        $common = [];
        
        foreach ($syllables1 as $i => $syllable1) {
            foreach ($syllables2 as $j => $syllable2) {
                if ($syllable1 === $syllable2) {
                    $common[] = [
                        'syllable' => $syllable1,
                        'word1' => $word1,
                        'word1_position' => $i,
                        'word2' => $word2,
                        'word2_position' => $j
                    ];
                }
            }
        }
        
        return $common;
    }
    
    /**
     * 한글 단어를 음절 단위로 분리
     */
    private function splitIntoSyllables(string $word): array
    {
        $syllables = [];
        $length = mb_strlen($word, 'UTF-8');
        
        for ($i = 0; $i < $length; $i++) {
            $syllables[] = mb_substr($word, $i, 1, 'UTF-8');
        }
        
        return $syllables;
    }
    
    /**
     * 동적 그리드 크기 계산
     */
    private function calculateGridSize(Collection $words, int $intersectionCount): int
    {
        $maxLength = $words->max(function($word) {
            return mb_strlen($word['word'], 'UTF-8');
        });
        
        // 제미나이 공식: max(단어들의 음절 수) + (교차점 개수 / 2) + 2 (여유 패딩)
        $gridSize = $maxLength + floor($intersectionCount / 2) + 2;
        
        // 최소 5x5, 최대 15x15로 제한
        return max(5, min(15, $gridSize));
    }
    
    /**
     * 백트래킹 기반 단어 배치
     */
    private function placeWordsWithBacktracking(Collection $words, int $gridSize, int $requiredIntersections): ?array
    {
        $grid = $this->createEmptyGrid($gridSize);
        $placedWords = [];
        $wordArray = $words->toArray();
        
        // 가장 긴 단어부터 정렬
        usort($wordArray, function($a, $b) {
            return mb_strlen($b['word'], 'UTF-8') - mb_strlen($a['word'], 'UTF-8');
        });
        
        if ($this->backtrackPlacement($wordArray, $grid, $placedWords, 0, $requiredIntersections)) {
            return [
                'grid' => $grid,
                'words' => $placedWords,
                'word_positions' => $this->generateWordPositions($placedWords)
            ];
        }
        
        return null;
    }
    
    /**
     * 백트래킹 배치 알고리즘
     */
    private function backtrackPlacement(array $words, array &$grid, array &$placedWords, int $index, int $requiredIntersections): bool
    {
        // 모든 단어를 배치했는지 확인
        if ($index >= count($words)) {
            return $this->countIntersections($placedWords) >= $requiredIntersections;
        }
        
        $word = $words[$index];
        $wordText = $word['word'];
        $wordLength = mb_strlen($wordText, 'UTF-8');
        $gridSize = count($grid);
        
        // 가로 방향으로 배치 시도
        for ($y = 0; $y < $gridSize; $y++) {
            for ($x = 0; $x <= $gridSize - $wordLength; $x++) {
                if ($this->canPlaceWord($grid, $wordText, $x, $y, 'horizontal')) {
                    $this->placeWord($grid, $wordText, $x, $y, 'horizontal');
                    $placedWords[] = [
                        'word' => $wordText,
                        'start_x' => $x,
                        'start_y' => $y,
                        'direction' => 'horizontal',
                        'id' => $word['id']
                    ];
                    
                    if ($this->backtrackPlacement($words, $grid, $placedWords, $index + 1, $requiredIntersections)) {
                        return true;
                    }
                    
                    // 백트래킹: 단어 제거
                    $this->removeWord($grid, $wordText, $x, $y, 'horizontal');
                    array_pop($placedWords);
                }
            }
        }
        
        // 세로 방향으로 배치 시도
        for ($y = 0; $y <= $gridSize - $wordLength; $y++) {
            for ($x = 0; $x < $gridSize; $x++) {
                if ($this->canPlaceWord($grid, $wordText, $x, $y, 'vertical')) {
                    $this->placeWord($grid, $wordText, $x, $y, 'vertical');
                    $placedWords[] = [
                        'word' => $wordText,
                        'start_x' => $x,
                        'start_y' => $y,
                        'direction' => 'vertical',
                        'id' => $word['id']
                    ];
                    
                    if ($this->backtrackPlacement($words, $grid, $placedWords, $index + 1, $requiredIntersections)) {
                        return true;
                    }
                    
                    // 백트래킹: 단어 제거
                    $this->removeWord($grid, $wordText, $x, $y, 'vertical');
                    array_pop($placedWords);
                }
            }
        }
        
        return false;
    }
    
    /**
     * 단어 배치 가능 여부 확인 (단어 독립성 검사 포함)
     */
    private function canPlaceWord(array $grid, string $word, int $x, int $y, string $direction): bool
    {
        $wordLength = mb_strlen($word, 'UTF-8');
        $gridSize = count($grid);
        
        // 경계 확인
        if ($direction === 'horizontal') {
            if ($x + $wordLength > $gridSize) return false;
        } else {
            if ($y + $wordLength > $gridSize) return false;
        }
        
        // 단어 배치 및 독립성 검사
        for ($i = 0; $i < $wordLength; $i++) {
            $char = mb_substr($word, $i, 1, 'UTF-8');
            $gridX = $direction === 'horizontal' ? $x + $i : $x;
            $gridY = $direction === 'horizontal' ? $y : $y + $i;
            
            $gridChar = $grid[$gridY][$gridX];
            
            // 교차점 확인
            if ($gridChar !== '' && $gridChar !== $char) {
                return false;
            }
            
            // 단어 독립성 검사 (핵심!)
            if (!$this->checkWordIndependence($grid, $word, $x, $y, $direction, $i)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 단어 독립성 검사 (제미나이 핵심 조건)
     */
    private function checkWordIndependence(array $grid, string $word, int $x, int $y, string $direction, int $position): bool
    {
        $wordLength = mb_strlen($word, 'UTF-8');
        $gridSize = count($grid);
        
        for ($i = 0; $i < $wordLength; $i++) {
            $gridX = $direction === 'horizontal' ? $x + $i : $x;
            $gridY = $direction === 'horizontal' ? $y : $y + $i;
            
            // 단어의 시작/끝 주변 검사
            if ($i === 0) { // 단어 시작
                if ($direction === 'horizontal') {
                    if ($gridX > 0 && $grid[$gridY][$gridX - 1] !== '') return false;
                } else {
                    if ($gridY > 0 && $grid[$gridY - 1][$gridX] !== '') return false;
                }
            }
            
            if ($i === $wordLength - 1) { // 단어 끝
                if ($direction === 'horizontal') {
                    if ($gridX < $gridSize - 1 && $grid[$gridY][$gridX + 1] !== '') return false;
                } else {
                    if ($gridY < $gridSize - 1 && $grid[$gridY + 1][$gridX] !== '') return false;
                }
            }
            
            // 단어 중간 부분 주변 검사
            if ($direction === 'horizontal') {
                // 가로 단어의 위/아래 검사
                if ($gridY > 0 && $grid[$gridY - 1][$gridX] !== '') {
                    // 교차점이 아닌 경우에만 체크
                    if ($grid[$gridY][$gridX] === '') return false;
                }
                if ($gridY < $gridSize - 1 && $grid[$gridY + 1][$gridX] !== '') {
                    if ($grid[$gridY][$gridX] === '') return false;
                }
            } else {
                // 세로 단어의 좌/우 검사
                if ($gridX > 0 && $grid[$gridY][$gridX - 1] !== '') {
                    if ($grid[$gridY][$gridX] === '') return false;
                }
                if ($gridX < $gridSize - 1 && $grid[$gridY][$gridX + 1] !== '') {
                    if ($grid[$gridY][$gridX] === '') return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * 그리드에 단어 배치
     */
    private function placeWord(array &$grid, string $word, int $x, int $y, string $direction): void
    {
        $wordLength = mb_strlen($word, 'UTF-8');
        
        for ($i = 0; $i < $wordLength; $i++) {
            $char = mb_substr($word, $i, 1, 'UTF-8');
            $gridX = $direction === 'horizontal' ? $x + $i : $x;
            $gridY = $direction === 'horizontal' ? $y : $y + $i;
            
            $grid[$gridY][$gridX] = $char;
        }
    }
    
    /**
     * 그리드에서 단어 제거
     */
    private function removeWord(array &$grid, string $word, int $x, int $y, string $direction): void
    {
        $wordLength = mb_strlen($word, 'UTF-8');
        
        for ($i = 0; $i < $wordLength; $i++) {
            $gridX = $direction === 'horizontal' ? $x + $i : $x;
            $gridY = $direction === 'horizontal' ? $y : $y + $i;
            
            $grid[$gridY][$gridX] = '';
        }
    }
    
    /**
     * 교차점 개수 계산
     */
    private function countIntersections(array $placedWords): int
    {
        $intersections = 0;
        
        for ($i = 0; $i < count($placedWords); $i++) {
            for ($j = $i + 1; $j < count($placedWords); $j++) {
                $word1 = $placedWords[$i];
                $word2 = $placedWords[$j];
                
                if ($this->wordsIntersect($word1, $word2)) {
                    $intersections++;
                }
            }
        }
        
        return $intersections;
    }
    
    /**
     * 두 단어가 교차하는지 확인
     */
    private function wordsIntersect(array $word1, array $word2): bool
    {
        // 서로 다른 방향이어야 교차 가능
        if ($word1['direction'] === $word2['direction']) {
            return false;
        }
        
        $word1Length = mb_strlen($word1['word'], 'UTF-8');
        $word2Length = mb_strlen($word2['word'], 'UTF-8');
        
        // 가로 단어와 세로 단어의 교차점 확인
        if ($word1['direction'] === 'horizontal') {
            $horizontal = $word1;
            $vertical = $word2;
        } else {
            $horizontal = $word2;
            $vertical = $word1;
        }
        
        // 교차점 좌표 계산
        $crossX = $vertical['start_x'];
        $crossY = $horizontal['start_y'];
        
        // 교차점이 가로 단어 범위 내에 있는지 확인
        if ($crossX < $horizontal['start_x'] || $crossX >= $horizontal['start_x'] + mb_strlen($horizontal['word'], 'UTF-8')) {
            return false;
        }
        
        // 교차점이 세로 단어 범위 내에 있는지 확인
        if ($crossY < $vertical['start_y'] || $crossY >= $vertical['start_y'] + mb_strlen($vertical['word'], 'UTF-8')) {
            return false;
        }
        
        // 교차점의 문자가 일치하는지 확인
        $horizontalChar = mb_substr($horizontal['word'], $crossX - $horizontal['start_x'], 1, 'UTF-8');
        $verticalChar = mb_substr($vertical['word'], $crossY - $vertical['start_y'], 1, 'UTF-8');
        
        return $horizontalChar === $verticalChar;
    }
    
    /**
     * 빈 그리드 생성
     */
    private function createEmptyGrid(int $size): array
    {
        $grid = [];
        for ($y = 0; $y < $size; $y++) {
            $grid[$y] = [];
            for ($x = 0; $x < $size; $x++) {
                $grid[$y][$x] = '';
            }
        }
        return $grid;
    }
    
    /**
     * 단어 위치 정보 생성
     */
    private function generateWordPositions(array $placedWords): array
    {
        $positions = [];
        
        foreach ($placedWords as $index => $word) {
            $wordLength = mb_strlen($word['word'], 'UTF-8');
            $wordPositions = [];
            
            for ($i = 0; $i < $wordLength; $i++) {
                $x = $word['direction'] === 'horizontal' ? $word['start_x'] + $i : $word['start_x'];
                $y = $word['direction'] === 'horizontal' ? $word['start_y'] : $word['start_y'] + $i;
                
                $wordPositions[] = [
                    'x' => $x,
                    'y' => $y,
                    'char' => mb_substr($word['word'], $i, 1, 'UTF-8')
                ];
            }
            
            $positions[] = [
                'word_id' => $word['id'],
                'word' => $word['word'],
                'start_x' => $word['start_x'],
                'start_y' => $word['start_y'],
                'direction' => $word['direction'],
                'length' => $wordLength,
                'positions' => $wordPositions,
                'clue' => ($word['direction'] === 'horizontal' ? '가로' : '세로') . ' ' . ($index + 1)
            ];
        }
        
        return $positions;
    }
    
    /**
     * 힌트 생성
     */
    private function generateHints(Collection $words): array
    {
        $hints = [];
        
        foreach ($words as $word) {
            $primaryHint = PzHint::where('word_id', $word['id'])
                ->where('is_primary', true)
                ->first();
                
            if ($primaryHint) {
                $hints[] = [
                    'word' => $word['word'],
                    'hint' => $primaryHint->hint_text,
                    'type' => $primaryHint->hint_type,
                    'difficulty' => $primaryHint->difficulty
                ];
            } else {
                // 기본 힌트가 없으면 단어 자체를 힌트로 사용
                $hints[] = [
                    'word' => $word['word'],
                    'hint' => "단어: {$word['word']}",
                    'type' => 'text',
                    'difficulty' => 'easy'
                ];
            }
        }
        
        return $hints;
    }
} 