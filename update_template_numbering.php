<?php

require_once 'vendor/autoload.php';

// Laravel 애플리케이션 부트스트랩
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\GridTemplateController;

echo "템플릿 넘버링 업데이트 시작...\n";

try {
    // 컨트롤러 인스턴스 생성
    $controller = new GridTemplateController();
    
    // 요청 객체 생성
    $request = new Request();
    $request->merge(['template_ids' => [11, 12, 13, 14]]);
    
    // 넘버링 업데이트 실행
    $response = $controller->updateTemplateNumbering($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "✅ 넘버링 업데이트 성공!\n\n";
        
        foreach ($data['results'] as $result) {
            echo "템플릿 ID: {$result['template_id']}\n";
            echo "템플릿 이름: {$result['template_name']}\n";
            echo "상태: {$result['status']}\n";
            echo "메시지: {$result['message']}\n";
            
            if (isset($result['new_word_count'])) {
                echo "단어 개수: {$result['new_word_count']}\n";
            }
            
            echo "---\n";
        }
    } else {
        echo "❌ 넘버링 업데이트 실패: {$data['message']}\n";
        if (isset($data['debug_info']['error'])) {
            echo "오류: {$data['debug_info']['error']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ 스크립트 실행 오류: " . $e->getMessage() . "\n";
    echo "스택 트레이스:\n" . $e->getTraceAsString() . "\n";
}

echo "\n템플릿 넘버링 업데이트 완료.\n"; 