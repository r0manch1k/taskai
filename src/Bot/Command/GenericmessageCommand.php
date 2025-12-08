<?php

declare(strict_types=1);

namespace Longman\TelegramBot\Commands\SystemCommands;

use App\Bot\Conversation\NewCompanyConversation;
use App\Bot\Conversation\NewCompanyConversationStep;
use App\Entity\Company;
use App\Service\BotCacheService;
use App\Service\BotResponseService;
use App\Service\BotUserService;
use App\Service\KaitenApiService;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Psr\Log\LoggerInterface;

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

        $message = $this->getMessage();

        $chat    = $message->getChat();
        $user    = $message->getFrom();
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        $text = trim($message->getText(true));

        // Вызываем заранее, так как далее часто будет использоваться
        $botUser = $bus->getBotUser($user_id);

        $data = [
            'chat_id'      => $chat_id,
            'parse_mode' => 'HTML',
            'reply_markup' => Keyboard::remove(),
        ];

        // Почему-то без импорта NewCompanyConversation не видит
        // NewCompanyConversationStep (пхп мОмент)
        new NewCompanyConversation(NewCompanyConversationStep::Start);

        switch ($text) {
            case NewCompanyConversationStep::Start->value:
                $bcs->getConversation(
                    $chat_id,
                    $user_id,
                    new NewCompanyConversation(
                        NewCompanyConversationStep::SetDomain
                    )
                );
                break;
        }

        $conversation = $bcs->getConversation($chat_id, $user_id);

        if ($conversation instanceof NewCompanyConversation) {
            switch ($conversation->step) {
                case NewCompanyConversationStep::SetDomain:
                    $data['text'] = $brs->newCompany($conversation);

                    $bcs->getConversation(
                        $chat_id,
                        $user_id,
                        new NewCompanyConversation(
                            NewCompanyConversationStep::SetToken,
                        ),
                        true
                    );

                    return Request::sendMessage($data);

                case NewCompanyConversationStep::SetToken:
                    $domain = $text;

                    if (!preg_match('/^[a-zA-Z0-9._+-]+$/', $domain)) {
                        $data['text'] = 'Невалидный домен. Попробуйте ещё раз!';

                        $logger->info(sprintf('Пользователь %s ввёл невалидный домен "%s"', $user_id, $domain));

                        return Request::sendMessage($data);
                    }

                    $data['text'] = $brs->newCompany($conversation);

                    $bcs->getConversation(
                        $chat_id,
                        $user_id,
                        new NewCompanyConversation(
                            NewCompanyConversationStep::Done,
                            $domain
                        ),
                        true
                    );

                    return Request::sendMessage($data);

                case NewCompanyConversationStep::Done:
                    $token = trim($text);

                    $ok = $kas->ping($conversation->domain, $token);

                    if (!$ok) {
                        $data['text'] = 'Ключ доступа не прошёл проверку. Введите другой!';

                        return Request::sendMessage($data);
                    }

                    /**
                     * @var Company
                     */
                    $company = null;

                    foreach ($botUser->getCompanies() as $c) {
                        if ($c->getDomain() === $conversation->domain) {
                            $company = $c;
                            break;
                        }
                    }

                    if (null !== $company) {
                        $company->setToken($token);
                    } else {
                        $company = new Company();
                        $company->setDomain($conversation->domain);
                        $company->setToken($conversation->token);
                    }

                    $bus->addCompanyToUser($botUser, $company);

                    $this->telegram->executeCommand('start');

            }
        }

        return Request::emptyResponse();
    }
}
