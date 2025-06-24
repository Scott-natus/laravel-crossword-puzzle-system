<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PuzzleLevel;
use App\Services\PuzzleGridTemplateService;

class GridTemplateController extends Controller
{
    protected $templateService;

    public function __construct(PuzzleGridTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * 그리드 템플릿 생성 페이지
     */
    public function create()
    {
        // 모든 레벨 정보 가져오기
        $levels = DB::table('puzzle_levels')
            ->orderBy('level')
            ->get();

        return view('puzzle.grid-templates.create', compact('levels'));
    }

    /**
     * 레벨 선택 시 조건 정보 반환 (AJAX)
     */
    public function getLevelConditions(Request $request)
    {
        $levelId = $request->input('level_id');
        
        $level = DB::table('puzzle_levels')
            ->where('id', $levelId)
            ->first();

        if (!$level) {
            return response()->json([
                'success' => false,
                'message' => '레벨을 찾을 수 없습니다.'
            ]);
        }

        // 해당 레벨의 기존 템플릿들 가져오기
        $existingTemplates = DB::table('puzzle_grid_templates')
            ->where('level_id', $levelId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'level' => $level,
            'existing_templates' => $existingTemplates
        ]);
    }

    /**
     * 그리드 템플릿 저장
     */
    public function store(Request $request)
    {
        $request->validate([
            'level_id' => 'required|integer|exists:puzzle_levels,id',
            'grid_size' => 'required|integer|min:3|max:20',
            'grid_pattern' => 'required|array',
            'word_positions' => 'required|array',
            'word_count' => 'required|integer|min:1',
            'intersection_count' => 'required|integer|min:0'
        ]);

        try {
            // 레벨 정보 가져오기
            $level = DB::table('puzzle_levels')
                ->where('id', $request->level_id)
                ->first();

            if (!$level) {
                return response()->json([
                    'success' => false,
                    'message' => '레벨을 찾을 수 없습니다.'
                ]);
            }

            // 조건 검증
            if ($request->word_count != $level->word_count) {
                return response()->json([
                    'success' => false,
                    'message' => "단어 개수가 일치하지 않습니다. 레벨 {$level->level}은 {$level->word_count}개 단어가 필요합니다."
                ]);
            }

            if ($request->intersection_count != $level->intersection_count) {
                return response()->json([
                    'success' => false,
                    'message' => "교차점 개수가 일치하지 않습니다. 레벨 {$level->level}은 {$level->intersection_count}개 교차점이 필요합니다."
                ]);
            }

            // 템플릿 이름 자동 생성
            $templateCount = DB::table('puzzle_grid_templates')
                ->where('level_id', $request->level_id)
                ->count();
            
            $templateName = "레벨 {$level->level} 템플릿 #" . ($templateCount + 1);

            // 템플릿 데이터 준비
            $template = [
                'level_id' => $request->level_id,
                'template_name' => $templateName,
                'grid_pattern' => $request->grid_pattern,
                'word_positions' => $request->word_positions,
                'grid_width' => $request->grid_size,
                'grid_height' => $request->grid_size,
                'difficulty_rating' => $level->word_difficulty,
                'word_count' => $request->word_count,
                'intersection_count' => $request->intersection_count,
                'category' => 'custom',
                'description' => null,
                'is_active' => true
            ];

            // 템플릿 저장
            $templateId = $this->templateService->saveTemplate($template);

            return response()->json([
                'success' => true,
                'message' => '그리드 템플릿이 성공적으로 저장되었습니다.',
                'template_id' => $templateId
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '템플릿 저장 중 오류가 발생했습니다: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 그리드 템플릿 목록
     */
    public function index()
    {
        $templates = DB::table('puzzle_grid_templates')
            ->join('puzzle_levels', 'puzzle_grid_templates.level_id', '=', 'puzzle_levels.id')
            ->select(
                'puzzle_grid_templates.*',
                'puzzle_levels.level',
                'puzzle_levels.level_name'
            )
            ->where('puzzle_grid_templates.is_active', true)
            ->orderBy('puzzle_levels.level')
            ->orderBy('puzzle_grid_templates.created_at', 'desc')
            ->get()
            ->map(function ($template) {
                // created_at을 Carbon 객체로 변환
                $template->created_at = \Carbon\Carbon::parse($template->created_at);
                $template->updated_at = \Carbon\Carbon::parse($template->updated_at);
                return $template;
            });

        return view('puzzle.grid-templates.index', compact('templates'));
    }

    /**
     * 그리드 템플릿 상세 보기
     */
    public function show($id)
    {
        $template = DB::table('puzzle_grid_templates')
            ->join('puzzle_levels', 'puzzle_grid_templates.level_id', '=', 'puzzle_levels.id')
            ->select(
                'puzzle_grid_templates.*',
                'puzzle_levels.level',
                'puzzle_levels.level_name'
            )
            ->where('puzzle_grid_templates.id', $id)
            ->where('puzzle_grid_templates.is_active', true)
            ->first();

        if (!$template) {
            return redirect()->route('puzzle.grid-templates.index')
                ->with('error', '템플릿을 찾을 수 없습니다.');
        }

        // created_at을 Carbon 객체로 변환
        $template->created_at = \Carbon\Carbon::parse($template->created_at);
        $template->updated_at = \Carbon\Carbon::parse($template->updated_at);

        // JSON 데이터 파싱
        $gridPattern = json_decode($template->grid_pattern, true);
        $wordPositions = json_decode($template->word_positions, true);

        return view('puzzle.grid-templates.show', compact('template', 'gridPattern', 'wordPositions'));
    }

    /**
     * 그리드 템플릿 삭제
     */
    public function destroy($id)
    {
        try {
            $template = DB::table('puzzle_grid_templates')
                ->where('id', $id)
                ->where('is_active', true)
                ->first();

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => '템플릿을 찾을 수 없습니다.'
                ]);
            }

            // 논리적 삭제 (is_active = false)
            DB::table('puzzle_grid_templates')
                ->where('id', $id)
                ->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => '템플릿이 성공적으로 삭제되었습니다.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '템플릿 삭제 중 오류가 발생했습니다: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 그리드 템플릿에서 단어 추출
     */
    public function extractWords(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:puzzle_grid_templates,id'
        ]);

        try {
            $template = DB::table('puzzle_grid_templates')
                ->where('id', $request->template_id)
                ->where('is_active', true)
                ->first();

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => '템플릿을 찾을 수 없습니다.',
                    'debug_info' => []
                ]);
            }

