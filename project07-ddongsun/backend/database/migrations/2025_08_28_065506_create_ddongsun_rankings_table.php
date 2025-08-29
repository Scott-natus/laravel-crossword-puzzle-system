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
        Schema::create('ddongsun_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('사용자 ID');
            $table->integer('week_number')->comment('주차');
            $table->integer('ddongsun_power')->comment('해당 주의 똥손력');
            $table->integer('rank')->comment('순위');
            $table->timestamps();
            
            $table->unique(['user_id', 'week_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ddongsun_rankings');
    }
};
