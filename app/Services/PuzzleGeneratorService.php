<?php

namespace App\Services;

use App\Models\PuzzleLevel;
use App\Models\PuzzleGridRules;
use App\Models\PzWord;
use App\Models\PzHint;
use App\Models\PuzzleSessions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PuzzleGeneratorService
{
    /**
     * 레벨에 맞는 퍼즐 생성
     */
    public function generatePuzzle(int $levelId, int $userId = null): array
    {
        $level = PuzzleLevel::findOrFail($levelId);
        $gridRules = PuzzleGridRules::where('level_id', $levelId)->first();

        // 레벨에 대한 규칙이 없으면, 오류가 발생하지 않도록 기본 규칙 객체를 생성합니다.
        if (!$gridRules) {
            $gridRules = (object) [
                'min_word_length' => 3,
                'max_word_length' => 15,
                'grid_size_min' => 5,
                'grid_size_max' => 15,
                'symmetry_required' => true,
                'black_square_ratio' => 0.17,
            ];
        }
        
        // 레벨에 맞는 단어들 선택
        $words = $this->selectWordsForLevel($level, $gridRules);

        // 생성할 단어를 찾지 못했다면, 서버가 다운되는 대신 예외를 발생시킵니다.
        if ($words->isEmpty()) {
            throw new \Exception("레벨 {$levelId}에 대한 단어를 찾을 수 없습니다. 단어와 레벨 설정을 확인해주세요.");
        }
        
        // 그리드 생성
        $grid = $this->generateGrid($words, $gridRules);
        
        // 힌트 생성
        $hints = $this->generateHints($words);
        
        return [
            'grid' => $grid,
            'words' => $words->toArray(),
            'hints' => $hints,
            'level' => $level->toArray(),
            'session_data' => [
                'user_id' => $userId,
                'level_id' => $levelId,
                'started_at' => now(),
                'expires_at' => now()->addHours(2), // 2시간 후 만료
            ]
        ];
    }

    /**
     * 레벨에 맞는 단어들 선택
     */
    private function selectWordsForLevel(PuzzleLevel $level, $gridRules): Collection
    {
        // 레벨에 맞는 난이도로 단어 선택
        $difficulty = $this->mapLevelToDifficulty($level->level);
        
        $words = PzWord::active()
            ->byDifficulty($difficulty)
            ->whereBetween('length', [$gridRules->min_word_length, $gridRules->max_word_length])
            ->inRandomOrder()
            ->limit(10) // 최대 10개 단어 선택
            ->get();

        return $words;
    }

    private function mapLevelToDifficulty(int $levelNumber): string
    {
        if ($levelNumber <= 30) {
            return 'easy';
        } elseif ($levelNumber <= 70) {
            return 'medium';
        } else {
            return 'hard';
        }
    }

    /**
     * 크로스워드 그리드 생성
     */
    private function generateGrid(Collection $words, $gridRules): array
    {
        // 간단한 그리드 생성 로직 (실제로는 더 복잡한 알고리즘이 필요)
        $gridSize = min(max($gridRules->grid_size_min, 8), $gridRules->grid_size_max);
        
        // 빈 그리드 생성
        $grid = [];
        for ($i = 0; $i < $gridSize; $i++) {
            $grid[$i] = array_fill(0, $gridSize, '');
        }
        
        // 첫 번째 단어를 가로로 배치 (오류 수정)
        $firstWord = $words->first();
        if ($firstWord) {
            $wordLength = mb_strlen($firstWord->word, 'UTF-8');
            $startCol = max(0, floor(($gridSize - $wordLength) / 2));
            
            for ($i = 0; $i < $wordLength; $i++) {
                $grid[floor($gridSize / 2)][$startCol + $i] = mb_substr($firstWord->word, $i, 1, 'UTF-8');
            }
        }
        
        return $grid;
    }

    /**
     * 최적 그리드 크기 계산
     */
    private function calculateOptimalGridSize(Collection $words, $gridRules): array
    {
        $maxWordLength = $words->max('length');
        $wordCount = $words->count();
        
        $minSize = $gridRules->grid_size_min ?? 5;
        $maxSize = $gridRules->grid_size_max ?? 15;
        
        // 단어 개수와 최대 길이를 고려한 크기 계산
        $estimatedSize = max($minSize, min($maxSize, max($maxWordLength + 2, $wordCount + 2)));
        
        return [
            'width' => $estimatedSize,
            'height' => $estimatedSize
        ];
    }

    /**
     * 빈 그리드 생성
     */
    private function createEmptyGrid(int $width, int $height): array
    {
        $grid = [];
        for ($y = 0; $y < $height; $y++) {
            $grid[$y] = [];
            for ($x = 0; $x < $width; $x++) {
                $grid[$y][$x] = '';
            }
        }
        return $grid;
    }

    /**
     * 그리드에 단어 배치
     */
    private function placeWordInGrid(array &$grid, string $word): bool
    {
        $height = count($grid);
        $width = count($grid[0]);
        $wordLength = strlen($word);
        
        // 가능한 모든 위치 시도
        $attempts = 0;
        $maxAttempts = 100;
        
        while ($attempts < $maxAttempts) {
            $x = rand(0, $width - $wordLength);
            $y = rand(0, $height - 1);
            $direction = rand(0, 1); // 0: 가로, 1: 세로
            
            if ($this->canPlaceWord($grid, $word, $x, $y, $direction)) {
                $this->placeWord($grid, $word, $x, $y, $direction);
                return true;
            }
            
            $attempts++;
        }
        
        return false;
    }

    /**
     * 단어 배치 가능 여부 확인
     */
    private function canPlaceWord(array $grid, string $word, int $x, int $y, int $direction): bool
    {
        $wordLength = strlen($word);
        $height = count($grid);
        $width = count($grid[0]);
        
        // 경계 확인
        if ($direction == 0) { // 가로
            if ($x + $wordLength > $width) return false;
        } else { // 세로
            if ($y + $wordLength > $height) return false;
        }
        
        // 기존 문자와의 충돌 확인
        for ($i = 0; $i < $wordLength; $i++) {
            $char = $word[$i];
            $gridX = $direction == 0 ? $x + $i : $x;
            $gridY = $direction == 0 ? $y : $y + $i;
            
            $gridChar = $grid[$gridY][$gridX];
            
            if ($gridChar !== '' && $gridChar !== $char) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * 그리드에 단어 배치
     */
    private function placeWord(array &$grid, string $word, int $x, int $y, int $direction): void
    {
        $wordLength = strlen($word);
        
        for ($i = 0; $i < $wordLength; $i++) {
            $char = $word[$i];
            $gridX = $direction == 0 ? $x + $i : $x;
            $gridY = $direction == 0 ? $y : $y + $i;
            
            $grid[$gridY][$gridX] = $char;
        }
    }

    /**
     * 그리드 확장
     */
    private function expandGrid(array $grid): array
    {
        $height = count($grid);
        $width = count($grid[0]);
        
        $newGrid = $this->createEmptyGrid($width + 2, $height + 2);
        
        // 기존 그리드 내용을 새 그리드 중앙에 복사
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $newGrid[$y + 1][$x + 1] = $grid[$y][$x];
            }
        }
        
        return $newGrid;
    }

    /**
     * 검은 칸 추가 (대칭성 유지)
     */
    private function addBlackSquares(array $grid, $gridRules): array
    {
        $height = count($grid);
        $width = count($grid[0]);
        $targetRatio = $gridRules->black_square_ratio ?? 0.17;
        
        // 빈 칸들을 찾아서 검은 칸으로 변환
        $emptyCells = [];
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($grid[$y][$x] === '') {
                    $emptyCells[] = ['x' => $x, 'y' => $y];
                }
            }
        }
        
        // 대칭성 유지를 위해 셀 쌍으로 처리
        $blackSquaresNeeded = (int)($targetRatio * $width * $height);
        $blackSquaresAdded = 0;
        
        shuffle($emptyCells);
        
        foreach ($emptyCells as $cell) {
            if ($blackSquaresAdded >= $blackSquaresNeeded) break;
            
            $x = $cell['x'];
            $y = $cell['y'];
            
            // 대칭 위치 계산
            $symX = $width - 1 - $x;
            $symY = $height - 1 - $y;
            
            // 대칭 위치도 빈 칸인지 확인
            if ($grid[$y][$x] === '' && $grid[$symY][$symX] === '') {
                $grid[$y][$x] = '#';
                $grid[$symY][$symX] = '#';
                $blackSquaresAdded += 2;
            }
        }
        
        return $grid;
    }

    /**
     * 힌트 생성
     */
    private function generateHints(Collection $words): array
    {
        $hints = [];
        
        foreach ($words as $word) {
            $primaryHint = $word->primaryHint;
            if ($primaryHint) {
                $hints[] = [
                    'word' => $word->word,
                    'hint' => $primaryHint->hint_text,
                    'type' => $primaryHint->hint_type,
                ];
            } else {
                // 기본 힌트가 없으면 단어 자체를 힌트로 사용
                $hints[] = [
                    'word' => $word->word,
                    'hint' => "단어: {$word->word}",
                    'type' => 'text',
                ];
            }
        }
        
        return $hints;
    }

    /**
     * 템플릿 기반 퍼즐 생성
     */
    public function generatePuzzleFromTemplate(int $levelId, int $templateId = null): array
    {
        $level = PuzzleLevel::findOrFail($levelId);
        
        if ($templateId) {
            $template = PuzzleGridTemplates::findOrFail($templateId);
            return [
                'level' => $level,
                'grid' => $template->grid_pattern,
                'word_positions' => $template->word_positions,
                'template' => $template
            ];
        }
        
        // 템플릿이 없으면 동적 생성
        return $this->generatePuzzle($levelId);
    }
} 