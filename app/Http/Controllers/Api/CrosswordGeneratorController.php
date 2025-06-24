<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CrosswordGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CrosswordGeneratorController extends Controller
{
    protected $crosswordGenerator;
    
    public function __construct(CrosswordGeneratorService $crosswordGenerator)
    {
        $this->crosswordGenerator = $crosswordGenerator;
    }
    
    /**
     * 레벨별 크로스워드 퍼즐 생성
     */
    public function generate(Request $request)
    {
        $request->validate([
            'level' => 'required|integer|min:1|max:100',
        ]);
        
        $level = $request->input('level');
        
        try {
            Log::info("크로스워드 퍼즐 생성 시작", ['level' => $level]);
            
            $puzzle = $this->crosswordGenerator->generateCrossword($level);
            
            Log::info("크로스워드 퍼즐 생성 완료", [
                'level' => $level,
                'word_count' => $puzzle['stats']['word_count'],
                'intersection_count' => $puzzle['stats']['intersection_count'],
                'grid_size' => $puzzle['stats']['grid_size']
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $puzzle
            ]);
            
        } catch (\Exception $e) {
            Log::error("크로스워드 퍼즐 생성 실패", [
                'level' => $level,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 크로스워드 퍼즐 미리보기 (테스트용)
     */
    public function preview(Request $request)
    {
        $request->validate([
            'level' => 'required|integer|min:1|max:100',
        ]);
        
        $level = $request->input('level');
        
        try {
            $puzzle = $this->crosswordGenerator->generateCrossword($level);
            
            // 그리드 시각화를 위한 HTML 생성
            $html = $this->generateGridHtml($puzzle['grid']);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'puzzle' => $puzzle,
                    'grid_html' => $html
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 그리드 HTML 생성 (미리보기용)
     */
    private function generateGridHtml(array $grid): string
    {
        $html = '<div style="display: inline-block; border: 2px solid #333; background: #fff;">';
        
        foreach ($grid as $y => $row) {
            $html .= '<div style="display: flex;">';
            foreach ($row as $x => $cell) {
                $backgroundColor = $cell === '' ? '#f0f0f0' : '#fff';
                $border = '1px solid #ccc';
                
                $html .= sprintf(
                    '<div style="width: 30px; height: 30px; border: %s; background: %s; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px;">%s</div>',
                    $border,
                    $backgroundColor,
                    htmlspecialchars($cell)
                );
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * 크로스워드 통계 정보
     */
    public function stats(Request $request)
    {
        $request->validate([
            'level' => 'required|integer|min:1|max:100',
        ]);
        
        $level = $request->input('level');
        
        try {
            // 레벨 정보 조회
            $levelInfo = \App\Models\PuzzleLevel::where('level', $level)->first();
            
            if (!$levelInfo) {
                return response()->json([
                    'success' => false,
                    'message' => '레벨 정보를 찾을 수 없습니다.'
                ], 404);
            }
            
            // 사용 가능한 단어 수 조회
            $availableWords = \App\Models\PzWord::active()
                ->where('difficulty', $levelInfo->word_difficulty)
                ->where('is_active', true)
                ->count();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'level' => $levelInfo->toArray(),
                    'available_words' => $availableWords,
                    'estimated_combinations' => $this->calculateCombinationCount($availableWords, $levelInfo->word_count)
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 조합 개수 계산 (nCr)
     */
    private function calculateCombinationCount(int $n, int $r): int
    {
        if ($r > $n) return 0;
        if ($r === 0 || $r === $n) return 1;
        
        $r = min($r, $n - $r);
        $result = 1;
        
        for ($i = 0; $i < $r; $i++) {
            $result = $result * ($n - $i) / ($i + 1);
        }
        
        return (int) $result;
    }
} 