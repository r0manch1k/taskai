<?php

declare(strict_types=1);

namespace App\Bot\Conversation;

enum GenerateCardConversationStep: string
{
    case Start = 'Создать карточку';
    case SetRawDescription = 'set_raw_description';
    case SetAsap = 'set_asap';
    case SetDueDate = 'set_due_date';
    case SetResponsible = 'set_responsible';
    case Done = 'done';
}

final class GenerateCardConversation extends Conversation
{
    public function __construct(
        GenerateCardConversationStep $step,
        public ?string $rawDescription = null,
        public ?bool $asap = null,
        public ?string $dueDate = null,
        public ?bool $dueDateTimePresent = null,
        public ?int $responsibleId = null,
    ) {
        parent::__construct($step);
    }
}
