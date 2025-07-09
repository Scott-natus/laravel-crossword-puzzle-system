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
        Schema::create('user_wrong_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('사용자 ID');
            $table->foreignId('word_id')->constrained('pz_words')->onDelete('cascade')->comment('단어 ID');
            $table->text('user_answer')->comment('사용자가 입력한 답변');
            $table->text('correct_answer')->comment('정답');
            $table->string('category', 50)->comment('단어 카테고리');
            $table->integer('level')->comment('게임 레벨');
            $table->timestamp('created_at')->useCurrent();
            
            // 인덱스
            $table->index(['user_id', 'word_id']);
            $table->index(['word_id']);
            $table->index(['category']);
            $table->index('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_wrong_answers');
    }
};
