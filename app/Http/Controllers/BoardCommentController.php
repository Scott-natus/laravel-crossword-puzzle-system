<?php

namespace App\Http\Controllers;

use App\Models\BoardComment;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class BoardCommentController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth');
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'board_id' => 'required|exists:boards,id',
            'content' => 'required',
            'parent_id' => 'nullable|exists:board_comments,id',
        ]);

        // 게시글의 board_type_id 가져오기
        $board = \App\Models\Board::findOrFail($validated['board_id']);

        $comment = new \App\Models\BoardComment();
        $comment->board_id = $validated['board_id'];
        $comment->board_type_id = $board->board_type_id;  // board_type_id 설정
        $comment->user_id = auth()->id();
        $comment->content = $validated['content'];
        $comment->parent_id = $request->input('parent_id');
        $comment->save();

        // 댓글 알림 처리
        $this->notificationService->handleCommentNotification($comment);

        return redirect()->route('board.show', ['boardType' => $board->boardType->slug, 'board' => $board->id])
            ->with('success', '댓글이 등록되었습니다!');
    }

    /**
     * Display the specified resource.
     */
    public function show(BoardComment $boardComment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BoardComment $boardComment)
    {
        // 본인 댓글인지 확인
        if ($boardComment->user_id !== auth()->id()) {
            $board = $boardComment->board ?? \App\Models\Board::find($boardComment->board_id);
            $boardTypeSlug = $board->boardType->slug;
            return redirect()->route('board.show', ['boardType' => $boardTypeSlug, 'board' => $board->id])
                ->with('error', '자신의 댓글만 수정할 수 있습니다.');
        }

        return view('board.comments.edit', compact('boardComment'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BoardComment $boardComment)
    {
        // 본인 댓글인지 확인
        if ($boardComment->user_id !== auth()->id()) {
            $board = $boardComment->board ?? \App\Models\Board::find($boardComment->board_id);
            $boardTypeSlug = $board->boardType->slug;
            return redirect()->route('board.show', ['boardType' => $boardTypeSlug, 'board' => $board->id])
                ->with('error', '자신의 댓글만 수정할 수 있습니다.');
        }

        $validated = $request->validate([
            'content' => 'required',
        ]);

        $boardComment->content = $validated['content'];
        $boardComment->save();

        $board = $boardComment->board ?? \App\Models\Board::find($boardComment->board_id);
        $boardTypeSlug = $board->boardType->slug;
        return redirect()->route('board.show', ['boardType' => $boardTypeSlug, 'board' => $board->id])
            ->with('success', '댓글이 수정되었습니다.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BoardComment $boardComment)
    {
        // 관리자이거나 본인 댓글인 경우에만 삭제 가능
        if (auth()->user()->email !== 'rainynux@gmail.com' && $boardComment->user_id !== auth()->id()) {
            $board = $boardComment->board ?? \App\Models\Board::find($boardComment->board_id);
            $boardTypeSlug = $board->boardType->slug;
            return redirect()->route('board.show', ['boardType' => $boardTypeSlug, 'board' => $board->id])
                ->with('error', '자신의 댓글만 삭제할 수 있습니다.');
        }

        // 대댓글이 있는 경우 함께 삭제
        $boardComment->children()->delete();
        $boardComment->delete();

        $board = $boardComment->board ?? \App\Models\Board::find($boardComment->board_id);
        $boardTypeSlug = $board->boardType->slug;
        return redirect()->route('board.show', ['boardType' => $boardTypeSlug, 'board' => $board->id])
            ->with('success', '댓글이 삭제되었습니다.');
    }
}
