<?php

declare(strict_types=1);

namespace App\Bot;

use App\Entity\BotUser;
use App\Service\BotCacheService;
use App\Service\BotResponseService;
use App\Service\BotUserService;
use App\Service\CompanyService;
use App\Service\KaitenApiClient;
use Longman\TelegramBot\Telegram;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class Context
{
    public function __construct(
        public int $chatId,
        public BotUser $botUser,
        public Telegram $telegram,
        public ?string $text,
        public BotResponseService $botResponseService,
        public BotCacheService $botCacheService,
        public BotUserService $botUserService,
        public LoggerInterface $logger,
        public KaitenApiClient $kaitenApiClient,
        public CompanyService $companyService,
        public MessageBusInterface $messageBusInterface,
    ) {
    }
}
