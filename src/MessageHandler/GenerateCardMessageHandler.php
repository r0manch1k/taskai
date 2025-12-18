<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Bot\Bot;
use App\Message\GenerateCardMessage;
use App\Service\CompanyService;
use App\Service\KaitenApiService;
use App\Service\OpenRouterApiService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;

#[AsMessageHandler]
class GenerateCardMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private KaitenApiService $kas,
        private Environment $twig,
        private OpenRouterApiService $oras,
        private CompanyService $cs,
        private HttpClientInterface $http,
        private Bot $bot,
    ) {
    }

    public function __invoke(GenerateCardMessage $message)
    {
        $tags = $this->kas->getTags($message->getBotUser());

        $this->logger->info('Начинаем формирование промпта для ИИ');
        $prompt = $this->twig->render('prompts/generate_card.html.twig', [
            'rawDescription' => $message->getRawDescription(),
            'tags' => $tags,
        ]);

        $content = $this->oras->chat($prompt);
        $this->logger->info('Получен ответ от ИИ');

        $data = json_decode($content, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            $this->logger->error('Ошибка декодирования JSON', [
                'error' => json_last_error_msg(),
                'content' => $content,
            ]);

            return;
        }
        $this->logger->info('Ответ от ИИ декодирован из JSON');

        $requiredFields = ['title', 'description'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->logger->error("Отсутствует необходимое поле '{$field}' в ответе ИИ", [
                    'response' => $data,
                ]);

                return;
            }
        }

        $cardDto = new \App\Dto\CardDto(
            id: null,
            title: (string) $data['title'],
            boardId: $this->cs->getCompany($message->getBotUser()->getCompanyId())->getBoardId(),
            asap: $message->getAsap(),
            dueDate: $message->getDueDate(),
            dueDateTimePresent: $message->getDueDateTimePresent(),
            description: (string) $data['description'],
            ownerId: $message->getOwnerId(),
            responsibleId: $message->getResponsibleId(),
        );

        $createdCard = $this->kas->createCard(
            $message->getBotUser(),
            $cardDto
        );

        if (null === $createdCard->id) {
            $this->logger->error('Карточка не была создана в Kaiten', [
                'card' => $createdCard,
            ]);

            return;
        }
        $this->logger->info('Карточка успешно создана в Kaiten');

        $url = $this->kas->getCardUrl($message->getBotUser(), $message->getSpaceId(), $message->getChatId());

        $this->bot->sendMessage($message->getChatId(), sprintf('Генерация задачи завершена. Вот ссылка: %s', $url));

        if (empty($data['tag'])) {
            $this->logger->info('Тэг отсутствует. Пропускаем шаг.');

            return;
        }

        $cratedTag = $this->kas->createCardTag(
            $message->getBotUser(),
            $createdCard->id,
            $data['tag']
        );

        if (empty($cratedTag)) {
            $this->logger->error('Тэг не был добавлен в Kaiten');

            return;
        }
        $this->logger->info('Тэг успешно добавлен в Kaiten');
    }
}
