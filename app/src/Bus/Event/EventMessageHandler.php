<?php

namespace App\Bus\Event;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class EventMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(EventMessage $message): void
    {
        $this->logger->debug('Received event message', ['id' => $message->id, 'accountId' => $message->accountId]);
        sleep(1);
    }
}
