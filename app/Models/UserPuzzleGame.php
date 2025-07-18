<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPuzzleGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'guest_id',
        'current_puzzle_data',
        'current_game_state',
        'current_puzzle_started_at',
        'has_active_puzzle',
        'current_level',
        'first_attempt_at',
        'total_play_time',
        'accuracy_rate',
        'total_correct_answers',
        'total_wrong_answers',
        'current_level_correct_answers',
        'current_level_wrong_answers',
        'ranking',
        'last_played_at',
        'is_active',
    ];

    protected $casts = [
        'first_attempt_at' => 'datetime',
        'last_played_at' => 'datetime',
        'current_puzzle_started_at' => 'datetime',
        'is_active' => 'boolean',
        'has_active_puzzle' => 'boolean',
        'accuracy_rate' => 'decimal:2',
        'current_puzzle_data' => 'array',
        'current_game_state' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getCurrentLevelTemplate()
    {
        return PuzzleGridTemplate::where('level_id', $this->current_level)
            ->where('is_active', true)
            ->inRandomOrder()
            ->first();
    }

    public function updateAccuracyRate()
    {
        $total = $this->total_correct_answers + $this->total_wrong_answers;
        if ($total > 0) {
            $this->accuracy_rate = round(($this->total_correct_answers / $total) * 100, 2);
        }
        $this->save();
    }

    public function incrementCorrectAnswer()
    {
        $this->increment('total_correct_answers');
        $this->increment('current_level_correct_answers');
        $this->updateAccuracyRate();
    }

    public function incrementWrongAnswer()
    {
        $this->increment('total_wrong_answers');
        $this->increment('current_level_wrong_answers');
        $this->updateAccuracyRate();
    }

    public function resetCurrentLevelStats()
    {
        $this->current_level_correct_answers = 0;
        $this->current_level_wrong_answers = 0;
        $this->save();
    }

    public function advanceToNextLevel()
    {
        $this->increment('current_level');
        $this->resetCurrentLevelStats();
        $this->last_played_at = now();
        $this->save();
    }

    /**
     * 현재 퍼즐 세션 시작
     */
    public function startNewPuzzle($puzzleData)
    {
        $this->current_puzzle_data = $puzzleData;
        $this->current_game_state = [
            'answered_words' => [],
            'wrong_answers' => [],
            'hints_used' => [],
            'additional_hints' => [],
            'started_at' => now()->toISOString()
        ];
        $this->current_puzzle_started_at = now();
        $this->has_active_puzzle = true;
        $this->save();
    }

    /**
     * 현재 퍼즐 세션 종료
     */
    public function endCurrentPuzzle()
    {
        $this->current_puzzle_data = null;
        $this->current_game_state = null;
        $this->current_puzzle_started_at = null;
        $this->has_active_puzzle = false;
        $this->save();
    }

    /**
     * 게임 상태 업데이트
     */
    public function updateGameState($gameState)
    {
        $this->current_game_state = $gameState;
        $this->save();
    }

    /**
     * 활성 퍼즐이 있는지 확인
     */
    public function hasActivePuzzle()
    {
        return $this->has_active_puzzle && $this->current_puzzle_data !== null;
    }

    /**
     * 현재 퍼즐 데이터 가져오기
     */
    public function getCurrentPuzzleData()
    {
        return $this->current_puzzle_data;
    }

    /**
     * 현재 게임 상태 가져오기
     */
    public function getCurrentGameState()
    {
        return $this->current_game_state;
    }

    /**
     * 현재 레벨의 클리어 조건 확인
     */
    public function checkLevelClearCondition()
    {
        $level = PuzzleLevel::where('level', $this->current_level)->first();
        
        if (!$level || !$level->clear_condition) {
            return true; // 클리어 조건이 없으면 자유롭게 진행
        }

        // 현재 레벨을 클리어한 횟수 확인
        $clearCount = \DB::table('puzzle_game_records')
            ->where('user_id', $this->user_id)
            ->where('level_played', $this->current_level)
            ->where('game_status', 'completed')
            ->count();

        return $clearCount >= $level->clear_condition;
    }

    /**
     * 레벨 클리어 기록 저장
     */
    public function recordLevelClear($gameData = [])
    {
        $level = PuzzleLevel::where('level', $this->current_level)->first();
        
        \DB::table('puzzle_game_records')->insert([
            'user_id' => $this->user_id,
            'level_played' => $this->current_level,
            'game_status' => 'completed',
            'score' => $gameData['score'] ?? 0,
            'play_time' => $gameData['play_time'] ?? 0,
            'hints_used' => $gameData['hints_used'] ?? 0,
            'words_found' => $gameData['words_found'] ?? 0,
            'total_words' => $gameData['total_words'] ?? 0,
            'accuracy' => $gameData['accuracy'] ?? 0,
            'level_before' => $this->current_level,
            'level_after' => $this->current_level + 1,
            'level_up' => true,
            'game_data' => json_encode($gameData),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * 다음 레벨로 진행 가능한지 확인
     */
    public function canAdvanceToNextLevel()
    {
        // 클리어 조건 확인
        if (!$this->checkLevelClearCondition()) {
            return false;
        }

        // 다음 레벨이 존재하는지 확인
        $nextLevel = PuzzleLevel::where('level', $this->current_level + 1)->first();
        return $nextLevel !== null;
    }

    /**
     * 레벨 클리어 조건 메시지 반환
     */
    public function getLevelClearConditionMessage()
    {
        $level = PuzzleLevel::where('level', $this->current_level)->first();
        
        if (!$level || !$level->clear_condition) {
            return null;
        }

        $clearCount = \DB::table('puzzle_game_records')
            ->where('user_id', $this->user_id)
            ->where('level_played', $this->current_level)
            ->where('game_status', 'completed')
            ->count();

        $remaining = $level->clear_condition - $clearCount;
        
        if ($remaining <= 0) {
            return "레벨 {$this->current_level} 클리어 조건을 만족했습니다!";
        } else {
            return "레벨 {$this->current_level}을 {$level->clear_condition}회 클리어해야 합니다. (현재: {$clearCount}회, 남은 횟수: {$remaining}회)";
        }
    }
}
