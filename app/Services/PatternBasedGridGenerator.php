<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PatternBasedGridGenerator
{
    /**
     * 실제 DB 템플릿에서 추출한 패턴 데이터
     */
    private $patternTemplates = [
        'cross_5x5' => [
            'name' => '십자형 패턴 (5x5)',
            'grid_pattern' => [
                [2,2,1,1,1],  // 상단 가로 단어 (2칸)
                [1,1,1,1,1],  // 빈 공간
                [1,1,2,1,1],  // 중앙 세로 단어 (1칸)
                [1,1,1,1,1],  // 빈 공간
                [1,1,1,2,2]   // 하단 가로 단어 (2칸)
            ],
            'word_structure' => [
                'horizontal' => 2,
                'vertical' => 1,
                'total' => 3
            ],
            'description' => '크로스워드 규칙에 맞는 기본 십자형 패턴',
            'black_cells' => 5,
            'white_cells' => 20
        ],
        'l_shape_5x5' => [
            'name' => 'L자형 패턴 (5x5)',
            'grid_pattern' => [
                [2,2,1,1,1],  // 상단 가로 단어 (2칸)
                [1,1,1,1,1],  // 빈 공간
                [1,1,1,1,1],  // 빈 공간
                [1,1,1,1,1],  // 빈 공간
                [1,1,1,2,2]   // 하단 가로 단어 (2칸)
            ],
            'word_structure' => [
                'horizontal' => 2,
                'vertical' => 0,
                'total' => 2
            ],
            'description' => '크로스워드 규칙에 맞는 L자형 패턴',
            'black_cells' => 4,
            'white_cells' => 21
        ],
        'mesh_5x5' => [
            'name' => '그물형 패턴 (5x5)',
            'grid_pattern' => [
                [2,2,1,1,1],  // 상단 가로 단어 (2칸)
                [1,1,1,1,1],  // 빈 공간
                [1,1,2,1,1],  // 중앙 세로 단어 (1칸)
                [1,1,1,1,1],  // 빈 공간
                [1,1,1,2,2]   // 하단 가로 단어 (2칸)
            ],
            'word_structure' => [
                'horizontal' => 2,
                'vertical' => 1,
                'total' => 3
            ],
            'description' => '크로스워드 규칙에 맞는 그물형 패턴',
            'black_cells' => 5,
            'white_cells' => 20
        ],
        'symmetric_6x6' => [
            'name' => '대칭형 패턴 (6x6)',
            'grid_pattern' => [
                [1,2,2,2,2,1],  // 상단 가로 단어
                [1,1,1,1,2,1],  // 세로 단어들
                [1,1,1,2,2,2],  // 중앙 가로 단어
                [2,2,2,1,1,1],  // 중앙 가로 단어
                [1,1,1,2,2,1],  // 하단 가로 단어
                [2,2,1,1,1,1]   // 하단 가로 단어
            ],
            'word_structure' => [
                'horizontal' => 4,
                'vertical' => 2,
                'total' => 6
            ],
            'description' => '좌우 대칭 구조, 안정적인 퍼즐 레이아웃',
            'black_cells' => 15,
            'white_cells' => 21
        ]
    ];

    /**
     * 패턴 기반 그리드 생성 (실시간 변형)
     */
    public function generateByPattern($patternName, $variation = 0)
    {
        if (!isset($this->patternTemplates[$patternName])) {
            throw new \InvalidArgumentException("지원하지 않는 패턴: {$patternName}");
        }

        $template = $this->patternTemplates[$patternName];
        $baseGrid = $template['grid_pattern'];

        // 실시간 변형 적용 (기본 변형 + 랜덤 세부 조정)
        $modifiedGrid = $this->applyRealTimeVariation($baseGrid, $variation);

        // 단어 위치 추출
        $wordPositions = $this->extractWordPositions($modifiedGrid);

        // 메타데이터 생성
        $metadata = [
            'pattern_name' => $patternName,
            'variation' => $variation,
            'description' => $template['description'],
            'word_structure' => $template['word_structure'],
            'grid_size' => count($modifiedGrid) . 'x' . count($modifiedGrid[0]),
            'black_cell_ratio' => $this->calculateBlackRatio($modifiedGrid)
        ];

        return [
            'grid_pattern' => $modifiedGrid,
            'word_positions' => $wordPositions,
            'metadata' => $metadata
        ];
    }

    /**
     * 실시간 변형 적용 (기본 변형 + 랜덤 세부 조정)
     */
    private function applyRealTimeVariation($grid, $variation)
    {
        // 1. 기본 변형 적용
        $modifiedGrid = $this->applyVariation($grid, $variation);
        
        // 2. 랜덤 세부 조정 (패턴의 기본 구조는 유지하되 세부 배치 변경)
        $modifiedGrid = $this->applyRandomAdjustments($modifiedGrid);
        
        // 3. 추가 랜덤 변형 (더 다양한 결과를 위해)
        $modifiedGrid = $this->applyAdditionalRandomVariation($modifiedGrid);
        
        return $modifiedGrid;
    }

    /**
     * 그리드에 변형 적용
     */
    private function applyVariation($grid, $variation)
    {
        switch ($variation) {
            case 1:
                return $this->rotateGrid($grid, 1); // 90도 회전
            case 2:
                return $this->rotateGrid($grid, 2); // 180도 회전
            case 3:
                return $this->flipHorizontal($grid); // 좌우 반전
            default:
                return $grid;
        }
    }

    /**
     * 랜덤 세부 조정 적용 (크로스워드 규칙 준수)
     */
    private function applyRandomAdjustments($grid)
    {
        $rows = count($grid);
        $cols = count($grid[0]);
        $modifiedGrid = $grid;
        
        // 50% 확률로 크로스워드 규칙 적용
        if (rand(1, 100) <= 50) {
            // 1. 크로스워드 규칙에 맞게 수정
            $modifiedGrid = $this->fixCrosswordRules($modifiedGrid);
        }
        
        return $modifiedGrid;
    }

    /**
     * 랜덤하게 검은칸을 흰칸으로 변경
     */
    private function randomlyChangeBlackToWhite($grid)
    {
        $rows = count($grid);
        $cols = count($grid[0]);
        
        // 검은칸 위치들 찾기
        $blackPositions = [];
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                if ($grid[$i][$j] == 2) {
                    $blackPositions[] = [$i, $j];
                }
            }
        }
        
        if (empty($blackPositions)) {
            return $grid;
        }
        
        // 랜덤하게 하나 선택하여 변경
        $randomPos = $blackPositions[array_rand($blackPositions)];
        $grid[$randomPos[0]][$randomPos[1]] = 1;
        
        return $grid;
    }

    /**
     * 크로스워드 규칙에 맞게 수정
     */
    private function fixCrosswordRules($grid)
    {
        $rows = count($grid);
        $cols = count($grid[0]);
        $modifiedGrid = $grid;
        
        // 1. 가로 방향으로만 연결된 검은칸 그룹 찾기
        $horizontalGroups = $this->findHorizontalGroups($modifiedGrid);
        
        // 2. 세로 방향으로만 연결된 검은칸 그룹 찾기
        $verticalGroups = $this->findVerticalGroups($modifiedGrid);
        
        // 3. 모든 검은칸을 흰칸으로 초기화
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                $modifiedGrid[$i][$j] = 1;
            }
        }
        
        // 4. 가로 그룹들을 다시 배치 (2칸 이상인 것만)
        foreach ($horizontalGroups as $group) {
            if (count($group) >= 2) {
                foreach ($group as $pos) {
                    $modifiedGrid[$pos[0]][$pos[1]] = 2;
                }
            }
        }
        
        // 5. 세로 그룹들을 다시 배치 (2칸 이상인 것만)
        foreach ($verticalGroups as $group) {
            if (count($group) >= 2) {
                foreach ($group as $pos) {
                    $modifiedGrid[$pos[0]][$pos[1]] = 2;
                }
            }
        }
        
        return $modifiedGrid;
    }

    /**
     * 가로 방향 그룹 찾기
     */
    private function findHorizontalGroups($grid)
    {
        $rows = count($grid);
        $cols = count($grid[0]);
        $groups = [];
        
        for ($i = 0; $i < $rows; $i++) {
            $currentGroup = [];
            for ($j = 0; $j < $cols; $j++) {
                if ($grid[$i][$j] == 2) {
                    $currentGroup[] = [$i, $j];
                } else {
                    if (count($currentGroup) >= 2) {
                        $groups[] = $currentGroup;
                    }
                    $currentGroup = [];
                }
            }
            // 행 끝에서 그룹 처리
            if (count($currentGroup) >= 2) {
                $groups[] = $currentGroup;
            }
        }
        
        return $groups;
    }

    /**
     * 세로 방향 그룹 찾기
     */
    private function findVerticalGroups($grid)
    {
        $rows = count($grid);
        $cols = count($grid[0]);
        $groups = [];
        
        for ($j = 0; $j < $cols; $j++) {
            $currentGroup = [];
            for ($i = 0; $i < $rows; $i++) {
                if ($grid[$i][$j] == 2) {
                    $currentGroup[] = [$i, $j];
                } else {
                    if (count($currentGroup) >= 2) {
                        $groups[] = $currentGroup;
                    }
                    $currentGroup = [];
                }
            }
            // 열 끝에서 그룹 처리
            if (count($currentGroup) >= 2) {
                $groups[] = $currentGroup;
            }
        }
        
        return $groups;
    }

    /**
     * 독립된 1칸 검은칸만 제거 (간단한 버전)
     */
    private function removeIsolatedSingleCells($grid)
    {
        $rows = count($grid);
        $cols = count($grid[0]);
        $modifiedGrid = $grid;
        
        // 독립된 1칸 검은칸만 제거
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                if ($modifiedGrid[$i][$j] == 2) {
                    if ($this->isIsolatedBlackCell($modifiedGrid, $i, $j)) {
                        $modifiedGrid[$i][$j] = 1; // 흰칸으로 변경
                    }
                }
            }
        }
        
        return $modifiedGrid;
    }

    /**
     * 크로스워드 규칙 검증 및 수정
     */
    private function validateAndFixCrosswordRules($grid)
    {
        $rows = count($grid);
        $cols = count($grid[0]);
        $modifiedGrid = $grid;
        
        // 1. 독립된 1칸 검은칸 제거
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                if ($modifiedGrid[$i][$j] == 2) {
                    if ($this->isIsolatedBlackCell($modifiedGrid, $i, $j)) {
                        $modifiedGrid[$i][$j] = 1; // 흰칸으로 변경
                    }
                }
            }
        }
        
        // 2. 대각선 인접 검은칸 제거 (가로형/세로형만 허용)
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                if ($modifiedGrid[$i][$j] == 2) {
                    if ($this->hasDiagonalAdjacent($modifiedGrid, $i, $j)) {
                        $modifiedGrid[$i][$j] = 1; // 흰칸으로 변경
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
        $rows = count($grid);
        $cols = count($grid[0]);
        
        // 상하좌우 확인
        $adjacentBlack = 0;
        
        // 위
        if ($row > 0 && $grid[$row-1][$col] == 2) $adjacentBlack++;
        // 아래
        if ($row < $rows-1 && $grid[$row+1][$col] == 2) $adjacentBlack++;
        // 왼쪽
        if ($col > 0 && $grid[$row][$col-1] == 2) $adjacentBlack++;
        // 오른쪽
        if ($col < $cols-1 && $grid[$row][$col+1] == 2) $adjacentBlack++;
        
        return $adjacentBlack == 0; // 인접한 검은칸이 없으면 독립된 1칸
    }

    /**
     * 대각선 인접 검은칸이 있는지 확인
     */
    private function hasDiagonalAdjacent($grid, $row, $col)
    {
        $rows = count($grid);
        $cols = count($grid[0]);
        
        // 대각선 4방향 확인
        $diagonalBlack = 0;
        
        // 좌상단
        if ($row > 0 && $col > 0 && $grid[$row-1][$col-1] == 2) $diagonalBlack++;
        // 우상단
        if ($row > 0 && $col < $cols-1 && $grid[$row-1][$col+1] == 2) $diagonalBlack++;
        // 좌하단
        if ($row < $rows-1 && $col > 0 && $grid[$row+1][$col-1] == 2) $diagonalBlack++;
        // 우하단
        if ($row < $rows-1 && $col < $cols-1 && $grid[$row+1][$col+1] == 2) $diagonalBlack++;
        
        return $diagonalBlack > 0; // 대각선 인접 검은칸이 있으면 true
    }

    /**
     * 단어 길이 조정 (2칸 이상 보장)
     */
    private function adjustWordLengths($grid)
    {
        $rows = count($grid);
        $cols = count($grid[0]);
        $modifiedGrid = $grid;
        
        // 가로 단어 길이 조정
        for ($i = 0; $i < $rows; $i++) {
            $wordStart = -1;
            $wordLength = 0;
            
            for ($j = 0; $j < $cols; $j++) {
                if ($modifiedGrid[$i][$j] == 2) {
                    if ($wordStart == -1) {
                        $wordStart = $j;
                    }
                    $wordLength++;
                } else {
                    if ($wordLength == 1) {
                        // 1칸 단어는 흰칸으로 변경
                        $modifiedGrid[$i][$wordStart] = 1;
                    }
                    $wordStart = -1;
                    $wordLength = 0;
                }
            }
            
            // 행 끝에서 1칸 단어 처리
            if ($wordLength == 1) {
                $modifiedGrid[$i][$wordStart] = 1;
            }
        }
        
        // 세로 단어 길이 조정
        for ($j = 0; $j < $cols; $j++) {
            $wordStart = -1;
            $wordLength = 0;
            
            for ($i = 0; $i < $rows; $i++) {
                if ($modifiedGrid[$i][$j] == 2) {
                    if ($wordStart == -1) {
                        $wordStart = $i;
                    }
                    $wordLength++;
                } else {
                    if ($wordLength == 1) {
                        // 1칸 단어는 흰칸으로 변경
                        $modifiedGrid[$wordStart][$j] = 1;
                    }
                    $wordStart = -1;
                    $wordLength = 0;
                }
            }
            
            // 열 끝에서 1칸 단어 처리
            if ($wordLength == 1) {
                $modifiedGrid[$wordStart][$j] = 1;
            }
        }
        
        return $modifiedGrid;
    }

    /**
     * 단어 독립성 보장 (단어끼리 인접하지 않도록)
     */
    private function ensureWordIndependence($grid)
    {
        $rows = count($grid);
        $cols = count($grid[0]);
        $modifiedGrid = $grid;
        
        // 단어 그룹 찾기
        $wordGroups = $this->findWordGroups($modifiedGrid);
        
        // 단어 그룹 간 인접 확인 및 수정
        for ($i = 0; $i < count($wordGroups); $i++) {
            for ($j = $i + 1; $j < count($wordGroups); $j++) {
                if ($this->areWordGroupsAdjacent($wordGroups[$i], $wordGroups[$j])) {
                    // 인접한 단어 그룹 중 하나를 흰칸으로 변경
                    $modifiedGrid = $this->separateWordGroups($modifiedGrid, $wordGroups[$i], $wordGroups[$j]);
                }
            }
        }
        
        return $modifiedGrid;
    }

    /**
     * 단어 그룹 찾기
     */
    private function findWordGroups($grid)
    {
        $rows = count($grid);
        $cols = count($grid[0]);
        $visited = array_fill(0, $rows, array_fill(0, $cols, false));
        $wordGroups = [];
        
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                if ($grid[$i][$j] == 2 && !$visited[$i][$j]) {
                    $group = [];
                    $this->dfsWordGroup($grid, $i, $j, $visited, $group);
                    if (count($group) >= 2) { // 2칸 이상인 그룹만
                        $wordGroups[] = $group;
                    }
                }
            }
        }
        
        return $wordGroups;
    }

    /**
     * DFS로 단어 그룹 탐색
     */
    private function dfsWordGroup($grid, $row, $col, &$visited, &$group)
    {
        $rows = count($grid);
        $cols = count($grid[0]);
        
        if ($row < 0 || $row >= $rows || $col < 0 || $col >= $cols || 
            $visited[$row][$col] || $grid[$row][$col] != 2) {
            return;
        }
        
        $visited[$row][$col] = true;
        $group[] = [$row, $col];
        
        // 상하좌우만 탐색 (대각선 제외)
        $this->dfsWordGroup($grid, $row-1, $col, $visited, $group);
        $this->dfsWordGroup($grid, $row+1, $col, $visited, $group);
        $this->dfsWordGroup($grid, $row, $col-1, $visited, $group);
        $this->dfsWordGroup($grid, $row, $col+1, $visited, $group);
    }

    /**
     * 단어 그룹 간 인접 여부 확인
     */
    private function areWordGroupsAdjacent($group1, $group2)
    {
        foreach ($group1 as $pos1) {
            foreach ($group2 as $pos2) {
                $rowDiff = abs($pos1[0] - $pos2[0]);
                $colDiff = abs($pos1[1] - $pos2[1]);
                
                // 상하좌우 인접 (대각선 제외)
                if (($rowDiff == 1 && $colDiff == 0) || ($rowDiff == 0 && $colDiff == 1)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 단어 그룹 분리
     */
    private function separateWordGroups($grid, $group1, $group2)
    {
        // 더 작은 그룹을 흰칸으로 변경
        if (count($group1) <= count($group2)) {
            foreach ($group1 as $pos) {
                $grid[$pos[0]][$pos[1]] = 1;
            }
        } else {
            foreach ($group2 as $pos) {
                $grid[$pos[0]][$pos[1]] = 1;
            }
        }
        
        return $grid;
    }

    /**
     * 추가 랜덤 변형 적용
     */
    private function applyAdditionalRandomVariation($grid)
    {
        $rows = count($grid);
        $cols = count($grid[0]);
        $modifiedGrid = $grid;
        
        // 40% 확률로 추가 변형 적용
        if (rand(1, 100) <= 40) {
            // 1. 일부 검은칸을 흰칸으로 변경
            $blackToWhiteCount = rand(1, 2);
            for ($i = 0; $i < $blackToWhiteCount; $i++) {
                $modifiedGrid = $this->randomlyChangeBlackToWhite($modifiedGrid);
            }
            
            // 2. 일부 흰칸을 검은칸으로 변경
            $whiteToBlackCount = rand(1, 2);
            for ($i = 0; $i < $whiteToBlackCount; $i++) {
                $modifiedGrid = $this->randomlyChangeWhiteToBlack($modifiedGrid);
            }
        }
        
        return $modifiedGrid;
    }

    /**
     * 랜덤하게 흰칸을 검은칸으로 변경
     */
    private function randomlyChangeWhiteToBlack($grid)
    {
        $rows = count($grid);
        $cols = count($grid[0]);
        
        // 흰칸 위치들 찾기
        $whitePositions = [];
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                if ($grid[$i][$j] == 1) {
                    $whitePositions[] = [$i, $j];
                }
            }
        }
        
        if (empty($whitePositions)) {
            return $grid;
        }
        
        // 랜덤하게 하나 선택하여 변경
        $randomPos = $whitePositions[array_rand($whitePositions)];
        $grid[$randomPos[0]][$randomPos[1]] = 2;
        
        return $grid;
    }

    /**
     * 그리드 회전 (90도 * times)
     */
    private function rotateGrid($grid, $times)
    {
        $result = $grid;
        for ($i = 0; $i < $times; $i++) {
            $result = $this->rotate90Degrees($result);
        }
        return $result;
    }

    /**
     * 90도 시계방향 회전
     */
    private function rotate90Degrees($grid)
    {
        $rows = count($grid);
        $cols = count($grid[0]);
        $rotated = [];

        for ($i = 0; $i < $cols; $i++) {
            $rotated[$i] = [];
            for ($j = 0; $j < $rows; $j++) {
                $rotated[$i][$j] = $grid[$rows - 1 - $j][$i];
            }
        }

        return $rotated;
    }

    /**
     * 그리드 좌우 반전
     */
    private function flipHorizontal($grid)
    {
        $result = [];
        foreach ($grid as $row) {
            $result[] = array_reverse($row);
        }
        return $result;
    }

    /**
     * 그리드에서 단어 위치 추출
     */
    private function extractWordPositions($grid)
    {
        $wordPositions = [];
        $wordId = 1;

        // 가로 단어 추출
        for ($y = 0; $y < count($grid); $y++) {
            $x = 0;
            while ($x < count($grid[0])) {
                if ($grid[$y][$x] == 1) { // 흰칸 시작
                    $startX = $x;
                    // 단어 끝 찾기
                    while ($x < count($grid[0]) && $grid[$y][$x] == 1) {
                        $x++;
                    }
                    $endX = $x - 1;

                    // 2글자 이상인 경우만 추가
                    if ($endX - $startX >= 1) {
                        $wordPositions[] = [
                            'id' => $wordId,
                            'start_x' => $startX,
                            'start_y' => $y,
                            'end_x' => $endX,
                            'end_y' => $y,
                            'direction' => 'horizontal',
                            'length' => $endX - $startX + 1
                        ];
                        $wordId++;
                    }
                } else {
                    $x++;
                }
            }
        }

        // 세로 단어 추출
        for ($x = 0; $x < count($grid[0]); $x++) {
            $y = 0;
            while ($y < count($grid)) {
                if ($grid[$y][$x] == 1) { // 흰칸 시작
                    $startY = $y;
                    // 단어 끝 찾기
                    while ($y < count($grid) && $grid[$y][$x] == 1) {
                        $y++;
                    }
                    $endY = $y - 1;

                    // 2글자 이상인 경우만 추가
                    if ($endY - $startY >= 1) {
                        $wordPositions[] = [
                            'id' => $wordId,
                            'start_x' => $x,
                            'start_y' => $startY,
                            'end_x' => $x,
                            'end_y' => $endY,
                            'direction' => 'vertical',
                            'length' => $endY - $startY + 1
                        ];
                        $wordId++;
                    }
                } else {
                    $y++;
                }
            }
        }

        return $wordPositions;
    }

    /**
     * 검은칸 비율 계산
     */
    private function calculateBlackRatio($grid)
    {
        $totalCells = count($grid) * count($grid[0]);
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

    /**
     * 레벨에 맞는 패턴 선택
     */
    public function selectPatternForLevel($level)
    {
        $levelNumber = $level->level;
        $wordCount = $level->word_count;
        $intersectionCount = $level->intersection_count;

        // 레벨별 패턴 매핑
        $patternMapping = [
            1 => ['cross_5x5', 'l_shape_5x5', 'mesh_5x5'],
            2 => ['cross_5x5', 'l_shape_5x5', 'mesh_5x5'],
            5 => ['symmetric_6x6'],
            11 => ['symmetric_6x6']
        ];

        if (isset($patternMapping[$levelNumber])) {
            return $patternMapping[$levelNumber];
        }

        // 기본 패턴 (5x5)
        return ['cross_5x5', 'l_shape_5x5', 'mesh_5x5'];
    }

    /**
     * 랜덤 패턴 생성
     */
    public function generateRandomPattern($level)
    {
        $availablePatterns = $this->selectPatternForLevel($level);
        $patternName = $availablePatterns[array_rand($availablePatterns)];
        $variation = rand(0, 3);

        return $this->generateByPattern($patternName, $variation);
    }

    /**
     * 사용 가능한 패턴 목록 반환
     */
    public function listAvailablePatterns()
    {
        $patterns = [];
        foreach ($this->patternTemplates as $name => $template) {
            $patterns[] = [
                'name' => $name,
                'display_name' => $template['name'],
                'description' => $template['description'],
                'word_structure' => $template['word_structure'],
                'grid_size' => count($template['grid_pattern']) . 'x' . count($template['grid_pattern'][0]),
                'black_cells' => $template['black_cells'],
                'white_cells' => $template['white_cells']
            ];
        }
        return $patterns;
    }

    /**
     * 그리드 검증
     */
    public function validateGrid($grid, $wordPositions)
    {
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'stats' => []
        ];

        // 기본 검증
        if (empty($grid) || empty($grid[0])) {
            $validation['is_valid'] = false;
            $validation['errors'][] = '빈 그리드';
            return $validation;
        }

        $width = count($grid[0]);
        $height = count($grid);

        // 단어 길이 검증
        foreach ($wordPositions as $word) {
            if ($word['length'] < 2) {
                $validation['errors'][] = "단어 {$word['id']}: 한 글자 단어는 허용되지 않음";
                $validation['is_valid'] = false;
            }
        }

        // 교차점 검증
        $intersections = $this->findIntersections($wordPositions);
        if (empty($intersections)) {
            $validation['warnings'][] = '교차점이 없음';
        }

        // 통계 정보
        $validation['stats'] = [
            'grid_size' => "{$width}x{$height}",
            'word_count' => count($wordPositions),
            'horizontal_words' => count(array_filter($wordPositions, fn($w) => $w['direction'] == 'horizontal')),
            'vertical_words' => count(array_filter($wordPositions, fn($w) => $w['direction'] == 'vertical')),
            'intersection_count' => count($intersections),
            'black_cell_ratio' => $this->calculateBlackRatio($grid)
        ];

        return $validation;
    }

    /**
     * 교차점 찾기
     */
    private function findIntersections($wordPositions)
    {
        $intersections = [];
        $horizontalWords = array_filter($wordPositions, fn($w) => $w['direction'] == 'horizontal');
        $verticalWords = array_filter($wordPositions, fn($w) => $w['direction'] == 'vertical');

        foreach ($horizontalWords as $hWord) {
            foreach ($verticalWords as $vWord) {
                // 교차점 확인
                if ($hWord['start_x'] <= $vWord['start_x'] && $vWord['start_x'] <= $hWord['end_x'] &&
                    $vWord['start_y'] <= $hWord['start_y'] && $hWord['start_y'] <= $vWord['end_y']) {
                    $intersections[] = [$hWord['id'], $vWord['id']];
                }
            }
        }

        return $intersections;
    }
} 