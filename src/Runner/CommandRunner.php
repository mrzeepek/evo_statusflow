<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Runner;

use Evolutive\Module\EvoStatusFlow\Exception\EvoStatusFlowException;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

class CommandRunner
{
    /**
     * @param KernelInterface $kernel Symfony kernel
     */
    public function __construct(
        private readonly KernelInterface $kernel,
    ) {
    }

    /**
     * Run the status flow process command
     *
     * @param int|null $ruleId Optional specific rule ID to process
     * @param bool $dryRun Whether to run in dry-run mode
     *
     * @return string Command output
     *
     * @throws CommandNotFoundException If the command is not found
     * @throws EvoStatusFlowException If the command execution fails
     */
    public function run(?int $ruleId = null, bool $dryRun = false): string
    {
        try {
            $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($this->kernel);
            $application->setAutoExit(false);

            $input = new ArrayInput([
                'command' => 'evolutive:evo_statusflow:process',
                '--rule-id' => $ruleId,
                '--dry-run' => $dryRun,
            ]);

            // Use BufferedOutput to capture the output
            $output = new BufferedOutput();
            $exitCode = $application->run($input, $output);

            if ($exitCode !== 0) {
                throw new EvoStatusFlowException('Command execution failed with exit code: ' . $exitCode);
            }

            return $output->fetch();
        } catch (CommandNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new EvoStatusFlowException('Unexpected error during command execution: ' . $e->getMessage(), 0, $e);
        }
    }
}
