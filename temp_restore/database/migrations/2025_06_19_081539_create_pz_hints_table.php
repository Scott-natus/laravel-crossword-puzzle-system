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
        Schema::create('pz_hints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('word_id')->constrained('pz_words')->onDelete('cascade')->comment('단어 ID');
            $table->text('content')->comment('힌트 내용');
            $table->enum('type', ['text', 'image', 'sound'])->default('text')->comment('힌트 타입');
            $table->string('file_path')->nullable()->comment('파일 경로 (이미지/사운드)');
            $table->string('original_name')->nullable()->comment('원본 파일명');
            $table->boolean('is_primary')->default(false)->comment('주요 힌트 여부');
            $table->integer('sort_order')->default(0)->comment('정렬 순서');
            $table->timestamps();
            
            // 인덱스
            $table->index('word_id');
            $table->index('is_primary');
            $table->index('type');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pz_hints');
    }
};
