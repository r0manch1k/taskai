<?php

declare(strict_types=1);

namespace App\Bot\State;

use App\Bot\Context;
use App\Bot\Conversation\Conversation;
use App\Bot\Conversation\NewCompanyConversation;
use App\Bot\Conversation\NewCompanyConversationStep;
use App\Entity\Company;
use InvalidArgumentException;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

final class NewCompanyState implements StateInterface
{
    public function handle(Context $context, Conversation $conversation): ServerResponse
    {
        if (!$conversation instanceof NewCompanyConversation) {
            throw new InvalidArgumentException('Ожидалось NewCompanyConversation');
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
            // Сообщение с просьбой указать домен
            case NewCompanyConversationStep::SetDomain:
                $data['text'] = $context->brs->newCompany($conversation);

                $context->bcs->getConversation(
                    $chatId,
                    $userId,
                    new NewCompanyConversation(
                        NewCompanyConversationStep::SetToken,
                    ),
                    true
                );

                return Request::sendMessage($data);

                // Сообщение с просьбой указать ключ доступа
            case NewCompanyConversationStep::SetToken:
                $domain = $text;

                if (!preg_match('/^[a-zA-Z0-9._+-]+$/', $domain)) {
                    $context->logger->info(sprintf('Пользователь %s ввёл невалидный домен "%s"', $context->botUser->getId(), $domain));
                    $data['text'] = 'Невалидный домен. Попробуйте ещё раз!';

                    return Request::sendMessage($data);
                }

                $data['text'] = $context->brs->newCompany($conversation);

                $context->bcs->getConversation(
                    $chatId,
                    $userId,
                    new NewCompanyConversation(
                        NewCompanyConversationStep::Done,
                        $domain
                    ),
                    true
                );

                return Request::sendMessage($data);

                // Сообщение с результатом проверки ключа доступа
            case NewCompanyConversationStep::Done:
                $token = $text;

                $kaitenUser = $context->kas->getCurrentUser($conversation->domain, $token);

                if (!$kaitenUser->id) {
                    $data['text'] = 'Ключ доступа не прошёл проверку. Введите другой!';

                    return Request::sendMessage($data);
                }

                $company = $context->botUser->isCompanyExists($conversation->domain);

                if (null == $company) {
                    $company = new Company();
                    $company->setDomain($conversation->domain);
                }

                $company->setUserID($kaitenUser->id);
                $company->setToken($token);

                $context->bus->setCompany($context->botUser, $company);

                $context->bcs->getConversation($chatId, $userId, null, true);

                $context->telegram->executeCommand('start');

                return Request::emptyResponse();
        }

        $data['text'] = $context->brs->unknown();

        return Request::sendMessage($data);
    }
}
