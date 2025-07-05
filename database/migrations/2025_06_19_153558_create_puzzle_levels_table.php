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
        Schema::create('puzzle_levels', function (Blueprint $table) {
            $table->id();
            $table->integer('level')->unique(); // 레벨 (1-100)
            $table->string('level_name'); // 레벨 명칭
            $table->integer('word_count'); // 퍼즐(단어)의 개수
            $table->integer('word_difficulty'); // 단어의 난이도 (1-5)
            $table->integer('hint_difficulty'); // 힌트의 난이도 (1: 쉬움, 2: 보통, 3: 어려움)
            $table->integer('intersection_count'); // 퍼즐내 교차점의 개수
            $table->integer('time_limit'); // 실행시간 (초)
            $table->timestamps();
            $table->string('updated_by')->nullable(); // 수정자
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puzzle_levels');
    }
};
