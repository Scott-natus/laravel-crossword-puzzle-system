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
        if (!$user) {
            // 비로그인 상태일 때 안내 페이지 표시
            return view('puzzle.game.guest');
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

        // 단어 정보에 실제 pz_words ID 추가 (보안을 위해 단어 텍스트는 제거)
        $wordsWithIds = [];
        foreach ($extractData['extracted_words']['word_order'] as $wordInfo) {
            // 단어 텍스트로 실제 pz_words ID 찾기
            $pzWord = DB::table('pz_words')
                ->where('word', $wordInfo['extracted_word'])
                ->where('is_active', true)
                ->first();
            
            // 보안을 위해 단어 텍스트는 제거하고 ID만 전송
            $secureWordInfo = [
                'pz_word_id' => $pzWord ? $pzWord->id : null,
                'category' => $pzWord ? $pzWord->category : '일반',
                'position' => $wordInfo['position'],
                'word_id' => $wordInfo['word_id'] // 그리드 내 단어 ID
            ];
            
            // 기본 힌트 정보만 추가 (정답 단어는 제외)
            if ($pzWord) {
                $baseHint = DB::table('pz_hints')
                    ->where('word_id', $pzWord->id)
                    ->where('is_primary', true)
                    ->first();
                $secureWordInfo['hint_id'] = $baseHint ? $baseHint->id : null;
                $secureWordInfo['hint'] = $baseHint ? $baseHint->hint_text : null;
            } else {
                $secureWordInfo['hint_id'] = null;
                $secureWordInfo['hint'] = null;
            }
            
            $wordsWithIds[] = $secureWordInfo;
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
        
        // 보안을 위해 정답 정보는 로그에 기록하지 않음
        \Log::info('Answer check', [
            'word_id' => $request->word_id,
            'is_correct' => $isCorrect
        ]);
        
        if ($isCorrect) {
            $game->incrementCorrectAnswer();
            $message = '정답입니다!';
        } else {
            $game->incrementWrongAnswer();
            $wrongCount = $game->current_level_wrong_answers;
            
            // 틀린 답변 기록
            $this->recordWrongAnswer($user->id, $word->id, $request->answer, $word->word, $word->category, $game->current_level);
            
            // 오답 4회일 때 특별한 메시지
            if ($wrongCount == 4) {
                $message = '현재 오답이 4회 입니다, 5회 오답시 레벨을 재시작합니다';
            } else {
                $message = '오답입니다. 누적 오답: ' . $wrongCount . '회';
            }
            
            // 오답 5회 초과 체크
            if ($wrongCount >= 5) {
                // 게임 상태 초기화
                $game->current_level_correct_answers = 0;
                $game->current_level_wrong_answers = 0;
                $game->save();
                
                return response()->json([
                    'is_correct' => false,
                    'message' => '오답회수가 초과했습니다, 레벨을 다시 시작합니다.',
                    'restart_level' => true,
                    'wrong_count_exceeded' => true
                ]);
            }
        }

        return response()->json([
            'is_correct' => $isCorrect,
            'message' => $message,
            'correct_answer' => $isCorrect ? $word->word : null, // 정답일 때만 정답 전송
            'correct_count' => $game->current_level_correct_answers,
            'wrong_count' => $game->current_level_wrong_answers
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

        // 요구사항: 기본 힌트 제외하고 난이도 순서로 힌트 선택 (쉬운 것부터)
        $query = DB::table('pz_hints')
            ->where('word_id', $request->word_id)
            ->where('id', '<>', $request->base_hint_id) // 기본 힌트 제외
            ->select('hint_text as hint', 'id', 'difficulty')
            ->orderBy('difficulty', 'asc') // 난이도 낮은 것부터 (쉬운 것 우선)
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
            'hint_text' => $additionalHint ? $additionalHint->hint : null,
            'difficulty' => $additionalHint ? $additionalHint->difficulty : null
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

    public function showAnswer(Request $request)
    {
        $request->validate([
            'word_id' => 'required|integer',
        ]);

        $user = Auth::user();
        
        // 관리자 권한 체크
        if (!$user->is_admin) {
            return response()->json(['error' => '관리자만 접근 가능합니다.'], 403);
        }

        // 단어 정보 가져오기
        $word = DB::table('pz_words')
            ->where('id', $request->word_id)
            ->where('is_active', true)
            ->first();

        if (!$word) {
            return response()->json(['error' => '단어를 찾을 수 없습니다.'], 404);
        }

        return response()->json([
            'success' => true,
            'answer' => $word->word,
            'message' => '정답: ' . $word->word,
            'show_answer' => true
        ]);
    }

    /**
     * 틀린 답변 기록
     */
    private function recordWrongAnswer($userId, $wordId, $userAnswer, $correctAnswer, $category, $level)
    {
        try {
            // 매번 새로운 틀린 답변 기록 생성
            DB::table('user_wrong_answers')->insert([
                'user_id' => $userId,
                'word_id' => $wordId,
                'user_answer' => $userAnswer,
                'correct_answer' => $correctAnswer,
                'category' => $category,
                'level' => $level,
                'created_at' => now()
            ]);

            \Log::info('틀린 답변 기록', [
                'user_id' => $userId,
                'word_id' => $wordId,
                'user_answer' => $userAnswer,
                'category' => $category,
                'level' => $level
            ]);

        } catch (\Exception $e) {
            \Log::error('틀린 답변 기록 실패', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'word_id' => $wordId
            ]);
        }
    }
}
