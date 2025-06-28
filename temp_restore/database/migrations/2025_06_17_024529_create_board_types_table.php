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
        Schema::create('board_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 게시판 이름 (예: 공지사항, 자유게시판, Q&A)
            $table->string('slug')->unique(); // URL에 사용될 고유 식별자
            $table->text('description')->nullable(); // 게시판 설명
            $table->boolean('is_active')->default(true); // 게시판 활성화 여부
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_types');
    }
};
