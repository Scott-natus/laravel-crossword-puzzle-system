<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LottoResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'draw_number',
        'draw_date',
        'numbers',
        'bonus_number',
    ];

    protected $casts = [
        'numbers' => 'array',
        'draw_date' => 'date',
    ];
}
