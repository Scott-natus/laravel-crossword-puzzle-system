<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuzzleGridRules extends Model
{
    use HasFactory;

    protected $fillable = [
        'level_id',
        'grid_size_min',
        'grid_size_max',
        'word_placement_rules',
        'symmetry_required',
        'black_square_ratio',
        'min_word_length',
        'max_word_length',
        'allow_diagonal',
        'allow_backwards',
        'grid_patterns',
        'is_active'
    ];

    protected $casts = [
        'word_placement_rules' => 'array',
        'symmetry_required' => 'boolean',
        'black_square_ratio' => 'decimal:2',
        'allow_diagonal' => 'boolean',
        'allow_backwards' => 'boolean',
        'grid_patterns' => 'array',
        'is_active' => 'boolean',
    ];

    public function level()
    {
        return $this->belongsTo(PuzzleLevel::class, 'level_id');
    }
}
