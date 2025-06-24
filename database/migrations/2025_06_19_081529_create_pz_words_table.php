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
        Schema::create('pz_words', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50)->comment('카테고리');
            $table->string('word', 50)->comment('단어');
            $table->integer('length')->comment('글자수');
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium')->comment('난이도');
            $table->boolean('is_active')->default(true)->comment('사용여부');
            $table->timestamps();
            
            // 인덱스
            $table->index(['category', 'word']);
            $table->index('is_active');
            $table->index('difficulty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pz_words');
    }
};
