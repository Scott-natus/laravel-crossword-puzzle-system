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
        Schema::table('user_puzzle_games', function (Blueprint $table) {
            $table->uuid('guest_id')->nullable()->index()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_puzzle_games', function (Blueprint $table) {
            $table->dropColumn('guest_id');
        });
    }
};
