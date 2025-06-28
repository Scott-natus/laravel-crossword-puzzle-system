<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuzzleHintLimits extends Model
{
    use HasFactory;

    protected $fillable = [
        'level_id',
        'hint_type',
        'max_uses_per_game',
        'cost_per_use',
        'cooldown_seconds',
        'unlock_required',
        'unlock_level',
        'description',
        'is_active'
    ];

    protected $casts = [
        'unlock_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function level()
    {
        return $this->belongsTo(PuzzleLevel::class, 'level_id');
    }

    public function isUnlockedForUser($userLevel)
    {
        if (!$this->unlock_required) {
            return true;
        }
        
        return $userLevel >= $this->unlock_level;
    }
}
