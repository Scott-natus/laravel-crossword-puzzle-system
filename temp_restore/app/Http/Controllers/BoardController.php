<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\BoardType;
use Mews\Purifier\Facades\Purifier;
use Illuminate\Support\Facades\Hash;
use App\Models\Board;

class BoardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $boardTypeSlug = $request->route('boardType');
        $boardType = BoardType::where('slug', $boardTypeSlug)->firstOrFail();
        
        // 디버깅 로그 추가
        \Log::info('Board index method called:', [
            'requested_slug' => $boardTypeSlug,
            'found_boardType_id' => $boardType->id,
            'found_boardType_name' => $boardType->name,
            'found_boardType_slug' => $boardType->slug
        ]);
        
        $query = \App\Models\Board::with(['user', 'attachments', 'children'])
            ->where('board_type_id', $boardType->id);
            
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
            $query->orWhereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // 전체 글을 created_at desc로 모두 가져옴
        $allBoards = $query->orderBy('created_at', 'desc')->get();

        // 트리 구조를 평탄화(flatten)
        $flattened = [];
        $addedIds = [];
        $addToFlat = function($board, $depth = 0) use (&$addToFlat, &$flattened, &$addedIds, $boardType) {
            if (in_array($board->id, $addedIds)) return; // 이미 추가된 글은 건너뜀
            $board->depth = $depth;
            $flattened[] = $board;
            $addedIds[] = $board->id;
            foreach ($board->children->where('board_type_id', $boardType->id)->sortBy('created_at') as $child) {
                $addToFlat($child, $depth + 1);
            }
        };
        // board_type_id가 같은 모든 글 중 parent_id가 null인 글(원글)부터 시작
        foreach ($allBoards as $board) {
            if ($board->parent_id === null) {
                $addToFlat($board, 0);
            }
        }

        // 페이징
        $page = $request->input('page', 1);
        $perPage = 15;
        $paged = array_slice($flattened, ($page - 1) * $perPage, $perPage);
        $boards = new LengthAwarePaginator($paged, count($flattened), $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        // 통계카드용 데이터
        $totalPosts = \App\Models\Board::where('board_type_id', $boardType->id)->count();
        $totalComments = \App\Models\BoardComment::whereHas('board', function($q) use ($boardType) {
            $q->where('board_type_id', $boardType->id);
        })->count();
        $activeUsers = \App\Models\User::whereHas('boards', function($q) use ($boardType) {
            $q->where('board_type_id', $boardType->id);
        })->count();
        $totalAttachments = \App\Models\BoardAttachment::whereHas('board', function($q) use ($boardType) {
            $q->where('board_type_id', $boardType->id);
        })->count();

        // 디버깅 로그 추가
        \Log::info('Board index view data:', [
            'boardType_id' => $boardType->id,
            'boardType_name' => $boardType->name,
            'boardType_slug' => $boardType->slug,
            'totalPosts' => $totalPosts
        ]);

        return view('board.index', compact('boards', 'search', 'totalPosts', 'totalComments', 'activeUsers', 'totalAttachments', 'boardType'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $boardType = BoardType::where('slug', $request->route('boardType'))->firstOrFail();
        return view('board.create', compact('boardType'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $boardType = BoardType::where('slug', $request->route('boardType'))->firstOrFail();
        
        // 디버깅을 위해 요청 데이터 로깅
        \Log::info('Board creation request data:', [
            'title' => $request->input('title'),
            'content_length' => strlen($request->input('content')),
            'has_content' => $request->has('content'),
            'all_data' => $request->all()
        ]);
        
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'password' => 'required',
            'attachments.*' => [
                'file',
                'max:102400', // 100MB
                'mimes:jpg,jpeg,png,gif,bmp,webp,mp4,avi,mov,wmv,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar',
                'mimetypes:image/jpeg,image/png,image/gif,image/bmp,image/webp,video/mp4,video/avi,video/quicktime,video/x-ms-wmv,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,text/plain,application/zip,application/x-rar-compressed'
            ],
        ]);

        \Log::info('Validation passed', [
            'validated' => $validated,
            'all_data' => $request->all()
        ]);

        try {
            DB::beginTransaction();

            $board = new \App\Models\Board();
            $board->title = $validated['title'];
            
            // 디버깅을 위해 content 데이터 로깅
            \Log::info('Content before purification:', [
                'content' => $validated['content']
            ]);
            
            // HTML Purifier를 사용하여 내용을 안전하게 처리
            $board->content = Purifier::clean($validated['content']);
            
            // 디버깅을 위해 정제된 content 데이터 로깅
            \Log::info('Content after purification:', [
                'content' => $board->content
            ]);
            
            $board->user_id = auth()->id();
            $board->password = bcrypt($validated['password']);
            $board->comment_notify = $request->has('comment_notify');
            $board->views = 0;
            $board->parent_id = $request->input('parent_id');
            $board->board_type_id = $boardType->id;
            
            // 디버깅을 위해 저장 전 데이터 로깅
            \Log::info('Board data before save:', $board->toArray());
            
            $board->save();

            // 첨부파일 저장
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    if ($file->isValid()) {
                        // 파일 확장자 검증
                        $extension = strtolower($file->getClientOriginalExtension());
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'mp4', 'avi', 'mov', 'wmv', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar'];
                        
                        if (!in_array($extension, $allowedExtensions)) {
                            continue;
                        }

                        // 파일명을 안전하게 처리
                        $safeFileName = time() . '_' . Str::random(10) . '.' . $extension;
                        
                        // storage/app/public/attachments 디렉토리에 저장
                        $path = $file->storeAs('attachments', $safeFileName, 'public');
                        
                        if ($path) {
                            $board->attachments()->create([
                                'file_path' => $path,
                                'file_type' => $file->getClientMimeType(),
                                'file_size' => $file->getSize(),
                                'original_name' => $file->getClientOriginalName(),
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('board.index', ['boardType' => $boardType->slug])->with('success', '게시글이 등록되었습니다!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Board creation error: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', '게시글 등록 중 오류가 발생했습니다.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($boardType, $id)
    {
        // 인증 체크 제거: 비회원도 게시글 상세 열람 가능
        $board = \App\Models\Board::with(['user', 'attachments', 'comments.user', 'comments.children.user', 'parent'])->findOrFail($id);
        
        // 디버깅을 위한 로그 추가
        \Log::info('Board data before increment:', [
            'id' => $board->id,
            'title' => $board->title,
            'content_length' => strlen($board->content)
        ]);
        
        $board->increment('views');
        
        // 디버깅을 위한 로그 추가
        \Log::info('Board data after increment:', [
            'id' => $board->id,
            'title' => $board->title,
            'content_length' => strlen($board->content)
        ]);

        // 트리의 루트(최상위 원글) 찾기
        $root = $board;
        while ($root->parent) {
            $root = $root->parent;
        }
        // 트리 전체(원글~답글) 불러오기
        $thread = $this->getThread($root);

        // 최상위 댓글만 전달 (계층형)
        $comments = $board->comments()->whereNull('parent_id')->orderBy('created_at')->get();

        return view('board.show', compact('board', 'comments', 'root', 'thread', 'boardType'));
    }

    // 재귀적으로 트리 구조를 반환
    protected function getThread($board)
    {
        $board->load(['children.user']);
        foreach ($board->children as $child) {
            $this->getThread($child);
        }
        return $board;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($boardType, $id)
    {
        $boardType = BoardType::where('slug', $boardType)->firstOrFail();
        $board = Board::findOrFail($id);
        
        // 관리자 권한 체크
        if (auth()->user() && auth()->user()->email === 'rainynux@gmail.com') {
            // 관리자는 수정 가능
            return view('board.edit', compact('board', 'boardType'));
        }
        
        // 본인 글인지 확인
        if ($board->user_id !== auth()->id()) {
            return redirect()->route('board.show', ['boardType' => $boardType->slug, 'board' => $id])
                ->with('error', '자신의 글만 수정할 수 있습니다.');
        }

        return view('board.edit', compact('board', 'boardType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $boardType, Board $board)
    {
        \Log::info('Update method called', [
            'boardType' => $boardType,
            'board_id' => $board->id
        ]);

        $boardType = BoardType::where('slug', $boardType)->firstOrFail();
        
        \Log::info('BoardType found', [
            'boardType' => $boardType->toArray()
        ]);

        // 디버깅을 위해 요청 데이터 로깅
        \Log::info('Update request data:', [
            'title' => $request->input('title'),
            'content_length' => strlen($request->input('content')),
            'has_content' => $request->has('content'),
            'has_attachments' => $request->hasFile('attachments'),
            'deleted_attachments' => $request->input('deleted_attachments'),
            'all_data' => $request->all()
        ]);
        
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'password' => 'required',
            'attachments.*' => [
                'file',
                'max:102400', // 100MB
                'mimes:jpg,jpeg,png,gif,bmp,webp,mp4,avi,mov,wmv,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar',
                'mimetypes:image/jpeg,image/png,image/gif,image/bmp,image/webp,video/mp4,video/avi,video/quicktime,video/x-ms-wmv,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,text/plain,application/zip,application/x-rar-compressed'
            ],
        ]);

        \Log::info('Validation passed', [
            'validated' => $validated
        ]);

        try {
            DB::beginTransaction();

            // 관리자 권한 체크
            if (auth()->user() && auth()->user()->email === 'rainynux@gmail.com' && $request->password === 'tngkrrhk') {
                // 관리자는 비밀번호 검증 없이 수정 가능
                \Log::info('Admin access granted');
            } else {
                // 일반 사용자는 비밀번호 검증
                \Log::info('Password check', [
                    'input_password' => $request->password,
                    'db_password_hash' => $board->password,
                    'hash_check' => Hash::check($request->password, $board->password)
                ]);
                if (!Hash::check($request->password, $board->password)) {
                    return back()->withErrors(['password' => '비밀번호가 일치하지 않습니다.']);
                }
            }

            // 본인 글인지 확인
            if ($board->user_id !== auth()->id() && !(auth()->user() && auth()->user()->email === 'rainynux@gmail.com')) {
                return redirect()->route('board.show', ['boardType' => $boardType->slug, 'board' => $board->id])
                    ->with('error', '자신의 글만 수정할 수 있습니다.');
            }

            // 삭제할 첨부파일 처리
            $deletedAttachments = $request->input('deleted_attachments');
            \Log::info('Deleted attachments processing:', [
                'raw_input' => $deletedAttachments,
                'input_type' => gettype($deletedAttachments),
                'is_null' => is_null($deletedAttachments),
                'is_empty' => empty($deletedAttachments)
            ]);
            
            if ($deletedAttachments) {
                $deletedIds = json_decode($deletedAttachments, true);
                \Log::info('Decoded deleted attachments:', [
                    'decoded' => $deletedIds,
                    'is_array' => is_array($deletedIds),
                    'count' => is_array($deletedIds) ? count($deletedIds) : 0
                ]);
                
                if (is_array($deletedIds)) {
                    foreach ($deletedIds as $attachmentId) {
                        \Log::info('Processing attachment deletion:', ['attachment_id' => $attachmentId]);
                        $attachment = $board->attachments()->find($attachmentId);
                        if ($attachment) {
                            \Log::info('Found attachment to delete:', [
                                'id' => $attachment->id,
                                'file_path' => $attachment->file_path,
                                'original_name' => $attachment->original_name
                            ]);
                            // 파일 시스템에서 삭제
                            \Storage::disk('public')->delete($attachment->file_path);
                            // 데이터베이스에서 삭제
                            $attachment->delete();
                            \Log::info('Attachment deleted successfully', ['attachment_id' => $attachmentId]);
                        } else {
                            \Log::warning('Attachment not found for deletion', ['attachment_id' => $attachmentId]);
                        }
                    }
                } else {
                    \Log::warning('Failed to decode deleted attachments JSON', ['raw' => $deletedAttachments]);
                }
            } else {
                \Log::info('No deleted attachments to process');
            }

            // HTML Purifier로 content 정제
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'p,br,b,strong,i,em,u,h1,h2,h3,h4,h5,h6,ul,ol,li,a[href],img[src|alt],blockquote,pre,code,table,thead,tbody,tr,th,td');
            $config->set('HTML.SafeIframe', true);
            $config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%');
            $config->set('AutoFormat.AutoParagraph', true);
            $config->set('AutoFormat.RemoveEmpty', true);
            
            // 캐시 디렉토리 설정
            $cachePath = storage_path('app/purifier');
            if (!file_exists($cachePath)) {
                mkdir($cachePath, 0755, true);
            }
            $config->set('Cache.SerializerPath', $cachePath);
            
            try {
                $purifier = new \HTMLPurifier($config);
                $board->content = $purifier->purify($validated['content']);
                
                \Log::info('Content purified successfully', [
                    'content_length' => strlen($board->content)
                ]);
            } catch (\Exception $e) {
                \Log::error('HTML Purifier error: ' . $e->getMessage());
                return back()->withErrors(['content' => '내용 처리 중 오류가 발생했습니다.']);
            }

            \Log::info('Content before purification:', ['content' => $request->content]);
            \Log::info('Content after purification:', ['content' => $board->content]);

            // 게시글 데이터 업데이트
            $board->title = $validated['title'];
            $board->comment_notify = $request->has('comment_notify');
            
            \Log::info('Board data before save:', [
                'id' => $board->id,
                'title' => $board->title,
                'content_length' => strlen($board->content)
            ]);

            $board->save();

            \Log::info('Board data after save:', [
                'id' => $board->id,
                'title' => $board->title,
                'content_length' => strlen($board->content)
            ]);

            // 새 첨부파일 업로드 처리
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    if ($file->isValid()) {
                        // 파일 확장자 검증
                        $extension = strtolower($file->getClientOriginalExtension());
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'mp4', 'avi', 'mov', 'wmv', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar'];
                        
                        if (!in_array($extension, $allowedExtensions)) {
                            continue;
                        }

                        // 파일명을 안전하게 처리
                        $safeFileName = time() . '_' . Str::random(10) . '.' . $extension;
                        
                        // storage/app/public/attachments 디렉토리에 저장
                        $path = $file->storeAs('attachments', $safeFileName, 'public');
                        
                        if ($path) {
                            $board->attachments()->create([
                                'file_path' => $path,
                                'file_type' => $file->getClientMimeType(),
                                'file_size' => $file->getSize(),
                                'original_name' => $file->getClientOriginalName(),
                            ]);
                            \Log::info('New attachment uploaded', [
                                'file_path' => $path,
                                'original_name' => $file->getClientOriginalName()
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return redirect()
                ->route('board.show', ['boardType' => $boardType->slug, 'board' => $board->id])
                ->with('success', '게시글이 수정되었습니다.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Board update error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()
                ->withInput()
                ->withErrors(['error' => '게시글 수정 중 오류가 발생했습니다.']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($boardType, $id)
    {
        $board = \App\Models\Board::findOrFail($id);
        $user = auth()->user();
        $inputPassword = request('password');
        $isAdmin = $user && $user->email === 'rainynux@gmail.com';
        $masterPassword = 'tngkrrhk';

        // 관리자 마스터 패스워드 허용
        if ($isAdmin && Hash::check($inputPassword, bcrypt($masterPassword))) {
            // 통과 (관리자)
        } else {
            // 본인 글인지 확인
            if ($board->user_id !== auth()->id()) {
                return redirect()->route('board.show', ['boardType' => $boardType, 'board' => $id])
                    ->with('error', '자신의 글만 삭제할 수 있습니다.');
            }
            // 비밀번호 확인
            if (!$inputPassword || !Hash::check($inputPassword, $board->password)) {
                return redirect()->route('board.show', ['boardType' => $boardType, 'board' => $id])
                    ->with('error', '비밀번호가 일치하지 않습니다.');
            }
        }
        // 첨부파일 삭제
        foreach ($board->attachments as $attachment) {
            \Storage::disk('public')->delete($attachment->file_path);
            $attachment->delete();
        }
        // 댓글 삭제
        $board->comments()->delete();
        // 게시글 삭제
        $board->delete();
        return redirect()->route('board.index', ['boardType' => $boardType])
            ->with('success', '게시글이 삭제되었습니다.');
    }

    /**
     * Handle image upload from editor
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:10240', // 10MB
        ]);

        $file = $request->file('file');
        $path = $file->store('editor-images', 'public');

        return response()->json([
            'url' => Storage::url($path)
        ]);
    }

    /**
     * Copy the specified board post
     */
    public function copy(Board $board)
    {
        // 새 게시글 생성
        $newBoard = $board->replicate();
        $newBoard->title = '[복사] ' . $board->title;
        $newBoard->user_id = auth()->id();
        $newBoard->views = 0;
        $newBoard->save();

        // 첨부파일 복사
        foreach ($board->attachments as $attachment) {
            $newPath = 'attachments/' . time() . '_' . basename($attachment->file_path);
            Storage::disk('public')->copy($attachment->file_path, $newPath);
            
            $newBoard->attachments()->create([
                'file_path' => $newPath,
                'file_type' => $attachment->file_type,
                'file_size' => $attachment->file_size,
                'original_name' => $attachment->original_name,
            ]);
        }

        return redirect()->route('board.show', ['boardType' => $newBoard->boardType->slug, 'board' => $newBoard->id])
            ->with('success', '게시글이 복사되었습니다.');
    }
}
