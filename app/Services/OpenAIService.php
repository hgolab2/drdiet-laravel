<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAIService
{
    public function chat(array $messages)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openai.key'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4.1-mini',
            'messages' => $messages,
            'temperature' => 0.7,
        ]);

        return $response->json()['choices'][0]['message']['content'] ?? null;
    }
    public function generateImage(string $prompt)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openai.key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/images/generations', [
            'model' => 'gpt-image-1',
            'prompt' => $prompt,
            'size' => '1024x1024s',
        ]);

        return $response->json('data.0.url') ?? null;
    }

}
