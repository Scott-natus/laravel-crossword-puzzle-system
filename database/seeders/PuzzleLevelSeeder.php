<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PuzzleLevel;

class PuzzleLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1부터 100까지의 레벨 데이터 생성
        for ($i = 1; $i <= 100; $i++) {
            PuzzleLevel::create([
                'level' => $i,
                'level_name' => 'Level ' . $i,
                'word_count' => 10, // 기본값
                'word_difficulty' => 1, // 기본값
                'hint_difficulty' => 1, // 기본값
                'intersection_count' => 2, // 기본값
                'time_limit' => 300, // 기본값 (5분)
                'updated_by' => 1, // 기본 관리자 ID
            ]);
        }
    }
} 