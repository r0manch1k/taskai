<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\BoardDto;
use App\Dto\SpaceDto;
use App\Dto\UserDto;
use App\Entity\BotUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Throwable;

class KaitenApiService
{
    public function __construct(
        private LoggerInterface $logger,
        private CompanyService $cs,
    ) {
    }

    public function getCurrentUser(string $domain, string $token): UserDto
    {
        $client = HttpClient::create();

        $url = sprintf('https://%s.kaiten.ru/api/v1/users/current', $domain);

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

                return new UserDto(null, null);
            }

            $data = $response->toArray();

            $user = new UserDto(
                    id: $data['id'] ?? 0,
                    email: $data['email'] ?? '',
                );

            return $user;

        } catch (Throwable $e) {
            $this->logger->error(sprintf('Ошибка при получении ответа с %s', $url));

            return new UserDto(null, null);
        }
    }

    /**
     * Summary of getSpaces.
     *
     * @return SpaceDto[]
     */
    public function getSpaces(BotUser $botUser): array
    {
        $companyId = $botUser->getCompanyId();
        if (null === $companyId) {
            $this->logger->warning('У пользователя нет выбранной компании.');

            return [];
        }

        $company = $this->cs->getCompany($companyId);

        $domain = $company->getDomain();
        $token = $company->getToken();

        if (empty($token)) {
            $this->logger->error('Пустой токен');

            return [];
        }

        $client = HttpClient::create();
        $url = sprintf('https://%s.kaiten.ru/api/latest/spaces', $domain);

        try {
            $response = $client->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ],
                'timeout' => 5,
            ]);

            if (200 !== $response->getStatusCode()) {
                $this->logger->warning(sprintf('Не удалось получить Spaces. Код ответа: %s', $response->getStatusCode()));

                return [];
            }

            $data = $response->toArray();

            $spaces = [];
            foreach ($data as $space) {
                $spaces[] = new SpaceDto(
                    id: $space['id'] ?? 0,
                    title: $space['title'] ?? '',
                    created: $space['created'] ?? ''
                );
            }

            return $spaces;

        } catch (Throwable $e) {
            $this->logger->error(sprintf('Ошибка при запросе %s: %s', $url, $e->getMessage()));

            return [];
        }
    }

    /**
     * Summary of getBoards.
     *
     * @return BoardDto[]
     */
    public function getBoards(BotUser $botUser): array
    {
        $companyId = $botUser->getCompanyId();
        if (null === $companyId) {
            $this->logger->warning('У пользователя нет выбранной компании.');

            return [];
        }

        $company = $this->cs->getCompany($companyId);

        $spaceId = $company->getSpaceId();
        if (null === $spaceId) {
            $this->logger->warning('У пользователя в выбранной компании нет выбранного пространства.');

            return [];
        }

        $domain = $company->getDomain();
        $token = $company->getToken();

        if (empty($token)) {
            $this->logger->error('Пустой токен');

            return [];
        }

        $client = HttpClient::create();
        $url = sprintf('https://%s.kaiten.ru/api/latest/spaces/%s/boards', $domain, $spaceId);

        try {
            $response = $client->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ],
                'timeout' => 5,
            ]);

            if (200 !== $response->getStatusCode()) {
                $this->logger->warning(sprintf('Не удалось получить Boards. Код ответа: %s', $response->getStatusCode()));

                return [];
            }

            $data = $response->toArray();

            /**
             * @var BoardDto[]
             */
            $boards = [];
            foreach ($data as $board) {
                $boards[] = new BoardDto(
                    id: $board['id'] ?? 0,
                    title: $board['title'] ?? '',
                    description: $board['created'] ?? ''
                );
            }

            return $boards;

        } catch (Throwable $e) {
            $this->logger->error(sprintf('Ошибка при запросе %s: %s', $url, $e->getMessage()));

            return [];
        }
    }
}
