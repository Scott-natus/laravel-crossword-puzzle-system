<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BoardComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index($boardType, $boardId)
    {
        $comments = BoardComment::where('board_id', $boardId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($comments);
    }

    public function store(Request $request, $boardType, $boardId)
    {
        $validator = Validator::make($request->all(), [
            'writer' => 'required|string|max:255',
            'password' => 'required|string|min:4',
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:board_comments,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $comment = new BoardComment();
        $comment->board_id = $boardId;
        $comment->user_id = auth()->id();
        $comment->content = $request->content;
        $comment->parent_id = $request->parent_id;
        $comment->save();

        return response()->json($comment, 201);
    }

    public function update(Request $request, $boardType, $boardId, BoardComment $comment)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($comment->user_id !== auth()->id()) {
            return response()->json(['message' => '자신의 댓글만 수정할 수 있습니다.'], 403);
        }

        $comment->content = $request->content;
        $comment->save();

        return response()->json($comment);
    }

    public function destroy(Request $request, $boardType, $boardId, BoardComment $comment)
    {
        if ($comment->user_id !== auth()->id()) {
            return response()->json(['message' => '자신의 댓글만 삭제할 수 있습니다.'], 403);
        }

        $comment->delete();

        return response()->json(null, 204);
    }
} 