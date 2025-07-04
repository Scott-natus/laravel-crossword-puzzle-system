<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPuzzleProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_level',
        'total_score',
        'games_played',
        'games_completed',
        'games_failed',
        'current_streak',
        'best_streak',
        'total_play_time',
        'last_played_at',
        'first_played_at',
        'is_active',
        'preferences'
    ];

    protected $casts = [
        'last_played_at' => 'datetime',
        'first_played_at' => 'datetime',
        'is_active' => 'boolean',
        'preferences' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 