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
        Schema::create('number_statistics', function (Blueprint $table) {
            $table->id();
            $table->integer('number')->comment('로또 번호 (1-45)');
            $table->integer('selection_count')->default(0)->comment('선택된 횟수');
            $table->integer('ddongsun_rankers_count')->default(0)->comment('똥손 랭커 선택 횟수');
            $table->integer('week_number')->comment('주차');
            $table->timestamps();
            
            $table->unique(['number', 'week_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('number_statistics');
    }
};
