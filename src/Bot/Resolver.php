<?php

declare(strict_types=1);

namespace App\Bot;

use App\Bot\Conversation\Conversation;
use App\Bot\Conversation\NewCompanyConversation;
use App\Bot\Conversation\SelectBoardConversation;
use App\Bot\State\NewCompanyState;
use App\Bot\State\SelectBoardState;
use App\Bot\State\StateInterface;

final class Resolver
{
    public function resolve(Conversation $conversation): StateInterface
    {
        return match (true) {
            $conversation instanceof NewCompanyConversation => new NewCompanyState(),
            $conversation instanceof SelectBoardConversation  => new SelectBoardState(),
        };
    }
}
