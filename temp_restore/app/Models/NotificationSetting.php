<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_notify',
        'app_notify',
        'email',
        'device_token'
    ];

    protected $casts = [
        'email_notify' => 'boolean',
        'app_notify' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
