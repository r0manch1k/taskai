<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Throwable;

class KaitenApiService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function ping(string $domain, string $token): bool
    {
        $client = HttpClient::create();

        $url = sprintf('https://%s.kaiten.ru/api/v1/spaces', $domain);

        try {
            $response = $client->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept'        => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 5,
            ]);

            if (200 != $response->getStatusCode()) {
                $this->logger->warning(sprintf('Не удалось успешно проверить токен! Статус ответа: %s', $response->getStatusCode()));

                return false;
            }

            return true;

        } catch (Throwable $e) {
            $this->logger->error(sprintf('Ошибка при получении ответа с %s', $url));

            return false;
        }
    }
}
