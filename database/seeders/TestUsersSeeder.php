<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 테스트 사용자 생성
        $users = [
            [
                'name' => '테스트 사용자 1',
                'email' => 'test1@ddongsun.com',
                'password' => Hash::make('123456'),
                'total_ddongsun_power' => 150,
                'current_level' => '실버',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '테스트 사용자 2',
                'email' => 'test2@ddongsun.com',
                'password' => Hash::make('123456'),
                'total_ddongsun_power' => 300,
                'current_level' => '골드',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '테스트 사용자 3',
                'email' => 'test3@ddongsun.com',
                'password' => Hash::make('123456'),
                'total_ddongsun_power' => 500,
                'current_level' => '플래티넘',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->insert($user);
        }
    }
}
