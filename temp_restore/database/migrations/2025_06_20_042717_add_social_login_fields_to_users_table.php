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
        Schema::table('users', function (Blueprint $table) {
            $table->string('provider')->nullable(); // google, kakao, naver
            $table->string('provider_id')->nullable(); // SNS에서 제공하는 고유 ID
            $table->string('avatar')->nullable(); // 프로필 이미지 URL
            $table->string('nickname')->nullable(); // SNS에서 제공하는 닉네임
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['provider', 'provider_id', 'avatar', 'nickname']);
        });
    }
};
