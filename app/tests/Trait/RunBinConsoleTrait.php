<?php

namespace App\Tests\Trait;

use Symfony\Component\Process\Process;

trait RunBinConsoleTrait
{
    private function runBinConsole(string $command, int $timeoutSeconds = 60, array $options = []): void
    {
        $cmd = [
            'php',
            $this->consolePath,
            $command,
            '--no-interaction',
            '--env=dev',
        ];

        foreach ($options as $key => $value) {
            if (is_int($key)) {
                $cmd[] = $value;
            } else {
                $cmd[] = sprintf('%s=%s', $key, $value);
            }
        }

        $process = new Process($cmd);
        $process->setTimeout($timeoutSeconds);
        $process->run();

        if (!$process->isSuccessful()) {
            $output = $process->getOutput();
            $error = $process->getErrorOutput();
            $this->fail(sprintf(
                'Console command failed: "%s". Exit code: %s. Output: %s. Error: %s',
                $command,
                $process->getExitCode(),
                $output,
                $error
            ));
        }
    }
}
