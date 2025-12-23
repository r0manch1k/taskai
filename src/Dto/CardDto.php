<?php

declare(strict_types=1);

namespace App\Dto;

readonly class CardDto
{
    public function __construct(
        public ?int $id,
        public string $title,
        public int $boardId,
        public bool $asap,
        public string $dueDate,
        public bool $dueDateTimePresent,
        public string $description,
        public int $ownerId,
        public int $responsibleId,
    ) {
    }
}
