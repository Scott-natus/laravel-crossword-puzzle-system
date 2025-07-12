<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum', ['except' => ['login', 'register']]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        
        // Laravel 기존 로그인과 동일한 비즈니스 로직 추가
        // 1. 최종 접속일 업데이트
        $user->update(['last_login_at' => now()]);
        
        // 2. 환영 메시지 설정 (세션 대신 응답에 포함)
        $welcomeMessage = $user->name . '님, 다시 오신 것을 환영합니다! 👋';
        
        // 3. 로그인 정보 기억하기 처리 (API에서는 쿠키 대신 응답에 포함)
        $rememberEmail = $request->filled('remember') ? $request->email : null;
        
        // 4. 리다이렉션 URL 처리 (API에서는 응답에 포함)
        $redirectUrl = $request->get('redirect', '/main');
        
        // 디버깅 로그 추가
        \Log::info('API Login debug', [
            'user_id' => $user->id,
            'email' => $user->email,
            'last_login_at' => $user->last_login_at,
            'welcome_message' => $welcomeMessage,
            'redirect_url' => $redirectUrl
        ]);
        
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'user' => $user,
            'welcome_message' => $welcomeMessage,
            'redirect_url' => $redirectUrl,
            'remember_email' => $rememberEmail,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }

    public function me()
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    // 사용자 통계 정보
    public function getUserStats()
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // 기존 user_puzzle_games 테이블에서 기본 통계
        $userGame = \DB::table('user_puzzle_games')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        // 새로운 game_sessions 테이블에서 상세 통계
        $gameStats = \DB::table('game_sessions')
            ->where('user_id', $user->id)
            ->selectRaw('
                COUNT(*) as total_sessions,
                SUM(total_play_time) as total_play_time,
                AVG(accuracy_rate) as average_accuracy,
                SUM(hints_used_count) as total_hints_used
            ')
            ->first();

        $stats = [
            'current_level' => $userGame ? $userGame->current_level : 1,
            'total_games' => $userGame ? $userGame->total_correct_answers + $userGame->total_wrong_answers : 0,
            'total_score' => $userGame ? $userGame->total_correct_answers : 0,
            'last_played' => $userGame ? $userGame->last_played_at : null,
            'total_play_time' => $gameStats ? (int)$gameStats->total_play_time : 0,
            'average_accuracy' => $gameStats ? round((float)$gameStats->average_accuracy, 1) : 0,
            'total_hints_used' => $gameStats ? (int)$gameStats->total_hints_used : 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    // 최근 게임 세션 목록
    public function getRecentGameSessions()
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $recentSessions = \DB::table('game_sessions as gs')
            ->join('pz_words as pw', 'gs.word_id', '=', 'pw.id')
            ->where('gs.user_id', $user->id)
            ->select([
                'gs.id',
                'gs.word_id',
                'pw.word',
                'gs.session_started_at',
                'gs.session_ended_at',
                'gs.total_play_time',
                'gs.accuracy_rate',
                'gs.total_correct_answers',
                'gs.total_wrong_answers',
                'gs.hints_used_count',
                'gs.is_completed',
                'gs.created_at'
            ])
            ->orderBy('gs.created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $recentSessions
        ]);
    }

    // 기존 최근 게임 기록 (호환성 유지)
    public function getRecentGames()
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $recentGames = \DB::table('user_puzzle_games')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->select([
                'id',
                'current_level as level',
                'total_correct_answers as score',
                'last_played_at as completed_at',
                'created_at'
            ])
            ->orderBy('last_played_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $recentGames
        ]);
    }
} 