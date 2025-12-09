<?php

declare(strict_types=1);

namespace Longman\TelegramBot\Commands\SystemCommands;

use App\Bot\Conversation\NewCompanyConversation;
use App\Bot\Conversation\NewCompanyConversationStep;
use App\Bot\Conversation\SelectBoardConversation;
use App\Bot\Conversation\SelectBoardConversationStep;
use App\Dto\BoardDto;
use App\Entity\Company;
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
                    ),
                    true
                );
                break;
        }

        $conversation = $bcs->getConversation($chat_id, $user_id);

        if ($conversation instanceof SelectBoardConversation) {
            switch ($conversation->step) {
                case SelectBoardConversationStep::SetSpace:
                    $domain = $text;

                    $company = $botUser->isCompanyExists($domain);

                    if (null === $company) {
                        $data['text'] = $brs->error();

                        $logger->error('Комания - нулл');

                        return Request::sendMessage($data);
                    }

                    $bus->setCompanyId($botUser, $company->getId());

                    $spaces = $kas->getSpaces($botUser);

                    if (empty($spaces)) {
                        $data['text'] = 'У вас нет пространств. Создайте и попробуйте ещё раз';

                        return Request::sendMessage($data);
                    }

                    $data['text'] = $brs->selectCompany($conversation);

                    $buttons = [];

                    foreach ($spaces as $space) {
                        $buttons[] = [$space->title];
                    }

                    /**
                     * @psalm-suppress TooManyArguments
                     */
                    $keyboard = new Keyboard(...$buttons);

                    $data['reply_markup'] = $keyboard;

                    $bcs->getConversation(
                        $chat_id,
                        $user_id,
                        new SelectBoardConversation(
                            SelectBoardConversationStep::SetBoard,
                            $spaces
                        ),
                        true
                    );

                    return Request::sendMessage($data);

                case SelectBoardConversationStep::SetBoard:
                    $spaces = $conversation->spaces;

                    $space = null;
                    foreach ($spaces as $s) {
                        if ($s->title === $text) {
                            $space = $s;
                            break;
                        }
                    }

                    $company = $cs->getCompany($botUser->getCompanyId());

                    if (null === $space) {
                        $data['text'] = 'Указанное пространство не найдено. Попробуйте ещё раз.';

                        return Request::sendMessage($data);
                    }

                    $cs->setSpaceId($company, $space->id);

                    $boards = $kas->getBoards($botUser);

                    if (empty($boards)) {
                        $data['text'] = 'У вас нет досок. Создайте и попробуйте ещё раз';

                        return Request::sendMessage($data);
                    }

                    $data['text'] = $brs->selectCompany($conversation);

                    $buttons = [];

                    foreach ($boards as $board) {
                        $buttons[] = [$board->title];
                    }

                    /**
                     * @psalm-suppress TooManyArguments
                     */
                    $keyboard = new Keyboard(...$buttons);

                    $data['reply_markup'] = $keyboard;

                    $bcs->getConversation(
                        $chat_id,
                        $user_id,
                        new SelectBoardConversation(
                            SelectBoardConversationStep::Done,
                            $spaces,
                            $boards
                        ),
                        true
                    );

                    return Request::sendMessage($data);

                case SelectBoardConversationStep::Done:
                    $boards = $conversation->boards;

                    $board = null;
                    foreach ($boards as $b) {
                        if ($b->title === $text) {
                            $board = $b;
                            break;
                        }
                    }

                    $company = $cs->getCompany($botUser->getCompanyId());

                    if (null === $board) {
                        $data['text'] = 'Указанная доска не найдена. Попробуйте ещё раз.';

                        return Request::sendMessage($data);
                    }

                    $cs->setBoardId($company, $board->id);

                    $data['text'] = $brs->selectCompany($conversation);

                    $buttons = ['Создать задачу'];

                    /**
                     * @psalm-suppress TooManyArguments
                     */
                    $keyboard = new Keyboard(...$buttons);

                    $data['reply_markup'] = $keyboard;

                    $bcs->getConversation($chat_id, $user_id, null, true);

                    return Request::sendMessage($data);

            }
        } elseif ($conversation instanceof NewCompanyConversation) {
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
                    $token = $text;

                    $ok = $kas->ping($conversation->domain, $token);

                    if (!$ok) {
                        $data['text'] = 'Ключ доступа не прошёл проверку. Введите другой!';

                        return Request::sendMessage($data);
                    }

                    $company = $botUser->isCompanyExists($conversation->domain);

                    if (null !== $company) {
                        $company->setToken($token);
                    } else {
                        $company = new Company();
                        $company->setDomain($conversation->domain);
                        $company->setToken($token);
                    }

                    $bus->setCompany($botUser, $company);

                    $bcs->getConversation($chat_id, $user_id, null, true);

                    $this->telegram->executeCommand('start');

                    return Request::emptyResponse();

            }
        }

        $data['text'] = $brs->unknown();

        return Request::sendMessage($data);
    }
}
