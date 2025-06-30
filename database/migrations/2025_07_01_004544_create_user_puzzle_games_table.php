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
        Schema::create('user_puzzle_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('current_level')->default(1);
            $table->timestamp('first_attempt_at')->nullable();
            $table->integer('total_play_time')->default(0); // 초 단위
            $table->decimal('accuracy_rate', 5, 2)->default(0.00); // 정답률 (0.00 ~ 100.00)
            $table->integer('total_correct_answers')->default(0);
            $table->integer('total_wrong_answers')->default(0);
            $table->integer('current_level_correct_answers')->default(0);
            $table->integer('current_level_wrong_answers')->default(0);
            $table->integer('ranking')->default(0);
            $table->timestamp('last_played_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
            $table->index('ranking');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_puzzle_games');
    }
};
