<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPuzzleGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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
        'is_active' => 'boolean',
        'accuracy_rate' => 'decimal:2',
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
