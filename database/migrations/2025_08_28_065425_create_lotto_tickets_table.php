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
        Schema::create('lotto_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('사용자 ID');
            $table->string('image_path')->comment('업로드된 이미지 경로');
            $table->json('numbers')->comment('선택된 번호들');
            $table->integer('ddongsun_power')->comment('해당 용지의 똥손력');
            $table->date('upload_date')->comment('업로드 날짜');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotto_tickets');
    }
};
