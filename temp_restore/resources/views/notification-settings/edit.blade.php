@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">알림 설정</div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('notification-settings.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="email_notify" name="email_notify"
                                    {{ old('email_notify', $setting->email_notify) ? 'checked' : '' }}>
                                <label class="form-check-label" for="email_notify">이메일 알림 받기</label>
                            </div>
                        </div>

                        <div class="mb-3 email-field" style="display: {{ old('email_notify', $setting->email_notify) ? 'block' : 'none' }}">
                            <label for="email" class="form-label">이메일 주소</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                                name="email" value="{{ old('email', $setting->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="app_notify" name="app_notify"
                                    {{ old('app_notify', $setting->app_notify) ? 'checked' : '' }}>
                                <label class="form-check-label" for="app_notify">앱 알림 받기</label>
                            </div>
                        </div>

                        <div class="mb-3 app-field" style="display: {{ old('app_notify', $setting->app_notify) ? 'block' : 'none' }}">
                            <label for="device_token" class="form-label">디바이스 토큰</label>
                            <input type="text" class="form-control @error('device_token') is-invalid @enderror"
                                id="device_token" name="device_token" value="{{ old('device_token', $setting->device_token) }}">
                            @error('device_token')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">앱에서 디바이스 토큰을 복사하여 붙여넣으세요.</div>
                        </div>

                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">설정 저장</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const emailNotify = document.getElementById('email_notify');
    const appNotify = document.getElementById('app_notify');
    const emailField = document.querySelector('.email-field');
    const appField = document.querySelector('.app-field');

    emailNotify.addEventListener('change', function() {
        emailField.style.display = this.checked ? 'block' : 'none';
    });

    appNotify.addEventListener('change', function() {
        appField.style.display = this.checked ? 'block' : 'none';
    });
});
</script>
@endpush
@endsection 