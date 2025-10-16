<?php

namespace App\Tests\Trait;

use Symfony\Component\Process\Process;

trait RunMessengerConsumerTrait
{
    public function runMessengerConsumeEvents(string $consolePath, string $exchange, string $queue): Process
    {
        $cmd = [
            'php',
            $consolePath,
            'messenger:consume',
            $exchange,
            '--no-interaction',
            '--env=dev',
            "--queues=$queue",
            '-vvv',
        ];

        $process = new Process($cmd);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->start();

        return $process;
    }
}
