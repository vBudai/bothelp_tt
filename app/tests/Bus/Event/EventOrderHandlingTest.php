<?php
namespace App\Tests\Bus\Event;

use App\Bus\Event\EventMessage;
use App\Tests\Bus\Event\Helper\ProcessHelper;
use App\Tests\Trait\RunBinConsoleTrait;
use App\Tests\Trait\RunMessengerConsumerTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Process\Process;

class EventOrderHandlingTest extends KernelTestCase
{
    use RunBinConsoleTrait;
    use RunMessengerConsumerTrait;

    private ProcessHelper $processHelper;

    private const EVENTS_COUNT = 500;

    private string $consolePath;

    private string $logFile;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->consolePath = dirname(__DIR__, 3) . '/bin/console';
        $this->processHelper = new ProcessHelper();

        $logsDir = self::getContainer()->getParameter('kernel.logs_dir');
        $this->logFile = $logsDir . '/dev.log';
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }

        $this->runBinConsole(
            'app:generate-events',
            600,
            ['--count' => self::EVENTS_COUNT, '--max-acc-id' => 10]
        );
    }

    /**
     * @throws \Exception
     */
    public function testEventHandingOrder()
    {
        // Act
        dump('Запускаем воркеры');
        $processes = $this->processHelper->startWorkersProcessed($this->consolePath);
        dump('Ждём пока завершатся');
        $this->processHelper->waitTillWorkersProcessed($this->logFile, self::EVENTS_COUNT);
        $this->processHelper->stopProcesses($processes);


        // Assert - проверка кол-ва обработанных событий и их порядка
        $lastIdPerAccount = [];
        if (!file_exists($this->logFile)) {
            $this->fail("Log file $this->logFile does not exist");
        }

        $totalProcessed = 0;
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            if (str_contains($line, 'Received event message')) {
                ++$totalProcessed;
                preg_match('/"id":(\d+).*"accountId":(\d+)/', $line, $matches);
                if (count($matches) === 3) {
                    $eventId   = (int)$matches[1];
                    $accountId = (int)$matches[2];

                    if (isset($lastIdPerAccount[$accountId])) {
                        $this->assertGreaterThan(
                            $lastIdPerAccount[$accountId],
                            $eventId,
                            "Event order is broken for account $accountId: previous id={$lastIdPerAccount[$accountId]}, current id=$eventId"
                        );
                    }

                    $lastIdPerAccount[$accountId] = $eventId;
                }
            }
        }

        $this->assertSame(
            self::EVENTS_COUNT,
            $totalProcessed,
            "Expected exactly " . self::EVENTS_COUNT . " events to be processed"
        );
    }
}
