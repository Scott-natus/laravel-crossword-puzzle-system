<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // 환영 메시지 설정
        if (!session('welcome_message')) {
            session(['welcome_message' => 'natus 작업소에 오신 것을 환영합니다!']);
        }
        
        return view('home');
    }
}
