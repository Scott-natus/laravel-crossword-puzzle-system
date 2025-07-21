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
     * DataTables용 데이터 API (클라이언트 사이드)
     */
    public function getData(Request $request)
    {
        try {
            $difficultyFilter = $request->input('difficulty_filter', ''); // 난이도 필터 추가
            $page = $request->input('page', 1); // 페이지 번호
            $perPage = $request->input('per_page', 100); // 페이지당 데이터 수
            
            \Log::info('getData 호출됨', [
                'page' => $page,
                'per_page' => $perPage,
                'difficulty_filter' => $difficultyFilter
            ]);
            
            // 쿼리 로깅 활성화
            \DB::enableQueryLog();
            
            // 기본 쿼리 (JOIN 최적화)
            $query = PzWord::select([
                'pz_words.*',
                \DB::raw('COALESCE(hints_stats.hints_count, 0) as hints_count'),
                \DB::raw('hints_stats.hints_max_created_at')
            ])
            ->leftJoin(\DB::raw('(
                SELECT 
                    word_id,
                    COUNT(*) as hints_count,
                    MAX(created_at) as hints_max_created_at
                FROM pz_hints 
                GROUP BY word_id
            ) as hints_stats'), 'pz_words.id', '=', 'hints_stats.word_id');
            
            // 난이도 필터 조건
            if (!empty($difficultyFilter)) {
                $query->where('difficulty', $difficultyFilter);
            }
            
            // 전체 개수 조회 (데이터베이스에서 직접 COUNT)
            $total = PzWord::when(!empty($difficultyFilter), function($query) use ($difficultyFilter) {
                return $query->where('difficulty', $difficultyFilter);
            })->count();
            
            // 페이지네이션 적용 (offset/limit 사용)
            $offset = ($page - 1) * $perPage;
            $words = $query->orderBy('created_at', 'desc')
                          ->offset($offset)
                          ->limit($perPage)
                          ->get();
            
            // 실행된 SQL 쿼리 로깅
            $queries = \DB::getQueryLog();
            \Log::info('실행된 SQL 쿼리들:', $queries);
            
            \Log::info('쿼리 결과', [
                'total' => $total,
                'current_page' => $page,
                'per_page' => $perPage,
                'has_more' => ($offset + $perPage) < $total,
                'data_count' => $words->count()
            ]);
            
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
                'data' => $data,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'draw' => $request->input('draw', 1),
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
                'has_more' => ($offset + $perPage) < $total
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
