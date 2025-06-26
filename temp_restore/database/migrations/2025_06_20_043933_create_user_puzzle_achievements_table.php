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
        Schema::create('user_puzzle_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('achievement_type'); // level_up, streak, score, accuracy, etc.
            $table->string('achievement_name'); // 업적 이름
            $table->text('description')->nullable(); // 업적 설명
            $table->integer('value_achieved'); // 달성한 값
            $table->integer('required_value'); // 필요 값
            $table->integer('bonus_score')->default(0); // 보너스 점수
            $table->json('metadata')->nullable(); // 추가 메타데이터
            $table->timestamp('achieved_at'); // 달성 시간
            $table->timestamps();
            
            // 인덱스
            $table->index(['user_id', 'achievement_type']);
            $table->index('achieved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_puzzle_achievements');
    }
};
