<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuzzleScoringRules extends Model
{
    use HasFactory;

    protected $fillable = [
        'level_id',
        'base_score_per_word',
        'time_bonus_multiplier',
        'accuracy_bonus_multiplier',
        'hint_penalty_per_use',
        'streak_bonus_multiplier',
        'perfect_completion_bonus',
        'speed_bonus_threshold',
        'speed_bonus_multiplier',
        'first_try_bonus',
        'is_active'
    ];

    protected $casts = [
        'time_bonus_multiplier' => 'decimal:2',
        'accuracy_bonus_multiplier' => 'decimal:2',
        'streak_bonus_multiplier' => 'decimal:2',
        'speed_bonus_multiplier' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function level()
    {
        return $this->belongsTo(PuzzleLevel::class, 'level_id');
    }

    public function calculateScore($wordCount, $playTime, $accuracy, $hintsUsed, $isFirstTry = false)
    {
        $baseScore = $wordCount * $this->base_score_per_word;
        
        // 시간 보너스
        $timeBonus = $baseScore * ($this->time_bonus_multiplier - 1.0);
        
        // 정확도 보너스
        $accuracyBonus = $baseScore * ($this->accuracy_bonus_multiplier - 1.0) * ($accuracy / 100);
        
        // 힌트 페널티
        $hintPenalty = $hintsUsed * $this->hint_penalty_per_use;
        
        // 첫 시도 보너스
        $firstTryBonus = $isFirstTry ? $this->first_try_bonus : 0;
        
        // 속도 보너스
        $speedBonus = 0;
        if ($playTime <= $this->speed_bonus_threshold) {
            $speedBonus = $baseScore * ($this->speed_bonus_multiplier - 1.0);
        }
        
        $totalScore = $baseScore + $timeBonus + $accuracyBonus + $firstTryBonus + $speedBonus - $hintPenalty;
        
        return max(0, (int)$totalScore);
    }
}
