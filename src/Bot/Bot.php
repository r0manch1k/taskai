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
    ) {
        parent::__construct($token, $username);

        $this->addCommandsPaths([__DIR__ . '/Command']);

        // Не нашёл как сделать нормальный Dependency Injection в команды
        $commands = $this->getCommandsList();
        $logger->critical(json_encode($commands, JSON_PRETTY_PRINT));
        foreach ($commands as $name => $_) {
            $this->setCommandConfig($name, [
                'brs' => $this->brs,
                'bcs' => $this->bcs,
                'bus' => $this->bus,
                'logger' => $this->logger,
                'kas' => $this->kas,
                'cs' => $this->cs,
            ]);
        }
    }
}
