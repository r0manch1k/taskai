<?php

declare(strict_types=1);

namespace App\Service;

use App\Bot\Conversation\NewCompanyConversation;
use App\Bot\Conversation\SelectBoardConversation;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class BotResponseService
{
    public function __construct(
        // private BotCacheService $botCacheService,
        // private LoggerInterface $logger,
        // private BotUserService $bus,
        private Environment $twig,
    ) {
    }

    public function start(): string
    {
        return $this->twig->render('messages/start.html.twig');
    }

    public function newCompany(NewCompanyConversation $conversation): string
    {
        return $this->twig->render('messages/new_company.html.twig', ['conversation' => $conversation]);
    }

    public function selectCompany(SelectBoardConversation $conversation): string
    {
        return $this->twig->render('messages/select_board.html.twig', ['conversation' => $conversation]);
    }

    public function error(): string
    {
        return $this->twig->render('messages/error.html.twig');
    }

    public function unknown(): string
    {
        return $this->twig->render('messages/unknown.html.twig');
    }
}
