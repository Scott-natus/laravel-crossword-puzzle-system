<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobilePushSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'daily_reminder',
        'level_complete',
        'achievement',
        'streak_reminder',
    ];

    protected $casts = [
        'daily_reminder' => 'boolean',
        'level_complete' => 'boolean',
        'achievement' => 'boolean',
        'streak_reminder' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
