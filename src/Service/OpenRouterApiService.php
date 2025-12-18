<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

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
        try {
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

            $statusCode = $response->getStatusCode();
            if (200 !== $statusCode) {
                $this->logger->error('OpenRouter API вернул некорректный HTTP-статус', [
                    'status_code' => $statusCode,
                ]);

                return '';
            }

            $data = $response->toArray(false);

            if (
                !isset($data['choices'][0]['message']['content'])
                || !is_string($data['choices'][0]['message']['content'])
            ) {
                $this->logger->error('Неожиданный формат ответа от OpenRouter API', [
                    'response' => $data,
                ]);

                return '';
            }

            return $data['choices'][0]['message']['content'];
        } catch (Throwable $e) {
            $this->logger->error('Ошибка при выполнении запроса к OpenRouter API', [
                'exception' => $e,
            ]);

            return '';
        }
    }
}
