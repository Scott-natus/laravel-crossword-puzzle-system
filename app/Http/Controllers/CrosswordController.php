<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class CrosswordController extends Controller
{
    public function getPuzzle(Request $request, $levelId): JsonResponse
    {
        try {
            // 퍼즐 정보 조회
            $puzzle = DB::table('crossword_puzzles')
                ->where('level_id', $levelId)
                ->first();

            if (!$puzzle) {
                return response()->json(['error' => '퍼즐을 찾을 수 없습니다.'], 404);
            }

            // 단어 정보 조회
            $words = DB::table('crossword_words')
                ->where('puzzle_id', $puzzle->id)
                ->get();

            // 그리드 정보 조회
            $grid = DB::table('crossword_grid')
                ->where('puzzle_id', $puzzle->id)
                ->orderBy('y')
                ->orderBy('x')
                ->get();

            // 그리드를 2차원 배열로 변환
            $gridArray = [];
            for ($y = 0; $y < $puzzle->grid_size; $y++) {
                $gridArray[$y] = [];
                for ($x = 0; $x < $puzzle->grid_size; $x++) {
                    $cell = $grid->where('x', $x)->where('y', $y)->first();
                    $gridArray[$y][$x] = [
                        'char' => $cell ? $cell->char_value : null,
                        'isBlack' => $cell ? $cell->is_black : false
                    ];
                }
            }

            // 단어 위치 정보 생성
            $wordPositions = [];
            foreach ($words as $word) {
                $positions = [];
                for ($i = 0; $i < $word->length; $i++) {
                    $x = $word->start_x + ($word->direction == 0 ? $i : 0);
                    $y = $word->start_y + ($word->direction == 1 ? $i : 0);
                    $positions[] = [
                        'x' => $x,
                        'y' => $y,
                        'char' => mb_substr($word->word, $i, 1)
                    ];
                }
                $wordPositions[] = [
                    'word_id' => $word->id,
                    'word' => $word->word,
                    'clue' => $word->clue,
                    'start_x' => $word->start_x,
                    'start_y' => $word->start_y,
                    'direction' => $word->direction,
                    'length' => $word->length,
                    'positions' => $positions
                ];
            }

            return response()->json([
                'puzzle' => [
                    'id' => $puzzle->id,
                    'level_id' => $puzzle->level_id,
                    'name' => $puzzle->name,
                    'description' => $puzzle->description,
                    'grid_size' => $puzzle->grid_size,
                    'time_limit' => $puzzle->time_limit
                ],
                'grid' => $gridArray,
                'words' => $words->map(function($word) {
                    return [
                        'id' => $word->id,
                        'word' => $word->word,
                        'clue' => $word->clue,
                        'start_x' => $word->start_x,
                        'start_y' => $word->start_y,
                        'direction' => $word->direction,
                        'length' => $word->length
                    ];
                }),
                'word_positions' => $wordPositions
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => '서버 오류가 발생했습니다.'], 500);
        }
    }

    public function submitAnswer(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'puzzle_id' => 'required|integer',
                'answers' => 'required|array'
            ]);

            $puzzleId = $request->input('puzzle_id');
            $answers = $request->input('answers');

            // 정답 확인
            $correctWords = DB::table('crossword_words')
                ->where('puzzle_id', $puzzleId)
                ->get();

            $correctCount = 0;
            $totalWords = $correctWords->count();

            foreach ($correctWords as $word) {
                $userAnswer = $answers[$word->id] ?? '';
                if (strtolower($userAnswer) === strtolower($word->word)) {
                    $correctCount++;
                }
            }

            $isComplete = $correctCount === $totalWords;

            return response()->json([
                'correct_count' => $correctCount,
                'total_words' => $totalWords,
                'is_complete' => $isComplete,
                'score' => $isComplete ? ($correctCount * 100) : 0
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => '서버 오류가 발생했습니다.'], 500);
        }
    }

    public function storePuzzle(Request $request)
    {
        $data = $request->all();

        DB::transaction(function () use ($data) {
            // 1. 퍼즐 저장
            $puzzleId = DB::table('crossword_puzzles')->insertGetId([
                'level_id' => $data['level'],
                'name' => "레벨 {$data['level']} 퍼즐",
                'grid_size' => count($data['grid']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. 단어 및 힌트 저장
            foreach ($data['wordInfo'] as $wordInfo) {
                $wordId = DB::table('crossword_words')->insertGetId([
                    'puzzle_id' => $puzzleId,
                    'word' => $wordInfo['word'],
                    'clue' => $data['hints'][$wordInfo['word']][1]['hint'], // 보통 난이도 힌트
                    'start_x' => $wordInfo['startX'],
                    'start_y' => $wordInfo['startY'],
                    'direction' => $wordInfo['direction'] === 'horizontal' ? 0 : 1,
                    'length' => mb_strlen($wordInfo['word']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 힌트는 crossword_words 테이블의 clue 필드에만 저장
                // pz_hints 테이블 저장 로직 제거 (외래키 제약조건 문제 해결)
            }

            // 3. 그리드 저장
            foreach ($data['grid'] as $y => $row) {
                foreach ($row as $x => $char) {
                    if (!empty($char)) {
                        DB::table('crossword_grid')->insert([
                            'puzzle_id' => $puzzleId,
                            'x' => $x,
                            'y' => $y,
                            'char_value' => $char,
                            'is_black' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            return response()->json(['puzzle_id' => $puzzleId]);
        });

        return response()->json(['message' => '퍼즐이 성공적으로 저장되었습니다.']);
    }
} 