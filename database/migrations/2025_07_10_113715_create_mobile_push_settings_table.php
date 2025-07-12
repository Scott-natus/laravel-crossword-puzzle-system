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
        Schema::create('mobile_push_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('daily_reminder')->default(true)->comment('일일 퍼즐 알림');
            $table->boolean('level_complete')->default(true)->comment('레벨 완료 알림');
            $table->boolean('achievement')->default(true)->comment('업적 달성 알림');
            $table->boolean('streak_reminder')->default(true)->comment('연속 성공 알림');
            $table->timestamps();
            
            // 인덱스
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_push_settings');
    }
};
