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
            'word_numbering' => 'array',
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

            // word_positions의 id 값을 사용자가 선택한 번호로 업데이트
            $wordPositions = $request->word_positions;
            $wordNumbering = $request->word_numbering;
            
            if ($wordNumbering) {
                // 번호 매핑 생성
                $numberMapping = [];
                foreach ($wordNumbering as $item) {
                    $numberMapping[$item['word_id']] = $item['order'];
                }
                
                // word_positions의 id 값을 선택된 번호로 변경
                foreach ($wordPositions as &$word) {
                    if (isset($numberMapping[$word['id']])) {
                        $word['id'] = $numberMapping[$word['id']];
                    }
                }
            }

            // 템플릿 데이터 준비
            $template = [
                'level_id' => $request->level_id,
                'template_name' => $templateName,
                'grid_pattern' => $request->grid_pattern,
                'word_positions' => $wordPositions,
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

    /**
     * 기존 템플릿들의 넘버링을 새로운 로직으로 업데이트
     */
    public function updateTemplateNumbering(Request $request)
    {
        try {
            $templateIds = $request->input('template_ids', [11, 12, 13, 14]);
            $results = [];
            
            foreach ($templateIds as $templateId) {
                $template = DB::table('puzzle_grid_templates')
                    ->where('id', $templateId)
                    ->where('is_active', true)
                    ->first();
                
                if (!$template) {
                    $results[] = [
                        'template_id' => $templateId,
                        'status' => 'not_found',
                        'message' => '템플릿을 찾을 수 없습니다.'
                    ];
                    continue;
                }
                
                $gridPattern = json_decode($template->grid_pattern, true);
                $wordPositions = json_decode($template->word_positions, true);
                
                if (!$gridPattern || !$wordPositions) {
                    $results[] = [
                        'template_id' => $templateId,
                        'status' => 'invalid_data',
                        'message' => '그리드 패턴 또는 단어 위치 데이터가 유효하지 않습니다.'
                    ];
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
                
                $results[] = [
                    'template_id' => $templateId,
                    'template_name' => $template->template_name,
                    'status' => 'updated',
                    'message' => '넘버링이 성공적으로 업데이트되었습니다.',
                    'old_word_count' => count($wordPositions),
                    'new_word_count' => count($newWordOrder),
                    'new_numbering' => $newWordOrder
                ];
                
                \Log::info("템플릿 넘버링 업데이트 완료", [
                    'template_id' => $templateId,
                    'template_name' => $template->template_name,
                    'word_count' => count($newWordOrder)
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => '템플릿 넘버링 업데이트가 완료되었습니다.',
                'results' => $results
            ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
            
        } catch (\Exception $e) {
            \Log::error("템플릿 넘버링 업데이트 오류", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '템플릿 넘버링 업데이트 중 오류가 발생했습니다: ' . $e->getMessage(),
                'debug_info' => [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            ], 500, [], JSON_INVALID_UTF8_SUBSTITUTE);
        }
    }

    /**
     * 그리드 템플릿 수정 (주로 번호 정보 수정)
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'level_id' => 'required|integer|exists:puzzle_levels,id',
            'grid_size' => 'required|integer|min:3|max:20',
            'grid_pattern' => 'required|array',
            'word_positions' => 'required|array',
            'word_numbering' => 'array',
            'word_count' => 'required|integer|min:1',
            'intersection_count' => 'required|integer|min:0'
        ]);

        try {
            // 기존 템플릿 확인
            $existingTemplate = DB::table('puzzle_grid_templates')
                ->where('id', $id)
                ->where('is_active', true)
                ->first();

            if (!$existingTemplate) {
                return response()->json([
                    'success' => false,
                    'message' => '수정할 템플릿을 찾을 수 없습니다.'
                ]);
            }

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

            // word_positions의 id 값을 사용자가 선택한 번호로 업데이트
            $wordPositions = $request->word_positions;
            $wordNumbering = $request->word_numbering;
            
            if ($wordNumbering) {
                // 번호 매핑 생성
                $numberMapping = [];
                foreach ($wordNumbering as $item) {
                    $numberMapping[$item['word_id']] = $item['order'];
                }
                
                // word_positions의 id 값을 선택된 번호로 변경
                foreach ($wordPositions as &$word) {
                    if (isset($numberMapping[$word['id']])) {
                        $word['id'] = $numberMapping[$word['id']];
                    }
                }
            }

            // 템플릿 업데이트
            $updateData = [
                'word_positions' => json_encode($wordPositions),
                'updated_at' => now()
            ];

            DB::table('puzzle_grid_templates')
                ->where('id', $id)
                ->update($updateData);

            return response()->json([
                'success' => true,
                'message' => '템플릿 번호 정보가 성공적으로 수정되었습니다.',
                'template_id' => $id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '템플릿 수정 중 오류가 발생했습니다: ' . $e->getMessage()
            ]);
        }
    }
} 