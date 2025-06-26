<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoardAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_path',
        'file_type',
        'file_size',
        'original_name',
    ];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }
}
