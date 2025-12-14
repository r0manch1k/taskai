<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\GenerateCardMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GenerateCardMessageHandler
{
    public function __invoke(GenerateCardMessage $message)
    {
        // ... do some work - like sending an SMS message!
    }
}
