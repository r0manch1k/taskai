<?php

declare(strict_types=1);

namespace App\Dto;

readonly class TagDto
{
    public function __construct(
        public ?int $id,
        public ?string $name,
    ) {
    }
}
