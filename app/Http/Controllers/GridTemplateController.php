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
        
        // 사용자가 설정한 번호 순서를 유지 (정렬하지 않음)
        \Log::info("템플릿 상세 조회", [
            'template_id' => $id,
            'word_positions' => $wordPositions
        ]);

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
     * 템플릿에서 단어 추출
     */
    public function extractWords(Request $request)
    {
        $templateId = $request->input('template_id');
        
        try {
            // 템플릿 정보 가져오기
            $template = DB::table('puzzle_grid_templates')
                ->join('puzzle_levels', 'puzzle_grid_templates.level_id', '=', 'puzzle_levels.id')
                ->select(
                    'puzzle_grid_templates.*',
                    'puzzle_levels.word_difficulty',
                    'puzzle_levels.hint_difficulty'
                )
                ->where('puzzle_grid_templates.id', $templateId)
                ->first();

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => '템플릿을 찾을 수 없습니다.'
                ]);
            }

            $wordPositions = json_decode($template->word_positions, true);
            $gridPattern = json_decode($template->grid_pattern, true);
            
            // 디버깅: 정렬 전 wordPositions 확인
            \Log::info("정렬 전 wordPositions", [
                'template_id' => $templateId,
                'word_positions' => $wordPositions
            ]);
            
            // 사용자가 설정한 번호 순서대로 정렬
            usort($wordPositions, function($a, $b) {
                return $a['id'] - $b['id'];
            });

            // 디버깅: 정렬 후 wordPositions 확인
            \Log::info("정렬 후 wordPositions", [
                'template_id' => $templateId,
                'word_positions' => $wordPositions
            ]);

            // 단어 추출 결과
            $extractedWords = [];
            $usedWords = [];

            // 1. 단어 순서대로 처리
            foreach ($wordPositions as $word) {
                $wordId = $word['id'];
                
                // 이미 처리된 단어는 건너뛰기
                if (in_array($wordId, $usedWords)) {
                    continue;
                }

                // 2. 단어의 상태 확인 (교차점이 있는지 여부)
                $intersections = $this->findWordIntersections($word, $wordPositions);
                
                // 디버깅 로그 추가
                \Log::info("단어 처리 중", [
                    'word_id' => $wordId,
                    'word_position' => $word,
                    'intersections_count' => count($intersections),
                    'intersections' => $intersections,
                    'used_words' => $usedWords
                ]);
                
                if (empty($intersections)) {
                    // 3. 교차점이 없다면 독립 단어 추출
                    $extractedWord = $this->extractIndependentWord($word, $template);
                    $extractedWords[] = [
                        'word_id' => $wordId,
                        'position' => $word,
                        'type' => 'no_intersection',
                        'extracted_word' => $extractedWord['word'],
                        'hint' => $extractedWord['hint']
                    ];
                    $usedWords[] = $wordId;
                } else {
                    // 4. 교차점이 있다면 연쇄 처리
                    // 이미 처리된 단어와의 교차점이 있는지 확인
                    $processedIntersections = [];
                    $unprocessedIntersections = [];
                    
                    foreach ($intersections as $intersection) {
                        if (in_array($intersection['connected_word_id'], $usedWords)) {
                            $processedIntersections[] = $intersection;
                        } else {
                            $unprocessedIntersections[] = $intersection;
                        }
                    }
                    
                    // 이미 처리된 단어와 교차점이 있다면, 해당 정보를 사용해서 연쇄 처리
                    if (!empty($processedIntersections)) {
                        $chainResult = $this->extractChainWordsWithProcessed($word, $processedIntersections, $unprocessedIntersections, $wordPositions, $template, $usedWords, $extractedWords);
                        
                        foreach ($chainResult as $chainWord) {
                            $extractedWords[] = $chainWord;
                            $usedWords[] = $chainWord['word_id'];
                        }
                    } else {
                        // 새로운 연쇄 처리
                        $chainResult = $this->extractChainWords($word, $intersections, $wordPositions, $template, $usedWords);
                        
                        foreach ($chainResult as $chainWord) {
                            $extractedWords[] = $chainWord;
                            $usedWords[] = $chainWord['word_id'];
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'template' => [
                    'id' => $template->id,
                    'template_name' => $template->template_name,
                    'level_id' => $template->level_id
                ],
                'extracted_words' => [
                    'grid_info' => [
                        'width' => $template->grid_width,
                        'height' => $template->grid_height,
                        'pattern' => $gridPattern
                    ],
                    'word_order' => $extractedWords
                ],
                'word_analysis' => [
                    'total_words' => count($wordPositions),
                    'extracted_words' => count($extractedWords)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '단어 추출 중 오류가 발생했습니다: ' . $e->getMessage()
            ]);
        }
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

    /**
     * 단어의 교차점 찾기
     */
    private function findWordIntersections($word, $wordPositions)
    {
        $intersections = [];
        
        foreach ($wordPositions as $otherWord) {
            if ($word['id'] === $otherWord['id']) continue;
            
            $intersection = $this->findIntersection($word, $otherWord);
            if ($intersection) {
                $intersections[] = [
                    'position' => $intersection,
                    'connected_word_id' => $otherWord['id'],
                    'connected_word' => $otherWord
                ];
            }
        }
        
        return $intersections;
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
     * 독립 단어 추출 (교차점이 없는 단어)
     */
    private function extractIndependentWord($word, $template)
    {
        $length = $word['length'];
        $wordDifficulty = $template->word_difficulty;
        $hintDifficulty = $this->convertDifficultyToEnglish($template->hint_difficulty);
        
        $result = DB::table('pz_words as a')
            ->join('pz_hints as b', 'a.id', '=', 'b.word_id')
            ->select('a.word', 'b.hint_text as hint')
            ->where('a.length', $length)
            ->where('a.is_active', true)
            ->where('a.difficulty', '<=', $wordDifficulty)
            ->whereRaw("b.difficulty = '{$hintDifficulty}'")
            ->orderByRaw('RANDOM()')
            ->first();
        
        if (!$result) {
            return [
                'word' => '추출 실패',
                'hint' => '조건에 맞는 단어를 찾을 수 없습니다.'
            ];
        }
        
        return [
            'word' => $result->word,
            'hint' => $result->hint
        ];
    }

    /**
     * 이미 처리된 단어와의 교차점을 고려한 연쇄 단어 추출
     */
    private function extractChainWordsWithProcessed($word, $processedIntersections, $unprocessedIntersections, $wordPositions, $template, &$usedWords, $extractedWords)
    {
        $chainWords = [];
        
        // 이미 처리된 단어 중 하나와의 교차점 정보 찾기
        $processedIntersection = $processedIntersections[0];
        $processedWordId = $processedIntersection['connected_word_id'];
        
        // 이미 추출된 단어에서 해당 단어의 정보 찾기
        $processedWordInfo = null;
        foreach ($extractedWords as $extractedWord) {
            if ($extractedWord['word_id'] == $processedWordId) {
                $processedWordInfo = $extractedWord;
                break;
            }
        }
        
        if (!$processedWordInfo) {
            return $this->fallbackToIndependentWord($word, $template);
        }
        
        // 현재 단어의 교차점 음절 위치
        $currentWordSyllablePos = $this->getSyllablePosition($word, $processedIntersection['position']);
        
        // 이미 처리된 단어의 교차점 음절 위치
        $processedWord = $this->findWordById($processedWordId, $wordPositions);
        $processedWordSyllablePos = $this->getSyllablePosition($processedWord, $processedIntersection['position']);
        
        // 이미 처리된 단어의 해당 위치 음절 추출
        $processedWordSyllable = mb_substr($processedWordInfo['extracted_word'], $processedWordSyllablePos - 1, 1, 'UTF-8');
        
        // 현재 단어 추출 (이미 처리된 단어의 음절과 매칭)
        $currentWordCandidates = $this->extractWordCandidatesWithSyllables($word, $template, [$processedWordSyllable], $currentWordSyllablePos, 100);
        
        if (empty($currentWordCandidates)) {
            return $this->fallbackToIndependentWord($word, $template);
        }
        
        // 매칭되는 단어 찾기
        $matchedWord = null;
        foreach ($currentWordCandidates as $candidate) {
            $currentSyllable = mb_substr($candidate->word, $currentWordSyllablePos - 1, 1, 'UTF-8');
            if ($currentSyllable === $processedWordSyllable) {
                $matchedWord = $candidate;
                break;
            }
        }
        
        if (!$matchedWord) {
            return $this->fallbackToIndependentWord($word, $template);
        }
        
        // 현재 단어 추가
        $chainWords[] = [
            'word_id' => $word['id'],
            'position' => $word,
            'type' => 'chain_connected',
            'extracted_word' => $matchedWord->word,
            'hint' => $matchedWord->hint
        ];
        
        // 추가로 처리되지 않은 교차점이 있다면 처리
        if (!empty($unprocessedIntersections)) {
            foreach ($unprocessedIntersections as $intersection) {
                $connectedWord = $this->findWordById($intersection['connected_word_id'], $wordPositions);
                
                if (!$connectedWord || in_array($connectedWord['id'], $usedWords)) {
                    continue;
                }
                
                // 현재 단어와 연결된 단어의 교차점 찾기
                $newIntersection = $this->findIntersection($word, $connectedWord);
                if (!$newIntersection) {
                    continue;
                }
                
                // 연결된 단어의 교차점 음절 위치
                $connectedWordSyllablePos = $this->getSyllablePosition($connectedWord, $newIntersection);
                
                // 현재 단어의 교차점 음절 추출
                $currentWordSyllablePos2 = $this->getSyllablePosition($word, $newIntersection);
                $currentWordSyllable2 = mb_substr($matchedWord->word, $currentWordSyllablePos2 - 1, 1, 'UTF-8');
                
                // 연결된 단어 추출
                $connectedWordCandidates = $this->extractWordCandidatesWithSyllables($connectedWord, $template, [$currentWordSyllable2], $connectedWordSyllablePos, 100);
                
                if (empty($connectedWordCandidates)) {
                    continue;
                }
                
                // 매칭되는 연결 단어 찾기
                $matchedConnectedWord = null;
                foreach ($connectedWordCandidates as $candidate) {
                    $connectedSyllable = mb_substr($candidate->word, $connectedWordSyllablePos - 1, 1, 'UTF-8');
                    if ($connectedSyllable === $currentWordSyllable2) {
                        $matchedConnectedWord = $candidate;
                        break;
                    }
                }
                
                if ($matchedConnectedWord) {
                    $chainWords[] = [
                        'word_id' => $connectedWord['id'],
                        'position' => $connectedWord,
                        'type' => 'chain_middle',
                        'extracted_word' => $matchedConnectedWord->word,
                        'hint' => $matchedConnectedWord->hint
                    ];
                    break;
                }
            }
        }
        
        return $chainWords;
    }

    /**
     * 연쇄 단어 추출 (교차점이 있는 단어들)
     */
    private function extractChainWords($firstWord, $intersections, $wordPositions, $template, &$usedWords)
    {
        $chainWords = [];
        
        // 4-1. 첫 번째 단어 추출 (100개)
        $firstWordCandidates = $this->extractWordCandidates($firstWord, $template, 100);
        
        if (empty($firstWordCandidates)) {
            return $this->fallbackToIndependentWord($firstWord, $template);
        }
        
        // 첫 번째 교차점 처리
        $firstIntersection = $intersections[0];
        $secondWord = $this->findWordById($firstIntersection['connected_word_id'], $wordPositions);
        
        if (!$secondWord || in_array($secondWord['id'], $usedWords)) {
            return $this->fallbackToIndependentWord($firstWord, $template);
        }
        
        // 4-2. 첫 번째 단어의 교차점 음절 추출
        $firstWordSyllablePos = $this->getSyllablePosition($firstWord, $firstIntersection['position']);
        $firstWordSyllables = $this->extractSyllablesFromCandidates($firstWordCandidates, $firstWordSyllablePos);
        
        // 4-3. 두 번째 단어의 교차점 음절 위치
        $secondWordSyllablePos = $this->getSyllablePosition($secondWord, $firstIntersection['position']);
        
        // 4-4. 두 번째 단어 추출
        $secondWordCandidates = $this->extractWordCandidatesWithSyllables($secondWord, $template, $firstWordSyllables, $secondWordSyllablePos, 100);
        
        if (empty($secondWordCandidates)) {
            return $this->fallbackToIndependentWord($firstWord, $template);
        }
        
        // 4-5. 첫 번째와 두 번째 단어 매칭
        $matchedPair = $this->findMatchingWordPair($firstWordCandidates, $secondWordCandidates, $firstWordSyllablePos, $secondWordSyllablePos);
        
        if (!$matchedPair) {
            return $this->fallbackToIndependentWord($firstWord, $template);
        }
        
        // 첫 번째와 두 번째 단어 추가
        $chainWords[] = [
            'word_id' => $firstWord['id'],
            'position' => $firstWord,
            'type' => 'intersection_start',
            'extracted_word' => $matchedPair['first_word'],
            'hint' => $matchedPair['first_hint']
        ];
        
        $chainWords[] = [
            'word_id' => $secondWord['id'],
            'position' => $secondWord,
            'type' => 'chain_middle',
            'extracted_word' => $matchedPair['second_word'],
            'hint' => $matchedPair['second_hint']
        ];
        
        // 4-6~4-9. 세 번째 단어가 있는지 확인
        $remainingIntersections = array_slice($intersections, 1);
        foreach ($remainingIntersections as $intersection) {
            $thirdWord = $this->findWordById($intersection['connected_word_id'], $wordPositions);
            
            if (!$thirdWord || in_array($thirdWord['id'], $usedWords)) {
                continue;
            }
            
            // 두 번째 단어와 세 번째 단어의 교차점 찾기
            $secondIntersection = $this->findIntersection($secondWord, $thirdWord);
            if (!$secondIntersection) {
                continue;
            }
            
            // 4-6. 두 번째 단어의 교차점 음절 추출
            $secondWordSyllablePos2 = $this->getSyllablePosition($secondWord, $secondIntersection);
            // 두 번째 단어의 매칭된 단어에서 해당 위치의 음절 추출
            $secondWordSyllable = mb_substr($matchedPair['second_word'], $secondWordSyllablePos2 - 1, 1, 'UTF-8');
            $secondWordSyllables = [$secondWordSyllable];
            
            // 4-7. 세 번째 단어의 교차점 음절 위치
            $thirdWordSyllablePos = $this->getSyllablePosition($thirdWord, $secondIntersection);
            
            // 4-8. 세 번째 단어 추출
            $thirdWordCandidates = $this->extractWordCandidatesWithSyllables($thirdWord, $template, $secondWordSyllables, $thirdWordSyllablePos, 100);
            
            if (empty($thirdWordCandidates)) {
                continue;
            }
            
            // 4-9. 세 단어 매칭
            $matchedTriple = $this->findMatchingWordTriple(
                $matchedPair['first_word'], 
                $matchedPair['second_word'], 
                $thirdWordCandidates,
                $firstWordSyllablePos, 
                $secondWordSyllablePos, 
                $thirdWordSyllablePos
            );
            
            if ($matchedTriple) {
                $chainWords[] = [
                    'word_id' => $thirdWord['id'],
                    'position' => $thirdWord,
                    'type' => 'chain_middle',
                    'extracted_word' => $matchedTriple['third_word'],
                    'hint' => $matchedTriple['third_hint']
                ];
                break;
            }
        }
        
        return $chainWords;
    }

    /**
     * 한국어 난이도를 영어로 변환
     */
    private function convertDifficultyToEnglish($difficulty)
    {
        $difficultyMap = [
            '쉬움' => 'easy',
            '보통' => 'medium',
            '어려움' => 'hard'
        ];
        
        return $difficultyMap[$difficulty] ?? 'easy';
    }

    /**
     * 단어 후보 추출
     */
    private function extractWordCandidates($word, $template, $limit = 100)
    {
        $length = $word['length'];
        $wordDifficulty = $template->word_difficulty;
        $hintDifficulty = $this->convertDifficultyToEnglish($template->hint_difficulty);
        
        return DB::table('pz_words as a')
            ->join('pz_hints as b', 'a.id', '=', 'b.word_id')
            ->select('a.word', 'b.hint_text as hint')
            ->where('a.length', $length)
            ->where('a.is_active', true)
            ->where('a.difficulty', '<=', $wordDifficulty)
            ->whereRaw("b.difficulty = '{$hintDifficulty}'")
            ->orderByRaw('RANDOM()')
            ->limit($limit)
            ->get();
    }

    /**
     * 특정 음절 위치의 음절들을 추출
     */
    private function extractSyllablesFromCandidates($candidates, $syllablePos)
    {
        $syllables = [];
        foreach ($candidates as $candidate) {
            $word = $candidate->word;
            if (mb_strlen($word, 'UTF-8') >= $syllablePos) {
                $syllable = mb_substr($word, $syllablePos - 1, 1, 'UTF-8');
                if (!in_array($syllable, $syllables)) {
                    $syllables[] = $syllable;
                }
            }
        }
        return $syllables;
    }

    /**
     * 특정 음절 조건으로 단어 후보 추출
     */
    private function extractWordCandidatesWithSyllables($word, $template, $syllables, $syllablePos, $limit = 100)
    {
        if (empty($syllables)) {
            return collect();
        }
        
        $length = $word['length'];
        $wordDifficulty = $template->word_difficulty;
        $hintDifficulty = $this->convertDifficultyToEnglish($template->hint_difficulty);
        
        $syllableConditions = [];
        foreach ($syllables as $syllable) {
            $syllableConditions[] = "substring(word, {$syllablePos}, 1) = '" . addslashes($syllable) . "'";
        }
        
        $syllableWhere = '(' . implode(' OR ', $syllableConditions) . ')';
        
        return DB::table('pz_words as a')
            ->join('pz_hints as b', 'a.id', '=', 'b.word_id')
            ->select('a.word', 'b.hint_text as hint')
            ->whereRaw($syllableWhere)
            ->where('a.length', $length)
            ->where('a.is_active', true)
            ->where('a.difficulty', '<=', $wordDifficulty)
            ->whereRaw("b.difficulty = '{$hintDifficulty}'")
            ->orderByRaw('RANDOM()')
            ->limit($limit)
            ->get();
    }

    /**
     * 두 단어 매칭 찾기
     */
    private function findMatchingWordPair($firstCandidates, $secondCandidates, $firstPos, $secondPos)
    {
        foreach ($firstCandidates as $firstCandidate) {
            $firstSyllable = mb_substr($firstCandidate->word, $firstPos - 1, 1, 'UTF-8');
            
            foreach ($secondCandidates as $secondCandidate) {
                $secondSyllable = mb_substr($secondCandidate->word, $secondPos - 1, 1, 'UTF-8');
                
                if ($firstSyllable === $secondSyllable) {
                    return [
                        'first_word' => $firstCandidate->word,
                        'first_hint' => $firstCandidate->hint,
                        'second_word' => $secondCandidate->word,
                        'second_hint' => $secondCandidate->hint
                    ];
                }
            }
        }
        
        return null;
    }

    /**
     * 세 단어 매칭 찾기
     */
    private function findMatchingWordTriple($firstWord, $secondWord, $thirdCandidates, $firstPos, $secondPos, $thirdPos)
    {
        $firstSyllable = mb_substr($firstWord, $firstPos - 1, 1, 'UTF-8');
        $secondSyllable = mb_substr($secondWord, $secondPos - 1, 1, 'UTF-8');
        
        foreach ($thirdCandidates as $thirdCandidate) {
            $thirdSyllable = mb_substr($thirdCandidate->word, $thirdPos - 1, 1, 'UTF-8');
            
            if ($secondSyllable === $thirdSyllable) {
                return [
                    'third_word' => $thirdCandidate->word,
                    'third_hint' => $thirdCandidate->hint
                ];
            }
        }
        
        return null;
    }

    /**
     * 음절 위치 계산
     */
    private function getSyllablePosition($word, $intersection)
    {
        if ($word['direction'] === 'horizontal') {
            return $intersection['x'] - $word['start_x'] + 1;
        } else {
            return $intersection['y'] - $word['start_y'] + 1;
        }
    }

    /**
     * ID로 단어 찾기
     */
    private function findWordById($id, $wordPositions)
    {
        foreach ($wordPositions as $word) {
            if ($word['id'] == $id) {
                return $word;
            }
        }
        return null;
    }

    /**
     * 독립 단어로 폴백
     */
    private function fallbackToIndependentWord($word, $template)
    {
        $extractedWord = $this->extractIndependentWord($word, $template);
        return [[
            'word_id' => $word['id'],
            'position' => $word,
            'type' => 'no_intersection',
            'extracted_word' => $extractedWord['word'],
            'hint' => $extractedWord['hint']
        ]];
    }
} 