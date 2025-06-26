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
        $query = PzWord::with(['primaryHint'])
            ->withCount('hints') // 힌트 개수
            ->selectSub(function ($query) {
                $query->from('pz_hints')
                    ->whereColumn('pz_hints.word_id', 'pz_words.id')
                    ->latest('created_at')
                    ->limit(1)
                    ->select('created_at');
            }, 'latest_hint_date'); // 마지막 힌트 생성일

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
        }

        // 정렬
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        if (in_array($sortBy, ['hints_count', 'difficulty', 'latest_hint_date', 'created_at'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            // 기본 정렬: 생성일자 내림차순
            $query->orderBy('created_at', 'desc');
        }
        
        // 2차 정렬: 단어 알파벳순
        $query->orderBy('word', 'asc');
        
        // 3차 정렬: 카테고리 알파벳순
        $query->orderBy('category', 'asc');

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

        $words = $query->paginate(15)->withQueryString();

        return view('puzzle.words.index', compact(
            'words', 
            'categories', 
            'difficulties', 
            'sortBy', 
            'sortDir',
            'searchType',
            'searchCategory',
            'searchWord'
        ));
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
            'difficulty' => 'required|in:1,2,3',
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
