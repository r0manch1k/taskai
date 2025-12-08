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

class SelectBoardConversation
{
    public function __construct(
        public SelectBoardConversationStep $step,
        /**
         * @var SpaceDto[]
         */
        public array $spaces = [],
        /**
         * @var BoardDto[]
         */
        public $boards = [],
    ) {
    }
}
