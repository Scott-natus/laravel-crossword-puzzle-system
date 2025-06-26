<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Services\PuzzleGeneratorService;
use App\Models\PuzzleSessions;
use App\Models\PuzzleHintLimits;
use App\Models\PuzzleScoringRules;
use App\Models\PzWord;
use App\Models\PuzzleLevel;

class PuzzleGameController extends Controller
{
    /**
     * 퍼즐 생성
     */
    public function generate(Request $request, PuzzleGeneratorService $generator)
    {
        $request->validate([
            'level_id' => 'sometimes|required|integer|min:1|max:100',
            'level' => 'sometimes|required|integer|min:1|max:100',
        ]);
        
        $levelId = $request->input('level_id', $request->input('level'));

        if (!$levelId) {
            return response()->json(['success' => false, 'message' => 'level_id is required.'], 400);
        }

        $userId = $request->user()?->id;

        try {
            $puzzle = $generator->generatePuzzle($levelId, $userId);
        } catch (\Exception $e) {
            // 퍼즐 생성 중 어떤 오류가 발생하더라도, 서버가 다운되는 대신
            // 정상적인 JSON 오류 메시지를 반환하도록 처리합니다.
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }

        // 세션 저장
        $session = PuzzleSessions::create([
            'user_id' => $userId,
            'level_id' => $levelId,
            'session_token' => Str::uuid(),
            'grid_data' => $puzzle['grid'],
            'selected_words' => $puzzle['words'],
            'game_state' => null,
            'user_progress' => null,
            'started_at' => now(),
            'expires_at' => now()->addHours(2),
            'is_active' => true,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'session_token' => $session->session_token,
                'level' => $puzzle['level'],
                'grid' => $puzzle['grid'],
                'words' => $puzzle['words'],
                'word_positions' => $puzzle['word_positions'],
                'grid_rules' => $puzzle['grid_rules'],
            ]
        ]);
    }

    /**
     * 답안 제출
     */
    public function submitAnswer(Request $request)
    {
        $request->validate([
            'session_token' => 'required|string',
            'word_id' => 'required|integer',
            'answer' => 'required|string',
        ]);
        $session = PuzzleSessions::where('session_token', $request->input('session_token'))->firstOrFail();
        $word = PzWord::findOrFail($request->input('word_id'));
        $answer = $request->input('answer');

        $isCorrect = (mb_strtoupper($word->word) === mb_strtoupper($answer));
        // 진행상황 저장
        $progress = $session->user_progress ?? [];
        $progress[$word->id] = [
            'answer' => $answer,
            'is_correct' => $isCorrect,
            'answered_at' => now(),
        ];
        $session->user_progress = $progress;
        $session->save();

        return response()->json([
            'success' => true,
            'data' => [
                'is_correct' => $isCorrect,
            ]
        ]);
    }

    /**
     * 힌트 요청
     */
    public function hint(Request $request)
    {
        $request->validate([
            'session_token' => 'required|string',
            'word_id' => 'required|integer',
            'hint_type' => 'nullable|string',
        ]);
        $session = PuzzleSessions::where('session_token', $request->input('session_token'))->firstOrFail();
        $word = PzWord::findOrFail($request->input('word_id'));
        $hintType = $request->input('hint_type', 'text');

        // 힌트 제한 체크
        $hintLimit = PuzzleHintLimits::where('level_id', $session->level_id)
            ->where('hint_type', $hintType)
            ->first();
        if (!$hintLimit || !$hintLimit->is_active) {
            return response()->json(['success' => false, 'message' => '힌트 사용 불가'], 403);
        }
        // TODO: 힌트 사용 횟수, 쿨다운 체크 필요

        // 힌트 제공 (예시: 단어의 첫 글자)
        $hint = mb_substr($word->word, 0, 1) . str_repeat(' _', mb_strlen($word->word) - 1);

        return response()->json([
            'success' => true,
            'data' => [
                'hint' => $hint,
            ]
        ]);
    }

    /**
     * 퍼즐 완료 여부 확인
     */
    public function checkCompletion(Request $request)
    {
        $request->validate([
            'session_token' => 'required|string',
        ]);
        $session = PuzzleSessions::where('session_token', $request->input('session_token'))->firstOrFail();
        $words = $session->selected_words ?? [];
        $progress = $session->user_progress ?? [];
        $completed = true;
        foreach ($words as $word) {
            if (empty($progress[$word['id']]['is_correct']) || !$progress[$word['id']]['is_correct']) {
                $completed = false;
                break;
            }
        }
        return response()->json([
            'success' => true,
            'data' => [
                'completed' => $completed,
            ]
        ]);
    }

    /**
     * 게임 상태 저장
     */
    public function saveGameState(Request $request)
    {
        $request->validate([
            'session_token' => 'required|string',
            'game_state' => 'required|array',
        ]);
        $session = PuzzleSessions::where('session_token', $request->input('session_token'))->firstOrFail();
        $session->game_state = $request->input('game_state');
        $session->save();
        return response()->json(['success' => true]);
    }

    /**
     * 게임 결과 제출
     */
    public function submitResult(Request $request)
    {
        $request->validate([
            'session_token' => 'required|string',
            'score' => 'required|integer',
            'accuracy' => 'required|numeric',
            'hints_used' => 'required|integer',
            'time_used' => 'required|integer',
        ]);
        $session = PuzzleSessions::where('session_token', $request->input('session_token'))->firstOrFail();
        $session->status = 'completed';
        $session->is_active = false;
        $session->save();
        // TODO: 기록 저장, 업적/레벨업 처리 등
        return response()->json(['success' => true]);
    }
}
