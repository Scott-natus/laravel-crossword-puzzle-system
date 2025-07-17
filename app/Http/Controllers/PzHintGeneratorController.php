<?php

namespace App\Http\Controllers;

use App\Models\PzWord;
use App\Models\PzHint;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PzHintGeneratorController extends Controller
{
    private $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->middleware('auth')->except(['generateForWords', 'getWords', 'getCrosswordWords']);
        $this->geminiService = $geminiService;
    }

    /**
     * 힌트 생성 페이지 표시
     */
    public function index(Request $request)
    {
        $query = PzWord::active()->with('hints');

        // 힌트 보유 상태 필터링
        $status = $request->input('status');
        if ($status === 'with_hints') {
            $query->whereHas('hints');
        } elseif ($status === 'without_hints') {
            $query->doesntHave('hints');
        }

        // 검색 타입과 검색어 처리
        $searchType = $request->input('search_type', 'keyword');
        $searchCategory = $request->input('search_category', '');
        $searchWord = $request->input('search_word', '');

        // 검색 조건에 따른 쿼리 구성
        if ($searchWord) {
            switch ($searchType) {
                case 'keyword':
                    // 키워드 검색: 카테고리 + 단어에서 검색
                    $query->where(function($q) use ($searchWord) {
                        $q->where('category', 'like', "%{$searchWord}%")
                          ->orWhere('word', 'like', "%{$searchWord}%");
                    });
                    break;
                    
                case 'category':
                    // 카테고리 검색
                    if ($searchCategory && $searchCategory !== '전체 카테고리') {
                        $query->where('category', $searchCategory);
                    }
                    if ($searchWord) {
                        if ($searchCategory === '전체 카테고리' || !$searchCategory) {
                            // B가 기본값인 경우: 카테고리 필드에서만 검색
                            $query->where('category', 'like', "%{$searchWord}%");
                        } else {
                            // B에 값이 선택된 경우: 선택된 카테고리에서 키워드 검색
                            $query->where(function($q) use ($searchWord) {
                                $q->where('category', 'like', "%{$searchWord}%")
                                  ->orWhere('word', 'like', "%{$searchWord}%");
                            });
                        }
                    }
                    break;
                    
                case 'word':
                    // 단어 검색
                    if ($searchCategory && $searchCategory !== '전체 카테고리') {
                        $query->where('category', $searchCategory);
                    }
                    if ($searchWord) {
                        $query->where('word', 'like', "%{$searchWord}%");
                    }
                    break;
            }
        } else {
            // 검색어가 없는 경우에도 카테고리 필터 적용
            if ($searchType === 'word' && $searchCategory && $searchCategory !== '전체 카테고리') {
                $query->where('category', $searchCategory);
            }
        }

        $categories = PzWord::distinct()->pluck('category')->sort()->values();
        
        return view('puzzle.hint-generator.index', compact(
            'categories', 
            'status', 
            'searchType', 
            'searchCategory', 
            'searchWord'
        ));
    }

    /**
     * 단일 단어에 대한 힌트 생성
     */
    public function generateForWord(Request $request, $wordId)
    {
        $word = PzWord::active()->findOrFail($wordId);
        
        // 비활성화된 단어는 힌트 생성 불가
        if (!$word->is_active) {
            return response()->json([
                'success' => false,
                'message' => '비활성화된 단어에는 힌트를 생성할 수 없습니다.'
            ], 403);
        }
        
        try {
            $result = $this->geminiService->generateHint(
                $word->word,
                $word->category
            );

            if ($result['success']) {
                $createdHints = [];
                $successCount = 0;
                $errorCount = 0;
                
                // 기존 힌트 삭제 (새로 생성할 것이므로)
                $word->hints()->delete();
                
                // 세 가지 난이도의 힌트를 모두 저장
                foreach ($result['hints'] as $difficulty => $hintData) {
                    if ($hintData['success']) {
                        // 난이도 매핑 (1,2,3 -> 1,2,3)
                        $difficultyMap = [
                            1 => 1,
                            2 => 2, 
                            3 => 3
                        ];
                        
                        $hint = PzHint::create([
                            'word_id' => $word->id,
                            'hint_text' => $hintData['hint'],
                            'hint_type' => 'text',
                            'difficulty' => $difficultyMap[$difficulty] ?? 2,
                            'is_primary' => ($difficulty == 1), // 무조건 쉬운 난이도(1) 힌트를 primary로 설정
                        ]);
                        
                        $createdHints[] = $hint;
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => "힌트가 생성되었습니다. (성공: {$successCount}개, 실패: {$errorCount}개)",
                    'hints' => $createdHints,
                    'word' => $word->load('hints'),
                    'summary' => [
                        'total' => 3,
                        'success' => $successCount,
                        'error' => $errorCount
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? '힌트 생성 중 오류가 발생했습니다.'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '서버 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 여러 단어에 대한 힌트 일괄 생성
     */
    public function generateBatch(Request $request)
    {
        $validated = $request->validate([
            'word_ids' => 'required|array',
            'word_ids.*' => 'exists:pz_words,id',
            'overwrite' => 'boolean'
        ]);

        try {
            DB::beginTransaction();

            $words = PzWord::active()->whereIn('id', $validated['word_ids'])->get();
            $results = [];
            $successCount = 0;
            $errorCount = 0;

            foreach ($words as $word) {
                // 기존 힌트가 있고 덮어쓰기가 false인 경우 스킵
                if (!$validated['overwrite'] && $word->hints()->exists()) {
                    $results[] = [
                        'word_id' => $word->id,
                        'word' => $word->word,
                        'status' => 'skipped',
                        'message' => '기존 힌트가 있어서 스킵되었습니다.'
                    ];
                    continue;
                }

                // 덮어쓰기인 경우 기존 힌트 삭제
                if ($validated['overwrite']) {
                    $word->hints()->delete();
                }

                $result = $this->geminiService->generateHint(
                    $word->word,
                    $word->category
                );

                if ($result['success']) {
                    // 기존 힌트 삭제
                    if ($validated['overwrite']) {
                        $word->hints()->delete();
                    }
                    
                    $createdHints = [];
                    $hintCount = 0;
                    
                    // 세 가지 난이도의 힌트를 모두 저장
                    foreach ($result['hints'] as $difficulty => $hintData) {
                        if ($hintData['success']) {
                            // 난이도 매핑 (1,2,3 -> 1,2,3)
                            $difficultyMap = [
                                1 => 1,
                                2 => 2, 
                                3 => 3
                            ];
                            
                            $hint = PzHint::create([
                                'word_id' => $word->id,
                                'hint_text' => $hintData['hint'],
                                'hint_type' => 'text',
                                'difficulty' => $difficultyMap[$difficulty] ?? 2,
                                'is_primary' => ($difficulty == $word->difficulty), // 단어의 난이도와 일치하는 힌트를 primary로 설정
                            ]);
                            
                            $createdHints[] = $hint;
                            $hintCount++;
                        }
                    }

                    $results[] = [
                        'word_id' => $word->id,
                        'word' => $word->word,
                        'status' => 'success',
                        'hint_count' => $hintCount,
                        'hints' => $createdHints
                    ];
                    $successCount++;
                } else {
                    $results[] = [
                        'word_id' => $word->id,
                        'word' => $word->word,
                        'status' => 'error',
                        'message' => $result['error']
                    ];
                    $errorCount++;
                }

                // API 호출 간격 조절
                sleep(1);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "힌트 생성 완료: 성공 {$successCount}개, 실패 {$errorCount}개",
                'results' => $results,
                'summary' => [
                    'total' => count($words),
                    'success' => $successCount,
                    'error' => $errorCount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch hint generation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '일괄 힌트 생성 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 카테고리별 힌트 생성
     */
    public function generateByCategory(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string',
            'overwrite' => 'boolean'
        ]);

        $words = PzWord::where('category', $validated['category'])
            ->active()
            ->get();

        if ($words->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => '해당 카테고리의 단어가 없습니다.'
            ], 404);
        }

        $wordIds = $words->pluck('id')->toArray();
        
        return $this->generateBatch(new Request([
            'word_ids' => $wordIds,
            'overwrite' => $validated['overwrite'] ?? false
        ]));
    }

    /**
     * 기존 힌트 수정 (더 쉽게)
     */
    public function regenerateHints(Request $request)
    {
        $validated = $request->validate([
            'hint_ids' => 'required|array',
            'hint_ids.*' => 'exists:pz_hints,id'
        ]);

        try {
            DB::beginTransaction();

            $hints = PzHint::whereIn('id', $validated['hint_ids'])
                ->whereHas('word', function($q) {
                    $q->active(); // 활성화된 단어의 힌트만
                })
                ->with('word')
                ->get();
            $results = [];
            $successCount = 0;
            $errorCount = 0;

            foreach ($hints as $hint) {
                $result = $this->geminiService->generateHint(
                    $hint->word->word,
                    $hint->word->category
                );

                if ($result['success'] && isset($result['hints'][$hint->difficulty])) {
                    $newHintText = $result['hints'][$hint->difficulty]['hint'];
                    
                    $hint->update([
                        'hint_text' => $newHintText,
                        'correction_status' => 'y' // 보정 완료 표시
                    ]);
                    
                    $results[] = [
                        'hint_id' => $hint->id,
                        'word' => $hint->word->word,
                        'status' => 'success',
                        'old_hint' => $hint->getOriginal('hint_text'),
                        'new_hint' => $newHintText
                    ];
                    $successCount++;
                } else {
                    $results[] = [
                        'hint_id' => $hint->id,
                        'word' => $hint->word->word,
                        'status' => 'error',
                        'message' => '힌트 재생성 실패'
                    ];
                    $errorCount++;
                }

                // API 호출 간격 조절
                sleep(1);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "힌트 수정 완료: 성공 {$successCount}개, 실패 {$errorCount}개",
                'results' => $results,
                'success_count' => $successCount,
                'error_count' => $errorCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Hint regeneration error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '힌트 수정 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 보정이 필요한 힌트 목록 조회
     */
    public function getHintsForCorrection(Request $request)
    {
        $query = PzHint::with('word')
            ->whereHas('word', function($q) {
                $q->active(); // 활성화된 단어의 힌트만
            })
            ->where(function($q) {
                $q->where('correction_status', '!=', 'y') // 보정되지 않은 힌트
                  ->orWhereNull('correction_status');
            });

        // 난이도별 필터링
        $difficulty = $request->input('difficulty');
        if ($difficulty) {
            $query->where('difficulty', $difficulty);
        }

        // 카테고리별 필터링
        $category = $request->input('category');
        if ($category) {
            $query->whereHas('word', function($q) use ($category) {
                $q->where('category', $category);
            });
        }

        $hints = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($hints);
    }

    /**
     * API 연결 테스트
     */
    public function testConnection()
    {
        try {
            $isConnected = $this->geminiService->testConnection();
            
            return response()->json([
                'success' => $isConnected,
                'message' => $isConnected ? 'API 연결 성공' : 'API 연결 실패'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'API 연결 테스트 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 힌트 생성 통계
     */
    public function getStats()
    {
        $stats = [
            'total_words' => PzWord::active()->count(),
            'words_with_hints' => PzWord::active()->has('hints')->count(),
            'words_without_hints' => PzWord::active()->doesntHave('hints')->count(),
            'total_hints' => PzHint::whereHas('word', function($query) {
                $query->active();
            })->count(),
            'categories' => PzWord::active()->selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->orderBy('count', 'desc')
                ->get()
        ];

        return response()->json($stats);
    }

    public function generateForWords(Request $request)
    {
        $words = $request->input('words', []);
        $hints = [];

        foreach ($words as $word) {
            $result = $this->geminiService->generateHint($word, '일반');
            if ($result['success']) {
                $hints[$word] = $result['hints'];
            }
        }

        return response()->json($hints);
    }

    public function getWords(Request $request)
    {
        $limit = $request->get('limit', 5);
        
        $words = DB::table('pz_words')
            ->where('is_active', true)
            ->whereRaw('LENGTH(word) <= 6') // 6글자 이하 단어만 선택
            ->inRandomOrder()
            ->limit($limit)
            ->pluck('word')
            ->toArray();
        
        return response()->json([
            'words' => $words
        ]);
    }

    public function getCrosswordWords(Request $request)
    {
        $level = $request->get('level', 1);
        
        // 레벨 정보 가져오기
        $levelInfo = DB::table('puzzle_levels')
            ->where('level', $level)
            ->first();
            
        if (!$levelInfo) {
            return response()->json(['error' => '레벨 정보를 찾을 수 없습니다.'], 404);
        }
        
        $wordCount = $levelInfo->word_count;
        $intersectionCount = $levelInfo->intersection_count;
        $wordDifficulty = $levelInfo->word_difficulty;
        
        // 새로운 난이도 규칙 적용
        $allowedDifficulties = $this->getAllowedDifficulties($wordDifficulty);
        
        // 출제 가능한 단어 풀 생성 (힌트 존재 + 난이도 맞는 단어들)
        $availableWords = DB::table('pz_words as pw')
            ->join('pz_hints as ph', 'pw.id', '=', 'ph.word_id')
            ->whereIn('pw.difficulty', $allowedDifficulties) // 새로운 난이도 규칙 적용
            ->where('pw.is_active', true)
            ->select('pw.id', 'pw.word', 'ph.hint_text', 'ph.difficulty as hint_difficulty')
            ->get();
            
        if ($availableWords->count() < $wordCount) {
            return response()->json(['error' => '충분한 단어가 없습니다.'], 400);
        }
        
        // 크로스워드 단어 조합 찾기
        $selectedWords = $this->findCrosswordCombination($availableWords, $wordCount, $intersectionCount);
        
        if (!$selectedWords) {
            return response()->json(['error' => '조건에 맞는 단어 조합을 찾을 수 없습니다.'], 400);
        }
        
        // 선택된 단어들의 힌트 정보 가져오기
        $selectedWordList = $selectedWords['words'];
        $hints = [];
        
        foreach ($selectedWordList as $word) {
            $wordHints = $availableWords->where('word', $word)->values();
            $hints[$word] = [];
            
            foreach ($wordHints as $hint) {
                $difficulty = $hint->hint_difficulty;
                $hints[$word][$difficulty] = [
                    'hint' => $hint->hint_text,
                    'difficulty' => $difficulty
                ];
            }
        }
        
        return response()->json([
            'level' => $level,
            'wordCount' => $wordCount,
            'intersectionCount' => $intersectionCount,
            'words' => $selectedWords,
            'hints' => $hints
        ]);
    }
    
    private function findCrosswordCombination($availableWords, $wordCount, $intersectionCount, $maxAttempts = 100)
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            // 1. 첫 번째 단어 선택
            $firstWord = $availableWords->random();
            $firstSyllables = $this->splitIntoSyllables($firstWord->word);
            
            // 2. 두 번째 단어 찾기 (첫 번째 단어와 교차)
            $secondWord = null;
            $intersection1 = null;
            $intersection1Info = null;
            
            foreach ($availableWords as $word) {
                if ($word->id === $firstWord->id) continue;
                
                $syllables = $this->splitIntoSyllables($word->word);
                $commonSyllables = array_intersect($firstSyllables, $syllables);
                
                if (!empty($commonSyllables)) {
                    // 교차점의 위치와 방향 정보 확인
                    foreach ($commonSyllables as $syllable) {
                        $firstPos = array_search($syllable, $firstSyllables);
                        $secondPos = array_search($syllable, $syllables);
                        
                        // 교차점 정보 생성
                        $intersectionInfo = [
                            'syllable' => $syllable,
                            'firstWord' => [
                                'word' => $firstWord->word,
                                'position' => $firstPos,
                                'direction' => 'horizontal' // 첫 번째 단어는 가로로 가정
                            ],
                            'secondWord' => [
                                'word' => $word->word,
                                'position' => $secondPos,
                                'direction' => 'vertical' // 두 번째 단어는 세로로 가정
                            ]
                        ];
                        
                        // 이 교차점이 유효한지 확인 (새로운 단어 생성 방지)
                        if ($this->isValidIntersection($intersectionInfo, [$firstWord->word])) {
                            $secondWord = $word;
                            $intersection1 = $syllable;
                            $intersection1Info = $intersectionInfo;
                            break 2;
                        }
                    }
                }
            }
            
            if (!$secondWord) continue;
            
            // 3. 교차점 후보 확장 (위치와 방향 고려)
            $secondSyllables = $this->splitIntoSyllables($secondWord->word);
            $usedSyllables = [$intersection1];
            $candidateSyllables = [];
            
            // 첫 번째 단어의 남은 음절들
            foreach ($firstSyllables as $pos => $syllable) {
                if (!in_array($syllable, $usedSyllables)) {
                    $candidateSyllables[] = [
                        'syllable' => $syllable,
                        'word' => $firstWord->word,
                        'position' => $pos,
                        'direction' => 'horizontal'
                    ];
                }
            }
            
            // 두 번째 단어의 남은 음절들
            foreach ($secondSyllables as $pos => $syllable) {
                if (!in_array($syllable, $usedSyllables)) {
                    $candidateSyllables[] = [
                        'syllable' => $syllable,
                        'word' => $secondWord->word,
                        'position' => $pos,
                        'direction' => 'vertical'
                    ];
                }
            }
            
            // 4. 세 번째 단어 찾기 (교차점 2개 만들기)
            $thirdWord = null;
            $intersection2 = null;
            $intersection2Info = null;
            
            foreach ($availableWords as $word) {
                if (in_array($word->id, [$firstWord->id, $secondWord->id])) continue;
                
                $syllables = $this->splitIntoSyllables($word->word);
                
                foreach ($candidateSyllables as $candidate) {
                    $syllablePos = array_search($candidate['syllable'], $syllables);
                    
                    if ($syllablePos !== false) {
                        // 교차점 정보 생성
                        $intersectionInfo = [
                            'syllable' => $candidate['syllable'],
                            'firstWord' => $candidate,
                            'secondWord' => [
                                'word' => $word->word,
                                'position' => $syllablePos,
                                'direction' => $candidate['direction'] === 'horizontal' ? 'vertical' : 'horizontal'
                            ]
                        ];
                        
                        // 이 교차점이 유효한지 확인
                        if ($this->isValidIntersection($intersectionInfo, [$firstWord->word, $secondWord->word])) {
                            $thirdWord = $word;
                            $intersection2 = $candidate['syllable'];
                            $intersection2Info = $intersectionInfo;
                            break 2;
                        }
                    }
                }
            }
            
            if (!$thirdWord) continue;
            
            // 5. 나머지 단어들 채우기
            $selectedWords = [$firstWord, $secondWord, $thirdWord];
            $usedWordIds = [$firstWord->id, $secondWord->id, $thirdWord->id];
            
            for ($i = 3; $i < $wordCount; $i++) {
                $nextWord = null;
                foreach ($availableWords as $word) {
                    if (!in_array($word->id, $usedWordIds)) {
                        $nextWord = $word;
                        break;
                    }
                }
                
                if ($nextWord) {
                    $selectedWords[] = $nextWord;
                    $usedWordIds[] = $nextWord->id;
                } else {
                    break;
                }
            }
            
            // 6. 조건 확인
            if (count($selectedWords) === $wordCount) {
                return [
                    'words' => array_map(function($word) { return $word->word; }, $selectedWords),
                    'intersections' => [
                        [
                            'word1' => $intersection1Info['firstWord']['word'],
                            'word2' => $intersection1Info['secondWord']['word'],
                            'syllable' => $intersection1,
                            'word1Position' => $intersection1Info['firstWord']['position'],
                            'word2Position' => $intersection1Info['secondWord']['position'],
                            'word1Direction' => $intersection1Info['firstWord']['direction'],
                            'word2Direction' => $intersection1Info['secondWord']['direction']
                        ],
                        [
                            'word1' => $intersection2Info['firstWord']['word'],
                            'word2' => $intersection2Info['secondWord']['word'],
                            'syllable' => $intersection2,
                            'word1Position' => $intersection2Info['firstWord']['position'],
                            'word2Position' => $intersection2Info['secondWord']['position'],
                            'word1Direction' => $intersection2Info['firstWord']['direction'],
                            'word2Direction' => $intersection2Info['secondWord']['direction']
                        ]
                    ]
                ];
            }
        }
        
        return null;
    }
    
    private function splitIntoSyllables($word)
    {
        // 한글 음절 단위로 분리
        $syllables = [];
        for ($i = 0; $i < mb_strlen($word, 'UTF-8'); $i++) {
            $syllables[] = mb_substr($word, $i, 1, 'UTF-8');
        }
        return $syllables;
    }

    /**
     * 교차점이 유효한지 확인 (새로운 단어 생성 방지)
     */
    private function isValidIntersection($intersectionInfo, $existingWords)
    {
        $syllable = $intersectionInfo['syllable'];
        $word1 = $intersectionInfo['firstWord'];
        $word2 = $intersectionInfo['secondWord'];
        
        // 교차점 위치 계산
        $word1Pos = $word1['position'];
        $word2Pos = $word2['position'];
        
        // 새로운 단어가 생성되는지 확인
        if ($word1['direction'] === 'horizontal' && $word2['direction'] === 'vertical') {
            // 가로 단어에서 세로로 교차
            $newVerticalWord = $this->getNewWordAtIntersection($word1['word'], $word2['word'], $word1Pos, $word2Pos);
            
            // 새로 생성된 세로 단어가 출제된 단어 목록에 있는지 확인
            if ($newVerticalWord && !in_array($newVerticalWord, $existingWords)) {
                return false; // 새로운 단어가 생성되므로 유효하지 않음
            }
        } elseif ($word1['direction'] === 'vertical' && $word2['direction'] === 'horizontal') {
            // 세로 단어에서 가로로 교차
            $newHorizontalWord = $this->getNewWordAtIntersection($word1['word'], $word2['word'], $word1Pos, $word2Pos);
            
            // 새로 생성된 가로 단어가 출제된 단어 목록에 있는지 확인
            if ($newHorizontalWord && !in_array($newHorizontalWord, $existingWords)) {
                return false; // 새로운 단어가 생성되므로 유효하지 않음
            }
        }
        
        return true;
    }
    
    /**
     * 교차점에서 새로 생성되는 단어 추출
     */
    private function getNewWordAtIntersection($word1, $word2, $pos1, $pos2)
    {
        // 교차점에서 새로 생성되는 단어를 추출하는 로직
        // 예: '생산'과 '산업'에서 '산'이 교차하면 '생사'라는 세로 단어가 생성됨
        
        $syllables1 = $this->splitIntoSyllables($word1);
        $syllables2 = $this->splitIntoSyllables($word2);
        
        // 교차점 이전의 음절들로 새 단어 구성
        $newWord = '';
        
        // 첫 번째 단어의 교차점 이전 음절들
        for ($i = 0; $i < $pos1; $i++) {
            $newWord .= $syllables1[$i];
        }
        
        // 두 번째 단어의 교차점 이전 음절들
        for ($i = 0; $i < $pos2; $i++) {
            $newWord .= $syllables2[$i];
        }
        
        return $newWord ?: null;
    }

    /**
     * 새로운 난이도 규칙에 따른 허용 난이도 반환
     */
    private function getAllowedDifficulties($levelDifficulty)
    {
        switch ($levelDifficulty) {
            case 1:
                return [1, 2]; // 레벨 1: 난이도 1,2
            case 2:
                return [1, 2, 3]; // 레벨 2: 난이도 1,2,3
            case 3:
                return [2, 3, 4]; // 레벨 3: 난이도 2,3,4
            case 4:
                return [3, 4, 5]; // 레벨 4: 난이도 3,4,5
            case 5:
                return [4, 5]; // 레벨 5: 난이도 4,5
            default:
                return [1, 2, 3, 4, 5]; // 기본값: 모든 난이도
        }
    }

    /**
     * Ajax로 단어 목록 제공 (DataTables용)
     */
    public function getWordsAjax(Request $request)
    {
        $start = $request->input('start', 0);
        $length = $request->input('length', 30);
        $search = $request->input('search.value', '');
        $orderColumn = $request->input('order.0.column', 0); // 기본값을 id(최근 등록순)로 변경
        $orderDir = $request->input('order.0.dir', 'desc'); // 기본값을 desc로 변경
        
        // 컬럼 매핑
        $columns = ['id', 'category', 'word', 'length', 'difficulty', 'hints_count'];
        $orderBy = $columns[$orderColumn] ?? 'id'; // 기본값을 id로 변경
        
        $query = PzWord::active()->withCount('hints');
        
        // 검색 조건
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('word', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }
        
        // 힌트 보유 상태 필터링
        $status = $request->input('status');
        if ($status === 'with_hints') {
            $query->whereHas('hints');
        } elseif ($status === 'without_hints') {
            $query->doesntHave('hints');
        }
        
        $totalRecords = $query->count();
        $filteredRecords = $query->count();
        
        $words = $query->orderBy($orderBy, $orderDir)
            ->skip($start)
            ->take($length)
            ->get();
        
        $data = [];
        foreach ($words as $word) {
            $data[] = [
                '<input type="checkbox" class="word-checkbox" value="' . $word->id . '">',
                $word->category,
                $word->word,
                $word->length,
                '<span class="badge bg-' . $this->getDifficultyBadgeColor($word->difficulty) . '">' . $word->difficulty_text . '</span>',
                '<span class="badge bg-' . ($word->hints_count > 0 ? 'success' : 'secondary') . '">' . $word->hints_count . '</span>',
                $this->getActionButtons($word)
            ];
        }
        
        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }
    
    /**
     * 난이도별 배지 색상 반환
     */
    private function getDifficultyBadgeColor($difficulty)
    {
        switch ($difficulty) {
            case 1: return 'success';
            case 2: return 'warning';
            case 3: return 'danger';
            case 4: return 'dark';
            case 5: return 'secondary';
            default: return 'secondary';
        }
    }
    
    /**
     * 액션 버튼 HTML 생성
     */
    private function getActionButtons($word)
    {
        // 비활성화된 단어는 힌트 관련 버튼을 표시하지 않음
        if (!$word->is_active) {
            return '<span class="text-muted">비활성화됨</span>';
        }
        
        if ($word->hints_count > 0) {
            return '<button type="button" class="btn btn-sm btn-outline-primary" data-word-id="' . $word->id . '">
                        <i class="fas fa-eye"></i> 힌트 보기
                    </button>
                    <button type="button" class="btn btn-sm btn-warning ms-1" data-word-id="' . $word->id . '">
                        <i class="fas fa-redo"></i> 재생성
                    </button>';
        } else {
            return '<button type="button" class="btn btn-sm btn-primary" data-word-id="' . $word->id . '">
                        <i class="fas fa-magic"></i> 힌트 생성
                    </button>';
        }
    }

    /**
     * 단어의 힌트 데이터 가져오기
     */
    public function getWordHints($wordId)
    {
        try {
            $word = PzWord::active()->with('hints')->findOrFail($wordId);
            
            // 비활성화된 단어인 경우 힌트를 표시하지 않음
            if (!$word->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => '비활성화된 단어의 힌트는 표시할 수 없습니다.'
                ], 403);
            }
            
            $hints = $word->hints->map(function($hint) {
                return [
                    'id' => $hint->id,
                    'hint_text' => $hint->hint_text,
                    'difficulty' => $hint->difficulty,
                    'difficulty_text' => $hint->difficulty_text,
                    'hint_type' => $hint->hint_type,
                    'is_primary' => $hint->is_primary
                ];
            });
            
            return response()->json([
                'success' => true,
                'hints' => $hints,
                'word' => [
                    'id' => $word->id,
                    'word' => $word->word,
                    'category' => $word->category,
                    'length' => $word->length,
                    'difficulty' => $word->difficulty
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '힌트를 불러오는데 실패했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
} 