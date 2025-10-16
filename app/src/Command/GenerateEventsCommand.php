<?php

namespace App\Command;

use App\Bus\Event\EventMessage;
use App\Service\Events\DispatchEventService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:generate-events',
    description: 'Generate events for 1000 accounts and dispatch it to RabbitMQ',
)]
class GenerateEventsCommand extends Command
{
    private const DEFAULT_EVENTS_COUNT = 10000;
    private const DEFAULT_MAX_ACCOUNT_ID = 1000;

    public function __construct(
        private readonly DispatchEventService $service,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'count',
                null,
                InputOption::VALUE_OPTIONAL,
                'Number of events to generate',
                self::DEFAULT_EVENTS_COUNT
            )
        ->addOption(
            'max-acc-id',
            null,
            InputOption::VALUE_OPTIONAL,
            'Max account id to generate',
            self::DEFAULT_MAX_ACCOUNT_ID
        );
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = (int) $input->getOption('count');
        $maxAccId = (int) $input->getOption('max-acc-id');
        for ($i = 1; $i <= $count; ++$i) {
            $event = new EventMessage($i, rand(1, $maxAccId));
            $this->service->dispatch($event);

            if (0 === $i % 100) {
                $output->writeln("Dispatched $i events...");
            }
        }

        return Command::SUCCESS;
    }
}
