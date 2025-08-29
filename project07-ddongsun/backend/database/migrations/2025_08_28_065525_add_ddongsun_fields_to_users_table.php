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
            $table->integer('total_ddongsun_power')->default(0)->comment('누적 똥손력');
            $table->string('current_level')->default('브론즈')->comment('똥손 레벨');
            $table->string('profile_image')->nullable()->comment('프로필 이미지');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['total_ddongsun_power', 'current_level', 'profile_image']);
        });
    }
};
