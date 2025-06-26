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
        Schema::create('user_puzzle_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('current_level')->default(1); // 현재 퍼즐 레벨 (1-100)
            $table->integer('total_score')->default(0); // 총 점수
            $table->integer('games_played')->default(0); // 총 게임 수
            $table->integer('games_completed')->default(0); // 완료한 게임 수
            $table->integer('games_failed')->default(0); // 실패한 게임 수
            $table->integer('current_streak')->default(0); // 현재 연속 성공 횟수
            $table->integer('best_streak')->default(0); // 최고 연속 성공 횟수
            $table->integer('total_play_time')->default(0); // 총 플레이 시간 (초)
            $table->timestamp('last_played_at')->nullable(); // 마지막 플레이 시간
            $table->timestamp('first_played_at')->nullable(); // 첫 플레이 시간
            $table->boolean('is_active')->default(true); // 퍼즐 게임 활성화 여부
            $table->json('preferences')->nullable(); // 사용자 설정 (JSON)
            $table->timestamps();
            
            // 인덱스
            $table->index(['user_id', 'current_level']);
            $table->index('total_score');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_puzzle_profiles');
    }
};
