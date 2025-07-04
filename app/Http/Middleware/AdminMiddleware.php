<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 로그인 확인
        if (!Auth::check()) {
            // 리다이렉션 URL을 세션에 저장하고 로그인 페이지로 이동
            session(['redirect_url' => $request->url()]);
            return redirect()->route('login')->with('error', '로그인이 필요합니다.');
        }

        $user = Auth::user();

        // 관리자 권한 확인 (rainynux@gmail.com 또는 is_admin = true)
        if (!$user->isSpecificAdmin('rainynux@gmail.com') && !$user->isAdmin()) {
            return redirect()->route('main')->with('error', '관리자 권한이 필요합니다.');
        }

        return $next($request);
    }
} 