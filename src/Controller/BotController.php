<?php

declare(strict_types=1);

namespace App\Controller;

use App\Bot\Bot;
use Longman\TelegramBot\Exception\TelegramException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BotController extends AbstractController
{
    public function __construct(
        private Bot $bot,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/bot/webhook', name: 'bot_webhook')]
    public function webhook(Request $request): Response
    {
        $this->logger->warning($request->getContent());
        try {
            $this->bot->handle();
        } catch (TelegramException $e) {
            $this->logger->error($e->getMessage());
        }

        return new Response('OK');
    }
}
