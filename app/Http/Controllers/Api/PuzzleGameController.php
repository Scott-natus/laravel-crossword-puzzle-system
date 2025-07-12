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
use App\Models\UserPuzzleGame;
use Illuminate\Support\Facades\DB;

class PuzzleGameController extends Controller
{
    /**
     * React 앱용 퍼즐 템플릿 조회 (라라벨 웹의 getTemplate 참고)
     */
    public function getTemplate(Request $request)
    {
        $user = $request->user();
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
        
        while ($retryCount < $maxRetries && !$extractData) {
            try {
                $extractData = $this->extractWordsFromTemplate($template, $level);
                $retryCount++;
            } catch (\Exception $e) {
                \Log::error("단어 추출 실패 (시도 {$retryCount}): " . $e->getMessage());
                $retryCount++;
                
                if ($retryCount >= $maxRetries) {
                    return response()->json(['error' => '퍼즐 생성에 실패했습니다. 다시 시도해주세요.'], 500);
                }
            }
        }

        if (!$extractData) {
            return response()->json(['error' => '퍼즐 생성에 실패했습니다.'], 500);
        }

        // 단어 정보에 ID 추가
        $wordsWithIds = [];
        foreach ($extractData['extracted_words']['word_order'] as $wordInfo) {
            // 단어 텍스트로 실제 pz_words ID 찾기
            $pzWord = DB::table('pz_words')
                ->where('word', $wordInfo['extracted_word'])
                ->where('is_active', true)
                ->first();
            
            // 보안을 위해 단어 텍스트는 제거하고 ID만 전송
            $secureWordInfo = [
                'id' => $pzWord ? $pzWord->id : null,
                'pz_word_id' => $pzWord ? $pzWord->id : null,
                'category' => $pzWord ? $pzWord->category : '일반',
                'position' => $wordInfo['position'],
                'word_id' => $wordInfo['word_id'], // 그리드 내 단어 ID
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
            'success' => true,
            'data' => [
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
            ]
        ]);
    }

    /**
     * 템플릿에서 단어 추출 (라라벨 웹의 extractWordsFromTemplate 참고)
     */
    private function extractWordsFromTemplate($template, $level)
    {
        $gridPattern = json_decode($template->grid_pattern, true);
        $wordPositions = json_decode($template->word_positions, true);
        
        // 단어 위치 정보를 id 순서대로 정렬 (백엔드에서 처리)
        usort($wordPositions, function($a, $b) {
            return $a['id'] - $b['id'];
        });

        $extractedWords = [];
        $wordOrder = [];
        
        foreach ($wordPositions as $wordPos) {
            $word = $this->findSuitableWord($wordPos, $level);
            
            if ($word) {
                $extractedWords[] = $word->word;
                $wordOrder[] = [
                    'extracted_word' => $word->word,
                    'position' => $wordPos,
                    'word_id' => $wordPos['id']
                ];
            }
        }

        return [
            'extracted_words' => [
                'words' => $extractedWords,
                'word_order' => $wordOrder,
                'grid_info' => [
                    'pattern' => $gridPattern,
                    'width' => $template->grid_width,
                    'height' => $template->grid_height
                ]
            ]
        ];
    }

    /**
     * 적합한 단어 찾기 (라라벨 웹의 findSuitableWord 참고)
     */
    private function findSuitableWord($wordPosition, $level)
    {
        $length = $wordPosition['length'];
        $direction = $wordPosition['direction'];
        
        // 레벨 조건에 맞는 단어 검색
        $query = DB::table('pz_words')
            ->where('length', $length)
            ->where('is_active', true)
            ->where('difficulty', '<=', $level->word_difficulty);
        
        // 이미 사용된 단어 제외
        if (!empty($this->usedWords)) {
            $query->whereNotIn('word', $this->usedWords);
        }
        
        $word = $query->inRandomOrder()->first();
        
        if ($word) {
            $this->usedWords[] = $word->word;
            return $word;
        }
        
        return null;
    }

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
     * 답안 제출 (라라벨 웹과 동일한 로직)
     */
    public function submitAnswer(Request $request)
    {
        $request->validate([
            'word_id' => 'required|integer',
            'answer' => 'required|string',
        ]);

        $user = $request->user();
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
            'wrong_count' => $game->current_level_wrong_answers,
            'show_answer' => $user->is_admin ? $word->word : null // 관리자일 때만 정답 전송
        ]);
    }

    /**
     * 힌트 요청 (라라벨 웹과 동일한 로직)
     */
    public function getPuzzleHints(Request $request)
    {
        $request->validate([
            'word_id' => 'required|integer',
            'current_hint_id' => 'nullable|integer', // 현재 보여주고 있는 힌트 ID
            'base_hint_id' => 'nullable|integer', // 기본 힌트 ID (제외해야 함)
        ]);

        $user = $request->user();
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

    /**
     * 레벨 완료
     */
    public function completeLevel(Request $request)
    {
        $user = $request->user();
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

    /**
     * 틀린 답변 기록 (라라벨 웹과 동일)
     */
    private function recordWrongAnswer($userId, $wordId, $userAnswer, $correctAnswer, $category, $level)
    {
        try {
            DB::table('puzzle_wrong_answers')->insert([
                'user_id' => $userId,
                'word_id' => $wordId,
                'user_answer' => $userAnswer,
                'correct_answer' => $correctAnswer,
                'category' => $category,
                'level' => $level,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            \Log::error('틀린 답변 기록 실패', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'word_id' => $wordId
            ]);
        }
    }

    /**
     * 힌트 요청 (기존 메서드)
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
