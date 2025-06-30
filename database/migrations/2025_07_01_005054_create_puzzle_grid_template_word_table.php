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
        Schema::create('puzzle_grid_template_word', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('word_id');
            $table->json('word_positions')->nullable();
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('puzzle_grid_templates')->onDelete('cascade');
            $table->foreign('word_id')->references('id')->on('pz_words')->onDelete('cascade');
            
            $table->unique(['template_id', 'word_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puzzle_grid_template_word');
    }
};
