<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('board_comments', function (Blueprint $table) {
            $table->foreignId('board_type_id')->after('board_id')->nullable();
        });

        // 기존 댓글들의 board_type_id를 해당 게시글의 board_type_id로 설정
        DB::statement('UPDATE board_comments SET board_type_id = boards.board_type_id FROM boards WHERE board_comments.board_id = boards.id');

        Schema::table('board_comments', function (Blueprint $table) {
            $table->foreignId('board_type_id')->nullable(false)->change();
            $table->foreign('board_type_id')->references('id')->on('board_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('board_comments', function (Blueprint $table) {
            $table->dropForeign(['board_type_id']);
            $table->dropColumn('board_type_id');
        });
    }
}; 