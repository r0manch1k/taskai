<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenRouterApiService
{
    public function __construct(
        private LoggerInterface $logger,
        private HttpClientInterface $http,
        private string $apiKey,
    ) {
    }

    public function chat(string $prompt): string
    {
        $response = $this->http->request(
            'POST',
            'https://openrouter.ai/api/v1/chat/completions',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'deepseek/deepseek-chat',
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.2,
                ],
            ]
        );

        $data = $response->toArray();

        return $data['choices'][0]['message']['content'] ?? '';
    }
}
