<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginController extends Controller
{
    /**
     * Google 로그인 리다이렉트
     */
    public function redirectToGoogle()
    {
        // 리다이렉션 URL을 세션에 저장
        if (request()->has('redirect')) {
            session(['redirect_url' => request()->get('redirect')]);
        }
        
        return Socialite::driver('google')->redirect();
    }

    /**
     * Google 로그인 콜백 처리
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            $user = $this->findOrCreateUser($googleUser, 'google');
            
            // 최종 접속일 업데이트
            $user->update(['last_login_at' => now()]);
            
            Auth::login($user);
            
            // 세션에서 리다이렉션 URL 가져오기
            $redirectUrl = session('redirect_url', '/main');
            session()->forget('redirect_url');
            
            return redirect()->intended($redirectUrl);
            
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Google 로그인 중 오류가 발생했습니다.');
        }
    }

    /**
     * Kakao 로그인 리다이렉트
     */
    public function redirectToKakao()
    {
        // 리다이렉션 URL을 세션에 저장
        if (request()->has('redirect')) {
            session(['redirect_url' => request()->get('redirect')]);
        }
        
        return Socialite::driver('kakao')->redirect();
    }

    /**
     * Kakao 로그인 콜백 처리
     */
    public function handleKakaoCallback()
    {
        try {
            $kakaoUser = Socialite::driver('kakao')->user();
            
            $user = $this->findOrCreateUser($kakaoUser, 'kakao');
            
            // 최종 접속일 업데이트
            $user->update(['last_login_at' => now()]);
            
            Auth::login($user);
            
            // 세션에서 리다이렉션 URL 가져오기
            $redirectUrl = session('redirect_url', '/main');
            session()->forget('redirect_url');
            
            return redirect()->intended($redirectUrl);
            
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Kakao 로그인 중 오류가 발생했습니다.');
        }
    }

    /**
     * Naver 로그인 리다이렉트
     */
    public function redirectToNaver()
    {
        // 리다이렉션 URL을 세션에 저장
        if (request()->has('redirect')) {
            session(['redirect_url' => request()->get('redirect')]);
        }
        
        return Socialite::driver('naver')->redirect();
    }

    /**
     * Naver 로그인 콜백 처리
     */
    public function handleNaverCallback()
    {
        try {
            $naverUser = Socialite::driver('naver')->user();
            
            $user = $this->findOrCreateUser($naverUser, 'naver');
            
            // 최종 접속일 업데이트
            $user->update(['last_login_at' => now()]);
            
            Auth::login($user);
            
            // 세션에서 리다이렉션 URL 가져오기
            $redirectUrl = session('redirect_url', '/main');
            session()->forget('redirect_url');
            
            return redirect()->intended($redirectUrl);
            
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Naver 로그인 중 오류가 발생했습니다.');
        }
    }

    /**
     * 사용자 찾기 또는 생성
     */
    private function findOrCreateUser($socialUser, $provider)
    {
        // 기존 사용자 찾기
        $user = User::where('provider', $provider)
                   ->where('provider_id', $socialUser->getId())
                   ->first();

        if ($user) {
            return $user;
        }

        // 이메일로 기존 사용자 찾기 (SNS 로그인이 아닌 경우)
        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            // 기존 사용자에게 SNS 정보 추가
            $user->update([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
                'nickname' => $socialUser->getNickname() ?? $socialUser->getName(),
            ]);
            
            return $user;
        }

        // 새 사용자 생성
        return User::create([
            'name' => $socialUser->getName(),
            'email' => $socialUser->getEmail(),
            'password' => Hash::make(str_random(16)), // 랜덤 비밀번호
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),
            'nickname' => $socialUser->getNickname() ?? $socialUser->getName(),
        ]);
    }
}
