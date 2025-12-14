<?php

declare(strict_types=1);

namespace App\Dto;

readonly class BoardDto
{
    public function __construct(
        public ?int $id,
        public ?string $title,
        public ?string $description,
    ) {
    }
}
