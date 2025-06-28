<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PuzzleGridTemplateService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PuzzleGridController extends Controller
{
    protected $templateService;

    public function __construct(PuzzleGridTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * 레벨별 그리드 템플릿 목록 보기
     */
    public function index()
    {
        $templates = DB::table('puzzle_grid_templates')
            ->where('is_active', true)
            ->orderBy('level_id')
            ->get()
            ->map(function ($template) {
                // created_at을 Carbon 객체로 변환
                $template->created_at = Carbon::parse($template->created_at);
                $template->updated_at = Carbon::parse($template->updated_at);
                return $template;
            });

        return view('puzzle.grids.index', compact('templates'));
    }

    /**
     * 특정 레벨의 그리드 보기
     */
    public function show($level)
    {
        $template = $this->templateService->getTemplateByLevel($level);
        
        if (!$template) {
            return redirect()->route('puzzle.grids.index')
                ->with('error', "레벨 {$level}의 그리드 템플릿을 찾을 수 없습니다.");
        }

        // JSON 데이터 파싱
        $gridPattern = json_decode($template->grid_pattern, true);
        $wordPositions = json_decode($template->word_positions, true);
        
        // 교차점 정보 계산
        $intersections = $this->calculateIntersections($wordPositions);

        return view('puzzle.grids.show', compact('template', 'gridPattern', 'wordPositions', 'intersections'));
    }

    /**
     * 그리드 생성 페이지
     */
    public function create()
    {
        return view('puzzle.grids.create');
    }

    /**
     * 그리드 저장
     */
    public function store(Request $request)
    {
        $request->validate([
            'level' => 'required|integer|min:1',
        ]);

        try {
            $level = $request->input('level');
            
            if ($level == 1) {
                $template = $this->templateService->createLevel1Template();
                $templateId = $this->templateService->saveTemplate($template);
                
                return redirect()->route('puzzle.grids.show', $level)
                    ->with('success', "레벨 {$level} 그리드 템플릿이 성공적으로 생성되었습니다.");
            } else {
                return back()->with('error', "레벨 {$level}은 아직 지원되지 않습니다.");
            }
        } catch (\Exception $e) {
            return back()->with('error', '그리드 생성 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 교차점 정보 계산
     */
    private function calculateIntersections($wordPositions)
    {
        $intersections = [];
        
        for ($i = 0; $i < count($wordPositions); $i++) {
            for ($j = $i + 1; $j < count($wordPositions); $j++) {
                $word1 = $wordPositions[$i];
                $word2 = $wordPositions[$j];
                
                if ($word1['direction'] !== $word2['direction']) {
                    $intersection = $this->findIntersection($word1, $word2);
                    if ($intersection) {
                        $intersections[] = [
                            'position' => $intersection,
                            'word1_id' => $word1['id'],
                            'word2_id' => $word2['id'],
                            'word1_direction' => $word1['direction'],
                            'word2_direction' => $word2['direction']
                        ];
                    }
                }
            }
        }
        
        return $intersections;
    }

    /**
     * 두 단어의 교차점 찾기
     */
    private function findIntersection($word1, $word2)
    {
        if ($word1['direction'] === 'horizontal' && $word2['direction'] === 'vertical') {
            $horizontal = $word1;
            $vertical = $word2;
        } elseif ($word1['direction'] === 'vertical' && $word2['direction'] === 'horizontal') {
            $horizontal = $word2;
            $vertical = $word1;
        } else {
            return null;
        }
        
        if ($horizontal['start_y'] >= $vertical['start_y'] && 
            $horizontal['start_y'] <= $vertical['end_y'] &&
            $vertical['start_x'] >= $horizontal['start_x'] && 
            $vertical['start_x'] <= $horizontal['end_x']) {
            
            return [
                'x' => $vertical['start_x'],
                'y' => $horizontal['start_y']
            ];
        }
        
        return null;
    }
} 