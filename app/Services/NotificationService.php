<?php

namespace App\Services;

use App\Models\Board;
use App\Models\BoardComment;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * 댓글 알림 처리
     */
    public function handleCommentNotification(BoardComment $comment)
    {
        $board = $comment->board;
        
        // 게시글 작성자가 댓글 알림을 받도록 설정했는지 확인
        if (!$board->comment_notify) {
            return;
        }

        $boardAuthor = $board->user;
        $commentAuthor = $comment->user;

        // 자신의 글에 자신이 댓글을 달면 알림을 보내지 않음
        if ($boardAuthor->id === $commentAuthor->id) {
            return;
        }

        // 게시글 작성자의 알림 설정 확인
        $settings = $boardAuthor->notificationSettings;

        if (!$settings) {
            return;
        }

        // 이메일 알림
        if ($settings->email_notify && $settings->email) {
            $this->sendEmailNotification($boardAuthor, $board, $comment);
        }

        // 앱 알림
        if ($settings->app_notify && $settings->device_token) {
            $this->sendAppNotification($boardAuthor, $board, $comment);
        }
    }

    /**
     * 이메일 알림 전송
     */
    protected function sendEmailNotification(User $user, Board $board, BoardComment $comment)
    {
        try {
            Mail::send('emails.comment-notification', [
                'user' => $user,
                'board' => $board,
                'comment' => $comment
            ], function ($message) use ($user, $board) {
                $message->to($user->email)
                    ->subject('새로운 댓글이 달렸습니다: ' . $board->title);
            });
        } catch (\Exception $e) {
            Log::error('이메일 알림 전송 실패: ' . $e->getMessage());
        }
    }

    /**
     * 앱 알림 전송
     */
    protected function sendAppNotification(User $user, Board $board, BoardComment $comment)
    {
        try {
            // Firebase Cloud Messaging을 사용한 푸시 알림 전송
            $fcm = new \Kreait\Firebase\Factory();
            $messaging = $fcm->createMessaging();

            $message = [
                'token' => $user->notificationSettings->device_token,
                'notification' => [
                    'title' => '새로운 댓글이 달렸습니다',
                    'body' => $board->title . ' 게시글에 새로운 댓글이 달렸습니다.'
                ],
                'data' => [
                    'board_id' => $board->id,
                    'comment_id' => $comment->id
                ]
            ];

            $messaging->send($message);
        } catch (\Exception $e) {
            Log::error('앱 알림 전송 실패: ' . $e->getMessage());
        }
    }
} 