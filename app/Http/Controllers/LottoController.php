<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LottoTicket;
use App\Models\DdongsunRanking;
use App\Models\NumberStatistic;
use App\Models\LottoResult;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LottoController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $currentWeek = Carbon::now()->weekOfYear;
        
        // 사용자의 로또 티켓들
        $tickets = $user ? $user->lottoTickets()->latest()->paginate(10) : collect();
        
        // 주간 랭킹
        $weeklyRankings = DdongsunRanking::with('user')
            ->where('week_number', $currentWeek)
            ->orderBy('ddongsun_power', 'desc')
            ->limit(10)
            ->get();
        
        // 번호별 통계
        $numberStats = NumberStatistic::where('week_number', $currentWeek)
            ->orderBy('selection_count', 'desc')
            ->limit(10)
            ->get();
        
        // 최근 로또 결과
        $recentResults = LottoResult::latest('draw_date')->limit(5)->get();
        
        return view('lotto.index', compact('tickets', 'weeklyRankings', 'numberStats', 'recentResults', 'user'));
    }

    public function upload()
    {
        return view('lotto.upload');
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'numbers' => 'required|array|min:6|max:6',
            'numbers.*' => 'integer|min:1|max:45',
        ]);

        $user = Auth::user();
        
        // 이미지 저장
        $imagePath = $request->file('image')->store('lotto-tickets', 'public');
        
        // 똥손력 계산 (간단한 알고리즘)
        $ddongsunPower = $this->calculateDdongsunPower($request->numbers);
        
        // 로또 티켓 저장
        $ticket = LottoTicket::create([
            'user_id' => $user->id,
            'image_path' => $imagePath,
            'numbers' => $request->numbers,
            'ddongsun_power' => $ddongsunPower,
            'upload_date' => now(),
        ]);
        
        // 사용자 똥손력 업데이트
        $user->increment('total_ddongsun_power', $ddongsunPower);
        $this->updateUserLevel($user);
        
        // 번호 통계 업데이트
        $this->updateNumberStatistics($request->numbers);
        
        return redirect()->route('lotto.index')->with('success', '로또 용지가 성공적으로 업로드되었습니다!');
    }

    public function show($id)
    {
        $ticket = LottoTicket::with('user')->findOrFail($id);
        return view('lotto.show', compact('ticket'));
    }

    public function rankings()
    {
        $currentWeek = Carbon::now()->weekOfYear;
        
        $weeklyRankings = DdongsunRanking::with('user')
            ->where('week_number', $currentWeek)
            ->orderBy('ddongsun_power', 'desc')
            ->paginate(20);
        
        $allTimeRankings = \App\Models\User::orderBy('total_ddongsun_power', 'desc')
            ->limit(20)
            ->get();
        
        return view('lotto.rankings', compact('weeklyRankings', 'allTimeRankings'));
    }

    public function statistics()
    {
        $currentWeek = Carbon::now()->weekOfYear;
        
        $numberStats = NumberStatistic::where('week_number', $currentWeek)
            ->orderBy('selection_count', 'desc')
            ->get();
        
        $weeklyTrends = NumberStatistic::selectRaw('number, SUM(selection_count) as total_selections')
            ->groupBy('number')
            ->orderBy('total_selections', 'desc')
            ->limit(20)
            ->get();
        
        return view('lotto.statistics', compact('numberStats', 'weeklyTrends'));
    }

    private function calculateDdongsunPower($numbers)
    {
        // 간단한 똥손력 계산 알고리즘
        // 1. 연속된 번호가 많을수록 똥손력 높음
        // 2. 끝자리 같은 번호가 많을수록 똥손력 높음
        // 3. 특정 패턴이 많을수록 똥손력 높음
        
        $power = 0;
        
        // 연속된 번호 체크
        sort($numbers);
        for ($i = 0; $i < count($numbers) - 1; $i++) {
            if ($numbers[$i + 1] - $numbers[$i] == 1) {
                $power += 10;
            }
        }
        
        // 끝자리 같은 번호 체크
        $lastDigits = array_map(function($num) {
            return $num % 10;
        }, $numbers);
        
        $digitCounts = array_count_values($lastDigits);
        foreach ($digitCounts as $count) {
            if ($count >= 2) {
                $power += ($count - 1) * 15;
            }
        }
        
        // 기본 똥손력
        $power += rand(20, 50);
        
        return min($power, 100); // 최대 100
    }

    private function updateUserLevel($user)
    {
        $totalPower = $user->total_ddongsun_power;
        
        if ($totalPower >= 1000) {
            $level = '플래티넘';
        } elseif ($totalPower >= 500) {
            $level = '골드';
        } elseif ($totalPower >= 200) {
            $level = '실버';
        } else {
            $level = '브론즈';
        }
        
        $user->update(['current_level' => $level]);
    }

    private function updateNumberStatistics($numbers)
    {
        $currentWeek = Carbon::now()->weekOfYear;
        
        foreach ($numbers as $number) {
            NumberStatistic::updateOrCreate(
                ['number' => $number, 'week_number' => $currentWeek],
                [
                    'selection_count' => \DB::raw('selection_count + 1'),
                ]
            );
        }
    }
}
