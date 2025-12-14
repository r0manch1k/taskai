<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
class GenerateCardMessage
{
    public function __construct(
        private int $chatId,
        private string $rawDescription,
        private bool $asap,
        private string $dueDate,
        private bool $dueDateTimePresent,
        private int $ownerId,
        private int $responsibleId
    ) {
    }

    public function getRawDescription(): string
    {
        return $this->rawDescription;
    }
}
