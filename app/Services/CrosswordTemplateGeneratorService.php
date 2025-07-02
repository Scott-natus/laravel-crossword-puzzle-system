<?php

namespace App\Services;

class CrosswordTemplateGeneratorService
{
    // 레벨 조건에 따른 그리드 크기 계산
    public function calculateGridSize($wordCount, $intersectionCount, $averageSyllables = 3)
    {
        $requiredBlackCells = ($wordCount * $averageSyllables) - $intersectionCount;
        $independentSpacing = $wordCount * 2;
        $bufferSpace = ceil($requiredBlackCells * 0.2);
        $totalRequiredCells = $requiredBlackCells + $independentSpacing + $bufferSpace;
        $gridSize = ceil(sqrt($totalRequiredCells));
        $gridSize = max(5, $gridSize);
        return [
            'size' => $gridSize,
            'required_black_cells' => $requiredBlackCells,
            'independent_spacing' => $independentSpacing,
            'buffer_space' => $bufferSpace,
            'total_required' => $totalRequiredCells
        ];
    }

    // 단어 크기 랜덤 생성 (그리드 크기와 어울리게 개선)
    public function generateWordSizes($wordCount, $gridSize = 5)
    {
        $maxTotal = intval($gridSize * $gridSize * 0.7);
        $sizes = array_fill(0, $wordCount, 2);
        $remaining = $maxTotal - array_sum($sizes);

        while ($remaining > 0) {
            $idx = rand(0, $wordCount - 1);
            if ($sizes[$idx] < min(4, $gridSize)) {
                $sizes[$idx]++;
                $remaining--;
            }
        }
        return $sizes;
    }

    // 그리드에 단어 배치
    public function placeWordsInGrid($gridSize, $wordSizes, $intersectionCount)
    {
        $grid = array_fill(0, $gridSize, array_fill(0, $gridSize, 1));
        $placedWords = [];
        $intersections = [];
        $firstWordSize = $wordSizes[0];
        $startX = rand(0, $gridSize - $firstWordSize);
        $startY = rand(0, $gridSize - 1);
        $placedWords[] = [
            'id' => 1,
            'start_x' => $startX,
            'start_y' => $startY,
            'end_x' => $startX + $firstWordSize - 1,
            'end_y' => $startY,
            'direction' => 'horizontal',
            'length' => $firstWordSize
        ];
        for ($i = 0; $i < $firstWordSize; $i++) {
            $grid[$startY][$startX + $i] = 2;
        }
        for ($wordIndex = 1; $wordIndex < count($wordSizes); $wordIndex++) {
            $wordSize = $wordSizes[$wordIndex];
            $placed = false;
            $maxAttempts = 100;
            $attempts = 0;
            while (!$placed && $attempts < $maxAttempts) {
                $attempts++;
                $direction = rand(0, 1) ? 'horizontal' : 'vertical';
                // 교차점이 필요한 경우 (가로와 세로 단어만 교차 가능)
                if (count($intersections) < $intersectionCount) {
                    $intersectionWord = $this->findIntersectionWord($grid, $wordSize, $direction, $placedWords);
                    if ($intersectionWord) {
                        $placedWords[] = $intersectionWord;
                        $intersections[] = [
                            'word1_id' => $intersectionWord['intersects_with'],
                            'word2_id' => $wordIndex + 1,
                            'x' => $intersectionWord['intersection_x'],
                            'y' => $intersectionWord['intersection_y']
                        ];
                        $this->placeWordInGrid($grid, $intersectionWord);
                        $placed = true;
                        continue;
                    }
                }
                $independentWord = $this->findIndependentPosition($grid, $wordSize, $direction, $placedWords);
                if ($independentWord) {
                    $placedWords[] = $independentWord;
                    $this->placeWordInGrid($grid, $independentWord);
                    $placed = true;
                }
            }
            if (!$placed) {
                $forcedWord = $this->forcePlaceWord($grid, $wordSize, $direction, $wordIndex + 1);
                $placedWords[] = $forcedWord;
                $this->placeWordInGrid($grid, $forcedWord);
            }
        }
        return [
            'grid' => $grid,
            'word_positions' => $placedWords,
            'intersections' => $intersections
        ];
    }

