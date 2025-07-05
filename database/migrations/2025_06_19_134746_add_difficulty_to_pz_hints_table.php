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
        Schema::table('pz_hints', function (Blueprint $table) {
            $table->integer('difficulty')->default(2)->after('type')->comment('힌트 난이도 (1: 쉬움, 2: 보통, 3: 어려움)');
            $table->index('difficulty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pz_hints', function (Blueprint $table) {
            $table->dropIndex(['difficulty']);
            $table->dropColumn('difficulty');
        });
    }
};
