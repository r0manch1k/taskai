<?php

declare(strict_types=1);

namespace App\Controller;

use App\Bot\Bot;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Exception\TelegramLogException;
use Longman\TelegramBot\TelegramLog;
use Psr\Log\LoggerInterface;

final class BotController extends AbstractController
{
    public function __construct(
        private Bot $bot,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/bot/webhook', name: 'bot_webhook')]
    public function webhook(Request $request): Response
    {
        $this->logger->warning($request->getContent());
        try {
            $this->bot->handle();
        } catch (TelegramException $e) {
            // Log telegram errors
            TelegramLog::error($e);
            // Uncomment this to output any errors (ONLY FOR DEVELOPMENT!)
            $this->logger->error($e);
        } catch (TelegramLogException $e) {
            // Uncomment this to output log initialisation errors (ONLY FOR DEVELOPMENT!)
            // echo $e;
            $this->logger->error($e);
        }

        return new Response('OK');
    }
}
