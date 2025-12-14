<?php

declare(strict_types=1);

namespace App\Bot\Conversation;

use App\Dto\BoardDto;
use App\Dto\SpaceDto;

enum SelectBoardConversationStep: string
{
    case SetSpace = 'set_space';
    case SetBoard = 'set_board';
    case Done = 'done';
}

final class SelectBoardConversation extends Conversation
{
    public function __construct(
        SelectBoardConversationStep $step,
        /**
         * @var SpaceDto[]
         */
        public array $spaces = [],
        /**
         * @var BoardDto[]
         */
        public array $boards = [],
    ) {
        parent::__construct($step);
    }
}
