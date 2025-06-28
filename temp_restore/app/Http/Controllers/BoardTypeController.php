<?php

namespace App\Http\Controllers;

use App\Models\BoardType;
use Illuminate\Http\Request;

class BoardTypeController extends Controller
{
    public function index()
    {
        $boardTypes = BoardType::where('is_active', true)->get();
        return view('board-types.index', compact('boardTypes'));
    }
}
