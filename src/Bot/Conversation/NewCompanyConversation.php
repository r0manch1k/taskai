<?php

declare(strict_types=1);

namespace App\Bot\Conversation;

enum NewCompanyConversationStep: string
{
    case Start = 'Добавить компанию';
    case SetDomain = 'set_domain';
    case SetToken = 'set_token';
    case Done = 'done';
}

class NewCompanyConversation
{
    public function __construct(
        public NewCompanyConversationStep $step,
        public ?string $domain = null,
        public ?string $token = null,
    ) {
    }
}
