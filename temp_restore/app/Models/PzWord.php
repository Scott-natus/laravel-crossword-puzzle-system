<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PzWord extends Model
{
    use HasFactory;

    protected $fillable = [
        'word',
        'length',
        'category',
        'difficulty',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'difficulty' => 'integer',
    ];

    // 난이도 상수
    const DIFFICULTY_EASY = 1;
    const DIFFICULTY_MEDIUM = 2;
    const DIFFICULTY_HARD = 3;
    const DIFFICULTY_VERY_HARD = 4;
    const DIFFICULTY_EXTREME = 5;

    // 관계 정의
    public function hints()
    {
        return $this->hasMany(PzHint::class, 'word_id')->orderBy('created_at');
    }

    public function primaryHint()
    {
        return $this->hasOne(PzHint::class, 'word_id')->where('is_primary', true);
    }

    // 스코프
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByDifficulty($query, $difficulty)
    {
        $difficultyMap = [
            'easy' => self::DIFFICULTY_EASY,
            'medium' => self::DIFFICULTY_MEDIUM,
            'hard' => self::DIFFICULTY_HARD,
            'very_hard' => self::DIFFICULTY_VERY_HARD,
            'extreme' => self::DIFFICULTY_EXTREME,
        ];
        
        $difficultyValue = $difficultyMap[$difficulty] ?? $difficulty;
        return $query->where('difficulty', $difficultyValue);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('word', 'like', "%{$search}%")
              ->orWhere('category', 'like', "%{$search}%");
        });
    }

    // 액세서
    public function getHintCountAttribute()
    {
        return $this->hints()->count();
    }

    public function getDifficultyTextAttribute()
    {
        $difficultyMap = [
            1 => '쉬움',
            2 => '보통', 
            3 => '어려움',
            4 => '매우 어려움',
            5 => '극도 어려움'
        ];
        
        return $difficultyMap[$this->difficulty] ?? '알 수 없음';
    }

    // 부트 메서드
    protected static function boot()
    {
        parent::boot();

        // 단어 저장 시 글자수 자동 계산
        static::saving(function ($word) {
            $word->length = mb_strlen($word->word, 'UTF-8');
        });
    }
}
