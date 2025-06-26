<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DictionaryService
{
    private $apiKey;
    private $baseUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    public function getDefinition(string $word)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful dictionary assistant. Provide clear and concise definitions.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "다음 단어의 의미를 한국어로 설명해주세요: {$word}"
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 500
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['choices'][0]['message']['content'])) {
                    return [
                        'word' => $word,
                        'definition' => $data['choices'][0]['message']['content'],
                        'source' => 'gpt'
                    ];
                }
            }
            
            return null;
        } catch (\Exception $e) {
            \Log::error('OpenAI API Error: ' . $e->getMessage());
            return null;
        }
    }
} 