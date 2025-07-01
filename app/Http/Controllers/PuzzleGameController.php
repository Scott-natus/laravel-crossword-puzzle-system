<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserPuzzleGame;
use App\Models\PuzzleGridTemplates;
use App\Models\PuzzleLevel;
use App\Models\PzWord;
use App\Http\Controllers\GridTemplateController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PuzzleGameController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $game = UserPuzzleGame::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$game) {
            $game = UserPuzzleGame::create([
                'user_id' => $user->id,
                'current_level' => 1,
                'first_attempt_at' => now(),
                'is_active' => true,
            ]);
        }

        $level = PuzzleLevel::where('level', $game->current_level)->first();

        return view('puzzle.game.index', compact('game', 'level'));
    }

    public function getTemplate(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => '로그인이 필요합니다.'], 401);
        }

        $game = UserPuzzleGame::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$game) {
            $game = UserPuzzleGame::create([
                'user_id' => $user->id,
                'current_level' => 1,
                'first_attempt_at' => now(),
                'is_active' => true,
            ]);
        }

        $level = PuzzleLevel::where('level', $game->current_level)->first();
        if (!$level) {
            return response()->json(['error' => '레벨을 찾을 수 없습니다.'], 404);
        }

        // 레벨에 해당하는 템플릿 중 랜덤으로 하나 선택
        $template = DB::table('puzzle_grid_templates')
            ->where('level_id', $level->id)
            ->where('is_active', true)
            ->inRandomOrder()
            ->first();

        if (!$template) {
            return response()->json(['error' => '해당 레벨의 템플릿을 찾을 수 없습니다.'], 404);
        }

        // 5회 반복 로직으로 단어 추출 시도
        $maxRetries = 5;
        $retryCount = 0;
        $extractData = null;
        
        while ($retryCount < $maxRetries) {
            $retryCount++;
            
            // GridTemplateController의 extractWords 메서드 호출
            $templateService = app(\App\Services\PuzzleGridTemplateService::class);
            $gridTemplateController = new GridTemplateController($templateService);
            $extractRequest = new Request(['template_id' => $template->id]);
            $extractResult = $gridTemplateController->extractWords($extractRequest);
            
            $extractData = json_decode($extractResult->getContent(), true);
            
            // 성공하면 루프 종료
            if ($extractData['success']) {
                \Log::info("게임 단어 추출 성공 - 시도 #{$retryCount}에서 완료", [
                    'template_id' => $template->id,
                    'level_id' => $level->id,
                    'user_id' => $user->id
                ]);
                break;
            }
            
            \Log::info("게임 단어 추출 실패 - 시도 #{$retryCount}", [
                'template_id' => $template->id,
                'level_id' => $level->id,
                'user_id' => $user->id,
                'error_message' => $extractData['message'] ?? '알 수 없는 오류'
            ]);
            
            // 마지막 시도가 아니면 잠시 대기 후 재시도
            if ($retryCount < $maxRetries) {
                usleep(100000); // 0.1초 대기
            }
        }
        
        // 5회 시도 후에도 실패한 경우
        if (!$extractData['success']) {
            \Log::error("게임 단어 추출 최종 실패 - 5회 시도 후 실패", [
                'template_id' => $template->id,
                'level_id' => $level->id,
                'user_id' => $user->id,
                'final_error' => $extractData['message'] ?? '알 수 없는 오류'
            ]);
            
            return response()->json([
                'error' => '단어 추출에 실패했습니다. 잠시 후 다시 시도해주세요.',
                'retry_count' => $retryCount,
                'message' => $extractData['message'] ?? '알 수 없는 오류'
            ], 500);
        }

        // 단어 정보에 실제 pz_words ID 추가
        $wordsWithIds = [];
        foreach ($extractData['extracted_words']['word_order'] as $wordInfo) {
            // 단어 텍스트로 실제 pz_words ID 찾기
            $pzWord = DB::table('pz_words')
                ->where('word', $wordInfo['extracted_word'])
                ->where('is_active', true)
                ->first();
            
            $wordInfo['pz_word_id'] = $pzWord ? $pzWord->id : null;
            
            // 기본 힌트 ID 추가
            if ($pzWord) {
                $baseHint = DB::table('pz_hints')
                    ->where('word_id', $pzWord->id)
                    ->where('is_primary', true)
                    ->first();
                $wordInfo['hint_id'] = $baseHint ? $baseHint->id : null;
                $wordInfo['hint'] = $baseHint ? $baseHint->hint_text : null;
            } else {
                $wordInfo['hint_id'] = null;
                $wordInfo['hint'] = null;
            }
            
            $wordsWithIds[] = $wordInfo;
        }

        return response()->json([
            'template' => [
                'id' => $template->id,
                'template_name' => $template->template_name,
                'grid_pattern' => $extractData['extracted_words']['grid_info']['pattern'],
                'grid_width' => $extractData['extracted_words']['grid_info']['width'],
                'grid_height' => $extractData['extracted_words']['grid_info']['height'],
                'words' => $wordsWithIds
            ],
            'level' => $level,
            'game' => $game,
        ]);
    }

    public function checkAnswer(Request $request)
    {
        $request->validate([
            'word_id' => 'required|integer',
            'answer' => 'required|string',
        ]);

        $user = Auth::user();
        $game = UserPuzzleGame::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$game) {
            return response()->json(['error' => '게임을 찾을 수 없습니다.'], 404);
        }

        // 단어 ID로 실제 단어 정보 가져오기
        $word = DB::table('pz_words')
            ->where('id', $request->word_id)
            ->where('is_active', true)
            ->first();

        if (!$word) {
            return response()->json(['error' => '단어를 찾을 수 없습니다.'], 404);
        }

        // 정답 확인 (대소문자 구분 없이, 공백 제거)
        $userAnswer = trim(strtolower($request->answer));
        $correctAnswer = trim(strtolower($word->word));
        
        $isCorrect = ($userAnswer === $correctAnswer);
        
        // 디버깅 로그
        \Log::info('Answer check', [
            'word_id' => $request->word_id,
            'user_answer' => $userAnswer,
            'correct_answer' => $correctAnswer,
            'is_correct' => $isCorrect
        ]);
        
        if ($isCorrect) {
            $game->incrementCorrectAnswer();
            $message = '정답입니다!';
        } else {
            $game->incrementWrongAnswer();
            $message = '오답입니다. 누적 오답: ' . $game->current_level_wrong_answers . '회';
        }

        return response()->json([
            'is_correct' => $isCorrect,
            'message' => $message,
            'correct_answer' => $word->word, // 디버깅용으로 정답도 반환
        ]);
    }

    public function getHints(Request $request)
    {
        $request->validate([
            'word_id' => 'required|integer',
            'current_hint_id' => 'nullable|integer', // 현재 보여주고 있는 힌트 ID
            'base_hint_id' => 'nullable|integer', // 기본 힌트 ID (제외해야 함)
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => '로그인이 필요합니다.'], 401);
        }

        // 디버깅 로그 추가
        \Log::info('getHints called', [
            'word_id' => $request->word_id,
            'current_hint_id' => $request->current_hint_id,
            'base_hint_id' => $request->base_hint_id,
            'has_current_hint_id' => $request->has('current_hint_id'),
            'has_base_hint_id' => $request->has('base_hint_id')
        ]);

        // 사용자가 요청한 쿼리 방식으로 수정
        // 기본 힌트 ID를 사용하여 같은 word_id를 가진 다른 힌트를 찾되, 기본 힌트 ID는 제외
        $query = DB::table('pz_hints as h1')
            ->join('pz_hints as h2', 'h1.word_id', '=', 'h2.word_id')
            ->where('h1.id', $request->base_hint_id)
            ->where('h2.id', '<>', $request->base_hint_id)
            ->select('h2.hint_text as hint', 'h2.id')
            ->orderByRaw('RANDOM()')
            ->limit(1);

        // 실제 실행되는 SQL 쿼리를 로그에 출력
        \Log::info('SQL Query', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'base_hint_id' => $request->base_hint_id
        ]);

        // 추가 힌트 하나만 가져오기
        $additionalHint = $query->first();

        \Log::info('Query result', [
            'found_hint' => $additionalHint ? true : false,
            'hint_id' => $additionalHint ? $additionalHint->id : null,
            'hint_text' => $additionalHint ? $additionalHint->hint : null
        ]);

        if (!$additionalHint) {
            return response()->json([
                'hint' => null,
                'message' => '더 이상 사용할 수 있는 힌트가 없습니다.'
            ]);
        }

        return response()->json([
            'hint' => $additionalHint,
            'message' => '추가 힌트를 제공합니다.'
        ]);
    }

    public function completeLevel(Request $request)
    {
        $user = Auth::user();
        $game = UserPuzzleGame::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$game) {
            return response()->json(['error' => '게임을 찾을 수 없습니다.'], 404);
        }

        $game->advanceToNextLevel();

        return response()->json([
            'message' => '축하합니다! 다음 레벨로 진행합니다.',
            'new_level' => $game->current_level,
        ]);
    }

    public function gameOver(Request $request)
    {
        $user = Auth::user();
        $game = UserPuzzleGame::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$game) {
            return response()->json(['error' => '게임을 찾을 수 없습니다.'], 404);
        }

        $game->last_played_at = now();
        $game->save();

        return response()->json([
            'message' => '게임오버! 5분 후 재시도 가능합니다.',
        ]);
    }
}
