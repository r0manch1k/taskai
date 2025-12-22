<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\BoardDto;
use App\Dto\CardDto;
use App\Dto\SpaceDto;
use App\Dto\TagDto;
use App\Dto\UserDto;
use App\Entity\BotUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Throwable;

class KaitenApiClient
{
    public function __construct(
        private LoggerInterface $logger,
        private CompanyService $companyService,
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

        $company = $this->companyService->getCompany($companyId);

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

        $company = $this->companyService->getCompany($companyId);

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

    /**
     * Summary of getSpaceUsers.
     *
     * @return UserDto[]
     */
    public function getSpaceUsers(BotUser $botUser): array
    {
        $companyId = $botUser->getCompanyId();
        if (null === $companyId) {
            $this->logger->warning('У пользователя нет выбранной компании.');

            return [];
        }

        $company = $this->companyService->getCompany($companyId);

        $spaceId = $company->getSpaceId();
        if (null === $spaceId) {
            $this->logger->warning('У пользователя в выбранной компании нет выбранного пространства.');

            return [];
        }

        $domain = $company->getDomain();
        $token  = $company->getToken();

        if (empty($token)) {
            $this->logger->error('Пустой токен');

            return [];
        }

        $client = HttpClient::create();
        $url = sprintf('https://%s.kaiten.ru/api/latest/spaces/%s/users', $domain, $spaceId);

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
                $this->logger->warning(sprintf(
                    'Не удалось получить пользователей пространства. Код ответа: %s',
                    $response->getStatusCode()
                ));

                return [];
            }

            $data = $response->toArray();

            /**
             * @var UserDto[]
             */
            $users = [];
            foreach ($data as $user) {
                $users[] = new UserDto(
                    id: $user['id'] ?? 0,
                    email: $user['email'] ?? '',
                );
            }

            return $users;

        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Ошибка при запросе %s: %s',
                $url,
                $e->getMessage()
            ));

            return [];
        }
    }

    /**
     * Summary of getTags.
     *
     * @return TagDto[]
     */
    public function getTags(BotUser $botUser): array
    {
        $companyId = $botUser->getCompanyId();
        if (null === $companyId) {
            $this->logger->warning('У пользователя нет выбранной компании.');

            return [];
        }

        $company = $this->companyService->getCompany($companyId);

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
        $url = sprintf('https://%s.kaiten.ru/api/latest/tags', $domain);

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
                $this->logger->warning(sprintf('Не удалось получить Tags. Код ответа: %s', $response->getStatusCode()));

                return [];
            }

            $data = $response->toArray();

            /**
             * @var TagDto[]
             */
            $tags = [];
            foreach ($data as $tag) {
                $tags[] = new TagDto(
                    id: $tag['id'] ?? 0,
                    name: $tag['name'] ?? '',
                );
            }

            return $tags;

        } catch (Throwable $e) {
            $this->logger->error(sprintf('Ошибка при запросе %s: %s', $url, $e->getMessage()));

            return [];
        }
    }

    public function createCard(BotUser $botUser, CardDto $cardDto): CardDto
    {
        $companyId = $botUser->getCompanyId();
        if (null === $companyId) {
            $this->logger->warning('У пользователя нет выбранной компании.');

            return $cardDto;
        }

        $company = $this->companyService->getCompany($companyId);

        $spaceId = $company->getSpaceId();
        if (null === $spaceId) {
            $this->logger->warning('У пользователя в выбранной компании нет выбранного пространства.');

            return $cardDto;
        }

        $domain = $company->getDomain();
        $token = $company->getToken();

        if (empty($token)) {
            $this->logger->error('Пустой токен');

            return $cardDto;
        }

        $client = HttpClient::create();
        $url = sprintf('https://%s.kaiten.ru/api/latest/cards', $domain);

        $postData = [
            'title' => $cardDto->title,
            'board_id' => $cardDto->boardId,
            'asap' => $cardDto->asap,
            'due_date' => $cardDto->dueDate,
            'due_date_time_present' => $cardDto->dueDateTimePresent,
            'description' => $cardDto->description,
            'owner_id' => $cardDto->ownerId,
            'responsible_id' => $cardDto->responsibleId,
        ];

        try {
            $response = $client->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ],
                'json' => $postData,
                'timeout' => 5,
            ]);

            if (200 !== $response->getStatusCode()) {
                $this->logger->warning(sprintf('Не удалось создать карточку. Код ответа: %s', $response->getStatusCode()));

                return $cardDto;
            }

            $data = $response->toArray();

            return new CardDto(
                id: $data['id'] ?? null,
                title: $cardDto->title,
                boardId: $cardDto->boardId,
                asap: $cardDto->asap,
                dueDate: $cardDto->dueDate,
                dueDateTimePresent: $cardDto->dueDateTimePresent,
                description: $cardDto->description,
                ownerId: $cardDto->ownerId,
                responsibleId: $cardDto->responsibleId,
            );

        } catch (Throwable $e) {
            $this->logger->error(sprintf('Ошибка при создании карточки %s: %s', $url, $e->getMessage()));

            return $cardDto;
        }

    }

    public function createCardTag(BotUser $botUser, int $cardId, string $tag): string
    {
        $companyId = $botUser->getCompanyId();
        if (null === $companyId) {
            $this->logger->warning('У пользователя нет выбранной компании.');

            return '';
        }

        $company = $this->companyService->getCompany($companyId);

        $spaceId = $company->getSpaceId();
        if (null === $spaceId) {
            $this->logger->warning('У пользователя в выбранной компании нет выбранного пространства.');

            return '';
        }

        $domain = $company->getDomain();
        $token = $company->getToken();

        if (empty($token)) {
            $this->logger->error('Пустой токен');

            return '';
        }

        $client = HttpClient::create();

        $url = sprintf('https://%s.kaiten.ru/api/cards/%d/tags', $domain, $cardId);

        $postData = [
            'name' => $tag,
        ];

        try {
            $response = $client->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ],
                'json' => $postData,
                'timeout' => 5,
            ]);

            if (200 !== $response->getStatusCode()) {
                $this->logger->warning(sprintf(
                    'Не удалось добавить тег "%s" к карточке %d. Код ответа: %s',
                    $tag,
                    $cardId,
                    $response->getStatusCode()
                ));

                return '';
            }

            $data = $response->toArray(false);

            return (string) ($data['name'] ?? $tag);

        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Ошибка при добавлении тега "%s" к карточке %d: %s',
                $tag,
                $cardId,
                $e->getMessage()
            ));

            return '';
        }
    }

    public function getCardUrl(BotUser $botUser, int $spaceId, int $cardId): string
    {
        $companyId = $botUser->getCompanyId();
        if (null === $companyId) {
            $this->logger->warning('У пользователя нет выбранной компании.');

            return '';
        }

        $company = $this->companyService->getCompany($companyId);

        $domain = $company->getDomain();

        $url = sprintf('https://%s.kaiten.ru/space/%s/boards/card/%s', $domain, $spaceId, $cardId);

        return $url;
    }
}
