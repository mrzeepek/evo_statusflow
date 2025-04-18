<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Command;

use Evolutive\Module\EvoStatusFlow\CQRS\Command\StatusFlow\ProcessRulesCommand;
use Monolog\Logger;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ProcessStatusFlowCommand extends Command
{
    public const SUCCESS = 0;
    public const FAILURE = 1;
    public const INVALID = 2;

    /**
     * @var string
     */
    protected static $defaultName = 'evolutive:evo_statusflow:process';

    /**
     * @param CommandBusInterface $commandBus Command bus for executing CQRS commands
     * @param Logger $logger Logging service
     */
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly Logger $logger,
    ) {
        parent::__construct();
    }

    /**
     * Configure the command with its options and description
     */
    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Process status flow rules and apply transitions')
            ->addOption('rule-id', null, InputOption::VALUE_REQUIRED, 'Process only a specific rule')
            ->addOption('object-type', null, InputOption::VALUE_OPTIONAL, 'Process only specific object type')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run in dry-run mode without applying changes')
            ->setHelp(
                'This command processes status flow rules and applies status transitions based on configured rules.

Examples:
  bin/console evolutive:evo_statusflow:process                 # Process all active rules
  bin/console evolutive:evo_statusflow:process --rule-id=5     # Process only rule with ID 5
  bin/console evolutive:evo_statusflow:process --dry-run       # Test what changes would be made without applying them'
            );
    }

    /**
     * Execute the status flow rules processing command
     *
     * @param InputInterface $input Command input interface
     * @param OutputInterface $output Command output interface
     *
     * @return int Command return code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Processing Status Flow Rules');

        $ruleId = $input->getOption('rule-id') ? (int) $input->getOption('rule-id') : null;
        $objectType = $input->getOption('object-type');
        $dryRun = (bool) $input->getOption('dry-run');

        $this->logger->info('Starting status flow processing', [
            'rule_id' => $ruleId,
            'object_type' => $objectType,
            'dry_run' => $dryRun,
        ]);

        $command = new ProcessRulesCommand($objectType, $dryRun, $ruleId);

        $processedCount = $this->commandBus->handle($command);

        $message = sprintf(
            'Successfully processed %d status transitions',
            $processedCount
        );

        if ($ruleId) {
            $message = sprintf(
                'Successfully processed rule #%d with %d status transitions',
                $ruleId,
                $processedCount
            );
        }

        if ($dryRun) {
            $message .= ' (DRY RUN - no changes were made)';
        }

        $this->logger->info('Completed status flow processing', [
            'rule_id' => $ruleId,
            'processed_count' => $processedCount,
            'dry_run' => $dryRun,
        ]);

        $io->success($message);

        return self::SUCCESS;
    }
}
