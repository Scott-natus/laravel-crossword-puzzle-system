<?php
// Google Cloud Vision API OCR 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $response = ['success' => false, 'text' => '', 'error' => ''];
    
    try {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $file = $_FILES['image'];
        $fileName = uniqid() . '_' . basename($file['name']);
        $filePath = $uploadDir . $fileName;
        
        // 파일 업로드
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Google Cloud Vision API 호출
            $apiKey = 'YOUR_GOOGLE_CLOUD_API_KEY'; // 실제 API 키로 교체 필요
            $imageData = base64_encode(file_get_contents($filePath));
            
            $data = [
                'requests' => [
                    [
                        'image' => [
                            'content' => $imageData
                        ],
                        'features' => [
                            [
                                'type' => 'TEXT_DETECTION',
                                'maxResults' => 1
                            ]
                        ]
                    ]
                ]
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://vision.googleapis.com/v1/images:annotate?key=" . $apiKey);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $jsonResult = json_decode($result, true);
                if (isset($jsonResult['responses'][0]['textAnnotations'][0]['description'])) {
                    $response['success'] = true;
                    $response['text'] = $jsonResult['responses'][0]['textAnnotations'][0]['description'];
                } else {
                    $response['error'] = '텍스트를 찾을 수 없습니다.';
                }
            } else {
                $response['error'] = 'API 호출 실패 (HTTP ' . $httpCode . ')';
            }
            
            // 임시 파일 삭제
            unlink($filePath);
        } else {
            $response['error'] = '파일 업로드에 실패했습니다.';
        }
    } catch (Exception $e) {
        $response['error'] = '오류: ' . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Cloud Vision OCR 데모</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .preview-image { max-width: 100%; max-height: 400px; }
        .ocr-result { 
            background: #f8f9fa; 
            border: 1px solid #dee2e6; 
            border-radius: 5px; 
            padding: 15px; 
            margin-top: 15px;
            white-space: pre-wrap;
            font-family: monospace;
        }
        .loading { text-align: center; padding: 20px; }
        .api-key-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h1 class="mb-4">☁️ Google Cloud Vision OCR 데모</h1>
    
    <div class="api-key-warning">
        <h6>⚠️ 설정 필요:</h6>
        <p class="mb-0">이 데모를 사용하려면 Google Cloud Vision API 키가 필요합니다. 
        <code>YOUR_GOOGLE_CLOUD_API_KEY</code>를 실제 API 키로 교체해주세요.</p>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>파일 업로드 (Google Vision API)</h5>
                </div>
                <div class="card-body">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <input type="file" name="image" id="fileInput" class="form-control mb-3" accept="image/*" required>
                        <button type="submit" class="btn btn-primary">Google Vision OCR 처리</button>
                        <button type="button" id="clearBtn" class="btn btn-secondary ms-2">초기화</button>
                    </form>
                </div>
            </div>
            
            <div id="preview" class="mt-3" style="display: none;">
                <h6>미리보기:</h6>
                <img id="previewImage" class="preview-image border">
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>OCR 결과</h5>
                </div>
                <div class="card-body">
                    <div id="loading" class="loading" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">처리중...</span>
                        </div>
                        <div class="mt-2">Google Vision API에서 이미지를 분석하고 있습니다...</div>
                    </div>
                    <div id="result" class="ocr-result" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <div class="alert alert-info">
            <h6>📋 Google Cloud Vision API 특징:</h6>
            <ul class="mb-0">
                <li>매우 정확한 OCR 결과</li>
                <li>다양한 언어 지원</li>
                <li>복잡한 레이아웃 처리 가능</li>
                <li>API 키 필요 (유료 서비스)</li>
            </ul>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    const fileInput = $('#fileInput');
    const uploadForm = $('#uploadForm');
    const clearBtn = $('#clearBtn');
    const preview = $('#preview');
    const previewImage = $('#previewImage');
    const loading = $('#loading');
    const result = $('#result');
    
    // 파일 선택 시 미리보기
    fileInput.on('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.attr('src', e.target.result);
                preview.show();
            };
            reader.readAsDataURL(file);
            result.hide();
        }
    });
    
    // 폼 제출 (Google Vision API OCR 처리)
    uploadForm.on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        loading.show();
        result.hide();
        
        $.ajax({
            url: 'ocr-google-vision.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                loading.hide();
                if (response.success) {
                    result.text(response.text || '텍스트를 찾을 수 없습니다.');
                } else {
                    result.text('오류: ' + (response.error || '알 수 없는 오류'));
                }
                result.show();
            },
            error: function() {
                loading.hide();
                result.text('서버 통신 오류가 발생했습니다.');
                result.show();
            }
        });
    });
    
    // 초기화
    clearBtn.on('click', function() {
        fileInput.val('');
        preview.hide();
        result.hide();
        loading.hide();
    });
});
</script>
</body>
</html> 