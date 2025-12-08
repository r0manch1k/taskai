<?php

declare(strict_types=1);

namespace Longman\TelegramBot\Commands\SystemCommands;

use App\Bot\Conversation\NewCompanyConversation;
use App\Bot\Conversation\NewCompanyConversationStep;
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

        $message = $this->getMessage();

        $chat    = $message->getChat();
        $user    = $message->getFrom();
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        $bcs->invalidateBotUser($chat_id, $user_id);

        /**
         * @var BotUserService
         */
        $bus = $this->getConfig()['bus'];

        $botUser = $bus->getBotUser($user_id);
        $companies = $botUser->getCompanies();

        $buttons = [];
        foreach ($companies as $company) {
            $buttons[] = [$company->getDomain()];
        }

        // Почему-то без импорта NewCompanyConversation не видит
        // NewCompanyConversationStep (пхп мОмент)
        new NewCompanyConversation(NewCompanyConversationStep::Start);

        $buttons[] = [NewCompanyConversationStep::Start->value];

        /**
         * @psalm-suppress TooManyArguments
         */
        $keyboard = new Keyboard(...$buttons);

        $keyboard->setResizeKeyboard(true);
        $keyboard->setOneTimeKeyboard(false);
        $keyboard->setSelective(false);

        /**
         * @var BotResponseService
         */
        $brs = $this->getConfig()['brs'];

        return $this->replyToChat($brs->start(), [
            'reply_markup' => $keyboard,
            'parse_mode' => 'HTML',
        ]);
    }
}
