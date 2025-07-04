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
}
