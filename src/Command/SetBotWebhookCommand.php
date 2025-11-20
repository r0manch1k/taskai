<?php

declare(strict_types=1);

namespace App\Command;

use App\Bot\Bot;
use Longman\TelegramBot\Exception\TelegramException;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'bot:webhook:set',
)]
class SetBotWebhookCommand extends Command
{
    private ?string $url;

    public function __construct(
        private Bot $bot,
    ) {
        parent::__construct();
    }

    public function interact(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('Введите URL:');
        $url = trim((string) readline('> '));

        if (!isset($url)) {
            throw new RuntimeException('Пустой URL');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Некорректный URL');
        }

        $this->url = $url;

    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = $this->bot->setWebhook($this->url . '/bot/webhook');

            if ($result->isOk()) {
                $output->writeln($result->getDescription());
            }
        } catch (TelegramException $e) {
            $output->writeln(sprintf('Ошибка: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
