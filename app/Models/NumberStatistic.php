<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NumberStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'selection_count',
        'ddongsun_rankers_count',
        'week_number',
    ];
}
