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
        Schema::create('mobile_push_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('device_token')->comment('FCM 디바이스 토큰');
            $table->string('platform', 10)->comment('플랫폼 (ios, android)');
            $table->string('app_version', 20)->nullable()->comment('앱 버전');
            $table->boolean('is_active')->default(true)->comment('활성화 여부');
            $table->timestamps();
            
            // 인덱스
            $table->index(['user_id', 'is_active']);
            $table->index('device_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_push_tokens');
    }
};
