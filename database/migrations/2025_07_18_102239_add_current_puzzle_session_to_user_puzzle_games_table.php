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
        Schema::table('user_puzzle_games', function (Blueprint $table) {
            // 현재 퍼즐 세션 정보 저장
            $table->json('current_puzzle_data')->nullable()->after('guest_id'); // 현재 퍼즐의 템플릿, 단어, 그리드 정보
            $table->json('current_game_state')->nullable()->after('current_puzzle_data'); // 현재 게임 상태 (정답/오답, 힌트 사용 등)
            $table->timestamp('current_puzzle_started_at')->nullable()->after('current_game_state'); // 현재 퍼즐 시작 시간
            $table->boolean('has_active_puzzle')->default(false)->after('current_puzzle_started_at'); // 활성 퍼즐 존재 여부
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_puzzle_games', function (Blueprint $table) {
            $table->dropColumn([
                'current_puzzle_data',
                'current_game_state', 
                'current_puzzle_started_at',
                'has_active_puzzle'
            ]);
        });
    }
};
