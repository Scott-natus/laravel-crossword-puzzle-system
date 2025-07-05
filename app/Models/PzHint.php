<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PzHint extends Model
{
    use HasFactory;

    protected $fillable = [
        'word_id',
        'hint_text',
        'hint_type',
        'file_path',
        'original_name',
        'is_primary',
        'sort_order',
        'difficulty',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    // 관계 정의
    public function word()
    {
        return $this->belongsTo(PzWord::class, 'word_id');
    }

    // 스코프
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('hint_type', $type);
    }

    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    // 난이도 한글 변환
    public function getDifficultyTextAttribute()
    {
        $difficultyMap = [
            1 => '쉬움',
            2 => '보통',
            3 => '어려움'
        ];
        
        return $difficultyMap[$this->difficulty] ?? '보통';
    }

    public function getFileUrlAttribute()
    {
        if ($this->hint_type === 'image' && $this->file_path) {
            return asset('storage/' . $this->file_path);
        }
        if ($this->hint_type === 'sound' && $this->file_path) {
            return asset('storage/' . $this->file_path);
        }
        return null;
    }

    public function getOriginalNameAttribute()
    {
        return $this->original_name;
    }

    // 부트 메서드
    protected static function boot()
    {
        parent::boot();

        // Primary 힌트 설정 시 다른 힌트들의 primary 해제
        static::saving(function ($hint) {
            if ($hint->is_primary) {
                static::where('word_id', $hint->word_id)
                      ->where('id', '!=', $hint->id)
                      ->update(['is_primary' => false]);
            }
        });

        // 힌트 삭제 시 primary 힌트 재설정
        static::deleted(function ($hint) {
            if ($hint->is_primary) {
                $nextHint = static::where('word_id', $hint->word_id)
                                 ->orderBy('created_at')
                                 ->first();
                if ($nextHint) {
                    $nextHint->update(['is_primary' => true]);
                }
            }
        });
    }
}
