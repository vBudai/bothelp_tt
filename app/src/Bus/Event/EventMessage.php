<?php

namespace App\Bus\Event;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage(transport: "events")]
readonly class EventMessage
{
    public function __construct(
        public int $id,
        public int $accountId,
    ) {
    }

    public function getRoutingKey(): string
    {
        return 'shard.' . $this->accountId % $_ENV['EVENTS_SHARDS_COUNT'];
    }
}
