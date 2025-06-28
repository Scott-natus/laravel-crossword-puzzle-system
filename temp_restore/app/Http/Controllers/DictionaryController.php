<?php

namespace App\Http\Controllers;

use App\Services\DictionaryService;
use Illuminate\Http\Request;

class DictionaryController extends Controller
{
    private $dictionaryService;

    public function __construct(DictionaryService $dictionaryService)
    {
        $this->dictionaryService = $dictionaryService;
    }

    public function getDefinition(Request $request)
    {
        $word = $request->input('word');
        
        if (empty($word)) {
            return response()->json([
                'success' => false,
                'message' => '단어를 입력해주세요.'
            ], 400);
        }

        $definition = $this->dictionaryService->getDefinition($word);

        if ($definition === null) {
            return response()->json([
                'success' => false,
                'message' => '단어를 찾을 수 없습니다.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $definition
        ]);
    }
} 