<?php

namespace App\Services;

class CrosswordPatternGenerator
{
    /**
     * 크로스워드 규칙에 맞는 패턴 생성
     */
    public function generatePattern($size, $wordCount, $intersectionCount)
    {
        $grid = array_fill(0, $size, array_fill(0, $size, 1)); // 모든 칸을 흰칸으로 초기화
        
        // 1. 가로 단어들 배치
        $grid = $this->placeHorizontalWords($grid, $wordCount);
        
        // 2. 세로 단어들 배치 (교차점 고려)
        $grid = $this->placeVerticalWords($grid, $intersectionCount);
        
        // 3. 크로스워드 규칙 검증 및 수정
        $grid = $this->validateAndFixCrosswordRules($grid);
        
        // 4. 단어 위치 추출
        $wordPositions = $this->extractWordPositions($grid);
        
        return [
            'grid_pattern' => $grid,
            'word_positions' => $wordPositions,
            'metadata' => [
                'size' => $size,
                'word_count' => count($wordPositions),
                'intersection_count' => $this->countIntersections($wordPositions),
                'black_cell_ratio' => $this->calculateBlackRatio($grid)
            ]
        ];
    }
    
    /**
     * 가로 단어들 배치
     */
    private function placeHorizontalWords($grid, $wordCount)
    {
        $size = count($grid);
        $placedWords = 0;
        $maxAttempts = 100;
        $attempts = 0;
        
        while ($placedWords < $wordCount && $attempts < $maxAttempts) {
            $attempts++;
            
            // 랜덤 행 선택
            $row = rand(0, $size - 1);
            
            // 랜덤 길이 (2~4칸)
            $length = rand(2, min(4, $size - 2));
            
            // 랜덤 시작 위치
            $startCol = rand(0, $size - $length);
            
            // 해당 위치에 단어를 배치할 수 있는지 확인
            if ($this->canPlaceHorizontalWord($grid, $row, $startCol, $length)) {
                // 단어 배치
                for ($i = 0; $i < $length; $i++) {
                    $grid[$row][$startCol + $i] = 2;
                }
                $placedWords++;
            }
        }
        
        return $grid;
    }
    
    /**
     * 세로 단어들 배치 (교차점 고려)
     */
    private function placeVerticalWords($grid, $intersectionCount)
    {
        $size = count($grid);
        $placedWords = 0;
        $maxAttempts = 100;
        $attempts = 0;
        
        while ($placedWords < $intersectionCount && $attempts < $maxAttempts) {
            $attempts++;
            
            // 랜덤 열 선택
            $col = rand(0, $size - 1);
            
            // 랜덤 길이 (2~4칸)
            $length = rand(2, min(4, $size - 2));
            
            // 랜덤 시작 위치
            $startRow = rand(0, $size - $length);
            
            // 해당 위치에 단어를 배치할 수 있는지 확인
            if ($this->canPlaceVerticalWord($grid, $startRow, $col, $length)) {
                // 단어 배치
                for ($i = 0; $i < $length; $i++) {
                    $grid[$startRow + $i][$col] = 2;
                }
                $placedWords++;
            }
        }
        
        return $grid;
    }
    
