<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PuzzleGridTemplateService
{
    /**
     * 레벨1에 맞는 기본 그리드 템플릿 생성
     */
    public function createLevel1Template()
    {
        // 레벨1 설정: 5개 단어, 2개 교차점, 5x5 그리드
        $template = [
            'level_id' => 1,
            'template_name' => 'Level 1 - Basic 5x5 Grid',
            'grid_width' => 5,
            'grid_height' => 5,
            'difficulty_rating' => 1,
            'word_count' => 5,
            'intersection_count' => 2,
            'category' => 'beginner',
            'description' => '레벨1용 기본 5x5 그리드 템플릿. 5개 단어, 2개 교차점',
            'is_active' => true
        ];

        // 5x5 그리드 패턴 (0: 빈칸, 1: 글자칸, 2: 검은칸)
        $template['grid_pattern'] = [
            [1, 1, 1, 1, 1], // 첫 번째 단어 (가로)
            [1, 2, 1, 2, 1], // 두 번째 단어 (세로)와 교차
            [1, 2, 1, 2, 1], // 세 번째 단어 (가로)
            [1, 2, 1, 2, 1], // 네 번째 단어 (세로)
            [1, 1, 1, 1, 1]  // 다섯 번째 단어 (가로)
        ];

        // 단어 위치 정보
        $template['word_positions'] = [
            [
                'id' => 1,
                'word' => '', // 나중에 매칭될 단어
                'start_x' => 0,
                'start_y' => 0,
                'end_x' => 4,
                'end_y' => 0,
                'direction' => 'horizontal',
                'length' => 5,
                'clue_number' => 1
            ],
            [
                'id' => 2,
                'word' => '',
                'start_x' => 0,
                'start_y' => 0,
                'end_x' => 0,
                'end_y' => 4,
                'direction' => 'vertical',
                'length' => 5,
                'clue_number' => 2
            ],
            [
                'id' => 3,
                'word' => '',
                'start_x' => 0,
                'start_y' => 2,
                'end_x' => 4,
                'end_y' => 2,
                'direction' => 'horizontal',
                'length' => 5,
                'clue_number' => 3
            ],
            [
                'id' => 4,
                'word' => '',
                'start_x' => 2,
                'start_y' => 0,
                'end_x' => 2,
                'end_y' => 4,
                'direction' => 'vertical',
                'length' => 5,
                'clue_number' => 4
            ],
            [
                'id' => 5,
                'word' => '',
                'start_x' => 0,
                'start_y' => 4,
                'end_x' => 4,
                'end_y' => 4,
                'direction' => 'horizontal',
                'length' => 5,
                'clue_number' => 5
            ]
        ];

        return $template;
    }

    /**
     * 그리드 템플릿을 데이터베이스에 저장
     */
    public function saveTemplate($template)
    {
        try {
            // 새 규칙에 따라 단어 순서 정렬
            $sortedWordPositions = $this->sortWordPositionsWithPriority($template['word_positions'], $template['grid_width'], $template['grid_height']);
            $id = DB::table('puzzle_grid_templates')->insertGetId([
                'level_id' => $template['level_id'],
                'template_name' => $template['template_name'],
                'grid_pattern' => json_encode($template['grid_pattern']),
                'word_positions' => json_encode($sortedWordPositions),
                'grid_width' => $template['grid_width'],
                'grid_height' => $template['grid_height'],
                'difficulty_rating' => $template['difficulty_rating'],
                'word_count' => $template['word_count'],
                'intersection_count' => $template['intersection_count'],
                'category' => $template['category'],
                'description' => $template['description'],
                'is_active' => $template['is_active'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
            Log::info("그리드 템플릿 저장 완료", [
                'template_id' => $id,
                'template_name' => $template['template_name'],
                'level_id' => $template['level_id']
            ]);
            return $id;
        } catch (\Exception $e) {
            Log::error("그리드 템플릿 저장 실패", [
                'error' => $e->getMessage(),
                'template' => $template
            ]);
            throw $e;
        }
    }

    /**
     * 좌측 맨위부터 한 칸씩 스캔하며, 해당 칸에서 가로/세로 단어가 동시에 시작하면 가로를 먼저 넘버링, 그 다음 세로를 넘버링
     */
    private function sortWordPositionsWithPriority($wordPositions, $gridWidth, $gridHeight)
    {
        $sorted = [];
        $used = [];
        $wordNumber = 1;
        // (x, y) 기준으로 단어 시작점 찾기
        for ($y = 0; $y < $gridHeight; $y++) {
            for ($x = 0; $x < $gridWidth; $x++) {
                // 해당 칸에서 시작하는 가로/세로 단어 찾기
                $hWord = null;
                $vWord = null;
                foreach ($wordPositions as $idx => $word) {
                    if (in_array($idx, $used)) continue;
                    if ($word['start_x'] == $x && $word['start_y'] == $y) {
                        if ($word['direction'] == 'horizontal') $hWord = $idx;
                        if ($word['direction'] == 'vertical') $vWord = $idx;
                    }
                }
                // 가로/세로가 모두 있으면 가로 먼저, 그 다음 세로
                if (!is_null($hWord)) {
                    $word = $wordPositions[$hWord];
                    $word['id'] = $wordNumber++;
                    $sorted[] = $word;
                    $used[] = $hWord;
                }
                if (!is_null($vWord)) {
                    $word = $wordPositions[$vWord];
                    // 가로와 세로가 같은 칸에서 시작하면 id가 중복되지 않게 증가
                    $word['id'] = $wordNumber++;
                    $sorted[] = $word;
                    $used[] = $vWord;
                }
            }
        }
        return $sorted;
    }

    /**
     * 레벨에 맞는 그리드 템플릿 조회
     */
    public function getTemplateByLevel($levelId)
    {
        return DB::table('puzzle_grid_templates')
            ->where('level_id', $levelId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * 그리드 패턴을 시각적으로 출력
     */
    public function visualizeGrid($gridPattern)
    {
        $visual = "그리드 패턴:\n";
        foreach ($gridPattern as $row) {
            $visual .= implode(' ', array_map(function($cell) {
                return $cell == 1 ? '□' : ($cell == 2 ? '■' : '·');
            }, $row)) . "\n";
        }
        return $visual;
    }

    /**
     * 단어 위치 정보를 시각적으로 출력
     */
    public function visualizeWordPositions($wordPositions)
    {
        $visual = "단어 위치 정보:\n";
        foreach ($wordPositions as $pos) {
            $visual .= sprintf(
                "단어 %d: (%d,%d) → (%d,%d) [%s, 길이: %d]\n",
                $pos['id'],
                $pos['start_x'],
                $pos['start_y'],
                $pos['end_x'],
                $pos['end_y'],
                $pos['direction'],
                $pos['length']
            );
        }
        return $visual;
    }
} 