    private function findIntersectionWord($grid, $wordSize, $direction, $placedWords)
    {
        $gridSize = count($grid);
        foreach ($placedWords as $existingWord) {
            // 가로와 세로 단어만 교차 가능
            if ($existingWord['direction'] === $direction) {
                continue; // 같은 방향이면 교차 불가
            }
            
            // 가로 단어를 세로 단어와 교차
            if ($direction === 'horizontal') {
                for ($x = 0; $x <= $gridSize - $wordSize; $x++) {
                    for ($y = $existingWord['start_y']; $y <= $existingWord['end_y']; $y++) {
                        if ($this->canPlaceWordAt($grid, $x, $y, $wordSize, $direction, $placedWords)) {
                            return [
                                'id' => count($placedWords) + 1,
                                'start_x' => $x,
                                'start_y' => $y,
                                'end_x' => $x + $wordSize - 1,
                                'end_y' => $y,
                                'direction' => $direction,
                                'length' => $wordSize,
                                'intersects_with' => $existingWord['id'],
                                'intersection_x' => $x,
                                'intersection_y' => $y
                            ];
                        }
                    }
                }
            } 
            // 세로 단어를 가로 단어와 교차
            else {
                for ($x = $existingWord['start_x']; $x <= $existingWord['end_x']; $x++) {
                    for ($y = 0; $y <= $gridSize - $wordSize; $y++) {
                        if ($this->canPlaceWordAt($grid, $x, $y, $wordSize, $direction, $placedWords)) {
                            return [
                                'id' => count($placedWords) + 1,
                                'start_x' => $x,
                                'start_y' => $y,
                                'end_x' => $x,
                                'end_y' => $y + $wordSize - 1,
                                'direction' => $direction,
                                'length' => $wordSize,
                                'intersects_with' => $existingWord['id'],
                                'intersection_x' => $x,
                                'intersection_y' => $y
                            ];
                        }
                    }
                }
            }
        }
        return null;
    }

    private function findIndependentPosition($grid, $wordSize, $direction, $placedWords)
    {
        $gridSize = count($grid);
        for ($attempt = 0; $attempt < 50; $attempt++) {
            if ($direction === 'horizontal') {
                $x = rand(0, $gridSize - $wordSize);
                $y = rand(0, $gridSize - 1);
            } else {
                $x = rand(0, $gridSize - 1);
                $y = rand(0, $gridSize - $wordSize);
            }
            if ($this->canPlaceWordAt($grid, $x, $y, $wordSize, $direction, $placedWords)) {
                return [
                    'id' => count($placedWords) + 1,
                    'start_x' => $x,
                    'start_y' => $y,
                    'end_x' => $direction === 'horizontal' ? $x + $wordSize - 1 : $x,
                    'end_y' => $direction === 'vertical' ? $y + $wordSize - 1 : $y,
                    'direction' => $direction,
                    'length' => $wordSize
                ];
            }
        }
        return null;
    }

    // 강제 배치 시 그리드 전체를 순회하며 빈 공간에만 배치 (끝 우선 아님)
    private function forcePlaceWord($grid, $wordSize, $direction, $wordId)
    {
        $gridSize = count($grid);
        for ($y = 0; $y < $gridSize; $y++) {
            for ($x = 0; $x < $gridSize; $x++) {
                if ($direction === 'horizontal' && $x + $wordSize <= $gridSize) {
                    $canPlace = true;
                    for ($i = 0; $i < $wordSize; $i++) {
                        if ($grid[$y][$x + $i] === 2) {
                            $canPlace = false;
                            break;
                        }
                    }
                    if ($canPlace) {
                        return [
                            'id' => $wordId,
                            'start_x' => $x,
                            'start_y' => $y,
                            'end_x' => $x + $wordSize - 1,
                            'end_y' => $y,
                            'direction' => $direction,
                            'length' => $wordSize
                        ];
                    }
                }
                if ($direction === 'vertical' && $y + $wordSize <= $gridSize) {
                    $canPlace = true;
                    for ($i = 0; $i < $wordSize; $i++) {
                        if ($grid[$y + $i][$x] === 2) {
                            $canPlace = false;
                            break;
                        }
                    }
                    if ($canPlace) {
                        return [
                            'id' => $wordId,
                            'start_x' => $x,
                            'start_y' => $y,
                            'end_x' => $x,
                            'end_y' => $y + $wordSize - 1,
                            'direction' => $direction,
                            'length' => $wordSize
                        ];
                    }
                }
            }
        }
        // 완전히 불가하면 null 반환
        return null;
    }

