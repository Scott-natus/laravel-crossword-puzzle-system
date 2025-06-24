<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Board;
use App\Models\BoardType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BoardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function index(Request $request, $boardType)
    {
        $search = $request->input('keyword');
        $searchType = $request->input('search_type', 'title');
        $boardType = BoardType::where('slug', $boardType)->firstOrFail();
        
        $query = Board::with(['user', 'attachments', 'children'])
            ->where('board_type_id', $boardType->id);
            
        if ($search) {
            $query->where(function($q) use ($search, $searchType) {
                switch ($searchType) {
                    case 'writer':
                        $q->whereHas('user', function($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                        break;
                    case 'content':
                        $q->where('content', 'like', "%{$search}%");
                        break;
                    case 'title':
                    default:
                        $q->where('title', 'like', "%{$search}%");
                        break;
                }
            });
        }

        $boards = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($boards);
    }

    public function show($boardType, $id)
    {
        $board = Board::with(['user', 'attachments', 'comments.user'])->findOrFail($id);
        $board->increment('views');

        return response()->json($board);
    }

    public function store(Request $request, $boardType)
    {
        $boardType = BoardType::where('slug', $boardType)->firstOrFail();
        
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'password' => 'required|string|min:4',
            'attachments.*' => 'file|max:102400',
        ]);

        try {
            DB::beginTransaction();

            $board = new Board();
            $board->title = $validated['title'];
            $board->content = $validated['content'];
            $board->password = bcrypt($validated['password']);
            $board->user_id = auth()->id();
            $board->views = 0;
            $board->parent_id = $request->input('parent_id');
            $board->board_type_id = $boardType->id;
            $board->save();

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    if ($file->isValid()) {
                        $path = $file->store('attachments', 'public');
                        $board->attachments()->create([
                            'file_path' => $path,
                            'file_type' => $file->getClientMimeType(),
                            'file_size' => $file->getSize(),
                            'original_name' => $file->getClientOriginalName(),
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json($board, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => '게시글 등록 중 오류가 발생했습니다.'], 500);
        }
    }

    public function update(Request $request, $boardType, $id)
    {
        $board = Board::findOrFail($id);
        
        if ($board->user_id !== auth()->id()) {
            return response()->json(['message' => '자신의 글만 수정할 수 있습니다.'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'attachments.*' => 'file|max:102400',
        ]);

        try {
            DB::beginTransaction();

            // 삭제할 첨부파일 처리
            $deletedAttachments = $request->input('files_to_delete');
            if ($deletedAttachments) {
                $deletedIds = json_decode($deletedAttachments, true);
                if (is_array($deletedIds)) {
                    foreach ($deletedIds as $attachmentId) {
                        $attachment = $board->attachments()->find($attachmentId);
                        if ($attachment) {
                            // 파일 시스템에서 삭제
                            Storage::disk('public')->delete($attachment->file_path);
                            // 데이터베이스에서 삭제
                            $attachment->delete();
                        }
                    }
                }
            }

            $board->title = $validated['title'];
            $board->content = $validated['content'];
            $board->save();

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    if ($file->isValid()) {
                        $path = $file->store('attachments', 'public');
                        $board->attachments()->create([
                            'file_path' => $path,
                            'file_type' => $file->getClientMimeType(),
                            'file_size' => $file->getSize(),
                            'original_name' => $file->getClientOriginalName(),
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json($board);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => '게시글 수정 중 오류가 발생했습니다.'], 500);
        }
    }

    public function destroy($boardType, $id)
    {
        $board = Board::findOrFail($id);
        
        if ($board->user_id !== auth()->id()) {
            return response()->json(['message' => '자신의 글만 삭제할 수 있습니다.'], 403);
        }

        try {
            DB::beginTransaction();

            // 첨부 파일 삭제
            foreach ($board->attachments as $attachment) {
                Storage::disk('public')->delete($attachment->file_path);
                $attachment->delete();
            }

            $board->delete();

            DB::commit();
            return response()->json(['message' => '게시글이 삭제되었습니다.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => '게시글 삭제 중 오류가 발생했습니다.'], 500);
        }
    }

    public function getRelatedThreads($boardType, $id)
    {
        $board = Board::with(['user', 'children.user'])->findOrFail($id);
        
        // 현재 글의 부모 글을 찾습니다
        $parent = $board->parent_id ? Board::with(['user', 'children.user'])->find($board->parent_id) : null;
        
        // 부모 글이 있으면 부모 글부터 시작하고, 없으면 현재 글부터 시작합니다
        $root = $parent ?? $board;
        
        // 트리 구조로 변환
        $tree = $this->buildThreadTree($root);
        
        return response()->json([$tree]);
    }

    private function buildThreadTree($board)
    {
        $result = [
            'id' => $board->id,
            'title' => $board->title,
            'content' => $board->content,
            'created_at' => $board->created_at,
            'user' => $board->user,
            'children' => []
        ];

        if ($board->children) {
            foreach ($board->children as $child) {
                $result['children'][] = $this->buildThreadTree($child);
            }
        }

        return $result;
    }
} 