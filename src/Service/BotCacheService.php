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
     *
     * @param object $conversation
     */
    public function getConversation(int $chat_id, int $user_id, ?object $conversation = null, bool $reset = false): ?object
    {
        $key = sprintf('%s_%s_conversation', $chat_id, $user_id);

        if ($reset) {
            $this->botCache->delete($key);
        }

        $cache = $this->botCache->get(
            $key,
            function (ItemInterface $item) use ($chat_id, $user_id, $conversation): object|null {
                $item->tag([sprintf('%s_%s', $chat_id, $user_id)]);
                $item->expiresAfter(600);

                return $conversation;
            }
        );

        return $cache;
    }

    public function invalidateBotUser(int $chat_id, int $user_id): void
    {
        $this->botCache->invalidateTags([sprintf('%s_%s', $chat_id, $user_id)]);
    }
}