    private function canPlaceWordAt($grid, $x, $y, $wordSize, $direction, $placedWords)
    {
        $gridSize = count($grid);
        if ($direction === 'horizontal') {
            if ($x + $wordSize > $gridSize) return false;
        } else {
            if ($y + $wordSize > $gridSize) return false;
        }
        foreach ($placedWords as $word) {
            if ($this->wordsOverlap($x, $y, $wordSize, $direction, $word)) {
                return false;
            }
        }
        foreach ($placedWords as $word) {
            if ($this->wordsAdjacent($x, $y, $wordSize, $direction, $word)) {
                return false;
            }
        }
        return true;
    }

    private function wordsOverlap($x1, $y1, $size1, $dir1, $word2)
    {
        $x2 = $word2['start_x'];
        $y2 = $word2['start_y'];
        $size2 = $word2['length'];
        $dir2 = $word2['direction'];
        if ($dir1 === $dir2) {
            if ($dir1 === 'horizontal') {
                return $y1 === $y2 && !($x1 + $size1 <= $x2 || $x2 + $size2 <= $x1);
            } else {
                return $x1 === $x2 && !($y1 + $size1 <= $y2 || $y2 + $size2 <= $y1);
            }
        }
        return false;
    }

    private function wordsAdjacent($x1, $y1, $size1, $dir1, $word2)
    {
        $dir2 = $word2['direction'];
        $overlapCount = 0;

        // 새 단어의 각 칸
        for ($i = 0; $i < $size1; $i++) {
            $cx = $dir1 === 'horizontal' ? $x1 + $i : $x1;
            $cy = $dir1 === 'vertical' ? $y1 + $i : $y1;

            // 기존 단어의 각 칸
            for ($j = 0; $j < $word2['length']; $j++) {
                $wx = $word2['direction'] === 'horizontal' ? $word2['start_x'] + $j : $word2['start_x'];
                $wy = $word2['direction'] === 'vertical' ? $word2['start_y'] + $j : $word2['start_y'];

                // 1. 같은 칸(겹침) 체크
                if ($cx === $wx && $cy === $wy) {
                    $overlapCount++;
                    // 교차점(가로/세로만)만 허용, 그 외 겹침은 금지
                    if ($overlapCount > 1 || $dir1 === $dir2) {
                        return true; // 겹침 금지
                    }
                    continue;
                }

                // 2. 상하좌우 인접 체크
                $adjacents = [
                    [$wx - 1, $wy], [$wx + 1, $wy], [$wx, $wy - 1], [$wx, $wy + 1]
                ];
                foreach ($adjacents as [$ax, $ay]) {
                    if ($cx === $ax && $cy === $ay) {
                        return true; // 인접 금지
                    }
                }
            }
        }
        // 교차점(한 칸만 겹침, 가로/세로만)은 허용
        return false;
    }

    private function isWordCell($x, $y, $word)
    {
        return $x >= $word['start_x'] && $x <= $word['end_x'] && 
               $y >= $word['start_y'] && $y <= $word['end_y'];
    }

    private function placeWordInGrid(&$grid, $word)
    {
        if ($word['direction'] === 'horizontal') {
            for ($i = 0; $i < $word['length']; $i++) {
                $grid[$word['start_y']][$word['start_x'] + $i] = 2;
            }
        } else {
            for ($i = 0; $i < $word['length']; $i++) {
                $grid[$word['start_y'] + $i][$word['start_x']] = 2;
            }
        }
    }

    // 레벨에 따른 샘플 템플릿 생성
    public function generateSampleTemplates($level, $sampleCount = 3)
    {
        $samples = [];
        for ($i = 0; $i < $sampleCount; $i++) {
            $gridInfo = $this->calculateGridSize($level->word_count, $level->intersection_count);
            $wordSizes = $this->generateWordSizes($level->word_count);
            $result = $this->placeWordsInGrid($gridInfo['size'], $wordSizes, $level->intersection_count);
            $blackCells = 0;
            $whiteCells = 0;
            foreach ($result['grid'] as $row) {
                foreach ($row as $cell) {
                    if ($cell === 2) $blackCells++;
                    else $whiteCells++;
                }
            }
            $samples[] = [
                'name' => "샘플 " . ($i + 1),
                'description' => "{$gridInfo['size']}×{$gridInfo['size']} 그리드, 검은색 {$blackCells}칸, 흰색 {$whiteCells}칸",
                'grid' => $result['grid'],
                'word_positions' => $result['word_positions'],
                'intersections' => $result['intersections'],
                'black_cells' => $blackCells,
                'white_cells' => $whiteCells,
                'grid_size' => $gridInfo['size'],
                'word_count' => $level->word_count,
                'intersection_count' => count($result['intersections'])
            ];
        }
        return $samples;
    }
}
