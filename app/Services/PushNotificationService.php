<?php

namespace App\Services;

use App\Models\MobilePushToken;
use App\Models\MobilePushSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private $fcmKey;
    private $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->fcmKey = config('services.fcm.server_key');
    }

    /**
     * 일일 퍼즐 알림
     */
    public function sendDailyReminder($userId)
    {
        $settings = MobilePushSettings::where('user_id', $userId)->first();
        
        if (!$settings || !$settings->daily_reminder) {
            return false;
        }

        $tokens = $this->getUserTokens($userId);
        
        if (empty($tokens)) {
            return false;
        }

        $message = [
            'title' => '오늘의 십자낱말 퍼즐',
            'body' => '새로운 퍼즐이 기다리고 있어요!',
            'data' => [
                'type' => 'daily_reminder',
                'action' => 'open_app'
            ]
        ];

        return $this->sendToTokens($tokens, $message);
    }

    /**
     * 레벨 완료 알림
     */
    public function sendLevelComplete($userId, $levelId, $score)
    {
        $settings = MobilePushSettings::where('user_id', $userId)->first();
        
        if (!$settings || !$settings->level_complete) {
            return false;
        }

        $tokens = $this->getUserTokens($userId);
        
        if (empty($tokens)) {
            return false;
        }

        $message = [
            'title' => '레벨 완료! 🎉',
            'body' => "레벨 {$levelId}을 완료했습니다! 점수: {$score}점",
            'data' => [
                'type' => 'level_complete',
                'level_id' => $levelId,
                'score' => $score,
                'action' => 'show_result'
            ]
        ];

        return $this->sendToTokens($tokens, $message);
    }

    /**
     * 업적 달성 알림
     */
    public function sendAchievement($userId, $achievementName, $description)
    {
        $settings = MobilePushSettings::where('user_id', $userId)->first();
        
        if (!$settings || !$settings->achievement) {
            return false;
        }

        $tokens = $this->getUserTokens($userId);
        
        if (empty($tokens)) {
            return false;
        }

        $message = [
            'title' => '업적 달성! 🏆',
            'body' => $description,
            'data' => [
                'type' => 'achievement',
                'achievement_name' => $achievementName,
                'action' => 'show_achievement'
            ]
        ];

        return $this->sendToTokens($tokens, $message);
    }

    /**
     * 연속 성공 알림
     */
    public function sendStreakReminder($userId, $streakCount)
    {
        $settings = MobilePushSettings::where('user_id', $userId)->first();
        
        if (!$settings || !$settings->streak_reminder) {
            return false;
        }

        $tokens = $this->getUserTokens($userId);
        
        if (empty($tokens)) {
            return false;
        }

        $message = [
            'title' => '연속 성공 기록! 🔥',
            'body' => "{$streakCount}일 연속으로 퍼즐을 완료했습니다!",
            'data' => [
                'type' => 'streak_reminder',
                'streak_count' => $streakCount,
                'action' => 'open_app'
            ]
        ];

        return $this->sendToTokens($tokens, $message);
    }

    /**
     * 사용자 토큰 조회
     */
    private function getUserTokens($userId)
    {
        return MobilePushToken::where('user_id', $userId)
            ->where('is_active', true)
            ->pluck('device_token')
            ->toArray();
    }

    /**
     * FCM으로 푸시 전송
     */
    private function sendToTokens($tokens, $message)
    {
        if (empty($tokens)) {
            return false;
        }

        $payload = [
            'registration_ids' => $tokens,
            'notification' => [
                'title' => $message['title'],
                'body' => $message['body'],
                'sound' => 'default',
                'badge' => 1,
            ],
            'data' => $message['data'],
            'priority' => 'high',
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            Log::info('FCM Response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'tokens_count' => count($tokens)
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('FCM Error', [
                'message' => $e->getMessage(),
                'tokens_count' => count($tokens)
            ]);
            return false;
        }
    }
} 