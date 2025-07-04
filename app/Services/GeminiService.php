<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeminiService
{
    private $apiKey;
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    /**
     * 단어에 대한 힌트 생성 (세 가지 난이도를 한 번에)
     */
    public function generateHint(string $word, string $category): array
    {
        try {
            $prompt = $this->buildPrompt($word, $category);

            $requestData = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.8,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 1024,
                ]
            ];

            $url = $this->baseUrl . '?key=' . $this->apiKey;
            $response = Http::timeout(60)->post($url, $requestData);

            if ($response->successful()) {
                $data = $response->json();
                $hints = $this->extractMultipleHintsFromResponse($data);

                // 사용빈도 정보 추출
                $frequency = null;
                if (isset($hints[1]['frequency'])) {
                    $frequency = $hints[1]['frequency'];
                }

                return [
                    'success' => true,
                    'hints' => $hints,
                    'word' => $word,
                    'category' => $category,
                    'frequency' => $frequency
                ];
            } else {
                $error = 'API 요청 실패: ' . $response->status();
                return $this->getFailureResponse($error);
            }
        } catch (\Exception $e) {
            return $this->getFailureResponse('서비스 오류: ' . $e->getMessage());
        }
    }

    /**
     * 프롬프트 구성
     */
    private function buildPrompt(string $word, string $category): string
    {
        return "당신은 한글의 십자낱말 퍼즐 전문가입니다.

'{$category}' 카테고리에 속하는 단어 '{$word}'에 대한 일반적인 사용빈도 (1~5 , 낮을수록 자주 사용하는 빈도 ) 와

낱말에 대한 힌트를 3가지 난이도(쉬움, 보통, 어려움)로 생성해주세요.



**힌트 생성시 요구사항:**

1. 각 힌트는 40자 미만의 한국어로 작성해주세요.

2. 정답인 '{$word}'를 직접적으로 언급하지 마세요.

3. 아래 형식을 반드시 지켜서 응답해주세요. 다른 부가 설명은 절대 포함하지 마세요.



**응답 형식:**

'{$word}' 의 사용빈도 : [여기에 빈도 작성]

쉬움: [여기에 쉬운 난이도의 힌트 작성]

보통: [여기에 보통 난이도의 힌트 작성]

어려움: [여기에 어려운 난이도의 힌트 작성]";
    }

    /**
     * 응답에서 여러 힌트 추출
     */
    private function extractMultipleHintsFromResponse(array $response): array
    {
        $difficulties = [1 => '쉬움', 2 => '보통', 3 => '어려움'];
        $hints = [];
        $frequency = null;

        try {
            if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                $text = $response['candidates'][0]['content']['parts'][0]['text'];

                // 사용빈도 추출
                $frequencyPattern = "/의 사용빈도\s*:\s*(\d+)/";
                preg_match($frequencyPattern, $text, $frequencyMatches);
                if (isset($frequencyMatches[1])) {
                    $frequency = (int)$frequencyMatches[1];
                }

                foreach ($difficulties as $level => $diffText) {
                    $pattern = "/{$diffText}\s*:\s*([^\\n\\[\\]]+)/";
                    preg_match($pattern, $text, $matches);
                    
                    $hintText = isset($matches[1]) ? trim($matches[1]) : '힌트 추출 실패';
                    $isSuccess = isset($matches[1]);

                    $hints[$level] = [
                        'difficulty' => $level,
                        'difficulty_text' => $diffText,
                        'hint' => $hintText,
                        'success' => $isSuccess
                    ];
                }

                // 사용빈도 정보를 첫 번째 힌트에 포함
                if ($frequency !== null && isset($hints[1])) {
                    $hints[1]['frequency'] = $frequency;
                }

                return $hints;
            }
            throw new \Exception('Invalid response structure');
        } catch (\Exception $e) {
            Log::error('Multiple Hint extraction error', [
                'response' => $response,
                'error' => $e->getMessage()
            ]);
            return $this->getFailureResponse('힌트 추출 중 오류 발생');
        }
    }

    private function getFailureResponse(string $errorMessage): array
    {
        $difficulties = [1 => '쉬움', 2 => '보통', 3 => '어려움'];
        $hints = [];
        foreach ($difficulties as $level => $diffText) {
            $hints[$level] = [
                'difficulty' => $level,
                'difficulty_text' => $diffText,
                'hint' => '힌트 생성 실패',
                'success' => false,
                'error' => $errorMessage
            ];
        }
        return [
            'success' => false,
            'hints' => $hints,
            'error' => $errorMessage
        ];
    }

    /**
     * 응답에서 힌트 추출 (기존 메소드는 유지하되 사용되지 않음)
     */
    private function extractHintFromResponse(array $response): string
    {
        try {
            if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                $text = $response['candidates'][0]['content']['parts'][0]['text'];
                return trim($text);
            }
            return '힌트 생성에 실패했습니다.';
        } catch (\Exception $e) {
            Log::error('Hint extraction error', [
                'response' => $response,
                'error' => $e->getMessage()
            ]);
            return '힌트 추출 중 오류가 발생했습니다.';
        }
    }

    /**
     * 단어 생성 (재미나이 API 활용)
     */
    public function generateWords(string $prompt): array
    {
        try {
            Log::info('단어 생성 시작', ['prompt' => $prompt]);
            
            $requestData = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 1024,
                ]
            ];

            $url = $this->baseUrl . '?key=' . $this->apiKey;
            Log::info('Gemini API 호출', ['url' => $url]);
            
            $response = Http::timeout(60)->post($url, $requestData);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Gemini API 응답 성공', ['response' => $data]);
                
                // 응답 텍스트 직접 확인
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $responseText = $data['candidates'][0]['content']['parts'][0]['text'];
                    Log::info('Gemini API 응답 텍스트', ['text' => $responseText]);
                }
                
                $words = $this->extractWordsFromResponse($data);
                Log::info('단어 추출 결과', ['words' => $words]);

                return [
                    'success' => true,
                    'words' => $words,
                    'prompt' => $prompt
                ];
            } else {
                $error = 'API 요청 실패: ' . $response->status();
                Log::error('Gemini API word generation failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return [
                    'success' => false,
                    'error' => $error
                ];
            }
        } catch (\Exception $e) {
            Log::error('Gemini word generation error', [
                'error' => $e->getMessage(),
                'prompt' => $prompt
            ]);
            return [
                'success' => false,
                'error' => '서비스 오류: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 응답에서 단어 추출 (줄 단위 파싱)
     */
    private function extractWordsFromResponse(array $response): array
    {
        $words = [];

        try {
            if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                $text = $response['candidates'][0]['content']['parts'][0]['text'];
                Log::info('파싱 직전 텍스트', ['text' => $text]);
                $lines = preg_split('/\r?\n|\r/', $text);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!$line) continue;
                    // [카테고리,단어] 형식
                    if (preg_match('/\[([^,\]]+),([^\]]+)\]/', $line, $match)) {
                        $category = trim($match[1]);
                        $word = trim($match[2]);
                    } elseif (preg_match('/\[([^\]]+)\]/', $line, $match)) {
                        $word = trim($match[1]);
                        $category = $this->extractCategoryFromPrompt($text);
                    } else {
                        continue;
                    }
                    if (!empty($word) && mb_strlen($word) >= 2 && mb_strlen($word) <= 5) {
                        $words[] = [
                            'category' => $category,
                            'word' => $word
                        ];
                    }
                }
                return $words;
            }
            throw new \Exception('Invalid response structure');
        } catch (\Exception $e) {
            Log::error('Word extraction error', [
                'response' => $response,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * 프롬프트에서 카테고리 추출
     */
    private function extractCategoryFromPrompt(string $text): string
    {
        // "분야: XXX" 패턴에서 카테고리 추출
        if (preg_match('/분야:\s*([^\n]+)/', $text, $matches)) {
            return trim($matches[1]);
        }
        
        // 기본값
        return '일반';
    }

    /**
     * 난이도 텍스트 변환
     */
    private function getDifficultyText(int $difficulty): string
    {
        return [1 => '쉬움', 2 => '보통', 3 => '어려움'][$difficulty] ?? '보통';
    }

    /**
     * 여러 단어에 대한 힌트 일괄 생성
     */
    public function generateHintsForWords(array $words): array
    {
        $results = [];
        foreach ($words as $word) {
            if (count($results) > 0) {
                sleep(1);
            }
            $result = $this->generateHint($word['word'], $word['category']);
            $results[] = ['word_id' => $word['id'], 'word' => $word['word'], 'result' => $result];
        }
        return $results;
    }

    /**
     * API 연결 테스트
     */
    public function testConnection(): bool
    {
        try {
            $testWord = '테스트';
            $testCategory = '일반';
            $prompt = $this->buildPrompt($testWord, $testCategory);

            $requestData = ['contents' => [['parts' => [['text' => $prompt]]]]];
            $url = $this->baseUrl . '?key=' . $this->apiKey;

            $response = Http::timeout(10)->post($url, $requestData);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Gemini connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
} 