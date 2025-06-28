<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardVote;
use Illuminate\Http\Request;

class BoardVoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function vote(Request $request, Board $board)
    {
        $validated = $request->validate([
            'is_agree' => 'required|boolean'
        ]);

        // 이미 투표한 경우 업데이트
        $vote = $board->userVote();
        if ($vote) {
            $vote->update($validated);
        } else {
            // 새로운 투표 생성
            $board->votes()->create([
                'user_id' => auth()->id(),
                'is_agree' => $validated['is_agree']
            ]);
        }

        return response()->json([
            'success' => true,
            'agree_count' => $board->agreeVotes()->count(),
            'disagree_count' => $board->disagreeVotes()->count(),
            'user_vote' => $validated['is_agree']
        ]);
    }
} 