<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PuzzleGridTemplateService;

class CreatePuzzleGridTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'puzzle:create-grid-template {level=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '레벨에 맞는 퍼즐 그리드 템플릿을 생성합니다.';

    /**
     * Execute the console command.
     */
    public function handle(PuzzleGridTemplateService $templateService)
    {
        $level = $this->argument('level');
        
        $this->info("🎯 레벨 {$level} 그리드 템플릿 생성 시작...");
        
        try {
            // 레벨1 템플릿 생성
            if ($level == 1) {
                $template = $templateService->createLevel1Template();
                
                $this->info("📋 템플릿 정보:");
                $this->line("   - 이름: {$template['template_name']}");
                $this->line("   - 크기: {$template['grid_width']}x{$template['grid_height']}");
                $this->line("   - 단어 수: {$template['word_count']}");
                $this->line("   - 교차점 수: {$template['intersection_count']}");
                $this->line("   - 난이도: {$template['difficulty_rating']}");
                
                // 그리드 패턴 시각화
                $this->info("\n🔲 그리드 패턴:");
                $this->line($templateService->visualizeGrid($template['grid_pattern']));
                
                // 단어 위치 정보 시각화
                $this->info("📝 단어 위치 정보:");
                $this->line($templateService->visualizeWordPositions($template['word_positions']));
                
                // 데이터베이스에 저장
                $this->info("\n💾 데이터베이스에 저장 중...");
                $templateId = $templateService->saveTemplate($template);
                
                $this->info("✅ 그리드 템플릿 생성 완료!");
                $this->line("   - 템플릿 ID: {$templateId}");
                $this->line("   - 레벨: {$level}");
                
            } else {
                $this->error("❌ 레벨 {$level}은 아직 지원되지 않습니다. 현재는 레벨 1만 지원합니다.");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ 그리드 템플릿 생성 실패: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
} 