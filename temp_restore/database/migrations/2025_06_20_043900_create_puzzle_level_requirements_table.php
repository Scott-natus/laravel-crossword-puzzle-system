<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('puzzle_level_requirements', function (Blueprint $table) {
            $table->id();
            $table->integer('from_level'); // 현재 레벨
            $table->integer('to_level'); // 목표 레벨
            $table->integer('required_score')->default(0); // 필요 점수
            $table->integer('required_games')->default(1); // 필요 게임 수
            $table->integer('required_wins')->default(1); // 필요 승리 수
            $table->decimal('required_accuracy', 5, 2)->default(0); // 필요 정확도 (%)
            $table->integer('required_streak')->default(0); // 필요 연속 성공
            $table->integer('max_play_time')->nullable(); // 최대 플레이 시간 (초)
            $table->integer('max_hints_used')->nullable(); // 최대 힌트 사용 수
            $table->boolean('is_active')->default(true); // 활성화 여부
            $table->text('description')->nullable(); // 조건 설명
            $table->timestamps();
            
            // 인덱스
            $table->index(['from_level', 'to_level']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puzzle_level_requirements');
    }
};
