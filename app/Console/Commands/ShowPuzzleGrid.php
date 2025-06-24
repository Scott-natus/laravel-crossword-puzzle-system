<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PuzzleGridTemplateService;
use Illuminate\Support\Facades\DB;

class ShowPuzzleGrid extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'puzzle:show-grid {level=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '저장된 퍼즐 그리드를 시각적으로 보여줍니다.';

    /**
     * Execute the console command.
     */
    public function handle(PuzzleGridTemplateService $templateService)
    {
        $level = $this->argument('level');
        
        $this->info("🔍 레벨 {$level} 그리드 조회 중...");
        
        try {
            // 데이터베이스에서 템플릿 조회
            $template = $templateService->getTemplateByLevel($level);
            
            if (!$template) {
                $this->error("❌ 레벨 {$level}의 그리드 템플릿을 찾을 수 없습니다.");
                return 1;
            }
            
            $this->info("📋 템플릿 정보:");
            $this->line("   - ID: {$template->id}");
            $this->line("   - 이름: {$template->template_name}");
            $this->line("   - 크기: {$template->grid_width}x{$template->grid_height}");
            $this->line("   - 단어 수: {$template->word_count}");
            $this->line("   - 교차점 수: {$template->intersection_count}");
            $this->line("   - 난이도: {$template->difficulty_rating}");
            $this->line("   - 카테고리: {$template->category}");
            
            // 그리드 패턴 파싱 및 시각화
            $gridPattern = json_decode($template->grid_pattern, true);
            $this->info("\n🔲 그리드 패턴:");
            $this->line($templateService->visualizeGrid($gridPattern));
            
            // 단어 위치 정보 파싱 및 시각화
            $wordPositions = json_decode($template->word_positions, true);
            $this->info("📝 단어 위치 정보:");
            $this->line($templateService->visualizeWordPositions($wordPositions));
            
            // 교차점 정보 표시
            $this->info("🔗 교차점 정보:");
            $this->showIntersections($wordPositions);
            
            // 그리드 번호 표시
            $this->info("\n🔢 그리드 번호 표시:");
            $this->showGridWithNumbers($gridPattern, $wordPositions);
            
        } catch (\Exception $e) {
            $this->error("❌ 그리드 조회 실패: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    /**
     * 교차점 정보 표시
     */
    private function showIntersections($wordPositions)
    {
        $intersections = [];
        
        // 모든 단어 쌍을 비교하여 교차점 찾기
        for ($i = 0; $i < count($wordPositions); $i++) {
            for ($j = $i + 1; $j < count($wordPositions); $j++) {
                $word1 = $wordPositions[$i];
                $word2 = $wordPositions[$j];
                
                // 가로-세로 교차 확인
                if ($word1['direction'] !== $word2['direction']) {
                    $intersection = $this->findIntersection($word1, $word2);
                    if ($intersection) {
                        $intersections[] = [
                            'position' => $intersection,
                            'word1_id' => $word1['id'],
                            'word2_id' => $word2['id']
                        ];
                    }
                }
            }
        }
        
        foreach ($intersections as $intersection) {
            $this->line("   교차점: ({$intersection['position']['x']}, {$intersection['position']['y']}) - 단어 {$intersection['word1_id']}와 단어 {$intersection['word2_id']}");
        }
    }
    
    /**
     * 두 단어의 교차점 찾기
     */
    private function findIntersection($word1, $word2)
    {
        // 가로 단어와 세로 단어의 교차점 찾기
        if ($word1['direction'] === 'horizontal' && $word2['direction'] === 'vertical') {
            $horizontal = $word1;
            $vertical = $word2;
        } elseif ($word1['direction'] === 'vertical' && $word2['direction'] === 'horizontal') {
            $horizontal = $word2;
            $vertical = $word1;
        } else {
            return null;
        }
        
        // 가로 단어의 y좌표가 세로 단어 범위에 있고, 세로 단어의 x좌표가 가로 단어 범위에 있는지 확인
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
    
    /**
     * 번호가 표시된 그리드 출력
     */
    private function showGridWithNumbers($gridPattern, $wordPositions)
    {
        $width = count($gridPattern[0]);
        $height = count($gridPattern);
        
        // 번호 매핑 생성
        $numberMap = [];
        foreach ($wordPositions as $pos) {
            $numberMap[$pos['start_x']][$pos['start_y']] = $pos['clue_number'];
        }
        
        // 그리드 출력
        for ($y = 0; $y < $height; $y++) {
            $row = "";
            for ($x = 0; $x < $width; $x++) {
                if ($gridPattern[$y][$x] == 1) {
                    // 번호가 있으면 번호 표시, 없으면 빈칸
                    $number = isset($numberMap[$x][$y]) ? $numberMap[$x][$y] : " ";
                    $row .= sprintf("%2s", $number);
                } else {
                    $row .= " ■";
                }
            }
            $this->line($row);
        }
    }
} 