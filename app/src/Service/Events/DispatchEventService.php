<?php

namespace App\Service\Events;

use App\Bus\Event\EventMessage;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class DispatchEventService
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ){}

    /**
     * @throws ExceptionInterface
     */
    public function dispatch(EventMessage $eventMsg): void
    {
        $amqpStamp = new AmqpStamp($eventMsg->getRoutingKey());
        $this->messageBus->dispatch($eventMsg, [$amqpStamp]);
    }
}
