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
        Schema::create('lotto_results', function (Blueprint $table) {
            $table->id();
            $table->integer('round_number')->unique()->comment('회차');
            $table->json('winning_numbers')->comment('당첨 번호들');
            $table->date('draw_date')->comment('추첨 날짜');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotto_results');
    }
};
