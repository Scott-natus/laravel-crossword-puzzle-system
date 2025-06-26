<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuzzleGridTemplates extends Model
{
    use HasFactory;

    protected $fillable = [
        'level_id',
        'template_name',
        'grid_pattern',
        'word_positions',
        'grid_width',
        'grid_height',
        'difficulty_rating',
        'word_count',
        'intersection_count',
        'category',
        'description',
        'is_active'
    ];

    protected $casts = [
        'grid_pattern' => 'array',
        'word_positions' => 'array',
        'is_active' => 'boolean',
    ];

    public function level()
    {
        return $this->belongsTo(PuzzleLevel::class, 'level_id');
    }

    public function getGridSize()
    {
        return [
            'width' => $this->grid_width,
            'height' => $this->grid_height
        ];
    }

    public function getWordCount()
    {
        return $this->word_count;
    }

    public function getIntersectionCount()
    {
        return $this->intersection_count;
    }
}
