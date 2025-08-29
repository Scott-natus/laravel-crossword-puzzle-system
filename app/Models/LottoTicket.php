<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LottoTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'image_path',
        'numbers',
        'ddongsun_power',
        'upload_date',
    ];

    protected $casts = [
        'numbers' => 'array',
        'upload_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
