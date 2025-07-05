<?php

namespace App\Http\Controllers;

use App\Models\PzHint;
use App\Models\PzWord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PzHintController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * 힌트 목록 조회
     */
    public function index($wordId)
    {
        $hints = PzHint::where('word_id', $wordId)
                      ->orderBy('created_at')
                      ->get();

        return response()->json($hints);
    }

    /**
     * 힌트 저장
     */
    public function store(Request $request, $wordId)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'type' => 'required|in:text,image,sound',
            'difficulty' => 'nullable|integer|between:1,3',
            'file' => 'nullable|file|max:10240', // 10MB
        ]);

        try {
            DB::beginTransaction();

            $word = PzWord::findOrFail($wordId);
            
            $hintData = [
                'word_id' => $wordId,
                'hint_text' => $validated['content'],
                'hint_type' => $validated['type'],
                'difficulty' => $validated['difficulty'] ?? 2,
            ];

            // 파일 업로드 처리
            if ($request->hasFile('file') && in_array($validated['type'], ['image', 'sound'])) {
                $file = $request->file('file');
                $extension = $file->getClientOriginalExtension();
                $fileName = time() . '_' . uniqid() . '.' . $extension;
                
                $path = $file->storeAs('puzzle-hints', $fileName, 'public');
                
                if ($validated['type'] === 'image') {
                    $hintData['image_url'] = $path;
                } else {
                    $hintData['audio_url'] = $path;
                }
            }

            // 첫 번째 힌트인 경우 primary로 설정
            $isFirstHint = !$word->hints()->exists();
            if ($isFirstHint) {
                $hintData['is_primary'] = true;
            }

            $hint = PzHint::create($hintData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '힌트가 추가되었습니다.',
                'hint' => $hint
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => '힌트 추가 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 힌트 수정
     */
    public function update(Request $request, $id)
    {
        $hint = PzHint::findOrFail($id);

        $validated = $request->validate([
            'content' => 'required|string',
            'file' => 'nullable|file|max:10240',
        ]);

        try {
            DB::beginTransaction();

            $hint->hint_text = $validated['content'];

            // 파일 업로드 처리
            if ($request->hasFile('file') && in_array($hint->hint_type, ['image', 'sound'])) {
                // 기존 파일 삭제
                if ($hint->hint_type === 'image' && $hint->image_url) {
                    Storage::disk('public')->delete($hint->image_url);
                }
                if ($hint->hint_type === 'sound' && $hint->audio_url) {
                    Storage::disk('public')->delete($hint->audio_url);
                }

                $file = $request->file('file');
                $extension = $file->getClientOriginalExtension();
                $fileName = time() . '_' . uniqid() . '.' . $extension;
                
                $path = $file->storeAs('puzzle-hints', $fileName, 'public');
                
                if ($hint->hint_type === 'image') {
                    $hint->image_url = $path;
                } else {
                    $hint->audio_url = $path;
                }
            }

            $hint->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '힌트가 수정되었습니다.',
                'hint' => $hint
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => '힌트 수정 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 힌트 삭제
     */
    public function destroy($wordId, $id)
    {
        $hint = PzHint::findOrFail($id);

        try {
            DB::beginTransaction();

            // 파일 삭제
            if ($hint->hint_type === 'image' && $hint->image_url) {
                Storage::disk('public')->delete($hint->image_url);
            }
            if ($hint->hint_type === 'sound' && $hint->audio_url) {
                Storage::disk('public')->delete($hint->audio_url);
            }

            $hint->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '힌트가 삭제되었습니다.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => '힌트 삭제 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * Primary 힌트 설정
     */
    public function setPrimary($wordId, $id)
    {
        $hint = PzHint::findOrFail($id);
        
        $hint->update(['is_primary' => true]);

        return response()->json([
            'success' => true,
            'message' => '주요 힌트로 설정되었습니다.'
        ]);
    }

    /**
     * 힌트 순서 변경
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'hints' => 'required|array',
            'hints.*.id' => 'required|exists:pz_hints,id',
            'hints.*.sort_order' => 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['hints'] as $hintData) {
                PzHint::where('id', $hintData['id'])
                      ->update(['sort_order' => $hintData['sort_order']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '순서가 변경되었습니다.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => '순서 변경 중 오류가 발생했습니다.'
            ], 500);
        }
    }
}
