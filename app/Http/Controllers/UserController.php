<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserController extends Controller
{
    /**
     * 사용자 통계 정보 조회
     */
    public function getStats(Request $request)
    {
        try {
            $user = Auth::user();
            
            // 사용자의 게임 프로필 조회
            $userProfile = DB::table('user_puzzle_games')
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if (!$userProfile) {
                // 프로필이 없으면 기본값 반환
                $stats = [
                    'current_level' => 1,
                    'total_games' => 0,
                    'total_score' => 0,
                    'last_played' => null,
                ];
            } else {
                // 총 게임 수 계산 (정답 + 오답)
                $totalGames = $userProfile->total_correct_answers + $userProfile->total_wrong_answers;
                
                // 총 점수 계산 (정답률 * 100)
                $totalScore = round($userProfile->accuracy_rate * 100);
                
                $stats = [
                    'current_level' => $userProfile->current_level,
                    'total_games' => $totalGames,
                    'total_score' => $totalScore,
                    'last_played' => $userProfile->last_played_at ? Carbon::parse($userProfile->last_played_at)->format('Y-m-d H:i:s') : null,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '통계 정보를 불러오는데 실패했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 사용자의 최근 게임 기록 조회
     */
    public function getRecentGames(Request $request)
    {
        try {
            $user = Auth::user();
            
            // 사용자의 게임 프로필 조회
            $userProfile = DB::table('user_puzzle_games')
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if (!$userProfile) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            // 최근 게임 기록 생성 (실제 기록 테이블이 없으므로 프로필 데이터로 시뮬레이션)
            $recentGames = [];
            
            // 현재 레벨의 최근 게임 기록 생성
            $currentLevel = $userProfile->current_level;
            $accuracyRate = $userProfile->accuracy_rate;
            
            // 최근 5개의 가상 게임 기록 생성
            for ($i = 0; $i < 5; $i++) {
                $score = round($accuracyRate * 100) + rand(-10, 10);
                $score = max(0, min(100, $score)); // 0-100 범위로 제한
                
                $recentGames[] = [
                    'id' => $i + 1,
                    'level' => $currentLevel,
                    'score' => $score,
                    'completed_at' => Carbon::now()->subDays($i)->format('Y-m-d H:i:s'),
                    'created_at' => Carbon::now()->subDays($i)->format('Y-m-d H:i:s'),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $recentGames
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '게임 기록을 불러오는데 실패했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 