<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoardComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'board_id',
        'board_type_id',
        'user_id',
        'content',
        'parent_id'
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function board()
    {
        return $this->belongsTo(\App\Models\Board::class);
    }

    public function boardType()
    {
        return $this->belongsTo(\App\Models\BoardType::class);
    }

    public function parent()
    {
        return $this->belongsTo(\App\Models\BoardComment::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(\App\Models\BoardComment::class, 'parent_id');
    }

    // 댓글의 계층(깊이) 반환
    public function getDepthAttribute()
    {
        $depth = 0;
        $parent = $this->parent;
        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }
        return $depth;
    }
}
