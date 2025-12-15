<?php

declare(strict_types=1);

namespace App\Bot\State;

use App\Bot\Context;
use App\Bot\Conversation\Conversation;
use App\Bot\Conversation\GenerateCardConversationStep;
use App\Bot\Conversation\SelectBoardConversation;
use App\Bot\Conversation\SelectBoardConversationStep;
use InvalidArgumentException;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

final class SelectBoardState implements StateInterface
{
    public function handle(Context $context, Conversation $conversation): ServerResponse
    {
        if (!$conversation instanceof SelectBoardConversation) {
            throw new InvalidArgumentException('Ожидалось SelectBoardConversation');
        }

        $chatId = $context->chatId;
        $userId = $context->botUser->getId();
        $text = $context->text;

        $data = [
            'parse_mode' => 'HTML',
            'reply_markup' => Keyboard::remove(),
            'chat_id'      => $chatId,
        ];

        switch ($conversation->step) {
            // Сообщение с просьбой указать пространство
            case SelectBoardConversationStep::SetSpace:
                $domain = $text;

                $company = $context->botUser->isCompanyExists($domain);

                if (null === $company) {
                    $context->logger->error('Комания - нулл');
                    $data['text'] = $context->brs->error();

                    return Request::sendMessage($data);
                }

                $context->bus->setCompanyId($context->botUser, $company->getId());

                $spaces = $context->kas->getSpaces($context->botUser);

                if (empty($spaces)) {
                    $data['text'] = 'У вас нет пространств. Создайте и попробуйте ещё раз';

                    return Request::sendMessage($data);
                }

                $data['text'] = $context->brs->selectCompany($conversation);

                $buttons = [];
                foreach ($spaces as $space) {
                    $buttons[] = [$space->title];
                }

                /**
                 * @psalm-suppress TooManyArguments
                 */
                $keyboard = new Keyboard(...$buttons);

                $data['reply_markup'] = $keyboard;

                $context->bcs->getConversation(
                    $chatId,
                    $userId,
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

                $company = $context->cs->getCompany($context->botUser->getCompanyId());

                if (null === $space) {
                    $data['text'] = 'Указанное пространство не найдено. Попробуйте ещё раз.';

                    $buttons = [];
                    foreach ($spaces as $space) {
                        $buttons[] = [$space->title];
                    }

                    /**
                     * @psalm-suppress TooManyArguments
                     */
                    $keyboard = new Keyboard(...$buttons);

                    $data['reply_markup'] = $keyboard;

                    return Request::sendMessage($data);
                }

                $context->cs->setSpaceId($company, $space->id);

                $boards = $context->kas->getBoards($context->botUser);

                if (empty($boards)) {
                    $data['text'] = 'У вас нет досок. Создайте и попробуйте ещё раз';

                    return Request::sendMessage($data);
                }

                $data['text'] = $context->brs->selectCompany($conversation);

                $buttons = [];
                foreach ($boards as $board) {
                    $buttons[] = [$board->title];
                }

                /**
                 * @psalm-suppress TooManyArguments
                 */
                $keyboard = new Keyboard(...$buttons);

                $data['reply_markup'] = $keyboard;

                $context->bcs->getConversation(
                    $chatId,
                    $userId,
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

                $company = $context->cs->getCompany($context->botUser->getCompanyId());

                if (null === $board) {
                    $data['text'] = 'Указанная доска не найдена. Попробуйте ещё раз.';

                    $buttons = [];
                    foreach ($boards as $board) {
                        $buttons[] = [$board->title];
                    }

                    /**
                     * @psalm-suppress TooManyArguments
                     */
                    $keyboard = new Keyboard(...$buttons);

                    $data['reply_markup'] = $keyboard;

                    return Request::sendMessage($data);
                }

                $context->cs->setBoardId($company, $board->id);

                $data['text'] = $context->brs->selectCompany($conversation);

                $buttons = [GenerateCardConversationStep::Start->value];

                /**
                 * @psalm-suppress TooManyArguments
                 */
                $keyboard = new Keyboard(...$buttons);

                $data['reply_markup'] = $keyboard;

                $context->bcs->getConversation($chatId, $userId, null, true);

                return Request::sendMessage($data);

        }

        $data['text'] = $context->brs->unknown();

        return Request::sendMessage($data);
    }
}
