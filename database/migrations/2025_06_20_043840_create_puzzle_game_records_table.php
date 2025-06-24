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
        Schema::create('puzzle_game_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('level_played'); // 플레이한 레벨
            $table->string('game_status'); // completed, failed, abandoned
            $table->integer('score')->default(0); // 획득 점수
            $table->integer('play_time')->default(0); // 플레이 시간 (초)
            $table->integer('hints_used')->default(0); // 사용한 힌트 수
            $table->integer('words_found')->default(0); // 찾은 단어 수
            $table->integer('total_words')->default(0); // 총 단어 수
            $table->decimal('accuracy', 5, 2)->default(0); // 정확도 (%)
            $table->integer('level_before')->nullable(); // 플레이 전 레벨
            $table->integer('level_after')->nullable(); // 플레이 후 레벨
            $table->boolean('level_up')->default(false); // 레벨업 여부
            $table->json('game_data')->nullable(); // 게임 상세 데이터 (JSON)
            $table->text('notes')->nullable(); // 관리자 메모
            $table->timestamps();
            
            // 인덱스
            $table->index(['user_id', 'level_played']);
            $table->index(['user_id', 'created_at']);
            $table->index('game_status');
            $table->index('level_up');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puzzle_game_records');
    }
};