            $gridPattern = json_decode($template->grid_pattern, true);
            $wordPositions = json_decode($template->word_positions, true);
            
            // 단어 배치 순서 결정 로직
            $wordOrder = $this->determineWordOrder($wordPositions, $gridPattern);
            
            return response()->json([
                'success' => true,
                'message' => '단어 배치 순서가 결정되었습니다.',
                'template' => $template,
                'word_analysis' => [
                    'total_words' => count($wordPositions),
                    'word_positions' => $wordPositions
                ],
                'extracted_words' => [
                    'status' => 'order_determined',
                    'message' => '단어 배치 순서가 결정되었습니다.',
                    'word_order' => $wordOrder,
                    'grid_info' => [
                        'width' => $template->grid_width,
                        'height' => $template->grid_height,
                        'pattern' => $gridPattern
                    ]
                ],
                'debug_info' => [
                    'algorithm_version' => 'word_order_only',
                    'template_id' => $template->id,
                    'level_id' => $template->level_id
                ]
            ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '단어 배치 순서 결정 중 오류가 발생했습니다: ' . $e->getMessage(),
                'debug_info' => [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            ], 500, [], JSON_INVALID_UTF8_SUBSTITUTE);
        }
    }
    
    /**
     * 단어 배치 순서 결정 로직 (어제 작업 복원 - 완전한 버전)
     * 좌측 상단부터 순차적으로 넘버링 (가로열 → 다음행 가로열 순서)
     */
    private function determineWordOrder($wordPositions, $gridPattern)
    {
        $wordOrder = [];
        $usedWords = [];
        $gridSize = count($gridPattern);
        $visitedPositions = [];
        
        // 1. 좌측 상단부터 순차적으로 검색하여 단어 순서 결정
        for ($y = 0; $y < $gridSize; $y++) {
            for ($x = 0; $x < $gridSize; $x++) {
                $positionKey = $x . ',' . $y;
                if (in_array($positionKey, $visitedPositions)) continue;
                
                // 현재 위치가 검은색칸(단어가 있는 칸)인지 확인
                if ($gridPattern[$y][$x] === 1) {
                    // 이 위치에서 시작하는 단어들 찾기
                    $wordsAtPosition = $this->findWordsAtPosition($x, $y, $wordPositions, $usedWords);
                    
                    if (!empty($wordsAtPosition)) {
                        // 첫 번째 칸이 교차점이면 가로가 1번, 세로가 2번
                        if (count($wordsAtPosition) > 1) {
                            // 가로 단어를 먼저 처리
                            foreach ($wordsAtPosition as $word) {
                                if ($word['direction'] === 'horizontal') {
                                    $wordOrder[] = [
                                        'word_id' => $word['id'],
                                        'position' => $word,
                                        'type' => count($wordOrder) === 0 ? 'first_word' : 'intersection_horizontal',
                                        'order' => count($wordOrder) + 1,
                                        'start_x' => $x,
                                        'start_y' => $y
                                    ];
                                    $usedWords[] = $word['id'];
                                    $this->markWordPositionsAsVisited($word, $visitedPositions);
                                    break;
                                }
                            }
                            
                            // 세로 단어를 두 번째로 처리
                            foreach ($wordsAtPosition as $word) {
                                if ($word['direction'] === 'vertical' && !in_array($word['id'], $usedWords)) {
                                    $wordOrder[] = [
                                        'word_id' => $word['id'],
                                        'position' => $word,
                                        'type' => 'intersection_vertical',
                                        'order' => count($wordOrder) + 1,
                                        'start_x' => $x,
                                        'start_y' => $y
                                    ];
                                    $usedWords[] = $word['id'];
                                    $this->markWordPositionsAsVisited($word, $visitedPositions);
                                    break;
                                }
                            }
                        } else {
                            // 단일 단어인 경우
                            $word = $wordsAtPosition[0];
                            $wordOrder[] = [
                                'word_id' => $word['id'],
                                'position' => $word,
                                'type' => count($wordOrder) === 0 ? 'first_word' : 'sequential_word',
                                'order' => count($wordOrder) + 1,
                                'start_x' => $x,
                                'start_y' => $y
                            ];
                            $usedWords[] = $word['id'];
                            $this->markWordPositionsAsVisited($word, $visitedPositions);
                        }
                        
                        // 교차점으로 연결된 단어들 처리
                        $this->processConnectedWords($wordOrder, $usedWords, $wordPositions, $visitedPositions);
                        
                        // 1번째 단어가 교차점으로 연결된 점이 없다면, 
                        // 1번째 단어가 차지한 검은색칸을 제외하고 흰색칸을 따라 다시 검색
                        if (count($wordOrder) === 1) {
                            $this->searchNextWordAfterFirst($wordOrder, $usedWords, $wordPositions, $gridPattern, $visitedPositions);
                        }
                    }
                }
            }
        }
        
        // 2. 남은 단어들 처리 (교차점이 없는 단어들)
        foreach ($wordPositions as $word) {
            if (!in_array($word['id'], $usedWords)) {
                $wordOrder[] = [
                    'word_id' => $word['id'],
                    'position' => $word,
                    'type' => 'remaining_word',
                    'order' => count($wordOrder) + 1
                ];
                $usedWords[] = $word['id'];
            }
        }
        
        return $wordOrder;
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
     * 첫 번째 단어 이후 다음 단어 검색 (흰색칸을 따라)
     */
    private function searchNextWordAfterFirst(&$wordOrder, &$usedWords, $wordPositions, $gridPattern, &$visitedPositions)
    {
        $gridSize = count($gridPattern);
        
        // 첫 번째 단어가 차지한 검은색칸을 제외하고 흰색칸을 따라 다시 검색
        for ($y = 0; $y < $gridSize; $y++) {
            for ($x = 0; $x < $gridSize; $x++) {
                $positionKey = $x . ',' . $y;
                if (in_array($positionKey, $visitedPositions)) continue;
                
                // 현재 위치가 검은색칸(단어가 있는 칸)인지 확인
                if ($gridPattern[$y][$x] === 1) {
                    // 이 위치에서 시작하는 단어들 찾기
                    $wordsAtPosition = $this->findWordsAtPosition($x, $y, $wordPositions, $usedWords);
                    
                    if (!empty($wordsAtPosition)) {
                        // 다음 단어 처리
                        $word = $wordsAtPosition[0];
                        $wordOrder[] = [
                            'word_id' => $word['id'],
                            'position' => $word,
                            'type' => 'next_sequential_word',
                            'order' => count($wordOrder) + 1,
                            'start_x' => $x,
                            'start_y' => $y
                        ];
                        $usedWords[] = $word['id'];
                        $this->markWordPositionsAsVisited($word, $visitedPositions);
                        return; // 첫 번째 다음 단어를 찾았으면 종료
                    }
                }
            }
        }
    }
    
    /**
     * 특정 위치에서 시작하는 단어들 찾기 (가로, 세로 모두)
     */
    private function findWordsAtPosition($x, $y, $wordPositions, $usedWords)
    {
        $words = [];
        
        foreach ($wordPositions as $word) {
            if (in_array($word['id'], $usedWords)) continue;
            
            // 가로 단어 확인
            if ($word['direction'] === 'horizontal' && 
                $word['start_x'] === $x && $word['start_y'] === $y) {
                $words[] = $word;
            }
            
            // 세로 단어 확인
            if ($word['direction'] === 'vertical' && 
                $word['start_x'] === $x && $word['start_y'] === $y) {
                $words[] = $word;
            }
        }
        
        return $words;
    }
    
    /**
     * 교차점으로 연결된 단어들 처리
     */
    private function processConnectedWords(&$wordOrder, &$usedWords, $wordPositions, &$visitedPositions)
    {
        $processed = false;
        
        do {
            $processed = false;
            
            foreach ($wordOrder as $orderedWord) {
                $word = $orderedWord['position'];
                
                // 이 단어와 교차점으로 연결된 단어들 찾기
                $connectedWords = $this->findConnectedWordsByIntersection($word, $wordPositions, $usedWords);
                
                foreach ($connectedWords as $connectedWord) {
                    $wordOrder[] = [
                        'word_id' => $connectedWord['id'],
                        'position' => $connectedWord,
                        'type' => 'intersection_connected',
                        'order' => count($wordOrder) + 1,
                        'connected_to' => $word['id']
                    ];
                    $usedWords[] = $connectedWord['id'];
                    $this->markWordPositionsAsVisited($connectedWord, $visitedPositions);
                    $processed = true;
                }
            }
        } while ($processed);
    }
    
    /**
     * 교차점으로 연결된 단어들 찾기
     */
    private function findConnectedWordsByIntersection($word, $wordPositions, $usedWords)
    {
        $connectedWords = [];
        
        foreach ($wordPositions as $otherWord) {
            if (in_array($otherWord['id'], $usedWords)) continue;
            if ($word['id'] === $otherWord['id']) continue;
            
            // 교차점 확인
            $intersection = $this->findIntersection($word, $otherWord);
            if ($intersection) {
                $connectedWords[] = $otherWord;
            }
        }
        
        return $connectedWords;
    }
    
    /**
     * 두 단어의 교차점 찾기
     */
    private function findIntersection($word1, $word2)
    {
        // 가로-세로 교차만 고려
        if ($word1['direction'] == $word2['direction']) {
            return null;
        }
        
        $horizontal = $word1['direction'] == 'horizontal' ? $word1 : $word2;
        $vertical = $word1['direction'] == 'vertical' ? $word1 : $word2;
        
        // 교차점 좌표 계산
        $intersectX = $vertical['start_x'];
        $intersectY = $horizontal['start_y'];
        
        // 교차점이 두 단어 범위 내에 있는지 확인
        if ($intersectX >= $horizontal['start_x'] && $intersectX <= $horizontal['end_x'] &&
            $intersectY >= $vertical['start_y'] && $intersectY <= $vertical['end_y']) {
            
            return [
                'x' => $intersectX,
                'y' => $intersectY,
                'horizontal_word_id' => $horizontal['id'],
                'vertical_word_id' => $vertical['id']
            ];
        }
        
        return null;
    }
} 