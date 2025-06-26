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
        Schema::create('PZ_hints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('word_id')->constrained('PZ_words')->comment('PZ_words 테이블의 id 참조');
            $table->text('hint_text')->comment('실제 퍼즐에 보여질 힌트');
            $table->string('hint_type', 20)->default('TEXT')->comment('힌트 유형');
            $table->string('image_url', 255)->nullable()->comment('이미지 힌트일 경우 이미지 URL');
            $table->string('audio_url', 255)->nullable()->comment('오디오 힌트일 경우 오디오 URL');
            $table->boolean('is_primary')->default(false)->comment('이 힌트가 주 힌트인지 여부');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('PZ_hints');
    }
};
