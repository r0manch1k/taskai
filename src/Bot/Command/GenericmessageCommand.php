<?php

declare(strict_types=1);

namespace Longman\TelegramBot\Commands\SystemCommands;

use App\Bot\Context;
use App\Bot\Conversation\GenerateCardConversation;
use App\Bot\Conversation\GenerateCardConversationStep;
use App\Bot\Conversation\NewCompanyConversation;
use App\Bot\Conversation\NewCompanyConversationStep;
use App\Bot\Resolver;
use App\Service\BotCacheService;
use App\Service\BotResponseService;
use App\Service\BotUserService;
use App\Service\CompanyService;
use App\Service\KaitenApiClient;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

class GenericmessageCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'genericmessage';

    /**
     * @var string
     */
    protected $description = 'Handle generic message';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * Main command execution.
     *
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        /**
         * @var BotCacheService
         */
        $botCacheService = $this->getConfig()['botCacheService'];

        /**
         * @var BotResponseService
         */
        $botResponseService = $this->getConfig()['botResponseService'];

        /**
         * @var BotUserService
         */
        $botUserService = $this->getConfig()['botUserService'];

        /**
         * @var LoggerInterface
         */
        $logger = $this->getConfig()['logger'];

        /**
         * @var KaitenApiClient
         */
        $kaitenApiClient = $this->getConfig()['kaitenApiClient'];

        /**
         * @var CompanyService
         */
        $companyService = $this->getConfig()['companyService'];

        /**
         * @var MessageBusInterface
         */
        $messageBusInterface = $this->getConfig()['messageBusInterface'];

        $message = $this->getMessage();
        $chat = $message->getChat();
        $user = $message->getFrom();
        $chatId = $chat->getId();
        $userId = $user->getId();
        $text = trim($message->getText(true));
        $botUser = $botUserService->getBotUser($chatId);

        $context = new Context($chatId, $botUser, $this->telegram, $text, $botResponseService, $botCacheService, $botUserService, $logger, $kaitenApiClient, $companyService, $messageBusInterface);

        // На всякий случай готовим сообщение здесь (если какая-нибудь внештатная ситуация)
        $data = [
            'parse_mode' => 'HTML',
            'reply_markup' => Keyboard::remove(),
            'chat_id'      => $chatId,
            'text' => $context->botResponseService->unknown(),
        ];

        // Баг импорта
        new NewCompanyConversation(NewCompanyConversationStep::Start);
        new GenerateCardConversation(GenerateCardConversationStep::Start);

        // Начинаем диалоги, если введено стартовое сообщение для них
        switch ($text) {
            case NewCompanyConversationStep::Start->value:
                $botCacheService->getConversation(
                    $chatId,
                    $userId,
                    new NewCompanyConversation(
                        NewCompanyConversationStep::SetDomain
                    ),
                    true
                );
                break;
            case GenerateCardConversationStep::Start->value:
                $botCacheService->getConversation(
                    $chatId,
                    $userId,
                    new GenerateCardConversation(
                        GenerateCardConversationStep::SetRawDescription
                    ),
                    true
                );
                break;
        }

        $conversation = $botCacheService->getConversation($chatId, $userId);

        if (null === $conversation) {
            $data['text'] = $context->botResponseService->unknown();

            return Request::sendMessage($data);
        }

        $resolver = new Resolver();

        try {
            $state = $resolver->resolve($conversation);
        } catch (Throwable $e) {
            $logger->error(sprintf('Ошибка при резолве диалога: %s', $e->getMessage()));
            $data['text'] = $context->botResponseService->error();

            return Request::sendMessage($data);
        }

        try {
            $response = $state->handle($context, $conversation);
        } catch (Throwable $e) {
            $logger->error(sprintf('Ошибка при хэндле сообщения: %s', $e->getMessage()));
            $data['text'] = $context->botResponseService->error();

            return Request::sendMessage($data);
        }

        return $response;
    }
}
