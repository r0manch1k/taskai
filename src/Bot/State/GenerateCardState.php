<?php

declare(strict_types=1);

namespace App\Bot\State;

use App\Bot\Context;
use App\Bot\Conversation\Conversation;
use App\Bot\Conversation\GenerateCardConversation;
use App\Bot\Conversation\GenerateCardConversationStep;
use App\Bot\Conversation\SelectBoardConversation;
use App\Bot\Conversation\SelectBoardConversationStep;
use InvalidArgumentException;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

final class GenerateCardState implements StateInterface
{
    public function handle(Context $context, Conversation $conversation): ServerResponse
    {
        if (!$conversation instanceof GenerateCardConversation) {
            throw new InvalidArgumentException('Ожидалось GenerateCardConversation');
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
            // Сообщение с просьбой описать задачу
            case GenerateCardConversationStep::SetRawDescription:
                $data['text'] = $context->brs->generateCard($conversation);

                $context->bcs->getConversation(
                    $chatId,
                    $userId,
                    new GenerateCardConversation(
                        GenerateCardConversationStep::SetAsap,
                    ),
                    true
                );

                return Request::sendMessage($data);

            // Сообщение с просьбой указать, является ли задача срочной
            case GenerateCardConversationStep::SetAsap:
                $rawDescription = $text;

                if (mb_strlen($text) < 15) {
                    $data['text'] = 'Слишком короткое описание задачи';

                    return Request::sendMessage($data);
                }

                if (mb_strlen($text) > 2000) {
                    $data['text'] = 'Описание слишком длинное';

                    return Request::sendMessage($data);
                }

                $data['text'] = $context->brs->generateCard($conversation);

                $buttons = ['Да', 'Нет'];

                /**
                 * @psalm-suppress TooManyArguments
                 */
                $keyboard = new Keyboard(...$buttons);

                $data['reply_markup'] = $keyboard;

                $context->bcs->getConversation(
                    $chatId,
                    $userId,
                    new GenerateCardConversation(
                        GenerateCardConversationStep::SetAsap,
                        $rawDescription
                    ),
                    true
                );

                return Request::sendMessage($data);
            
            // Сообщение с просьбой указать дедлайн
            case GenerateCardConversationStep::SetDueDate:
                $asap = $text;

                if ($asap == 'Да') {
                    $context->bcs->getConversation(
                        $chatId,
                        $userId,
                        new GenerateCardConversation(
                            GenerateCardConversationStep::SetAsap,
                            $conversation->rawDescription,
                            true
                        ),
                        true
                    );
                } else if ($asap == 'Нет') {
                    $context->bcs->getConversation(
                        $chatId,
                        $userId,
                        new GenerateCardConversation(
                            GenerateCardConversationStep::SetAsap,
                            $conversation->rawDescription,
                            false
                        ),
                        true
                    );
                } else {
                    $data['text'] = 'Выберите <i>Да</i> или <i>Нет</i>';

                    return Request::sendMessage($data);
                }

                $data['text'] = $context->brs->generateCard($conversation);

                return Request::sendMessage($data);

        }

        $data['text'] = $context->brs->unknown();

        return Request::sendMessage($data);
    }
}
