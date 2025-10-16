<?php

namespace App\Bus\Event;

readonly class EventMessage
{
    public const SHARDS_COUNT = 4;

    public function __construct(
        public int $id,
        public int $accountId,
    ){}

    public function getRoutingKey(): string
    {
        return 'shard.' . $this->accountId % self::SHARDS_COUNT;
    }
}
