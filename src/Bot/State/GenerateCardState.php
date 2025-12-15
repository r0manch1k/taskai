<?php

declare(strict_types=1);

namespace App\Bot\State;

use App\Bot\Context;
use App\Bot\Conversation\Conversation;
use App\Bot\Conversation\GenerateCardConversation;
use App\Bot\Conversation\GenerateCardConversationStep;
use DateTimeImmutable;
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
                        GenerateCardConversationStep::SetDueDate,
                        $rawDescription
                    ),
                    true
                );

                return Request::sendMessage($data);

                // Сообщение с просьбой указать дедлайн
            case GenerateCardConversationStep::SetDueDate:
                $asap = $text;

                if ('Да' == $asap) {
                    $asap = true;
                } elseif ('Нет' == $asap) {
                    $asap = false;
                } else {
                    $data['text'] = 'Выберите <i>Да</i> или <i>Нет</i>';

                    return Request::sendMessage($data);
                }

                $data['text'] = $context->brs->generateCard($conversation);

                $context->bcs->getConversation(
                    $chatId,
                    $userId,
                    new GenerateCardConversation(
                        GenerateCardConversationStep::SetResponsible,
                        $conversation->rawDescription,
                        $asap,
                    ),
                    true
                );

                return Request::sendMessage($data);

                // Сообщение с просьбой указать ответственного
            case GenerateCardConversationStep::SetResponsible:
                $dueDate = $text;

                $isDateOnly   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate);
                $isWithMinute = preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $dueDate);

                if (!$isDateOnly && !$isWithMinute) {
                    $data['text'] = 'Некорректный формат даты. Используй YYYY-MM-DD или YYYY-MM-DDTHH:MM';

                    return Request::sendMessage($data);
                }

                $format = $isDateOnly ? 'Y-m-d' : 'Y-m-d\TH:i';

                $dt = DateTimeImmutable::createFromFormat($format, $dueDate);

                if (false === $dt) {
                    $data['text'] = 'Некорректная дата или время';

                    return Request::sendMessage($data);
                }

                $users = $context->kas->getSpaceUsers($context->botUser);

                if (empty($users)) {
                    $data['text'] = 'Не найдено пользователей в выбранном пространстве! Добавьте и начните снова.';

                    return Request::sendMessage($data);
                }

                $buttons = [];
                foreach ($users as $user) {
                    $buttons[] = [$user->email];
                }

                /**
                 * @psalm-suppress TooManyArguments
                 */
                $keyboard = new Keyboard(...$buttons);

                $data['reply_markup'] = $keyboard;

                $context->bcs->getConversation(
                    $chatId,
                    $userId,
                    new GenerateCardConversation(
                        GenerateCardConversationStep::Confirm,
                        $conversation->rawDescription,
                        $conversation->asap,
                        $users,
                        $dueDate,
                        !$isDateOnly
                    ),
                    true
                );

                $data['text'] = $context->brs->generateCard($conversation);

                return Request::sendMessage($data);

                // Сообщение с подтверждением
            case GenerateCardConversationStep::Confirm:
                $email = $text;

                /**
                 * @var int
                 */
                $responsibleId = null;

                /**
                 * @var string
                 */
                $responsibleEmail = null;

                foreach ($conversation->users as $user) {
                    if (mb_strtolower($user->email) === mb_strtolower($email)) {
                        $responsibleId = $user->id;
                        $responsibleEmail = $user->email;
                        break;
                    }
                }

                if (null === $responsibleId || null === $responsibleEmail) {
                    $data['text'] = 'Выберите ответственного, используя кнопки ниже';

                    $buttons = [];
                    foreach ($conversation->users as $user) {
                        $buttons[] = [$user->email];
                    }

                    /**
                     * @psalm-suppress TooManyArguments
                     */
                    $keyboard = new Keyboard(...$buttons);

                    $data['reply_markup'] = $keyboard;

                    return Request::sendMessage($data);
                }

                $conversation->responsibleEmail = $responsibleEmail;

                $data['text'] = $context->brs->generateCard($conversation);

                $buttons = ['Начать заново', 'Подтвердить'];

                /**
                 * @psalm-suppress TooManyArguments
                 */
                $keyboard = new Keyboard(...$buttons);

                $data['reply_markup'] = $keyboard;

                $context->bcs->getConversation(
                    $chatId,
                    $userId,
                    new GenerateCardConversation(
                        GenerateCardConversationStep::Done,
                        $conversation->rawDescription,
                        $conversation->asap,
                        $conversation->users,
                        $conversation->dueDate,
                        $conversation->dueDateTimePresent,
                        $responsibleId,
                        $responsibleEmail
                    ),
                    true
                );

                return Request::sendMessage($data);

                // Сообщение с подтверждением
            case GenerateCardConversationStep::Done:
                $input = $text;

                switch ($input) {
                    case 'Начать заново':
                        $context->bcs->getConversation(
                            $chatId,
                            $userId,
                            new GenerateCardConversation(GenerateCardConversationStep::SetAsap),
                            true
                        );

                        $data['text'] = 'Диалог перезапущен. Введите описание новой задачи.';

                        return Request::sendMessage($data);
                    case 'Подтвердить':
                        $data['text'] = 'Подтверждено. Задача будет создана и ссылка на неё появится в чате.';

                        return Request::sendMessage($data);
                }

                $data['text'] = 'Пожалуйста, используйте кнопки: "Начать заново" или "Подтвердить".';

                return Request::sendMessage($data);
        }

        $data['text'] = $context->brs->unknown();

        return Request::sendMessage($data);
    }
}
