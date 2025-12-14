<?php

declare(strict_types=1);

namespace Longman\TelegramBot\Commands\SystemCommands;

use App\Bot\Conversation\NewCompanyConversation;
use App\Bot\Conversation\NewCompanyConversationStep;
use App\Bot\Conversation\SelectBoardConversation;
use App\Bot\Conversation\SelectBoardConversationStep;
use App\Service\BotCacheService;
use App\Service\BotResponseService;
use App\Service\BotUserService;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

class StartCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'start';

    /**
     * @var string
     */
    protected $description = 'Start command';

    /**
     * @var string
     */
    protected $usage = '/start';

    /**
     * @var string
     */
    protected $version = '1.2.0';

    /**
     * @var bool
     */
    protected $private_only = false;

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
         * @var BotUserService
         */
        $bus = $this->getConfig()['bus'];

        /**
         * @var BotResponseService
         */
        $brs = $this->getConfig()['brs'];

        $message = $this->getMessage();

        $chat    = $message->getChat();
        $user    = $message->getFrom();
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        $bcs->invalidateBotUser($chat_id, $user_id);

        $botUser = $bus->getBotUser($user_id);
        $companies = $botUser->getCompanies();

        $buttons = [];
        foreach ($companies as $company) {
            $buttons[] = [$company->getDomain()];
        }

        // Почему-то без импорта NewCompanyConversation не видит NewCompanyConversationStep
        new NewCompanyConversation(NewCompanyConversationStep::Start);

        $buttons[] = [NewCompanyConversationStep::Start->value];

        /**
         * @psalm-suppress TooManyArguments
         */
        $keyboard = new Keyboard(...$buttons);

        $keyboard->setResizeKeyboard(true);
        $keyboard->setOneTimeKeyboard(false);
        $keyboard->setSelective(false);

        // По умолчанию ожидается, что существует корпоративный аккаунт и
        // поэтому начинается диалог выбора доски, но если пользователь
        // выбирает опцию добавления новаого аккаунта, то диалог в
        // кэше перезапишется в GenericmessageCommand
        $bcs->getConversation(
            $chat_id,
            $user_id,
            new SelectBoardConversation(
                SelectBoardConversationStep::SetSpace
            ),
            true
        );

        return $this->replyToChat($brs->start(), [
            'reply_markup' => $keyboard,
            'parse_mode' => 'HTML',
        ]);
    }
}
