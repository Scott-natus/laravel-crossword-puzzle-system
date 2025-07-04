<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PuzzleGameRecord extends Model
{
    protected $fillable = [
        'user_id',
        'level_played',
        'game_status',
        'score',
        'play_time',
        'hints_used',
        'words_found',
        'total_words',
        'accuracy',
        'level_before',
        'level_after',
        'level_up',
        'game_data',
        'notes'
    ];

    protected $casts = [
        'level_up' => 'boolean',
        'game_data' => 'array',
        'accuracy' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 