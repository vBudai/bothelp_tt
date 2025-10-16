<?php

use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $framework): void {
    $count = (int) ($_SERVER['EVENTS_SHARDS_COUNT'] ?? 4);

    $queues = [];
    for ($i = 0; $i < $count; $i++) {
        $queues["events.shard.$i"] = [
            'binding_keys' => ["shard.$i"],
            'arguments' => [
                'x-single-active-consumer' => true,
            ],
        ];
    }

    $framework->messenger()->transport('events', [
        'dsn' => '%env(MESSENGER_TRANSPORT_DSN)%/events',
        'options' => [
            'exchange' => [
                'name' => 'events',
                'type' => 'direct',
            ],
            'queues' => $queues,
        ],
    ]);
};
