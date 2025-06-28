<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuzzleSessions extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'level_id',
        'session_token',
        'grid_data',
        'selected_words',
        'game_state',
        'user_progress',
        'started_at',
        'expires_at',
        'is_active',
        'status'
    ];

    protected $casts = [
        'grid_data' => 'array',
        'selected_words' => 'array',
        'game_state' => 'array',
        'user_progress' => 'array',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * 모델 속성을 JSON으로 직렬화할 때 호출됩니다.
     *
     * @param  mixed  $value
     * @return string
     *
     * @throws \Illuminate\Database\Eloquent\JsonEncodingException
     */
    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function level()
    {
        return $this->belongsTo(PuzzleLevel::class, 'level_id');
    }

    public function isExpired()
    {
        return now()->isAfter($this->expires_at);
    }

    public function isActive()
    {
        return $this->is_active && !$this->isExpired();
    }
}
