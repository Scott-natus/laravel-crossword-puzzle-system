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
        Schema::create('puzzle_grid_templates', function (Blueprint $table) {
            $table->id();
            $table->integer('level_id'); // 레벨 ID
            $table->string('template_name'); // 템플릿 이름
            $table->json('grid_pattern'); // 그리드 패턴 (2D 배열)
            $table->json('word_positions'); // 단어 위치 정보
            $table->integer('grid_width'); // 그리드 너비
            $table->integer('grid_height'); // 그리드 높이
            $table->integer('difficulty_rating'); // 난이도 등급 (1-5)
            $table->integer('word_count'); // 단어 수
            $table->integer('intersection_count'); // 교차점 수
            $table->string('category')->default('general'); // 카테고리
            $table->text('description')->nullable(); // 설명
            $table->boolean('is_active')->default(true); // 활성화 여부
            $table->timestamps();
            
            $table->index('level_id');
            $table->index('difficulty_rating');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puzzle_grid_templates');
    }
}; 