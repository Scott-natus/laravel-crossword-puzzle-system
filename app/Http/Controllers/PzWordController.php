<?php

namespace App\Http\Controllers;

use App\Models\PzWord;
use App\Models\PzHint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PzWordController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 통계 데이터
        $stats = $this->getStats();
        
        // 카테고리 목록 가져오기
        $categories = PzWord::distinct()->pluck('category')->sort()->values();
        
        // 난이도 목록
        $difficulties = [
            PzWord::DIFFICULTY_EASY => '쉬움', 
            PzWord::DIFFICULTY_MEDIUM => '보통', 
            PzWord::DIFFICULTY_HARD => '어려움',
            PzWord::DIFFICULTY_VERY_HARD => '매우 어려움',
            PzWord::DIFFICULTY_EXTREME => '극도 어려움'
        ];

        return view('puzzle.words.index', compact('stats', 'categories', 'difficulties'));
    }

    /**
     * 통계 데이터 조회
     */
    public function getStats()
    {
        $stats = [
            'total_words' => PzWord::count(),
            'active_words' => PzWord::where('is_active', true)->count(),
            'inactive_words' => PzWord::where('is_active', false)->count(),
            'words_with_hints' => PzWord::whereHas('hints')->count(),
            'words_without_hints' => PzWord::whereDoesntHave('hints')->count(),
            'total_hints' => PzHint::count(),
            'difficulty_distribution' => PzWord::select('difficulty', DB::raw('count(*) as count'))
                ->groupBy('difficulty')
                ->pluck('count', 'difficulty')
                ->toArray(),
            'category_distribution' => PzWord::select('category', DB::raw('count(*) as count'))
                ->groupBy('category')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'category')
                ->toArray()
        ];

        return $stats;
    }

    /**
     * DataTables용 데이터 API
     */
    public function getData(Request $request)
    {
        try {
            $draw = $request->input('draw', 1);
            $start = $request->input('start', 0);
            $length = $request->input('length', 25);
            $search = $request->input('search.value', '');
            $orderColumn = $request->input('order.0.column', 7); // 기본값: 입력일자
            $orderDir = $request->input('order.0.dir', 'desc');
            $difficultyFilter = $request->input('difficulty_filter', ''); // 난이도 필터 추가
            
            // 컬럼 매핑
            $columns = ['category', 'word', 'length', 'difficulty', 'hints_count', 'is_active', 'latest_hint_date', 'created_at'];
            $orderBy = $columns[$orderColumn] ?? 'created_at';
            
            // 쿼리 시작
            $query = PzWord::withCount('hints')
                ->withMax('hints', 'created_at');
            
            // 검색 조건
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('word', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }
            
            // 난이도 필터 조건
            if (!empty($difficultyFilter)) {
                $query->where('difficulty', $difficultyFilter);
            }
            
            // 전체 레코드 수 (필터 적용 전)
            $totalRecords = PzWord::count();
            
            // 필터 적용 후 레코드 수
            $filteredRecords = $query->count();
            
            // 정렬 및 페이징
            $query->orderBy($orderBy, $orderDir);
            $words = $query->skip($start)->take($length)->get();
            
            // 데이터 변환
            $data = $words->map(function ($word) {
                return [
                    'id' => $word->id,
                    'category' => $word->category,
                    'word' => $word->word,
                    'length' => $word->length,
                    'difficulty' => $word->difficulty,
                    'hints_count' => $word->hints_count,
                    'is_active' => $word->is_active,
                    'latest_hint_date' => $word->hints_max_created_at ? 
                        \Carbon\Carbon::parse($word->hints_max_created_at)->format('Y-m-d H:i') : '',
                    'created_at' => $word->created_at->format('Y-m-d H:i'),
                ];
            });
            
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            \Log::error('DataTables API 오류: ' . $e->getMessage());
            return response()->json([
                'error' => '데이터를 불러오는 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 일괄 처리 API
     */
    public function batchUpdate(Request $request)
    {
        $request->validate([
            'word_ids' => 'required|array',
            'word_ids.*' => 'integer|exists:pz_words,id'
        ]);

        $wordIds = $request->input('word_ids');
        $updates = [];

        try {
            DB::beginTransaction();

            // 난이도 변경
            if ($request->has('update_difficulty') && $request->input('update_difficulty')) {
                $difficulty = $request->input('difficulty');
                if (!in_array($difficulty, [1, 2, 3, 4, 5])) {
                    throw new \Exception('유효하지 않은 난이도입니다.');
                }
                $updates['difficulty'] = $difficulty;
            }

            // 사용여부 변경
            if ($request->has('update_active') && $request->input('update_active')) {
                $isActive = $request->input('is_active');
                if (!in_array($isActive, ['0', '1'])) {
                    throw new \Exception('유효하지 않은 활성화 상태입니다.');
                }
                $updates['is_active'] = (bool)$isActive;
            }

            // 변경할 항목이 없으면 오류
            if (empty($updates)) {
                throw new \Exception('변경할 항목을 선택해주세요.');
            }

            $updatedCount = PzWord::whereIn('id', $wordIds)->update($updates);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$updatedCount}개의 단어가 업데이트되었습니다.",
                'updated_count' => $updatedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => '일괄 처리 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('puzzle.words.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:50',
            'word' => 'required|string|max:50',
            'difficulty' => 'required|in:1,2,3,4,5',
        ]);

        try {
            DB::beginTransaction();

            $word = PzWord::create($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '단어가 추가되었습니다.',
                'word' => $word->load('hints')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => '단어 추가 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $word = PzWord::with('hints')->findOrFail($id);
        return response()->json($word);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $word = PzWord::with('hints')->findOrFail($id);
        return view('puzzle.words.edit', compact('word'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $word = PzWord::findOrFail($id);

        $validated = $request->validate([
            'is_active' => 'boolean',
            'difficulty' => 'sometimes|in:1,2,3,4,5',
        ]);

        $word->update($validated);

        return response()->json([
            'success' => true,
            'message' => '단어가 수정되었습니다.',
            'word' => $word->load('hints')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $word = PzWord::findOrFail($id);
        $word->delete();

        return response()->json([
            'success' => true,
            'message' => '단어가 삭제되었습니다.'
        ]);
    }

    /**
     * 단어 사용 여부 토글
     */
    public function toggleActive($id)
    {
        $word = PzWord::findOrFail($id);
        $word->update(['is_active' => !$word->is_active]);

        return response()->json([
            'success' => true,
            'message' => '사용 여부가 변경되었습니다.',
            'is_active' => $word->is_active
        ]);
    }
}
