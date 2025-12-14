<?php

declare(strict_types=1);

namespace App\Bot\State;

use App\Bot\Context;
use App\Bot\Conversation\Conversation;
use Longman\TelegramBot\Entities\ServerResponse;

interface StateInterface
{
    public function handle(Context $context, Conversation $conversation): ServerResponse;
}
