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
        return "당신은 한글 십자낱말 퍼즐을 위한 힌트를 만드는 전문가입니다.

단어 '{$word}' ({$category} 카테고리)에 대한 힌트를 3가지 난이도로 만들어주세요.

**힌트 작성 규칙:**
1. 정답 단어를 직접 언급하지 마세요
2. 30자 내외로 연상되기 쉽게 설명해 주세요
3. 초등학생도 이해할 수 있게 작성하세요
4. 너무 어렵거나 추상적인 표현은 피하세요

**응답 형식 (다른 설명 없이):**

'{$word}' 의 사용빈도 : [1~5 숫자]

쉬움: [매우 쉬운 힌트]
보통: [보통 난이도 힌트]  
어려움: [조금 어려운 힌트]";
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
     * 응답에서 단어 추출 (줄 단위 파싱) - [카테고리,단어,난이도] 형식
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
                    
                    // "단어, 난이도" 형식 파싱
                    if (preg_match('/^([^,]+),\s*(\d+)$/', $line, $match)) {
                        $word = trim($match[1]);
                        $difficulty = (int)$match[2];
                        
                        // 난이도 범위 검증 (1~5)
                        if ($difficulty < 1 || $difficulty > 5) {
                            $difficulty = 2; // 기본값
                        }
                        
                        if (!empty($word) && mb_strlen($word) >= 2 && mb_strlen($word) <= 5) {
                            $words[] = [
                                'word' => $word,
                                'difficulty' => $difficulty
                            ];
                        }
                    }
                    // 기존 [카테고리,단어,난이도] 형식도 지원 (하위 호환성)
                    elseif (preg_match('/\[([^,\]]+),([^,\]]+),(\d+)\]/', $line, $match)) {
                        $category = trim($match[1]);
                        $word = trim($match[2]);
                        $difficulty = (int)$match[3];
                        
                        // 난이도 범위 검증 (1~5)
                        if ($difficulty < 1 || $difficulty > 5) {
                            $difficulty = 2; // 기본값
                        }
                        
                        if (!empty($word) && mb_strlen($word) >= 2 && mb_strlen($word) <= 5) {
                            $words[] = [
                                'category' => $category,
                                'word' => $word,
                                'difficulty' => $difficulty
                            ];
                        }
                    }
                    // 기존 [카테고리,단어] 형식도 지원 (하위 호환성)
                    elseif (preg_match('/\[([^,\]]+),([^\]]+)\]/', $line, $match)) {
                        $category = trim($match[1]);
                        $word = trim($match[2]);
                        
                        if (!empty($word) && mb_strlen($word) >= 2 && mb_strlen($word) <= 5) {
                            $words[] = [
                                'category' => $category,
                                'word' => $word,
                                'difficulty' => 2 // 기본값
                            ];
                        }
                    }
                    // 단순 [단어] 형식도 지원
                    elseif (preg_match('/\[([^\]]+)\]/', $line, $match)) {
                        $word = trim($match[1]);
                        $category = $this->extractCategoryFromPrompt($text);
                        
                        if (!empty($word) && mb_strlen($word) >= 2 && mb_strlen($word) <= 5) {
                            $words[] = [
                                'category' => $category,
                                'word' => $word,
                                'difficulty' => 2 // 기본값
                            ];
                        }
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
     * 단어 분석 (품사 판별)
     */
    public function analyzeWords(string $prompt): array
    {
        try {
            Log::info('단어 분석 시작', ['prompt' => $prompt]);
            
            $requestData = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.3,
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
                $raw_response = isset($data['candidates'][0]['content']['parts'][0]['text']) ? $data['candidates'][0]['content']['parts'][0]['text'] : '';
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $responseText = $data['candidates'][0]['content']['parts'][0]['text'];
                    Log::info('Gemini API 응답 텍스트', ['text' => $responseText]);
                }
                $words = $this->extractAnalyzedWordsFromResponse($data);
                Log::info('단어 분석 결과', ['words' => $words]);

                return [
                    'success' => true,
                    'words' => $words,
                    'prompt' => $prompt,
                    'raw_response' => $raw_response
                ];
            } else {
                $error = 'API 요청 실패: ' . $response->status();
                Log::error('Gemini API word analysis failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return [
                    'success' => false,
                    'error' => $error
                ];
            }
        } catch (\Exception $e) {
            Log::error('Gemini word analysis error', [
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
     * 응답에서 분석된 단어 추출 (줄 단위 파싱)
     */
    private function extractAnalyzedWordsFromResponse(array $response): array
    {
        $words = [];

        try {
            if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                $text = $response['candidates'][0]['content']['parts'][0]['text'];
                Log::info('분석 결과 파싱 직전 텍스트', ['text' => $text]);
                $lines = preg_split('/\r?\n|\r/', $text);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!$line || $line === '없음') continue;
                    
                    // 단순히 한 줄에 한 단어씩 추출
                    $word = trim($line);
                    if (!empty($word) && mb_strlen($word) >= 1) {
                        $words[] = $word;
                    }
                }
                return $words;
            }
            throw new \Exception('Invalid response structure');
        } catch (\Exception $e) {
            Log::error('Analyzed word extraction error', [
                'response' => $response,
                'error' => $e->getMessage()
            ]);
            return [];
        }
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

    /**
     * 카테고리와 힌트를 함께 생성
     */
    public function generateCategoryAndHints(string $word): array
    {
        try {
            $prompt = $this->buildCategoryAndHintsPrompt($word);
            
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
            Log::info('Gemini API 카테고리+힌트 생성 호출', ['word' => $word]);
            
            $response = Http::timeout(60)->post($url, $requestData);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Gemini API 카테고리+힌트 응답 성공', ['word' => $word]);
                
                $result = $this->extractCategoryAndHintsFromResponse($data);
                
                if ($result['success']) {
                    Log::info('카테고리+힌트 추출 성공', [
                        'word' => $word,
                        'category' => $result['category'],
                        'hints_count' => count($result['hints'])
                    ]);
                }

                return $result;
            } else {
                $error = 'API 요청 실패: ' . $response->status();
                Log::error('Gemini API 카테고리+힌트 생성 실패', [
                    'word' => $word,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return [
                    'success' => false,
                    'error' => $error
                ];
            }
        } catch (\Exception $e) {
            Log::error('Gemini 카테고리+힌트 생성 오류', [
                'word' => $word,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => '서비스 오류: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 카테고리와 힌트 생성 프롬프트
     */
    private function buildCategoryAndHintsPrompt(string $word): string
    {
        return "당신은 한글 십자낱말 퍼즐을 위한 힌트를 만드는 전문가입니다.

단어 '{$word}' 에 대한 적절한 카테고리를 하나 생성하고 
카테고리와 단어 '{$word}'에  적정한 힌트를 3가지 난이도로 만들어주세요.

카테고리는 적합한 한가지 분야로 판단해주세요. (예: 사회 , 사회과학, 인문, 인문학 등)
 

**힌트 작성 규칙:**
1. 정답 단어를 직접 언급하지 마세요
2. 50자 내외로 연상되기 쉽게 설명해 주세요
3. 초등학생도 이해할 수 있게 작성하세요
4. 너무 어렵거나 추상적인 표현은 피하세요

**응답 형식 (다른 설명 없이):**

'{$word}' 의 카테고리 : [카테고리]

쉬움: [매우 쉬운 힌트]
보통: [보통 난이도 힌트]  
어려움: [조금 어려운 힌트]";
    }

    /**
     * 응답에서 카테고리와 힌트 추출
     */
    private function extractCategoryAndHintsFromResponse(array $response): array
    {
        $difficulties = [1 => '쉬움', 2 => '보통', 3 => '어려움'];
        $hints = [];
        $category = null;

        try {
            if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                $text = $response['candidates'][0]['content']['parts'][0]['text'];
                Log::info('카테고리+힌트 파싱 직전 텍스트', ['text' => $text]);

                // 카테고리 추출
                $categoryPattern = "/의 카테고리\s*:\s*([^\\n]+)/";
                preg_match($categoryPattern, $text, $categoryMatches);
                if (isset($categoryMatches[1])) {
                    $category = trim($categoryMatches[1]);
                    // 대괄호 제거
                    $category = preg_replace('/^\[|\]$/', '', $category);
                }

                // 힌트들 추출
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

                if ($category && count(array_filter($hints, fn($h) => $h['success'])) > 0) {
                    return [
                        'success' => true,
                        'category' => $category,
                        'hints' => $hints
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => '카테고리 또는 힌트 추출 실패',
                        'category' => $category,
                        'hints' => $hints
                    ];
                }
            }
            throw new \Exception('Invalid response structure');
        } catch (\Exception $e) {
            Log::error('카테고리+힌트 추출 오류', [
                'response' => $response,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => '카테고리+힌트 추출 중 오류 발생',
                'category' => null,
                'hints' => []
            ];
        }
    }
} 