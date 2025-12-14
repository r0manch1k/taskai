<?php

declare(strict_types=1);

namespace App\Dto;

readonly class UserDto
{
    public function __construct(
        public ?int $id,
        public ?string $email,
    ) {
    }
}
