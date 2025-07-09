<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWrongAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'word_id',
        'user_answer',
        'correct_answer',
        'category',
        'level'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 관계 정의
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function word()
    {
        return $this->belongsTo(PzWord::class, 'word_id');
    }

    // 스코프
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByWord($query, $wordId)
    {
        return $query->where('word_id', $wordId);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * 특정 단어를 틀린 횟수 조회
     */
    public function scopeWrongCount($query, $userId, $wordId)
    {
        return $query->where('user_id', $userId)
                    ->where('word_id', $wordId)
                    ->count();
    }

    /**
     * 자주 틀리는 단어 조회 (틀린 횟수 기준)
     */
    public function scopeFrequentlyWrong($query, $minCount = 3)
    {
        return $query->select('word_id', DB::raw('COUNT(*) as wrong_count'))
                    ->groupBy('word_id')
                    ->having('wrong_count', '>=', $minCount);
    }
}
