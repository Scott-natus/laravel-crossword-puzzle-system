<?php

namespace App\Http\Controllers;

use App\Models\PuzzleLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PuzzleLevelController extends Controller
{
    /**
     * 레벨 목록 표시
     */
    public function index()
    {
        $levels = PuzzleLevel::orderBy('level', 'asc')->get();
        return view('puzzle.levels.index', compact('levels'));
    }

    /**
     * 개별 레벨 업데이트
     */
    public function update(Request $request, $id)
    {
        $level = PuzzleLevel::findOrFail($id);
        
        $validator = Validator::make($request->all(), PuzzleLevel::getValidationRules());
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '유효성 검사 실패',
                'errors' => $validator->errors()
            ], 422);
        }

        // 교차점 개수 유효성 검사
        if ($request->intersection_count >= $request->word_count) {
            return response()->json([
                'success' => false,
                'message' => '교차점 개수는 단어 개수보다 적어야 합니다.'
            ], 422);
        }

        try {
            $level->update([
                'word_count' => $request->word_count,
                'word_difficulty' => $request->word_difficulty,
                'hint_difficulty' => $request->hint_difficulty,
                'intersection_count' => $request->intersection_count,
                'time_limit' => $request->time_limit,
                'updated_by' => Auth::user()->email
            ]);

            return response()->json([
                'success' => true,
                'message' => '레벨이 성공적으로 업데이트되었습니다.',
                'level' => $level->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '업데이트 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 기본 데이터 생성 (origin 테이블에서 복원)
     */
    public function generateDefaultData()
    {
        try {
            // 기존 데이터 삭제
            PuzzleLevel::truncate();
            
            // puzzle_levels_origin에서 데이터 복사
            $originData = DB::table('puzzle_levels_origin')->get();
            
            if ($originData->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'puzzle_levels_origin 테이블에 데이터가 없습니다.'
                ], 404);
            }

            // 데이터 복원
            foreach ($originData as $row) {
                PuzzleLevel::create([
                    'level' => $row->level,
                    'level_name' => $row->level_name,
                    'word_count' => $row->word_count,
                    'word_difficulty' => $row->word_difficulty,
                    'hint_difficulty' => $row->hint_difficulty,
                    'intersection_count' => $row->intersection_count,
                    'time_limit' => $row->time_limit,
                    'updated_by' => Auth::user()->email
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => '기본 데이터가 성공적으로 복원되었습니다. (총 ' . $originData->count() . '개 레벨)'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '데이터 복원 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 샘플보기용 레벨 목록 조회
     */
    public function getLevelsForSamples()
    {
        try {
            $levels = PuzzleLevel::orderBy('level', 'asc')->get();
            
            return response()->json([
                'success' => true,
                'levels' => $levels
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '레벨 목록 조회 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
}
