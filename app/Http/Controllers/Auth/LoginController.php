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
    protected $redirectTo = '/home';

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
        
        return redirect()->intended($this->redirectPath());
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
