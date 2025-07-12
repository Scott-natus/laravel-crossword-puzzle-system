<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MobilePushToken;
use App\Models\MobilePushSettings;
use Illuminate\Support\Facades\Log;

class MobilePushController extends Controller
{
    /**
     * 푸시 토큰 등록
     */
    public function registerToken(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
            'platform' => 'required|in:ios,android',
            'app_version' => 'nullable|string',
        ]);

        $userId = auth()->id();
        
        // 기존 토큰 비활성화
        MobilePushToken::where('user_id', $userId)
            ->where('device_token', $request->device_token)
            ->update(['is_active' => false]);

        // 새 토큰 등록
        MobilePushToken::create([
            'user_id' => $userId,
            'device_token' => $request->device_token,
            'platform' => $request->platform,
            'app_version' => $request->app_version,
            'is_active' => true,
        ]);

        // 기본 푸시 설정 생성 (없는 경우)
        MobilePushSettings::firstOrCreate([
            'user_id' => $userId,
        ]);

        Log::info('푸시 토큰 등록', [
            'user_id' => $userId,
            'platform' => $request->platform,
            'app_version' => $request->app_version,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * 푸시 토큰 삭제
     */
    public function unregisterToken(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
        ]);

        MobilePushToken::where('user_id', auth()->id())
            ->where('device_token', $request->device_token)
            ->update(['is_active' => false]);

        Log::info('푸시 토큰 삭제', [
            'user_id' => auth()->id(),
            'device_token' => $request->device_token,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * 푸시 설정 조회
     */
    public function getSettings()
    {
        $settings = MobilePushSettings::where('user_id', auth()->id())->first();
        
        if (!$settings) {
            // 기본 설정 반환
            return response()->json([
                'daily_reminder' => true,
                'level_complete' => true,
                'achievement' => true,
                'streak_reminder' => true,
            ]);
        }

        return response()->json($settings);
    }

    /**
     * 푸시 설정 업데이트
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'daily_reminder' => 'boolean',
            'level_complete' => 'boolean',
            'achievement' => 'boolean',
            'streak_reminder' => 'boolean',
        ]);

        MobilePushSettings::updateOrCreate(
            ['user_id' => auth()->id()],
            $request->only([
                'daily_reminder',
                'level_complete', 
                'achievement',
                'streak_reminder'
            ])
        );

        Log::info('푸시 설정 업데이트', [
            'user_id' => auth()->id(),
            'settings' => $request->all(),
        ]);

        return response()->json(['success' => true]);
    }
}
