<?php

namespace App\Http\Controllers;

use App\Models\NotificationSetting;
use Illuminate\Http\Request;

class NotificationSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        $setting = auth()->user()->notificationSetting ?? new NotificationSetting();
        return view('notification-settings.edit', compact('setting'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'email_notify' => 'boolean',
            'app_notify' => 'boolean',
            'email' => 'required_if:email_notify,1|email',
            'device_token' => 'required_if:app_notify,1|string',
        ]);

        $setting = auth()->user()->notificationSetting ?? new NotificationSetting();
        $setting->user_id = auth()->id();
        $setting->email_notify = $request->boolean('email_notify');
        $setting->app_notify = $request->boolean('app_notify');
        $setting->email = $validated['email'] ?? null;
        $setting->device_token = $validated['device_token'] ?? null;
        $setting->save();

        return redirect()->route('notification-settings.edit')
            ->with('success', '알림 설정이 업데이트되었습니다.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
