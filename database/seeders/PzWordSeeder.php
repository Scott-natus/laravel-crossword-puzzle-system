<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PzWord;
use App\Models\PzHint;

class PzWordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 100; $i++) {
            for ($j = 1; $j <= 10; $j++) { // 각 레벨당 10개의 단어 생성
                $word = PzWord::create([
                    'category' => '레벨' . $i,
                    'word' => '단어' . $j . '-' . $i,
                    'length' => strlen('단어' . $j . '-' . $i),
                    'difficulty' => $i <= 33 ? 1 : ($i <= 66 ? 2 : 3), // 1: easy, 2: medium, 3: hard
                    'is_active' => true,
                ]);

                PzHint::create([
                    'word_id' => $word->id,
                    'content' => '힌트' . $j . '-' . $i,
                ]);
            }
        }
    }
}
