<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DdongsunRanking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'week_number',
        'ddongsun_power',
        'rank',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
