<?php

declare(strict_types=1);

namespace App\Bot\Conversation;

use UnitEnum;

abstract class Conversation
{
    public function __construct(
        public UnitEnum $step,
    ) {
    }
}
