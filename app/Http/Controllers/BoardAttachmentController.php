<?php

namespace App\Http\Controllers;

use App\Models\BoardAttachment;
use Illuminate\Http\Request;

class BoardAttachmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function destroy($id)
    {
        $attachment = BoardAttachment::findOrFail($id);
        
        // 본인 글의 첨부파일인지 확인
        if ($attachment->board->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => '자신의 글의 첨부파일만 삭제할 수 있습니다.'
            ], 403);
        }

        // 파일 삭제
        \Storage::disk('public')->delete($attachment->file_path);
        
        // DB에서 삭제
        $attachment->delete();

        return response()->json([
            'success' => true,
            'message' => '첨부파일이 삭제되었습니다.'
        ]);
    }
} 