@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h2 class="mb-0">🔍 OCR 텍스트 인식</h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>📤 이미지 업로드</h5>
                            <form id="ocrForm" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label for="image" class="form-label">이미지 파일 선택</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                                    <div class="form-text">JPG, PNG, GIF 형식 지원 (최대 10MB)</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">OCR 방법 선택</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="ocrMethod" id="serverMethod" value="server" checked>
                                        <label class="form-check-label" for="serverMethod">
                                            🖥️ 서버 Tesseract (무료, 정확도 좋음)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="ocrMethod" id="googleMethod" value="google">
                                        <label class="form-check-label" for="googleMethod">
                                            ☁️ Google Vision API (유료, 최고 정확도)
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" id="processBtn">
                                    <span class="spinner-border spinner-border-sm d-none" id="loadingSpinner"></span>
                                    OCR 처리 시작
                                </button>
                            </form>
                            
                            <div id="preview" class="mt-3" style="display: none;">
                                <h6>📷 미리보기:</h6>
                                <img id="previewImage" class="img-fluid border rounded" style="max-height: 300px;">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>📝 OCR 결과</h5>
                            <div id="result" class="border rounded p-3 bg-light" style="min-height: 200px; display: none;">
                                <div id="resultText" class="mb-3"></div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-success" id="saveBtn" style="display: none;">
                                        💾 결과 저장
                                    </button>
                                    <button class="btn btn-sm btn-secondary" id="copyBtn" style="display: none;">
                                        📋 복사
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" id="clearResultBtn">
                                        🗑️ 초기화
                                    </button>
                                </div>
                            </div>
                            
                            <div id="error" class="alert alert-danger mt-3" style="display: none;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>📊 OCR 방법별 특징</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6>🖥️ 서버 Tesseract</h6>
                                    <ul class="mb-0">
                                        <li>✅ 무료 사용</li>
                                        <li>✅ 개인정보 보호</li>
                                        <li>✅ 한글/영어 지원</li>
                                        <li>❌ 정확도 제한</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6>☁️ Google Vision API</h6>
                                    <ul class="mb-0">
                                        <li>✅ 최고 정확도</li>
                                        <li>✅ 다양한 언어</li>
                                        <li>✅ 복잡한 레이아웃</li>
                                        <li>❌ API 키 필요</li>
                                        <li>❌ 유료 서비스</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>🔗 다른 OCR 데모 페이지들</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="/ocr-demo.html" class="btn btn-outline-primary w-100 mb-2">
                                📱 클라이언트 OCR
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="/ocr-server.php" class="btn btn-outline-warning w-100 mb-2">
                                🖥️ 서버 OCR
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="/ocr-google-vision.php" class="btn btn-outline-danger w-100 mb-2">
                                ☁️ Google Vision
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="/ocr-comparison.html" class="btn btn-outline-info w-100 mb-2">
                                🔍 비교 테스트
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    const form = $('#ocrForm');
    const imageInput = $('#image');
    const processBtn = $('#processBtn');
    const loadingSpinner = $('#loadingSpinner');
    const preview = $('#preview');
    const previewImage = $('#previewImage');
    const result = $('#result');
    const resultText = $('#resultText');
    const error = $('#error');
    const saveBtn = $('#saveBtn');
    const copyBtn = $('#copyBtn');
    const clearResultBtn = $('#clearResultBtn');
    
    let ocrResult = null;
    
    // 파일 선택 시 미리보기
    imageInput.on('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.attr('src', e.target.result);
                preview.show();
            };
            reader.readAsDataURL(file);
            result.hide();
            error.hide();
        }
    });
    
    // 폼 제출
    form.on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const method = $('input[name="ocrMethod"]:checked').val();
        
        // 로딩 상태
        processBtn.prop('disabled', true);
        loadingSpinner.removeClass('d-none');
        result.hide();
        error.hide();
        
        // API 엔드포인트 선택
        let url = '';
        if (method === 'server') {
            url = '{{ route("ocr.process-server") }}';
        } else if (method === 'google') {
            url = '{{ route("ocr.process-google-vision") }}';
        }
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    ocrResult = response;
                    resultText.text(response.text);
                    result.show();
                    saveBtn.show();
                    copyBtn.show();
                } else {
                    error.text('오류: ' + (response.error || '알 수 없는 오류')).show();
                }
            },
            error: function(xhr) {
                let errorMsg = '서버 통신 오류가 발생했습니다.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMsg = xhr.responseJSON.error;
                }
                error.text('오류: ' + errorMsg).show();
            },
            complete: function() {
                processBtn.prop('disabled', false);
                loadingSpinner.addClass('d-none');
            }
        });
    });
    
    // 결과 저장
    saveBtn.on('click', function() {
        if (!ocrResult) return;
        
        $.ajax({
            url: '{{ route("ocr.save-result") }}',
            type: 'POST',
            data: {
                text: ocrResult.text,
                method: ocrResult.method,
                filename: imageInput[0].files[0]?.name || 'unknown'
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('OCR 결과가 저장되었습니다.');
                } else {
                    alert('저장 실패: ' + response.error);
                }
            },
            error: function() {
                alert('저장 중 오류가 발생했습니다.');
            }
        });
    });
    
    // 결과 복사
    copyBtn.on('click', function() {
        const text = resultText.text();
        navigator.clipboard.writeText(text).then(function() {
            alert('텍스트가 클립보드에 복사되었습니다.');
        }).catch(function() {
            alert('복사에 실패했습니다.');
        });
    });
    
    // 결과 초기화
    clearResultBtn.on('click', function() {
        result.hide();
        error.hide();
        ocrResult = null;
        saveBtn.hide();
        copyBtn.hide();
    });
});
</script>
@endpush 