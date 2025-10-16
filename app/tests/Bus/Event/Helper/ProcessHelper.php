<?php

namespace App\Tests\Bus\Event\Helper;

use App\Bus\Event\EventMessage;
use App\Tests\Trait\RunMessengerConsumerTrait;
use Symfony\Component\Process\Process;

class ProcessHelper
{
    use RunMessengerConsumerTrait;

    /**
     * @return Process[]
     */
    public function startWorkersProcessed(string $consolePath): array
    {
        $processes = [];
        for ($i = 0; $i < EventMessage::SHARDS_COUNT; $i++) {
            $processes[] = $this->runMessengerConsumeEvents(
                $consolePath,
                'events',
                "events.shard.$i",
            );
        }

        return $processes;
    }

    /**
     * @throws \Exception
     */
    public function waitTillWorkersProcessed(string $logFile, int $expectedCount): void
    {
        $lastCount = 0;
        $stableSince = time();

        while (true) {
            $count = 0;

            if (file_exists($logFile)) {
                $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
                foreach ($lines as $line) {
                    if (str_contains($line, 'Received event message')) {
                        $count++;
                    }
                }
            }

            dump("Обработано событий: $count");
            if ($count === $lastCount) {
                if ((time() - $stableSince) >= 3) {
                    if ($count == $expectedCount) {
                        return;
                    } else {
                        throw new \Exception("Обработано не то кол-во событий: $count/$expectedCount");
                    }
                }
            } else {
                $stableSince = time();
                $lastCount = $count;
            }

            sleep(2);
        }
    }

    /**
     * @param Process[] $processes
     */
    public function stopProcesses(array $processes): void
    {
        foreach ($processes as $process) {
            if ($process->isRunning()) {
                $process->signal(SIGKILL);
            }
        }
    }
}
