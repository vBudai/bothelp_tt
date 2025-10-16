<?php

namespace App\Tests\Bus\Event;

use App\Tests\Bus\Event\Helper\ProcessHelper;
use App\Tests\Bus\Event\Helper\RabbitCleaner;
use App\Tests\Trait\RunBinConsoleTrait;
use App\Tests\Trait\RunMessengerConsumerTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EventOrderHandlingTest extends KernelTestCase
{
    use RunBinConsoleTrait;
    use RunMessengerConsumerTrait;

    private readonly ProcessHelper $processHelper;
    private readonly RabbitCleaner $rabbitCleaner;

    private readonly string $consolePath;
    private readonly string $logFile;

    private readonly int $eventsCount;
    private readonly int $maxAccountId;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->consolePath = dirname(__DIR__, 3).'/bin/console';
        $this->processHelper = new ProcessHelper();
        $this->rabbitCleaner = new RabbitCleaner();

        $this->eventsCount = (int) $_ENV['EVENTS_COUNT'];
        $this->maxAccountId = (int) $_ENV['MAX_ACCOUNT_ID'];

        $logsDir = self::getContainer()->getParameter('kernel.logs_dir');
        $this->logFile = $logsDir.'/dev.log';
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }

        $this->rabbitCleaner->deleteAllRabbitQueues();
        $this->runBinConsole(
            'app:generate-events',
            600,
            ['--count' => $this->eventsCount, '--max-acc-id' => $this->maxAccountId]
        );
    }

    /**
     * @throws \Exception
     */
    public function testEventHandlingOrder(): void
    {
        // Act
        dump('Запускаем воркеры');
        $processes = $this->processHelper->startWorkersProcessed();
        dump('Ждём пока завершатся');
        $this->processHelper->waitTillWorkersProcessed($this->logFile, $this->eventsCount);
        $this->processHelper->stopProcesses($processes);

        // Assert - проверка кол-ва обработанных событий и их порядка
        $lastIdPerAccount = [];
        if (!file_exists($this->logFile)) {
            $this->fail("Log file $this->logFile does not exist");
        }

        $totalProcessed = 0;
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            if (!str_contains($line, 'Received event message')) {
                continue;
            }

            ++$totalProcessed;
            preg_match('/"id":(\d+).*"accountId":(\d+)/', $line, $matches);
            if (3 !== count($matches)) {
                continue;
            }

            $eventId = (int) $matches[1];
            $accountId = (int) $matches[2];
            if (isset($lastIdPerAccount[$accountId])) {
                $this->assertGreaterThan(
                    minimum: $lastIdPerAccount[$accountId],
                    actual: $eventId,
                    message: "Event order is broken for account $accountId: previous id={$lastIdPerAccount[$accountId]}, current id=$eventId"
                );
            }

            $lastIdPerAccount[$accountId] = $eventId;
        }

        $this->assertSame(
            $this->eventsCount,
            $totalProcessed,
            "Expected exactly $this->eventsCount events to be processed"
        );
    }
}
