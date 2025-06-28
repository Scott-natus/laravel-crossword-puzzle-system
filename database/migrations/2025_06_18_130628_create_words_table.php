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
        Schema::create('PZ_words', function (Blueprint $table) {
            $table->id();
            $table->string('word', 255)->unique()->comment('실제 퍼즐에 들어갈 단어');
            $table->integer('length')->comment('단어 길이');
            $table->string('category', 50)->nullable()->comment('단어 카테고리');
            $table->integer('difficulty')->comment('단어 난이도 (1~5 등급)');
            $table->boolean('is_active')->default(true)->comment('퍼즐에 사용 가능 여부');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('PZ_words');
    }
};
