<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
        'requires_auth'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_auth' => 'boolean',
    ];

    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }
}
