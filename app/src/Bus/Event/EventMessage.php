<?php

namespace App\Bus\Event;

readonly class EventMessage
{
    public function __construct(
        public int $id,
        public int $accountId,
    ) {
    }

    public function getRoutingKey(): string
    {
        return 'shard.'.$this->accountId % $_ENV['EVENTS_SHARDS_COUNT'];
    }
}
