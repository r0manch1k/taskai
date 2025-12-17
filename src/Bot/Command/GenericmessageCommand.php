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
use App\Service\KaitenApiService;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

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
        $bcs = $this->getConfig()['bcs'];

        /**
         * @var BotResponseService
         */
        $brs = $this->getConfig()['brs'];

        /**
         * @var BotUserService
         */
        $bus = $this->getConfig()['bus'];

        /**
         * @var LoggerInterface
         */
        $logger = $this->getConfig()['logger'];

        /**
         * @var KaitenApiService
         */
        $kas = $this->getConfig()['kas'];

        /**
         * @var CompanyService
         */
        $cs = $this->getConfig()['cs'];

        /**
         * @var MessageBusInterface
         */
        $mbus = $this->getConfig()['mbus'];

        $message = $this->getMessage();
        $chat = $message->getChat();
        $user = $message->getFrom();
        $chatId = $chat->getId();
        $userId = $user->getId();
        $text = trim($message->getText(true));
        $botUser = $bus->getBotUser($chatId);

        $context = new Context($chatId, $botUser, $this->telegram, $text, $brs, $bcs, $bus, $logger, $kas, $cs, $mbus);

        // Баг импорта
        new NewCompanyConversation(NewCompanyConversationStep::Start);
        new GenerateCardConversation(GenerateCardConversationStep::Start);

        // Начинаем диалоги, если введено стартовое сообщение для них
        switch ($text) {
            case NewCompanyConversationStep::Start->value:
                $bcs->getConversation(
                    $chatId,
                    $userId,
                    new NewCompanyConversation(
                        NewCompanyConversationStep::SetDomain
                    ),
                    true
                );
                break;
            case GenerateCardConversationStep::Start->value:
                $bcs->getConversation(
                    $chatId,
                    $userId,
                    new GenerateCardConversation(
                        GenerateCardConversationStep::SetRawDescription
                    ),
                    true
                );
                break;
        }

        $conversation = $bcs->getConversation($chatId, $userId);

        if (null === $conversation) {
            $data = [
                'parse_mode' => 'HTML',
                'reply_markup' => Keyboard::remove(),
                'chat_id'      => $chatId,
                'text' => $context->brs->unknown(),
            ];

            return Request::sendMessage($data);
        }

        $resolver = new Resolver();

        $state = $resolver->resolve($conversation);

        return $state->handle($context, $conversation);
    }
}
