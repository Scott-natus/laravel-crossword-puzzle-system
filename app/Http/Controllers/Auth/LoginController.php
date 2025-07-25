<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/main';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        // 최종 접속일 업데이트
        $user->update(['last_login_at' => now()]);
        
        // 로그인 환영 메시지 설정
        session(['welcome_message' => $user->name . '님, 다시 오신 것을 환영합니다! 👋']);
        
        // 로그인 정보 기억하기가 체크되어 있으면 이메일과 체크박스 상태를 쿠키에 저장
        if ($request->filled('remember')) {
            cookie()->queue('remember_email', $request->email, 60 * 24 * 30); // 30일간 저장
            cookie()->queue('remember_me', '1', 60 * 24 * 30); // 30일간 저장
        } else {
            // 체크되지 않았으면 쿠키 삭제
            cookie()->queue(cookie()->forget('remember_email'));
            cookie()->queue(cookie()->forget('remember_me'));
        }
        
        // 리다이렉션 URL이 있으면 해당 URL로, 없으면 기본 경로로
        $redirectUrl = $request->get('redirect');
        
        // redirect 파라미터가 없거나 현재 로그인 페이지 URL과 같으면 기본 경로로
        if (!$redirectUrl || $redirectUrl === request()->url()) {
            $redirectUrl = $this->redirectPath();
        }
        
        // 디버깅 로그 추가
        \Log::info('Login redirect debug', [
            'get_redirect' => $request->get('redirect'),
            'current_url' => request()->url(),
            'redirectPath' => $this->redirectPath(),
            'final_redirect' => $redirectUrl
        ]);
        
        return redirect($redirectUrl);
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return $this->loggedOut($request) ?: redirect('/');
    }
}
