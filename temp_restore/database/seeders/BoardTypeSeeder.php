<?php

namespace Database\Seeders;

use App\Models\BoardType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BoardTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $boardTypes = [
            [
                'name' => '주저리',
                'slug' => 'bbs1',
                'description' => '주저리 게시판입니다.',
                'is_active' => true,
            ],
            [
                'name' => '자유게시판',
                'slug' => 'talk',
                'description' => '자유롭게 이야기를 나누세요.',
                'is_active' => true,
            ],
            [
                'name' => '자료정리',
                'slug' => 'lab',
                'description' => '자료를 정리하는 게시판입니다.',
                'is_active' => true,
            ],
        ];

        foreach ($boardTypes as $boardType) {
            BoardType::create($boardType);
        }
    }
}
