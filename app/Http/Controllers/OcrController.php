<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class OcrController extends Controller
{
    /**
     * OCR 처리 페이지 표시
     */
    public function index()
    {
        return view('ocr.index');
    }
    
    /**
     * 서버 사이드 OCR 처리
     */
    public function processServer(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240' // 10MB 제한
        ]);
        
        try {
            $file = $request->file('image');
            $fileName = uniqid() . '_' . $file->getClientOriginalName();
            $filePath = storage_path('app/temp/' . $fileName);
            
            // 임시 디렉토리 생성
            if (!is_dir(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            // 파일 저장
            $file->move(storage_path('app/temp'), $fileName);
            
            // Tesseract 명령어 실행
            $command = "tesseract " . escapeshellarg($filePath) . " stdout -l kor+eng";
            $output = shell_exec($command);
            
            // 임시 파일 삭제
            unlink($filePath);
            
            if ($output !== null) {
                $text = trim($output);
                return response()->json([
                    'success' => true,
                    'text' => $text ?: '텍스트를 찾을 수 없습니다.',
                    'method' => 'server_tesseract'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'OCR 처리 중 오류가 발생했습니다.'
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('OCR 처리 오류: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => '오류: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Google Vision API OCR 처리
     */
    public function processGoogleVision(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240'
        ]);
        
        try {
            $file = $request->file('image');
            $imageData = base64_encode(file_get_contents($file->getRealPath()));
            
            // Google Cloud Vision API 호출
            $apiKey = config('services.google.vision_api_key', 'YOUR_API_KEY');
            
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
                    return response()->json([
                        'success' => true,
                        'text' => $jsonResult['responses'][0]['textAnnotations'][0]['description'],
                        'method' => 'google_vision'
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => '텍스트를 찾을 수 없습니다.'
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'API 호출 실패 (HTTP ' . $httpCode . ')'
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Google Vision API 오류: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => '오류: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * OCR 결과를 데이터베이스에 저장
     */
    public function saveResult(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:10000',
            'method' => 'required|string|in:server_tesseract,google_vision,client_tesseract',
            'filename' => 'nullable|string'
        ]);
        
        try {
            // OCR 결과를 로그에 저장 (실제로는 데이터베이스에 저장)
            Log::info('OCR 결과 저장', [
                'text' => $request->text,
                'method' => $request->method,
                'filename' => $request->filename,
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'OCR 결과가 저장되었습니다.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('OCR 결과 저장 오류: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => '저장 중 오류가 발생했습니다.'
            ]);
        }
    }
    
    /**
     * OCR 히스토리 조회
     */
    public function history()
    {
        // 실제로는 데이터베이스에서 조회
        $history = [
            [
                'id' => 1,
                'text' => '샘플 OCR 결과',
                'method' => 'server_tesseract',
                'created_at' => now()->subHours(1)
            ]
        ];
        
        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }
} 