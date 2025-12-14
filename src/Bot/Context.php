<?php

declare(strict_types=1);

namespace App\Bot;

use App\Entity\BotUser;
use App\Service\BotCacheService;
use App\Service\BotResponseService;
use App\Service\BotUserService;
use App\Service\CompanyService;
use App\Service\KaitenApiService;
use Longman\TelegramBot\Telegram;
use Psr\Log\LoggerInterface;

final class Context
{
    public function __construct(
        public int $chatId,
        public BotUser $botUser,
        public Telegram $telegram,
        public ?string $text,
        public BotResponseService $brs,
        public BotCacheService $bcs,
        public BotUserService $bus,
        public LoggerInterface $logger,
        public KaitenApiService $kas,
        public CompanyService $cs,
    ) {
    }
}
