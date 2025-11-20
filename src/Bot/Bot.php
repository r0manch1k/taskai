<?php

declare(strict_types=1);

namespace App\Bot;

use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Exception\TelegramLogException;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\TelegramLog;

class Bot extends Telegram
{
    public function __construct(
        string $token,
        string $username,
    ) {
        parent::__construct($token, $username);

        try {
            $this->addCommandsPaths([__DIR__ . '/Command']);
        } catch (TelegramException $e) {
            // Log telegram errors
            TelegramLog::error($e->getMessage());
            // Uncomment this to output any errors (ONLY FOR DEVELOPMENT!)
            // echo $e;
        } catch (TelegramLogException $e) {
            // Uncomment this to output log initialisation errors (ONLY FOR DEVELOPMENT!)
            // echo $e;
        }
    }
}
