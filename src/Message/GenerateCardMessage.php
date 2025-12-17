<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\BotUser;
use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
class GenerateCardMessage
{
    public function __construct(
        private int $spaceId,
        private int $chatId,
        private BotUser $botUser,
        private string $rawDescription,
        private bool $asap,
        private string $dueDate,
        private bool $dueDateTimePresent,
        private int $ownerId,
        private int $responsibleId,
    ) {
    }

    public function getChatId(): int
    {
        return $this->chatId;
    }

    public function getSpaceId(): int
    {
        return $this->spaceId;
    }

    public function getBotUser(): BotUser
    {
        return $this->botUser;
    }

    public function getRawDescription(): string
    {
        return $this->rawDescription;
    }

    public function getAsap(): bool
    {
        return $this->asap;
    }

    public function getDueDate(): string
    {
        return $this->dueDate;
    }

    public function getDueDateTimePresent(): bool
    {
        return $this->dueDateTimePresent;
    }

    public function getOwnerId(): int
    {
        return $this->ownerId;
    }

    public function getResponsibleId(): int
    {
        return $this->responsibleId;
    }
}