    /**
     * 가로 단어 배치 가능 여부 확인
     */
    private function canPlaceHorizontalWord($grid, $row, $startCol, $length)
    {
        $size = count($grid);
        
        // 범위 확인
        if ($startCol + $length > $size) {
            return false;
        }
        
        // 해당 위치가 모두 흰칸인지 확인
        for ($i = 0; $i < $length; $i++) {
            if ($grid[$row][$startCol + $i] != 1) {
                return false;
            }
        }
        
        // 단어들 간의 독립성 확인 (인접하지 않도록)
        // 좌우 끝에 검은칸이 있는지 확인
        if ($startCol > 0 && $grid[$row][$startCol - 1] == 2) {
            return false;
        }
        if ($startCol + $length < $size && $grid[$row][$startCol + $length] == 2) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 세로 단어 배치 가능 여부 확인
     */
    private function canPlaceVerticalWord($grid, $startRow, $col, $length)
    {
        $size = count($grid);
        
        // 범위 확인
        if ($startRow + $length > $size) {
            return false;
        }
        
        // 해당 위치가 모두 흰칸인지 확인
        for ($i = 0; $i < $length; $i++) {
            if ($grid[$startRow + $i][$col] != 1) {
                return false;
            }
        }
        
        // 단어들 간의 독립성 확인 (인접하지 않도록)
        // 상하 끝에 검은칸이 있는지 확인
        if ($startRow > 0 && $grid[$startRow - 1][$col] == 2) {
            return false;
        }
        if ($startRow + $length < $size && $grid[$startRow + $length][$col] == 2) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 크로스워드 규칙 검증 및 수정
     */
    private function validateAndFixCrosswordRules($grid)
    {
        $size = count($grid);
        $modifiedGrid = $grid;
        
        // 1. 독립된 1칸 검은칸 제거
        for ($i = 0; $i < $size; $i++) {
            for ($j = 0; $j < $size; $j++) {
                if ($modifiedGrid[$i][$j] == 2) {
                    if ($this->isIsolatedBlackCell($modifiedGrid, $i, $j)) {
                        $modifiedGrid[$i][$j] = 1;
                    }
                }
            }
        }
        
        // 2. 대각선 인접 검은칸 제거
        for ($i = 0; $i < $size; $i++) {
            for ($j = 0; $j < $size; $j++) {
                if ($modifiedGrid[$i][$j] == 2) {
                    if ($this->hasDiagonalAdjacent($modifiedGrid, $i, $j)) {
                        $modifiedGrid[$i][$j] = 1;
                    }
                }
            }
        }
        
        return $modifiedGrid;
    }
    
    /**
     * 독립된 1칸 검은칸인지 확인
     */
    private function isIsolatedBlackCell($grid, $row, $col)
    {
        $size = count($grid);
        $adjacentBlack = 0;
        
        // 상하좌우 확인
        if ($row > 0 && $grid[$row-1][$col] == 2) $adjacentBlack++;
        if ($row < $size-1 && $grid[$row+1][$col] == 2) $adjacentBlack++;
        if ($col > 0 && $grid[$row][$col-1] == 2) $adjacentBlack++;
        if ($col < $size-1 && $grid[$row][$col+1] == 2) $adjacentBlack++;
        
        return $adjacentBlack == 0;
    }
    
    /**
     * 대각선 인접 검은칸이 있는지 확인
     */
    private function hasDiagonalAdjacent($grid, $row, $col)
    {
        $size = count($grid);
        $diagonalBlack = 0;
        
        // 대각선 4방향 확인
        if ($row > 0 && $col > 0 && $grid[$row-1][$col-1] == 2) $diagonalBlack++;
        if ($row > 0 && $col < $size-1 && $grid[$row-1][$col+1] == 2) $diagonalBlack++;
        if ($row < $size-1 && $col > 0 && $grid[$row+1][$col-1] == 2) $diagonalBlack++;
        if ($row < $size-1 && $col < $size-1 && $grid[$row+1][$col+1] == 2) $diagonalBlack++;
        
        return $diagonalBlack > 0;
    }
    
    /**
     * 단어 위치 추출
     */
    private function extractWordPositions($grid)
    {
        $wordPositions = [];
        $wordId = 1;
        $size = count($grid);
        
        // 가로 단어 추출
        for ($i = 0; $i < $size; $i++) {
            $j = 0;
            while ($j < $size) {
                if ($grid[$i][$j] == 2) {
                    $startJ = $j;
                    while ($j < $size && $grid[$i][$j] == 2) {
                        $j++;
                    }
                    $endJ = $j - 1;
                    
                    if ($endJ - $startJ >= 1) {
                        $wordPositions[] = [
                            'id' => $wordId,
                            'start_x' => $startJ,
                            'start_y' => $i,
                            'end_x' => $endJ,
                            'end_y' => $i,
                            'direction' => 'horizontal',
                            'length' => $endJ - $startJ + 1
                        ];
                        $wordId++;
                    }
                } else {
                    $j++;
                }
            }
        }
        
        // 세로 단어 추출
        for ($j = 0; $j < $size; $j++) {
            $i = 0;
            while ($i < $size) {
                if ($grid[$i][$j] == 2) {
                    $startI = $i;
                    while ($i < $size && $grid[$i][$j] == 2) {
                        $i++;
                    }
                    $endI = $i - 1;
                    
                    if ($endI - $startI >= 1) {
                        $wordPositions[] = [
                            'id' => $wordId,
                            'start_x' => $j,
                            'start_y' => $startI,
                            'end_x' => $j,
                            'end_y' => $endI,
                            'direction' => 'vertical',
                            'length' => $endI - $startI + 1
                        ];
                        $wordId++;
                    }
                } else {
                    $i++;
                }
            }
        }
        
        return $wordPositions;
    }
    
    /**
     * 교차점 개수 계산
     */
    private function countIntersections($wordPositions)
    {
        $intersections = 0;
        $horizontalWords = array_filter($wordPositions, fn($w) => $w['direction'] == 'horizontal');
        $verticalWords = array_filter($wordPositions, fn($w) => $w['direction'] == 'vertical');
        
        foreach ($horizontalWords as $hWord) {
            foreach ($verticalWords as $vWord) {
                if ($hWord['start_x'] <= $vWord['start_x'] && $vWord['start_x'] <= $hWord['end_x'] &&
                    $vWord['start_y'] <= $hWord['start_y'] && $hWord['start_y'] <= $vWord['end_y']) {
                    $intersections++;
                }
            }
        }
        
        return $intersections;
    }
    
    /**
     * 검은칸 비율 계산
     */
    private function calculateBlackRatio($grid)
    {
        $size = count($grid);
        $totalCells = $size * $size;
        $blackCells = 0;
        
        foreach ($grid as $row) {
            foreach ($row as $cell) {
                if ($cell == 2) {
                    $blackCells++;
                }
            }
        }
        
        return $blackCells / $totalCells;
    }
} 