<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Command;

use Evolutive\Module\EvoStatusFlow\Service\LoggingService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CleanLogsCommand extends Command
{
    public const SUCCESS = 0;
    public const FAILURE = 1;
    public const INVALID = 2;

    /**
     * @param LoggingService $loggingService
     */
    public function __construct(public readonly LoggingService $loggingService)
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('evolutive:status-flow:clean-logs')
            ->setDescription('Clean up old logs from the database')
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Number of days to keep logs (overrides configuration)'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Evo Status Flow - Log Cleanup');

        // Check if database logging is enabled
        if (!$this->loggingService->isDatabaseLoggingEnabled()) {
            $io->warning('Database logging is currently disabled. No logs to clean up.');

            return self::SUCCESS;
        }

        // Get days from option or from configuration
        $days = $input->getOption('days');

        if ($days === null) {
            $days = $this->loggingService->getLogRetentionDays();
            $io->note(sprintf('Using configured retention period: %d days', $days));
        } else {
            $days = (int) $days;
            $io->note(sprintf('Using specified retention period: %d days', $days));
        }

        $io->section('Cleaning logs...');

        try {
            $deletedCount = $this->loggingService->cleanOldLogs();

            if ($deletedCount > 0) {
                $io->success(sprintf('Successfully deleted %d old log entries', $deletedCount));
            } else {
                $io->note('No logs needed to be deleted');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Error during log cleanup: %s', $e->getMessage()));

            return self::FAILURE;
        }
    }
}
