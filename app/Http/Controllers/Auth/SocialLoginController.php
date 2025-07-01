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
            
            Auth::login($user);
            
            return redirect()->intended('/main');
            
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Google 로그인 중 오류가 발생했습니다.');
        }
    }

    /**
     * Kakao 로그인 리다이렉트
     */
    public function redirectToKakao()
    {
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
            
            Auth::login($user);
            
            return redirect()->intended('/main');
            
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Kakao 로그인 중 오류가 발생했습니다.');
        }
    }

    /**
     * Naver 로그인 리다이렉트
     */
    public function redirectToNaver()
    {
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
            
            Auth::login($user);
            
            return redirect()->intended('/main');
            
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
