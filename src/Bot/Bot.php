<?php

declare(strict_types=1);

namespace App\Bot;

use App\Service\BotCacheService;
use App\Service\BotResponseService;
use App\Service\BotUserService;
use App\Service\CompanyService;
use App\Service\KaitenApiService;
use Longman\TelegramBot\Telegram;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class Bot extends Telegram
{
    public function __construct(
        string $token,
        string $username,
        private BotResponseService $brs,
        private BotCacheService $bcs,
        private BotUserService $bus,
        private LoggerInterface $logger,
        private KaitenApiService $kas,
        private CompanyService $cs,
        private MessageBusInterface $mbus,
    ) {
        parent::__construct($token, $username);

        $this->addCommandsPaths([__DIR__ . '/Command']);

        // Не нашёл как сделать нормальный Dependency Injection в команды
        $commands = $this->getCommandsList();

        foreach ($commands as $name => $_) {
            $this->setCommandConfig($name, [
                'brs' => $this->brs,
                'bcs' => $this->bcs,
                'bus' => $this->bus,
                'logger' => $this->logger,
                'kas' => $this->kas,
                'cs' => $this->cs,
                'mbus' => $this->mbus,
            ]);
        }
    }

    public function sendMessage(int $chatId, string $text): void
    {
        $token = $this->getApiKey();
        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        $postData = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        $options = [
            'http' => [
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($postData),
                'timeout' => 5,
            ],
        ];

        $context  = stream_context_create($options);

        $result = @file_get_contents($url, false, $context);

        if (false === $result) {
            $this->logger->error("Не удалось отправить сообщение в чат {$chatId} через HTTP.");
        } else {
            $response = json_decode($result, true);
            if (isset($response['ok']) && true === $response['ok']) {
                $this->logger->info("Сообщение успешно отправлено в чат {$chatId} через HTTP.");
            } else {
                $this->logger->error("Не удалось отправить сообщение в чат {$chatId} через HTTP. Ответ: " . $result);
            }
        }
    }
}
