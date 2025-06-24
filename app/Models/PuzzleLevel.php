<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuzzleLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'level',
        'level_name',
        'word_count',
        'word_difficulty',
        'hint_difficulty',
        'intersection_count',
        'time_limit',
        'updated_by'
    ];

    protected $casts = [
        'word_difficulty' => 'integer',
        'intersection_count' => 'integer',
        'time_limit' => 'integer',
    ];

    /**
     * 레벨별 명칭 반환
     */
    public static function getLevelName($level)
    {
        if ($level >= 1 && $level <= 10) {
            return '실마리 발견자 (Clue Spotter)';
        } elseif ($level >= 11 && $level <= 25) {
            return '단서 수집가 (Clue Collector)';
        } elseif ($level >= 26 && $level <= 50) {
            return '논리적 추적자 (Logical Tracer)';
        } elseif ($level >= 51 && $level <= 75) {
            return '미궁의 해설가 (Labyrinth Commentator)';
        } elseif ($level >= 76 && $level <= 99) {
            return '진실의 파수꾼 (Guardian of Truth)';
        } elseif ($level >= 100) {
            return '절대적 해답 (Absolute Resolution)';
        }
        
        return 'Unknown Level';
    }

    /**
     * 기본 실행시간 계산 (단어 개수 * 10초)
     */
    public static function calculateDefaultTimeLimit($wordCount)
    {
        return $wordCount * 10;
    }

    /**
     * 유효성 검사 규칙
     */
    public static function getValidationRules()
    {
        return [
            'word_count' => 'required|integer|min:1',
            'word_difficulty' => 'required|integer|between:1,5',
            'hint_difficulty' => 'required|in:easy,medium,hard',
            'intersection_count' => 'required|integer|min:1',
            'time_limit' => 'required|integer|min:1',
        ];
    }

    /**
     * 교차점 개수 유효성 검사
     */
    public function validateIntersectionCount()
    {
        return $this->intersection_count < $this->word_count;
    }
}
