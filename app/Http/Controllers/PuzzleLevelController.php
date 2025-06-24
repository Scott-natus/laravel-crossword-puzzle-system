<?php

namespace App\Http\Controllers;

use App\Models\PuzzleLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
     * 기본 데이터 생성 (시더 역할)
     */
    public function generateDefaultData()
    {
        try {
            // 기존 데이터 삭제
            PuzzleLevel::truncate();
            
            $levels = [];
            
            // 레벨 1-100까지 기본 데이터 생성
            for ($level = 1; $level <= 100; $level++) {
                $levelName = PuzzleLevel::getLevelName($level);
                
                // 레벨별 기본 설정
                if ($level >= 1 && $level <= 10) {
                    $wordCount = 5 + ($level - 1) * 2; // 5, 7, 9, 11, 13, 15, 17, 19, 21, 23
                    $wordDifficulty = 1;
                    $hintDifficulty = 'easy';
                    $intersectionCount = max(2, $wordCount - 3);
                } elseif ($level >= 11 && $level <= 25) {
                    $wordCount = 25 + ($level - 11) * 3; // 25, 28, 31, 34, 37, 40, 43, 46, 49, 52, 55, 58, 61, 64, 67
                    $wordDifficulty = 2;
                    $hintDifficulty = 'easy';
                    $intersectionCount = max(5, $wordCount - 8);
                } elseif ($level >= 26 && $level <= 50) {
                    $wordCount = 70 + ($level - 26) * 4; // 70, 74, 78, 82, 86, 90, 94, 98, 102, 106, 110, 114, 118, 122, 126, 130, 134, 138, 142, 146, 150, 154, 158, 162, 166
                    $wordDifficulty = 3;
                    $hintDifficulty = 'medium';
                    $intersectionCount = max(10, $wordCount - 15);
                } elseif ($level >= 51 && $level <= 75) {
                    $wordCount = 170 + ($level - 51) * 5; // 170, 175, 180, 185, 190, 195, 200, 205, 210, 215, 220, 225, 230, 235, 240, 245, 250, 255, 260, 265, 270, 275, 280, 285, 290
                    $wordDifficulty = 4;
                    $hintDifficulty = 'medium';
                    $intersectionCount = max(15, $wordCount - 25);
                } elseif ($level >= 76 && $level <= 99) {
                    $wordCount = 295 + ($level - 76) * 6; // 295, 301, 307, 313, 319, 325, 331, 337, 343, 349, 355, 361, 367, 373, 379, 385, 391, 397, 403, 409, 415, 421, 427, 433
                    $wordDifficulty = 5;
                    $hintDifficulty = 'hard';
                    $intersectionCount = max(20, $wordCount - 35);
                } else { // level 100
                    $wordCount = 439;
                    $wordDifficulty = 5;
                    $hintDifficulty = 'hard';
                    $intersectionCount = 25;
                }
                
                $timeLimit = PuzzleLevel::calculateDefaultTimeLimit($wordCount);
                
                $levels[] = [
                    'level' => $level,
                    'level_name' => $levelName,
                    'word_count' => $wordCount,
                    'word_difficulty' => $wordDifficulty,
                    'hint_difficulty' => $hintDifficulty,
                    'intersection_count' => $intersectionCount,
                    'time_limit' => $timeLimit,
                    'updated_by' => Auth::user()->email,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            // 대량 삽입
            PuzzleLevel::insert($levels);
            
            return response()->json([
                'success' => true,
                'message' => '기본 데이터가 성공적으로 생성되었습니다.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '데이터 생성 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
}
