<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'user_id',
        'password',
        'comment_notify',
        'views',
        'parent_id',
        'board_type_id'
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function attachments()
    {
        return $this->hasMany(\App\Models\BoardAttachment::class);
    }

    public function comments()
    {
        return $this->hasMany(\App\Models\BoardComment::class);
    }

    public function votes()
    {
        return $this->hasMany(\App\Models\BoardVote::class);
    }

    public function agreeVotes()
    {
        return $this->votes()->where('is_agree', true);
    }

    public function disagreeVotes()
    {
        return $this->votes()->where('is_agree', false);
    }

    public function userVote()
    {
        return $this->votes()->where('user_id', auth()->id())->first();
    }

    public function parent()
    {
        return $this->belongsTo(Board::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Board::class, 'parent_id');
    }

    public function boardType()
    {
        return $this->belongsTo(BoardType::class);
    }
}
