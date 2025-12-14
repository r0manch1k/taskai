<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class BotCacheService
{
    public function __construct(
        private TagAwareCacheInterface $botCache,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Отдаёт или устанавливает текущий диалог в кэш.
     * Предполагается, что для определенного пользователя в определённом чате
     * может быть только один действующий диалог.
     */
    public function getConversation(int $chatId, int $userId, ?object $conversation = null, bool $reset = false): ?object
    {
        $key = sprintf('%s_%s_conversation', $chatId, $userId);

        if ($reset) {
            $this->botCache->delete($key);
        }

        $cache = $this->botCache->get(
            $key,
            function (ItemInterface $item) use ($chatId, $userId, $conversation): object|null {
                $item->tag([sprintf('%s_%s', $chatId, $userId)]);
                $item->expiresAfter(600);

                return $conversation;
            }
        );

        return $cache;
    }

    public function invalidateBotUser(int $chatId, int $userId): void
    {
        $this->botCache->invalidateTags([sprintf('%s_%s', $chatId, $userId)]);
    }
}
