<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LottoResultsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 최근 1년간의 로또 당첨 번호 데이터 (샘플)
        $lottoResults = [
            [
                'round_number' => 1001,
                'winning_numbers' => json_encode([1, 7, 13, 19, 25, 31]),
                'draw_date' => '2024-08-24',
            ],
            [
                'round_number' => 1000,
                'winning_numbers' => json_encode([2, 8, 14, 20, 26, 32]),
                'draw_date' => '2024-08-17',
            ],
            [
                'round_number' => 999,
                'winning_numbers' => json_encode([3, 9, 15, 21, 27, 33]),
                'draw_date' => '2024-08-10',
            ],
            [
                'round_number' => 998,
                'winning_numbers' => json_encode([4, 10, 16, 22, 28, 34]),
                'draw_date' => '2024-08-03',
            ],
            [
                'round_number' => 997,
                'winning_numbers' => json_encode([5, 11, 17, 23, 29, 35]),
                'draw_date' => '2024-07-27',
            ],
        ];

        foreach ($lottoResults as $result) {
            DB::table('lotto_results')->insert($result);
        }
    }
}
